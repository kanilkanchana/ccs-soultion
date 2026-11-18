<?php
namespace App\Http\Controllers;
use App\Services\{CcsWebsiteSettings,CcsWebsiteIntake,CcsSerialLookupService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CcsWebsitePublicController extends Controller {
 public function config(CcsWebsiteSettings $settings){$s=$settings->values();return response()->json(['version'=>1,'forms_enabled'=>$s['enabled'],'lookup_mode'=>$s['lookup_mode'],'timezone'=>'Asia/Colombo']);}
 public function submit(Request $r,CcsWebsiteIntake $intake){$result=$intake->receive($r->all(),(string)($r->header('Origin')??$r->getSchemeAndHttpHost()),(string)$r->ip());return response()->json($result+['received'=>true,'appointment_confirmed'=>false,'message'=>'Your request is saved for workshop review. The workshop will contact you; the preferred date is not a confirmed appointment.'],201);}
 public function lookup(Request $r,CcsWebsiteSettings $settings){
  $mode=$settings->values()['lookup_mode'];abort_if($mode==='disabled',403,'Online record lookup is not enabled. Contact the workshop.');
  $data=$r->validate(['plate'=>'required|string|max:30','serial'=>'nullable|string|max:100']);$plate=mb_strtoupper(preg_replace('/[\s-]/u','',trim($data['plate'])));
  $matches=DB::table('vehicles as v')->where('v.is_system_walkin',false)->join('customers as c','c.id','=','v.customer_id')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->whereRaw("UPPER(REPLACE(REPLACE(v.registration_number,' ',''),'-','')) = ?",[$plate])->limit(2)->get(['v.id','v.customer_id','v.registration_number','v.make','v.model','v.manufacture_year']);
  $notFound=fn()=>response()->json(['found'=>false,'message'=>'No unique matching record was found. Check the details or contact the workshop.'],404);
  if($matches->count()!==1)return $notFound();$v=$matches[0];
  $parts=DB::table('invoice_items as item')->join('invoices as i','i.id','=','item.invoice_id')->join('repair_jobs as j','j.id','=','i.repair_job_id')->where('j.vehicle_id',$v->id)->whereNull('j.deleted_at')->whereIn('i.status',['issued','partially_paid','paid'])->whereNotNull('item.serial_number')->where('item.serial_number','!=','')->select('item.description','item.serial_number','j.job_number')->orderByDesc('item.id')->limit(100)->get();
  $modules=DB::table('battery_module_readings as m')->join('battery_service_records as s','s.id','=','m.battery_service_record_id')->join('repair_jobs as j','j.id','=','s.repair_job_id')->where('s.vehicle_id',$v->id)->where('j.vehicle_id',$v->id)->whereNull('j.deleted_at')->whereNotNull('m.serial_number')->select('m.serial_number','j.job_number')->limit(100)->get();
  foreach($modules as $m)if(!$parts->contains(fn($p)=>$p->serial_number===$m->serial_number&&$p->job_number===$m->job_number))$parts->push((object)['description'=>'Battery module','serial_number'=>$m->serial_number,'job_number'=>$m->job_number]);
  $w=app(CcsSerialLookupService::class)->warranties((int)$v->customer_id,(int)$v->id);
  if($mode==='plate_serial'){
   $key=mb_strtolower(trim((string)($data['serial']??'')));if($key==='')return $notFound();
   $matched=$parts->contains(fn($p)=>mb_strtolower($p->serial_number)===$key)||collect($w)->contains(fn($p)=>mb_strtolower((string)$p['covered_serial'])===$key||mb_strtolower((string)$p['card_status']['card_number'])===$key);
   if(!$matched)return $notFound();
  }
  $cards=[];foreach($w as $card)$cards[]=['card_number'=>$card['card_status']['card_number'],'name'=>$card['name'],'part'=>$card['covered_description'],'serial'=>$card['covered_serial'],'starts_on'=>$card['starts_on'],'expires_on'=>$card['expires_on'],'distance_km'=>$card['distance_km'],'remaining_days'=>$card['card_status']['remaining_days'],'state'=>$card['card_status']['state'],'terms'=>$card['terms']];
  $jobs=DB::table('repair_jobs')->where('vehicle_id',$v->id)->whereNull('deleted_at')->orderByDesc('id')->limit(20)->get(['job_number','status','received_at','completed_at']);
  // Public projection only: no customer identity/contact, balances, diagnosis, internal notes or claims.
  unset($v->customer_id,$v->id);return response()->json(['found'=>true,'vehicle'=>$v,'parts'=>$parts->values(),'warranties'=>$cards,'jobs'=>$jobs,'access'=>'unverified_public_projection','notice'=>'This search does not verify customer identity or approve warranty coverage. Private customer details and invoices are not shown here. Contact the workshop for invoice copies.']);
 }
}
