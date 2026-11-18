<?php
namespace App\Http\Controllers;
use App\Services\{CcsAdministration,CcsCareOutbox,CcsCareScope,CcsMessagingSettings,CcsWhatsAppTest};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,Schema,Validator};
use RuntimeException;
class CcsWhatsAppSettingsController extends Controller {
    private function owner(Request $r): void {abort_unless(app(CcsAdministration::class)->owner($r->user('web')),403);}
    public function index(Request $r,CcsMessagingSettings $settings) {
        $this->owner($r);$ready=Schema::hasTable('ccs_care_state');
        $values=$ready?$settings->publicValues():[];
        $customers=DB::table('customers')->whereNull('deleted_at')->orderBy('name')->get(['id','name','customer_code']);
        $recent=$ready?DB::table('ccs_care_outbox')->orderByDesc('id')->limit(15)->get(['id','kind','state','error','created_at']):collect();
        $otpError=$ready?DB::table('ccs_care_state')->where('key','last_otp_error')->value('value'):null;
        return view('ccs.whatsapp-settings',compact('ready','values','customers','recent','otpError'));
    }
    private function failed(Request $r,array $errors) {return back()->withErrors($errors)->withInput($r->except(['access_token','current_password','_token']));}
    public function save(Request $r,CcsMessagingSettings $settings) {
        $this->owner($r);abort_unless(Schema::hasTable('ccs_care_state'),503,'Run the Customer Care migration first.');
        $v=Validator::make($r->all(),['current_password'=>'required|string|max:200','enabled'=>'nullable|boolean','notifications_enabled'=>'nullable|boolean','clear_token'=>'nullable|boolean','access_token'=>['nullable','string','max:4096','regex:/^\S+$/D'],'phone_number_id'=>['required','regex:/^[0-9]{5,30}$/D'],'recipient'=>['required','string','max:40'],'graph_version'=>['required','regex:/^v[0-9]{1,3}\.0$/D'],'customer_id'=>'required|integer|min:1|exists:customers,id','language'=>['required','regex:/^[a-z]{2}(?:_[A-Z]{2})?$/D'],'otp_template'=>['nullable','regex:/^[a-z0-9_]{1,100}$/D'],'invoice_template'=>['nullable','regex:/^[a-z0-9_]{1,100}$/D'],'payment_template'=>['nullable','regex:/^[a-z0-9_]{1,100}$/D'],'status_template'=>['nullable','regex:/^[a-z0-9_]{1,100}$/D'],'serial_template'=>['nullable','regex:/^[a-z0-9_]{1,100}$/D']]);
        if($v->fails())return $this->failed($r,$v->errors()->toArray());
        if(!Hash::check($r->input('current_password'),$r->user('web')->getAuthPassword()))return $this->failed($r,['current_password'=>'Current owner password is incorrect.']);
        $data=$v->validated();try{$old=$settings->load();}catch(RuntimeException){$old=null;}
        $token=$r->boolean('clear_token')?'':(trim((string)$r->input('access_token'))?:($old['access_token']??(string)config('ccs_whatsapp_test.access_token')));
        $data['access_token']=$token;$data['enabled']=$r->boolean('enabled');$data['notifications_enabled']=$r->boolean('notifications_enabled');$data['customer_id']=(int)$data['customer_id'];
        foreach(['otp_template','invoice_template','payment_template','status_template','serial_template'] as $key)$data[$key]=$data[$key]??'';
        $data['recipient']=preg_replace('/[\s()+-]/','',$data['recipient']);
        if(!preg_match('/^[1-9][0-9]{7,14}$/D',$data['recipient']))return $this->failed($r,['recipient'=>'Use one international number, e.g. 94 followed by the number without the leading zero.']);
        if($data['enabled']&&(!app(\App\Services\CcsCareEnvironment::class)->allowed()||(app(\App\Services\CcsCareFeatures::class)->on('api_enabled')&&$token==='')))return $this->failed($r,['enabled'=>'Enable local mode or CCS_CARE_HOSTED_TEST_ENABLED and configure a valid test token.']);
        $customer=DB::table('customers')->where('id',$data['customer_id'])->whereNull('deleted_at')->first();$saved=trim((string)($customer->whatsapp??''));$saved=$saved!==''?$saved:(string)($customer->phone??'');
        if(!$customer || app(CcsCareScope::class)->phone($saved)!==$data['recipient'])return $this->failed($r,['customer_id'=>'Choose a TEST customer whose saved WhatsApp/phone equals this verified test recipient.']);
        if($data['notifications_enabled']&&(!app(\App\Services\CcsCareFeatures::class)->on('api_enabled')||!$data['enabled']||!$data['invoice_template']||!$data['payment_template']||!$data['status_template']))return $this->failed($r,['notifications_enabled'=>'Enable the test sandbox/API master and enter all three approved utility template names first.']);
        unset($data['current_password'],$data['clear_token']);
        DB::transaction(function()use($data,$settings,$old,$r){$settings->save($data);
            if($data['enabled']&&(!$old||!($old['enabled']??false)||(!($old['notifications_enabled']??false)&&$data['notifications_enabled'])||(int)$old['customer_id']!==$data['customer_id']||$old['recipient']!==$data['recipient']||!DB::table('ccs_care_state')->where('key','baseline')->exists()))app(CcsCareOutbox::class)->initialize();
            app(CcsAdministration::class)->audit($r->user('web')->id,'whatsapp_settings_saved','setting',null,['enabled'=>$data['enabled'],'notifications_enabled'=>$data['notifications_enabled'],'customer_id'=>$data['customer_id'],'token_replaced'=>$r->filled('access_token')||$r->boolean('clear_token')]);
        });
        return back()->with('success','WhatsApp settings saved. Access token stays hidden. This update sends only from the Meta sandbox number to the selected TEST customer.');
    }
    public function sample(Request $r,CcsWhatsAppTest $transport) {
        $this->owner($r);$v=Validator::make($r->all(),['current_password'=>'required|string|max:200']);if($v->fails())return $this->failed($r,$v->errors()->toArray());
        if(!Hash::check($r->input('current_password'),$r->user('web')->getAuthPassword()))return $this->failed($r,['current_password'=>'Current owner password is incorrect.']);
        abort_unless(app(\App\Services\CcsCareEnvironment::class)->allowed(),403);
        try{app(CcsCareScope::class)->customer();$transport->send();return back()->with('success','Meta accepted one hello_world sample. Check WhatsApp to confirm delivery.');}
        catch(RuntimeException $e){return back()->withErrors(['whatsapp'=>$e->getMessage()]);}
    }
}
