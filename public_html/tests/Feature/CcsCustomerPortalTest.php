<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\CustomerPortalAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CcsCustomerPortalTest extends TestCase
{
    private string $backupTestRoot;
    protected function tearDown(): void {
        if(isset($this->backupTestRoot))\Illuminate\Support\Facades\File::deleteDirectory($this->backupTestRoot);
        parent::tearDown();
    }
    protected function setUp(): void
    {
        parent::setUp();$this->backupTestRoot=sys_get_temp_dir().'/ccs-isolated-'.Str::uuid();config(['ccs_backups.root'=>$this->backupTestRoot]);
        // These tests always create a new in-memory DB, never the workshop database.
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:',
            'cache.default'=>'array','session.driver'=>'array','auth.defaults.guard'=>'web']);
        DB::purge('sqlite');
        $this->artisan('migrate',['--database'=>'sqlite','--force'=>true])->assertExitCode(0);
        DB::table('users')->insert(['id'=>1,'name'=>'Staff','email'=>'staff@example.test','password'=>Hash::make('test-staff-password'),'created_at'=>now(),'updated_at'=>now()]);
        foreach([1,2] as $id){
            DB::table('customers')->insert(['id'=>$id,'customer_code'=>'TEST-C'.$id,'name'=>'Customer '.$id,'phone'=>'070000000'.$id,'notes'=>'PRIVATE CUSTOMER NOTE']);
            DB::table('vehicles')->insert(['id'=>$id,'customer_id'=>$id,'vehicle_code'=>'TEST-V'.$id,'registration_number'=>'TEST-00'.$id,'make'=>'Toyota','model'=>'Prius']);
            DB::table('repair_jobs')->insert(['id'=>$id,'vehicle_id'=>$id,'job_number'=>'TEST-J'.$id,'complaint'=>'PRIVATE COMPLAINT','diagnosis'=>'PRIVATE DIAGNOSIS']);
            DB::table('battery_service_records')->insert(['id'=>$id,'vehicle_id'=>$id,'repair_job_id'=>$id,'recorded_by'=>1,'request_token'=>(string)Str::uuid(),'service_date'=>'2026-10-25','module_count'=>1,'notes'=>'PRIVATE SERVICE NOTE']);
            DB::table('battery_module_readings')->insert(['battery_service_record_id'=>$id,'position'=>1,'serial_number'=>'00123ABC','voltage'=>'7.251','notes'=>'PRIVATE MODULE NOTE']);
            CustomerPortalAccount::create(['customer_id'=>$id,'username'=>'customer'.$id,'password'=>'test-customer-password','active'=>true,'access_version'=>1]);
        }
    }
    private function signIn(): void
    {
        $this->postJson('/customer/login',['username'=>'customer1','password'=>'test-customer-password'])->assertOk();
    }
    public function test_guest_cannot_search_or_read_service(): void
    {
        $this->getJson('/customer/api/search?serial_number=00123ABC')->assertUnauthorized();
        $this->getJson('/customer/api/services/1')->assertUnauthorized();
    }
    public function test_serial_search_preserves_zeros_and_never_returns_other_customer(): void
    {
        $this->signIn();
        $this->getJson('/customer/api/search?serial_number=00123abc')->assertOk()->assertJsonPath('meta.total',1)->assertJsonPath('data.0.vehicle_id',1);
        $this->getJson('/customer/api/search?serial_number=123ABC')->assertJsonPath('meta.total',0);
        $this->getJson('/customer/api/search?registration_number=TEST-002')->assertJsonPath('meta.total',0);
        $this->getJson('/customer/api/services/2')->assertNotFound();
        $this->getJson('/customer/api/vehicles/2/history')->assertNotFound();
        $this->getJson('/customer/api/vehicles/2/warranties')->assertNotFound();
    }
    public function test_only_safe_service_fields_are_returned(): void
    {
        $this->signIn();
        $response=$this->getJson('/customer/api/services/1')->assertOk()->assertJsonPath('data.modules.0.serial_number','00123ABC');
        foreach(['PRIVATE CUSTOMER NOTE','PRIVATE COMPLAINT','PRIVATE DIAGNOSIS','PRIVATE SERVICE NOTE','PRIVATE MODULE NOTE','password','recorded_by'] as $private){$this->assertStringNotContainsString($private,$response->getContent());}
        $this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
    }
    public function test_disabled_or_reset_access_invalidates_existing_session(): void
    {
        $this->signIn();
        DB::table('customer_portal_accounts')->where('customer_id',1)->update(['access_version'=>2]);
        Auth::forgetGuards();
        $this->getJson('/customer/api/me')->assertUnauthorized();
    }
    public function test_customer_login_does_not_allow_staff_management(): void
    {
        $this->signIn();
        $this->get('/ccs/customer-access')->assertRedirect();
        $this->assertFalse(Auth::guard('web')->check());
    }
    public function test_transfer_removes_former_owners_access(): void
    {
        $this->signIn();
        DB::table('vehicles')->where('id',1)->update(['customer_id'=>2]);
        $this->getJson('/customer/api/vehicles/1/history')->assertNotFound();
        $this->getJson('/customer/api/services/1')->assertNotFound();
        $this->getJson('/customer/api/search?serial_number=00123ABC')->assertJsonPath('meta.total',0);
    }
    public function test_search_requires_a_filter_and_returns_no_wildcard_matches(): void
    {
        $this->signIn();
        $this->getJson('/customer/api/search')->assertUnprocessable();
        $this->getJson('/customer/api/search?serial_number=%25')->assertOk()->assertJsonPath('meta.total',0);
    }
    public function test_provisional_warranties_are_not_published(): void
    {
        $this->signIn();
        $rule=DB::table('warranty_rules')->insertGetId(['name'=>'Test rule','category'=>'hybrid_battery','approved_by_owner'=>true,'active'=>true]);
        DB::table('warranties')->insert(['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-10-25','terms_snapshot'=>json_encode(['name'=>'Internal draft','provisional'=>true])]);
        $this->getJson('/customer/api/vehicles/1/warranties')->assertOk()->assertJsonCount(0,'data');
    }
    public function test_unified_lookup_is_scoped_and_preserves_serial_strings(): void
    {
        $this->getJson('/customer/api/serial-lookup?serial_number=00123ABC')->assertUnauthorized();
        $this->signIn();
        $response=$this->getJson('/customer/api/serial-lookup?serial_number=00123abc&customer_id=2')->assertOk()
            ->assertJsonPath('meta.total',1)->assertJsonPath('data.0.vehicle.id',1)->assertJsonPath('data.0.part.serial_number','00123ABC');
        $this->assertStringNotContainsString('PRIVATE',$response->getContent());
        $this->getJson('/customer/api/serial-lookup?serial_number=123ABC')->assertJsonPath('meta.total',0);
        $this->getJson('/customer/api/serial-lookup?serial_number=%25')->assertJsonPath('meta.total',0);
        $this->getJson('/customer/api/serial-lookup')->assertUnprocessable();
        $this->getJson('/ccs/customer-access/serial-preview/data?customer_id=1&serial_number=00123ABC')->assertUnauthorized();
    }
    public function test_staff_can_preview_records_without_creating_customer_login(): void
    {
        $this->actingAs(\App\Models\User::findOrFail(1),'web');
        $this->getJson('/ccs/customer-access/serial-preview/data?customer_id=2&serial_number=00123ABC')->assertOk()
            ->assertJsonPath('meta.preview',true)->assertJsonPath('data.0.vehicle.id',2);
        $this->getJson('/ccs/customer-access/serial-preview/data?serial_number=00123ABC')->assertUnprocessable();
    }
    public function test_unified_lookup_combines_finalized_parts_and_modules_and_paginates(): void
    {
        foreach(['paid','partially_paid','issued','draft','void'] as $index=>$status){
            $invoiceId=DB::table('invoices')->insertGetId(['invoice_number'=>'LOOKUP-'.$index,'repair_job_id'=>1,'status'=>$status,'issued_at'=>'2026-10-25 12:00:00']);
            DB::table('invoice_items')->insert(['invoice_id'=>$invoiceId,'description'=>'Test linked part','quantity'=>1,'unit_price_lkr'=>100,'line_total_lkr'=>100,'serial_number'=>'00123ABC','item_origin'=>'customer_supplied']);
        }
        $this->signIn();
        $this->getJson('/customer/api/serial-lookup?serial_number=00123ABC')->assertOk()->assertJsonPath('meta.total',4)->assertJsonCount(4,'data');
        for($position=2;$position<=22;$position++)DB::table('battery_module_readings')->insert(['battery_service_record_id'=>1,'position'=>$position,'serial_number'=>'00123ABC']);
        $this->getJson('/customer/api/serial-lookup?serial_number=00123ABC')->assertJsonPath('meta.total',25)->assertJsonCount(20,'data');
        $this->getJson('/customer/api/serial-lookup?serial_number=00123ABC&page=2')->assertJsonCount(5,'data');
        DB::table('vehicles')->where('id',1)->update(['customer_id'=>2]);
        $this->getJson('/customer/api/serial-lookup?serial_number=00123ABC')->assertJsonPath('meta.total',0);
    }
    public function test_warranty_approval_requires_configured_staff_account(): void
    {
        config(['ccs_warranty.approver_emails'=>[]]);
        $this->actingAs(\App\Models\User::findOrFail(1),'web');
        $rule=DB::table('warranty_rules')->insertGetId(['name'=>'Test only','category'=>'hybrid_battery','duration_months'=>6,'terms'=>'Confirmed sample terms','limit_basis'=>'whichever_first']);
        $this->post('/ccs/warranties/rules/'.$rule.'/approve',['confirmation'=>1,'limit_basis'=>'whichever_first'])->assertForbidden();
        $this->assertFalse((bool)DB::table('warranty_rules')->where('id',$rule)->value('approved_by_owner'));
    }
    public function test_approved_warranty_is_issued_with_snapshot_and_visible_to_customer(): void
    {
        config(['ccs_warranty.approver_emails'=>['staff@example.test']]);
        $this->actingAs(\App\Models\User::findOrFail(1),'web');
        $rule=DB::table('warranty_rules')->insertGetId(['name'=>'Test warranty','category'=>'hybrid_battery','duration_months'=>6,'distance_km'=>10000,'terms'=>'Sample owner-confirmed test terms','limit_basis'=>'whichever_first']);
        $this->post('/ccs/warranties/rules/'.$rule.'/approve',['confirmation'=>1,'limit_basis'=>'whichever_first'])->assertRedirect();
        $this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-01-31','starting_mileage_km'=>120000,'issue_now'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $record=DB::table('warranties')->where('warranty_rule_id',$rule)->first();
        $this->assertSame('2026-07-30',$record->expires_on);
        $this->assertSame(130000,(int)$record->mileage_limit_km);
        $snapshot=json_decode($record->terms_snapshot,true);$this->assertFalse($snapshot['provisional']);$this->assertSame('whichever_first',$snapshot['limit_basis']);
        $this->signIn();
        $this->getJson('/customer/api/vehicles/1/warranties')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.name','Test warranty');
        $this->getJson('/customer/api/serial-lookup?serial_number=00123ABC')->assertOk()->assertJsonCount(1,'warranties');
        $this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-01-31','starting_mileage_km'=>120000,'issue_now'=>1])->assertSessionHasErrors('repair_job_id');
        $this->assertSame(1,DB::table('warranties')->where('warranty_rule_id',$rule)->count());
    }
    public function test_draft_warranty_needs_separate_record_approval_and_distance_mileage(): void
    {
        config(['ccs_warranty.approver_emails'=>['staff@example.test']]);
        $this->actingAs(\App\Models\User::findOrFail(1),'web');
        $rule=DB::table('warranty_rules')->insertGetId(['name'=>'Distance test','category'=>'other','distance_km'=>5000,'terms'=>'Sample terms','limit_basis'=>'distance_first','approved_by_owner'=>true,'active'=>true]);
        $this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-10-25','issue_now'=>1])->assertSessionHasErrors('starting_mileage_km');
        $this->assertSame(0,DB::table('warranties')->count());
        $this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-10-25','starting_mileage_km'=>0])->assertRedirect()->assertSessionHasNoErrors();
        $record=DB::table('warranties')->first();$this->assertTrue(json_decode($record->terms_snapshot,true)['provisional']);
        $this->post('/ccs/warranties/'.$record->id.'/approve',['confirmation'=>1])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse(json_decode(DB::table('warranties')->value('terms_snapshot'),true)['provisional']);
    }
    public function test_free_service_package_is_snapshotted_capped_and_rejects_duplicate_visits(): void
    {
        config(['ccs_warranty.approver_emails'=>['staff@example.test']]);
        $this->actingAs(\App\Models\User::findOrFail(1),'web');
        $rule=DB::table('warranty_rules')->insertGetId(['name'=>'New battery test','category'=>'hybrid_battery','coverage_kind'=>'new_battery','duration_months'=>36,'free_service_count'=>3,'terms'=>'Test conditions only','limit_basis'=>'time_first','approved_by_owner'=>true,'active'=>true]);
        $this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-01-01','issue_now'=>1])->assertSessionHasNoErrors();
        $id=DB::table('warranties')->value('id');
        for($job=3;$job<=6;$job++)DB::table('repair_jobs')->insert(['id'=>$job,'vehicle_id'=>1,'job_number'=>'FREE-'.$job]);
        $this->post('/ccs/warranties/'.$id.'/free-services',['repair_job_id'=>2,'service_date'=>'2026-10-25','confirmation'=>1])->assertSessionHasErrors('repair_job_id');
        foreach([3,4,5] as $job)$this->post('/ccs/warranties/'.$id.'/free-services',['repair_job_id'=>$job,'service_date'=>'2026-10-25','confirmation'=>1])->assertSessionHasNoErrors();
        $this->post('/ccs/warranties/'.$id.'/free-services',['repair_job_id'=>3,'service_date'=>'2026-10-25','confirmation'=>1])->assertSessionHasErrors('repair_job_id');
        $this->post('/ccs/warranties/'.$id.'/free-services',['repair_job_id'=>6,'service_date'=>'2026-10-25','confirmation'=>1])->assertSessionHasErrors('confirmation');
        $this->assertSame(3,DB::table('warranty_service_visits')->count());
        $this->signIn();
        $this->getJson('/customer/api/vehicles/1/warranties')->assertJsonPath('data.0.free_services.included',3)->assertJsonPath('data.0.free_services.used',3)->assertJsonPath('data.0.free_services.remaining',0);
        DB::table('warranty_rules')->where('id',$rule)->update(['free_service_count'=>10]);
        $this->getJson('/customer/api/vehicles/1/warranties')->assertJsonPath('data.0.free_services.included',3);
    }
    public function test_accumulator_no_warranty_can_be_recorded_without_implying_unlimited_coverage(): void
    {
        config(['ccs_warranty.approver_emails'=>['staff@example.test']]);$this->actingAs(\App\Models\User::findOrFail(1),'web');
        $id=DB::table('warranty_rules')->insertGetId(['name'=>'Accumulator no warranty','category'=>'abs','coverage_kind'=>'no_warranty','duration_months'=>0,'free_service_count'=>0,'terms'=>'No warranty for accumulator repair','limit_basis'=>'custom']);
        $this->post('/ccs/warranties/rules/'.$id.'/approve',['confirmation'=>1,'limit_basis'=>'custom'])->assertSessionHasNoErrors();
        $this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$id,'starts_on'=>'2026-10-25','issue_now'=>1])->assertSessionHasNoErrors();
        $this->signIn();$this->getJson('/customer/api/vehicles/1/warranties')->assertJsonPath('data.0.coverage_kind','no_warranty')->assertJsonPath('data.0.expires_on',null)->assertJsonPath('data.0.free_services.included',0);
    }
    public function test_part_warranties_allow_distinct_parts_but_never_another_jobs_item(): void
    {
        config(['ccs_warranty.approver_emails'=>['staff@example.test']]);$this->actingAs(\App\Models\User::findOrFail(1),'web');
        $rule=DB::table('warranty_rules')->insertGetId(['name'=>'Module warranty','category'=>'hybrid_battery','coverage_kind'=>'module','duration_months'=>6,'terms'=>'Test terms','limit_basis'=>'time_first','approved_by_owner'=>true,'active'=>true]);
        $ids=[];foreach([1,1,2] as $n=>$job){$invoice=DB::table('invoices')->insertGetId(['invoice_number'=>'PART-W-'.$n,'repair_job_id'=>$job,'status'=>'paid']);$ids[]=DB::table('invoice_items')->insertGetId(['invoice_id'=>$invoice,'description'=>'Module','quantity'=>1,'unit_price_lkr'=>100,'line_total_lkr'=>100,'serial_number'=>'00-PART-'.$n,'item_origin'=>'manual_part']);}
        foreach([$ids[0],$ids[1]] as $item)$this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-10-25','invoice_item_id'=>$item,'issue_now'=>1])->assertSessionHasNoErrors();
        $this->assertSame(2,DB::table('warranties')->count());
        $this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-10-25','invoice_item_id'=>$ids[2],'issue_now'=>1])->assertSessionHasErrors('invoice_item_id');
        $this->post('/ccs/warranties/assign',['repair_job_id'=>1,'warranty_rule_id'=>$rule,'starts_on'=>'2026-10-25','covered_serial'=>'OTHER-CUSTOMER','issue_now'=>1])->assertSessionHasErrors('covered_serial');
    }
}
