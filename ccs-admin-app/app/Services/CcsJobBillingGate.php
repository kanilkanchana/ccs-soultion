<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class CcsJobBillingGate {
 public const READY=['ready','completed'];
 public static function allowed(?string $status): bool {return in_array($status,self::READY,true);}
 public function check(int $jobId): void {
  $job=DB::table('repair_jobs')->where('id',$jobId)->whereNull('deleted_at')->lockForUpdate()->first();
  if(!$job || !self::allowed($job->status))throw ValidationException::withMessages(['repair_job_id'=>__('Save the repair status as Ready or Completed before billing this job.')]);
 }
}
