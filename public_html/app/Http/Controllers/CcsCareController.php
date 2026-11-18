<?php
namespace App\Http\Controllers;
use App\Services\CcsCareScope;
use App\Services\CcsCareTransport;
use App\Services\CcsSerialLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
class CcsCareController extends Controller {
    public function page() { return view('customer-care.page',['careBoot'=>['demo'=>false,'direct'=>app(\App\Services\CcsCareFeatures::class)->on('direct_lookup'),'base'=>url('/customer-care'),'homeUrl'=>config('ccs_care_sandbox.home_url')]]); }
    public function requestOtp(Request $r,CcsCareScope $scope,CcsCareTransport $transport) {
        $data=$r->validate(['plate'=>['required','string','max:30']]);
        abort_if(app(\App\Services\CcsCareFeatures::class)->on('direct_lookup'),409,'Direct lookup mode is enabled. Refresh the page.');
        abort_unless((string)config('ccs_care_sandbox.otp_template')!=='',503,'OTP template is not configured yet.');
        $customer=$scope->customer();
        $key='ccs-care-otp:'.hash('sha256',(string)$customer->id);
        if(!RateLimiter::attempt($key,1,static fn()=>true,60)) throw ValidationException::withMessages(['plate'=>'Please wait 60 seconds before requesting another code.']);
        $hourKey=$key.':hour';
        if(!RateLimiter::attempt($hourKey,5,static fn()=>true,3600)) throw ValidationException::withMessages(['plate'=>'Too many requests. Please try again later.']);
        $plate=$scope->plate($data['plate']);
        // Find globally first: an ambiguous plate must never pick a random owner.
        $matches=DB::table('vehicles')->whereNull('deleted_at')->whereRaw("UPPER(REPLACE(REPLACE(registration_number,' ',''),'-','')) = ?",[$plate])->limit(2)->get();
        $matchesCustomer=$matches->count()===1 && (int)$matches[0]->customer_id===(int)$customer->id;
        $nonce=(string)$r->session()->get('ccs_care_nonce',Str::random(48));
        $r->session()->put('ccs_care_nonce',$nonce); $r->session()->forget('ccs_care_grant');
        $binding=hash('sha256',$nonce);$id=(string)Str::uuid();$code=(string)random_int(100000,999999);
        DB::transaction(function()use($binding,$id,$code,$matchesCustomer,$customer,$scope){
            DB::table('ccs_care_challenges')->where('session_binding',$binding)->whereIn('state',['pending','sent'])->update(['state'=>'replaced','updated_at'=>now()]);
            DB::table('ccs_care_challenges')->insert(['id'=>$id,'session_binding'=>$binding,'customer_id'=>$matchesCustomer?$customer->id:null,'otp_hash'=>Hash::make($code),'epoch'=>$scope->epoch(),'attempts'=>0,'state'=>$matchesCustomer?'pending':'dummy','expires_at'=>now()->addMinutes(5),'created_at'=>now(),'updated_at'=>now()]);
        });
        if($matchesCustomer){
            try { $transport->otp($code);DB::table('ccs_care_challenges')->where('id',$id)->where('state','pending')->update(['state'=>'sent','updated_at'=>now()]); }
            catch(RuntimeException $error) { DB::table('ccs_care_state')->updateOrInsert(['key'=>'last_otp_error'],['value'=>mb_substr($error->getMessage(),0,500),'created_at'=>now(),'updated_at'=>now()]); DB::table('ccs_care_challenges')->where('id',$id)->update(['state'=>'failed','otp_hash'=>null,'updated_at'=>now()]); }
        }
        unset($code);
        // Deliberately identical for missing, ambiguous, unrelated and matching plates.
        return response()->json(['challenge'=>$id,'expires_in'=>300,'resend_after'=>60,'message'=>'If this vehicle matches the configured test customer, a code is sent to their saved WhatsApp number.']);
    }
    public function verify(Request $r,CcsCareScope $scope) {
        abort_if(app(\App\Services\CcsCareFeatures::class)->on('direct_lookup'),409,'Direct lookup mode is enabled. Refresh the page.');
        $data=$r->validate(['challenge'=>'required|uuid','code'=>['required','regex:/^[0-9]{6}$/D']]);
        $customer=$scope->customer();$binding=hash('sha256',(string)$r->session()->get('ccs_care_nonce','missing'));
        $valid=DB::transaction(function()use($data,$binding,$scope,$customer){
            $c=DB::table('ccs_care_challenges')->where('id',$data['challenge'])->lockForUpdate()->first();
            if(!$c||!hash_equals($c->session_binding,$binding)||$c->state!=='sent'||$c->attempts>=5||$c->epoch!==$scope->epoch()||\Illuminate\Support\Carbon::parse($c->expires_at)->isPast()||(int)$c->customer_id!==(int)$customer->id)return false;
            $ok=Hash::check($data['code'],$c->otp_hash);
            DB::table('ccs_care_challenges')->where('id',$c->id)->update(['attempts'=>$c->attempts+1,'state'=>$ok?'used':($c->attempts+1>=5?'locked':'sent'),'otp_hash'=>$ok||$c->attempts+1>=5?null:$c->otp_hash,'updated_at'=>now()]);
            return $ok;
        });
        if(!$valid) throw ValidationException::withMessages(['code'=>'Invalid or expired code. Request a new code if necessary.']);
        $r->session()->regenerate();$r->session()->regenerateToken();
        $r->session()->put('ccs_care_grant',['customer_id'=>(int)$customer->id,'until'=>time()+900,'epoch'=>$scope->epoch(),'mode'=>'otp','features'=>app(\App\Services\CcsCareFeatures::class)->fingerprint()]);
        return response()->json(['verified'=>true,'csrf_token'=>csrf_token()]);
    }
    public function direct(Request $r,CcsCareScope $scope) {
        $features=app(\App\Services\CcsCareFeatures::class);
        abort_unless($features->on('direct_lookup'),403,'Direct access is disabled.');
        $data=$r->validate(['plate'=>'required|string|max:30']);$customer=$scope->customer(false);
        $r->session()->forget('ccs_care_grant');
        $matches=DB::table('vehicles')->whereNull('deleted_at')->whereRaw("UPPER(REPLACE(REPLACE(registration_number,' ',''),'-','')) = ?",[$scope->plate($data['plate'])])->limit(2)->get();
        if($matches->count()!==1||(int)$matches[0]->customer_id!==(int)$customer->id)throw ValidationException::withMessages(['plate'=>'No unique configured TEST vehicle found.']);
        $r->session()->regenerate();$r->session()->regenerateToken();
        $r->session()->put('ccs_care_grant',['customer_id'=>(int)$customer->id,'until'=>time()+900,'epoch'=>$scope->epoch(),'mode'=>'direct','features'=>$features->fingerprint()]);
        return response()->json(['opened'=>true,'csrf_token'=>csrf_token()]);
    }
    public function records(Request $r,CcsCareScope $scope) {
        $features=app(\App\Services\CcsCareFeatures::class);$customer=$scope->customer(false);$g=$r->session()->get('ccs_care_grant',[]);
        abort_unless(($g['mode']??'otp')===($features->on('direct_lookup')?'direct':'otp')&&($g['features']??'')===$features->fingerprint(),401,'Access settings changed. Search again.');
        abort_unless(($g['customer_id']??0)===(int)$customer->id&&($g['until']??0)>time()&&($g['epoch']??'')===$scope->epoch(),401,'Please verify your WhatsApp code again.');
        $vehicles=DB::table('vehicles')->where('customer_id',$customer->id)->whereNull('deleted_at')->orderBy('registration_number')->limit(100)->get(['id','registration_number','make','model','manufacture_year']);
        $result=[];
        foreach($vehicles as $v){
            $jobs=DB::table('repair_jobs')->where('vehicle_id',$v->id)->whereNull('deleted_at')->orderByDesc('id')->limit(20)->get(['id','job_number','status','received_at','completed_at']);
            $invoices=DB::table('invoices as i')->join('repair_jobs as j','j.id','=','i.repair_job_id')->where('j.vehicle_id',$v->id)->whereNull('j.deleted_at')->whereIn('i.status',['issued','partially_paid','paid'])->select('i.id','i.invoice_number','i.total_lkr','i.status','i.issued_at')->selectSub(fn($q)=>$q->from('payments as p')->selectRaw('COALESCE(SUM(p.amount_lkr),0)')->whereColumn('p.invoice_id','i.id'),'paid_lkr')->orderByDesc('i.id')->limit(20)->get();
            foreach($invoices as $i){$i->due_lkr=max(0,round((float)$i->total_lkr-(float)$i->paid_lkr,2));}
            $serials=DB::table('invoice_items as item')->join('invoices as i','i.id','=','item.invoice_id')->join('repair_jobs as j','j.id','=','i.repair_job_id')->where('j.vehicle_id',$v->id)->whereNull('j.deleted_at')->whereIn('i.status',['issued','partially_paid','paid'])->whereNotNull('item.serial_number')->where('item.serial_number','!=','')->orderByDesc('item.id')->limit(100)->get(['item.description','item.serial_number','j.job_number']);
            $moduleSerials=DB::table('battery_module_readings as m')->join('battery_service_records as s','s.id','=','m.battery_service_record_id')->join('repair_jobs as j','j.id','=','s.repair_job_id')->where('s.vehicle_id',$v->id)->where('j.vehicle_id',$v->id)->whereNull('j.deleted_at')->orderByDesc('m.id')->limit(100)->get(['m.serial_number','m.voltage','j.job_number']);
            $serials=$serials->map(fn($s)=>(array)$s)->all();
            foreach($moduleSerials as $m){if(!collect($serials)->contains(fn($s)=>$s['serial_number']===$m->serial_number&&$s['job_number']===$m->job_number))$serials[]=['description'=>'Battery module','serial_number'=>$m->serial_number,'job_number'=>$m->job_number,'reading'=>$m->voltage===null?null:$m->voltage.' V'];}
            $warranties=app(CcsSerialLookupService::class)->warranties((int)$customer->id,(int)$v->id);
            foreach($warranties as &$w){$w['claims']=DB::table('warranty_claims')->where('warranty_id',$w['id'])->orderByDesc('id')->limit(10)->get(['id','status','received_on']);}unset($w);
            $result[]=['vehicle'=>$v,'jobs'=>$jobs,'invoices'=>$invoices,'serials'=>$serials,'warranties'=>$warranties];
        }
        $messages=DB::table('ccs_care_outbox')->where('customer_id',$customer->id)->orderByDesc('id')->limit(10)->get(['kind','state','created_at']);
        return response()->json(['customer'=>['name'=>$customer->name,'code'=>$customer->customer_code],'vehicles'=>$result,'messages'=>$messages,'limits'=>['vehicles'=>100,'jobs_per_vehicle'=>20,'invoices_per_vehicle'=>20,'serials_per_vehicle'=>100],'expires_at'=>$g['until'],'csrf_token'=>csrf_token()]);
    }
    public function logout(Request $r) { $r->session()->forget(['ccs_care_grant','ccs_care_nonce']);$r->session()->migrate(true);$r->session()->regenerateToken();return response()->json(['csrf_token'=>csrf_token()]); }
}
