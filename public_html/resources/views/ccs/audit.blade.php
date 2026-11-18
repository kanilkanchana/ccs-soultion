@extends('ccs.layout')
@section('title',__('Audit Log'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
<div class="directory-head"><h1>{{ __('Audit Log') }}</h1><a href="{{ route('ccs.settings') }}">{{ __('System Settings') }}</a></div><section class="directory-panel"><form class="directory-filter"><input name="q" value="{{ $q }}" maxlength="100" placeholder="Action / staff / record type"><button>{{ __('Search') }}</button></form><div class="directory-scroll"><table class="directory-table"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Staff') }}</th><th>{{ __('Action') }}</th><th>{{ __('Record') }}</th></tr></thead><tbody>
@forelse($logs as $log)
<tr><td>{{ $log->created_at }}</td><td>{{ $log->staff_name??'System' }}</td><td>{{ $log->action }}</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td></tr>
@empty
<tr><td colspan="4">{{ __('No records found.') }}</td></tr>
@endforelse
</tbody></table></div><div class="directory-footer">{{ $logs->links() }}</div></section>
@endsection
