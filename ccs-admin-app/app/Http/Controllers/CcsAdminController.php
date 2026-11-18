<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CcsAdminController extends Controller
{
    private const STATUSES = ['new_enquiry','vehicle_received','vehicle_for_assessment','inspection_booked','repair_in_progress','awaiting_parts','awaiting_approval','testing','ready','delivered','completed','closed'];

    public function loginForm(Request $request) { if(!$request->cookie('ccs_setup_done'))return redirect()->route('ccs.setup');return response()->view('ccs.login')->header('Cache-Control','no-store, private'); }
    public function setup(Request $request) { if($request->query('step')==='install' && $request->cookie('ccs_setup_done'))return redirect()->route('ccs.login');return response()->view('ccs.first-setup',['step'=>$request->query('step')==='install'?'install':'language'])->header('Cache-Control','no-store, private')->header('X-Robots-Tag','noindex, nofollow'); }
    public function setupLanguage(Request $request) {
        $data=$request->validate(['language'=>['required',\Illuminate\Validation\Rule::in(array_keys(config('ccs_admin.locales')))]]);
        $request->session()->put('ccs_language',$data['language']);
        return redirect()->route('ccs.setup',['step'=>'install'])->cookie('ccs_language',$data['language'],525600,null,null,$request->isSecure(),true)->withoutCookie('ccs_setup_done');
    }
    public function setupComplete(Request $request) { return redirect()->route('ccs.login')->cookie('ccs_setup_done','1',525600,null,null,$request->isSecure(),true); }

    public function login(Request $request)
    {
        $data = $request->validate(['email'=>'required|email','password'=>'required|string']);
        if (!Auth::attempt($data)){app(\App\Services\CcsAdministration::class)->audit(null,'staff_login_failed','session');throw ValidationException::withMessages(['email'=>'Incorrect login details.']);}
        if(!Auth::user()->ccs_active){Auth::logout();throw ValidationException::withMessages(['email'=>'This staff account is disabled.']);}
        $request->session()->regenerate();$request->session()->put('ccs_restore_epoch',app(\App\Services\CcsSqliteBackup::class)->epoch());$request->session()->put('ccs_staff_version',(int)Auth::user()->ccs_session_version);$request->session()->put('ccs_last_activity',time());
        app(\App\Services\CcsAdministration::class)->audit(Auth::id(),'staff_login','user',Auth::id());
        return redirect()->to(url(!empty(app(\App\Services\CcsAdministration::class)->preferences(Auth::id())['touch_mode'])?'/t/dashboard':'/ccs'));
    }

    public function logout(Request $request)
    {
        if(Auth::id())app(\App\Services\CcsAdministration::class)->audit(Auth::id(),'staff_logout','user',Auth::id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('ccs.login');
    }

    public function dashboard(Request $request)
    {
        if(app(\App\Services\CcsAdministration::class)->role($request->user())==='technician')return app(CcsTechnicianController::class)->jobs($request);
        $stats = ['customers'=>DB::table('customers')->where('is_system_walkin',false)->whereNull('deleted_at')->count(),
                  'vehicles'=>DB::table('vehicles')->where('is_system_walkin',false)->whereNull('deleted_at')->count(),
                  'jobs'=>DB::table('repair_jobs')->where('is_system_walkin',false)->whereNull('deleted_at')->count(),
                  'invoices'=>DB::table('invoices')->count()];
        $recent = DB::table('repair_jobs as j')->where('j.is_system_walkin',false)->join('vehicles as v','v.id','=','j.vehicle_id')
            ->join('customers as c','c.id','=','v.customer_id')->whereNull('j.deleted_at')
            ->select('j.*','v.registration_number','c.name as customer_name')->orderByDesc('j.id')->limit(8)->get();
        $admin=app(\App\Services\CcsAdministration::class);$canStock=$admin->can($request->user(),'ccs.stock');
        $stockSummary=$canStock?DB::table('inventory_items')->where('active',true)->selectRaw('COUNT(*) as total, SUM(CASE WHEN quantity_on_hand > 0 AND quantity_on_hand <= reorder_level THEN 1 ELSE 0 END) as low, SUM(CASE WHEN quantity_on_hand <= 0 THEN 1 ELSE 0 END) as empty')->first():null;
        $notifications=collect(app(\App\Services\CcsStaffNotifications::class)->items($request->user()))->where('read',false)->values();
        return view('ccs.dashboard',compact('stats','recent','stockSummary','notifications','canStock'));
    }

    public function updateCustomer(Request $request,int $id)
    {
        $data=$request->validateWithBag('customerEdit',[
            'email'=>['nullable','email:rfc','max:254'],'name'=>['required','string','max:150'], 'phone'=>['nullable','string','max:30'],
            'whatsapp'=>['nullable','string','max:30'], 'nic'=>['nullable','string','max:20'],
            'address'=>['nullable','string','max:500'], 'notes'=>['nullable','string','max:2000'],
        ]);
        $data['name']=trim($data['name']);if($request->attributes->has('rules_nic_key'))$data['rules_key']=$request->attributes->get('rules_nic_key');
        if($data['name']==='')throw \Illuminate\Validation\ValidationException::withMessages(['name'=>__('Customer name is required.')])->errorBag('customerEdit');
        DB::transaction(function()use($request,$id,$data){
            $customer=DB::table('customers')->where('id',$id)->whereNull('deleted_at')->lockForUpdate()->first();abort_unless($customer,404);
            DB::table('customers')->where('id',$id)->update(array_merge($data,['updated_at'=>now()]));
            DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'customer_updated','entity_type'=>'customer','entity_id'=>$id,'metadata'=>json_encode(['fields'=>array_keys($data)]),'created_at'=>now(),'updated_at'=>now()]);
        });
        return redirect()->route('ccs.customers.show',$id)->with('success',__('Customer details updated.'));
    }
    public function customers(Request $request)
    {
        $search=trim((string)$request->query('q',''));
        $query=DB::table('customers as c')->where('c.is_system_walkin',false)->whereNull('c.deleted_at')
            ->select('c.*')->orderByDesc('c.id');
        if ($search !== '') {
            $like='%'.addcslashes($search,'%_\\').'%';
            $query->where(function($q)use($like){
                $q->where('c.name','like',$like)->orWhere('c.customer_code','like',$like)
                  ->orWhere('c.phone','like',$like)->orWhereExists(function($sub)use($like){
                      $sub->selectRaw('1')->from('vehicles as v')->whereColumn('v.customer_id','c.id')->whereNull('v.deleted_at')->where('v.registration_number','like',$like);
                  });
            });
        }
        $customers=$query->paginate(15)->withQueryString();
        return view('ccs.customers',compact('customers','search'));
    }

    private function vehicleFields(Request $request,bool $required): array
    {
        return array_merge($request->validate(['registration_number'=>[$required?'required':'nullable','string','max:30'],'make'=>[$required?'required':'nullable','string','max:80'],'model'=>[$required?'required':'nullable','string','max:100'],'generation'=>'nullable|string|max:40','manufacture_year'=>'nullable|integer|between:1950,2100','colour'=>'nullable|string|max:40','current_mileage_km'=>'nullable|integer|min:0|max:2000000']),app(\App\Services\CcsHumanRules::class)->vehicleExtras($request));
    }
    private function createRegisteredVehicle(int $customerId,array $data): int
    {
        $plate=trim($data['registration_number']);$key=mb_strtoupper(str_replace([' ','-'],'',$plate));
        if($key==='')throw ValidationException::withMessages(['registration_number'=>'Enter a valid number plate.']);
        if(DB::table('vehicles')->whereNull('deleted_at')->whereRaw("UPPER(REPLACE(REPLACE(registration_number, ' ', ''), '-', '')) = ?",[$key])->exists())throw ValidationException::withMessages(['registration_number'=>'This number plate is already registered. Open its existing customer/vehicle record.']);
        $id=DB::table('vehicles')->insertGetId(array_merge($data,['customer_id'=>$customerId,'registration_number'=>$plate,'vehicle_code'=>'PENDING-'.Str::uuid(),'created_at'=>now(),'updated_at'=>now()]));
        DB::table('vehicles')->where('id',$id)->update(['vehicle_code'=>sprintf('CCS-V%06d',$id)]);return $id;
    }
    public function saveCustomer(Request $request)
    {
        $data=$request->validate(['email'=>'nullable|email:rfc|max:254','name'=>'required|string|max:150','phone'=>'required|string|max:30','nic'=>'nullable|string|max:20','address'=>'nullable|string|max:500','whatsapp'=>'nullable|string|max:30','notes'=>'nullable|string|max:2000','include_vehicle'=>'nullable|boolean','next_action'=>'nullable|in:profile,job']);
        $data['rules_key']=$request->attributes->get('rules_nic_key');$include=$request->boolean('include_vehicle')||$request->filled('registration_number');$vehicle=$this->vehicleFields($request,$include);
        if($request->input('next_action')==='job' && !$include)throw ValidationException::withMessages(['registration_number'=>'Add a vehicle to continue to a repair job.']);
        $result=DB::transaction(function()use($data,$vehicle,$include,$request){
            unset($data['include_vehicle'],$data['next_action']);$id=DB::table('customers')->insertGetId(array_merge($data,['customer_code'=>'PENDING-'.Str::uuid(),'created_at'=>now(),'updated_at'=>now()]));
            DB::table('customers')->where('id',$id)->update(['customer_code'=>sprintf('CCS-C%06d',$id)]);$vehicleId=$include?$this->createRegisteredVehicle($id,$vehicle):null;
            DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'customer_registered','entity_type'=>'customer','entity_id'=>$id,'metadata'=>json_encode(['vehicle_id'=>$vehicleId]),'created_at'=>now(),'updated_at'=>now()]);return ['customer_id'=>$id,'vehicle_id'=>$vehicleId];
        });
        if($request->input('next_action')==='job')return redirect()->route('ccs.jobs',['new'=>1,'vehicle_id'=>$result['vehicle_id']])->with('success','Customer and vehicle registered. Add the repair details.');
        return redirect()->route('ccs.customers.show',$result['customer_id'])->with('success',$include?'Customer and vehicle registered.':'Customer registered.');
    }
    public function customerShow(Request $request,int $id)
    {
        $request->validate(['vehicle_id'=>['nullable','integer','min:1']]);$customer=DB::table('customers')->where('is_system_walkin',false)->whereNull('deleted_at')->find($id);abort_unless($customer,404);
        $vehicles=DB::table('vehicles')->where('customer_id',$id)->whereNull('deleted_at')->orderByDesc('id')->get();$selectedVehicle=$request->filled('vehicle_id')?$vehicles->firstWhere('id',(int)$request->query('vehicle_id')):null;if($request->filled('vehicle_id'))abort_unless($selectedVehicle,404);
        $query=DB::table('repair_jobs as j')->where('j.is_system_walkin',false)->join('vehicles as v','v.id','=','j.vehicle_id')->where('v.customer_id',$id)->whereNull('v.deleted_at')->whereNull('j.deleted_at')->select('j.*','v.registration_number','v.make','v.model');
        if($selectedVehicle)$query->where('v.id',$selectedVehicle->id);$jobs=$query->orderByDesc('j.id')->paginate(15)->withQueryString();
        $invoices=DB::table('invoices')->whereIn('repair_job_id',$jobs->pluck('id'))->orderByDesc('id')->get()->groupBy('repair_job_id');$statusLabels=$this->jobStatusLabels();
        return view('ccs.customer-show',compact('customer','vehicles','jobs','invoices','selectedVehicle','statusLabels'));
    }
    public function saveVehicle(Request $request)
    {
        $request->validate(['customer_id'=>['required','integer',\Illuminate\Validation\Rule::exists('customers','id')->whereNull('deleted_at')],'next_action'=>'nullable|in:profile,job']);$data=$this->vehicleFields($request,true);
        $id=DB::transaction(function()use($request,$data){$owner=DB::table('customers')->where('id',$request->input('customer_id'))->whereNull('deleted_at')->lockForUpdate()->first();abort_unless($owner,404);$id=$this->createRegisteredVehicle($owner->id,$data);DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'vehicle_registered','entity_type'=>'vehicle','entity_id'=>$id,'metadata'=>json_encode(['customer_id'=>$owner->id]),'created_at'=>now(),'updated_at'=>now()]);return $id;});
        if($request->input('next_action')==='job')return redirect()->route('ccs.jobs',['new'=>1,'vehicle_id'=>$id])->with('success','Vehicle registered. Add the repair details.');
        return redirect()->route('ccs.customers.show',['id'=>$request->input('customer_id'),'vehicle_id'=>$id])->with('success','Vehicle registered.');
    }

    private function jobQuery()
    {
        return DB::table('repair_jobs as j')->where('j.is_system_walkin',false)->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')
            ->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')
            ->select('j.*','v.registration_number','v.make','v.model','c.name as customer_name','c.phone','c.id as customer_id')
            ->selectSub(function($q){$q->from('battery_service_records as b')->selectRaw('COUNT(*)')->whereColumn('b.repair_job_id','j.id');},'battery_record_count');
    }
    private function jobStatusLabels(): array
    {
        return ['new_enquiry'=>'New Enquiry','vehicle_received'=>'Received','vehicle_for_assessment'=>'For Assessment','inspection_booked'=>'Inspection Booked','repair_in_progress'=>'In Progress','awaiting_parts'=>'Awaiting Parts','awaiting_approval'=>'Awaiting Approval','testing'=>'Testing','ready'=>'Ready','delivered'=>'Delivered','completed'=>'Completed','closed'=>'Closed'];
    }
    public function jobs(Request $request)
    {
        if(app(\App\Services\CcsAdministration::class)->role($request->user())==='technician')return app(CcsTechnicianController::class)->jobs($request);
        $request->validate(['q'=>['nullable','string','max:150'],'category'=>['nullable','in:hybrid_battery,abs_brakes,diagnostics,parts_other'],'job_status'=>['nullable','in:'.implode(',',self::STATUSES)],'customer_id'=>['nullable','integer','exists:customers,id'],'job_id'=>['nullable','integer','min:1'],'stage'=>['nullable','in:received,in_progress,awaiting_parts,awaiting_approval,ready,delivered'],'vehicle_id'=>['nullable','integer','min:1']]);
        $search=trim((string)$request->query('q',''));$category=(string)$request->query('category','');$jobStatus=(string)$request->query('job_status','');$stage=(string)$request->query('stage','');$selectedCustomerId=$request->filled('customer_id')?(int)$request->query('customer_id'):null;
        $customers=DB::table('customers')->where('is_system_walkin',false)->whereNull('deleted_at')->orderBy('name')->get(['id','name','phone','customer_code']);
        $vehicles=DB::table('vehicles as v')->join('customers as c','c.id','=','v.customer_id')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->where('v.id',(int)old('vehicle_id',$request->query('vehicle_id',0)))->select('v.*','c.name as customer_name','c.customer_code','c.phone')->get();
        $query=$this->jobQuery();
        if($search!=='')$query->where(function($q)use($search){foreach(['j.job_number','c.name','c.customer_code','c.phone','c.nic','v.registration_number','v.make','v.model','j.service_type','j.complaint'] as $i=>$field){if($i===0)$q->where($field,'like','%'.$search.'%');else $q->orWhere($field,'like','%'.$search.'%');}});
        if($selectedCustomerId)$query->where('c.id',$selectedCustomerId);
        if($category!=='')$query->where('j.job_category',$category);
        if($jobStatus!=='')$query->where('j.status',$jobStatus);
        $groups=['received'=>['new_enquiry','vehicle_received','vehicle_for_assessment','inspection_booked'],'in_progress'=>['repair_in_progress','testing'],'awaiting_parts'=>['awaiting_parts'],'awaiting_approval'=>['awaiting_approval'],'ready'=>['ready','completed'],'delivered'=>['delivered','closed']];
        if($stage!=='')$query->whereIn('j.status',$groups[$stage]);
        $jobs=$query->orderByDesc('j.id')->paginate(15)->withQueryString();
        $selectedId=(int)$request->query('job_id',$jobs->first()->id??0);
        $selectedJob=$selectedId?$this->jobQuery()->where('j.id',$selectedId)->first():null;
        if($request->filled('job_id'))abort_unless($selectedJob,404);
        $ids=$jobs->pluck('id');if($selectedJob)$ids=$ids->push($selectedJob->id)->unique();
        $invoiceSummaries=DB::table('invoices')->whereIn('repair_job_id',$ids)->where('status','!=','void')->orderByDesc('id')->get()->unique('repair_job_id')->keyBy('repair_job_id');
        $receipts=DB::table('payments')->whereIn('invoice_id',$invoiceSummaries->pluck('id'))->get(['invoice_id','amount_lkr'])->groupBy('invoice_id');
        foreach($invoiceSummaries as $invoice){$paid=$receipts->get($invoice->id,collect())->sum(fn($p)=>(int)round((float)$p->amount_lkr*100));$invoice->paid_lkr=$paid/100;$invoice->due_lkr=max(0,(int)round((float)$invoice->total_lkr*100)-$paid)/100;}
        $linkedParts=DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->whereIn('inv.repair_job_id',$ids)->where('inv.status','!=','void')->select('item.description','item.serial_number','item.quantity','item.item_origin','item.stock_managed','inv.repair_job_id','inv.invoice_number','inv.id as invoice_id','inv.status')->get()->groupBy('repair_job_id');
        $technicians=DB::table('users')->where('ccs_active',true)->where('ccs_role','technician')->orderBy('name')->pluck('name');$statuses=self::STATUSES;$statusLabels=$this->jobStatusLabels();
        return view('ccs.jobs',compact('customers','vehicles','jobs','selectedJob','statuses','statusLabels','search','category','jobStatus','stage','selectedCustomerId','linkedParts','invoiceSummaries','technicians'));
    }
    private function jobFields(Request $request): array
    {
        return $request->validate(['complaint'=>'required|string|max:5000','diagnosis'=>'nullable|string|max:5000','mileage_at_service_km'=>'nullable|integer|min:0|max:2000000','job_category'=>'nullable|in:hybrid_battery,abs_brakes,diagnostics,parts_other','service_type'=>'nullable|string|max:120','fault_codes'=>'nullable|string|max:1000','recommended_work'=>'nullable|string|max:5000','approved_work'=>'nullable|string|max:5000','technician_name'=>'nullable|string|max:150','parts_notes'=>'nullable|string|max:3000','estimated_minutes'=>'nullable|integer|min:0|max:100000','next_service_date'=>'nullable|date_format:Y-m-d','next_service_km'=>'nullable|integer|min:0|max:2000000','warranty_notes'=>'nullable|string|max:3000']);
    }
    private function technicianId(?string $name): ?int
    {
        if(!trim((string)$name))return null;$matches=DB::table('users')->where('name',trim($name))->where('ccs_active',true)->where('ccs_role','technician')->limit(2)->pluck('id');return $matches->count()===1?(int)$matches->first():null;
    }
    public function saveJob(Request $request)
    {
        $request->validate(['vehicle_id'=>['required','integer','exists:vehicles,id'],'next_action'=>['nullable','in:jobs,pos']]);$data=$this->jobFields($request);
        $data['job_category']=$data['job_category']??'parts_other';$data['service_type']=$data['service_type']??'Repair / service';$data['technician_name']=isset($data['technician_name'])?trim($data['technician_name']):null;
        $id=DB::transaction(function()use($request,$data){
            $vehicle=DB::table('vehicles as v')->join('customers as c','c.id','=','v.customer_id')->where('v.id',$request->input('vehicle_id'))->whereNull('v.deleted_at')->whereNull('c.deleted_at')->first(['v.id']);abort_unless($vehicle,404);
            $id=DB::table('repair_jobs')->insertGetId(array_merge($data,['vehicle_id'=>$vehicle->id,'assigned_user_id'=>$this->technicianId($data['technician_name']),'job_number'=>'PENDING-'.Str::uuid(),'status'=>'vehicle_received','received_at'=>now(),'created_at'=>now(),'updated_at'=>now()]));
            DB::table('repair_jobs')->where('id',$id)->update(['job_number'=>sprintf('CCS-J%06d',$id)]);
            DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'repair_job_created','entity_type'=>'repair_job','entity_id'=>$id,'metadata'=>json_encode(['status'=>'vehicle_received','technician'=>$data['technician_name']??null]),'created_at'=>now(),'updated_at'=>now()]);return $id;
        });
        return redirect()->route($request->input('next_action')==='pos'?'ccs.pos':'ccs.jobs',['job_id'=>$id])->with('success','Repair job created.');
    }
    public function updateJob(Request $request,int $id)
    {
        if(app(\App\Services\CcsAdministration::class)->role($request->user())==='technician')return app(CcsTechnicianController::class)->update($request,$id);
        $data=$request->validate(['status'=>['required','in:'.implode(',',self::STATUSES)],'diagnosis'=>'nullable|string|max:5000','technician_name'=>'nullable|string|max:150']);
        DB::transaction(function()use($request,$id,$data){
            $job=DB::table('repair_jobs')->where('id',$id)->whereNull('deleted_at')->lockForUpdate()->first();abort_unless($job,404);
            abort_unless($this->jobQuery()->where('j.id',$id)->exists(),404);
            $updates=['status'=>$data['status'],'completed_at'=>in_array($data['status'],['ready','delivered','completed','closed'],true)?($job->completed_at??now()):null,'updated_at'=>now()];
            if(array_key_exists('diagnosis',$data))$updates['diagnosis']=$data['diagnosis'];
            if(array_key_exists('technician_name',$data)){$updates['technician_name']=$data['technician_name'];$updates['assigned_user_id']=$this->technicianId($data['technician_name']);}
            DB::table('repair_jobs')->where('id',$id)->update($updates);
            DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'repair_job_status_updated','entity_type'=>'repair_job','entity_id'=>$id,'metadata'=>json_encode(['from'=>$job->status,'to'=>$data['status'],'previous_completed_at'=>$job->completed_at,'completed_at'=>$updates['completed_at'],'previous_technician'=>$job->technician_name??null,'technician'=>array_key_exists('technician_name',$updates)?$updates['technician_name']:$job->technician_name]),'created_at'=>now(),'updated_at'=>now()]);
        });
        return back()->with('success','Repair status and technician updated.');
    }
    public function jobCard(int $id)
    {
        $job=$this->jobQuery()->where('j.id',$id)->first();abort_unless($job,404);
        $technicians=DB::table('users')->where('ccs_active',true)->where('ccs_role','technician')->orderBy('name')->pluck('name');$statusLabels=$this->jobStatusLabels();
        $parts=DB::table('invoice_items as item')->join('invoices as inv','inv.id','=','item.invoice_id')->where('inv.repair_job_id',$id)->where('inv.status','!=','void')->get(['item.description','item.quantity','item.serial_number','inv.invoice_number','inv.status']);
        return view('ccs.job-card',compact('job','technicians','statusLabels','parts'));
    }
    public function updateJobDetails(Request $request,int $id)
    {
        $data=$this->jobFields($request);if(array_key_exists('technician_name',$data))$data['assigned_user_id']=$this->technicianId($data['technician_name']);
        DB::transaction(function()use($request,$id,$data){$job=$this->jobQuery()->where('j.id',$id)->first();abort_unless($job,404);DB::table('repair_jobs')->where('id',$id)->update(array_merge($data,['updated_at'=>now()]));DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'repair_job_details_updated','entity_type'=>'repair_job','entity_id'=>$id,'metadata'=>json_encode(['fields'=>array_keys($data)]),'created_at'=>now(),'updated_at'=>now()]);});
        return back()->with('success','Job card details saved.');
    }

    public function pos(Request $request)
    {
        $request->validate(['job_id'=>['nullable','integer','min:1']]);$preselectedJob=null;
        if($request->filled('job_id')){
            $preselectedJob=$this->jobQuery()->where('j.id',(int)$request->query('job_id'))->first();abort_unless($preselectedJob,404);
            $existing=DB::table('invoices')->where('repair_job_id',$preselectedJob->id)->where('status','!=','void')->orderByDesc('id')->value('id');
            if($existing)return redirect()->route('ccs.invoices.show',$existing)->with('success','This job already has an invoice.');
        }
        $jobs=DB::table('repair_jobs as j')->where('j.is_system_walkin',false)->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')
            ->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')->whereNotExists(function($q){$q->selectRaw('1')->from('invoices')->whereColumn('invoices.repair_job_id','j.id')->where('invoices.status','!=','void');})
            ->select('j.id','j.job_number','j.status','j.service_type','v.registration_number','v.make','v.model','c.customer_code','c.phone','c.name as customer_name')->orderByDesc('j.id')->limit(50)->get();
        
        $invoices=DB::table('invoices as i')->join('repair_jobs as j','j.id','=','i.repair_job_id')->select('i.*','j.job_number')->orderByDesc('i.id')->limit(50)->get();
        
        $rules=DB::table('warranty_rules')->orderByDesc('id')->get();

        $dueInvoices = DB::table('invoices as i')
            ->join('repair_jobs as j', 'j.id', '=', 'i.repair_job_id')
            ->join('vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->select('i.*', 'c.name as customer_name', 'v.registration_number')
            ->where('i.status','!=','void')->orderByDesc('i.updated_at')
            ->limit(300)
            ->get();
            
        // Load all receipt/item rows in two queries rather than two queries per invoice.
        $dueInvoiceIds=$dueInvoices->pluck('id');
        $receiptGroups=DB::table('payments')->whereIn('invoice_id',$dueInvoiceIds)->orderByDesc('paid_at')->orderByDesc('id')->get()->groupBy('invoice_id');
        $itemGroups=DB::table('invoice_items')->whereIn('invoice_id',$dueInvoiceIds)->orderBy('id')->get(['invoice_id','description','quantity','line_total_lkr'])->groupBy('invoice_id');
        foreach($dueInvoices as $di) {
            $payments=$receiptGroups->get($di->id,collect());
            $paidCents=$payments->sum(fn($payment)=>(int)round((float)$payment->amount_lkr*100));
            $di->balance=max(0,(int)round((float)$di->total_lkr*100)-$paidCents)/100;
            $di->payments_history=$payments->map(function($payment){return ['date'=>\Illuminate\Support\Carbon::parse($payment->paid_at)->format('Y-m-d'),'amount'=>$payment->amount_lkr,'method'=>$payment->method];})->values()->toArray();
            $di->items=$itemGroups->get($di->id,collect())->map(fn($item)=>(object)['description'=>$item->description,'quantity'=>$item->quantity,'line_total_lkr'=>$item->line_total_lkr]);
            $lastPayment=$payments->first();$di->last_paid_date=$lastPayment?\Illuminate\Support\Carbon::parse($lastPayment->paid_at)->format('Y-m-d'):'No payments yet';
        }

        if($preselectedJob && !\App\Services\CcsJobBillingGate::allowed($preselectedJob->status)){ $request->session()->flash('success',__('Save the repair status as Ready or Completed before billing this job.')); $preselectedJob=null; }
        if($preselectedJob && !$jobs->contains('id',$preselectedJob->id))$jobs->prepend($preselectedJob);
        $stockEnabled=app(\App\Services\CcsStockService::class)->enabled();
        return view('ccs.pos',compact('jobs','invoices','rules','dueInvoices','stockEnabled','preselectedJob'));
    }

    public function posJobSearch(Request $request){
        $request->validate(['q'=>['nullable','string','max:150']]);$q=trim((string)$request->query('q',''));
        $query=DB::table('repair_jobs as j')->where('j.is_system_walkin',false)->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')
            ->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')
            ->whereNotExists(function($x){$x->selectRaw('1')->from('invoices as i')->whereColumn('i.repair_job_id','j.id')->where('i.status','!=','void');});
        if($q!==''){
            $exact=clone $query;
            $exact->where(function($x)use($q){
                $x->whereRaw('LOWER(c.customer_code) = ?', [strtolower($q)])
                    ->orWhereRaw('LOWER(j.job_number) = ?', [strtolower($q)])
                    ->orWhereRaw("UPPER(REPLACE(REPLACE(v.registration_number, ' ', ''), '-', '')) = ?", [strtoupper(str_replace([' ','-'],'',$q))]);
                $digits=preg_replace('/\D/','',$q);
                if($digits!=='' && preg_match('/^[+0-9 ()-]+$/',$q)){
                    $phones=[$digits];if(str_starts_with($digits,'94'))$phones[]='0'.substr($digits,2);if(str_starts_with($digits,'0'))$phones[]='94'.substr($digits,1);
                    $x->orWhereIn("c.phone",$phones)->orWhereIn(DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')"),$phones);
                }
            });
            if($exact->exists())$query=$exact;
            else $query->where(function($x)use($q){foreach(['c.name','c.customer_code','c.phone','v.registration_number','v.make','v.model','j.job_number'] as $n=>$field){if($n===0)$x->where($field,'like','%'.$q.'%');else $x->orWhere($field,'like','%'.$q.'%');}});
        }
        return response()->json(['data'=>$query->orderByDesc('j.id')->limit(30)->get(['j.id','j.job_number','j.status','j.service_type','j.created_at','v.id as vehicle_id','v.registration_number','v.make','v.model','c.id as customer_id','c.customer_code','c.name as customer_name','c.phone'])])->header('Cache-Control','no-store, private');
    }

    public function createInvoice(Request $request)
    {
        if($request->filled('initial_request_token')){$prior=DB::table('invoices')->where('rules_request_token',$request->input('initial_request_token'))->first();if($prior){$hash=app(\App\Services\CcsInvoiceInput::class)->hash($request);if(!hash_equals((string)$prior->rules_payload_hash,$hash))throw ValidationException::withMessages(['rules'=>'Invoice request token already used for different details.']);return redirect()->route('ccs.invoices.show',$prior->id);}}
        $data=$request->validate([
            'repair_job_id'=>'required|integer|exists:repair_jobs,id',
            'description'=>'required|array|min:1|max:20','description.*'=>'required|string|max:200',
            'quantity'=>'required|array','quantity.*'=>'required|numeric|gt:0|lte:10000',
            'unit_price'=>'required|array','unit_price.*'=>'required|numeric|min:0|lte:99999999',
            'item_year'=>'nullable|array|max:20', 'item_year.*'=>'nullable|string|max:50',
            'item_condition'=>'nullable|array|max:20', 'item_condition.*'=>'nullable|string|max:80',
            'item_supplier'=>'nullable|array|max:20', 'item_supplier.*'=>'nullable|string|max:150',
            'expected_warranty_rule_id'=>'nullable|array|max:20','expected_warranty_rule_id.*'=>'nullable|integer|min:1',
            'inventory_item_id'=>'nullable|array|max:20','inventory_item_id.*'=>'nullable|integer|min:1',
            'stock_unit_id'=>'nullable|array|max:20','stock_unit_id.*'=>'nullable|integer|min:1',
            'module_voltage'=>'nullable|array|max:20','module_voltage.*'=>'nullable|numeric|min:0|max:99999.999','module_position'=>'nullable|array|max:20','module_position.*'=>'nullable|integer|min:1|max:100','requires_serial'=>'nullable|array|max:20','requires_serial.*'=>'nullable|boolean','is_battery_module'=>'nullable|array|max:20','is_battery_module.*'=>'nullable|boolean','manual_serial'=>'nullable|array|max:20','manual_serial.*'=>'nullable|string|max:100',
            'item_origin'=>'nullable|array|max:20','item_origin.*'=>'nullable|in:service,manual_part,customer_supplied',
            'discount'=>'nullable|numeric|min:0',
            'is_credit_sale'=>'nullable|boolean',
            'invoice_locale'=>'nullable|in:en,si,ta,si_LK,ta_LK','initial_received'=>'nullable|numeric|min:0|decimal:0,2|max:9999999999.99',
            'initial_method'=>'nullable|in:cash,card,bank_transfer,other','initial_reference'=>'nullable|string|max:100',
            'initial_request_token'=>'required|uuid',
            'credit_nic'=>'nullable|string|max:20',
            'credit_address'=>'nullable|string|max:500',
            'credit_phone'=>'nullable|string|max:30',
        ]);

        if(count($data['description'])!==count($data['quantity']) || count($data['description'])!==count($data['unit_price']))
            throw ValidationException::withMessages(['description'=>'Line item arrays must be same length.']);
        
        $lines=[]; $subtotal=0;
        foreach($data['description'] as $key=>$desc){
            if(!isset($data['quantity'][$key],$data['unit_price'][$key])) throw ValidationException::withMessages(['description'=>'Missing quantity or price.']);
            $qty=(float)$data['quantity'][$key]; $price=(float)$data['unit_price'][$key];
            $serial=trim((string)($data['manual_serial'][$key]??''));
            $origin=$data['item_origin'][$key]??'service';
            if(empty($data['inventory_item_id'][$key])&&(!empty($data['requires_serial'][$key])||!empty($data['is_battery_module'][$key]))&&($origin==='service'||$qty!=1))throw ValidationException::withMessages(['description'=>'Serialized parts and battery modules must be part lines with quantity 1.']);
            if($origin==='service'&&empty($data['inventory_item_id'][$key])&&$serial!=='')throw ValidationException::withMessages(['description'=>'A service cannot have a part serial number.']);
            if($serial!==''&&$qty!=1)throw ValidationException::withMessages(['description'=>'Add each serialized manual part as a separate item with quantity 1.']);
            $line=round($qty*$price,2); $subtotal+=$line;
            $lines[]=['description'=>$desc,'quantity'=>$qty,'unit_price_lkr'=>$price,'line_total_lkr'=>$line,'item_year'=>$data['item_year'][$key]??null,'item_condition'=>$data['item_condition'][$key]??null,'item_supplier'=>$data['item_supplier'][$key]??null,'module_voltage'=>$data['module_voltage'][$key]??null,'module_position'=>$data['module_position'][$key]??null,'requires_serial'=>!empty($data['requires_serial'][$key])||!empty($data['is_battery_module'][$key]),'is_battery_module'=>!empty($data['is_battery_module'][$key]),'serial_number'=>$serial!==''?$serial:null,'item_origin'=>$origin];
        }
        
        $savedReceipt=DB::table('payments as p')->join('invoices as i','i.id','=','p.invoice_id')->where('p.request_token',$data['initial_request_token'])->where('i.repair_job_id',$data['repair_job_id'])->value('i.id');
        if($savedReceipt)return redirect()->route('ccs.invoices.show',$savedReceipt)->with('success','This invoice and initial payment were already saved.');
        $lines=app(\App\Services\CcsStockService::class)->prepareLines($lines,$data);
        $serialsInBill=[];
        foreach($lines as $line){if(isset($line['serial_number'])&&$line['serial_number']!==''){$serialKey=mb_strtoupper($line['serial_number']);if(isset($serialsInBill[$serialKey]))throw ValidationException::withMessages(['stock'=>'A serial number appears twice in this invoice.']);$serialsInBill[$serialKey]=true;}}
        $subtotal=array_sum(array_column($lines,'line_total_lkr'));
        $discount=round((float)($data['discount']??0),2);
        if($discount>$subtotal)throw ValidationException::withMessages(['discount'=>'Discount cannot exceed subtotal.']);
        
        $initialReceived=(float)($data['initial_received']??0);
        $totalCents=(int)round(($subtotal-$discount)*100);
        if($totalCents===0&&$initialReceived>0)throw ValidationException::withMessages(['initial_received'=>'No payment is needed for a zero-total invoice.']);
        $isCreditSale = $request->boolean('is_credit_sale') || ($initialReceived>0 && (int)round($initialReceived*100)<$totalCents);
        $invoiceStatus = 'draft'; // POS prepares the invoice; finalization is the only payment commit point.
        if(($data['initial_method']??'cash')!=='cash' && (int)round($initialReceived*100)>$totalCents)throw ValidationException::withMessages(['initial_received'=>'This payment exceeds the invoice total.']); 

        $invoiceId=DB::transaction(function()use($data,$lines,$subtotal,$discount,$isCreditSale,$invoiceStatus,$initialReceived,$request){
            DB::table('repair_jobs')->where('id',$data['repair_job_id'])->lockForUpdate()->first();
            
            $prior=DB::table('payments')->where('request_token',$data['initial_request_token'])->first();
            if($prior){
                $priorInvoice=DB::table('invoices')->where('id',$prior->invoice_id)->first();
                if($priorInvoice && (int)$priorInvoice->repair_job_id===(int)$data['repair_job_id'])return $priorInvoice->id;
                throw ValidationException::withMessages(['initial_received'=>'Payment token already used. Refresh the POS page.']);
            }
            app(\App\Services\CcsJobBillingGate::class)->check((int)$data['repair_job_id']);
            if(DB::table('invoices')->where('repair_job_id',$data['repair_job_id'])->where('status','!=','void')->exists())
                throw ValidationException::withMessages(['repair_job_id'=>'This job already has an invoice.']);
            
            if ($isCreditSale) {
                $job = DB::table('repair_jobs')->where('id', $data['repair_job_id'])->first();
                if ($job) {
                    $vehicle = DB::table('vehicles')->where('id', $job->vehicle_id)->first();
                    if ($vehicle) {
                        $updateData = [];
                        if (!empty($data['credit_nic'])) {$updateData['nic'] = $data['credit_nic'];$updateData['rules_key']=app(\App\Services\CcsHumanRules::class)->nicKey($data['credit_nic']);}
                        if (!empty($data['credit_address'])) $updateData['address'] = $data['credit_address'];
                        if (!empty($data['credit_phone'])) $updateData['phone'] = $data['credit_phone'];
                        
                        if (!empty($updateData)) {
                            DB::table('customers')->where('id', $vehicle->customer_id)->update($updateData+['updated_at'=>now()]);
                        }
                    }
                }
            }

            $lines=app(\App\Services\CcsPartRequirements::class)->recordPopup((int)$data['repair_job_id'],$lines,$request->user()->id);
            if($invoiceStatus!=='draft')app(\App\Services\CcsPartRequirements::class)->check((int)$data['repair_job_id'],$lines);
            $id=DB::table('invoices')->insertGetId([
                'rules_discount_authorized_by'=>$request->attributes->get('rules_discount_authorized_by')??(app(\App\Services\CcsHumanRules::class)->owner($request->user())?$request->user()->id:($request->attributes->get('rules_discount_approval')?DB::table('ccs_rule_approvals')->where('id',$request->attributes->get('rules_discount_approval'))->value('decided_by'):null)),'rules_request_token'=>$data['initial_request_token'],'rules_payload_hash'=>$request->attributes->get('rules_walkin_hash')??app(\App\Services\CcsInvoiceInput::class)->hash($request),'invoice_number'=>'PENDING-'.Str::uuid(),'print_locale'=>$data['invoice_locale']??app(\App\Services\CcsAdministration::class)->preferences($request->user()->id)['invoice_locale'],
                'repair_job_id'=>$data['repair_job_id'],
                'status'=>$invoiceStatus,
                'is_credit_sale'=>$isCreditSale, 
                'subtotal_lkr'=>round($subtotal,2),
                'discount_lkr'=>$discount,
                'tax_lkr'=>0,
                'total_lkr'=>round($subtotal-$discount,2),
                'issued_at'=>$invoiceStatus!=='draft' ? now() : null,
                'created_at'=>now(),
                'updated_at'=>now()
            ]);
            DB::table('invoices')->where('id',$id)->update(['invoice_number'=>sprintf('CCS-I%06d',$id)]);
            $lines=app(\App\Services\CcsStockService::class)->reserve(DB::table('invoices')->where('id',$id)->first(),$lines,$request->user()->id);
            foreach($lines as $line){DB::table('invoice_items')->insert(array_merge($line,['invoice_id'=>$id,'created_at'=>now(),'updated_at'=>now()]));}
            app(\App\Services\CcsStockWarrantyService::class)->issueInvoice($id,$request->user()->id);

            return $id;
        });
        
        $request->session()->put('ccs_pos_payment.'.$invoiceId,['amount'=>$initialReceived,'method'=>$data['initial_method']??'cash','reference'=>$data['initial_reference']??'']);
        return redirect()->route('ccs.invoices.show',$invoiceId)->with('success','Draft invoice saved. Review the amount and method, then finalize to record payment.');
    }

    public function invoice(int $id)
    {
        $invoice=DB::table('invoices as i')->join('repair_jobs as j','j.id','=','i.repair_job_id')
            ->join('vehicles as v','v.id','=','j.vehicle_id')->join('customers as c','c.id','=','v.customer_id')
            ->select('i.*','j.job_number','v.registration_number','v.make','v.model','c.name as customer_name','c.phone')->where('i.id',$id)->first();
        abort_unless($invoice,404);
        
        $items=DB::table('invoice_items')->where('invoice_id',$id)->get();
        
        $invoiceWarranties = DB::table('warranties as w')
            ->join('warranty_rules as r','r.id','=','w.warranty_rule_id')->where('w.repair_job_id',$invoice->repair_job_id)
            ->where('r.approved_by_owner',true)->where('r.active',true)->select('w.*')->orderByDesc('w.id')->get()
            ->filter(function($entry){$terms=json_decode($entry->terms_snapshot,true);return is_array($terms)&&($terms['provisional']??true)===false;})
            ->map(function($entry){$terms=json_decode($entry->terms_snapshot,true);$entry->rule_name=$terms['name']??'Warranty';$entry->duration_months=$terms['duration_months']??null;$entry->benefits=app(\App\Services\CcsWarrantyBenefits::class)->summary($entry->id,$terms);return $entry;});
        return view('ccs.invoice',compact('invoice','items','invoiceWarranties'));
    }

    // POS credit sales and customer receipts share the Credit Ledger source.
    private function customerCreditQuery(bool $creditOnly = true)
    {
        return DB::table('invoices as i')
            ->join('repair_jobs as j', 'j.id', '=', 'i.repair_job_id')
            ->join('vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->whereIn('i.status', ['issued', 'partially_paid', 'paid'])
            ->when($creditOnly, function ($query) {
                $query->where(function ($credit) {
                    $credit->where('i.is_credit_sale', true)->orWhereNull('i.is_credit_sale')->orWhereIn('i.status',['issued','partially_paid']);
                });
            })
            ->select('i.*', 'j.job_number', 'c.name as customer_name', 'c.nic',
                'c.phone', 'c.address', 'v.registration_number');
    }

    public function purchases(Request $request)
    {
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $selectedDateStr = $request->query('date') ?: null;
        $dateLabel = $selectedDateStr ?: __('All Dates');
        $invoices = $this->customerCreditQuery(false)->orderByDesc('i.created_at')->orderByDesc('i.id')->get();
        $payments = DB::table('payments')->whereIn('invoice_id', $invoices->pluck('id'))
            ->orderBy('paid_at')->orderBy('id')->get();
        $paymentsByInvoice = $payments->groupBy('invoice_id');
        $itemsByInvoice = DB::table('invoice_items')->whereIn('invoice_id', $invoices->pluck('id'))->orderBy('id')->get()->groupBy('invoice_id');
        $dailySales = [];
        $outstandingDues = [];
        $paymentHistory = [];
        $totalDueCents = 0;
        $salesCents = 0;
        $receiptsCents = 0;
        foreach ($invoices as $invoice) {
            $totalCents = (int) round((float) $invoice->total_lkr * 100);
            $paidCents = 0;
            foreach ($paymentsByInvoice->get($invoice->id, collect()) as $payment) {
                $amountCents = (int) round((float) $payment->amount_lkr * 100);
                $previousCents = max(0, $totalCents - $paidCents);
                $paidCents += $amountCents;
                $paymentDate = substr((string) $payment->paid_at, 0, 10);
                if ($selectedDateStr === null || $paymentDate === $selectedDateStr) {
                    $paymentHistory[] = (object) [
                        'id' => $payment->id, 'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer_name,
                        'paid_at' => $payment->paid_at, 'method' => $payment->method,
                        'reference' => $payment->reference,
                        'amount_lkr' => $amountCents / 100,
                        'previous_balance' => $previousCents / 100,
                        'remaining_balance' => max(0, $totalCents - $paidCents) / 100,
                        'payment_status' => $paidCents >= $totalCents ? 'Paid' : 'Partially Paid',
                    ];
                    $receiptsCents += $amountCents;
                }
            }
            $invoice->book_items = $itemsByInvoice->get($invoice->id, collect());
            $lastPayment = $paymentsByInvoice->get($invoice->id, collect())->last();
            $invoice->last_payment_date = $lastPayment ? substr((string) $lastPayment->paid_at, 0, 10) : null;
            $invoice->paid_amount = $paidCents / 100;
            $invoice->balance = max(0, $totalCents - $paidCents) / 100;
            $invoice->sale_date = substr((string) ($invoice->issued_at ?: $invoice->created_at), 0, 10);
            if ($selectedDateStr === null || $invoice->sale_date === $selectedDateStr) {
                $dailySales[] = $invoice;
                $salesCents += $totalCents;
            }
            if ($invoice->balance > 0) {
                $outstandingDues[] = $invoice;
                $totalDueCents += max(0, $totalCents - $paidCents);
            }
        }
        usort($paymentHistory, fn ($a, $b) => strcmp($b->paid_at, $a->paid_at) ?: ($b->id <=> $a->id));
        return view('ccs.purchases', [
            'selectedDateStr' => $selectedDateStr, 'dateLabel' => $dateLabel,
            'dailySales' => $dailySales, 'outstandingDues' => $outstandingDues,
            'paymentHistory' => $paymentHistory, 'totalOutstandingDue' => $totalDueCents / 100,
            'salesTotal' => $salesCents / 100, 'paymentsTotal' => $receiptsCents / 100,
        ]);
    }

    public function updateInvoiceItemDetails(Request $request, int $id)
    {
        $data = $request->validate([
            'item_year' => ['nullable', 'string', 'max:50'],
            'item_condition' => ['nullable', 'string', 'max:80'],
            'item_supplier' => ['nullable', 'string', 'max:150'],
        ]);
        DB::transaction(function () use ($id, $data, $request) {
            $item = DB::table('invoice_items')->where('id', $id)->lockForUpdate()->first();
            abort_unless($item, 404);
            DB::table('invoice_items')->where('id', $id)->update([
                'item_year' => $data['item_year'] ?? null,
                'item_condition' => $data['item_condition'] ?? null,
                'item_supplier' => $data['item_supplier'] ?? null,
                'updated_at' => now(),
            ]);
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id, 'action' => 'invoice_item_details_updated',
                'entity_type' => 'invoice_item', 'entity_id' => $id,
                'metadata' => json_encode(['invoice_id' => $item->invoice_id,
                    'before' => ['item_year' => $item->item_year, 'item_condition' => $item->item_condition, 'item_supplier' => $item->item_supplier],
                    'after' => $data]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return back()->with('success', 'Year, condition and supplier details saved.');
    }

    // --- CREDIT LEDGER MANAGEMENT FUNCTIONS ---
    public function creditLedgerView(Request $request)
    {
        $data=$request->validate(['q'=>['nullable','string','max:150']]);
        $search=trim((string)($data['q']??''));
        $query=$this->customerCreditQuery();
        if($search!=='')$query->where(function($q)use($search){foreach(['i.invoice_number','j.job_number','c.name','c.customer_code','c.nic','c.phone','v.registration_number'] as $i=>$field){if($i===0)$q->where($field,'like','%'.$search.'%');else $q->orWhere($field,'like','%'.$search.'%');}});
        $creditInvoices = $query->orderByDesc('i.created_at')->get();

        $grandTotalDue = 0;

        foreach($creditInvoices as $ci) {
            $paid = DB::table('payments')->where('invoice_id', $ci->id)->sum('amount_lkr');
            $ci->paid_amount = $paid;
            $ci->balance = max(0, $ci->total_lkr - $paid);
            
            if($ci->balance > 0) {
                $grandTotalDue += $ci->balance;
            }
        }

        return view('ccs.credit-ledger-management', compact('creditInvoices', 'grandTotalDue','search'));
    }

    public function searchCustomerByNic(Request $request)
    {
        $nic = trim($request->query('nic'));
        $customer = DB::table('customers')->where('nic', $nic)->whereNull('deleted_at')->first();
        
        if (!$customer) {
            return response()->json(['found' => false]);
        }

        $invoices = $this->customerCreditQuery()->where('c.id', $customer->id)->get();

        $totalOrig = 0;
        $totalPaid = 0;
        $totalBal = 0;
        $firstInvId = null;

        foreach($invoices as $inv) {
            $paid = DB::table('payments')->where('invoice_id', $inv->id)->sum('amount_lkr');
            $totalOrig += $inv->total_lkr;
            $totalPaid += $paid;
            $bal = max(0, $inv->total_lkr - $paid);
            $totalBal += $bal;
            if ($bal > 0 && !$firstInvId) {
                $firstInvId = $inv->id;
            }
        }

        if (!$firstInvId && count($invoices) > 0) {
            $firstInvId = $invoices[0]->id;
        }

        return response()->json([
            'found' => true,
            'customer' => $customer,
            'total_credit' => $totalOrig,
            'total_paid' => $totalPaid,
            'balance' => $totalBal,
            'invoice_id' => $firstInvId
        ]);
    }
}
