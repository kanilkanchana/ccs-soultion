<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Services\CcsSqliteBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Foundation\MaintenanceMode;
class CcsBackupTest extends TestCase {
 private string $work;
 protected function setUp(): void {
  parent::setUp();if(!class_exists(\SQLite3::class)||!class_exists(\ZipArchive::class))$this->markTestSkipped('sqlite3 and zip extensions required');
  $this->work=sys_get_temp_dir().'/ccs-backup-test-'.Str::uuid();mkdir($this->work,0700,true);touch($this->work.'/live.sqlite');
  config(['database.default'=>'sqlite','database.connections.sqlite.database'=>$this->work.'/live.sqlite','cache.default'=>'array','session.driver'=>'array','ccs_backups.root'=>$this->work.'/private','ccs_admin.owner_emails'=>['owner@example.test']]);DB::purge('sqlite');
  $this->artisan('migrate',['--database'=>'sqlite','--force'=>true])->assertExitCode(0);
  DB::table('users')->insert(['id'=>1,'name'=>'Owner','email'=>'owner@example.test','password'=>Hash::make('Owner-test-password123!'),'ccs_role'=>'owner','ccs_active'=>true]);
  DB::table('customers')->insert(['id'=>1,'customer_code'=>'BEFORE','name'=>'Before snapshot']);$this->actingAs(User::findOrFail(1));
 }
 protected function tearDown(): void {if(isset($this->work)){DB::purge('sqlite');File::deleteDirectory($this->work);}parent::tearDown();}
 public function test_only_owner_can_access_backup_routes(): void {
  config(['ccs_admin.owner_emails'=>[]]);$user=User::findOrFail(1);$user->ccs_role='manager';$user->save();$this->actingAs($user);
  $this->get('/ccs/settings/backups')->assertForbidden();$this->post('/ccs/settings/backups/create')->assertForbidden();$this->post('/ccs/settings/backups/prepare')->assertForbidden();
 }
 public function test_download_does_not_change_business_data_and_archive_is_checked(): void {
  $response=$this->post('/ccs/settings/backups/create');$response->assertOk()->assertStreamed()->assertDownload()->assertHeader('Content-Type','application/zip')->assertHeader('Cache-Control','no-store, private');$this->assertSame(1,DB::table('customers')->count());
  $payload=$response->streamedContent();$this->assertStringStartsWith("PK\x03\x04",$payload);$this->assertSame((string)strlen($payload),$response->headers->get('Content-Length'));$this->assertSame(hash('sha256',$payload),$response->headers->get('X-CCS-Archive-SHA256'));
  $backup=app(CcsSqliteBackup::class)->listing()[0];$this->assertSame(1,$backup['counts']['customers']);$this->assertFileExists(app(CcsSqliteBackup::class)->archive($backup['id']));$savedResponse=$this->get('/ccs/settings/backups/'.$backup['id'].'/download');$this->assertSame($payload,$savedResponse->streamedContent());$savedResponse->assertOk()->assertDownload('CCS_Backup_'.\Illuminate\Support\Carbon::parse($backup['created_at'])->timezone('Asia/Colombo')->format('Y-m-d_H-i-s').'_'.substr($backup['id'],0,8).'.zip')->assertHeader('Cache-Control','no-store, private');$this->assertDatabaseHas('audit_logs',['action'=>'database_backup_created']);
 }
 public function test_prepare_checks_password_and_never_restores_live_data(): void {
  $service=app(CcsSqliteBackup::class);$backup=$service->create(1);DB::table('customers')->where('id',1)->update(['name'=>'After snapshot']);
  $file=new UploadedFile($service->archive($backup['id']),'backup.zip','application/zip',null,true);
  $this->post('/ccs/settings/backups/prepare',['backup_file'=>$file,'password'=>'wrong','confirmation'=>1])->assertSessionHasErrors('password');
  $this->post('/ccs/settings/backups/prepare',['backup_file'=>$file,'password'=>'Owner-test-password123!','confirmation'=>1])->assertRedirect();$this->assertDatabaseHas('customers',['id'=>1,'name'=>'After snapshot']);
 }
 public function test_isolated_restore_recovers_snapshot_and_keeps_safety_backup_and_audit(): void {
  $service=app(CcsSqliteBackup::class);$backup=$service->create(1);DB::table('customers')->where('id',1)->update(['name'=>'After snapshot']);$job=$service->stage($service->archive($backup['id']),'owner@example.test');
  $maintenance=\Mockery::mock(MaintenanceMode::class);$maintenance->shouldReceive('active')->andReturn(true);$this->app->instance(MaintenanceMode::class,$maintenance);
  $result=$service->restore($job['id']);$this->assertDatabaseHas('customers',['id'=>1,'name'=>'Before snapshot']);$this->assertSame('completed',$result['state']);$this->assertFileExists($service->archive($result['safety_backup_id']));$this->assertNotSame('initial',$service->epoch());$this->assertDatabaseHas('audit_logs',['action'=>'database_restored']);
  $this->expectException(\RuntimeException::class);$service->restore($job['id']);
 }
 public function test_checksum_tampering_is_rejected_without_live_changes(): void {
  $service=app(CcsSqliteBackup::class);$backup=$service->create(1);$copy=$this->work.'/tampered.zip';copy($service->archive($backup['id']),$copy);$zip=new \ZipArchive();$zip->open($copy);$zip->addFromString('database.sqlite','not a database');$zip->close();
  try{$service->stage($copy,'owner@example.test');$this->fail('Tampered archive accepted');}catch(\RuntimeException $e){$this->assertDatabaseHas('customers',['id'=>1,'name'=>'Before snapshot']);}
 }
 public function test_restore_is_blocked_outside_maintenance(): void {
  $service=app(CcsSqliteBackup::class);$backup=$service->create(1);$job=$service->stage($service->archive($backup['id']),'owner@example.test');$this->expectException(\RuntimeException::class);$service->restore($job['id']);
 }
 public function test_schema_change_and_expired_request_cannot_restore(): void {
  $service=app(CcsSqliteBackup::class);$backup=$service->create(1);config(['ccs_backups.request_minutes'=>-1]);$job=$service->stage($service->archive($backup['id']),'owner@example.test');
  $maintenance=\Mockery::mock(MaintenanceMode::class);$maintenance->shouldReceive('active')->andReturn(true);$this->app->instance(MaintenanceMode::class,$maintenance);
  try{$service->restore($job['id']);$this->fail('Expired request accepted');}catch(\RuntimeException $e){$this->assertDatabaseHas('customers',['id'=>1,'name'=>'Before snapshot']);}
  DB::statement('ALTER TABLE customers ADD COLUMN isolated_test_extra TEXT');
  $this->expectException(\RuntimeException::class);$service->stage($service->archive($backup['id']),'owner@example.test');
 }
 private function webMaintenance(): void {
  $active=false;$maintenance=\Mockery::mock(MaintenanceMode::class);
  $maintenance->shouldReceive('active')->andReturnUsing(function()use(&$active){return $active;});
  $maintenance->shouldReceive('activate')->andReturnUsing(function($data)use(&$active){$active=true;});
  $maintenance->shouldReceive('deactivate')->andReturnUsing(function()use(&$active){$active=false;});
  $this->app->instance(MaintenanceMode::class,$maintenance);
 }
 public function test_owner_can_finish_restore_from_browser_without_terminal(): void {
  $this->webMaintenance();$service=app(CcsSqliteBackup::class);$backup=$service->create(1);
  DB::table('customers')->insert(['id'=>2,'customer_code'=>'AFTER','name'=>'Added after backup']);
  $job=$service->stage($service->archive($backup['id']),'owner@example.test');
  $this->get('/ccs/settings/backups/'.$job['id'].'/review')->assertOk()->assertSee('Restore Now');
  $response=$this->post('/ccs/settings/backups/'.$job['id'].'/restore',['password'=>'Owner-test-password123!','confirmation'=>1]);
  $response->assertOk()->assertSee('Restore Complete')->assertSee('Sign In Again');
  $this->assertDatabaseMissing('customers',['customer_code'=>'AFTER']);$this->assertDatabaseHas('customers',['customer_code'=>'BEFORE']);
  $completed=$service->job($job['id']);$this->assertSame('completed',$completed['state']);$this->assertFileExists($service->archive($completed['safety_backup_id']));$this->assertFalse(app()->isDownForMaintenance());
  $this->actingAs(User::findOrFail(1));$this->post('/ccs/settings/backups/'.$job['id'].'/restore',['password'=>'Owner-test-password123!','confirmation'=>1])->assertSessionHasErrors('restore');
 }
 public function test_web_restore_wrong_password_or_missing_confirmation_never_changes_data(): void {
  $this->webMaintenance();$service=app(CcsSqliteBackup::class);$backup=$service->create(1);$job=$service->stage($service->archive($backup['id']),'owner@example.test');
  DB::table('customers')->where('id',1)->update(['name'=>'Keep this change']);
  $this->post('/ccs/settings/backups/'.$job['id'].'/restore',['password'=>'wrong','confirmation'=>1])->assertSessionHasErrors('password');
  $this->post('/ccs/settings/backups/'.$job['id'].'/restore',['password'=>'Owner-test-password123!'])->assertSessionHasErrors('confirmation');
  $this->assertDatabaseHas('customers',['name'=>'Keep this change']);$this->assertSame('ready',$service->job($job['id'])['state']);$this->assertFalse(app()->isDownForMaintenance());
 }
 public function test_nonowner_cannot_apply_restore_by_direct_url(): void {
  config(['ccs_admin.owner_emails'=>[]]);$user=User::findOrFail(1);$user->ccs_role='manager';$user->save();$this->actingAs($user);
  $this->post('/ccs/settings/backups/'.Str::uuid().'/restore',['password'=>'Owner-test-password123!','confirmation'=>1])->assertForbidden();
 }
 public function test_all_reports_zip_has_dated_csv_folder_and_no_staff_password_export(): void {
  $export=app(\App\Services\CcsReportExport::class)->create(1);$file=app(\App\Services\CcsReportExport::class)->file($export['id']);$zip=new \ZipArchive();$this->assertTrue($zip->open($file['path'],\ZipArchive::CHECKCONS)===true);
  $this->assertSame(24,$export['files']);$csv=$zip->getFromName($export['folder'].'/staff_directory.csv');$this->assertIsString($csv);$this->assertStringNotContainsString('password',$csv);$this->assertStringNotContainsString('remember_token',$csv);$this->assertIsString($zip->getFromName($export['folder'].'/README.txt'));$zip->close();
  $reportResponse=$this->get('/ccs/settings/backups/reports/'.$export['id']);$this->assertSame(file_get_contents($file['path']),$reportResponse->streamedContent());$reportResponse->assertOk()->assertStreamed()->assertDownload($export['filename'])->assertHeader('Content-Type','application/zip')->assertHeader('Content-Encoding','identity');
  $this->assertSame(1,DB::table('customers')->count());
 }
}
