@extends('ccs.layout')
@section('title',__('Warranty Claims'))
@section('body-class','ccs-directory-page')
@section('content')
<!-- v101 stock warranty layout -->
<link rel="stylesheet" href="{{ asset('ccs-ui/admin-v102.css') }}?v=102"><script src="{{ asset('ccs-ui/admin-v101.js') }}?v=101" defer></script>

@include('ccs.partials.directory-styles')
@include('ccs.partials.editor-styles')
<style>
.claims-filter{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.claims-filter label{display:block;flex:1 1 250px;margin:0}.claims-filter label span{display:block;margin-bottom:5px;color:#b0c8bf;font-size:13px}.claims-filter input,.claims-filter select{width:100%;margin:0;box-sizing:border-box}.claims-filter button{width:auto;min-height:44px}.claims-count{margin:18px 0 12px;color:#a6c6bb}.claims-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;align-items:stretch}.claim-card{display:flex;flex-direction:column;min-width:0;margin:0!important;background:#14262e;border:1px solid #2e454c;border-radius:12px;padding:18px;overflow-wrap:anywhere}.claim-card-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.claim-card-header h2{font-size:16px;margin:0}.claim-badge{display:inline-block;flex-shrink:0;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:700;background:#39543e;color:#d6f5dc}.claim-badge.pending{background:#5a4c27;color:#ffe6a3}.claim-badge.rejected{background:#583737;color:#ffd2cb}.claim-badge.completed{background:#334b5c;color:#d0e8ff}.claim-customer{font-weight:700;font-size:16px;margin:16px 0 0}.claim-vehicle{color:#b9d0c6;margin:0 0 14px}.claim-part{margin:0 0 14px}.claim-part small{display:block;color:#a6c4b9}.claim-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0 0 14px}.claim-stats span{display:block;color:#a6c4b9;font-size:12px}.claim-stats strong{font-size:14px}.claim-summary{margin:0 0 16px;min-width:0}.claim-summary small{display:block;color:#a6c4b9}.claim-summary-text{white-space:pre-wrap;overflow-wrap:anywhere;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}.claim-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:auto}.claim-actions button,.claim-actions a{width:auto;min-width:0}.claim-details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.claim-panel{background:#142e30;border:1px solid #35514d;border-radius:10px;padding:18px;min-width:0}.claim-panel h3{margin:0 0 12px;font-size:16px}.claim-panel p{white-space:pre-wrap;overflow-wrap:anywhere}.claim-panel label{display:block}.claim-panel textarea,.claim-panel select{width:100%;box-sizing:border-box}.claim-panel textarea{min-height:110px}.claim-panel .claim-confirm{display:flex;gap:9px;align-items:flex-start}.claim-panel .claim-confirm input{width:auto;margin-top:5px}.claim-panel button{margin-top:12px}.claim-details .claim-wide{grid-column:1/-1}.claim-dialog-notice{padding:12px;border:1px solid #a56c59;border-radius:8px;background:#412a25}.claim-dialog-notice ul{margin-bottom:0}@media(max-width:1200px){.claims-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.claims-grid,.claim-details{grid-template-columns:1fr}.claim-details .claim-wide{grid-column:auto}.claims-filter label,.claims-filter button{flex:1 1 100%}.claim-card{padding:15px}}
</style>
<div class="page-head"><div><h1>{{ __('Warranty Claims') }}</h1><p class="muted">{{ __('A claim is reviewed against the saved warranty conditions. Dates do not automatically approve coverage.') }}</p></div><a class="btn secondary" href="{{ route('ccs.warranties') }}">{{ __('Find Customer Warranty Card') }}</a></div>
@if($selected)
<section class="card"><h2>{{ __('New Warranty Claim') }} · {{ sprintf('CCS-W%06d',$selected->id) }}</h2><p>{{ $selected->customer_name }} · {{ $selected->registration_number }}</p><form method="POST" action="{{ route('ccs.claims.store') }}">@csrf<input type="hidden" name="warranty_id" value="{{ $selected->id }}"><label>{{ __('Received Date') }}<input name="received_on" type="date" required max="{{ today()->toDateString() }}" value="{{ old('received_on',today()->toDateString()) }}"></label><label>{{ __('Customer Problem') }}<textarea name="problem" required maxlength="5000">{{ old('problem') }}</textarea></label><button>{{ __('Save Claim') }}</button></form></section>
@endif
<section class="card"><form class="claims-filter" method="GET" action="{{ route('ccs.claims.index') }}"><label><span>{{ __('Search') }}</span><input name="q" value="{{ $q }}" maxlength="150" placeholder="Customer / phone / plate / serial"></label><label><span>{{ __('Status') }}</span><select name="status"><option value="">{{ __('All Statuses') }}</option>
@foreach(['pending','approved','rejected','completed'] as $status)
<option value="{{ $status }}" @selected(request('status')===$status)>{{ __(ucfirst($status)) }}</option>
@endforeach
</select></label><button>{{ __('Search') }}</button></form></section>
<p class="claims-count">{{ $claims->total() }} {{ __('Warranty Claims') }}</p>
<div class="claims-grid" data-v68-cards="claims">
@forelse($claims as $claim)
@php
$terms = json_decode($claim->terms_snapshot, true) ?? [];
$dialogId = 'claim-details-'.$claim->id;
$isOldClaim = (string) old('claim_dialog') === (string) $claim->id;
@endphp
<article class="claim-card">
 <div class="claim-card-header"><h2>Claim #{{ $claim->id }}</h2><span class="claim-badge {{ $claim->status }}">{{ __(ucfirst($claim->status)) }}</span></div>
 <p class="claim-customer">{{ $claim->customer_name }}</p><p class="claim-vehicle">{{ $claim->registration_number }} · {{ sprintf('CCS-W%06d',$claim->warranty_id) }}</p>
 <p class="claim-part"><small>{{ __('Part') }} / {{ __('Serial') }}</small><strong>{{ $terms['covered_description'] ?? '—' }}</strong><br>{{ $terms['covered_serial'] ?? '—' }}</p>
 <div class="claim-stats"><div><span>{{ __('Received Date') }}</span><strong>{{ $claim->received_on }}</strong></div><div><span>{{ __('Ends On') }}</span><strong>{{ $claim->expires_on ?? '—' }}</strong></div></div>
 <div class="claim-summary"><small>{{ __('Customer Problem') }}</small><div class="claim-summary-text">{{ $claim->problem }}</div></div>
 <div class="claim-actions"><button type="button" data-open-claim="{{ $dialogId }}">{{ __('View / Manage') }}</button>
 @if($claim->repair_job_id)
 <a class="btn secondary" href="{{ route('ccs.jobs.card',$claim->repair_job_id) }}">{{ __('Open Claim Repair Job') }}</a>
 @endif
 </div>
</article>
<dialog id="{{ $dialogId }}" class="editor-window" aria-labelledby="claim-title-{{ $claim->id }}">
 <header class="dialog-head"><div><h2 id="claim-title-{{ $claim->id }}">Claim #{{ $claim->id }} · {{ $claim->customer_name }}</h2><span class="muted">{{ $claim->registration_number }} · {{ sprintf('CCS-W%06d',$claim->warranty_id) }} · {{ __(ucfirst($claim->status)) }}</span></div><button type="button" class="secondary" data-close-claim>{{ __('Close') }}</button></header>
 <section class="claim-details">
 @if($isOldClaim && $errors->any())
 <div class="claim-dialog-notice claim-wide" role="alert"><strong>{{ __('Please correct:') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
 @endif
 <div class="claim-panel"><h3>{{ __('Customer Problem') }}</h3><p>{{ $claim->problem }}</p><p><strong>{{ __('Received Date') }}:</strong> {{ $claim->received_on }}<br><strong>{{ __('Ends On') }}:</strong> {{ $claim->expires_on ?? '—' }}</p><p><strong>{{ __('Part') }}:</strong> {{ $terms['covered_description'] ?? '—' }}<br><strong>{{ __('Serial') }}:</strong> {{ $terms['covered_serial'] ?? '—' }}</p></div>
 <div class="claim-panel"><h3>{{ __('Saved Warranty Conditions') }}</h3><p>{{ $terms['terms'] ?? '—' }}</p><p><strong>{{ __('Technician Findings') }}:</strong> {{ $claim->findings ?? '—' }}</p><p><strong>{{ __('Decision Notes') }}:</strong> {{ $claim->decision_notes ?? '—' }}</p></div>
 @if($claim->status==='pending')
 <div class="claim-panel"><h3>{{ __('Record Findings') }}</h3><form method="POST" action="{{ route('ccs.claims.update',$claim->id) }}">@csrf<input type="hidden" name="claim_dialog" value="{{ $claim->id }}"><label for="findings-{{ $claim->id }}">{{ __('Technician Findings') }}</label><textarea id="findings-{{ $claim->id }}" name="findings" required maxlength="5000">{{ $isOldClaim ? old('findings',$claim->findings) : $claim->findings }}</textarea><button>{{ __('Save Findings') }}</button></form></div>
 @endif
 @if(app(\App\Services\CcsAdministration::class)->owner(auth()->user()) && in_array($claim->status,['pending','approved'],true))
 <div class="claim-panel"><h3>{{ __('Owner Decision') }}</h3><form method="POST" action="{{ route('ccs.claims.decision',$claim->id) }}">@csrf<input type="hidden" name="claim_dialog" value="{{ $claim->id }}"><label for="decision-{{ $claim->id }}">{{ __('Status') }}</label><select id="decision-{{ $claim->id }}" name="status">
 @if($claim->status==='pending')
 <option value="approved" @selected($isOldClaim && old('status')==='approved')>{{ __('Approve') }}</option><option value="rejected" @selected($isOldClaim && old('status')==='rejected')>{{ __('Reject') }}</option>
 @else
 <option value="completed">{{ __('Complete Claim') }}</option>
 @endif
 </select><label for="decision-notes-{{ $claim->id }}">{{ __('Decision Notes') }}</label><textarea id="decision-notes-{{ $claim->id }}" name="decision_notes" required maxlength="5000" placeholder="Decision / conditions / outcome">{{ $isOldClaim ? old('decision_notes') : '' }}</textarea><label class="claim-confirm"><input type="checkbox" name="confirmation" value="1" required @checked($isOldClaim && old('confirmation'))>{{ __('Conditions reviewed and decision confirmed') }}</label><button>{{ __('Save Decision') }}</button></form></div>
 @endif
 <div class="claim-panel claim-wide"><h3>{{ __('Repair Job') }}</h3>
 @if($claim->repair_job_id)
 <a class="btn secondary" href="{{ route('ccs.jobs.card',$claim->repair_job_id) }}">{{ __('Open Claim Repair Job') }}</a>
 @elseif($claim->status==='approved')
 <form method="POST" action="{{ route('ccs.claims.job',$claim->id) }}">@csrf<button>{{ __('Create Claim Repair Job') }}</button></form>
 @else
 <p class="muted">{{ __('A repair job can be created after the claim is approved.') }}</p>
 @endif
 </div>
 </section>
</dialog>
@empty
<section class="card">{{ __('No matching claims. Start from a customer warranty card.') }}</section>
@endforelse
</div>
{{ $claims->links() }}
<script>
(function(){
 document.querySelectorAll('[data-open-claim]').forEach(function(button){button.addEventListener('click',function(){var dialog=document.getElementById(button.dataset.openClaim);if(dialog&&!dialog.open)dialog.showModal();});});
 document.querySelectorAll('[data-close-claim]').forEach(function(button){button.addEventListener('click',function(){var dialog=button.closest('dialog');if(dialog)dialog.close();});});
 @if($errors->any() && old('claim_dialog'))
 var retryId = @json('claim-details-'.old('claim_dialog'));
 var retryDialog = document.getElementById(retryId);
 if(retryDialog&&!retryDialog.open)retryDialog.showModal();
 @endif
})();
</script>
@endsection
