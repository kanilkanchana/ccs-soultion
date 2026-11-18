<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class CcsStockTrialTest extends TestCase
{
    private string $backupTestRoot;
    protected function tearDown(): void {
        if(isset($this->backupTestRoot))\Illuminate\Support\Facades\File::deleteDirectory($this->backupTestRoot);
        parent::tearDown();
    }
    protected function setUp(): void {
        parent::setUp();$this->backupTestRoot=sys_get_temp_dir().'/ccs-isolated-'.Str::uuid();config(['ccs_backups.root'=>$this->backupTestRoot]);config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array','auth.defaults.guard'=>'web']);DB::purge('sqlite');
        $this->artisan('migrate',['--database'=>'sqlite','--force'=>true])->assertExitCode(0);
        DB::table('users')->insert(['id'=>1,'name'=>'Test staff','email'=>'stock@example.test','password'=>Hash::make('test-staff-password')]);
        DB::table('customers')->insert(['id'=>1,'customer_code'=>'TEST-C1','name'=>'Test customer']);
        DB::table('vehicles')->insert(['id'=>1,'customer_id'=>1,'vehicle_code'=>'TEST-V1','registration_number'=>'TEST-001','make'=>'Toyota','model'=>'Prius']);
        foreach([1,2,3] as $id)DB::table('repair_jobs')->insert(['id'=>$id,'vehicle_id'=>1,'job_number'=>'TEST-J'.$id,'complaint'=>'Test only']);
        DB::table('inventory_items')->insert(['id'=>1,'sku'=>'TEST-P1','name'=>'Test serialized part','selling_price_lkr'=>10000,'has_serial'=>true,'active'=>true,'quantity_on_hand'=>2]);
        foreach(['00123ABC','00456DEF'] as $serial)DB::table('stock_units')->insert(['inventory_item_id'=>1,'serial_number'=>$serial,'serial_key'=>strtoupper($serial),'status'=>'available']);
        DB::table('ccs_feature_settings')->where('key','stock_pos')->update(['enabled'=>true]);
        $this->actingAs(User::findOrFail(1),'web');
    }
    private function payload(int $job=1,array $extra=[]): array {
        return array_replace(['repair_job_id'=>$job,'description'=>['Test serialized part'],'quantity'=>[1],'unit_price'=>[10000],'inventory_item_id'=>[1],'stock_unit_id'=>[1],'discount'=>0,'initial_received'=>0,'initial_request_token'=>(string)Str::uuid()],$extra);
    }
    public function test_serial_snapshot_and_partial_payments_share_one_stock_sale(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>6000]))->assertSessionHasNoErrors();
        $invoice=DB::table('invoices')->first();$this->assertSame('partially_paid',$invoice->status);
        $this->assertDatabaseHas('invoice_items',['invoice_id'=>$invoice->id,'serial_number'=>'00123ABC','stock_managed'=>true]);
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'sold']);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>1]);
        $this->post('/ccs/invoices/'.$invoice->id.'/payments',['amount_lkr'=>1000,'method'=>'cash','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->assertEquals(7000,DB::table('payments')->where('invoice_id',$invoice->id)->sum('amount_lkr'));
        $this->assertEquals(3000,$invoice->total_lkr-DB::table('payments')->sum('amount_lkr'));
        $this->assertSame(1,DB::table('stock_movements')->count());
        $this->get('/ccs/invoices/'.$invoice->id)->assertOk()->assertSee('00123ABC');
    }
    public function test_partial_checkout_invoice_renders_with_receipts_warranty_and_print_settings(): void {
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();$invoice=DB::table('invoices')->first();
        $this->get('/ccs/invoices/'.$invoice->id)->assertOk()->assertSee('Invoice Print Settings')->assertSee('4,000.00')->assertSee('6,000.00')->assertSee('00123ABC');
        $this->post('/ccs/invoices/'.$invoice->id.'/payments',['amount_lkr'=>1000,'method'=>'cash','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->get('/ccs/invoices/'.$invoice->id)->assertOk()->assertSee('5,000.00')->assertSee('4,000.00')->assertSee('1,000.00');
        $this->get('/ccs/invoices/'.$invoice->id.'/print')->assertOk()->assertSee('00123ABC');
        $this->assertSame(2,DB::table('payments')->count());$this->assertSame(1,DB::table('stock_movements')->count());$this->assertSame(1,DB::table('warranties')->count());
    }
    public function test_draft_invoice_renders_the_nested_actual_serial_form(): void {
        $this->post('/ccs/pos',$this->payload())->assertSessionHasNoErrors();$invoice=DB::table('invoices')->first();
        DB::table('invoice_items')->insert(['invoice_id'=>$invoice->id,'description'=>'Manual serial test','quantity'=>1,'unit_price_lkr'=>0,'line_total_lkr'=>0,'requires_serial'=>true,'stock_managed'=>false]);
        $this->get('/ccs/invoices/'.$invoice->id)->assertOk()->assertSee('Complete Actual Serials')->assertSee('Save Draft Serials')->assertSee('Invoice Print Settings');
    }
    public function test_common_parts_document_shows_abs_serial_and_saved_details_without_battery_classification(): void {
        DB::table('inventory_items')->where('id',1)->update(['name'=>'ABS Pump Assembly','condition'=>'Reconditioned','item_year'=>'2021']);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();
        $this->get('/ccs/jobs/1/battery')->assertOk()->assertSee('Service / Parts Record')->assertSee('Reading / Details')->assertSee('00123ABC')->assertViewHas('documentRows',function($rows){return count($rows)===1&&$rows[0]['serial']==='00123ABC'&&str_contains($rows[0]['details'],'Reconditioned')&&$rows[0]['position']===null;});
        $this->assertSame(0,DB::table('battery_service_records')->count());$this->assertSame(1,DB::table('stock_movements')->count());
        $this->get('/ccs/jobs/2/battery')->assertOk()->assertDontSee('00123ABC');
    }
    public function test_common_document_combines_module_voltage_and_billed_parts_without_duplicate_module_serial(): void {
        $record=DB::table('battery_service_records')->insertGetId(['repair_job_id'=>1,'vehicle_id'=>1,'recorded_by'=>1,'request_token'=>(string)Str::uuid(),'service_date'=>today()->toDateString(),'module_count'=>1,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('battery_module_readings')->insert(['battery_service_record_id'=>$record,'position'=>1,'serial_number'=>'00123ABC','voltage'=>0]);
        DB::table('inventory_items')->where('id',1)->update(['is_battery_module'=>true]);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();
        $this->get('/ccs/jobs/1/battery')->assertOk()->assertViewHas('documentRows',fn($rows)=>count($rows)===1&&$rows[0]['serial']==='00123ABC'&&$rows[0]['details']==='0 V'&&$rows[0]['position']===1);
    }
    public function test_common_document_excludes_void_parts_and_other_jobs_when_viewing_history(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();
        $record=DB::table('battery_service_records')->insertGetId(['repair_job_id'=>2,'vehicle_id'=>1,'recorded_by'=>1,'request_token'=>(string)Str::uuid(),'service_date'=>today()->toDateString(),'module_count'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $this->get('/ccs/jobs/1/battery?record='.$record)->assertOk()->assertViewHas('recordJob',fn($j)=>(int)$j->id===2)->assertDontSee('00123ABC');
        DB::table('invoices')->update(['status'=>'void']);$this->get('/ccs/jobs/1/battery')->assertOk()->assertDontSee('00123ABC');
    }
    public function test_available_serial_edit_remove_restore_keeps_quantity_and_audit_consistent(): void {
        $this->post('/ccs/stock/1/units/1/update',['serial_number'=>'000CORRECTED'])->assertSessionHasNoErrors();$this->assertDatabaseHas('stock_units',['id'=>1,'serial_number'=>'000CORRECTED','status'=>'available']);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>2]);
        $this->post('/ccs/stock/1/units/1/update',['serial_number'=>'00456def'])->assertSessionHasErrors('serial_number');
        $this->post('/ccs/stock/1/units/1/remove',['confirmation'=>1])->assertSessionHasNoErrors();$this->post('/ccs/stock/1/units/1/remove',['confirmation'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'removed']);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>1]);$this->assertSame(1,DB::table('stock_movements')->where('reason','serial_removed')->count());
        $this->post('/ccs/stock/1/units/1/restore')->assertSessionHasNoErrors();$this->post('/ccs/stock/1/units/1/restore')->assertSessionHasNoErrors();$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>2]);$this->assertSame(1,DB::table('stock_movements')->where('reason','serial_restored')->count());
        $this->assertDatabaseHas('audit_logs',['action'=>'stock_serial_corrected']);
    }
    public function test_sold_serials_and_cashier_stock_mutations_are_protected(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();
        $this->post('/ccs/stock/1/units/1/update',['serial_number'=>'CHANGE-SOLD'])->assertSessionHasErrors('serial_number');$this->post('/ccs/stock/1/units/1/remove',['confirmation'=>1])->assertSessionHasErrors('serial_number');
        $this->assertDatabaseHas('stock_units',['id'=>1,'serial_number'=>'00123ABC','status'=>'sold']);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>1]);
        $user=User::findOrFail(1);$user->ccs_role='cashier';$user->save();$this->actingAs($user);$this->post('/ccs/stock/1/units/2/update',['serial_number'=>'CASHIER'])->assertForbidden();$this->post('/ccs/stock/1/units/2/remove',['confirmation'=>1])->assertForbidden();
    }
    public function test_manual_warranty_can_open_without_selected_customer_and_loads_scoped_jobs(): void {
        $this->get('/ccs/warranties')->assertOk()->assertSee('manualWarrantyCustomer',false)->assertSee('openManualWarranty',false);
        DB::table('customers')->insert(['id'=>2,'customer_code'=>'OTHER-C2','name'=>'Other customer']);DB::table('vehicles')->insert(['id'=>2,'customer_id'=>2,'vehicle_code'=>'OTHER-V2','registration_number'=>'OTHER-002','make'=>'Honda','model'=>'Fit']);DB::table('repair_jobs')->insert(['id'=>93,'vehicle_id'=>2,'job_number'=>'OTHER-J93','complaint'=>'Other customer job']);
        $this->getJson('/ccs/warranties/customer-jobs?customer_id=1')->assertOk()->assertJsonCount(3,'jobs')->assertJsonMissing(['job_number'=>'OTHER-J93']);
        $this->getJson('/ccs/warranties/customer-jobs?customer_id=2')->assertOk()->assertJsonCount(1,'jobs')->assertJsonPath('jobs.0.job_number','OTHER-J93');
    }
    public function test_same_unit_cannot_be_sold_to_two_jobs(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>10000]))->assertSessionHasNoErrors();
        $this->post('/ccs/pos',$this->payload(2,['initial_received'=>10000]))->assertSessionHasErrors('stock');
        $this->assertSame(1,DB::table('invoices')->count());$this->assertSame(1,DB::table('payments')->count());
    }
    public function test_cancel_draft_releases_serial_without_deleting_history(): void {
        $this->post('/ccs/pos',$this->payload())->assertSessionHasNoErrors();$id=DB::table('invoices')->value('id');
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'reserved']);
        $this->post('/ccs/invoices/'.$id.'/cancel-stock-draft',['confirmation'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'available','invoice_id'=>null]);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>2]);
        $this->assertDatabaseHas('invoices',['id'=>$id,'status'=>'void']);$this->assertSame(1,DB::table('invoice_items')->count());
        $this->post('/ccs/pos',$this->payload(2))->assertSessionHasNoErrors();
    }
    public function test_finalizing_reservation_does_not_deduct_twice(): void {
        $this->post('/ccs/pos',$this->payload())->assertSessionHasNoErrors();$id=DB::table('invoices')->value('id');
        $this->post('/ccs/invoices/'.$id.'/finalize',['confirmation'=>1,'amount_received'=>0,'method'=>'cash','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'sold']);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>1]);
    }
    public function test_disabled_trial_keeps_manual_pos_available(): void {
        DB::table('ccs_feature_settings')->where('key','stock_pos')->update(['enabled'=>false]);
        $this->post('/ccs/pos',$this->payload())->assertSessionHasErrors('stock');
        $this->post('/ccs/pos',$this->payload(1,['inventory_item_id'=>[null],'stock_unit_id'=>[null]]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>2]);$this->assertSame(0,DB::table('stock_movements')->count());
    }
    public function test_overselling_rolls_back_invoice_and_stock_together(): void {
        DB::table('inventory_items')->insert(['id'=>2,'sku'=>'TEST-P2','name'=>'Nonserial part','selling_price_lkr'=>100,'has_serial'=>false,'active'=>true,'quantity_on_hand'=>1]);
        $this->post('/ccs/pos',$this->payload(1,['inventory_item_id'=>[2],'stock_unit_id'=>[null],'quantity'=>[2],'unit_price'=>[100]]))->assertSessionHasErrors('stock');
        $this->assertSame(0,DB::table('invoices')->count());$this->assertSame(0,DB::table('payments')->count());$this->assertDatabaseHas('inventory_items',['id'=>2,'quantity_on_hand'=>1]);
    }
    public function test_stale_price_is_rejected_and_serials_are_not_generated(): void {
        $this->post('/ccs/pos',$this->payload(1,['unit_price'=>[1]]))->assertSessionHasErrors('stock');
        $this->assertDatabaseHas('stock_units',['serial_number'=>'00123ABC']);$this->assertSame(2,DB::table('stock_units')->count());
    }
    public function test_customer_search_includes_only_owned_finalized_stock_serials(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>10000]))->assertSessionHasNoErrors();
        \App\Models\CustomerPortalAccount::create(['customer_id'=>1,'username'=>'stock-customer','password'=>'test-customer-password','active'=>true,'access_version'=>1]);
        $this->postJson('/customer/login',['username'=>'stock-customer','password'=>'test-customer-password'])->assertOk();
        $this->getJson('/customer/api/search?serial_number=00123abc')->assertOk()->assertJsonCount(1,'parts')->assertJsonPath('parts.0.serial_number','00123ABC');
        DB::table('customers')->insert(['id'=>2,'customer_code'=>'TEST-C2','name'=>'Other customer']);
        DB::table('vehicles')->where('id',1)->update(['customer_id'=>2]);
        $this->getJson('/customer/api/search?serial_number=00123ABC')->assertOk()->assertJsonCount(0,'parts');
    }

    public function test_job_popup_searches_customer_id_name_and_car_model(): void {
        foreach(['TEST-C1','Test customer','TEST-001','Prius'] as $query){
            $this->getJson('/ccs/pos/jobs/search?q='.urlencode($query))->assertOk()->assertJsonCount(3,'data');
        }
        $this->post('/ccs/pos',$this->payload(1))->assertSessionHasNoErrors();
        $this->getJson('/ccs/pos/jobs/search?q=TEST-J1')->assertJsonCount(0,'data');
    }
    public function test_registered_barcode_finds_the_part_but_unknown_code_does_not(): void {
        DB::table('inventory_items')->where('id',1)->update(['barcode'=>'TEST-BARCODE-001']);
        $this->getJson('/ccs/stock/catalog?q=TEST-BARCODE-001')->assertOk()->assertJsonPath('data.0.id',1);
        $this->getJson('/ccs/stock/catalog?q=UNKNOWN-CODE')->assertJsonCount(0,'data');
    }
    public function test_customer_supplied_serial_is_saved_without_deducting_shop_stock(): void {
        $this->post('/ccs/pos',$this->payload(1,['inventory_item_id'=>[null],'stock_unit_id'=>[null],'manual_serial'=>['CUSTOMER-0007'],'item_origin'=>['customer_supplied'],'description'=>['Customer supplied ABS part'],'unit_price'=>[0]]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoice_items',['serial_number'=>'CUSTOMER-0007','item_origin'=>'customer_supplied','stock_managed'=>false]);
        $this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>2]);
        $this->assertSame(0,DB::table('stock_movements')->count());
    }

    public function test_repair_job_vehicle_search_auto_selects_unique_identifiers_but_not_models(): void {
        DB::table('customers')->where('id',1)->update(['phone'=>'0771234567']);
        foreach(['TEST-C1','0771234567','TEST-001'] as $q)$this->getJson('/ccs/vehicles/search?q='.urlencode($q))->assertOk()->assertJsonPath('auto_select',true)->assertJsonPath('data.0.id',1);
        $this->getJson('/ccs/vehicles/search?q=Prius')->assertJsonPath('mode','model')->assertJsonPath('auto_select',false)->assertJsonCount(1,'data');
        DB::table('vehicles')->insert(['id'=>2,'customer_id'=>1,'vehicle_code'=>'TEST-V2','registration_number'=>'TEST-002','make'=>'Toyota','model'=>'Prius']);
        $this->getJson('/ccs/vehicles/search?q=Prius')->assertJsonCount(2,'data')->assertJsonPath('auto_select',false);
        $this->getJson('/ccs/vehicles/search?q=0771234567')->assertJsonCount(2,'data')->assertJsonPath('auto_select',false);
        $this->getJson('/ccs/vehicles/search?q=TEST-001')->assertJsonPath('auto_select',true)->assertJsonCount(1,'data');
    }

    private function warrantyPackage(string $name='Stock package',int $months=36,?int $distance=null): int {
        return DB::table('warranty_rules')->insertGetId(['name'=>$name,'category'=>'hybrid_battery','coverage_kind'=>'new_battery','duration_months'=>$months,'distance_km'=>$distance,'free_service_count'=>3,'limit_basis'=>$distance?'whichever_first':'time_first','terms'=>'Sample confirmed terms only','approved_by_owner'=>true,'active'=>true]);
    }
    public function test_stock_sale_creates_one_customer_card_and_later_payments_do_not_duplicate_it(): void {
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();
        $invoice=DB::table('invoices')->first();$card=DB::table('warranties')->first();$this->assertNotNull($card);$this->assertNotNull($card->stock_sale_item_id);
        $snapshot=json_decode($card->terms_snapshot,true);$this->assertFalse($snapshot['provisional']);$this->assertSame('00123ABC',$snapshot['covered_serial']);$this->assertSame(3,$snapshot['free_service_count']);
        $this->post('/ccs/invoices/'.$invoice->id.'/payments',['amount_lkr'=>1000,'method'=>'cash','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        DB::transaction(fn()=>app(\App\Services\CcsStockWarrantyService::class)->issueInvoice($invoice->id,1));
        $this->assertSame(1,DB::table('warranties')->count());
        $this->get('/ccs/warranties?customer_id=1')->assertOk()->assertSee('00123ABC')->assertSee('CCS-W')->assertDontSee('Warranty Rules &amp; Owner Setup',false);
        $this->get('/ccs/warranties')->assertOk()->assertDontSee('00123ABC');
    }
    public function test_serial_sale_without_saved_package_shows_why_no_warranty_card_exists(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();$this->assertSame(0,DB::table('warranties')->count());
        $this->get('/ccs/warranties?customer_id=1')->assertOk()->assertViewHas('partsWithoutCards',fn($parts)=>$parts->count()===1&&$parts->first()->serial_number==='00123ABC'&&$parts->first()->sale_warranty_snapshot===null)->assertSee('No warranty package was saved with this sale.')->assertSee('00123ABC');
        // Assigning a default later is not consent to alter an already issued sale.
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->get('/ccs/warranties?customer_id=1')->assertOk()->assertSee('No warranty package was saved with this sale.');$this->assertSame(0,DB::table('warranties')->count());
        DB::table('customers')->insert(['id'=>2,'customer_code'=>'OTHER-OWNER','name'=>'Other owner']);
        $this->get('/ccs/warranties?customer_id=2')->assertOk()->assertViewHas('partsWithoutCards',fn($parts)=>$parts->isEmpty())->assertDontSee('00123ABC');
    }
    public function test_automatic_partial_sale_card_is_not_listed_as_a_part_without_coverage(): void {
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();
        $this->get('/ccs/warranties?customer_id=1')->assertOk()->assertViewHas('partsWithoutCards',fn($parts)=>$parts->isEmpty());$this->assertSame(1,DB::table('warranties')->count());
    }
    public function test_draft_freezes_package_and_issues_only_at_finalization(): void {
        $rule=$this->warrantyPackage('Original package',12);DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->post('/ccs/pos',$this->payload())->assertSessionHasNoErrors();$id=DB::table('invoices')->value('id');
        $this->assertSame(0,DB::table('warranties')->count());
        $other=$this->warrantyPackage('Future package',24);DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$other]);
        $this->post('/ccs/invoices/'.$id.'/finalize',['confirmation'=>1,'amount_received'=>0,'method'=>'cash','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $card=DB::table('warranties')->first();$this->assertSame($rule,(int)$card->warranty_rule_id);$this->assertSame('Original package',json_decode($card->terms_snapshot,true)['name']);
        $this->assertSame(1,DB::table('warranties')->count());
    }
    public function test_cancelled_stock_draft_never_issues_warranty(): void {
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->post('/ccs/pos',$this->payload())->assertSessionHasNoErrors();$id=DB::table('invoices')->value('id');
        $this->post('/ccs/invoices/'.$id.'/cancel-stock-draft',['confirmation'=>1])->assertSessionHasNoErrors();
        DB::transaction(fn()=>app(\App\Services\CcsStockWarrantyService::class)->issueInvoice($id,1));$this->assertSame(0,DB::table('warranties')->count());
    }
    public function test_distance_card_waits_for_mileage_without_losing_sale_or_payment(): void {
        $rule=$this->warrantyPackage('Distance package',12,30000);DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>10000]))->assertSessionHasNoErrors();
        $card=DB::table('warranties')->first();$this->assertTrue(json_decode($card->terms_snapshot,true)['provisional']);$this->assertNull($card->mileage_limit_km);$this->assertSame(1,DB::table('payments')->count());
        config(['ccs_warranty.approver_emails'=>['stock@example.test']]);
        $this->post('/ccs/warranties/'.$card->id.'/approve',['confirmation'=>1,'starting_mileage_km'=>120000])->assertSessionHasNoErrors();
        $card=DB::table('warranties')->first();$this->assertFalse(json_decode($card->terms_snapshot,true)['provisional']);$this->assertSame(150000,(int)$card->mileage_limit_km);
    }
    public function test_customer_has_multiple_stock_warranty_cards_for_repeat_purchases(): void {
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>10000]))->assertSessionHasNoErrors();
        $this->post('/ccs/pos',$this->payload(2,['initial_received'=>10000,'stock_unit_id'=>[2]]))->assertSessionHasNoErrors();
        $this->assertSame(2,DB::table('warranties')->count());$this->get('/ccs/warranties?customer_id=1')->assertOk()->assertSee('00123ABC')->assertSee('00456DEF');
        \App\Models\CustomerPortalAccount::create(['customer_id'=>1,'username'=>'warranty-customer','password'=>'test-customer-password','active'=>true,'access_version'=>1]);
        $this->postJson('/customer/login',['username'=>'warranty-customer','password'=>'test-customer-password'])->assertOk();
        $this->getJson('/customer/api/vehicles/1/warranties')->assertOk()->assertJsonCount(2,'data')->assertJsonPath('data.0.free_services.included',3)->assertJsonStructure(['data'=>[['card_status'=>['card_number','remaining_days','remaining_km']]]]);
    }
    public function test_card_day_counts_are_inclusive_and_never_claim_coverage(): void {
        \Illuminate\Support\Carbon::setTestNow('2026-10-26 12:00:00');
        try{
            $record=(object)['id'=>1,'starts_on'=>'2026-10-01','expires_on'=>'2026-10-26','mileage_limit_km'=>150000];
            $service=app(\App\Services\CcsWarrantyCardStatus::class);
            $status=$service->summary($record,['provisional'=>false],120000);$this->assertSame(1,$status['remaining_days']);$this->assertSame(30000,$status['remaining_km']);
            $record->expires_on='2026-10-25';$status=$service->summary($record,['provisional'=>false]);$this->assertSame(0,$status['remaining_days']);$this->assertSame('time_elapsed',$status['state']);
            $status=$service->summary($record,['provisional'=>false,'coverage_kind'=>'no_warranty']);$this->assertNull($status['remaining_days']);$this->assertSame('no_warranty',$status['state']);
        }finally{\Illuminate\Support\Carbon::setTestNow();}
    }
    public function test_payment_overview_uses_actual_receipts_and_links_to_existing_invoice_actions(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>6000]))->assertSessionHasNoErrors();
        $invoice=DB::table('invoices')->first();
        $this->get('/ccs/payments?q='.urlencode($invoice->invoice_number))->assertOk()->assertSee($invoice->invoice_number)->assertSee('6,000.00')->assertSee('4,000.00')->assertSee(route('ccs.invoices.show',$invoice->id),false);
        $this->get('/ccs/payments?status=paid')->assertOk()->assertDontSee($invoice->invoice_number);
        $this->get('/ccs/payments?status=invalid')->assertSessionHasErrors('status');
        $this->post('/ccs/invoices/'.$invoice->id.'/payments',['amount_lkr'=>1000,'method'=>'cash','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->get('/ccs/payments?q='.urlencode($invoice->invoice_number))->assertOk()->assertSee('7,000.00')->assertSee('3,000.00');
    }
    public function test_registration_to_job_sale_payment_and_warranty_keeps_one_vehicle_identity(): void {
        $this->post('/ccs/customers',['name'=>'Workflow client','phone'=>'0771234567','include_vehicle'=>1,'registration_number'=>'FLOW-001','make'=>'Toyota','model'=>'Aqua','current_mileage_km'=>100000,'next_action'=>'job'])->assertSessionHasNoErrors();
        $customer=DB::table('customers')->where('name','Workflow client')->first();$vehicle=DB::table('vehicles')->where('customer_id',$customer->id)->first();
        $this->get('/ccs/jobs?new=1&vehicle_id='.$vehicle->id)->assertOk()->assertSee('FLOW-001')->assertSee($customer->customer_code)->assertSee('Last recorded mileage');
        $this->post('/ccs/jobs',['vehicle_id'=>$vehicle->id,'complaint'=>'Workflow test','mileage_at_service_km'=>102000,'next_action'=>'pos'])->assertSessionHasNoErrors();
        $job=DB::table('repair_jobs')->where('vehicle_id',$vehicle->id)->first();$this->get('/ccs/pos?job_id='.$job->id)->assertOk()->assertViewHas('preselectedJob',fn($j)=>(int)$j->id===(int)$job->id);
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        $this->post('/ccs/pos',$this->payload($job->id,['initial_received'=>4000]))->assertSessionHasNoErrors();$invoice=DB::table('invoices')->where('repair_job_id',$job->id)->first();
        $this->post('/ccs/invoices/'.$invoice->id.'/payments',['amount_lkr'=>1000,'method'=>'cash','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->assertSame(5000,(int)DB::table('payments')->where('invoice_id',$invoice->id)->sum('amount_lkr'));$this->assertSame(2,DB::table('payments')->where('invoice_id',$invoice->id)->count());
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'sold']);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>1]);$this->assertSame(1,DB::table('stock_movements')->count());
        $this->assertSame(1,DB::table('warranties')->count());$this->get('/ccs/warranties?customer_id='.$customer->id)->assertOk()->assertSee('00123ABC');
        $this->get('/ccs/customers/'.$customer->id)->assertOk()->assertSee('Open Invoice / Payment')->assertSee($invoice->invoice_number);
        $this->get('/ccs/pos?job_id='.$job->id)->assertRedirect(route('ccs.invoices.show',$invoice->id));$this->assertSame(1,DB::table('invoices')->count());
    }
    public function test_pos_invoice_history_batches_receipts_and_lines_without_cross_invoice_totals(): void {
        for($i=1;$i<=12;$i++){
            $id=DB::table('invoices')->insertGetId(['invoice_number'=>'BATCH-'.$i,'repair_job_id'=>1,'status'=>'partially_paid','subtotal_lkr'=>10000,'total_lkr'=>10000]);
            DB::table('invoice_items')->insert(['invoice_id'=>$id,'description'=>'Item '.$i,'quantity'=>1,'unit_price_lkr'=>10000,'line_total_lkr'=>10000]);
            foreach([4000,1000] as $amount)DB::table('payments')->insert(['invoice_id'=>$id,'amount_lkr'=>$amount,'method'=>'cash','paid_at'=>now()]);
        }
        DB::enableQueryLog();DB::flushQueryLog();
        try{
            $response=$this->get('/ccs/pos')->assertOk()->assertViewHas('dueInvoices',function($rows){return $rows->count()===12&&$rows->every(fn($row)=>(float)$row->balance===5000.0&&count($row->payments_history)===2&&$row->items->count()===1);});
            $queries=collect(DB::getQueryLog())->pluck('query');
            $this->assertSame(1,$queries->filter(fn($sql)=>preg_match('/^select .* from "payments"/i',$sql))->count());
            $this->assertSame(1,$queries->filter(fn($sql)=>preg_match('/^select .* from "invoice_items"/i',$sql))->count());
        }finally{DB::disableQueryLog();}
    }
    public function test_quick_job_save_opens_pos_with_that_job_preselected(): void {
        $response=$this->post('/ccs/jobs',['vehicle_id'=>1,'complaint'=>'Quick test repair','technician_name'=>'Test staff','next_action'=>'pos'])->assertSessionHasNoErrors();
        $job=DB::table('repair_jobs')->where('complaint','Quick test repair')->first();$this->assertNotNull($job);$this->assertSame('vehicle_received',$job->status);$this->assertSame(1,(int)$job->assigned_user_id);$response->assertRedirect(route('ccs.pos',['job_id'=>$job->id]));
        $this->get('/ccs/pos?job_id='.$job->id)->assertOk()->assertViewHas('preselectedJob',fn($selected)=>(int)$selected->id===(int)$job->id)->assertSee($job->job_number);
    }
    public function test_open_pos_for_billed_job_returns_its_existing_invoice(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>6000]))->assertSessionHasNoErrors();$invoice=DB::table('invoices')->first();
        $this->get('/ccs/pos?job_id=1')->assertRedirect(route('ccs.invoices.show',$invoice->id));$this->assertSame(1,DB::table('invoices')->count());
    }
    public function test_repair_status_and_technician_do_not_change_payment_status(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>6000]))->assertSessionHasNoErrors();$invoice=DB::table('invoices')->first();
        $this->post('/ccs/jobs/1/status',['status'=>'delivered','technician_name'=>'Test staff'])->assertSessionHasNoErrors();$job=DB::table('repair_jobs')->find(1);$this->assertSame('delivered',$job->status);$this->assertNotNull($job->completed_at);
        $this->assertSame('partially_paid',DB::table('invoices')->where('id',$invoice->id)->value('status'));$this->assertEquals(6000,DB::table('payments')->sum('amount_lkr'));
        $this->post('/ccs/jobs/1/status',['status'=>'repair_in_progress','technician_name'=>''])->assertSessionHasNoErrors();$job=DB::table('repair_jobs')->find(1);$this->assertNull($job->completed_at);$this->assertNull($job->assigned_user_id);
        $this->post('/ccs/jobs/1/status',['status'=>'invalid'])->assertSessionHasErrors('status');$this->assertSame('repair_in_progress',DB::table('repair_jobs')->where('id',1)->value('status'));
    }
    public function test_job_card_can_be_completed_later_and_legacy_status_tabs_keep_history(): void {
        DB::table('repair_jobs')->where('id',1)->update(['status'=>'completed','technician_name'=>'Original tech']);
        $this->get('/ccs/jobs?stage=ready')->assertOk()->assertSee('TEST-J1')->assertDontSee('TEST-J2');
        $this->get('/ccs/jobs/1/card')->assertOk();
        $this->post('/ccs/jobs/1/card',['complaint'=>'Updated complaint','diagnosis'=>'Saved diagnosis','parts_notes'=>'Saved parts note'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('repair_jobs',['id'=>1,'diagnosis'=>'Saved diagnosis','parts_notes'=>'Saved parts note','status'=>'completed','technician_name'=>'Original tech']);
        DB::table('vehicles')->where('id',1)->update(['deleted_at'=>now()]);$this->get('/ccs/jobs/1/card')->assertNotFound();$this->get('/ccs/pos?job_id=1')->assertNotFound();
    }
    public function test_pos_deep_link_selects_a_job_older_than_its_recent_fifty(): void {
        for($id=4;$id<=60;$id++)DB::table('repair_jobs')->insert(['id'=>$id,'vehicle_id'=>1,'job_number'=>'OLDER-TEST-'.$id,'complaint'=>'Test only']);
        $this->get('/ccs/pos?job_id=1')->assertOk()->assertViewHas('jobs',fn($jobs)=>$jobs->contains('id',1));
    }
    public function test_customer_and_vehicle_register_together_and_open_quick_job(): void {
        $response=$this->post('/ccs/customers',['name'=>'New client','phone'=>'0770000001','include_vehicle'=>1,'registration_number'=>'NEW-1234','make'=>'Toyota','model'=>'Aqua','manufacture_year'=>2018,'current_mileage_km'=>100000,'next_action'=>'job']);$response->assertSessionHasNoErrors();
        $customer=DB::table('customers')->where('name','New client')->first();$vehicle=DB::table('vehicles')->where('customer_id',$customer->id)->first();$this->assertSame('NEW-1234',$vehicle->registration_number);$this->assertSame(100000,(int)$vehicle->current_mileage_km);
        $response->assertRedirect(route('ccs.jobs',['new'=>1,'vehicle_id'=>$vehicle->id]));$this->get('/ccs/jobs?new=1&vehicle_id='.$vehicle->id)->assertOk()->assertViewHas('vehicles',fn($vehicles)=>(int)$vehicles->first()->id===(int)$vehicle->id);
    }
    public function test_customer_only_registration_creates_no_empty_vehicle(): void {
        $this->post('/ccs/customers',['name'=>'Customer only','phone'=>'0770000002','include_vehicle'=>0])->assertSessionHasNoErrors();$customer=DB::table('customers')->where('name','Customer only')->first();$this->assertNotNull($customer);$this->assertSame(0,DB::table('vehicles')->where('customer_id',$customer->id)->count());$this->get('/ccs/customers/'.$customer->id)->assertOk();
    }
    public function test_duplicate_plate_rolls_back_combined_customer_registration(): void {
        $this->post('/ccs/customers',['name'=>'Do not duplicate','phone'=>'0770000003','include_vehicle'=>1,'registration_number'=>'test 001','make'=>'Toyota','model'=>'Prius'])->assertSessionHasErrors('registration_number');$this->assertSame(1,DB::table('customers')->count());$this->assertSame(1,DB::table('vehicles')->count());
    }
    public function test_directory_balances_use_finalized_invoices_not_drafts_or_duplicate_joins(): void {
        DB::table('repair_jobs')->where('id',1)->update(['received_at'=>now(),'created_at'=>now()]);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>6000]))->assertSessionHasNoErrors();
        $this->post('/ccs/pos',$this->payload(2,['stock_unit_id'=>[2]]))->assertSessionHasNoErrors();
        $this->get('/ccs/customers?credit=1')->assertOk()->assertViewHas('customers',function($rows){$row=$rows->first();return $rows->total()===1 && (float)$row->total_billed===10000.0 && (float)$row->credit_balance===4000.0 && (int)$row->vehicle_count===1;})->assertViewHas('activeThisMonth',1);
        $this->get('/ccs/vehicles?q=TEST-001')->assertOk()->assertSee('TEST-001');
    }
    public function test_customer_vehicle_history_rejects_another_customers_vehicle(): void {
        DB::table('customers')->insert(['id'=>2,'customer_code'=>'OTHER-C2','name'=>'Other owner']);DB::table('vehicles')->insert(['id'=>2,'customer_id'=>2,'vehicle_code'=>'OTHER-V2','registration_number'=>'OTHER-002','make'=>'Honda','model'=>'Fit']);
        $this->get('/ccs/customers/1?vehicle_id=2')->assertNotFound();$this->get('/ccs/customers/1?vehicle_id=1')->assertOk()->assertViewHas('selectedVehicle',fn($v)=>(int)$v->id===1);
        DB::table('customers')->where('id',2)->update(['deleted_at'=>now()]);$this->get('/ccs/vehicles?q=OTHER-002')->assertOk()->assertDontSee('OTHER-002');
    }
    public function test_stock_directory_creates_updates_and_filters_actual_prices_and_stock(): void {
        $this->post('/ccs/stock',['sku'=>'NEW-STOCK','name'=>'ABS pump','category'=>'ABS','condition'=>'Recon','service_application'=>'ABS replacement','reorder_level'=>2,'selling_price_lkr'=>15000,'purchase_price_lkr'=>10000,'quantity'=>1])->assertSessionHasNoErrors();
        $part=DB::table('inventory_items')->where('sku','NEW-STOCK')->first();
        $this->get('/ccs/stock?category=ABS&low=1')->assertOk()->assertViewHas('parts',fn($rows)=>$rows->total()===1)->assertSee('ABS pump');
        $this->post('/ccs/stock/'.$part->id.'/update',['name'=>'ABS pump revised','category'=>'ABS','condition'=>'Used','reorder_level'=>3,'selling_price_lkr'=>14000,'purchase_price_lkr'=>9000,'active'=>1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inventory_items',['id'=>$part->id,'quantity_on_hand'=>1,'selling_price_lkr'=>14000,'purchase_price_lkr'=>9000,'reorder_level'=>3]);
    }
    public function test_approved_warranty_edit_creates_revision_without_changing_saved_cards(): void {
        config(['ccs_warranty.approver_emails'=>['stock@example.test']]);
        $rule=$this->warrantyPackage();
        $this->post('/ccs/warranties/rules/'.$rule.'/update',['name'=>'Revised package','terms'=>'New future terms','duration_months'=>12,'free_service_count'=>1,'limit_basis'=>'time_first'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('warranty_rules',['id'=>$rule,'duration_months'=>36,'approved_by_owner'=>true]);
        $this->assertDatabaseHas('warranty_rules',['name'=>'Revised package','duration_months'=>12,'approved_by_owner'=>false,'active'=>false]);
    }
    public function test_warranty_search_print_and_manual_whatsapp_use_saved_customer_card(): void {
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);
        DB::table('customers')->where('id',1)->update(['phone'=>'0771234567']);
        $this->post('/ccs/pos',$this->payload(1,['expected_warranty_rule_id'=>[$rule],'initial_received'=>10000]))->assertSessionHasNoErrors();
        $w=DB::table('warranties')->first();$this->assertNotNull($w);
        $this->get('/ccs/warranties?q=00123ABC')->assertOk()->assertSee('00123ABC')->assertSee('wa.me/94771234567')->assertSee('Print Warranty / Save PDF');
        $this->get('/ccs/warranties/'.$w->id.'/print')->assertOk()->assertSee('00123ABC')->assertSee('Sample confirmed terms only');
        DB::table('warranties')->where('id',$w->id)->update(['terms_snapshot'=>json_encode(['provisional'=>true])]);
        $this->get('/ccs/warranties/'.$w->id.'/print')->assertNotFound();
    }
    public function test_stock_directory_migration_is_retry_safe_and_preserves_original_reorder_data(): void {
        DB::table('inventory_items')->where('id',1)->update(['reorder_level'=>7,'category'=>'Battery','service_application'=>'Saved application']);
        $migration=require database_path('migrations/2026_10_26_000018_add_stock_directory_fields.php');
        $migration->up();$migration->up();
        $this->assertDatabaseHas('inventory_items',['id'=>1,'reorder_level'=>7,'category'=>'Battery','service_application'=>'Saved application']);
        $migration->down();$this->assertDatabaseHas('inventory_items',['id'=>1,'reorder_level'=>7]);
        $migration->up();$this->assertDatabaseHas('inventory_items',['id'=>1,'reorder_level'=>7]);
    }
    public function test_module_finalize_blocks_without_job_voltage_then_succeeds(): void {
        DB::table('inventory_items')->where('id',1)->update(['is_battery_module'=>true]);
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasErrors('stock');
        $this->assertSame(0,DB::table('invoices')->count());$this->assertSame(0,DB::table('payments')->count());
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'available']);
        $this->post('/ccs/pos',$this->payload())->assertSessionHasNoErrors();$invoice=DB::table('invoices')->first();
        $issue=['confirmation'=>1,'method'=>'cash','amount_received'=>4000,'request_token'=>(string)Str::uuid()];
        $this->post('/ccs/invoices/'.$invoice->id.'/finalize',$issue)->assertSessionHasErrors('stock');
        $this->post('/ccs/jobs/1/battery',['request_token'=>(string)Str::uuid(),'service_date'=>today()->toDateString(),'module_count'=>34,'modules'=>[['position'=>1,'serial_number'=>'00123ABC','voltage'=>7.6]]])->assertSessionHasNoErrors();
        $this->post('/ccs/invoices/'.$invoice->id.'/finalize',$issue)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices',['id'=>$invoice->id,'status'=>'partially_paid']);$this->assertDatabaseHas('payments',['invoice_id'=>$invoice->id,'amount_lkr'=>4000]);
    }
    public function test_manual_serial_can_be_completed_only_on_draft(): void {
        $data=$this->payload(1,['inventory_item_id'=>[''],'stock_unit_id'=>[''],'item_origin'=>['manual_part'],'requires_serial'=>[1],'manual_serial'=>['']]);
        $this->post('/ccs/pos',$data)->assertSessionHasNoErrors();$invoice=DB::table('invoices')->first();$item=DB::table('invoice_items')->first();
        $issue=['confirmation'=>1,'method'=>'cash','request_token'=>(string)Str::uuid()];
        $this->post('/ccs/invoices/'.$invoice->id.'/finalize',$issue)->assertSessionHasErrors('stock');
        $this->post('/ccs/invoices/'.$invoice->id.'/complete-serials',['serials'=>[$item->id=>'000REAL']])->assertSessionHasNoErrors();
        $this->post('/ccs/invoices/'.$invoice->id.'/finalize',$issue)->assertSessionHasNoErrors();
        $this->post('/ccs/invoices/'.$invoice->id.'/complete-serials',['serials'=>[$item->id=>'CHANGE']])->assertStatus(409);
        $this->assertDatabaseHas('invoice_items',['id'=>$item->id,'serial_number'=>'000REAL']);
    }
    public function test_pos_popup_readings_save_battery_history_and_payment_atomically(): void {
        DB::table('inventory_items')->where('id',1)->update(['is_battery_module'=>true]);
        $data=$this->payload(1,['module_position'=>[1],'module_voltage'=>[7.6],'initial_received'=>10000]);
        $this->post('/ccs/pos',$data)->assertSessionHasNoErrors();
        $record=DB::table('battery_service_records')->first();$this->assertSame(1,(int)$record->repair_job_id);
        $this->assertDatabaseHas('battery_module_readings',['battery_service_record_id'=>$record->id,'serial_number'=>'00123ABC','position'=>1,'voltage'=>7.6]);
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'sold']);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>1]);$this->assertSame(1,DB::table('payments')->count());
        $this->get('/ccs/stock/module-reading?job_id=1&serial=00123abc')->assertOk()->assertJsonPath('reading.position',1);
        $this->get('/ccs/stock/module-reading?job_id=2&serial=00123ABC')->assertOk()->assertJsonPath('reading',null);
    }
    public function test_invalid_popup_and_stock_failure_do_not_leave_battery_records(): void {
        DB::table('inventory_items')->where('id',1)->update(['is_battery_module'=>true]);
        $this->post('/ccs/pos',$this->payload(1,['module_position'=>[1],'module_voltage'=>[''],'initial_received'=>10000]))->assertSessionHasErrors('stock');
        $this->assertSame(0,DB::table('battery_service_records')->count());$this->assertSame(0,DB::table('invoices')->count());
        DB::table('inventory_items')->where('id',1)->update(['quantity_on_hand'=>0]);
        $this->post('/ccs/pos',$this->payload(1,['module_position'=>[1],'module_voltage'=>[7.6],'initial_received'=>10000]))->assertSessionHasErrors('stock');
        $this->assertSame(0,DB::table('battery_service_records')->count());$this->assertSame(0,DB::table('payments')->count());
    }
    public function test_tamil_language_is_selectable_and_persisted(): void {
        $this->post('/ccs/settings/language',['language'=>'ta'])->assertSessionHasNoErrors()->assertSessionHas('ccs_language','ta');
        $this->get('/ccs/settings')->assertOk()->assertSee('தமிழ் + English');
    }
    public function test_cashier_and_technician_permissions_are_enforced_server_side(): void {
        $user=User::findOrFail(1);$user->ccs_role='cashier';$user->save();$this->actingAs($user);
        $this->get('/ccs/stock')->assertOk();$this->post('/ccs/stock',['sku'=>'DENY'])->assertForbidden();$this->get('/ccs/settings/users')->assertForbidden();
        $user->ccs_role='technician';$user->save();$this->actingAs($user);$this->get('/ccs/jobs')->assertOk();$this->get('/ccs/pos')->assertForbidden();$this->post('/ccs/pos',$this->payload())->assertForbidden();$this->post('/ccs/invoice-items/1/details',[])->assertForbidden();
    }
    public function test_owner_can_create_staff_and_audit_never_stores_password(): void {
        config(['ccs_admin.owner_emails'=>['stock@example.test']]);
        $this->post('/ccs/settings/users',['name'=>'New cashier','email'=>'cashier@example.test','role'=>'cashier','active'=>1,'password'=>'Example-only-Password123!','password_confirmation'=>'Example-only-Password123!'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users',['email'=>'cashier@example.test','ccs_role'=>'cashier','ccs_active'=>true]);$this->assertFalse(str_contains(json_encode(DB::table('audit_logs')->get()),'Example-only-Password123!'));
        $this->post('/ccs/settings/users',['id'=>1,'name'=>'Test staff','email'=>'stock@example.test','role'=>'technician','active'=>0])->assertSessionHasErrors('role');
    }
    public function test_warranty_claim_requires_owner_decision_and_links_only_one_repair_job(): void {
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);$this->post('/ccs/pos',$this->payload(1,['expected_warranty_rule_id'=>[$rule],'initial_received'=>10000]))->assertSessionHasNoErrors();$w=DB::table('warranties')->first();
        $this->post('/ccs/claims',['warranty_id'=>$w->id,'received_on'=>today()->toDateString(),'problem'=>'Customer reports an issue'])->assertSessionHasNoErrors();$c=DB::table('warranty_claims')->first();
        $this->get('/ccs/claims?q=Test%20customer')->assertOk()->assertSee('View / Manage')->assertSee('00123ABC')->assertSee('Customer reports an issue')->assertDontSee('id="decision-'.$c->id.'"',false);
        $decision=['status'=>'approved','decision_notes'=>'Conditions checked manually','confirmation'=>1];$this->post('/ccs/claims/'.$c->id.'/decision',$decision)->assertForbidden();
        config(['ccs_admin.owner_emails'=>['stock@example.test']]);$this->get('/ccs/claims')->assertOk()->assertSee('id="decision-'.$c->id.'"',false)->assertSee('Record Findings');$this->post('/ccs/claims/'.$c->id.'/decision',$decision)->assertSessionHasErrors('status');
        $this->post('/ccs/claims/'.$c->id.'/findings',['findings'=>'Inspection completed'])->assertSessionHasNoErrors();$this->post('/ccs/claims/'.$c->id.'/decision',$decision)->assertSessionHasNoErrors();
        $this->post('/ccs/claims/'.$c->id.'/job')->assertRedirect();$jobId=DB::table('warranty_claims')->value('repair_job_id');$this->post('/ccs/claims/'.$c->id.'/job')->assertRedirect(route('ccs.jobs.card',$jobId));$this->assertSame(4,DB::table('repair_jobs')->count());$this->assertSame(1,DB::table('payments')->count());
    }
    public function test_invoice_print_language_is_independent_and_keeps_receipt_history(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>4000]))->assertSessionHasNoErrors();$id=DB::table('invoices')->value('id');
        $this->post('/ccs/settings/language',['language'=>'si'])->assertSessionHasNoErrors();
        $this->post('/ccs/invoices/'.$id.'/print-settings',['print_locale'=>'ta_LK','print_paper'=>'80mm'])->assertSessionHasNoErrors()->assertSessionHas('ccs_language','si');
        $this->get('/ccs/invoices/'.$id.'/print')->assertOk()->assertSee('lang="ta-LK"',false)->assertSee('4,000.00')->assertSee('6,000.00')->assertSee('00123ABC');
    }
    public function test_notifications_are_scoped_to_staff_and_read_state_is_per_user(): void {
        DB::table('repair_jobs')->where('id',1)->update(['created_at'=>now()]);$this->get('/ccs/notifications/count')->assertOk()->assertJsonStructure(['unread']);$this->post('/ccs/notifications/read',['key'=>'job:1'])->assertSessionHasNoErrors();$this->assertDatabaseHas('ccs_notification_reads',['user_id'=>1,'notification_key'=>'job:1']);
    }
    public function test_stock_labels_encode_actual_serial_and_preserve_leading_zeroes(): void {
        $this->get('/ccs/stock/1/labels/print?unit_id=1&copies=1')->assertOk()->assertSee('00123ABC')->assertSee('<svg',false);
        $this->assertDatabaseHas('stock_units',['id'=>1,'status'=>'available']);$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>2]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);app(\App\Services\CcsBarcode::class)->svg("invalid\nserial");
    }
    public function test_public_staff_signup_and_profile_email_promotion_are_blocked(): void {
        $this->patch('/profile',['name'=>'Test staff','email'=>'unregistered-owner@example.test'])->assertForbidden();
        $this->delete('/profile',['password'=>'test-staff-password'])->assertForbidden();
        \Illuminate\Support\Facades\Auth::logout();$this->get('/register')->assertForbidden();$this->post('/register',['name'=>'Intruder','email'=>'intruder@example.test','password'=>'Example-only-Password123!','password_confirmation'=>'Example-only-Password123!'])->assertForbidden();
        $this->assertSame(1,DB::table('users')->count());
    }
    public function test_pos_invoice_language_is_saved_separately_from_system_language(): void {
        $this->withSession(['ccs_language'=>'si'])->post('/ccs/pos',$this->payload(1,['invoice_locale'=>'ta_LK']))->assertSessionHasNoErrors()->assertSessionHas('ccs_language','si');
        $this->assertDatabaseHas('invoices',['repair_job_id'=>1,'print_locale'=>'ta_LK']);
    }
    public function test_customer_warranty_preview_is_private_and_customer_scoped(): void {
        $rule=$this->warrantyPackage();DB::table('inventory_items')->where('id',1)->update(['default_warranty_rule_id'=>$rule]);$this->post('/ccs/pos',$this->payload(1,['expected_warranty_rule_id'=>[$rule],'initial_received'=>10000]))->assertSessionHasNoErrors();
        $this->get('/ccs/warranties/customer-preview?customer_id=1')->assertOk()->assertSee('00123ABC')->assertSee('Sample confirmed terms only');
        DB::table('customers')->insert(['id'=>2,'customer_code'=>'EMPTY-C2','name'=>'Other customer']);
        $this->get('/ccs/warranties/customer-preview?customer_id=2')->assertOk()->assertDontSee('00123ABC');
        $this->get('/ccs/warranties/customer-preview?customer_id=2&vehicle_id=1')->assertNotFound();
        \Illuminate\Support\Facades\Auth::logout();$this->get('/ccs/warranties/customer-preview?customer_id=1')->assertRedirect();$this->get('/customer/warranties')->assertRedirect(route('customer.login'));
    }

    public function test_staff_keyboard_choice_is_per_user_validated_and_preserved_when_other_preferences_change(): void {
        $user=User::findOrFail(1);$user->ccs_role='cashier';$user->save();$this->actingAs($user);
        $this->postJson('/ccs/settings/input-method',['input_method'=>'phonetic'])->assertOk()->assertJsonPath('input_method','phonetic');
        $this->get('/ccs/settings')->assertOk()->assertSee('ccsInputMode',false)->assertSee('Sinhala Phonetic')->assertSee('settings-language',false)->assertSee('settings-keyboard',false)->assertSee('settings-security',false)->assertSee('settings-printing',false)->assertSee('settings-notifications',false)->assertSee('phoneticGuide',false);
        $before=json_decode(DB::table('ccs_staff_preferences')->where('user_id',1)->value('preferences'),true);$this->assertSame('phonetic',$before['input_method']);
        $this->postJson('/ccs/settings/input-method',['input_method'=>'invalid'])->assertUnprocessable();
        $this->post('/ccs/settings/preferences',['scanner'=>'reader','paper'=>'a4','invoice_locale'=>'en','label_width'=>50,'label_height'=>30])->assertSessionHasNoErrors();
        $after=app(\App\Services\CcsAdministration::class)->preferences(1);$this->assertSame('phonetic',$after['input_method']);$this->assertSame('reader',$after['scanner']);
        DB::table('users')->insert(['id'=>2,'name'=>'Other staff','email'=>'other-input@example.test','password'=>Hash::make('sample-only-password')]);
        $this->assertSame('english',app(\App\Services\CcsAdministration::class)->preferences(2)['input_method']);
    }

    public function test_settings_user_management_is_shown_only_to_owner_and_uses_real_staff(): void {
        config(['ccs_admin.owner_emails'=>['stock@example.test']]);
        DB::table('users')->insert(['name'=>'Kasun Test Staff','email'=>'kasun-settings@example.test','password'=>Hash::make('sample-password-only'),'ccs_role'=>'cashier','ccs_active'=>true]);
        $this->get('/ccs/settings')->assertOk()->assertSee('id="settings-users"',false)->assertSee('Kasun Test Staff')->assertSee('staff-create',false)->assertSee('staff-edit-1',false);
        config(['ccs_admin.owner_emails'=>[]]);$user=User::findOrFail(1);$user->ccs_role='cashier';$user->save();$this->actingAs($user);
        $this->get('/ccs/settings')->assertOk()->assertDontSee('id="settings-users"',false)->assertDontSee('kasun-settings@example.test')->assertDontSee('staff-create',false);
        $this->post('/ccs/settings/users',['name'=>'Denied'])->assertForbidden();
    }

    public function test_customer_serial_records_show_all_owned_vehicles_and_exclude_other_customers_and_drafts(): void {
        $this->post('/ccs/pos',$this->payload(1,['initial_received'=>10000]))->assertSessionHasNoErrors();
        $this->post('/ccs/pos',$this->payload(2,['stock_unit_id'=>[2],'initial_received'=>10000]))->assertSessionHasNoErrors();
        DB::table('customers')->insert(['id'=>2,'customer_code'=>'TEST-C2','name'=>'Other customer']);
        DB::table('vehicles')->insert(['id'=>2,'customer_id'=>2,'vehicle_code'=>'TEST-V2','registration_number'=>'OTHER-001','make'=>'Honda','model'=>'Fit']);
        DB::table('repair_jobs')->insert(['id'=>4,'vehicle_id'=>2,'job_number'=>'OTHER-J1','complaint'=>'Other customer']);
        $other=DB::table('invoices')->insertGetId(['repair_job_id'=>4,'invoice_number'=>'OTHER-I1','status'=>'paid','total_lkr'=>1000]);
        DB::table('invoice_items')->insert(['invoice_id'=>$other,'description'=>'Other serial item','quantity'=>1,'unit_price_lkr'=>1000,'line_total_lkr'=>1000,'serial_number'=>'OTHER-SECRET-123','item_origin'=>'manual_part']);
        $draft=DB::table('invoices')->insertGetId(['repair_job_id'=>3,'invoice_number'=>'DRAFT-I1','status'=>'draft','total_lkr'=>1000]);
        DB::table('invoice_items')->insert(['invoice_id'=>$draft,'description'=>'Draft serial item','quantity'=>1,'unit_price_lkr'=>1000,'line_total_lkr'=>1000,'serial_number'=>'DRAFT-123','item_origin'=>'manual_part']);
        $this->get('/ccs/customers/1/serial-records')->assertOk()->assertSee('00123ABC')->assertSee('00456DEF')->assertDontSee('OTHER-SECRET-123')->assertDontSee('DRAFT-123');
        $this->get('/ccs/customers/1/serial-records?serial=00123abc')->assertOk()->assertSee('00123ABC')->assertDontSee('00456DEF');
        $this->get('/ccs/jobs/1/battery')->assertOk()->assertSee(route('ccs.customers.serial-records',1));
        $this->get('/ccs/customers/1')->assertOk()->assertSee(route('ccs.customers.serial-records',1));
        $this->get('/ccs/customers/99999/serial-records')->assertNotFound();
    }
}
