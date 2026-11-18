@extends('ccs.layout')
@section('title',__('Review Database Restore'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
<style>.restore-review-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px}.restore-review-grid pre{background:#08191d;padding:18px;border:1px solid #31534a;border-radius:10px;overflow:auto;font-size:14px;line-height:1.9}.restore-review-grid li{margin:12px 0;line-height:1.6}@media(max-width:900px){.restore-review-grid{grid-template-columns:1fr}}</style>
<div class="directory-head"><div><h1>{{ __('Review Database Restore') }}</h1><p>{{ __('The live database has not been changed.') }}</p></div><a class="btn secondary" href="{{ route('ccs.settings.backups') }}">{{ __('Backup & Restore') }}</a></div>
<div class="restore-review-grid"><section class="card"><h2>{{ __('Validated Backup') }}</h2><p>{{ __('Backup Date') }}: {{ \Illuminate\Support\Carbon::parse($job['backup_date'])->timezone('Asia/Colombo')->format('Y-m-d H:i') }}</p><p>{{ __('Request Status') }}: {{ $job['state'] }}</p><p>{{ __('Review expires') }}: {{ \Illuminate\Support\Carbon::createFromTimestamp($job['expires_at'])->timezone('Asia/Colombo')->format('Y-m-d H:i') }}</p><table><thead><tr><th>{{ __('Records In Backup') }}</th><th>{{ __('Count') }}</th></tr></thead><tbody>
@foreach($job['counts'] as $table=>$count)
<tr><td>{{ ucfirst(str_replace('_',' ',$table)) }}</td><td>{{ $count }}</td></tr>
@endforeach
</tbody></table></section><section class="card"><h2>{{ __('Restore This Backup') }}</h2><p>{{ __('The backup has been checked. Your current data has not changed yet.') }}</p>
@if($job['state']==='ready' && $job['expires_at']>=time())
<p>{{ __('Restore replaces current records with this backup. Records added after the backup date will be removed. A safety backup of current data is saved automatically first.') }}</p>
<form id="restore-now-form" method="POST" action="{{ route('ccs.settings.backups.apply',$job['id']) }}">@csrf
<label>{{ __('Current Owner Password') }}<input type="password" name="password" autocomplete="current-password" required></label>
<label style="display:flex;gap:10px;margin:18px 0"><input type="checkbox" name="confirmation" value="1" required style="width:auto"> <span>{{ __('I confirm that current data will be replaced with the backup shown here.') }}</span></label>
<button id="restore-now-button" type="submit">{{ __('Restore Now') }}</button>
<p id="restore-now-status" role="status" aria-live="polite" hidden>{{ __('Restoring backup… Please keep this page open. The system will reopen automatically when finished.') }}</p>
</form>
@else
<p>{{ __('This restore request is expired or already used. Prepare a new request if needed.') }}</p>
@endif
<p class="muted">{{ __('Other users cannot work during restore. All users must sign in again after it finishes. Use the owner login stored in the backup.') }}</p>
</section></div>
<script>
(function(){const form=document.getElementById('restore-now-form');if(!form)return;let busy=false;form.addEventListener('submit',function(e){if(busy){e.preventDefault();return;}if(!form.reportValidity()){e.preventDefault();return;}busy=true;document.getElementById('restore-now-button').disabled=true;document.getElementById('restore-now-status').hidden=false;});})();
</script>
@endsection
