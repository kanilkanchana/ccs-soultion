@extends('developer.layout')
@section('title','Business Cleanup Result')
@section('login')
<main style="padding:clamp(20px,5vw,70px);min-height:100dvh">
@if($failed)<h1>{{ __('Cleanup Needs Attention') }}</h1><section class="dev-card"><p>{{ __('Data cleanup committed, but final recording or reopening failed. Keep maintenance enabled and contact your administrator to inspect the safety backup and developer operation journal.') }}</p></section>
@else<h1>{{ __('Business Data Cleared') }}</h1><section class="dev-card"><p>{{ __('The selected business data was cleared and checked. Staff/developer accounts, settings, warranty rules, audit history and backups remain.') }}</p><p>{{ __('Safety Backup ID') }}: <code>{{ $result['backup_id'] }}</code></p><p>{{ __('All previous sessions have ended. Sign in again. The owner can find the safety backup in Backup & Restore.') }}</p><div class="dev-actions"><a class="dev-btn" href="{{ route('ccs.login') }}">{{ __('Staff Login') }}</a><a class="dev-btn dev-secondary" href="{{ route('developer.login') }}">{{ __('Developer Login') }}</a></div></section>@endif
</main>
@endsection
