<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class CcsWarrantyBenefits
{
 public function summary(int $warrantyId,array $snapshot): array {
  $total=max(0,(int)($snapshot['free_service_count']??0));
  $used=DB::table('warranty_service_visits')->where('warranty_id',$warrantyId)->count();
  $visits=DB::table('warranty_service_visits as s')->join('repair_jobs as j','j.id','=','s.repair_job_id')->where('s.warranty_id',$warrantyId)->whereNull('j.deleted_at')->orderBy('s.service_date')->orderBy('s.id')->get(['s.service_date','s.mileage_km','j.job_number']);
  return ['included'=>$total,'used'=>$used,'remaining'=>max(0,$total-$used),'visits'=>$visits];
 }
}
