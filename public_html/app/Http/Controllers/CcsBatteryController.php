<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CcsBatteryController extends Controller
{
    private function job(int $id)
    {
        $job = DB::table('repair_jobs as j')->join('vehicles as v','v.id','=','j.vehicle_id')
            ->join('customers as c','c.id','=','v.customer_id')->where('j.id',$id)->whereNull('j.deleted_at')->whereNull('v.deleted_at')->whereNull('c.deleted_at')
            ->select('j.*','v.registration_number','v.make','v.model','c.id as customer_id','c.name as customer_name','c.phone')->first();
        abort_unless($job,404);
        return $job;
    }

    public function show(Request $request, int $id)
    {
        $job = $this->job($id);
        $history = DB::table('battery_service_records')->where('vehicle_id',$job->vehicle_id)->orderByDesc('id')->get();
        $latest = $history->first();
        $request->validate(['record'=>['nullable','integer','min:1']]);
        $viewing = $request->filled('record');
        $record = $viewing ? $history->firstWhere('id',(int)$request->query('record')) : $latest;
        abort_if($viewing && !$record,404);
        $readings = $record ? DB::table('battery_module_readings')->where('battery_service_record_id',$record->id)->orderBy('position')->get()->keyBy('position') : collect();
        $recordJob = $record ? $this->job($record->repair_job_id) : $job;
        $documentJob=$viewing?$recordJob:$job;
        $documentRecord=$viewing?$record:$history->firstWhere('repair_job_id',$job->id);
        $documentRows=app(\App\Services\CcsServicePartsDocument::class)->rows((int)$documentJob->id,$documentRecord,$viewing);
        return view('ccs.battery-job-card',compact('job','recordJob','history','latest','viewing','record','readings','documentRows','documentRecord'));
    }

    public function store(Request $request, int $id)
    {
        $job = $this->job($id);
        $data = $request->validate([
            'request_token'=>['required','uuid'], 'base_record_id'=>['nullable','integer','min:1'],
            'service_date'=>['required','date_format:Y-m-d','before_or_equal:today'],
            'mileage_km'=>['nullable','integer','min:0','max:999999999'],
            'module_count'=>['required','integer','min:1','max:100'],
            'notes'=>['nullable','string','max:5000'],
            'modules'=>['required','array','min:1','max:100'],
            'modules.*.position'=>['required','integer','min:1','max:100','distinct'],
            'modules.*.serial_number'=>['nullable','string','max:100'],
            'modules.*.voltage'=>['nullable','numeric','min:0','max:99999.999'],
            'modules.*.notes'=>['nullable','string','max:250'],
        ]);
        $rows=[]; $seen=[];
        foreach ($data['modules'] as $index=>$module) {
            $serial=trim((string)($module['serial_number']??''));
            $notes=trim((string)($module['notes']??''));
            $voltage=$module['voltage']??null;
            if ($module['position']>$data['module_count']) {
                throw ValidationException::withMessages(['modules'=>'A position exceeds the module count.']);
            }
            if ($serial==='' && $notes==='' && $voltage===null) continue;
            if ($serial==='') throw ValidationException::withMessages(["modules.$index.serial_number"=>'Enter the serial number for every module with a reading or note.']);
            $key=mb_strtoupper($serial);
            if (isset($seen[$key])) throw ValidationException::withMessages(['modules'=>'The same serial number cannot be entered twice in one service record.']);
            $seen[$key]=true;
            $rows[]=['position'=>$module['position'],'serial_number'=>$serial,'voltage'=>$voltage,'notes'=>$notes?:null];
        }
        if (!$rows) throw ValidationException::withMessages(['modules'=>'Enter at least one module serial number.']);
        $recordId=DB::transaction(function () use ($request,$job,$data,$rows) {
            // Serialize records for this vehicle, including visits on different jobs.
            DB::table('vehicles')->where('id',$job->vehicle_id)->lockForUpdate()->first();
            $duplicate=DB::table('battery_service_records')->where('request_token',$data['request_token'])->first();
            if ($duplicate) {
                abort_unless((int)$duplicate->repair_job_id===(int)$job->id,409);
                return $duplicate->id;
            }
            $latest=DB::table('battery_service_records')->where('vehicle_id',$job->vehicle_id)->orderByDesc('id')->first();
            if ((int)($latest->id??0)!==(int)($data['base_record_id']??0)) {
                throw ValidationException::withMessages(['modules'=>'Another service record was saved. Reopen the job card to load the latest serial numbers.']);
            }
            if ($latest && $data['service_date']<$latest->service_date) {
                throw ValidationException::withMessages(['service_date'=>'Service date cannot precede the latest saved service.']);
            }
            if ($latest && $latest->mileage_km!==null && isset($data['mileage_km']) && $data['mileage_km']<$latest->mileage_km) {
                throw ValidationException::withMessages(['mileage_km'=>'Mileage cannot be lower than the previous service reading.']);
            }
            $recordId=DB::table('battery_service_records')->insertGetId([
                'repair_job_id'=>$job->id,'vehicle_id'=>$job->vehicle_id,'recorded_by'=>$request->user()->id,
                'request_token'=>$data['request_token'],'service_date'=>$data['service_date'],
                'mileage_km'=>$data['mileage_km']??null,'module_count'=>$data['module_count'],
                'notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now(),
            ]);
            foreach ($rows as $row) DB::table('battery_module_readings')->insert(array_merge($row,['battery_service_record_id'=>$recordId]));
            DB::table('audit_logs')->insert([
                'user_id'=>$request->user()->id,'action'=>'battery_service_recorded','entity_type'=>'battery_service_record',
                'entity_id'=>$recordId,'metadata'=>json_encode(['job_id'=>$job->id,'module_count'=>count($rows)]),
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            return $recordId;
        },3);
        return redirect()->route('ccs.jobs.battery', ['id'=>$id,'record'=>$recordId])->with('success','Service record saved. Print the job card or start the next service.');
    }
}
