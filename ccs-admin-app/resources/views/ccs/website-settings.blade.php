@extends('ccs.layout')
@section('title','Website Connection')
@section('body-class','ccs-directory-page')
@section('content')
<style>.ccs-directory-page main{max-width:none;min-width:0}</style>
<div class="page-head"><h1>Website Connection</h1><a class="btn secondary" href="{{ route('ccs.website.index') }}">Website Requests</a></div>
@if(!$ready)<p>Run the website migration before saving.</p>@else<section class="card"><h2>Public website → dashboard</h2><p>Website forms save requests only. Staff review before creating jobs, confirming bookings or recording warranty claims.</p><form method="POST" action="{{ route('ccs.settings.website.save') }}" autocomplete="off" data-input-english>@csrf<label><input style="width:auto" type="checkbox" name="enabled" value="1" @checked($values['enabled'])> Enable website forms and configured lookup</label><label>Allowed website origins — one per line<textarea name="origins" rows="7" required>{{ implode("\n",$values['origins']) }}</textarea></label><p><small>Example: https://ccssolutions.lk — no trailing slash or path. Add your final .lk domain when available. No API key goes in public JavaScript.</small></p><p>Public lookup mode: <strong>{{ $values['lookup_mode'] }}</strong> — changed through Developer → Website Controls.</p><label>Current owner password<input name="current_password" type="password" required autocomplete="current-password"></label><button>Save connection</button></form></section>@endif
@endsection
