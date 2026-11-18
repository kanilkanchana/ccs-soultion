<?php
namespace App\Http\Controllers;
use App\Services\{CcsAdministration,CcsCareFeatures,CcsCareMessage,CcsCarePdf,CcsCareTransport};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
use RuntimeException;
class CcsCareMessagesController extends Controller {
    private function access(Request $r): void {
        abort_unless(in_array(app(CcsAdministration::class)->role($r->user('web')),['owner','manager','cashier','legacy'],true),403);
        abort_unless(app(CcsCareFeatures::class)->on('manual_enabled'),403,'Manual WhatsApp actions are disabled by the developer.');
        abort_unless(app(\App\Services\CcsCareEnvironment::class)->allowed(),403,'Enable the explicitly configured hosted TEST mode or use local development.');
    }
    public function index(Request $r,CcsCareMessage $builder){
        $this->access($r);$data=$r->validate(['kind'=>'nullable|in:invoice,payment,status,serial','id'=>'nullable|integer|min:1']);$kind=$data['kind']??'invoice';$id=(int)($data['id']??0);$message=null;$error=null;$phone=null;
        if($id){try{$message=$builder->build($kind,$id);$phone=$builder->recipient();}catch(RuntimeException $e){$error=$e->getMessage();}}
        $requestKey=(string)Str::uuid();$api=app(CcsCareFeatures::class)->on('api_enabled');
        $recent=DB::table('ccs_care_outbox')->orderByDesc('id')->limit(20)->get(['id','kind','state','error','created_at']);
        return view('ccs.care-messages',compact('kind','id','message','error','phone','requestKey','api','recent'));
    }
    public function pdf(Request $r,int $id,CcsCareMessage $builder,CcsCarePdf $pdf){
        $this->access($r);try{$payload=$builder->build('invoice',$id);$bytes=$pdf->bytes($payload['snapshot']);}catch(RuntimeException $e){abort(422,$e->getMessage());}
        return response($bytes,200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="CCS-invoice-'.$id.'.pdf"','Cache-Control'=>'no-store, private']);
    }
    public function send(Request $r,CcsCareMessage $builder,CcsCareTransport $transport){
        $this->access($r);$data=$r->validate(['kind'=>'required|in:invoice,payment,status,serial','id'=>'required|integer|min:1','request_key'=>'required|uuid','confirm'=>'accepted']);
        abort_unless(Schema::hasTable('ccs_care_manual_requests'),503,'Run the v3 migration first.');
        try{
            app(CcsCareFeatures::class)->api();$payload=$builder->build($data['kind'],(int)$data['id']);app(\App\Services\CcsCareScope::class)->customer();
            // A database unique key protects double clicks / repeated POSTs across requests.
            $rowId=DB::transaction(function()use($r,$data,$payload){
                $inserted=DB::table('ccs_care_manual_requests')->insertOrIgnore(['id'=>$data['request_key'],'user_id'=>$r->user('web')->id,'kind'=>$data['kind'],'entity_id'=>$data['id'],'created_at'=>now(),'updated_at'=>now()]);
                if(!$inserted)return null;
                $payload['manual']=true;$id=DB::table('ccs_care_outbox')->insertGetId(['customer_id'=>$payload['customer_id'],'audit_id'=>null,'kind'=>$data['kind'],'parameters'=>json_encode($payload),'state'=>'processing','created_at'=>now(),'updated_at'=>now()]);
                DB::table('ccs_care_manual_requests')->where('id',$data['request_key'])->update(['outbox_id'=>$id]);
                app(CcsAdministration::class)->audit($r->user('web')->id,'whatsapp_manual_attempt','message',$id,['kind'=>$data['kind'],'entity_id'=>$data['id']]);return $id;
            });
            if(!$rowId)return back()->with('success','This send request was already processed. Check message history; no duplicate was sent.');
            $payload['manual']=true;
            try{$wamid=$transport->update($data['kind'],$payload);DB::table('ccs_care_outbox')->where('id',$rowId)->update(['state'=>'accepted','message_id'=>$wamid,'updated_at'=>now()]);return back()->with('success','Meta accepted the message. Confirm delivery on WhatsApp; acceptance is not a delivery receipt.');}
            catch(\Throwable $e){$safe=get_class($e)===RuntimeException::class?$e->getMessage():'Message preparation failed. No automatic retry; check history before sending again.';$safe=mb_substr($safe,0,500);DB::table('ccs_care_outbox')->where('id',$rowId)->update(['state'=>str_starts_with($safe,'UNKNOWN')?'unknown':'failed','error'=>$safe,'updated_at'=>now()]);return back()->withErrors(['whatsapp'=>$safe]);}
        }catch(RuntimeException $e){return back()->withErrors(['whatsapp'=>get_class($e)===RuntimeException::class?$e->getMessage():'Message could not be prepared. Check the local migration and message history.']);}
    }
}
