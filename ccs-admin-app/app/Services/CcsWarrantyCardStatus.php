<?php
namespace App\Services;
use Illuminate\Support\Carbon;
class CcsWarrantyCardStatus
{
 public function summary(object $record,array $snapshot,?int $currentMileage=null): array {
  $draft=($snapshot['provisional']??true)!==false;
  $none=($snapshot['coverage_kind']??'')==='no_warranty';
  $start=$record->starts_on?Carbon::parse($record->starts_on)->startOfDay():null;
  $end=$record->expires_on?Carbon::parse($record->expires_on)->startOfDay():null;
  $today=today();
  $state=($snapshot['revoked']??false)?'revoked':($draft?'pending':($none?'no_warranty':($start&&$today->lt($start)?'not_started':($end?($today->gt($end)?'time_elapsed':'time_remaining'):'no_time_limit'))));
  $remaining=(!$draft&&!$none&&$end)?max(0,(int)$today->diffInDays($end,false)+1):null;
  return ['card_number'=>sprintf('CCS-W%06d',$record->id),'state'=>$state,'remaining_days'=>$remaining,
   'remaining_km'=>!$draft&&!$none&&$record->mileage_limit_km!==null&&$currentMileage!==null?max(0,(int)$record->mileage_limit_km-$currentMileage):null,
   'current_mileage_km'=>$currentMileage,'eligibility'=>'Check the agreed conditions; date/km figures do not decide claim eligibility.'];
 }
}
