<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema};
use RuntimeException;
class CcsCareMessage {
    private function amount($v): string {return number_format((float)$v,2,'.','');}
    public function build(string $kind,int $id): array {
        $c=app(CcsCareScope::class)->customer(false);
        if(in_array($kind,['invoice','payment'],true)){
            $payment=null;
            if($kind==='payment'){$payment=DB::table('payments')->where('id',$id)->first();if(!$payment)throw new RuntimeException('Payment not found.');$id=(int)$payment->invoice_id;}
            $i=DB::table('invoices as i')->join('repair_jobs as j','j.id','=','i.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->where('i.id',$id)->where('v.customer_id',$c->id)->whereNull('v.deleted_at')->whereNull('j.deleted_at')->whereIn('i.status',['issued','partially_paid','paid'])->first(['i.*','j.job_number','v.registration_number']);
            if(!$i)throw new RuntimeException('Choose a finalized invoice owned by the configured TEST customer.');
            $payments=DB::table('payments')->where('invoice_id',$id)->orderBy('id')->get()->map(fn($p)=>(array)$p)->all();
            $paid=round(array_sum(array_column($payments,'amount_lkr')),2);$due=max(0,round((float)$i->total_lkr-$paid,2));
            $body=$kind==='invoice'?[$i->invoice_number,$this->amount($i->total_lkr),$this->amount($paid),$this->amount($due)]:[$i->invoice_number,$this->amount($payment->amount_lkr),$this->amount($paid),$this->amount($due)];
            $text="CCS Solutions\nInvoice: {$i->invoice_number}\nVehicle: {$i->registration_number}\n".($payment?'Payment received: LKR '.$this->amount($payment->amount_lkr)."\n":'')."Total: LKR ".$this->amount($i->total_lkr)."\nPaid: LKR ".$this->amount($paid)."\nOutstanding due: LKR ".$this->amount($due);
            $snapshot=['invoice'=>(array)$i,'customer'=>['name'=>$c->name,'code'=>$c->customer_code],'paid'=>$paid,'due'=>$due,'payments'=>$payments,'items'=>DB::table('invoice_items')->where('invoice_id',$id)->orderBy('id')->get()->map(fn($p)=>(array)$p)->all()];
            return ['v'=>3,'body'=>$body,'text'=>$text,'snapshot'=>$snapshot,'customer_id'=>(int)$c->id];
        }
        if($kind==='status'){
            $j=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->where('j.id',$id)->where('v.customer_id',$c->id)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->first(['j.job_number','j.status','v.registration_number']);
            if(!$j)throw new RuntimeException('Job is not owned by the configured TEST customer.');
            $body=[$j->registration_number,$j->job_number,str_replace('_',' ',$j->status)];
            return ['v'=>3,'body'=>$body,'customer_id'=>(int)$c->id,'text'=>"CCS Solutions repair update\nVehicle: {$body[0]}\nJob: {$body[1]}\nStatus: {$body[2]}"];
        }
        if($kind==='serial'){
            $w=DB::table('warranties as w')->join('repair_jobs as j','j.id','=','w.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->where('w.id',$id)->where('v.customer_id',$c->id)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->first(['w.id','v.id as vehicle_id','v.registration_number']);
            if(!$w)throw new RuntimeException('Warranty is not owned by the configured TEST customer.');
            $card=collect(app(CcsSerialLookupService::class)->warranties((int)$c->id,(int)$w->vehicle_id))->firstWhere('id',$w->id);
            if(!$card||($card['coverage_kind']??'')==='no_warranty')throw new RuntimeException('Choose an approved, serial-linked warranty card with coverage.');
            $body=[$w->registration_number,(string)$card['covered_description'],(string)($card['covered_serial']?:'Not recorded / service warranty'),(string)$card['card_status']['card_number'],(string)($card['starts_on']??'See agreed conditions'),(string)($card['expires_on']??'See mileage / conditions')];
            return ['v'=>3,'body'=>$body,'customer_id'=>(int)$c->id,'text'=>"CCS Solutions warranty\nVehicle: {$body[0]}\nPart: {$body[1]}\nSerial: {$body[2]}\nCard: {$body[3]}\nStart: {$body[4]}\nEnds: {$body[5]}\nCoverage follows the saved warranty conditions."];
        }
        throw new RuntimeException('Unsupported message type.');
    }
    public function recipient(): string {
        $c=app(CcsCareScope::class)->customer(false);$saved=trim((string)($c->whatsapp??''));$phone=app(CcsCareScope::class)->phone($saved!==''?$saved:(string)($c->phone??''));
        if(!preg_match('/^[1-9][0-9]{7,14}$/D',$phone))throw new RuntimeException('Save a valid TEST customer WhatsApp number first.');return $phone;
    }
}
