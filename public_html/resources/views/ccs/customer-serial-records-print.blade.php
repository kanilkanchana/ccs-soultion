<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ __('All Customer Serial Records') }}</title>
<style>body{font-family:Arial,system-ui,sans-serif;color:#111;margin:24px}.controls{margin-bottom:18px}button{padding:10px 16px;cursor:pointer}.sheet{max-width:190mm;margin:0 auto 28px}.brand{text-align:center}.brand h1{font-size:27px;margin:0}.brand h2{font-size:18px;margin:3px 0 18px}.info{display:grid;grid-template-columns:1fr 1fr;gap:4px 24px;font-size:12px;margin:15px 0 20px}.info div{padding:3px 0;border-bottom:1px dotted #aaa;overflow-wrap:anywhere}.columns{display:grid;grid-template-columns:1fr 1fr;gap:18px}table{width:100%;border-collapse:collapse;font-size:10px;table-layout:fixed}th,td{border:1px solid #333;padding:4px;text-align:left;vertical-align:top;overflow-wrap:anywhere}tr{break-inside:avoid}th{background:#eee}th:first-child,td:first-child{width:25px}small{display:block}.services{font-size:11px;margin-top:15px}.services p{border-bottom:1px dotted #aaa;min-height:18px;white-space:pre-wrap}.note{font-size:10px;color:#444}@page{size:A4;margin:12mm}@media print{body{margin:0}.controls{display:none}.sheet{break-after:page;margin:0}.sheet:last-child{break-after:auto}thead{display:table-header-group}}</style></head><body>
<div class="controls"><button type="button" onclick="window.print()">{{ __('Print / Save as PDF') }}</button></div>
@forelse($records->groupBy('vehicle_id') as $vehicleId => $vehicleRows)
@php
 $firstRow = $vehicleRows->first();
 $chunks = $vehicleRows->values()->chunk(34);
 $vehicleServices = $vehicleRows->pluck('job_id')->unique()->flatMap(fn($id) => $services->get($id) ?? collect());
@endphp
@foreach($chunks as $chunk)
@php $pageOffset = 34 * $loop->index; @endphp
<section class="sheet">
<div class="brand"><h1>C.C.S. Solutions</h1><h2>{{ __('All Customer Serial Records') }}</h2></div>
<div class="info"><div><strong>{{ __('Vehicle') }}:</strong> {{ $firstRow->make }} {{ $firstRow->model }}</div><div><strong>{{ __('Vehicle No:') }}</strong> {{ $firstRow->registration_number }}</div><div><strong>{{ __('Customer:') }}</strong> {{ $customer->name }}</div><div><strong>{{ __('Phone:') }}</strong> {{ $customer->phone ?: '—' }}</div></div>
<div class="columns">
@foreach([$chunk->take(23),$chunk->skip(23)] as $column)
@php $columnOffset = $loop->first ? 0 : 23; @endphp
<div><table><thead><tr><th>No.</th><th>{{ __('Part Name') }} / {{ __('Serial Number') }}</th><th>{{ __('Reading / Details') }} / {{ __('Job No:') }}</th></tr></thead><tbody>
@foreach($column as $row)
<tr><td>{{ $loop->iteration + $columnOffset + $pageOffset }}</td><td>{{ $row->part_name }}<small>{{ $row->serial_number }}</small></td><td>
@if($row->source === 'battery_module')
{{ __('Module Position') }} {{ $row->position }} · {{ $row->voltage === null ? __('Unmeasured') : $row->voltage.' V' }}
@else
@if($row->is_battery_module) {{ __('Measured voltage not recorded') }} @endif
@if($row->item_condition) {{ __('Condition') }}: {{ $row->item_condition }} @endif
@if($row->item_year) · {{ __('Year') }}: {{ $row->item_year }} @endif
@if(!$row->is_battery_module && !$row->item_condition && !$row->item_year) {{ __('Finalized invoice part') }} @endif
@endif
<small>{{ $row->job_number }} · {{ $row->recorded_at ?: '—' }}</small></td></tr>
@endforeach
</tbody></table>
@if($loop->last)
<div class="services"><strong>Service records</strong>
@forelse($vehicleServices as $service)
<p>{{ $service->service_date }} · {{ $service->mileage_km ?? '—' }} km — {{ $service->notes ?: '—' }}</p>
@empty
<p>—</p>
@endforelse
</div>
@endif
</div>
@endforeach
</div><p class="note">@if($serial !== '') {{ __('Search Exact Serial') }}: {{ $serial }} · @endif {{ __('Serial Records') }}: {{ $vehicleRows->count() }}</p></section>
@endforeach
@empty
<p>{{ __('No serial records found for this customer.') }}</p>
@endforelse
</body></html>
