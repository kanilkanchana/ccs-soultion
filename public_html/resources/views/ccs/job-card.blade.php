@extends('ccs.layout')
@section('title',__('Repair Job Card'))
@section('content')
<!-- CCS_CARE_V3_JOB --><div class="no-print">@include('ccs.partials.care-icon',['careKind'=>'status','careId'=>$job->id])</div>
<style>.job-card-page{max-width:1150px;margin:auto}.job-card-head{display:flex;justify-content:space-between;gap:14px;align-items:center;flex-wrap:wrap;margin-bottom:20px}.job-card-fields{display:grid;grid-template-columns:1fr 1fr;gap:16px}.job-card-fields textarea{min-height:100px;resize:vertical}.job-card-fields .wide{grid-column:1/-1}.job-card-fields label{margin-top:0}.job-card-print{display:none}.job-card-info{display:flex;gap:18px;flex-wrap:wrap;padding:16px;background:#17332e;border-radius:10px;margin-bottom:20px}.job-card-info small{display:block;margin-top:5px}.job-card-save{display:flex;justify-content:flex-end;margin-top:18px}@media(max-width:600px){.job-card-fields{grid-template-columns:1fr}.job-card-fields .wide{grid-column:auto}}@media print{.job-card-screen{display:none!important}.job-card-print{display:block}.job-card-print p{white-space:pre-wrap}.job-card-print table{font-size:12px}.job-card-print th,.job-card-print td{white-space:normal;color:black}.job-card-head .actions{display:none}.job-card-page .card{border:0;padding:0}.job-card-head h1{color:black}}
</style>
<div class="job-card-page"><div class="job-card-head"><div><h1>{{ __('Repair Job Card') }}</h1><small>{{ $job->job_number }} · {{ __($statusLabels[$job->status]??$job->status) }}</small></div><div class="actions no-print"><a class="btn secondary" href="{{ route('ccs.jobs',['job_id'=>$job->id]) }}">← {{ __('Job Cards') }}</a><a class="btn secondary" href="{{ route('ccs.pos',['job_id'=>$job->id]) }}">{{ __('Open POS / Invoice') }}</a><button type="button" onclick="window.print()">{{ __('Print / PDF') }}</button></div></div>
<section class="card job-card-screen"><div class="job-card-info"><div><strong>{{ $job->customer_name }}</strong><small>{{ $job->phone }}</small></div><div><strong>{{ $job->registration_number }}</strong><small>{{ $job->make }} {{ $job->model }}</small></div><div><strong>{{ __('Received') }}</strong><small>{{ $job->received_at }}</small></div></div>
<form method="POST" action="{{ route('ccs.jobs.card.update',$job->id) }}">
@csrf
<div class="job-card-fields"><div><label>{{ __('Category') }}</label><select name="job_category">
@foreach(['hybrid_battery'=>'Hybrid Battery','abs_brakes'=>'ABS / Brakes','diagnostics'=>'Vehicle Diagnostics / ECU','parts_other'=>'Parts / Other Work'] as $value=>$label)
<option value="{{ $value }}" @selected(old('job_category',$job->job_category??'parts_other')===$value)>{{ __($label) }}</option>
@endforeach
</select></div><div><label>{{ __('Service Type') }}</label><input name="service_type" maxlength="120" value="{{ old('service_type',$job->service_type) }}"></div><div><label>{{ __('Mileage (km)') }}</label><input name="mileage_at_service_km" type="number" min="0" max="2000000" value="{{ old('mileage_at_service_km',$job->mileage_at_service_km) }}"></div><div><label>{{ __('Technician') }}</label><input name="technician_name" list="cardTechnicians" maxlength="150" value="{{ old('technician_name',$job->technician_name) }}"></div><div class="wide"><label>{{ __('Customer Complaint / Symptoms *') }}</label><textarea name="complaint" required maxlength="5000">{{ old('complaint',$job->complaint) }}</textarea></div>
@foreach(['fault_codes'=>['Fault / Warning Codes',1000],'diagnosis'=>['Diagnosis / Findings',5000],'recommended_work'=>['Recommended Repair',5000],'approved_work'=>['Customer Approved Work',5000],'parts_notes'=>['Parts Installed / Notes',3000],'warranty_notes'=>['Warranty Notes (not official warranty)',3000]] as $field=>[$label,$max])
<div><label>{{ __($label) }}</label><textarea name="{{ $field }}" maxlength="{{ $max }}">{{ old($field,$job->$field) }}</textarea></div>
@endforeach
<div><label>{{ __('Estimated Time (minutes)') }}</label><input name="estimated_minutes" type="number" min="0" max="100000" value="{{ old('estimated_minutes',$job->estimated_minutes) }}"></div><div><label>{{ __('Next Service Date') }}</label><input name="next_service_date" type="date" value="{{ old('next_service_date',$job->next_service_date) }}"></div><div><label>{{ __('Next Service Mileage (km)') }}</label><input name="next_service_km" type="number" min="0" max="2000000" value="{{ old('next_service_km',$job->next_service_km) }}"></div></div><div class="job-card-save"><button type="submit">{{ __('Save Job Details') }}</button></div></form></section>
<section class="card job-card-screen"><h2>{{ __('Linked Parts / Serials') }}</h2>
@forelse($parts as $part)
<p>{{ $part->description }} · {{ $part->quantity }} · <strong>{{ $part->serial_number }}</strong><br><small>{{ $part->invoice_number }} · {{ __($part->status==='draft'?'Draft / Reserved':'Finalized') }}</small></p>
@empty
<p class="muted">{{ __('No billed parts yet.') }}</p>
@endforelse
<div class="actions"><a class="btn secondary" href="{{ route('ccs.jobs.battery',$job->id) }}">{{ __('Service / Parts Record →') }}</a><a class="btn secondary" href="{{ route('ccs.warranties',['job_id'=>$job->id]) }}">{{ __('Customer Warranties') }}</a></div></section>
<section class="job-card-print"><h2>CCS SOLUTIONS · {{ $job->job_number }}</h2><p>{{ $job->customer_name }} · {{ $job->phone }}<br>{{ $job->registration_number }} · {{ $job->make }} {{ $job->model }}<br>{{ __('Received') }}: {{ $job->received_at }} · {{ __('Mileage (km)') }}: {{ $job->mileage_at_service_km??'—' }}<br>{{ __('Technician') }}: {{ $job->technician_name??'—' }} · {{ __('Repair Status') }}: {{ __($statusLabels[$job->status]??$job->status) }}</p>
@foreach(['complaint'=>'Customer Complaint / Symptoms','fault_codes'=>'Fault / Warning Codes','diagnosis'=>'Diagnosis / Findings','recommended_work'=>'Recommended Repair','approved_work'=>'Customer Approved Work','parts_notes'=>'Parts Installed / Notes'] as $field=>$label)
<h3>{{ __($label) }}</h3><p>{{ $job->$field?:'—' }}</p>
@endforeach
<h3>{{ __('Linked Parts / Serials') }}</h3><table><thead><tr><th>{{ __('Item') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Serial Number') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
@foreach($parts as $part)
<tr><td>{{ $part->description }}</td><td>{{ $part->quantity }}</td><td>{{ $part->serial_number??'—' }}</td><td>{{ __($part->status==='draft'?'Draft / Reserved':'Finalized') }}</td></tr>
@endforeach
</tbody></table></section></div>
<datalist id="cardTechnicians">
@foreach($technicians as $technician)
<option value="{{ $technician }}"></option>
@endforeach
</datalist>
@endsection
