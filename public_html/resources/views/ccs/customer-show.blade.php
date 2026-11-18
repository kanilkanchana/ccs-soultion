@extends('ccs.layout')
@section('title',__('Customer History'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
<style>.customer-actions{display:flex;flex-wrap:wrap;gap:7px;min-width:230px}.customer-actions .btn{display:inline-flex;padding:8px 11px;font-size:12px;line-height:1.3;white-space:normal}.customer-edit-feedback{margin-bottom:14px;padding:12px;border-radius:8px;background:#24493d}.customer-edit-errors{color:#ffc4bc;white-space:normal}</style>
<div class="directory-head"><div><h1>{{ $customer->name }}</h1><p>{{ $customer->customer_code }} · {{ __('Customer & Vehicle History') }}</p></div><div class="actions"><a class="btn secondary" href="{{ route('ccs.customers') }}">← {{ __('Customers') }}</a><a class="btn secondary" href="{{ route('ccs.customers.serial-records',$customer->id) }}">{{ __('All Customer Serial Records') }}</a><a class="btn secondary" href="{{ route('ccs.warranties',['customer_id'=>$customer->id]) }}">{{ __('Customer Warranties') }}</a><button type="button" class="secondary" data-dialog-open="customerEditDialog">{{ __('Edit Customer') }}</button><button type="button" data-dialog-open="vehicleDialog">{{ __('+ Add Vehicle') }}</button></div></div>
@if(session('success'))<div class="customer-edit-feedback" role="status">{{ session('success') }}</div>@endif
<section class="directory-panel"><div class="profile-info"><div><small>{{ __('Phone') }}</small><strong>{{ $customer->phone }}</strong></div><div><small>{{ __('WhatsApp') }}</small><strong>{{ $customer->whatsapp??'—' }}</strong></div><div><small>{{ __('Registered Vehicles') }}</small><strong>{{ $vehicles->count() }}</strong></div></div><details style="padding:0 18px 18px"><summary>{{ __('More Customer Details') }}</summary><p>{{ __('Address') }}: {{ $customer->address??'—' }}<br>{{ __('NIC') }}: {{ $customer->nic??'—' }}</p><p style="white-space:pre-wrap">{{ $customer->notes??'' }}</p></details></section>
<section class="directory-panel"><h2 class="directory-section-title">{{ __('Registered Vehicles') }}</h2><div class="directory-scroll"><table class="directory-table"><thead><tr><th>{{ __('Vehicle ID / Plate') }}</th><th>{{ __('Make & Model') }}</th><th>{{ __('Year / Generation') }}</th><th>{{ __('Colour') }}</th><th>{{ __('Recorded Mileage') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($vehicles as $vehicle)
<tr @class(['selected'=>$selectedVehicle && $selectedVehicle->id===$vehicle->id])><td><strong>{{ $vehicle->registration_number }}</strong><small>{{ $vehicle->vehicle_code }}</small></td><td>{{ $vehicle->make }} {{ $vehicle->model }}</td><td>{{ $vehicle->manufacture_year??'—' }}<small>{{ $vehicle->generation??'—' }}</small></td><td>{{ $vehicle->colour??'—' }}</td><td>{{ $vehicle->current_mileage_km===null?'—':number_format($vehicle->current_mileage_km).' km' }}</td><td><div class="customer-actions"><a class="btn secondary" href="{{ route('ccs.customers.show',['id'=>$customer->id,'vehicle_id'=>$vehicle->id]) }}">{{ __('View History') }}</a><a class="btn secondary" href="{{ route('ccs.jobs',['new'=>1,'vehicle_id'=>$vehicle->id]) }}">{{ __('+ New Repair Job') }}</a></div></td></tr>
@empty
<tr><td colspan="6" class="directory-empty">{{ __('No vehicles registered.') }}</td></tr>
@endforelse
</tbody></table></div></section>
<section class="directory-panel"><h2 class="directory-section-title">{{ __('Vehicle Service History') }}</h2><form method="GET" action="{{ route('ccs.customers.show',$customer->id) }}" class="directory-filter"><select name="vehicle_id" aria-label="{{ __('Filter Vehicle') }}" onchange="this.form.submit()"><option value="">{{ __('All Vehicles') }}</option>
@foreach($vehicles as $vehicle)
<option value="{{ $vehicle->id }}" @selected($selectedVehicle && $selectedVehicle->id===$vehicle->id)>{{ $vehicle->registration_number }} · {{ $vehicle->make }} {{ $vehicle->model }}</option>
@endforeach
</select><button type="submit">{{ __('Filter') }}</button><a href="{{ route('ccs.vehicles.index',['customer_id'=>$customer->id]) }}">{{ __('Open Vehicles List') }} →</a></form><div class="directory-scroll"><table class="directory-table"><thead><tr><th>{{ __('Date / Job') }}</th><th>{{ __('Vehicle') }}</th><th>{{ __('Complaint') }}</th><th>{{ __('Repair Status') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
@forelse($jobs as $job)
@php($jobInvoices=$invoices->get($job->id,collect()))
<tr><td>{{ $job->received_at?substr($job->received_at,0,10):substr($job->created_at??'',0,10) }}<small>{{ $job->job_number }}</small></td><td>{{ $job->registration_number }}<small>{{ $job->make }} {{ $job->model }}</small></td><td style="white-space:normal;min-width:180px">{{ $job->complaint }}</td><td>{{ __($statusLabels[$job->status]??$job->status) }}</td><td>
@if($jobInvoices->isNotEmpty())
@foreach($jobInvoices as $invoice)
<a href="{{ route('ccs.invoices.show',$invoice->id) }}">{{ $invoice->invoice_number }}</a><small>Rs. {{ number_format($invoice->total_lkr,2) }} · {{ __($invoice->status==='void'?'Void':str_replace('_',' ',ucwords($invoice->status,'_'))) }}</small>
@endforeach
@else
{{ __('Not billed') }}
@endif
</td><td><div class="customer-actions">
@if(app(\App\Services\CcsAdministration::class)->can(auth()->user(),'ccs.pos'))
<a class="btn secondary" href="{{ route('ccs.pos',['job_id'=>$job->id]) }}">{{ __($jobInvoices->contains(fn($invoice)=>$invoice->status!=='void')?'Open Invoice / Payment':'Open POS') }}</a>
@endif
<a class="btn secondary" href="{{ route('ccs.jobs.card',$job->id) }}">{{ __('Open Job Card') }}</a><a class="btn secondary" href="{{ route('ccs.jobs.battery',$job->id) }}">{{ __('Service / Parts Record →') }}</a></div></td></tr>
@empty
<tr><td colspan="6" class="directory-empty">{{ __('No repair history yet.') }}</td></tr>
@endforelse
</tbody></table></div><div class="directory-footer"><span>{{ $jobs->firstItem()??0 }}–{{ $jobs->lastItem()??0 }} / {{ $jobs->total() }}</span>{{ $jobs->links() }}</div></section>
<dialog id="customerEditDialog" class="directory-dialog" @if($errors->customerEdit->any()) data-auto-open @endif>
<div class="directory-dialog-head"><h2>{{ __('Edit Customer') }}</h2><button type="button" data-dialog-close="customerEditDialog">{{ __('Close') }}</button></div>
@if($errors->customerEdit->any())<div class="customer-edit-errors" role="alert"><ul>@foreach($errors->customerEdit->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('ccs.customers.update',$customer->id) }}">@csrf @method('PUT')
<div class="directory-fields"><div><label for="editCustomerName">{{ __('Customer Name *') }}</label><input id="editCustomerName" name="name" value="{{ old('name',$customer->name) }}" maxlength="150" required></div><div><label for="editCustomerPhone">{{ __('Phone Number') }}</label><input id="editCustomerPhone" name="phone" value="{{ old('phone',$customer->phone) }}" maxlength="30"></div><div><label for="editCustomerWhatsapp">{{ __('WhatsApp Number') }}</label><input id="editCustomerWhatsapp" name="whatsapp" value="{{ old('whatsapp',$customer->whatsapp) }}" maxlength="30"></div><div><label for="editCustomerNic">{{ __('NIC') }}</label><input id="editCustomerNic" name="nic" value="{{ old('nic',$customer->nic) }}" maxlength="20"></div><div class="wide"><label for="editCustomerAddress">{{ __('Address') }}</label><input id="editCustomerAddress" name="address" value="{{ old('address',$customer->address) }}" maxlength="500"></div><div class="wide"><label for="editCustomerNotes">{{ __('Additional Notes') }}</label><textarea id="editCustomerNotes" name="notes" maxlength="2000">{{ old('notes',$customer->notes) }}</textarea></div></div><p><button type="submit">{{ __('Save Customer') }}</button></p></form></dialog>
@include('ccs.partials.add-vehicle')
@include('ccs.partials.directory-script')
@endsection
