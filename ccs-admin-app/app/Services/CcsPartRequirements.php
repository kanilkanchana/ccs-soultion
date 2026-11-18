<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CcsPartRequirements {
 public function recordPopup(int $jobId,array $lines,int $userId): array {
  $entered=array_values(array_filter($lines,fn($l)=>!empty($l['is_battery_module'])&&(($l['module_voltage']??null)!==null||($l['module_position']??null)!==null)));
  if($entered){
   $job=DB::table('repair_jobs')->where('id',$jobId)->whereNull('deleted_at')->first();abort_unless($job,404);
   DB::table('vehicles')->where('id',$job->vehicle_id)->lockForUpdate()->first();
   $latest=DB::table('battery_service_records')->where('vehicle_id',$job->vehicle_id)->orderByDesc('id')->first();
   $readings=$latest?DB::table('battery_module_readings')->where('battery_service_record_id',$latest->id)->get()->map(fn($r)=>['position'=>(int)$r->position,'serial_number'=>$r->serial_number,'voltage'=>$r->voltage,'notes'=>$r->notes])->keyBy('position')->all():[];
   $changed=!$latest||(int)$latest->repair_job_id!==$jobId;
   if($changed)foreach($readings as &$carried){$carried['voltage']=null;}unset($carried);
   $positions=[];
   foreach($entered as $line){
    $serial=trim((string)($line['serial_number']??''));$position=$line['module_position']??null;$voltage=$line['module_voltage']??null;
    if($serial===''||$position===null||$voltage===null)throw ValidationException::withMessages(['stock'=>__('Enter the module position, actual serial and measured voltage together.')]);
    if(isset($positions[$position]))throw ValidationException::withMessages(['stock'=>__('Two modules cannot use the same position.')]);$positions[$position]=true;
    $previous=$readings[$position]??null;
    foreach($readings as $key=>$reading)if(mb_strtoupper($reading['serial_number'])===mb_strtoupper($serial))unset($readings[$key]);
    if(!$previous||mb_strtoupper($previous['serial_number'])!==mb_strtoupper($serial)||$previous['voltage']===null||(float)$previous['voltage']!==(float)$voltage)$changed=true;
    $readings[$position]=['position'=>(int)$position,'serial_number'=>$serial,'voltage'=>$voltage,'notes'=>null];
   }
   if($latest && $latest->service_date>today()->toDateString())throw ValidationException::withMessages(['stock'=>__('Check the saved service date before recording module readings.')]);
   if($latest&&$latest->mileage_km!==null&&$job->mileage_at_service_km!==null&&(int)$job->mileage_at_service_km<(int)$latest->mileage_km)throw ValidationException::withMessages(['stock'=>__('Job mileage cannot be lower than the latest saved service mileage.')]);
   $count=max(34,(int)($latest->module_count??0),...array_keys($readings));
   if($changed){
   $id=DB::table('battery_service_records')->insertGetId(['repair_job_id'=>$jobId,'vehicle_id'=>$job->vehicle_id,'recorded_by'=>$userId,'request_token'=>(string)\Illuminate\Support\Str::uuid(),'service_date'=>today()->toDateString(),'mileage_km'=>$job->mileage_at_service_km??$latest?->mileage_km,'module_count'=>$count,'notes'=>'Module readings recorded from POS.','created_at'=>now(),'updated_at'=>now()]);
   foreach($readings as $reading)DB::table('battery_module_readings')->insert(array_merge($reading,['battery_service_record_id'=>$id]));
   DB::table('audit_logs')->insert(['user_id'=>$userId,'action'=>'battery_readings_saved_from_pos','entity_type'=>'battery_service_record','entity_id'=>$id,'metadata'=>json_encode(['job_id'=>$jobId,'entered_modules'=>count($entered)]),'created_at'=>now(),'updated_at'=>now()]);
   }
  }
  foreach($lines as &$line){unset($line['module_voltage'],$line['module_position']);}unset($line);return $lines;
 }
 public function check(int $jobId,iterable $lines): void {
  foreach($lines as $raw){$line=(array)$raw;$module=(bool)($line['is_battery_module']??false);$serial=trim((string)($line['serial_number']??''));
   if((!empty($line['requires_serial'])||$module)&&$serial==='')throw ValidationException::withMessages(['stock'=>'Actual serial is required for '.$line['description'].'. Complete the part details before finalizing.']);
   if((!empty($line['requires_serial'])||$module)&& (float)$line['quantity']!==1.0)throw ValidationException::withMessages(['stock'=>'Add each serialized unit as a separate row with quantity 1.']);
   if($module){
    $record=DB::table('battery_service_records')->where('repair_job_id',$jobId)->orderByDesc('id')->first();
    $reading=$record?DB::table('battery_module_readings')->where('battery_service_record_id',$record->id)->whereRaw('UPPER(serial_number) = ?',[mb_strtoupper($serial)])->first():null;
    if(!$reading||$reading->voltage===null)throw ValidationException::withMessages(['stock'=>__('Battery module :serial needs a measured voltage. Enter position and voltage in the part popup, or save them in this job’s Battery Job Card.',['serial'=>$serial])]);
   }
  }
 }
}
