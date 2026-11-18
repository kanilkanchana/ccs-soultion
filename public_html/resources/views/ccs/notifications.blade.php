@extends('ccs.layout')
@section('title',__('Notifications'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
<div class="directory-head"><div><h1>{{ __('Notifications') }}</h1><p class="directory-muted">{{ count(array_filter($items,fn($item)=>!$item['read'])) }} {{ __('Unread') }} · {{ count($items) }} {{ __('Current Alerts') }}</p></div><div class="actions"><a class="btn secondary" href="{{ route('ccs.settings') }}">{{ __('Notification Preferences') }}</a><form method="POST" action="{{ route('ccs.notifications.read') }}">@csrf<button>{{ __('Mark All Read') }}</button></form></div></div>
<section class="directory-panel"><div class="directory-scroll"><table class="directory-table"><thead><tr><th>{{ __('Notification') }}</th><th>{{ __('Details') }}</th><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($items as $item)
<tr><td><strong>{{ __($item['title']) }}</strong><small>{{ ucfirst($item['type']) }}</small></td><td>{{ $item['text'] }}</td><td>{{ $item['date'] }}</td><td><span class="directory-badge {{ $item['read']?'':'due' }}">{{ $item['read']?__('Read'):__('Unread') }}</span></td><td><div class="actions"><a class="btn secondary" href="{{ $item['url'] }}">{{ __('Open Record') }}</a>
@if(!$item['read'])
<form method="POST" action="{{ route('ccs.notifications.read') }}">@csrf<input type="hidden" name="key" value="{{ $item['key'] }}"><button>{{ __('Mark Read') }}</button></form>
@endif
</div></td></tr>
@empty
<tr><td colspan="5" class="directory-empty">{{ __('No current alerts for your preferences.') }}</td></tr>
@endforelse
</tbody></table></div><div class="directory-footer">{{ count($items) }} {{ __('Current Alerts') }}</div></section>
@endsection
