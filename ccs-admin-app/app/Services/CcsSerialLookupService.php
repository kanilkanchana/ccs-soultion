<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;

/** Scope is supplied by trusted server identity, never by public search input. */
class CcsSerialLookupService
{
    public function lookup(int $customerId,string $serial,int $page=1): array
    {
        $serial=trim($serial);
        $key=mb_strtolower($serial);
        $owned=function($query)use($customerId){
            return $query->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')
                ->where('v.customer_id',$customerId)->whereNull('v.deleted_at')->whereNull('c.deleted_at')->whereNull('j.deleted_at');
        };
        $battery=$owned(DB::table('battery_module_readings as m')->join('battery_service_records as s','s.id','=','m.battery_service_record_id')->join('repair_jobs as j','j.id','=','s.repair_job_id'))
            ->whereColumn('s.vehicle_id','v.id')->whereRaw('LOWER(m.serial_number) = ?',[$key])
            ->selectRaw("'battery_module' as source, m.id as record_id, s.id as service_id, j.id as job_id, v.id as vehicle_id, s.service_date as recorded_at");
        $parts=$owned(DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->join('repair_jobs as j','j.id','=','inv.repair_job_id'))
            ->whereIn('inv.status',['issued','partially_paid','paid'])
            ->where(function($q){$q->where('item.stock_managed',true)->orWhereIn('item.item_origin',['manual_part','customer_supplied']);})
            ->whereRaw('LOWER(item.serial_number) = ?',[$key])
            ->selectRaw("'invoice_part' as source, item.id as record_id, NULL as service_id, j.id as job_id, v.id as vehicle_id, inv.issued_at as recorded_at");
        $union=$battery->unionAll($parts);
        $query=DB::query()->fromSub($union,'matches');
        $total=(clone $query)->count();
        $rows=$query->orderByDesc('recorded_at')->orderBy('source')->orderByDesc('record_id')->forPage($page,20)->get();
        $data=[];
        foreach($rows as $row){
            $vehicle=DB::table('vehicles')->where('id',$row->vehicle_id)->where('customer_id',$customerId)->whereNull('deleted_at')->first(['id','registration_number','make','model','manufacture_year']);
            if(!$vehicle)continue;
            $job=DB::table('repair_jobs')->where('id',$row->job_id)->whereNull('deleted_at')->first(['job_number']);
            if(!$job)continue;
            $entry=['source'=>$row->source,'record_id'=>(int)$row->record_id,'service_id'=>$row->service_id===null?null:(int)$row->service_id,
                'recorded_at'=>$row->recorded_at,'job_number'=>$job->job_number,'vehicle'=>$vehicle];
            if($row->source==='battery_module'){
                $module=DB::table('battery_module_readings')->where('id',$row->record_id)->first(['serial_number','position','voltage']);
                $entry['part']=['description'=>'Battery module','serial_number'=>$module->serial_number,'position'=>$module->position,'voltage'=>$module->voltage,'origin'=>'battery_module'];
            }else{
                $part=DB::table('invoice_items')->where('id',$row->record_id)->first(['description','serial_number','item_origin']);
                $entry['part']=['description'=>$part->description,'serial_number'=>$part->serial_number,'origin'=>$part->item_origin];
            }
            $data[]=$entry;
        }
        $warranties=[];
        foreach(collect($data)->pluck('vehicle.id')->unique() as $vehicleId){
            foreach($this->warranties($customerId,(int)$vehicleId) as $warranty)$warranties[]=$warranty;
        }
        return ['data'=>$data,'warranties'=>$warranties,'meta'=>['serial_number'=>$serial,'current_page'=>$page,'per_page'=>20,'total'=>$total,'last_page'=>max(1,(int)ceil($total/20)),
            'warranty_scope'=>'vehicles_on_current_page','verification'=>'required_for_customer_access']];
    }
    public function warranties(int $customerId,int $vehicleId): array
    {
        $rows=DB::table('warranties as w')->join('repair_jobs as j','j.id','=','w.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')
            ->join('customers as c','c.id','=','v.customer_id')->join('warranty_rules as r','r.id','=','w.warranty_rule_id')
            ->where('v.id',$vehicleId)->where('v.customer_id',$customerId)->whereNull('v.deleted_at')->whereNull('c.deleted_at')->whereNull('j.deleted_at')
            ->where('r.approved_by_owner',true)->where('r.active',true)->orderByDesc('w.id')
            ->get(['w.id','w.invoice_item_id','w.terms_snapshot','w.starts_on','w.expires_on','w.mileage_limit_km','v.id as vehicle_id','v.current_mileage_km','j.job_number']);
        $data=[];
        foreach($rows as $row){
            $terms=json_decode($row->terms_snapshot,true);
            if(!is_array($terms)||($terms['provisional']??true)!==false)continue;
            $data[]=['card_status'=>app(CcsWarrantyCardStatus::class)->summary($row,$terms,$row->current_mileage_km===null?null:(int)$row->current_mileage_km),
                'duration_months'=>$terms['duration_months']??null,'distance_km'=>$terms['distance_km']??null,'quantity'=>$terms['quantity']??1,
                'invoice_item_id'=>$row->invoice_item_id,'coverage_kind'=>$terms['coverage_kind']??'standard','covered_serial'=>$terms['covered_serial']??null,'covered_description'=>$terms['covered_description']??null,'free_services'=>app(CcsWarrantyBenefits::class)->summary($row->id,$terms),
                'id'=>$row->id,'vehicle_id'=>$row->vehicle_id,'job_number'=>$row->job_number,'name'=>$terms['name']??'Warranty',
                'starts_on'=>$row->starts_on,'expires_on'=>$row->expires_on,'mileage_limit_km'=>$row->mileage_limit_km,'terms'=>$terms['terms']??null,'limit_basis'=>$terms['limit_basis']??null];
        }
        return $data;
    }
}
