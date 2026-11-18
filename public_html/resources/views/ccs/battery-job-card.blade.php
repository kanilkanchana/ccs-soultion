@extends('ccs.layout')
@section('title',__('Service / Parts Record'))
@section('content')
<style>
.battery-page{font-family:system-ui,sans-serif;color:#effbf4;display:grid;gap:18px}.battery-panel{padding:22px;background:#0c1f30;border:1px solid #234c70;border-radius:12px}.battery-top{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}.battery-top h1{margin:0;color:#ffcc00;font-size:25px}.battery-btn{display:inline-block;background:#a1ecb7;color:#062019;padding:10px 15px;border:0;border-radius:6px;text-decoration:none;font-weight:bold;cursor:pointer}.battery-muted{color:#a6c4b9}.battery-info,.battery-fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px}.battery-info p{margin:8px 0}.battery-page input,.battery-page textarea{box-sizing:border-box;background:#08191e;color:#effbf4;border:1px solid #4b7770;border-radius:5px;padding:9px;width:100%;font:inherit}.battery-fields label{display:block}.battery-fields input{margin-top:5px}.battery-table-wrap{overflow-x:auto;margin:16px 0}.battery-table{width:100%;border-collapse:collapse;min-width:600px}.battery-table th,.battery-table td{padding:9px;border-bottom:1px solid #234c70;text-align:left}.battery-table th{color:#87eec2;white-space:nowrap}.battery-table td:first-child{width:45px}.battery-table input[name$="[voltage]"]{min-width:100px}.battery-table input[name$="[serial_number]"]{min-width:180px}.battery-error{color:#ffaaaa}.battery-history{display:grid;gap:10px}.battery-history a{color:#87eec2}.battery-print-only{display:none}.battery-help{margin:12px 0;color:#a6c4b9;line-height:1.6}.battery-save{margin-top:16px}.battery-service-note{white-space:pre-wrap}
@media print{
.battery-table{min-width:0!important;table-layout:fixed}.battery-table-wrap{overflow:visible;margin:6px 0}.battery-table td:first-child,.battery-table th:first-child{width:7%!important}.battery-table th:nth-child(2){width:29%}.battery-table th:nth-child(3){width:31%}.battery-table th:nth-child(4){width:33%}
@page{size:A4;margin:12mm}
body,main,.battery-page{background:white!important;color:black!important}.no-print,aside,nav,header,footer,.sidebar{display:none!important}.battery-page .battery-panel{background:white;border:0;padding:0}.battery-page{display:block}.battery-print-only{display:block}.battery-top h1{color:black}.battery-info{grid-template-columns:1fr 1fr;font-size:12px}.battery-print-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.battery-print-grid table{width:100%;border-collapse:collapse;font-size:10px}.battery-print-grid th,.battery-print-grid td{border:1px solid black;padding:4px;text-align:left;height:18px;overflow-wrap:anywhere;white-space:normal;color:black}.battery-print-grid tr{break-inside:avoid}.battery-print-notes{font-size:11px;margin-top:12px;white-space:pre-wrap}.battery-print-services{font-size:10px}.battery-print-services p{margin:10px 0;white-space:pre-wrap}main{margin:0!important;padding:0!important;max-width:none!important}.battery-panel{break-inside:auto}
}
</style>
@php
    $shownJob = $viewing ? $recordJob : $job;
    $count = $viewing ? $record->module_count : (int) old('module_count', $latest->module_count ?? 34);
    $rows = [];
    for ($position=1; $position<=100; $position++) {
        $prior = $readings->get($position);
        $rows[$position] = [
            'position' => $position,
            'serial_number' => old("modules.$position.serial_number", $prior->serial_number ?? ''),
            'voltage' => old("modules.$position.voltage", ''),
            'notes' => old("modules.$position.notes", ''),
        ];
    }
@endphp
<div class="battery-page">
    <div class="battery-panel battery-top no-print">
        <h1>{{ __('Service / Parts Record') }}</h1>
        <div><a class="battery-btn" href="{{ route('ccs.jobs') }}">{{ __('← Repair Jobs') }}</a> <a class="battery-btn" href="{{ route('ccs.customers.serial-records',$shownJob->customer_id) }}">{{ __('All Customer Serial Records') }}</a>
        @if($viewing)
        <a class="battery-btn" href="{{ route('ccs.jobs.battery',$job->id) }}">{{ __('+ New Service') }}</a>
        @endif
        <button class="battery-btn" type="button" onclick="window.print()">{{ __('Print / Save as PDF') }}</button></div>
    </div>
    @if(session('success'))<div class="battery-panel no-print">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="battery-panel battery-error no-print"><strong>{{ __('Please check:') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <section class="battery-panel">
        <h2 class="battery-print-only">C.C.S. Solutions — {{ __('Service / Parts Record') }}</h2>
        <div class="battery-info">
            <div><p><strong>{{ __('Vehicle:') }}</strong> {{ $shownJob->make }} {{ $shownJob->model }}</p><p><strong>{{ __('Vehicle No:') }}</strong> {{ $shownJob->registration_number }}</p><p><strong>{{ __('Customer:') }}</strong> {{ $shownJob->customer_name }}</p><p><strong>{{ __('Phone:') }}</strong> {{ $shownJob->phone }}</p></div>
            <div><p><strong>{{ __('Job No:') }}</strong> {{ $shownJob->job_number }}</p><p><strong>{{ __('Nature of Job:') }}</strong> {{ $shownJob->service_type ?: $shownJob->complaint }}</p>
            @if($viewing)<p><strong>{{ __('Service Date:') }}</strong> {{ $record->service_date }}</p><p><strong>{{ __('Mileage:') }}</strong> {{ $record->mileage_km ?? '—' }} km</p><p><strong>{{ __('Recorded:') }}</strong> {{ $record->created_at }}</p>@else<p><strong>{{ __('Service Date:') }}</strong> {{ $documentRecord->service_date ?? substr($shownJob->received_at ?? $shownJob->created_at ?? '',0,10) }}</p><p><strong>{{ __('Mileage:') }}</strong> {{ $shownJob->mileage_at_service_km ?? '—' }} km</p>@endif</div>
        </div>
        <div class="no-print"><h2>{{ __('Service / Parts Record') }}</h2><p class="battery-help">{{ __('Parts and serials from this job are shown automatically. Battery modules show measured voltage; other parts show their saved details.') }}</p>
        @include('ccs.partials.service-parts-table',['documentTableRows'=>$documentRows,'documentOffset'=>0])
        </div>
        <div class="battery-print-only"><div class="battery-print-grid" @if(count($documentRows)<=23) style="grid-template-columns:minmax(0,1fr)" @endif>
        @foreach(count($documentRows)>23?[array_slice($documentRows,0,23),array_slice($documentRows,23)]:[$documentRows] as $documentTableRows)
        <div>@include('ccs.partials.service-parts-table',['documentTableRows'=>$documentTableRows,'documentOffset'=>$loop->first?0:23])
        @if($loop->last)<div class="battery-print-services"><h3>{{ __('Recent Services (up to this record)') }}</h3>
        @foreach(($viewing?$history->where('id','<=',$record->id):$history)->take(6)->reverse() as $service)<p>{{ $service->service_date }} — {{ $service->mileage_km ?? '—' }} km<br>{{ $service->notes ?: __('Service readings recorded.') }}</p>@endforeach
        </div>@endif</div>
        @endforeach
        </div>@if($documentRecord)<p class="battery-print-notes">{{ __('Service Notes') }}: {{ $documentRecord->notes ?: '—' }}</p>@endif</div>
        @if(!$viewing)
        <details class="no-print" style="margin-top:24px" @if(old('modules') || $job->job_category==='hybrid_battery') open @endif><summary style="cursor:pointer;font-weight:bold">{{ __('Add / Update Battery Module Readings') }}</summary>

        <p class="battery-help">{{ __('1. Choose module count. 2. Enter serial numbers and measured voltages. 3. Save, then print.') }}<br>Previous serial numbers are filled in for this vehicle. Enter fresh voltage readings; old service records stay unchanged.</p>
        <form method="POST" action="{{ route('ccs.jobs.battery.save',$job->id) }}" id="batteryForm">
            @csrf
            <input type="hidden" name="request_token" value="{{ old('request_token', (string) Illuminate\Support\Str::uuid()) }}">
            <input type="hidden" name="base_record_id" value="{{ old('base_record_id', $latest->id ?? null) }}">
            <div class="battery-fields">
                <label>{{ __('Service Date') }}<input type="date" name="service_date" required max="{{ now()->toDateString() }}" value="{{ old('service_date',now()->toDateString()) }}"></label>
                <label>{{ __('Mileage (km)') }}<input type="number" name="mileage_km" min="0" max="999999999" value="{{ old('mileage_km', $latest->mileage_km ?? $job->mileage_at_service_km) }}"></label>
                <label>{{ __('Number of Modules') }}<input id="moduleCount" type="number" name="module_count" min="1" max="100" required value="{{ $count }}"></label>
            </div>
            <div class="battery-table-wrap"><table class="battery-table"><thead><tr><th>{{ __('No.') }}</th><th>{{ __('Part Name') }}</th><th>{{ __('Serial Number') }}</th><th>{{ __('Reading / Details') }}</th><th>{{ __('Notes (optional)') }}</th></tr></thead><tbody>
            @foreach($rows as $position=>$row)
                <tr data-module-position="{{ $position }}" @if($position>$count) hidden @endif><td>{{ $position }}<input type="hidden" name="modules[{{ $position }}][position]" value="{{ $position }}" @disabled($position>$count)></td>
                <td>{{ __('Battery Module') }}</td><td><input aria-label="Module {{ $position }} serial number" name="modules[{{ $position }}][serial_number]" maxlength="100" value="{{ $row['serial_number'] }}" @disabled($position>$count)></td>
                <td><input aria-label="Module {{ $position }} voltage" name="modules[{{ $position }}][voltage]" placeholder="V" type="number" step="0.001" min="0" max="99999.999" value="{{ $row['voltage'] }}" @disabled($position>$count)></td>
                <td><input aria-label="Module {{ $position }} notes" name="modules[{{ $position }}][notes]" maxlength="250" value="{{ $row['notes'] }}" placeholder="e.g. Replaced module" @disabled($position>$count)></td></tr>
            @endforeach
            </tbody></table></div>
            <label>{{ __('Service Notes') }}<textarea name="notes" rows="3" maxlength="5000" placeholder="Work completed, replacements, next service…">{{ old('notes') }}</textarea></label>
            <p class="battery-help">Blank positions are allowed. Any row with a voltage or note needs a serial number. Use the module's actual serial number; leading zeros are preserved.</p>
            <button class="battery-btn battery-save" id="saveBattery" type="submit">{{ __('Save Service Record') }}</button>
        </form>

        </details>
        @else
        <p class="battery-muted no-print">{{ __('This saved record is kept as history. Use New Service to record new readings or module replacements.') }}</p>
        @endif
    </section>
    <section class="battery-panel no-print"><h2>{{ __('Vehicle Service History') }}</h2><div class="battery-history">
        @forelse($history as $service)<div><a href="{{ route('ccs.jobs.battery',['id'=>$job->id,'record'=>$service->id]) }}">{{ $service->service_date }} · {{ $service->mileage_km ?? '—' }} km · View / Print</a><br><small class="battery-muted">{{ $service->notes }}</small></div>@empty<p class="battery-muted">{{ __('No saved module readings yet. Billed parts are shown in the record above.') }}</p>@endforelse
    </div></section>
</div>
<script>
(()=>{
 const count=document.getElementById('moduleCount'), form=document.getElementById('batteryForm');
 if(!form)return;
 let previous=Number(count.value);
 count.addEventListener('change',()=>{
   const value=Number(count.value);
   if(!Number.isInteger(value)||value<1||value>100){count.value=previous;return;}
   const rows=[...document.querySelectorAll('[data-module-position]')];
   const hidesData=rows.some(row=>Number(row.dataset.modulePosition)>value&&Number(row.dataset.modulePosition)<=previous&&[...row.querySelectorAll('input:not([type="hidden"])')].some(input=>input.value.trim()));
   if(hidesData&&!confirm('Reducing module count excludes filled rows from the new service record. Earlier saved services remain unchanged. Continue?')){count.value=previous;return;}
   rows.forEach(row=>{const hidden=Number(row.dataset.modulePosition)>value;row.hidden=hidden;row.querySelectorAll('input').forEach(input=>input.disabled=hidden);});previous=value;
 });
 form.addEventListener('submit',()=>{const button=document.getElementById('saveBattery');button.disabled=true;button.textContent='Saving…';});
})();
</script>
@endsection
