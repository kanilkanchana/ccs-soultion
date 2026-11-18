<?php
namespace App\Http\Controllers;
use App\Services\{CcsAdministration,CcsWebsiteIntake};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class CcsWebsiteInboxController extends Controller {
 public const STATES=['new','reviewing','confirmed','in_progress','resolved','cancelled','converted'];
 private function access(Request $r): void {abort_unless(in_array(app(CcsAdministration::class)->role($r->user('web')),['owner','manager','cashier','legacy'],true),403);abort_unless(Schema::hasTable('ccs_website_requests'),503,'Run the website migration first.');}
 public function index(Request $r){
  $this->access($r);$d=$r->validate(['q'=>'nullable|string|max:150','kind'=>'nullable|in:inspection,quote,contact,warranty,complaint','status'=>'nullable|in:'.implode(',',self::STATES),'date'=>'nullable|date_format:Y-m-d']);
  $q=DB::table('ccs_website_requests');if(!empty($d['q']))$q->where(function($q)use($d){foreach(['public_reference','name','phone','vehicle','registration_number','reference'] as $i=>$key){$i===0?$q->where($key,'like','%'.$d['q'].'%'):$q->orWhere($key,'like','%'.$d['q'].'%');}});
  foreach(['kind','status'] as $field)if(!empty($d[$field]))$q->where($field,$d[$field]);if(!empty($d['date']))$q->where(fn($q)=>$q->where('preferred_date',$d['date'])->orWhere('confirmed_date',$d['date']));
  $items=$q->orderByDesc('id')->paginate(20)->withQueryString();$counts=DB::table('ccs_website_requests')->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status');return view('ccs.website-inbox',compact('items','counts'));
 }
 public function show(Request $r,int $id){
  $this->access($r);$item=DB::table('ccs_website_requests')->where('id',$id)->first();abort_unless($item,404);
  $query=mb_substr(trim((string)$r->query('find','')),0,150);
  $phone=app(CcsWebsiteIntake::class)->phone((string)$item->phone);
  $plate=preg_replace('/[\s-]/u','',mb_strtoupper((string)$item->registration_number));
  $customers=DB::table('customers')->whereNull('deleted_at')->get(['id','name','customer_code','phone','whatsapp'])->filter(function($c)use($query,$phone,$item){
   if($query!=='')return collect([$c->name,$c->phone,$c->whatsapp,$c->customer_code])->contains(fn($v)=>mb_stripos((string)$v,$query)!==false)||app(CcsWebsiteIntake::class)->phone((string)$c->phone)===app(CcsWebsiteIntake::class)->phone($query);
   return ($phone!==''&&(app(CcsWebsiteIntake::class)->phone((string)$c->phone)===$phone||app(CcsWebsiteIntake::class)->phone((string)$c->whatsapp)===$phone))||mb_strtolower(trim($c->name))===mb_strtolower(trim($item->name));
  });
  $vehicles=DB::table('vehicles as v')->join('customers as c','c.id','=','v.customer_id')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->get(['v.id','v.registration_number','v.make','v.model','v.customer_id','c.name','c.customer_code','c.phone'])->filter(function($v)use($customers,$query,$plate){return $customers->contains('id',$v->customer_id)||($query!==''?mb_stripos($v->registration_number,$query)!==false:($plate!==''&&preg_replace('/[\s-]/u','',mb_strtoupper($v->registration_number))===$plate));})->take(30)->values();
  $extraIds=$vehicles->pluck('customer_id');
  $customers=$customers->merge(DB::table('customers')->whereIn('id',$extraIds)->whereNull('deleted_at')->get(['id','name','customer_code','phone','whatsapp']))->unique('id')->take(30)->values();
  $linkedJob=$item->repair_job_id?DB::table('repair_jobs')->where('id',$item->repair_job_id)->whereNull('deleted_at')->first():null;
  return view('ccs.website-request',compact('item','vehicles','customers','query','linkedJob'));
 }
 public function update(Request $r,int $id){
  $this->access($r);$d=$r->validate(['version'=>'required|integer|min:1','status'=>'required|in:'.implode(',',self::STATES),'internal_notes'=>'nullable|string|max:5000','confirmed_date'=>'nullable|date_format:Y-m-d','confirmed_time'=>'nullable|date_format:H:i']);
  if($d['status']==='confirmed'&&(empty($d['confirmed_date'])||empty($d['confirmed_time'])))throw ValidationException::withMessages(['confirmed_date'=>'Confirm both the date and time after agreeing with the customer.']);
  DB::transaction(function()use($r,$id,$d){$item=DB::table('ccs_website_requests')->where('id',$id)->lockForUpdate()->first();abort_unless($item,404);if($item->version!==(int)$d['version'])throw ValidationException::withMessages(['version'=>'Another staff member updated this request. Refresh before saving.']);
   if($d['status']==='converted'&&!$item->repair_job_id)throw ValidationException::withMessages(['status'=>'Use the Link / Create Job action before marking converted.']);
   DB::table('ccs_website_requests')->where('id',$id)->update(['status'=>$d['status'],'internal_notes'=>$d['internal_notes']??null,'confirmed_date'=>$d['confirmed_date']??null,'confirmed_time'=>$d['confirmed_time']??null,'updated_by'=>$r->user('web')->id,'version'=>$item->version+1,'updated_at'=>now()]);app(CcsAdministration::class)->audit($r->user('web')->id,'website_request_reviewed','website_request',$id,['from'=>$item->status,'to'=>$d['status']]);
  });return back()->with('success','Request updated. No automatic WhatsApp message was sent.');
 }
 public function convert(Request $r,int $id){
  $this->access($r);$d=$r->validate(['version'=>'required|integer|min:1','confirm'=>'accepted','existing_job_id'=>'nullable|integer|min:1','vehicle_id'=>'nullable|integer|min:1','customer_id'=>'nullable|integer|min:1','create_customer'=>'nullable|boolean','customer_name'=>'nullable|string|max:150','phone'=>'nullable|string|max:30','registration_number'=>'nullable|string|max:30','make'=>'nullable|string|max:80','model'=>'nullable|string|max:100','manufacture_year'=>'nullable|integer|between:1950,2100']);
  $jobId=DB::transaction(function()use($r,$id,$d){
   $item=DB::table('ccs_website_requests')->where('id',$id)->lockForUpdate()->first();abort_unless($item,404);if($item->repair_job_id)return (int)$item->repair_job_id;
   if((int)$item->version!==(int)$d['version'])throw ValidationException::withMessages(['version'=>'Request changed. Refresh before linking a job.']);
   if(!empty($d['existing_job_id'])){$job=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->where('j.id',$d['existing_job_id'])->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->first(['j.id','v.id as vehicle_id','v.customer_id']);abort_unless($job,422,'Choose an active existing job.');$vehicleId=$job->vehicle_id;$customerId=$job->customer_id;$jobId=$job->id;}
   else{
    if(!empty($d['vehicle_id'])){$vehicle=DB::table('vehicles as v')->join('customers as c','c.id','=','v.customer_id')->where('v.id',$d['vehicle_id'])->whereNull('v.deleted_at')->whereNull('c.deleted_at')->first(['v.id','v.customer_id']);abort_unless($vehicle,422,'Choose an active vehicle.');$vehicleId=$vehicle->id;$customerId=$vehicle->customer_id;}
    else{
     foreach(['registration_number','make','model'] as $field)if(empty(trim((string)($d[$field]??''))))throw ValidationException::withMessages([$field=>'Confirm the plate, make and model before creating a vehicle.']);
     $plate=mb_strtoupper(preg_replace('/\s+/u',' ',trim($d['registration_number'])));$norm=preg_replace('/[\s-]/u','',$plate);
     if(DB::table('vehicles')->whereRaw("UPPER(REPLACE(REPLACE(registration_number,' ',''),'-','')) = ?",[$norm])->exists())throw ValidationException::withMessages(['registration_number'=>'This plate already exists (possibly archived). Select/review its existing record.']);
     if(!empty($d['customer_id'])){$customerId=(int)$d['customer_id'];abort_unless(DB::table('customers')->where('id',$customerId)->whereNull('deleted_at')->exists(),422,'Choose an active customer.');}
     else{
      if(!$r->boolean('create_customer')||empty(trim((string)($d['customer_name']??'')))||empty($d['phone']))throw ValidationException::withMessages(['customer_id'=>'Select an existing customer or explicitly confirm creation with a verified name and phone.']);
      $phone=app(CcsWebsiteIntake::class)->phone($d['phone']);if(!preg_match('/^[1-9][0-9]{7,14}$/D',$phone))throw ValidationException::withMessages(['phone'=>'Confirm a valid customer phone number.']);
      $matches=DB::table('customers')->whereNull('deleted_at')->get(['id','phone','whatsapp'])->contains(fn($c)=>app(CcsWebsiteIntake::class)->phone((string)$c->phone)===$phone||app(CcsWebsiteIntake::class)->phone((string)$c->whatsapp)===$phone);
      if($matches)throw ValidationException::withMessages(['customer_id'=>'A customer with this contact number exists. Search and select that customer instead of creating a duplicate.']);
      $customerId=DB::table('customers')->insertGetId(['name'=>trim($d['customer_name']),'phone'=>$phone,'customer_code'=>'PENDING-'.Str::uuid(),'created_at'=>now(),'updated_at'=>now()]);DB::table('customers')->where('id',$customerId)->update(['customer_code'=>sprintf('CCS-C%06d',$customerId)]);
      app(CcsAdministration::class)->audit($r->user('web')->id,'customer_registered','customer',$customerId,['source'=>'website_review']);
     }
     $vehicleId=DB::table('vehicles')->insertGetId(['customer_id'=>$customerId,'registration_number'=>$plate,'make'=>$d['make'],'model'=>$d['model'],'manufacture_year'=>$d['manufacture_year']??null,'vehicle_code'=>'PENDING-'.Str::uuid(),'created_at'=>now(),'updated_at'=>now()]);DB::table('vehicles')->where('id',$vehicleId)->update(['vehicle_code'=>sprintf('CCS-V%06d',$vehicleId)]);
    }
    $jobId=DB::table('repair_jobs')->insertGetId(['vehicle_id'=>$vehicleId,'job_number'=>'PENDING-'.Str::uuid(),'status'=>$item->status==='confirmed'?'inspection_booked':'new_enquiry','complaint'=>$item->message?:'Website '.$item->kind.' request '.$item->public_reference,'service_type'=>mb_substr($item->service?:'Website inspection / enquiry',0,120),'job_category'=>'parts_other','received_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);DB::table('repair_jobs')->where('id',$jobId)->update(['job_number'=>sprintf('CCS-J%06d',$jobId)]);
    app(CcsAdministration::class)->audit($r->user('web')->id,'repair_job_created','repair_job',$jobId,['source'=>'website_review','request_id'=>$id,'technician'=>null]);
   }
   DB::table('ccs_website_requests')->where('id',$id)->update(['repair_job_id'=>$jobId,'vehicle_id'=>$vehicleId,'customer_id'=>$customerId,'status'=>'converted','updated_by'=>$r->user('web')->id,'version'=>$item->version+1,'updated_at'=>now()]);app(CcsAdministration::class)->audit($r->user('web')->id,'website_request_linked','website_request',$id,['repair_job_id'=>$jobId]);return (int)$jobId;
  });return redirect()->route('ccs.jobs',['job_id'=>$jobId])->with('success','Website request linked to the job. Review the job before using POS.');
 }
}
