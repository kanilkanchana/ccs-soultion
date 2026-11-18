<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Illuminate\Validation\Rule;
class CcsTechnicianController extends Controller {
 public const STATUSES=['new_enquiry','vehicle_received','vehicle_for_assessment','inspection_booked','repair_in_progress','awaiting_parts','awaiting_approval','testing','ready','completed'];
 private function current(){return DB::table('repair_jobs as j')->where('j.is_system_walkin',false)->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->whereNotIn('j.status',['delivered','closed'])->select('j.id','j.job_number','j.status','j.assigned_user_id','j.technician_name','j.complaint','j.diagnosis','j.parts_notes','j.received_at','j.service_type','v.registration_number','v.make','v.model','c.name as customer_name');}
 public function jobs(Request $request){
  $request->validate(['q'=>'nullable|string|max:150','scope'=>'nullable|in:current,my','job_id'=>'nullable|integer|min:1']);$search=trim((string)$request->query('q',''));$scope=$request->query('scope','my');$query=$this->current();if($scope==='my')$query->where('j.assigned_user_id',$request->user()->id);
  if($search!=='')$query->where(function($q)use($search){$q->where('j.job_number','like','%'.$search.'%')->orWhere('v.registration_number','like','%'.$search.'%')->orWhere('c.name','like','%'.$search.'%')->orWhere('j.service_type','like','%'.$search.'%');});
  $jobs=$query->orderByDesc('j.id')->paginate(15)->withQueryString();$id=(int)$request->query('job_id',$jobs->first()->id??0);$selected=$id?$this->current()->where('j.id',$id)->first():null;if($request->filled('job_id'))abort_unless($selected,404);
  $technicians=DB::table('users')->where('ccs_active',true)->where('ccs_role','technician')->orderBy('name')->get(['id','name']);$statuses=self::STATUSES;return view('ccs.technician.jobs',compact('jobs','selected','technicians','statuses','search','scope'));
 }
 public function parts(Request $request){$request->validate(['q'=>'nullable|string|max:150']);$search=trim((string)$request->query('q',''));$query=DB::table('inventory_items')->where('active',true)->where('quantity_on_hand','>',0)->select('id','sku','name','category','quantity_on_hand');if($search!=='')$query->where(fn($q)=>$q->where('name','like','%'.$search.'%')->orWhere('sku','like','%'.$search.'%'));$parts=$query->orderBy('name')->paginate(24)->withQueryString();return view('ccs.technician.parts',compact('parts','search'));}
 public function update(Request $request,int $id){
  $data=$request->validate(['status'=>['required',Rule::in(self::STATUSES)],'technician_id'=>'nullable|integer|min:1','diagnosis'=>'nullable|string|max:5000','parts_notes'=>'nullable|string|max:3000']);
  DB::transaction(function()use($request,$id,$data){$job=DB::table('repair_jobs')->where('id',$id)->whereNull('deleted_at')->lockForUpdate()->first();abort_unless($job&&!in_array($job->status,['delivered','closed'],true),404);
   $updates=['status'=>$data['status'],'updated_at'=>now(),'completed_at'=>in_array($data['status'],['ready','completed'],true)?($job->completed_at??now()):null];foreach(['diagnosis','parts_notes'] as $key)if(array_key_exists($key,$data))$updates[$key]=$data[$key];
   if(array_key_exists('technician_id',$data)){$tech=empty($data['technician_id'])?null:DB::table('users')->where('id',$data['technician_id'])->where('ccs_active',true)->where('ccs_role','technician')->first(['id','name']);abort_if(!empty($data['technician_id'])&&!$tech,422,'Choose an active technician.');$updates['assigned_user_id']=$tech->id??null;$updates['technician_name']=$tech->name??null;}
   DB::table('repair_jobs')->where('id',$id)->update($updates);app(\App\Services\CcsAdministration::class)->audit($request->user()->id,'technician_job_updated','repair_job',$id,['from'=>$job->status,'to'=>$data['status'],'previous_assigned_user_id'=>$job->assigned_user_id,'assigned_user_id'=>$updates['assigned_user_id']??null]);
  });return back()->with('success','Job status, assignment and notes saved.');
 }
}
