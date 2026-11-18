@extends('ccs.layout')
@section('title',__('Vehicles'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
<div class="directory-head"><div><h1>{{ __('Vehicles') }}</h1><p>{{ __('Registered vehicles and their customer records') }}</p></div><div class="actions"><a class="btn secondary" href="{{ route('ccs.customers') }}">{{ __('Customers') }}</a><button type="button" data-dialog-open="vehicleDialog">{{ __('+ Add Vehicle') }}</button></div></div><section class="directory-panel"><form method="GET" action="{{ route('ccs.vehicles.index') }}" class="directory-filter"><input name="q" maxlength="150" value="{{ $search }}" placeholder="{{ __('Search plate, model, customer or phone') }}"><select name="customer_id" aria-label="{{ __('Select Customer') }}"><option value="">{{ __('All Customers') }}</option>
@foreach($customers as $owner)
<option value="{{ $owner->id }}" @selected($selectedCustomer===$owner->id)>{{ $owner->name }} · {{ $owner->customer_code }}</option>
@endforeach
</select><button type="submit">{{ __('Search') }}</button><a href="{{ route('ccs.vehicles.index') }}">{{ __('Reset') }}</a></form><div class="directory-scroll"><table class="directory-table" data-v68-records data-v68-secondary="2,3"><thead><tr><th>{{ __('Vehicle') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Year / Colour') }}</th><th>{{ __('Recorded Mileage') }}</th><th>{{ __('Jobs / Last Visit') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
@forelse($vehicles as $vehicle)
<tr data-directory-url="{{ route('ccs.customers.show',['id'=>$vehicle->customer_id,'vehicle_id'=>$vehicle->id]) }}" tabindex="0" role="button"><td><strong>{{ $vehicle->registration_number }}</strong><small>{{ $vehicle->make }} {{ $vehicle->model }} · {{ $vehicle->vehicle_code }}</small></td><td><a href="{{ route('ccs.customers.show',$vehicle->customer_id) }}">{{ $vehicle->customer_name }}</a><small>{{ $vehicle->phone }} · {{ $vehicle->customer_code }}</small></td><td>{{ $vehicle->manufacture_year??'—' }}<small>{{ $vehicle->colour??'—' }} · {{ $vehicle->generation??'—' }}</small></td><td>{{ $vehicle->current_mileage_km===null?'—':number_format($vehicle->current_mileage_km).' km' }}</td><td>{{ $vehicle->job_count }} {{ __('jobs') }}<small>{{ $vehicle->last_visit?substr($vehicle->last_visit,0,10):'—' }}</small></td><td><a href="{{ route('ccs.customers.show',['id'=>$vehicle->customer_id,'vehicle_id'=>$vehicle->id]) }}">{{ __('View Details / History') }} →</a></td></tr>
@empty
<tr><td colspan="6" class="directory-empty">{{ __('No matching vehicles.') }}</td></tr>
@endforelse
</tbody></table></div><div class="directory-footer"><span>{{ $vehicles->firstItem()??0 }}–{{ $vehicles->lastItem()??0 }} / {{ $vehicles->total() }}</span>{{ $vehicles->links() }}</div></section>
@include('ccs.partials.add-vehicle')
@include('ccs.partials.directory-script')
@endsection
