<?php
namespace App\Http\Controllers;
use App\Services\CcsPaymentService;
use App\Services\CcsStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CcsBillingController extends Controller
{
    public function issue(Request $request,int $id){
        $data=$request->validate(['reference'=>['nullable','string','max:100'],'confirmation'=>['required','accepted'],'amount_received'=>['nullable','numeric','min:0','decimal:0,2','max:9999999999.99'],'method'=>['required','in:cash,bank_transfer,card,other'],'request_token'=>['required','uuid']]);
        $result=DB::transaction(function()use($id,$request,$data){
            $invoice=DB::table('invoices')->where('id',$id)->lockForUpdate()->first();abort_unless($invoice,404);
            if($invoice->status!=='draft'){
                if(DB::table('payments')->where('invoice_id',$id)->where('request_token',$data['request_token'])->exists())return ['duplicate'=>true,'change_cents'=>0];
                throw ValidationException::withMessages(['confirmation'=>'Invoice already finalized. Use Record Payment for another payment.']);
            }
            app(\App\Services\CcsJobBillingGate::class)->check((int)$invoice->repair_job_id);
            app(\App\Services\CcsPartRequirements::class)->check((int)$invoice->repair_job_id,DB::table('invoice_items')->where('invoice_id',$id)->get());
            $total=(int)round((float)$invoice->total_lkr*100);if($total<0)throw ValidationException::withMessages(['confirmation'=>'Invalid invoice total.']);
            DB::table('invoices')->where('id',$id)->update(['status'=>$total===0?'paid':'issued','issued_at'=>now(),'is_credit_sale'=>(bool)$invoice->is_credit_sale||(int)round((float)($data['amount_received']??0)*100)<$total,'updated_at'=>now()]);
            app(CcsStockService::class)->finalize($id);
            app(\App\Services\CcsStockWarrantyService::class)->issueInvoice($id,$request->user()->id);
            DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'invoice_issued','entity_type'=>'invoice','entity_id'=>$id,'metadata'=>json_encode(['invoice_number'=>$invoice->invoice_number]),'created_at'=>now(),'updated_at'=>now()]);
            if((float)($data['amount_received']??0)>0)return app(CcsPaymentService::class)->record(DB::table('invoices')->where('id',$id)->first(),(float)$data['amount_received'],$data['method'],$data['reference']??null,$data['request_token'],$request->user()->id);
            return ['duplicate'=>false,'change_cents'=>0];
        },3);
        $request->session()->forget('ccs_pos_payment.'.$id);
        return redirect()->route('ccs.invoices.show',$id)->with('success',$result['duplicate']?'Payment already recorded.':'Invoice finalized. Payments and remaining balance are shown below.')->with('payment_change_lkr',$result['change_cents']/100);
    }
    public function completeSerials(Request $request,int $id){
        $data=$request->validate(['serials'=>['required','array','max:20'],'serials.*'=>['nullable','string','max:100']]);
        DB::transaction(function()use($request,$id,$data){
            $invoice=DB::table('invoices')->where('id',$id)->lockForUpdate()->first();abort_unless($invoice,404);abort_unless($invoice->status==='draft',409,'Only draft serials can be completed.');
            $lines=DB::table('invoice_items')->where('invoice_id',$id)->get();$seen=[];
            foreach($lines as $line){
                $serial=trim((string)$line->serial_number);
                if(!$line->stock_managed && ($line->requires_serial||$line->is_battery_module)){
                    $serial=trim((string)($data['serials'][$line->id]??$serial));
                    if($serial==='')throw ValidationException::withMessages(['serials'=>'Enter the actual serial for '.$line->description.'.']);
                    if((float)$line->quantity!==1.0)throw ValidationException::withMessages(['serials'=>'Cancel and recreate the draft with one row per serialized unit.']);
                    DB::table('invoice_items')->where('id',$line->id)->update(['serial_number'=>$serial,'updated_at'=>now()]);
                }
                if($serial!==''){$key=mb_strtoupper($serial);if(isset($seen[$key]))throw ValidationException::withMessages(['serials'=>'A serial cannot appear twice in an invoice.']);$seen[$key]=true;}
            }
            DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'draft_serials_completed','entity_type'=>'invoice','entity_id'=>$id,'metadata'=>null,'created_at'=>now(),'updated_at'=>now()]);
        });return back()->with('success','Draft serials saved. Complete module voltage in the Battery Job Card before finalization.');
    }
    public function payment(Request $request,int $id){
        $data=$request->validate(['amount_lkr'=>['required','numeric','gt:0','decimal:0,2','max:9999999999.99'],'method'=>['required','in:cash,bank_transfer,card,other'],'reference'=>['nullable','string','max:100'],'request_token'=>['required','uuid']]);
        $result=DB::transaction(function()use($id,$request,$data){$invoice=DB::table('invoices')->where('id',$id)->lockForUpdate()->first();abort_unless($invoice,404);return app(CcsPaymentService::class)->record($invoice,(float)$data['amount_lkr'],$data['method'],$data['reference']??null,$data['request_token'],$request->user()->id);},3);
        return redirect()->route('ccs.invoices.show',$id)->with('success',$result['duplicate']?'Payment already recorded.':'Payment recorded.')->with('payment_change_lkr',$result['change_cents']/100);
    }
}
