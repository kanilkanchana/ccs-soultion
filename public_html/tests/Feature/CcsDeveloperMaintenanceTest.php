<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\CcsDeveloperAccount;
use App\Services\CcsDeveloperReset;
use App\Services\CcsSqliteBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Contracts\Foundation\MaintenanceMode;
class CcsDeveloperMaintenanceTest extends TestCase {
 private string $work;
 protected function setUp(): void {
  parent::setUp();if(!class_exists(\SQLite3::class)||!class_exists(\ZipArchive::class))$this->markTestSkipped('sqlite3/zip required');
  $this->work=sys_get_temp_dir().'/ccs-dev-test-'.Str::uuid();mkdir($this->work,0700,true);touch($this->work.'/live.sqlite');config(['database.default'=>'sqlite','database.connections.sqlite.database'=>$this->work.'/live.sqlite','database.connections.sqlite.foreign_key_constraints'=>true,'cache.default'=>'array','session.driver'=>'array','ccs_backups.root'=>$this->work.'/backups','ccs_admin.owner_emails'=>['owner@example.test'],'app.key'=>'base64:'.base64_encode(str_repeat('t',32))]);DB::purge('sqlite');$this->artisan('migrate',['--database'=>'sqlite','--force'=>true])->assertExitCode(0);
  DB::table('users')->insert(['id'=>1,'name'=>'Owner','email'=>'owner@example.test','password'=>Hash::make('Owner-test-password!'),'ccs_role'=>'owner','ccs_active'=>true]);
  DB::table('ccs_developer_accounts')->insert(['id'=>1,'name'=>'Test Developer','email'=>'developer@example.test','password'=>Hash::make('Developer-test-password!'),'active'=>true,'can_clear_data'=>true,'access_version'=>1,'authorized_by'=>1]);
  DB::table('customers')->insert(['id'=>1,'customer_code'=>'C1','name'=>'Test customer']);DB::table('vehicles')->insert(['id'=>1,'customer_id'=>1,'vehicle_code'=>'V1','registration_number'=>'TEST-001','make'=>'Toyota','model'=>'Prius']);DB::table('repair_jobs')->insert(['id'=>1,'vehicle_id'=>1,'job_number'=>'J1','complaint'=>'Test']);DB::table('inventory_items')->insert(['id'=>1,'sku'=>'P1','name'=>'Test part','quantity_on_hand'=>1,'selling_price_lkr'=>10000,'has_serial'=>true]);DB::table('invoices')->insert(['id'=>1,'invoice_number'=>'I1','repair_job_id'=>1,'status'=>'partially_paid','total_lkr'=>10000]);DB::table('stock_units')->insert(['id'=>1,'inventory_item_id'=>1,'serial_number'=>'0001','serial_key'=>'0001','status'=>'sold','invoice_id'=>1]);DB::table('invoice_items')->insert(['invoice_id'=>1,'inventory_item_id'=>1,'stock_unit_id'=>1,'description'=>'Test part','quantity'=>1,'unit_price_lkr'=>10000,'line_total_lkr'=>10000,'serial_number'=>'0001']);DB::table('payments')->insert(['invoice_id'=>1,'amount_lkr'=>4000,'method'=>'cash','paid_at'=>now()]);
 }
 protected function tearDown(): void {if(isset($this->work)){DB::purge('sqlite');File::deleteDirectory($this->work);}parent::tearDown();}
 private function loginDeveloper(): void {$this->post('/developer/login',['email'=>'developer@example.test','password'=>'Developer-test-password!'])->assertRedirect(route('developer.home'));}
 private function maintenance(): void {$active=false;$mode=\Mockery::mock(MaintenanceMode::class);$mode->shouldReceive('active')->andReturnUsing(function()use(&$active){return $active;});$mode->shouldReceive('activate')->andReturnUsing(function($data)use(&$active){$active=true;});$mode->shouldReceive('deactivate')->andReturnUsing(function()use(&$active){$active=false;});$this->app->instance(MaintenanceMode::class,$mode);}
 public function test_staff_session_does_not_grant_developer_access_and_manager_cannot_authorize_it(): void {
  $this->actingAs(User::findOrFail(1));$this->get('/developer')->assertRedirect(route('developer.login'));$this->get('/ccs/settings/developer-access')->assertNotFound();
  config(['ccs_admin.owner_emails'=>[]]);$user=User::findOrFail(1);$user->ccs_role='manager';$user->save();$this->actingAs($user);$this->get('/ccs/settings/developer-access')->assertNotFound();$this->post('/ccs/settings/developer-access',[])->assertNotFound();
 }
 public function test_developer_accounts_cannot_be_created_through_the_old_web_endpoint(): void {
  $this->actingAs(User::findOrFail(1));
  $this->post('/ccs/settings/developer-access',['email'=>'second@example.test','password'=>'Private-created-password!'])->assertNotFound();
  $this->assertSame(1,CcsDeveloperAccount::count());
 }
 public function test_terminal_command_creates_hashed_login_without_password_in_audit(): void {
  $owner=User::findOrFail(1);
  $this->artisan('ccs:developer')
   ->expectsQuestion('Developer login email','second@example.test')
   ->expectsQuestion('Existing owner email (authorization record)',$owner->email)
   ->expectsQuestion('Developer display name','Kanil Developer')
   ->expectsQuestion('New developer password (16–64 characters)','Private-created-password!')
   ->expectsQuestion('Repeat developer password','Private-created-password!')
   ->expectsConfirmation('Allow this developer to clear business data?','yes')
   ->expectsConfirmation('Save and enable this developer login?','yes')->assertExitCode(0);
  $account=CcsDeveloperAccount::where('email','second@example.test')->firstOrFail();
  $this->assertTrue(Hash::check('Private-created-password!',$account->password));
  $this->assertTrue($account->active);$this->assertTrue($account->can_clear_data);
  $this->assertStringNotContainsString('Private-created-password!',json_encode(DB::table('audit_logs')->get()));
 }
 public function test_disabled_account_and_revoked_version_cannot_use_developer_portal(): void {
  $this->loginDeveloper();$this->get('/developer')->assertOk();DB::table('ccs_developer_accounts')->where('id',1)->update(['active'=>false,'access_version'=>2]);$this->get('/developer')->assertRedirect(route('developer.login'));$this->post('/developer/login',['email'=>'developer@example.test','password'=>'Developer-test-password!'])->assertSessionHasErrors('email');
 }
 public function test_cleanup_requires_permission_password_phrase_and_unchanged_review(): void {
  $this->maintenance();$this->loginDeveloper();$reset=app(CcsDeveloperReset::class);$plan=$reset->prepare(CcsDeveloperAccount::findOrFail(1),'records');$url='/developer/cleanup/'.$plan['id'];$phrase='CLEAR '.strtoupper(substr($plan['id'],0,8));
  $this->post($url,['password'=>'wrong','phrase'=>$phrase,'confirmation'=>1])->assertSessionHasErrors('password');$this->post($url,['password'=>'Developer-test-password!','phrase'=>'CLEAR','confirmation'=>1])->assertSessionHasErrors('phrase');$this->assertSame(1,DB::table('customers')->count());
  DB::table('customers')->where('id',1)->update(['name'=>'Changed since review']);$this->post($url,['password'=>'Developer-test-password!','phrase'=>$phrase,'confirmation'=>1])->assertSessionHasErrors('cleanup');$this->assertSame(1,DB::table('payments')->count());$this->assertFalse(app()->isDownForMaintenance());
  DB::table('ccs_developer_accounts')->where('id',1)->update(['can_clear_data'=>false]);$this->post('/developer/cleanup',['scope'=>'full_business'])->assertForbidden();
 }
 public function test_cleanup_creates_backup_then_clears_business_rows_and_keeps_accounts_catalogue(): void {
  $this->maintenance();$this->loginDeveloper();$plan=app(CcsDeveloperReset::class)->prepare(CcsDeveloperAccount::findOrFail(1),'records');$url='/developer/cleanup/'.$plan['id'];
  $this->post($url,['password'=>'Developer-test-password!','phrase'=>'CLEAR '.strtoupper(substr($plan['id'],0,8)),'confirmation'=>1])->assertOk()->assertSee('Business Data Cleared');
  foreach(['customers','vehicles','repair_jobs','invoices','payments','invoice_items','stock_units'] as $table)$this->assertSame(0,DB::table($table)->count());$this->assertDatabaseHas('inventory_items',['id'=>1,'quantity_on_hand'=>0]);$this->assertSame(1,DB::table('users')->count());$this->assertSame(1,CcsDeveloperAccount::count());$this->assertDatabaseHas('ccs_developer_events',['action'=>'business_data_cleared']);
  $done=app(CcsDeveloperReset::class)->plan($plan['id']);$this->assertSame('completed',$done['state']);$this->assertFileExists(app(CcsSqliteBackup::class)->archive($done['backup_id']));$this->assertFalse(app()->isDownForMaintenance());
  $this->loginDeveloper();$this->post($url,['password'=>'Developer-test-password!','phrase'=>'CLEAR '.strtoupper(substr($plan['id'],0,8)),'confirmation'=>1])->assertSessionHasErrors('cleanup');
 }
 public function test_queued_jobs_and_failed_backup_abort_before_any_deletion(): void {
  $this->maintenance();$this->loginDeveloper();$reset=app(CcsDeveloperReset::class);$plan=$reset->prepare(CcsDeveloperAccount::findOrFail(1),'records');$url='/developer/cleanup/'.$plan['id'];$data=['password'=>'Developer-test-password!','phrase'=>'CLEAR '.strtoupper(substr($plan['id'],0,8)),'confirmation'=>1];
  DB::table('jobs')->insert(['queue'=>'default','payload'=>'{}','attempts'=>0,'available_at'=>time(),'created_at'=>time()]);$this->post($url,$data)->assertSessionHasErrors('cleanup');$this->assertSame(1,DB::table('payments')->count());DB::table('jobs')->delete();
  $backups=\Mockery::mock(CcsSqliteBackup::class)->makePartial();$backups->shouldReceive('create')->once()->andThrow(new \RuntimeException('Isolated snapshot failure'));$this->app->instance(CcsSqliteBackup::class,$backups);
  $this->post($url,$data)->assertSessionHasErrors('cleanup');$this->assertSame(1,DB::table('customers')->count());$this->assertSame(1,DB::table('payments')->count());$this->assertSame(1,DB::table('stock_units')->count());$this->assertFalse(app()->isDownForMaintenance());
 }
 public function test_full_cleanup_removes_catalogue_and_failed_delete_rolls_back(): void {
  $this->maintenance();$this->loginDeveloper();$reset=app(CcsDeveloperReset::class);DB::statement("CREATE TRIGGER fail_customer_cleanup BEFORE DELETE ON customers BEGIN SELECT RAISE(ABORT, 'isolated cleanup rollback test'); END");$plan=$reset->prepare(CcsDeveloperAccount::findOrFail(1),'full_business');
  $this->post('/developer/cleanup/'.$plan['id'],['password'=>'Developer-test-password!','phrase'=>'CLEAR '.strtoupper(substr($plan['id'],0,8)),'confirmation'=>1])->assertSessionHasErrors('cleanup');$this->assertSame(1,DB::table('customers')->count());$this->assertSame(1,DB::table('payments')->count());$this->assertSame(1,DB::table('stock_units')->count());
  DB::statement('DROP TRIGGER fail_customer_cleanup');$plan=$reset->prepare(CcsDeveloperAccount::findOrFail(1),'full_business');$this->post('/developer/cleanup/'.$plan['id'],['password'=>'Developer-test-password!','phrase'=>'CLEAR '.strtoupper(substr($plan['id'],0,8)),'confirmation'=>1])->assertOk();$this->assertSame(0,DB::table('inventory_items')->count());$this->assertSame(1,DB::table('users')->count());
 }

 public function test_team_administrator_creates_private_member_but_member_cannot_delegate(): void {
  DB::table('ccs_developer_accounts')->where('id',1)->update(['can_manage_accounts'=>true]);$this->loginDeveloper();
  $this->get('/developer/team')->assertOk();
  $this->post('/developer/team',['name'=>'Kasun Developer','email'=>'kasun@example.test','password'=>'Kasun-private-test-password!','password_confirmation'=>'Kasun-private-test-password!','current_password'=>'wrong','active'=>1])->assertSessionHasErrors('current_password');
  $this->assertSame(1,CcsDeveloperAccount::count());
  $this->post('/developer/team',['name'=>'Kasun Developer','email'=>'kasun@example.test','password'=>'Kasun-private-test-password!','password_confirmation'=>'Kasun-private-test-password!','current_password'=>'Developer-test-password!','active'=>1,'can_manage_accounts'=>1])->assertSessionHasNoErrors();
  $member=CcsDeveloperAccount::where('email','kasun@example.test')->firstOrFail();
  $this->assertTrue(Hash::check('Kasun-private-test-password!',$member->password));$this->assertFalse($member->can_manage_accounts);$this->assertFalse($member->can_clear_data);
  $this->post('/developer/logout');$this->post('/developer/login',['email'=>'kasun@example.test','password'=>'Kasun-private-test-password!'])->assertRedirect(route('developer.home'));
  $this->get('/developer/team')->assertForbidden();$this->post('/developer/team',[])->assertForbidden();$this->post('/developer/test-data/review',[])->assertForbidden();
 }
 private function testPack(): array {return json_decode(file_get_contents(resource_path('test-data/full-workshop.json')),true);}
 public function test_test_pack_has_correct_receipts_stock_and_warranties_and_can_be_removed_in_isolation(): void {
  $service=app(\App\Services\CcsTestData::class);$pack=$this->testPack();$pack['date']=now('Asia/Colombo')->toDateString();
  $pack=$service->parse(json_encode($pack));$actor=CcsDeveloperAccount::findOrFail(1);
  $id=$service->import($pack,$actor,'test-safety-backup');
  $this->assertSame(12,DB::table('customers')->count());$this->assertSame(298000,$service->review($pack)['total']);$this->assertSame(87000,$service->review($pack)['due']);
  $invoice=DB::table('invoices')->where('invoice_number','TEST-I-DEMO-FULL-01-01')->first();
  $this->assertSame('partially_paid',$invoice->status);$this->assertEquals(4000,DB::table('payments')->where('invoice_id',$invoice->id)->sum('amount_lkr'));
  $part=DB::table('inventory_items')->where('sku','TEST-P-DEMO-FULL-01-01')->first();$this->assertSame(2,(int)$part->quantity_on_hand);$this->assertSame(2,DB::table('stock_units')->where('inventory_item_id',$part->id)->where('status','available')->count());
  $this->assertSame(6,DB::table('warranties')->count());$firstCustomer=DB::table('customers')->where('customer_code','TEST-C-DEMO-FULL-01-01')->value('id');$this->assertSame(2,DB::table('warranties as w')->join('repair_jobs as j','j.id','=','w.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->where('v.customer_id',$firstCustomer)->count());$this->assertSame(12,DB::table('payments')->where('invoice_id','!=',1)->count());$this->assertSame(1,DB::table('warranty_claims')->count());$this->assertSame(1,DB::table('warranty_service_visits')->count());
  $this->assertEquals(0,DB::table('battery_module_readings')->where('serial_number','TEST-S-DEMO-FULL-01-06-1')->value('voltage'));
  $before=DB::table('customers')->count();try{$service->import($pack,$actor,'duplicate');$this->fail('Duplicate batch accepted');}catch(\RuntimeException $e){$this->assertStringContainsString('already used',$e->getMessage());}$this->assertSame($before,DB::table('customers')->count());
  $service->remove($id,$actor,'removal-safety-backup');$this->assertSame(1,DB::table('customers')->count());$this->assertTrue(DB::table('customers')->where('id',1)->exists());$this->assertSame(1,DB::table('payments')->count());$this->assertEquals(4000,DB::table('payments')->where('invoice_id',1)->sum('amount_lkr'));$this->assertSame('removed',DB::table('ccs_test_imports')->where('id',$id)->value('state'));$this->assertEmpty(DB::select('PRAGMA foreign_key_check'));
 }
 public function test_import_rolls_back_on_duplicate_plate_and_edited_batch_cannot_be_removed(): void {
  $service=app(\App\Services\CcsTestData::class);$pack=$this->testPack();$pack['date']=now('Asia/Colombo')->toDateString();$actor=CcsDeveloperAccount::findOrFail(1);
  $bad=$pack;$bad['records'][1]['plate']=$bad['records'][0]['plate'];
  try{$service->import($bad,$actor,'test');$this->fail('Duplicate plate accepted');}catch(\RuntimeException $e){$this->assertStringContainsString('Duplicate test number plate',$e->getMessage());}
  $this->assertSame(1,DB::table('customers')->count());$this->assertSame(0,DB::table('ccs_test_imports')->count());
  $id=$service->import($pack,$actor,'test');DB::table('customers')->where('customer_code','TEST-C-DEMO-FULL-01-01')->update(['name'=>'Changed during test']);
  try{$service->remove($id,$actor,'test');$this->fail('Edited batch removed');}catch(\RuntimeException $e){$this->assertStringContainsString('edited or cleared',$e->getMessage());}$this->assertSame(12,DB::table('customers')->count());
 }
 public function test_import_controller_requires_review_and_password_and_has_a_dated_calendar(): void {
  $this->maintenance();$this->loginDeveloper();$pack=$this->testPack();$pack['date']=now('Asia/Colombo')->toDateString();
  $this->get('/developer/test-data')->assertOk();$this->post('/developer/test-data/review',['data'=>json_encode($pack)])->assertOk()->assertSee('87,000.00',false);
  $review=session('ccs_test_review');$this->post('/developer/test-data/import',['token'=>$review['token'],'current_password'=>'wrong','confirmation'=>1])->assertSessionHasErrors('current_password');$this->assertSame(1,DB::table('customers')->count());
  $this->post('/developer/test-data/import',['token'=>$review['token'],'current_password'=>'Developer-test-password!','confirmation'=>1])->assertRedirect();$this->assertSame(12,DB::table('customers')->count());
  $this->get('/developer/calendar?date='.$pack['date'])->assertOk()->assertSee($pack['batch']);
  $this->post('/developer/test-data/import',['token'=>$review['token'],'current_password'=>'Developer-test-password!','confirmation'=>1])->assertSessionHasErrors('data');$this->assertSame(12,DB::table('customers')->count());
 }
 public function test_import_is_disabled_in_production(): void {
  $original=app()->environment();app()->instance('env','production');$this->assertFalse(app(\App\Services\CcsTestData::class)->available());app()->instance('env',$original);
 }

 public function test_every_developer_template_compiles_to_parseable_php(): void {
  $compiler=app('blade.compiler');
  $files=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views/developer')));
  foreach($files as $file){
   if(!$file->isFile()||!str_ends_with($file->getFilename(),'.blade.php'))continue;
   $compiled=$compiler->compileString(file_get_contents($file->getPathname()));
   try{token_get_all($compiled,TOKEN_PARSE);}catch(\ParseError $e){$this->fail($file->getFilename().': '.$e->getMessage());}
  }
  $this->assertTrue(true);
 }
}
