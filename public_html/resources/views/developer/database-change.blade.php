@extends('developer.layout')
@section('title','Database change')
@section('content')
<div class="dev-head"><div><h1>{{ ucfirst($action) }} website request {{ $row?->id }}</h1><p>Only unlinked requests can be changed. Original values are retained in the developer audit history.</p></div><a class="dev-btn dev-secondary" href="{{ route('developer.database',['table'=>'ccs_website_requests']) }}">Back</a></div>
<form class="dev-card" method="POST" action="{{ route('developer.database.preview') }}" autocomplete="off">@csrf
<input type="hidden" name="action" value="{{ $action }}"><input type="hidden" name="id" value="{{ $row?->id }}">
@if($action==='delete')<p>Delete request <strong>{{ $row->public_reference }}</strong> · {{ $row->name }} · {{ $row->phone }}?</p><p>Linked jobs/customers/vehicles are protected. A full SQLite safety backup is required before deletion.</p>
@else
<div class="dev-grid"><label>Type<select name="kind">@foreach(['inspection','quote','contact','warranty','complaint'] as $kind)<option @selected(old('kind',$row?->kind??'contact')===$kind)>{{ $kind }}</option>@endforeach</select></label><label>Name<input name="name" required maxlength="150" value="{{ old('name',$row?->name) }}"></label><label>Phone<input name="phone" required maxlength="30" value="{{ old('phone',$row?->phone) }}" data-input-english></label><label>Vehicle<input name="vehicle" maxlength="180" value="{{ old('vehicle',$row?->vehicle) }}"></label><label>Plate<input name="registration_number" maxlength="30" value="{{ old('registration_number',$row?->registration_number) }}"></label><label>Serial / reference<input name="reference" maxlength="120" value="{{ old('reference',$row?->reference) }}"></label></div><label>Message<textarea style="min-height:100px" name="message" required maxlength="4000">{{ old('message',$row?->message) }}</textarea></label>
<p class="dev-muted">Manually added records are labelled internal. Contact/reminder consent is not invented. Status, ownership links and appointment confirmations remain in the normal Website Requests workflow.</p>
@endif
<label>Reason for this change<input name="reason" required minlength="10" maxlength="500" value="{{ old('reason') }}"></label><label>Current developer password<input type="password" name="current_password" required autocomplete="current-password"></label><button>Preview change</button></form>
@endsection
