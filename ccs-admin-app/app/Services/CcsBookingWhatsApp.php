<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema};use RuntimeException;
class CcsBookingWhatsApp {
 private function ready():bool{return Schema::hasTable('ccs_booking_whatsapp_outbox');}
 private function enabled():bool{return app(CcsCareEnvironment::class)->allowed()&&config('ccs_care_sandbox.enabled')===true&&config('ccs_care_sandbox.notifications_enabled')===true&&app(CcsCareFeatures::class)->on('api_enabled')&&app(CcsCareFeatures::class)->on('auto_booking');}
 private function recipient(object $r):string{return app(CcsCareScope::class)->phone(trim((string)$r->whatsapp)!==''?(string)$r->whatsapp:(string)$r->phone);}
 private function clean(string $s):string{return mb_substr(trim(preg_replace('/\s+/u',' ',$s)),0,150);}
 public function enqueue(object $r,string $kind):int {
  if(!$this->ready()||!$this->enabled()||($r->channel??null)==='email'||$r->kind!=='inspection'||!$r->contact_consent||!in_array($kind,['booking_received','booking_confirmed'],true))return 0;
  try{$customer=app(CcsCareScope::class)->customer();}catch(RuntimeException){return 0;}
  $to=$this->recipient($r);if(!hash_equals(preg_replace('/\D/','',(string)config('ccs_whatsapp_test.recipient')),$to))return 0;
  $body=[$r->public_reference,$this->clean($r->vehicle?:'Vehicle details supplied')];
  if($kind==='booking_confirmed'){if($r->status!=='confirmed'||!$r->confirmed_date||!$r->confirmed_time)return 0;$body[]=$r->confirmed_date;$body[]=substr($r->confirmed_time,0,5);}
  return DB::table('ccs_booking_whatsapp_outbox')->insertOrIgnore(['event_key'=>hash('sha256',$kind.'|'.$r->id.'|'.$to.($kind==='booking_confirmed'?'|'.$r->confirmed_date.'|'.$r->confirmed_time:'')),'request_id'=>$r->id,'customer_id'=>$customer->id,'kind'=>$kind,'recipient'=>$to,'epoch'=>app(CcsCareScope::class)->epoch(),'payload'=>json_encode(['v'=>3,'body'=>$body,'customer_id'=>(int)$customer->id]),'state'=>'pending','created_at'=>now(),'updated_at'=>now()]);
 }
 public function dispatch():int {
  if(!$this->ready()||!$this->enabled())return 0;$customer=app(CcsCareScope::class)->customer();$n=0;
  foreach(DB::table('ccs_booking_whatsapp_outbox')->where('state','pending')->orderBy('id')->limit(10)->get() as $item){
   $r=DB::table('ccs_website_requests')->where('id',$item->request_id)->first();$payload=json_decode($item->payload,true);
   if(\Illuminate\Support\Carbon::parse($item->created_at,'UTC')->lt(now('UTC')->subDay())||!$r||($r->channel??null)==='email'||!$r->contact_consent||$r->kind!=='inspection'||$r->status==='cancelled'||(int)$item->customer_id!==(int)$customer->id||$item->epoch!==app(CcsCareScope::class)->epoch()||!hash_equals($item->recipient,$this->recipient($r))||!hash_equals($item->recipient,preg_replace('/\D/','',(string)config('ccs_whatsapp_test.recipient')))||($item->kind==='booking_confirmed'&&(!in_array($r->status,['confirmed','converted'],true)||$r->confirmed_date!==($payload['body'][2]??null)||substr((string)$r->confirmed_time,0,5)!==($payload['body'][3]??null)))){
    DB::table('ccs_booking_whatsapp_outbox')->where('id',$item->id)->where('state','pending')->update(['state'=>'cancelled','error'=>'Request, consent, recipient, appointment or test scope changed.','updated_at'=>now()]);continue;
   }
   if(!config('ccs_care_sandbox.'.$item->kind.'_template'))continue;
   if(!DB::table('ccs_booking_whatsapp_outbox')->where('id',$item->id)->where('state','pending')->update(['state'=>'processing','updated_at'=>now()]))continue;
   try{$id=app(CcsCareTransport::class)->update($item->kind,$payload);DB::table('ccs_booking_whatsapp_outbox')->where('id',$item->id)->update(['state'=>'accepted','message_id'=>$id,'updated_at'=>now()]);$n++;}
   catch(RuntimeException $e){DB::table('ccs_booking_whatsapp_outbox')->where('id',$item->id)->update(['state'=>str_starts_with($e->getMessage(),'UNKNOWN')?'unknown':'failed','error'=>$e->getMessage(),'updated_at'=>now()]);}
  }return $n;
 }
}
