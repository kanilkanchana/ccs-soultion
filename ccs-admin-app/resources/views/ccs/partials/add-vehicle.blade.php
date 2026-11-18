<dialog class="directory-dialog" id="vehicleDialog" @if(old('vehicle_form')) data-auto-open @endif><div class="directory-dialog-head"><h2>{{ __('Add Vehicle') }}</h2><button type="button" data-dialog-close="vehicleDialog">{{ __('Close') }}</button></div><form method="POST" action="{{ route('ccs.vehicles.save') }}">
@csrf
<input type="hidden" name="vehicle_form" value="1">
@if(isset($customer))
<input type="hidden" name="customer_id" value="{{ $customer->id }}"><p>{{ $customer->name }} · {{ $customer->customer_code }}</p>
@else
<label>{{ __('Select Customer') }}</label><select name="customer_id" required><option value="">{{ __('Choose a customer') }}</option>
@foreach($customers as $owner)
<option value="{{ $owner->id }}" @selected((int)old('customer_id',$selectedCustomer)===$owner->id)>{{ $owner->name }} · {{ $owner->customer_code }} · {{ $owner->phone }}</option>
@endforeach
</select><p><a href="{{ route('ccs.customers') }}">{{ __('Register a new customer instead') }} →</a></p>
@endif
@include('ccs.partials.vehicle-fields')
<div class="directory-dialog-actions"><button class="secondary" type="submit" name="next_action" value="profile">{{ __('Save Vehicle') }}</button><button type="submit" name="next_action" value="job">{{ __('Save & New Repair Job') }} →</button></div></form></dialog>
