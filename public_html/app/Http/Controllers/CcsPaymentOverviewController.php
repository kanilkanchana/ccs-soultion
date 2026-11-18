<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CcsPaymentOverviewController extends Controller
{
 public function index(Request $request){
  $data=$request->validate(['q'=>['nullable','string','max:150'],'status'=>['nullable','in:draft,issued,partially_paid,paid'],'page'=>['nullable','integer','min:1','max:100000']]);
  $q=trim((string)($data['q']??''));$status=$data['status']??'';
  $invoices=DB::table('invoices as i')->join('repair_jobs as j','j.id','=','i.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')
   ->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->where('i.status','!=','void')
   ->when($status!=='',fn($query)=>$query->where('i.status',$status))
   ->when($q!=='',function($query)use($q){$query->where(function($x)use($q){foreach(['i.invoice_number','j.job_number','c.name','c.customer_code','c.phone','v.registration_number'] as $n=>$field){if($n===0)$x->where($field,'like','%'.$q.'%');else $x->orWhere($field,'like','%'.$q.'%');}});})
   ->select('i.id','i.invoice_number','i.repair_job_id','i.status','i.total_lkr','i.created_at','i.issued_at','j.job_number','c.name as customer_name','c.customer_code','v.registration_number')
   ->orderByDesc('i.id')->paginate(20)->withQueryString();
  $receipts=DB::table('payments')->whereIn('invoice_id',$invoices->pluck('id'))->get(['invoice_id','amount_lkr'])->groupBy('invoice_id');
  foreach($invoices as $invoice){$cents=$receipts->get($invoice->id,collect())->sum(fn($p)=>(int)round((float)$p->amount_lkr*100));$invoice->paid_lkr=$cents/100;$invoice->due_lkr=max(0,(int)round((float)$invoice->total_lkr*100)-$cents)/100;$invoice->display_status=$invoice->status==='draft'?'draft':($invoice->due_lkr<=0?'paid':($cents>0?'partially_paid':'issued'));}
  return response()->view('ccs.payment-finalization',compact('invoices','q','status'))->header('Cache-Control','no-store, private');
 }
}
