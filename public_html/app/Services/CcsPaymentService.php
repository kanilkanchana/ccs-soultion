<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CcsPaymentService
{
    // Caller must hold an invoice row lock inside a transaction.
    public function record(object $invoice, float $tendered, string $method, ?string $reference, string $token, int $userId): array
    {
        $prior=DB::table('payments')->where('request_token',$token)->first();
        if($prior){
            if((int)$prior->invoice_id!==(int)$invoice->id)throw ValidationException::withMessages(['amount_lkr'=>'This payment token belongs to another invoice. Refresh and try again.']);
            return ['duplicate'=>true,'change_cents'=>0];
        }
        if(!in_array($invoice->status,['issued','partially_paid','paid'],true))throw ValidationException::withMessages(['amount_lkr'=>'Finalize this invoice before receiving a payment.']);
        $total=(int)round((float)$invoice->total_lkr*100);
        $already=DB::table('payments')->where('invoice_id',$invoice->id)->pluck('amount_lkr')->reduce(fn(int $sum,$value)=>$sum+(int)round((float)$value*100),0);
        $given=(int)round($tendered*100);$remaining=max(0,$total-$already);
        if($given<=0||$remaining<=0)throw ValidationException::withMessages(['amount_lkr'=>'Enter a positive payment for an invoice with a remaining balance.']);
        if($method!=='cash'&&$given>$remaining)throw ValidationException::withMessages(['amount_lkr'=>'This payment exceeds the remaining balance.']);
        $amount=min($given,$remaining);$newPaid=$already+$amount;
        DB::table('payments')->insert([
            'invoice_id'=>$invoice->id,'amount_lkr'=>number_format($amount/100,2,'.',''),
            'method'=>$method,'reference'=>$reference,'request_token'=>$token,'received_by_user_id'=>$userId,
            'paid_at'=>now(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('invoices')->where('id',$invoice->id)->update([
            'status'=>$newPaid>=$total?'paid':'partially_paid',
            'is_credit_sale'=>(bool)($invoice->is_credit_sale??true)||$newPaid<$total,'updated_at'=>now(),
        ]);
        DB::table('audit_logs')->insert([
            'user_id'=>$userId,'action'=>'payment_received','entity_type'=>'invoice','entity_id'=>$invoice->id,
            'metadata'=>json_encode(['payment_lkr'=>$amount/100,'tendered_lkr'=>$given/100,'change_lkr'=>($given-$amount)/100,'method'=>$method]),
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        return ['duplicate'=>false,'change_cents'=>$given-$amount];
    }
}
