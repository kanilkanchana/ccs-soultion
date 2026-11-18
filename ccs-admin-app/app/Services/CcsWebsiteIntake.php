<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Validator,RateLimiter};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class CcsWebsiteIntake {
 public function phone(string $value): string {$p=preg_replace('/\D/','',$value);if(preg_match('/^0[0-9]{9}$/D',$p))$p='94'.substr($p,1);return $p;}
 public function receive(array $input,string $origin,string $ip): array {
  $v=Validator::make($input,[
   'submission_id'=>'required|uuid','kind'=>'required|in:inspection,quote,contact,warranty,complaint','name'=>'required|string|min:2|max:150','phone'=>'required|string|max:30','whatsapp'=>'nullable|string|max:30','vehicle'=>'nullable|string|max:180','registration_number'=>'nullable|string|max:30','chassis'=>'nullable|string|max:80','service'=>'nullable|string|max:150','reference'=>'nullable|string|max:120','message'=>'nullable|string|max:3000',
   'preferred_date'=>'nullable|date_format:Y-m-d|after_or_equal:'.now('Asia/Colombo')->toDateString(),'preferred_time'=>'nullable|date_format:H:i','contact_consent'=>'required|accepted','reminder_consent'=>'nullable|boolean','source_page'=>'required|in:index.html,contact.html,warranty.html,support.html,portal.html','website'=>'nullable|string|max:0',
  ]);$data=$v->validate();unset($data['website']);
  $data['phone']=$this->phone($data['phone']);if(!preg_match('/^[1-9][0-9]{7,14}$/D',$data['phone']))throw ValidationException::withMessages(['phone'=>'Enter a valid phone number, for example your Sri Lankan number with its leading 0.']);
  if(!empty($data['whatsapp'])){$data['whatsapp']=$this->phone($data['whatsapp']);if(!preg_match('/^[1-9][0-9]{7,14}$/D',$data['whatsapp']))throw ValidationException::withMessages(['whatsapp'=>'Enter a valid WhatsApp number.']);}
  if(in_array($data['kind'],['inspection','quote'],true)&&!trim((string)($data['vehicle']??'')))throw ValidationException::withMessages(['vehicle'=>'Enter the vehicle make and model.']);
  if(in_array($data['kind'],['contact','warranty','complaint'],true)&&!trim((string)($data['message']??'')))throw ValidationException::withMessages(['message'=>'Enter your question or current symptoms.']);
  foreach($data as $key=>$value)if(is_string($value))$data[$key]=trim($value);
  $data['contact_consent']=true;$data['reminder_consent']=(bool)($data['reminder_consent']??false);$data['source_origin']=$origin;
  $id=$data['submission_id'];$canonical=$data;unset($canonical['submission_id']);ksort($canonical);$hash=hash('sha256',json_encode($canonical,JSON_UNESCAPED_UNICODE));
  $prior=DB::table('ccs_website_requests')->where('submission_id',$id)->first();
  if($prior){abort_unless(hash_equals($prior->payload_hash,$hash),409,'This request key was already used for different details. Refresh and try again.');return ['reference'=>$prior->public_reference,'duplicate'=>true];}
  if(!RateLimiter::attempt('website-phone:'.hash('sha256',$data['phone']),5,static fn()=>true,3600))throw ValidationException::withMessages(['phone'=>'Too many requests for this contact number. Please call the workshop.']);
  $data['payload_hash']=$hash;$data['public_reference']='CCS-WEB-'.strtoupper(Str::random(12));$data['status']='new';$data['created_at']=now();$data['updated_at']=now();
  $saved=DB::transaction(function()use($data,$id,$hash){
   DB::table('ccs_website_requests')->insertOrIgnore($data);$row=DB::table('ccs_website_requests')->where('submission_id',$id)->first();abort_unless($row&&hash_equals($row->payload_hash,$hash),409,'Request conflict. Try a new submission.');return $row;
  });
  return ['reference'=>$saved->public_reference,'duplicate'=>false];
 }
}
