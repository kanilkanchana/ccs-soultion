@extends('customer.layout')
@section('title',__('My Warranty Cards'))
@section('actions')
@if($preview)
<a class="portal-btn" href="{{ route('ccs.warranties',['customer_id'=>$customer->id]) }}">{{ __('Back to Customer Warranties') }}</a>
@else
<a class="portal-btn" href="{{ route('customer.home') }}">{{ __('My Service Book') }}</a><form method="POST" action="{{ route('customer.logout') }}">@csrf<button>{{ __('Log out') }}</button></form>
@endif
@endsection
@section('content')
@if($preview)
<p class="portal-muted">{{ __('Staff preview of the customer warranty page. This is not a public sharing link.') }}</p>
@endif
<section class="portal-card"><h1>{{ __('My Warranty Cards') }}</h1><p>{{ $customer->name }} · {{ $customer->customer_code }}</p><form method="GET">
@if($preview)
<input type="hidden" name="customer_id" value="{{ $customer->id }}">
@endif
<div class="portal-grid"><label>{{ __('Vehicle') }}<select name="vehicle_id"><option value="">{{ __('All Vehicles') }}</option>
@foreach($vehicles as $vehicle)
<option value="{{ $vehicle->id }}" @selected($vehicleId===(int)$vehicle->id)>{{ $vehicle->registration_number }} · {{ $vehicle->make }} {{ $vehicle->model }}</option>
@endforeach
</select></label><label>{{ __('Actual Serial Number') }}<input name="serial" maxlength="100" value="{{ $serial }}" placeholder="{{ __('Optional exact serial') }}"></label></div><button style="margin-top:15px">{{ __('Search') }}</button></form></section>
@forelse($cards as $card)
<section class="portal-card"><div class="portal-top"><strong>{{ $card['card_status']['card_number'] }}</strong><small>{{ __(['no_warranty'=>'No Warranty','not_started'=>'Not started','time_elapsed'=>'Time limit elapsed','time_remaining'=>'Time remaining','no_time_limit'=>'Mileage / agreed conditions'][$card['card_status']['state']]??'Approved') }}</small></div><h2>{{ $card['name'] }}</h2><p>{{ $card['vehicle']->registration_number }} · {{ $card['vehicle']->make }} {{ $card['vehicle']->model }}</p><p>{{ $card['covered_description']??__('Job / service warranty') }}<br><strong>{{ $card['covered_serial']??'—' }}</strong></p><div class="portal-grid"><p>{{ __('Start Date') }}<br><strong>{{ $card['starts_on'] }}</strong></p><p>{{ __('Ends On') }}<br><strong>{{ $card['expires_on']??'—' }}</strong></p><p>{{ __('Duration (months)') }}: {{ $card['duration_months']??'—' }}<br>{{ __('Distance (km)') }}: {{ $card['distance_km']??'—' }}</p><p>{{ __('Time Left') }}: {{ $card['card_status']['remaining_days']===null?'—':$card['card_status']['remaining_days'].' '.__('days') }}<br>{{ __('Mileage Limit') }}: {{ $card['mileage_limit_km']??'—' }}</p></div>
@if($card['free_services']['included']>0)
<p>{{ __('Free Services Remaining') }}: <strong>{{ $card['free_services']['remaining'] }} / {{ $card['free_services']['included'] }}</strong></p>
@endif
<details><summary>{{ __('Conditions & History') }}</summary><p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $card['terms']??'—' }}</p><p>{{ __('Warranty Limit Basis') }}: {{ __(['time_first'=>'Time based','distance_first'=>'Distance based','whichever_first'=>'Whichever comes first','custom'=>'Custom - explain in conditions'][$card['limit_basis']??'']??'See conditions') }}</p>
@foreach($card['free_services']['visits'] as $visit)
<p>{{ $visit->service_date }} · {{ $visit->job_number }}</p>
@endforeach
</details><p class="portal-muted">{{ __('Contact the workshop for warranty claims. Coverage requires checking the agreed conditions.') }}</p></section>
@empty
<section class="portal-card">{{ __('No approved warranty cards match this selection.') }}</section>
@endforelse
{{ $cards->links() }}
@endsection
