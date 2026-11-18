<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CcsWarrantyController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q'=>['nullable','string','max:150'],'job_id'=>['nullable','integer','min:1'],'customer_id'=>['nullable','integer','min:1']]);
        $canApprove=app(\App\Services\CcsWarrantyApproval::class)->allowed($request);
        $selectedJob=(int)$request->query('job_id',old('repair_job_id',0));$selectedCustomer=(int)$request->query('customer_id',0);$q=trim((string)$request->query('q',''));
        if(!$selectedCustomer && $selectedJob)$selectedCustomer=(int)DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->where('j.id',$selectedJob)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->value('v.customer_id');
        $customers=DB::table('customers')->whereNull('deleted_at')->orderBy('name')->get(['id','name','phone','customer_code']);
        $rules=DB::table('warranty_rules')->orderBy('name')->get();
        $jobs=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')
            ->where('c.id',$selectedCustomer)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')
            ->get(['j.id','j.vehicle_id','j.job_number','j.mileage_at_service_km','v.registration_number','v.make','v.model','c.name as customer_name']);
        $entries=DB::table('warranties as w')->join('repair_jobs as j','j.id','=','w.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->leftJoin('warranty_rules as r','r.id','=','w.warranty_rule_id')
            ->when($selectedCustomer,fn($query)=>$query->where('c.id',$selectedCustomer))->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')
            ->when($q!=='',function($query)use($q){$query->where(function($x)use($q){$x->where('c.name','like','%'.$q.'%')->orWhere('c.customer_code','like','%'.$q.'%')->orWhere('c.phone','like','%'.$q.'%')->orWhere('j.job_number','like','%'.$q.'%')->orWhere('v.registration_number','like','%'.$q.'%')->orWhere('w.terms_snapshot','like','%'.$q.'%');});})
            ->select('w.*','v.id as vehicle_id','j.job_number','v.registration_number','v.make','v.model','v.current_mileage_km','c.name as customer_name','c.id as customer_id','c.phone as customer_phone','c.whatsapp as customer_whatsapp','r.name as rule_name','r.approved_by_owner','r.active')->orderByDesc('w.starts_on')->orderByDesc('w.id')->paginate(12)->withQueryString();
        foreach($entries as $entry){$snapshot=json_decode($entry->terms_snapshot,true)??[];$entry->card_status=app(\App\Services\CcsWarrantyCardStatus::class)->summary($entry,$snapshot,$entry->current_mileage_km===null?null:(int)$entry->current_mileage_km);$entry->benefits=app(\App\Services\CcsWarrantyBenefits::class)->summary($entry->id,$snapshot);}
        $partChoices=DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->whereIn('inv.repair_job_id',$jobs->pluck('id'))->whereIn('inv.status',['issued','partially_paid','paid'])->get(['item.id','item.description','item.serial_number','item.item_year','inv.repair_job_id']);
        // Show saved sale facts; a serial alone does not grant a warranty package.
        $partsWithoutCards=DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->join('repair_jobs as j','j.id','=','inv.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')
            ->whereIn('inv.repair_job_id',$jobs->pluck('id'))->whereIn('inv.status',['issued','partially_paid','paid'])
            ->where(function($query){$query->where('item.stock_managed',true)->orWhere('item.item_origin','!=','service');})
            ->whereNotExists(function($query){$query->selectRaw('1')->from('warranties as w')->where(function($match){$match->whereColumn('w.invoice_item_id','item.id')->orWhereColumn('w.stock_sale_item_id','item.id');});})
            ->orderByDesc('item.id')->limit(50)->get(['item.id','item.description','item.serial_number','item.sale_warranty_rule_id','item.sale_warranty_snapshot','inv.invoice_number','inv.id as invoice_id','j.id as repair_job_id','j.job_number','v.registration_number']);
        $serviceVisits=DB::table('warranty_service_visits')->whereIn('warranty_id',$entries->pluck('id'))->get()->groupBy('warranty_id');
        return view('ccs.warranties',compact('rules','jobs','entries','canApprove','selectedJob','selectedCustomer','customers','q','partChoices','serviceVisits','partsWithoutCards'));
    }

    public function customerJobs(Request $request){
        $data=$request->validate(['customer_id'=>['required','integer','min:1']]);
        abort_unless(DB::table('customers')->where('id',$data['customer_id'])->whereNull('deleted_at')->exists(),404);
        $jobs=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->where('v.customer_id',$data['customer_id'])->whereNull('j.deleted_at')->whereNull('v.deleted_at')->orderByDesc('j.id')->get(['j.id','j.job_number','j.mileage_at_service_km','v.registration_number']);
        $parts=DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->whereIn('inv.repair_job_id',$jobs->pluck('id'))->whereIn('inv.status',['issued','partially_paid','paid'])->get(['item.id','item.description','item.serial_number','inv.repair_job_id']);
        return response()->json(['jobs'=>$jobs,'parts'=>$parts])->header('Cache-Control','no-store, private');
    }
    public function printCard(Request $request,int $id)
    {
        $entry=DB::table('warranties as w')->join('repair_jobs as j','j.id','=','w.repair_job_id')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->join('warranty_rules as r','r.id','=','w.warranty_rule_id')->where('w.id',$id)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->where('r.approved_by_owner',true)->where('r.active',true)->select('w.*','j.job_number','v.registration_number','v.make','v.model','v.current_mileage_km','c.name as customer_name')->first();
        abort_unless($entry,404);$snapshot=json_decode($entry->terms_snapshot,true)??[];abort_unless(($snapshot['provisional']??true)===false,404);
        $card=app(\App\Services\CcsWarrantyCardStatus::class)->summary($entry,$snapshot,$entry->current_mileage_km===null?null:(int)$entry->current_mileage_km);
        $benefits=app(\App\Services\CcsWarrantyBenefits::class)->summary($id,$snapshot);
        return response()->view('ccs.warranty-print',compact('entry','snapshot','card','benefits'))->header('Cache-Control','no-store, private')->header('X-Robots-Tag','noindex, nofollow');
    }
    public function createRule(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:hybrid_battery,abs,other'],
            'duration_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'distance_km' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'terms' => ['required', 'string', 'max:5000'],
            'limit_basis'=>['required','in:time_first,distance_first,whichever_first,custom'],
            'coverage_kind'=>['nullable','in:standard,reconditioned_battery,new_battery,module,abs,no_warranty'],
            'free_service_count'=>['nullable','integer','min:0','max:20'],
        ]);
        // Rules stay drafts until a configured approver explicitly confirms them.
        DB::table('warranty_rules')->insert([
            'coverage_kind'=>$data['coverage_kind']??'standard','free_service_count'=>$data['free_service_count']??0,
            'name' => $data['name'], 'category' => $data['category'],
            'duration_months' => $data['duration_months'] ?? null,
            'distance_km' => $data['distance_km'] ?? null,
            'limit_basis' => $data['limit_basis'],
            'terms' => $data['terms'] ?? null,
            'approved_by_owner' => false, 'active' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('ccs.warranties',['packages'=>1])->with('success', 'Draft rule saved. Owner approval is required before this can become an official warranty.');
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'repair_job_id' => ['required', 'integer', 'exists:repair_jobs,id'],
            'warranty_rule_id' => ['required', 'integer', 'exists:warranty_rules,id'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'starting_mileage_km' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'issue_now'=>['nullable','accepted'],
            'invoice_item_id'=>['nullable','integer','exists:invoice_items,id'],
            'covered_serial'=>['nullable','string','max:100'],
            'battery_year'=>['nullable','integer','min:1900','max:2100'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $job = DB::table('repair_jobs')->lockForUpdate()->where('id', $data['repair_job_id'])->whereNull('deleted_at')->first();
            if (!$job) {
                throw ValidationException::withMessages(['repair_job_id' => 'Repair job not found.']);
            }
            abort_unless(DB::table('vehicles as v')->join('customers as c','c.id','=','v.customer_id')->where('v.id',$job->vehicle_id)->whereNull('v.deleted_at')->whereNull('c.deleted_at')->exists(),404);
            $rule = DB::table('warranty_rules')->where('id', $data['warranty_rule_id'])->lockForUpdate()->first();
            if (!$rule) {
                throw ValidationException::withMessages(['warranty_rule_id' => 'Warranty rule not found.']);
            }
            $issue=$request->boolean('issue_now');
            if($issue){app(\App\Services\CcsWarrantyApproval::class)->requireApprover($request);$this->checkRule($rule);}
            $part=null;
            if(!empty($data['invoice_item_id'])){
                $part=DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->where('item.id',$data['invoice_item_id'])->where('inv.repair_job_id',$job->id)->whereIn('inv.status',['issued','partially_paid','paid'])->first(['item.id','item.description','item.serial_number','item.item_year']);
                if(!$part)throw ValidationException::withMessages(['invoice_item_id'=>'Choose a finalized bill item from this job.']);
            }
            $serial=trim((string)($data['covered_serial']??''));
            if($part && $serial!=='' && strcasecmp($serial,(string)$part->serial_number)!==0)throw ValidationException::withMessages(['covered_serial'=>'Serial does not match the selected bill item.']);
            if($serial!=='' && !$part){
                $moduleExists=DB::table('battery_module_readings as m')->join('battery_service_records as s','s.id','=','m.battery_service_record_id')->where('s.repair_job_id',$job->id)->where('s.vehicle_id',$job->vehicle_id)->whereRaw('LOWER(m.serial_number) = ?',[mb_strtolower($serial)])->exists();
                if(!$moduleExists)throw ValidationException::withMessages(['covered_serial'=>'Choose a billed part or enter a module serial saved on this job card.']);
            }
            if($serial==='' && $part)$serial=(string)$part->serial_number;
            $start = Carbon::createFromFormat('Y-m-d', $data['starts_on'])->startOfDay();
            $months = $rule->duration_months;
            // Null or zero duration = no time coverage, not an indefinite warranty.
            $expires = $months !== null && (int) $months > 0 ? $start->copy()->addMonthsNoOverflow((int) $months)->subDay()->toDateString() : null;
            $baseMileage = $data['starting_mileage_km'] ?? $job->mileage_at_service_km;
            if($issue && (int)$rule->distance_km>0 && $baseMileage===null)throw ValidationException::withMessages(['starting_mileage_km'=>'Enter starting mileage for this distance warranty.']);
            $duplicates=DB::table('warranties')->where('repair_job_id',$job->id)->where('warranty_rule_id',$rule->id)->where('starts_on',$start->toDateString())->where('invoice_item_id',$part->id??null)->get();
            foreach($duplicates as $existing){$saved=json_decode($existing->terms_snapshot,true)??[];if(mb_strtolower((string)($saved['covered_serial']??''))===mb_strtolower($serial))throw ValidationException::withMessages(['repair_job_id'=>'This warranty already exists for this job, part/serial and start date.']);}
            $limit = $rule->distance_km !== null && $baseMileage !== null ? (int) $baseMileage + (int) $rule->distance_km : null;
            // CCS_CARE_V3_WARRANTY_ID
            $warrantyId=DB::table('warranties')->insertGetId([
                'invoice_item_id'=>$part->id??null,
                'repair_job_id' => $job->id, 'warranty_rule_id' => $rule->id,
                'starts_on' => $start->toDateString(), 'expires_on' => $expires,
                'mileage_limit_km' => $limit,
                'terms_snapshot' => json_encode([
                    'coverage_kind'=>$rule->coverage_kind,'free_service_count'=>(int)$rule->free_service_count,
                    'battery_year'=>$part->item_year??($data['battery_year']??null),
                    'covered_serial'=>$serial!==''?$serial:null,'covered_description'=>$part->description??null,
                    'name' => $rule->name, 'category' => $rule->category,
                    'duration_months' => $rule->duration_months, 'distance_km' => $rule->distance_km,
                    'terms' => $rule->terms, 'starting_mileage_km' => $baseMileage,
                    'operator_notes' => $data['notes'] ?? null,
                    'provisional' => !$issue,
                    'limit_basis'=>$rule->limit_basis,
                    'approved_by'=>$issue?$request->user('web')->id:null,
                    'approved_at'=>$issue?now()->toIso8601String():null,
                    'notice' => $issue?'Approved warranty record.':'Internal draft only. Not an approved warranty or customer entitlement.',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id,
                'action' => $issue?'warranty_issued':'provisional_warranty_assigned',
                'entity_type' => 'repair_job', 'entity_id' => $job->id,
                /* CCS_CARE_V3_WARRANTY_AUDIT */ 'metadata' => json_encode(['warranty_rule_id' => $rule->id,'warranty_id'=>$warrantyId]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
        $customerId=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->where('j.id',$data['repair_job_id'])->value('v.customer_id');
        return redirect()->route('ccs.warranties',['customer_id'=>$customerId])->with('success', $request->boolean('issue_now')?'Warranty issued. Approved details are available to the customer.':'Draft warranty saved. It remains hidden from customers until approved.');
    }
    public function updateRule(Request $request,int $id)
    {
        app(\App\Services\CcsWarrantyApproval::class)->requireApprover($request);
        $data=$request->validate(['name'=>['required','string','max:255'],'terms'=>['required','string','max:5000'],'limit_basis'=>['required','in:time_first,distance_first,whichever_first,custom'],'duration_months'=>['nullable','integer','min:0','max:120'],'distance_km'=>['nullable','integer','min:0','max:1000000'],'free_service_count'=>['required','integer','min:0','max:20']]);
        DB::transaction(function()use($id,$data,$request){$rule=DB::table('warranty_rules')->where('id',$id)->lockForUpdate()->first();abort_unless($rule,404);if($rule->approved_by_owner){
            $revision=array_merge($data,['category'=>$rule->category,'coverage_kind'=>$rule->coverage_kind,'package_key'=>null,'approved_by_owner'=>false,'active'=>false,'created_at'=>now(),'updated_at'=>now()]);
            $newId=DB::table('warranty_rules')->insertGetId($revision);$this->audit($request,'warranty_package_revision_created','warranty_rule',$newId,['previous_rule_id'=>$id]);return;
        }DB::table('warranty_rules')->where('id',$id)->update(array_merge($data,['updated_at'=>now()]));$this->audit($request,'warranty_draft_package_updated','warranty_rule',$id,[]);});
        return redirect()->route('ccs.warranties',['packages'=>1])->with('success','Package saved. Editing an approved package creates a draft revision; approve it and select it in stock for future bills. Existing cards keep their terms.');
    }
    private function checkRule($rule): void
    {
        if(!$rule->approved_by_owner||!$rule->active)throw ValidationException::withMessages(['warranty_rule_id'=>'Choose an active, owner-approved rule.']);
        if($rule->coverage_kind==='no_warranty'){if((int)$rule->duration_months>0||(int)$rule->distance_km>0||(int)$rule->free_service_count>0||!trim((string)$rule->terms))throw ValidationException::withMessages(['warranty_rule_id'=>'No-warranty packages must have zero coverage/services and an explicit explanation.']);return;}
        if((int)$rule->duration_months<=0 && (int)$rule->distance_km<=0)throw ValidationException::withMessages(['warranty_rule_id'=>'Set a positive duration or distance before approving.']);
        if(!$rule->limit_basis||!trim((string)$rule->terms))throw ValidationException::withMessages(['warranty_rule_id'=>'Conditions and the time/distance basis must be confirmed.']);
        if($rule->limit_basis==='time_first' && (int)$rule->duration_months<=0)throw ValidationException::withMessages(['warranty_rule_id'=>'Time-based coverage needs a duration.']);
        if($rule->limit_basis==='distance_first' && (int)$rule->distance_km<=0)throw ValidationException::withMessages(['warranty_rule_id'=>'Distance-based coverage needs a km limit.']);
    }
    public function approveRule(Request $request,int $id)
    {
        app(\App\Services\CcsWarrantyApproval::class)->requireApprover($request);
        $data=$request->validate(['confirmation'=>['required','accepted'],'limit_basis'=>['required','in:time_first,distance_first,whichever_first,custom']]);
        DB::transaction(function()use($request,$id,$data){
            $rule=DB::table('warranty_rules')->where('id',$id)->lockForUpdate()->first();abort_unless($rule,404);
            if($rule->approved_by_owner && $rule->active)return;
            $rule->approved_by_owner=true;$rule->active=true;$rule->limit_basis=$data['limit_basis'];$this->checkRule($rule);
            DB::table('warranty_rules')->where('id',$id)->update(['approved_by_owner'=>true,'active'=>true,'limit_basis'=>$rule->limit_basis,'updated_at'=>now()]);
            $this->audit($request,'warranty_rule_approved','warranty_rule',$id,['limit_basis'=>$rule->limit_basis]);
        });
        return back()->with('success','Rule approved. Existing draft records remain drafts until separately approved.');
    }
    public function approveRecord(Request $request,int $id)
    {
        app(\App\Services\CcsWarrantyApproval::class)->requireApprover($request);
        $data=$request->validate(['confirmation'=>['required','accepted'],'starting_mileage_km'=>['nullable','integer','min:0','max:2000000']]);
        DB::transaction(function()use($request,$id,$data){
            $record=DB::table('warranties')->where('id',$id)->lockForUpdate()->first();abort_unless($record,404);
            $rule=DB::table('warranty_rules')->where('id',$record->warranty_rule_id)->first();abort_unless($rule,404);$this->checkRule($rule);
            $job=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->where('j.id',$record->repair_job_id)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->exists();abort_unless($job,404);
            $terms=json_decode($record->terms_snapshot,true);
            if(!is_array($terms))throw ValidationException::withMessages(['warranty_rule_id'=>'Missing saved terms. Create a new warranty record.']);
            if(($terms['provisional']??true)===false)return;
            foreach(['name','duration_months','distance_km','terms','coverage_kind','free_service_count'] as $field){if((string)($terms[$field]??($field==='coverage_kind'?'standard':($field==='free_service_count'?0:'')))!==(string)$rule->$field)throw ValidationException::withMessages(['warranty_rule_id'=>'Saved terms differ from this rule. Create a new warranty record with the correct terms.']);}
            if(isset($data['starting_mileage_km']) && $record->mileage_limit_km===null){$terms['starting_mileage_km']=(int)$data['starting_mileage_km'];$record->mileage_limit_km=(int)$data['starting_mileage_km']+(int)$rule->distance_km;}
            if((int)$rule->distance_km>0 && $record->mileage_limit_km===null)throw ValidationException::withMessages(['starting_mileage_km'=>'This draft has no starting mileage. Create a new record with mileage.']);
            if(!$record->starts_on || ((int)$rule->duration_months>0 && !$record->expires_on))throw ValidationException::withMessages(['starts_on'=>'Warranty dates are missing. Create a corrected record.']);
            $terms['approval_pending_reason']=null;$terms['provisional']=false;$terms['limit_basis']=$rule->limit_basis;$terms['approved_by']=$request->user('web')->id;$terms['approved_at']=now()->toIso8601String();$terms['notice']='Approved warranty record.';
            DB::table('warranties')->where('id',$id)->update(['terms_snapshot'=>json_encode($terms,JSON_UNESCAPED_UNICODE),'mileage_limit_km'=>$record->mileage_limit_km,'updated_at'=>now()]);
            $this->audit($request,'warranty_record_approved','warranty',$id,['warranty_rule_id'=>$rule->id]);
        });
        return back()->with('success','Warranty record approved and available to the customer.');
    }
    public function recordService(Request $request,int $id)
    {
        $data=$request->validate(['repair_job_id'=>['required','integer','exists:repair_jobs,id'],'service_date'=>['required','date_format:Y-m-d','before_or_equal:today'],'mileage_km'=>['nullable','integer','min:0','max:2000000'],'notes'=>['nullable','string','max:2000'],'confirmation'=>['required','accepted']]);
        DB::transaction(function()use($request,$id,$data){
            $w=DB::table('warranties')->where('id',$id)->lockForUpdate()->first();abort_unless($w,404);
            $rule=DB::table('warranty_rules')->where('id',$w->warranty_rule_id)->first();
            $terms=json_decode($w->terms_snapshot,true)??[];
            if(!$rule||!$rule->approved_by_owner||!$rule->active||($terms['provisional']??true)!==false)throw ValidationException::withMessages(['confirmation'=>'Approve this warranty before recording free services.']);
            $original=DB::table('repair_jobs')->where('id',$w->repair_job_id)->whereNull('deleted_at')->first();abort_unless($original,404);
            $job=DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')->where('j.id',$data['repair_job_id'])->where('j.vehicle_id',$original->vehicle_id)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->first(['j.id']);
            if(!$job)throw ValidationException::withMessages(['repair_job_id'=>'Choose a service job for this same vehicle.']);
            if($data['service_date']<$w->starts_on)throw ValidationException::withMessages(['service_date'=>'Service date cannot be before the warranty start.']);
            if(DB::table('warranty_service_visits')->where('warranty_id',$id)->where('repair_job_id',$job->id)->exists())throw ValidationException::withMessages(['repair_job_id'=>'This job has already used a free service on this warranty.']);
            $used=DB::table('warranty_service_visits')->where('warranty_id',$id)->count();
            if($used>=(int)($terms['free_service_count']??0))throw ValidationException::withMessages(['confirmation'=>'No included free services remain.']);
            DB::table('warranty_service_visits')->insert(['warranty_id'=>$id,'repair_job_id'=>$job->id,'recorded_by'=>$request->user('web')->id,'service_date'=>$data['service_date'],'mileage_km'=>$data['mileage_km']??null,'notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now()]);
            $this->audit($request,'warranty_free_service_recorded','warranty',$id,['repair_job_id'=>$job->id,'service_date'=>$data['service_date']]);
        },3);
        return back()->with('success','Free service recorded. Remaining count updated.');
    }
    private function audit(Request $request,string $action,string $entity,int $id,array $meta): void
    {
        DB::table('audit_logs')->insert(['user_id'=>$request->user('web')->id,'action'=>$action,'entity_type'=>$entity,'entity_id'=>$id,'metadata'=>json_encode($meta),'created_at'=>now(),'updated_at'=>now()]);
    }
}
