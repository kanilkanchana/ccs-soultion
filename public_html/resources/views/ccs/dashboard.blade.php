@extends('ccs.layout')
@section('title',__('Dashboard'))
@section('content')
<!-- CCS_WEBSITE_DASH -->@include('ccs.partials.website-summary')
<style>.dash-panels{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin:16px 0}.dash-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.dash-metric{padding:12px;border:1px solid #315b52;border-radius:8px;min-width:0}.dash-metric strong{display:block;font-size:24px;margin-top:6px}.dash-alerts{display:grid;gap:8px;max-height:260px;overflow:auto}.dash-alert{display:block;padding:10px 12px;background:#19352f;border-radius:7px;color:#e8f9ed;text-decoration:none}.dash-alert small{display:block;color:#afcbbd;margin-top:4px}.dash-table{max-height:350px;overflow:auto}.dash-table th{position:sticky;top:0;background:#10292b}.dash-heading{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}</style>
<div class="page-head"><h1>{{ __('Workshop Dashboard') }}</h1></div>
<div class="grid">
@foreach($stats as $key=>$value)
<div class="card"><div class="muted">{{ __(ucfirst($key)) }}</div><div class="big">{{ $value }}</div></div>
@endforeach
</div>
<div class="dash-panels">
@if($canStock)
<section class="card"><div class="dash-heading"><h2>{{ __('Parts & Stock Overview') }}</h2><a class="btn secondary" href="{{ route('ccs.stock') }}">{{ __('View Stock') }}</a></div><div class="dash-metrics"><div class="dash-metric">{{ __('Active Parts') }}<strong>{{ $stockSummary->total ?? 0 }}</strong></div><div class="dash-metric">{{ __('Low Stock') }}<strong>{{ $stockSummary->low ?? 0 }}</strong></div><div class="dash-metric">{{ __('Out of Stock') }}<strong>{{ $stockSummary->empty ?? 0 }}</strong></div></div><p class="muted">{{ __('Low stock uses each part’s reorder level.') }}</p><a href="{{ route('ccs.stock',['low'=>1]) }}">{{ __('View Low Stock') }} →</a></section>
@endif
<section class="card"><div class="dash-heading"><h2>{{ __('Recent unread notifications') }} · {{ $notifications->count() }}</h2><a class="btn secondary" href="{{ route('ccs.notifications.index') }}">{{ __('View Notifications') }}</a></div><div class="dash-alerts">
@forelse($notifications->take(5) as $notice)
<a class="dash-alert" href="{{ $notice['url'] }}"><strong>{{ __($notice['title']) }}</strong><small>{{ $notice['text'] }}</small></a>
@empty
<p class="muted">{{ __('No unread notifications.') }}</p>
@endforelse
</div><p class="muted">{{ __('Showing up to five unread notifications based on your settings.') }}</p></section>
</div>
<section class="card"><div class="dash-heading"><h2>{{ __('Recent Repair Jobs') }}</h2><a class="btn secondary" href="{{ route('ccs.jobs') }}">{{ __('View Jobs') }}</a></div><div class="table-wrap dash-table"><table><thead><tr><th>{{ __('Job ID') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Plate') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
@forelse($recent as $job)
<tr><td><a href="{{ route('ccs.jobs',['job_id'=>$job->id]) }}">{{ $job->job_number }}</a></td><td>{{ $job->customer_name }}</td><td>{{ $job->registration_number }}</td><td><span class="status">{{ __(str_replace('_',' ',ucwords($job->status,'_'))) }}</span></td></tr>
@empty
<tr><td colspan="4">{{ __('No repair jobs yet. Add a customer, vehicle and job.') }}</td></tr>
@endforelse
</tbody></table></div></section>
<div class="actions"><a class="btn" href="{{ route('ccs.customers',['register'=>1]) }}">{{ __('Add Customer') }}</a><a class="btn secondary" href="{{ route('ccs.jobs',['new'=>1]) }}">{{ __('New Repair Job') }}</a>@if(app(\App\Services\CcsAdministration::class)->can(auth()->user(),'ccs.pos'))<a class="btn secondary" href="{{ route('ccs.pos') }}">{{ __('Create Invoice') }}</a>@endif</div>
@endsection
