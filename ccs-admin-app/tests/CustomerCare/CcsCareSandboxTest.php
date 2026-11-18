<?php
namespace Tests\CustomerCare;
use App\Services\{CcsCareScope,CcsCareTransport,CcsCareOutbox,CcsSqliteBackup,CcsSerialLookupService};
use App\Http\Controllers\CcsCareController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\{DB,Schema,Http,Hash};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
class CcsCareSandboxTest extends TestCase {
    private $app; private string $temp; private string $code=''; private $session;
    protected function setUp(): void {
        parent::setUp();$this->temp=sys_get_temp_dir().'/ccs-care-'.bin2hex(random_bytes(6));
        foreach(['config','bootstrap/cache','storage/framework/views','routes'] as $d)mkdir($this->temp.'/'.$d,0700,true);
        file_put_contents($this->temp.'/bootstrap/providers.php','<?php return [];');
        file_put_contents($this->temp.'/composer.json','{"autoload":{"psr-4":{"App\\\\":"app/"}}}');
        $this->app=Application::configure(basePath:$this->temp)->withRouting(using:static function(){})->withExceptions(static function(){})->withMiddleware(static function(){})->create();
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $this->app->instance('env','local');
        config(['app.debug'=>true,'app.env'=>'local','app.key'=>'base64:'.base64_encode(str_repeat('x',32)),'app.cipher'=>'AES-256-CBC',
          'database.default'=>'sqlite','database.connections.sqlite'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],
          'cache.default'=>'array','cache.stores.array'=>['driver'=>'array','serialize'=>false],
          'hashing.driver'=>'bcrypt','hashing.bcrypt.rounds'=>4,
          'view.paths'=>[dirname(__DIR__,2).'/resources/views'],'view.compiled'=>$this->temp.'/storage/framework/views',
          'ccs_whatsapp_test'=>['enabled'=>true,'access_token'=>'FAKE_TEST_TOKEN','phone_number_id'=>'123456789','recipient'=>'94770000000','graph_version'=>'v25.0'],
          'ccs_care_sandbox'=>['enabled'=>true,'customer_id'=>1,'otp_template'=>'ccs_verify','language'=>'en_US','notifications_enabled'=>false,'invoice_template'=>'ccs_invoice','payment_template'=>'ccs_payment','status_template'=>'ccs_status']]);
        $backup=$this->createMock(CcsSqliteBackup::class);$backup->method('epoch')->willReturn('test-epoch');$backup->method('root')->willReturn($this->temp);$this->app->instance(CcsSqliteBackup::class,$backup);
        $lookup=$this->createMock(CcsSerialLookupService::class);$lookup->method('warranties')->willReturn([]);$this->app->instance(CcsSerialLookupService::class,$lookup);
        Schema::create('customers',function(Blueprint $t){$t->id();$t->string('name');$t->string('customer_code');$t->string('phone');$t->string('whatsapp')->nullable();$t->softDeletes();});
        Schema::create('vehicles',function(Blueprint $t){$t->id();$t->integer('customer_id');$t->string('registration_number');$t->string('make')->default('Toyota');$t->string('model')->default('Prius');$t->integer('manufacture_year')->default(2016);$t->softDeletes();});
        Schema::create('repair_jobs',function(Blueprint $t){$t->id();$t->integer('vehicle_id');$t->string('job_number');$t->string('status');$t->timestamp('received_at')->nullable();$t->timestamp('completed_at')->nullable();$t->softDeletes();});
        Schema::create('invoices',function(Blueprint $t){$t->id();$t->integer('repair_job_id');$t->string('invoice_number');$t->decimal('total_lkr',12,2);$t->string('status');$t->timestamp('issued_at')->nullable();});
        Schema::create('payments',function(Blueprint $t){$t->id();$t->integer('invoice_id');$t->decimal('amount_lkr',12,2);});
        Schema::create('invoice_items',function(Blueprint $t){$t->id();$t->integer('invoice_id');$t->string('description');$t->string('serial_number')->nullable();});
        Schema::create('battery_service_records',function(Blueprint $t){$t->id();$t->integer('vehicle_id');$t->integer('repair_job_id');});
        Schema::create('battery_module_readings',function(Blueprint $t){$t->id();$t->integer('battery_service_record_id');$t->string('serial_number');$t->decimal('voltage',8,3)->nullable();});
        Schema::create('audit_logs',function(Blueprint $t){$t->id();$t->string('action');$t->string('entity_type');$t->integer('entity_id')->nullable();$t->text('metadata')->nullable();$t->integer('user_id')->nullable();$t->timestamps();});
        (require dirname(__DIR__,2).'/database/migrations/2026_10_28_000023_create_ccs_care_sandbox_tables.php')->up();
        DB::table('customers')->insert([['id'=>1,'name'=>'TEST Kasun','customer_code'=>'TEST-C1','phone'=>'0770000000'],['id'=>2,'name'=>'OTHER CUSTOMER','customer_code'=>'OTHER-C2','phone'=>'0770000001']]);
        DB::table('vehicles')->insert([['id'=>1,'customer_id'=>1,'registration_number'=>'TEST-4821'],['id'=>2,'customer_id'=>2,'registration_number'=>'OTHER-4822']]);
        DB::table('repair_jobs')->insert([['id'=>1,'vehicle_id'=>1,'job_number'=>'TEST-J1','status'=>'in_progress'],['id'=>2,'vehicle_id'=>2,'job_number'=>'OTHER-J2','status'=>'ready']]);
        DB::table('invoices')->insert([['id'=>1,'repair_job_id'=>1,'invoice_number'=>'TEST-I1','total_lkr'=>10000,'status'=>'partially_paid'],['id'=>2,'repair_job_id'=>2,'invoice_number'=>'OTHER-I2','total_lkr'=>20000,'status'=>'paid']]);
        DB::table('payments')->insert(['invoice_id'=>1,'amount_lkr'=>4000]);
        $this->session=new Store('ccs-test',new ArraySessionHandler(120));$this->session->start();
        $this->app->instance('session',$this->session);$this->app->instance('session.store',$this->session);
        (require dirname(__DIR__,2).'/database/migrations/2026_10_28_000024_add_care_message_controls.php')->up();
        // Most regression tests exercise enabled API behavior; migration defaults are checked separately.
        DB::table('ccs_care_state')->where('key','developer_features_v3')->delete();
        Http::preventStrayRequests();
    }
    protected function tearDown(): void {
        \Illuminate\Foundation\Bootstrap\HandleExceptions::flushState($this);$this->app->make('db')->disconnect();\Illuminate\Support\Facades\Facade::clearResolvedInstances();$this->app->flush();\Illuminate\Container\Container::setInstance(null);
        $it=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temp,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $f){$f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname());}rmdir($this->temp);parent::tearDown();
    }
    private function req(array $data=[]): Request {$r=Request::create('/customer-care','POST',$data,[],[],['REMOTE_ADDR'=>'127.0.0.1']);$r->setLaravelSession($this->session);$this->app->instance('request',$r);return $r;}
    private function transport(): CcsCareTransport {$mock=$this->createMock(CcsCareTransport::class);$mock->method('otp')->willReturnCallback(function($code){$this->code=$code;return 'wamid.TEST';});return $mock;}
    private function challenge(string $plate='TEST-4821'): array {return (new CcsCareController)->requestOtp($this->req(['plate'=>$plate]),new CcsCareScope,$this->transport())->getData(true);}
    private function verifyCode(string $id,string $code){return (new CcsCareController)->verify($this->req(['challenge'=>$id,'code'=>$code]),new CcsCareScope);}
    private function event(string $action,int $id,array $meta=[]): void {DB::table('audit_logs')->insert(['action'=>$action,'entity_type'=>$action==='repair_job_status_updated'?'repair_job':'invoice','entity_id'=>$id,'metadata'=>json_encode($meta)]);}
    public function test_verified_records_are_scoped_and_show_correct_due(): void {$c=$this->challenge();$this->verifyCode($c['challenge'],$this->code);$data=(new CcsCareController)->records($this->req(),new CcsCareScope)->getData(true);self::assertCount(1,$data['vehicles']);self::assertSame('TEST-4821',$data['vehicles'][0]['vehicle']['registration_number']);self::assertEquals(6000,$data['vehicles'][0]['invoices'][0]['due_lkr']);self::assertStringNotContainsString('OTHER',json_encode($data));}
    public function test_otp_is_hashed_and_single_use(): void {$c=$this->challenge();$stored=DB::table('ccs_care_challenges')->first();self::assertNotSame($this->code,$stored->otp_hash);self::assertTrue(Hash::check($this->code,$stored->otp_hash));$this->verifyCode($c['challenge'],$this->code);$this->expectException(ValidationException::class);$this->verifyCode($c['challenge'],$this->code);}
    public function test_invalid_plate_has_generic_reply_and_no_otp(): void {$c=$this->challenge('UNKNOWN');self::assertSame('',$this->code);self::assertArrayHasKey('challenge',$c);self::assertStringNotContainsString('UNKNOWN',json_encode($c));self::assertSame('dummy',DB::table('ccs_care_challenges')->first()->state);}
    public function test_other_customer_vehicle_cannot_receive_code(): void {$this->challenge('OTHER-4822');self::assertSame('',$this->code);}
    public function test_ambiguous_plate_is_blocked(): void {DB::table('vehicles')->insert(['customer_id'=>2,'registration_number'=>'TEST 4821']);$this->challenge();self::assertSame('',$this->code);}
    public function test_five_bad_codes_lock_challenge(): void {$c=$this->challenge();$bad=$this->code==='111111'?'222222':'111111';for($n=0;$n<5;$n++){try{$this->verifyCode($c['challenge'],$bad);}catch(ValidationException){}}self::assertSame('locked',DB::table('ccs_care_challenges')->first()->state);$this->expectException(ValidationException::class);$this->verifyCode($c['challenge'],$this->code);}
    public function test_expired_code_is_blocked(): void {$c=$this->challenge();DB::table('ccs_care_challenges')->update(['expires_at'=>now()->subSecond()]);$this->expectException(ValidationException::class);$this->verifyCode($c['challenge'],$this->code);}
    public function test_code_from_different_session_is_blocked(): void {$c=$this->challenge();$this->session->forget('ccs_care_nonce');$this->expectException(ValidationException::class);$this->verifyCode($c['challenge'],$this->code);}
    public function test_private_records_require_verification(): void {$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);(new CcsCareController)->records($this->req(),new CcsCareScope);}
    public function test_production_environment_is_blocked(): void {$this->app->instance('env','production');$this->expectException(RuntimeException::class);(new CcsCareScope)->customer();}
    public function test_saved_customer_number_cannot_be_redirected_to_developer(): void {DB::table('customers')->where('id',1)->update(['phone'=>'0770000009']);$this->expectException(RuntimeException::class);(new CcsCareScope)->customer();}
    public function test_rate_limit_prevents_resend_storm(): void {$this->challenge();$this->expectException(ValidationException::class);$this->challenge();}
    public function test_non_loopback_requests_are_blocked(): void {$r=$this->req();$r->server->set('REMOTE_ADDR','192.0.2.5');$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);(new \App\Http\Middleware\CcsCareLocalOnly)->handle($r,fn()=>response('should not run'));}
    public function test_baseline_never_sends_old_history_and_collect_is_idempotent(): void {$this->event('invoice_issued',1);$o=new CcsCareOutbox;$o->initialize();self::assertSame(0,$o->collect());$this->event('payment_received',1,['payment_lkr'=>1000]);$this->event('invoice_issued',2);$this->event('repair_job_status_updated',1,['from'=>'ready','to'=>'ready']);self::assertSame(1,$o->collect());self::assertSame(0,$o->collect());self::assertSame(0,$o->dispatch());self::assertSame(1,DB::table('ccs_care_outbox')->count());}
    public function test_otp_payload_uses_authentication_template_and_fixed_recipient(): void {Http::fakeSequence()->push(['display_phone_number'=>'15551450250'])->push(['messages'=>[['id'=>'wamid.TEST']]]);self::assertSame('wamid.TEST',(new CcsCareTransport)->otp('123456'));Http::assertSent(fn($r)=>$r->method()==='POST'&&$r['to']==='94770000000'&&$r['template']['components'][1]['parameters'][0]['text']==='123456');}
    public function test_template_sender_guard_prevents_production_send(): void {Http::fake(['*'=>Http::response(['display_phone_number'=>'94110000000'])]);try{(new CcsCareTransport)->otp('123456');self::fail('Real sender accepted');}catch(RuntimeException $e){self::assertStringContainsString('not the CCS Meta sandbox sender',$e->getMessage());}Http::assertSentCount(1);}
    public function test_view_compiles_and_does_not_include_demo_otp_bypass(): void {$view=view('customer-care.page',['careBoot'=>['demo'=>false,'base'=>'/customer-care','homeUrl'=>'#']])->render();self::assertStringContainsString('YOUR VEHICLE.',$view);self::assertStringContainsString('"demo":false',$view);self::assertStringNotContainsString('FAKE_TEST_TOKEN',$view);}
    public function test_outbox_sends_new_invoice_payment_and_status_once_only(): void {
        config(['ccs_care_sandbox.notifications_enabled'=>true]);$o=new CcsCareOutbox;$o->initialize();
        $this->event('invoice_issued',1);$this->event('payment_received',1,['payment_lkr'=>4000]);$this->event('repair_job_status_updated',1,['from'=>'vehicle_received','to'=>'in_progress']);
        self::assertSame(3,$o->collect());$transport=$this->createMock(CcsCareTransport::class);$transport->expects(self::exactly(3))->method('update')->willReturn('wamid.TEST');$this->app->instance(CcsCareTransport::class,$transport);
        self::assertSame(3,$o->dispatch());self::assertSame(0,$o->dispatch());self::assertSame(0,$o->collect());self::assertSame(3,DB::table('ccs_care_outbox')->where('state','accepted')->count());
    }
    public function test_outbox_timeout_is_not_retried(): void {
        config(['ccs_care_sandbox.notifications_enabled'=>true]);$o=new CcsCareOutbox;$o->initialize();$this->event('invoice_issued',1);$o->collect();
        $transport=$this->createMock(CcsCareTransport::class);$transport->expects(self::once())->method('update')->willThrowException(new RuntimeException('UNKNOWN delivery: timeout'));$this->app->instance(CcsCareTransport::class,$transport);
        self::assertSame(0,$o->dispatch());self::assertSame(0,$o->dispatch());self::assertSame('unknown',DB::table('ccs_care_outbox')->first()->state);
    }
    public function test_expired_grant_cannot_read_records(): void {
        $c=$this->challenge();$this->verifyCode($c['challenge'],$this->code);$g=$this->session->get('ccs_care_grant');$g['until']=time()-1;$this->session->put('ccs_care_grant',$g);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);(new CcsCareController)->records($this->req(),new CcsCareScope);
    }
    public function test_restore_epoch_invalidates_otp(): void {
        $c=$this->challenge();DB::table('ccs_care_challenges')->update(['epoch'=>'old-epoch']);$this->expectException(ValidationException::class);$this->verifyCode($c['challenge'],$this->code);
    }
    public function test_local_http_page_has_security_headers_and_csrf_protection(): void {
        copy(dirname(__DIR__,2).'/routes/ccs_care_sandbox.php',$this->temp.'/routes/ccs_care_sandbox.php');
        $this->app->register(\App\Providers\CcsCareSandboxServiceProvider::class);
        config(['session.driver'=>'array','session.cookie'=>'ccs-care-test','session.encrypt'=>false]);
        $this->app->forgetInstance('session.store');$this->app->instance('session',new \Illuminate\Session\SessionManager($this->app));
        $kernel=$this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $page=$kernel->handle(Request::create('/customer-care','GET',[],[],[],['REMOTE_ADDR'=>'127.0.0.1','HTTP_ACCEPT'=>'application/json']));
        self::assertSame(200,$page->getStatusCode(),substr($page->getContent(),0,2000));self::assertStringContainsString('no-store',$page->headers->get('Cache-Control'));self::assertSame('DENY',$page->headers->get('X-Frame-Options'));
        $post=$kernel->handle(Request::create('/customer-care/otp','POST',['plate'=>'TEST-4821'],[],[],['REMOTE_ADDR'=>'127.0.0.1','HTTP_ACCEPT'=>'application/json']));
        self::assertSame(419,$post->getStatusCode());
    }

    private function settingsRequest(array $replace=[],string $role='owner'): Request {
        $data=array_merge(['current_password'=>'TEST_OWNER_PASSWORD','enabled'=>'1','notifications_enabled'=>'1','access_token'=>'NEW_PRIVATE_TOKEN','phone_number_id'=>'123456789','recipient'=>'94770000000','graph_version'=>'v25.0','customer_id'=>'1','language'=>'en_US','otp_template'=>'ccs_verify','invoice_template'=>'ccs_invoice','payment_template'=>'ccs_payment','status_template'=>'ccs_status'],$replace);
        $r=$this->req($data);$owner=new class {public $id=1;public $email='test@example.invalid';public $ccs_role='owner';public $password;public function getAuthPassword(){return $this->password;}};
        $owner->ccs_role=$role;$owner->password=Hash::make('TEST_OWNER_PASSWORD');$r->setUserResolver(fn()=>$owner);config(['ccs_admin.owner_emails'=>[]]);return $r;
    }
    public function test_owner_settings_encrypt_token_and_never_return_it_in_public_values(): void {
        $settings=new \App\Services\CcsMessagingSettings;
        $r=(new \App\Http\Controllers\CcsWhatsAppSettingsController)->save($this->settingsRequest(),$settings);
        self::assertSame(302,$r->getStatusCode());$raw=DB::table('ccs_care_state')->where('key','owner_whatsapp_settings')->value('value');
        self::assertStringNotContainsString('NEW_PRIVATE_TOKEN',$raw);self::assertSame('NEW_PRIVATE_TOKEN',$settings->load()['access_token']);self::assertArrayNotHasKey('access_token',$settings->publicValues());self::assertTrue($settings->publicValues()['has_token']);self::assertTrue(DB::table('ccs_care_state')->where('key','baseline')->exists());
        self::assertStringNotContainsString('NEW_PRIVATE_TOKEN',json_encode(DB::table('audit_logs')->get()));
    }
    public function test_manager_cannot_save_whatsapp_credentials(): void {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new \App\Http\Controllers\CcsWhatsAppSettingsController)->save($this->settingsRequest([],'manager'),new \App\Services\CcsMessagingSettings);
    }
    public function test_wrong_password_does_not_save_or_flash_the_token(): void {
        (new \App\Http\Controllers\CcsWhatsAppSettingsController)->save($this->settingsRequest(['current_password'=>'WRONG']),new \App\Services\CcsMessagingSettings);
        self::assertFalse(DB::table('ccs_care_state')->where('key','owner_whatsapp_settings')->exists());self::assertStringNotContainsString('NEW_PRIVATE_TOKEN',json_encode($this->session->all()));self::assertStringNotContainsString('WRONG',json_encode($this->session->all()));
    }
    public function test_invalid_token_validation_does_not_flash_secret_input(): void {
        (new \App\Http\Controllers\CcsWhatsAppSettingsController)->save($this->settingsRequest(['access_token'=>'PRIVATE TOKEN WITH SPACES']),new \App\Services\CcsMessagingSettings);
        self::assertArrayNotHasKey('access_token',$this->session->get('_old_input',[]));self::assertArrayNotHasKey('current_password',$this->session->get('_old_input',[]));
    }
    public function test_blank_token_preserves_saved_token_and_clear_disables_it(): void {
        $controller=new \App\Http\Controllers\CcsWhatsAppSettingsController;$settings=new \App\Services\CcsMessagingSettings;
        $controller->save($this->settingsRequest(),$settings);$controller->save($this->settingsRequest(['access_token'=>'']),$settings);self::assertSame('NEW_PRIVATE_TOKEN',$settings->load()['access_token']);
        $controller->save($this->settingsRequest(['access_token'=>'','clear_token'=>'1','enabled'=>'0','notifications_enabled'=>'0']),$settings);self::assertSame('',$settings->load()['access_token']);self::assertFalse(config('ccs_whatsapp_test.enabled'));
    }
    public function test_assignment_without_status_change_and_new_assigned_job_create_notifications(): void {
        $o=new CcsCareOutbox;$o->initialize();
        $this->event('repair_job_status_updated',1,['from'=>'in_progress','to'=>'in_progress','previous_technician'=>null,'technician'=>'TEST TECH']);
        DB::table('audit_logs')->insert(['action'=>'repair_job_created','entity_type'=>'repair_job','entity_id'=>1,'metadata'=>json_encode(['status'=>'vehicle_received','technician'=>'TEST TECH'])]);
        self::assertSame(2,$o->collect());foreach(DB::table('ccs_care_outbox')->get() as $row)self::assertSame('technician assigned',json_decode($row->parameters,true)['body'][2]);
    }
    public function test_business_request_automatically_processes_notifications(): void {
        config(['ccs_care_sandbox.notifications_enabled'=>true]);$mock=$this->createMock(CcsCareOutbox::class);$mock->expects(self::once())->method('collect')->willReturn(1);$mock->expects(self::once())->method('dispatch')->willReturn(1);$this->app->instance(CcsCareOutbox::class,$mock);
        $r=$this->req();$route=new \Illuminate\Routing\Route('POST','ccs/invoices/1/finalize',fn()=>null);$route->name('ccs.invoices.finalize');$r->setRouteResolver(fn()=>$route);
        (new \App\Services\CcsCareAutoMessages)->afterRequest($r);
    }
    public function test_notification_failure_does_not_break_completed_checkout(): void {
        config(['ccs_care_sandbox.notifications_enabled'=>true]);$mock=$this->createMock(CcsCareOutbox::class);$mock->method('collect')->willThrowException(new RuntimeException('SENSITIVE_RAW_FAILURE'));$this->app->instance(CcsCareOutbox::class,$mock);
        $r=$this->req();$route=new \Illuminate\Routing\Route('POST','ccs/invoices/1/finalize',fn()=>null);$route->name('ccs.invoices.finalize');$r->setRouteResolver(fn()=>$route);
        (new \App\Services\CcsCareAutoMessages)->afterRequest($r);self::assertStringNotContainsString('SENSITIVE_RAW_FAILURE',DB::table('ccs_care_state')->where('key','last_worker_error')->value('value'));self::assertEquals(10000,DB::table('invoices')->where('id',1)->value('total_lkr'));
    }
    public function test_corrupt_encrypted_settings_disable_sending(): void {
        DB::table('ccs_care_state')->insert(['key'=>'owner_whatsapp_settings','value'=>'CORRUPTED','created_at'=>now(),'updated_at'=>now()]);(new \App\Services\CcsMessagingSettings)->apply();self::assertFalse(config('ccs_care_sandbox.enabled'));self::assertFalse(config('ccs_whatsapp_test.enabled'));
    }

    public function test_settings_view_contains_no_saved_token_and_compiles(): void {
        $settings=new \App\Services\CcsMessagingSettings;(new \App\Http\Controllers\CcsWhatsAppSettingsController)->save($this->settingsRequest(),$settings);
        copy(dirname(__DIR__,2).'/routes/ccs_care_sandbox.php',$this->temp.'/routes/ccs_care_sandbox.php');$this->app->register(\App\Providers\CcsCareSandboxServiceProvider::class);
        $this->app['router']->get('/ccs/settings',fn()=>'Settings')->name('ccs.settings');$this->app['router']->getRoutes()->refreshNameLookups();
        mkdir($this->temp.'/views/ccs',0700,true);file_put_contents($this->temp.'/views/ccs/layout.blade.php',"@yield('content')");$this->app['view']->getFinder()->setPaths([$this->temp.'/views',dirname(__DIR__,2).'/resources/views']);
        $html=view('ccs.whatsapp-settings',['ready'=>true,'values'=>$settings->publicValues(),'customers'=>DB::table('customers')->get(),'recent'=>collect(),'otpError'=>null])->render();
        self::assertStringContainsString('Save WhatsApp Settings',$html);self::assertStringNotContainsString('NEW_PRIVATE_TOKEN',$html);self::assertStringContainsString('leave blank to keep it',$html);
    }

    private function flags(array $overrides=[]): \App\Services\CcsCareFeatures {
        $f=new \App\Services\CcsCareFeatures;$f->save(array_merge($f::DEFAULTS,$overrides));return $f;
    }
    public function test_direct_lookup_works_without_api_token_and_never_calls_meta(): void {
        $this->flags(['api_enabled'=>false,'direct_lookup'=>true]);config(['ccs_whatsapp_test.access_token'=>'']);
        $r=(new CcsCareController)->direct($this->req(['plate'=>'test 4821']),new CcsCareScope);self::assertTrue($r->getData(true)['opened']);
        $data=(new CcsCareController)->records($this->req(),new CcsCareScope)->getData(true);self::assertSame('TEST Kasun',$data['customer']['name']);self::assertCount(1,$data['vehicles']);Http::assertNothingSent();
    }
    public function test_direct_endpoint_rejects_default_otp_mode(): void {$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);(new CcsCareController)->direct($this->req(['plate'=>'TEST-4821']),new CcsCareScope);}
    public function test_direct_lookup_rejects_other_customer(): void {$this->flags(['direct_lookup'=>true]);$this->expectException(ValidationException::class);(new CcsCareController)->direct($this->req(['plate'=>'OTHER-4822']),new CcsCareScope);}
    public function test_direct_lookup_rejects_ambiguous_plate(): void {$this->flags(['direct_lookup'=>true]);DB::table('vehicles')->insert(['customer_id'=>2,'registration_number'=>'TEST 4821']);$this->expectException(ValidationException::class);(new CcsCareController)->direct($this->req(['plate'=>'TEST-4821']),new CcsCareScope);}
    public function test_changing_developer_policy_revokes_customer_access(): void {
        $this->flags(['direct_lookup'=>true]);(new CcsCareController)->direct($this->req(['plate'=>'TEST-4821']),new CcsCareScope);$this->flags(['direct_lookup'=>false]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);(new CcsCareController)->records($this->req(),new CcsCareScope);
    }
    public function test_api_master_blocks_sample_and_otp_before_network(): void {
        $this->flags(['api_enabled'=>false]);foreach([fn()=>(new \App\Services\CcsWhatsAppTest)->send(),fn()=>(new CcsCareTransport)->otp('123456')] as $send){try{$send();self::fail('API was not blocked');}catch(RuntimeException $e){self::assertStringContainsString('disabled by the developer',$e->getMessage());}}Http::assertNothingSent();
    }
    public function test_disabled_auto_invoice_does_not_queue_invoice(): void {
        $this->flags(['auto_invoice'=>false]);$o=new CcsCareOutbox;$o->initialize();$this->event('invoice_issued',1);self::assertSame(0,$o->collect());self::assertSame(0,DB::table('ccs_care_outbox')->count());
    }
    public function test_message_contract_contains_total_paid_due_and_vehicle_job_status(): void {
        $b=new \App\Services\CcsCareMessage;$i=$b->build('invoice',1);self::assertSame(['TEST-I1','10000.00','4000.00','6000.00'],$i['body']);
        DB::table('payments')->insert(['invoice_id'=>1,'amount_lkr'=>1000]);$id=(int)DB::table('payments')->max('id');$p=$b->build('payment',$id);self::assertSame(['TEST-I1','1000.00','5000.00','5000.00'],$p['body']);
        self::assertSame(['TEST-4821','TEST-J1','in progress'],$b->build('status',1)['body']);
    }
    public function test_message_builder_rejects_other_customer_invoice(): void {$this->expectException(RuntimeException::class);(new \App\Services\CcsCareMessage)->build('invoice',2);}
    public function test_pdf_generation_contains_invoice_balances_and_serials(): void {
        symlink(dirname(__DIR__,2).'/ccs-care-pdf',$this->temp.'/ccs-care-pdf');
        try{$s=(new \App\Services\CcsCareMessage)->build('invoice',1)['snapshot'];$s['customer']['name']='කසුන් පෙරේරා / குமார்';$s['items']=[['description'=>'ABS Pump / බැටරි කොටස','serial_number'=>'TEST-ABS-001','quantity'=>1,'unit_price_lkr'=>10000,'line_total_lkr'=>10000]];
            $bytes=(new \App\Services\CcsCarePdf)->bytes($s);self::assertStringStartsWith('%PDF-',$bytes);self::assertGreaterThan(5000,strlen($bytes));file_put_contents('/tmp/CCS-v3-test-invoice.pdf',$bytes);
        }finally{unlink($this->temp.'/ccs-care-pdf');}
    }
    public function test_invoice_upload_and_document_template_have_correct_four_parameters(): void {
        $pdf=$this->createMock(\App\Services\CcsCarePdf::class);$pdf->method('bytes')->willReturn('%PDF-1.7 TEST');$this->app->instance(\App\Services\CcsCarePdf::class,$pdf);
        $payload=(new \App\Services\CcsCareMessage)->build('invoice',1);$payload['manual']=true;
        Http::fakeSequence()->push(['display_phone_number'=>'15551450250'])->push(['id'=>'123456'])->push(['messages'=>[['id'=>'wamid.PDF']]]);
        self::assertSame('wamid.PDF',(new CcsCareTransport)->update('invoice',$payload));
        Http::assertSent(fn($r)=>str_ends_with($r->url(),'/messages')&&$r['template']['components'][0]['parameters'][0]['document']['id']==='123456'&&count($r['template']['components'][1]['parameters'])===4&&$r['template']['components'][1]['parameters'][3]['text']==='6000.00');Http::assertSentCount(3);
    }
    public function test_failed_media_upload_never_sends_invoice_message(): void {
        $pdf=$this->createMock(\App\Services\CcsCarePdf::class);$pdf->method('bytes')->willReturn('%PDF-TEST');$this->app->instance(\App\Services\CcsCarePdf::class,$pdf);$payload=(new \App\Services\CcsCareMessage)->build('invoice',1);$payload['manual']=true;
        Http::fakeSequence()->push(['display_phone_number'=>'15551450250'])->push(['error'=>['code'=>190]],401);
        try{(new CcsCareTransport)->update('invoice',$payload);self::fail('Upload failure accepted');}catch(RuntimeException $e){self::assertStringContainsString('190',$e->getMessage());}Http::assertSentCount(2);
    }
    public function test_manual_api_send_is_idempotent_even_with_automatic_messages_off(): void {
        $admin=$this->createMock(\App\Services\CcsAdministration::class);$admin->method('role')->willReturn('owner');$this->app->instance(\App\Services\CcsAdministration::class,$admin);
        $transport=$this->createMock(CcsCareTransport::class);$transport->expects(self::once())->method('update')->willReturn('wamid.MANUAL');$key=(string)\Illuminate\Support\Str::uuid();
        $data=['kind'=>'status','id'=>1,'request_key'=>$key,'confirm'=>'1'];$controller=new \App\Http\Controllers\CcsCareMessagesController;
        foreach([1,2] as $x){$r=$this->req($data);$r->setUserResolver(fn($guard=null)=>(object)['id'=>1]);$controller->send($r,new \App\Services\CcsCareMessage,$transport);}
        self::assertSame(1,DB::table('ccs_care_outbox')->where('state','accepted')->count());self::assertSame(1,DB::table('ccs_care_manual_requests')->count());
    }
    public function test_manual_actions_disabled_are_denied_on_server(): void {
        $this->flags(['manual_enabled'=>false]);$admin=$this->createMock(\App\Services\CcsAdministration::class);$admin->method('role')->willReturn('owner');$this->app->instance(\App\Services\CcsAdministration::class,$admin);$r=$this->req();$r->setUserResolver(fn($guard=null)=>(object)['id'=>1]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);(new \App\Http\Controllers\CcsCareMessagesController)->index($r,new \App\Services\CcsCareMessage);
    }
    public function test_developer_toggle_requires_password_and_explicit_direct_consent(): void {
        Schema::create('ccs_developer_events',function(Blueprint $t){$t->id();$t->integer('developer_account_id');$t->string('action');$t->text('details');$t->timestamps();});
        $dev=(object)['id'=>1,'active'=>true,'password'=>Hash::make('TEST_DEVELOPER_PASSWORD')];$f=new \App\Services\CcsCareFeatures;$controller=new \App\Http\Controllers\CcsCareDeveloperController;
        $r=$this->req(['current_password'=>'WRONG','direct_lookup'=>'1','ack_direct'=>'1']);$r->attributes->set('ccs_developer',$dev);$controller->save($r,$f);self::assertFalse($f->on('direct_lookup'));
        $r=$this->req(['current_password'=>'TEST_DEVELOPER_PASSWORD','direct_lookup'=>'1']);$r->attributes->set('ccs_developer',$dev);$controller->save($r,$f);self::assertFalse($f->on('direct_lookup'));
        $r=$this->req(['current_password'=>'TEST_DEVELOPER_PASSWORD','direct_lookup'=>'1','ack_direct'=>'1']);$r->attributes->set('ccs_developer',$dev);$controller->save($r,$f);self::assertTrue($f->on('direct_lookup'));self::assertFalse($f->on('api_enabled'));self::assertSame(1,DB::table('ccs_developer_events')->count());
    }

    public function test_reenabling_api_does_not_replay_events_created_while_disabled(): void {
        $o=new CcsCareOutbox;$o->initialize();$this->flags(['api_enabled'=>false]);$this->event('invoice_issued',1);$this->flags(['api_enabled'=>true]);self::assertSame(0,$o->collect());
    }
    public function test_serial_message_contract_and_one_automatic_message_per_card(): void {
        Schema::create('warranties',function(Blueprint $t){$t->id();$t->integer('repair_job_id');$t->integer('invoice_item_id')->nullable();});
        DB::table('invoice_items')->insert(['id'=>1,'invoice_id'=>1,'description'=>'ABS Pump','serial_number'=>'TEST-ABS-001']);DB::table('warranties')->insert(['id'=>1,'repair_job_id'=>1,'invoice_item_id'=>1]);
        $lookup=$this->createMock(CcsSerialLookupService::class);$lookup->method('warranties')->willReturn([['id'=>1,'covered_serial'=>'TEST-ABS-001','covered_description'=>'ABS Pump','coverage_kind'=>'standard','card_status'=>['card_number'=>'CCS-W000001'],'starts_on'=>'2026-10-28','expires_on'=>'2027-04-27']]);$this->app->instance(CcsSerialLookupService::class,$lookup);
        $body=(new \App\Services\CcsCareMessage)->build('serial',1)['body'];self::assertSame(['TEST-4821','ABS Pump','TEST-ABS-001','CCS-W000001','2026-10-28','2027-04-27'],$body);
        $this->flags(['auto_serial'=>true,'auto_invoice'=>false]);$o=new CcsCareOutbox;$o->initialize();$this->event('invoice_issued',1);self::assertSame(1,$o->collect());
        DB::table('audit_logs')->insert(['action'=>'warranty_record_approved','entity_type'=>'warranty','entity_id'=>1]);self::assertSame(0,$o->collect());self::assertSame(1,DB::table('ccs_care_outbox')->where('kind','serial')->count());
    }
    public function test_developer_and_manual_send_views_compile_without_token_exposure(): void {
        copy(dirname(__DIR__,2).'/routes/ccs_care_sandbox.php',$this->temp.'/routes/ccs_care_sandbox.php');$this->app->register(\App\Providers\CcsCareSandboxServiceProvider::class);
        $this->app['router']->get('/ccs',fn()=>'Dashboard')->name('ccs.dashboard');$this->app['router']->get('/developer',fn()=>'Developer')->name('developer.home');$this->app['router']->getRoutes()->refreshNameLookups();
        mkdir($this->temp.'/views/ccs',0700,true);mkdir($this->temp.'/views/developer',0700,true);file_put_contents($this->temp.'/views/ccs/layout.blade.php',"@yield('content')");file_put_contents($this->temp.'/views/developer/layout.blade.php',"@yield('content')");$this->app['view']->getFinder()->setPaths([$this->temp.'/views',dirname(__DIR__,2).'/resources/views']);
        $payload=(new \App\Services\CcsCareMessage)->build('invoice',1);
        $html=view('ccs.care-messages',['kind'=>'invoice','id'=>1,'message'=>$payload,'error'=>null,'phone'=>'94770000000','requestKey'=>(string)\Illuminate\Support\Str::uuid(),'api'=>true,'recent'=>collect()])->render();
        self::assertStringContainsString('6000.00',$html);self::assertStringContainsString('wa.me/94770000000?text=',$html);self::assertStringContainsString('width:38px',$html);self::assertStringNotContainsString('FAKE_TEST_TOKEN',$html);
        $dev=view('developer.messaging',['developer'=>(object)['name'=>'TEST DEV'],'flags'=>\App\Services\CcsCareFeatures::DEFAULTS])->render();self::assertStringContainsString('direct_lookup',$dev);self::assertStringContainsString('api_enabled',$dev);self::assertStringNotContainsString('FAKE_TEST_TOKEN',$dev);
        file_put_contents('/tmp/ccs-v3-manual-preview.html',$html);file_put_contents('/tmp/ccs-v3-developer-preview.html',$dev);
    }

    public function test_upgrade_defaults_do_not_enable_paid_sending_or_direct_access(): void {
        (require dirname(__DIR__,2).'/database/migrations/2026_10_28_000024_add_care_message_controls.php')->up();
        $f=new \App\Services\CcsCareFeatures;self::assertFalse($f->on('api_enabled'));self::assertFalse($f->on('direct_lookup'));self::assertTrue($f->on('manual_enabled'));self::assertFalse($f->on('auto_invoice'));
    }

    public function test_hosted_test_requires_explicit_boolean_opt_in(): void {$this->app->instance('env','production');config(['ccs_care_sandbox.hosted_test_enabled'=>'true']);self::assertFalse(app(\App\Services\CcsCareEnvironment::class)->allowed());config(['ccs_care_sandbox.hosted_test_enabled'=>true]);self::assertTrue(app(\App\Services\CcsCareEnvironment::class)->allowed());self::assertSame(1,(int)(new CcsCareScope)->customer()->id);}
    public function test_hosted_test_keeps_fixed_meta_sender_and_recipient_guards(): void {$this->app->instance('env','production');config(['ccs_care_sandbox.hosted_test_enabled'=>true]);Http::fake(['*'=>Http::response(['display_phone_number'=>'94110000000'])]);try{(new CcsCareTransport)->otp('123456');self::fail('Real sender accepted');}catch(RuntimeException $e){self::assertStringContainsString('not the CCS Meta sandbox sender',$e->getMessage());}Http::assertSentCount(1);}
    public function test_hosted_test_never_opens_local_public_customer_page(): void {$this->app->instance('env','production');config(['ccs_care_sandbox.hosted_test_enabled'=>true]);$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);(new \App\Http\Middleware\CcsCareLocalOnly)->handle($this->req(),fn()=>response('private records'));}
    public function test_hosted_auto_collection_queues_new_payment_only_for_selected_customer(): void {$this->app->instance('env','production');config(['ccs_care_sandbox.hosted_test_enabled'=>true,'ccs_care_sandbox.notifications_enabled'=>true]);$outbox=new CcsCareOutbox;$outbox->initialize();$this->event('payment_received',1,['payment_lkr'=>1000]);self::assertSame(1,$outbox->collect());self::assertSame(0,$outbox->collect());self::assertSame('payment',DB::table('ccs_care_outbox')->first()->kind);self::assertSame(1,(int)DB::table('ccs_care_outbox')->first()->customer_id);}
    public function test_new_email_channel_suppresses_booking_whatsapp_but_whatsapp_channel_queues_once():void {
        foreach(['2026_10_29_000025_create_website_requests','2026_10_31_000028_add_website_contact_channels','2026_10_29_000027_create_booking_whatsapp_outbox'] as $m)(require dirname(__DIR__,2).'/database/migrations/'.$m.'.php')->up();
        config(['ccs_care_sandbox.notifications_enabled'=>true]);$this->flags(['auto_booking'=>true]);$data=['submission_id'=>(string)\Illuminate\Support\Str::uuid(),'kind'=>'inspection','name'=>'TEST Nimal','phone'=>'0770000000','vehicle'=>'Toyota Prius','contact_consent'=>true,'source_page'=>'contact.html','channel'=>'email','email'=>'test@example.test'];$intake=new \App\Services\CcsWebsiteIntake;$intake->receive($data,'http://localhost','127.0.0.1');self::assertSame(0,DB::table('ccs_booking_whatsapp_outbox')->count());$data['submission_id']=(string)\Illuminate\Support\Str::uuid();$data['channel']='whatsapp';$intake->receive($data,'http://localhost','127.0.0.1');$intake->receive($data,'http://localhost','127.0.0.1');self::assertSame(1,DB::table('ccs_booking_whatsapp_outbox')->count());Http::assertNothingSent();
    }
}
