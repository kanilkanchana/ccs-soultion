<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class CcsServicePartsDocument {
 public function rows(int $jobId,?object $record=null,bool $historical=false): array {
  $readings=$record?DB::table('battery_module_readings')->where('battery_service_record_id',$record->id)->orderBy('position')->get()->keyBy('position'):collect();
  $query=DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->where('inv.repair_job_id',$jobId)->where('inv.status','!=','void')
   ->where(function($q){$q->where('item.stock_managed',true)->orWhere('item.item_origin','!=','service')->orWhereNotNull('item.serial_number');});
  if($historical){$query->whereIn('inv.status',['issued','partially_paid','paid']);if($record?->created_at)$query->whereRaw('COALESCE(inv.issued_at, inv.created_at) <= ?',[$record->created_at]);else $query->whereRaw('1 = 0');}
  $lines=$query->orderBy('item.id')->get(['item.id','item.description','item.serial_number','item.is_battery_module','item.item_condition','item.item_year','item.quantity','inv.invoice_number','inv.status']);
  $rows=[];$used=[];
  if($record){
   for($position=1;$position<=(int)$record->module_count;$position++){
    $reading=$readings->get($position);$serial=(string)($reading->serial_number??'');
    $line=$serial!==''?$lines->first(fn($line)=>(bool)$line->is_battery_module&&strtoupper((string)$line->serial_number)===strtoupper($serial)):null;
    if($line)$used[]=$line->id;
    $voltage=$reading?->voltage;
    $rows[]=['name'=>$line?->description?:__('Battery Module'),'serial'=>$serial,'details'=>$voltage===null?'—':rtrim(rtrim(number_format((float)$voltage,3,'.',''),'0'),'.').' V','position'=>$position,'notes'=>(string)($reading->notes??''),'invoice'=>$line?->invoice_number,'draft'=>$line?->status==='draft'];
   }
  }
  foreach($lines as $line){
   if(in_array($line->id,$used,true))continue;
   $details=[];if($line->is_battery_module)$details[]=__('Measured voltage not recorded');if($line->item_condition!==null&&$line->item_condition!=='')$details[]=__('Condition').': '.$line->item_condition;if($line->item_year!==null&&$line->item_year!=='')$details[]=__('Year').': '.$line->item_year;
   if((float)$line->quantity!==1.0)$details[]=__('Quantity').': '.$line->quantity;
   $rows[]=['name'=>$line->description,'serial'=>(string)($line->serial_number??''),'details'=>$details?implode(' · ',$details):'—','position'=>null,'notes'=>'','invoice'=>$line->invoice_number,'draft'=>$line->status==='draft'];
  }
  return $rows;
 }
}
