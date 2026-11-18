<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use RuntimeException;
class CcsCareOutbox {
    private function baseline(): array { $r=DB::table('ccs_care_state')->where('key','baseline')->first();return $r?json_decode($r->value,true):[]; }
    public function initialize(): void {
        $c=app(CcsCareScope::class)->customer(false);
        DB::transaction(function()use($c){
            // Pending messages from an earlier baseline cannot be replayed accidentally.
            DB::table('ccs_care_outbox')->where('state','pending')->update(['state'=>'cancelled','updated_at'=>now()]);
            DB::table('ccs_care_state')->updateOrInsert(['key'=>'baseline'],['value'=>json_encode(['audit_id'=>(int)DB::table('audit_logs')->max('id'),'customer_id'=>(int)$c->id,'epoch'=>app(CcsCareScope::class)->epoch()]),'created_at'=>now(),'updated_at'=>now()]);
        });
    }
    public function collect(): int {
        $c=app(CcsCareScope::class)->customer();
        return DB::transaction(function()use($c){
            DB::table('ccs_care_state')->where('key','baseline')->lockForUpdate()->first();$s=$this->baseline();
            if(!$s || $s['customer_id']!==(int)$c->id || $s['epoch']!==app(CcsCareScope::class)->epoch())throw new RuntimeException('Initialize this test baseline first; no historical messages will be sent.');
            $events=DB::table('audit_logs')->where('id','>',$s['audit_id'])->orderBy('id')->limit(100)->get();$count=0;
            foreach($events as $e){
                $s['audit_id']=(int)$e->id;$m=json_decode($e->metadata??'{}',true)??[];$kind=null;$params=[];
                if(app(CcsCareFeatures::class)->on('auto_serial'))$count+=$this->serialMessages($e,(int)$c->id);
                if(in_array($e->action,['invoice_issued','payment_received'],true)&&$e->entity_type==='invoice'){
                    $i=DB::table('invoices as i')->join('repair_jobs as j','j.id','=','i.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->where('i.id',$e->entity_id)->where('v.customer_id',$c->id)->whereNull('v.deleted_at')->whereNull('j.deleted_at')->whereIn('i.status',['issued','partially_paid','paid'])->first(['i.id','i.invoice_number','i.total_lkr']);
                    if($i){
                        $paid=(float)DB::table('payments')->where('invoice_id',$i->id)->sum('amount_lkr');
                        if($e->action==='invoice_issued'){$kind='invoice';$params=[$i->invoice_number,number_format((float)$i->total_lkr,2,'.',''),number_format(max(0,(float)$i->total_lkr-$paid),2,'.','')];}
                        elseif(isset($m['payment_lkr'])){$kind='payment';$params=[$i->invoice_number,number_format((float)$m['payment_lkr'],2,'.','')];}
                    }
                }elseif($e->entity_type==='repair_job'&&(
                    ($e->action==='repair_job_status_updated'&&isset($m['to'])&&(($m['from']??null)!==$m['to']||(array_key_exists('previous_technician',$m)&&trim((string)($m['technician']??''))!==''&&($m['previous_technician']??'')!==$m['technician'])))
                    ||($e->action==='repair_job_created'&&trim((string)($m['technician']??''))!==''))){
                    $j=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->where('j.id',$e->entity_id)->where('v.customer_id',$c->id)->whereNull('v.deleted_at')->whereNull('j.deleted_at')->first(['j.job_number']);
                    if($j){$kind='status';$assigned=($e->action==='repair_job_created'||(($m['from']??null)===($m['to']??null)));$text=$assigned?'technician assigned':str_replace('_',' ',(string)$m['to']);$params=[$j->job_number,$text];}
                }
                if($kind&&!app(CcsCareFeatures::class)->on('auto_'.$kind))$kind=null;
                if($kind){
                    try{
                        $payload=app(CcsCareMessage::class)->build($kind==='payment'?'invoice':$kind,(int)$e->entity_id);
                        if($kind==='payment'){$payload['body']=[$payload['body'][0],number_format((float)$m['payment_lkr'],2,'.',''),$payload['body'][2],$payload['body'][3]];unset($payload['snapshot']);}
                        if($kind==='status'){$payload['body'][2]=$params[1];}
                        $count+=DB::table('ccs_care_outbox')->insertOrIgnore(['customer_id'=>$c->id,'audit_id'=>$e->id,'kind'=>$kind,'parameters'=>json_encode($payload),'state'=>'pending','created_at'=>now(),'updated_at'=>now()]);
                    }catch(RuntimeException){/* unrelated, draft or unapproved record: do not send */}
                }
            }
            DB::table('ccs_care_state')->where('key','baseline')->update(['value'=>json_encode($s),'updated_at'=>now()]);return $count;
        });
    }
    public function dispatch(): int {
        if(!app(CcsCareFeatures::class)->on('api_enabled'))return 0;
        $c=app(CcsCareScope::class)->customer();$s=$this->baseline();
        if(!$s||$s['customer_id']!==(int)$c->id||$s['epoch']!==app(CcsCareScope::class)->epoch())throw new RuntimeException('Baseline changed; sending stopped.');
        if(config('ccs_care_sandbox.notifications_enabled')!==true)return 0;
        $sent=0;
        foreach(DB::table('ccs_care_outbox')->where('customer_id',$c->id)->where('state','pending')->orderBy('id')->limit(10)->get() as $row){
            if(!app(CcsCareFeatures::class)->on('auto_'.$row->kind)){DB::table('ccs_care_outbox')->where('id',$row->id)->where('state','pending')->update(['state'=>'cancelled','updated_at'=>now()]);continue;}
            // Missing templates stay pending; they are never replaced with hello_world or free-form messages.
            if(!config('ccs_care_sandbox.'.$row->kind.'_template'))continue;
            if(!DB::table('ccs_care_outbox')->where('id',$row->id)->where('state','pending')->update(['state'=>'processing','updated_at'=>now()]))continue;
            try{$id=app(CcsCareTransport::class)->update($row->kind,json_decode($row->parameters,true));DB::table('ccs_care_outbox')->where('id',$row->id)->update(['state'=>'accepted','message_id'=>$id,'error'=>null,'updated_at'=>now()]);$sent++;}
            catch(RuntimeException $e){DB::table('ccs_care_outbox')->where('id',$row->id)->update(['state'=>str_starts_with($e->getMessage(),'UNKNOWN')?'unknown':'failed','error'=>$e->getMessage(),'updated_at'=>now()]);}
        }
        return $sent;
    }
    private function serialMessages(object $event,int $customerId): int {
        if(!\Illuminate\Support\Facades\Schema::hasTable('warranties'))return 0;
        if($event->action==='invoice_issued'&&$event->entity_type==='invoice'){
            $ids=DB::table('warranties as w')->join('invoice_items as item','item.id','=','w.invoice_item_id')->where('item.invoice_id',$event->entity_id)->pluck('w.id');
        }elseif($event->action==='warranty_record_approved'&&$event->entity_type==='warranty'){$ids=collect([(int)$event->entity_id]);}
        elseif($event->action==='warranty_issued'&&$event->entity_type==='repair_job'){
            $meta=json_decode($event->metadata??'{}',true)??[];
            $ids=isset($meta['warranty_id'])?DB::table('warranties')->where('repair_job_id',$event->entity_id)->where('id',(int)$meta['warranty_id'])->pluck('id'):collect();
        }
        else return 0;
        $count=0;
        foreach($ids as $id){
            if(DB::table('ccs_care_auto_serial_keys')->where('warranty_id',$id)->exists())continue;
            try{$payload=app(CcsCareMessage::class)->build('serial',(int)$id);}catch(RuntimeException){continue;}
            if($payload['customer_id']!==$customerId)continue;
            $outboxId=DB::table('ccs_care_outbox')->insertGetId(['customer_id'=>$customerId,'audit_id'=>null,'kind'=>'serial','parameters'=>json_encode($payload),'state'=>'pending','created_at'=>now(),'updated_at'=>now()]);
            DB::table('ccs_care_auto_serial_keys')->insert(['warranty_id'=>$id,'audit_id'=>$event->id,'outbox_id'=>$outboxId,'created_at'=>now(),'updated_at'=>now()]);$count++;
        }
        return $count;
    }
}
