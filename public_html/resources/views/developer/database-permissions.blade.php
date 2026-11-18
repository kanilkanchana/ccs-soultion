@extends('developer.layout')
@section('title','Database permissions')
@section('content')
<h1>Database permissions</h1><p>All write permissions start disabled. These permissions allow changes only to unlinked website requests. Financial, stock, customer/vehicle, authentication and schema changes use their existing controlled workflows.</p>
@foreach($accounts as $account)
<form class="dev-card" method="POST" action="{{ route('developer.database.permissions.save') }}" autocomplete="off">@csrf<input name="developer_id" type="hidden" value="{{ $account->id }}"><h2>{{ $account->name }} · {{ $account->email }} {{ $account->active?'':'(disabled)' }}</h2><div class="dev-actions">
@foreach(['add','edit','delete'] as $action)
<label><input type="checkbox" name="can_{{ $action }}" value="1" @checked((bool)($grants->get($account->id)?->{'can_'.$action}??false))> {{ ucfirst($action) }}</label>
@endforeach
</div><label>Current administrator developer password<input name="current_password" type="password" required autocomplete="current-password"></label><button>Save permissions</button></form>
@endforeach
@endsection
