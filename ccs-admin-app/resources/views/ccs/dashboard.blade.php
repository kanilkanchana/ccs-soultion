@extends('ccs.layout')
@section('title',__('Dashboard'))
@section('content')
@include('ccs.partials.website-summary')
@php
    $statusCounts = collect($recent ?? [])->groupBy(fn($job) => $job->status ?: 'unknown')->map->count();
    $statusLabel = fn($status) => __(str_replace('_',' ',ucwords($status,'_')));
    $statusDisplay = $statusCounts->sortDesc()->take(4);
    $maxStatus = max(1, (int) $statusDisplay->max());
    $metricIcons = ['customers' => '👥', 'vehicles' => '🚘', 'jobs' => '🛠', 'invoices' => '🧾'];
@endphp
<div class="pm-dashboard">
    <section class="pm-hero">
        <span class="pm-shape s1"></span><span class="pm-shape s2"></span><span class="pm-shape s3"></span>
        <div class="pm-hero-copy">
            <span class="pm-label">CCS Solutions / Workshop Control Center</span>
            <h1>{{ __('Welcome back, :name.', ['name' => auth('web')->user()?->name ?? 'ccsadmin']) }}</h1>
            <p>{{ __('Your workshop overview is ready. Manage customer vehicles, repair jobs, stock and invoices in one polished workspace.') }}</p>
            <div class="pm-actions">
                <a class="btn" href="{{ route('ccs.jobs',['new'=>1]) }}">{{ __('New Repair Job') }} ↗</a>
                <a class="btn secondary" href="{{ route('ccs.customers',['register'=>1]) }}">{{ __('Add Customer') }}</a>
                @if(app(
                    \App\Services\CcsAdministration::class
                )->can(auth()->user(),'ccs.pos'))
                    <a class="btn secondary" href="{{ route('ccs.pos') }}">{{ __('Create Invoice') }}</a>
                @endif
            </div>
        </div>
        <div class="pm-hero-visual">
            <div class="pm-car-panel">
                <img class="pm-car" src="{{ asset('ccs-care/hero-poster.jpg') }}" alt="CCS vehicle visual">
                <div class="pm-car-overlay"></div>
                <img class="pm-logo-mark" src="{{ asset('ccs-ui/ccs-logo-premium.png') }}" alt="CCS Solutions logo">
            </div>
        </div>
    </section>

    <section class="pm-kpis">
        @foreach($stats as $key=>$value)
            <article class="pm-metric">
                <div class="pm-metric-top">
                    <div>
                        <span class="eyebrow">{{ __(ucfirst($key)) }}</span>
                        <strong>{{ $value }}</strong>
                        <small>{{ __('Current totals from the workshop database') }}</small>
                    </div>
                    <span class="pm-icon">{{ $metricIcons[$key] ?? '•' }}</span>
                </div>
            </article>
        @endforeach
    </section>

    <section class="pm-content-grid">
        <div class="pm-stack">
            <section class="pm-card">
                <div class="pm-card-head">
                    <div>
                        <h2>{{ __('Recent Repair Jobs') }}</h2>
                        <p>{{ __('Latest workshop job records') }}</p>
                    </div>
                    <a class="btn secondary" href="{{ route('ccs.jobs') }}">{{ __('View Jobs') }} ↗</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Job ID') }}</th>
                                <th>{{ __('Customer') }}</th>
                                <th>{{ __('Plate') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($recent as $job)
                            <tr>
                                <td><a href="{{ route('ccs.jobs',['job_id'=>$job->id]) }}">{{ $job->job_number }}</a></td>
                                <td>{{ $job->customer_name }}</td>
                                <td>{{ $job->registration_number }}</td>
                                <td><span class="pm-status-badge">{{ $statusLabel($job->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="pm-empty">{{ __('No repair jobs yet. Add a customer, vehicle and job.') }}</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="pm-card pm-art-card">
                <div class="pm-art">
                    <img src="{{ asset('ccs-care/hero-poster.jpg') }}" alt="Hybrid repair vehicle">
                    <div class="pm-art-content">
                        <span class="pm-label">{{ __('Hybrid Diagnostics') }}</span>
                        <h3>{{ __('Premium workshop command center') }}</h3>
                        <p>{{ __('Track customers, job progress, battery service, stock movement and invoice activity from a clearer dashboard experience.') }}</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="pm-stack">
            <section class="pm-card">
                <div class="pm-card-head">
                    <div>
                        <h2>{{ __('Recent job status') }}</h2>
                        <p>{{ __('Distribution from the latest :count repair jobs — not all historical jobs.', ['count' => count($recent ?? [])]) }}</p>
                    </div>
                </div>
                <div class="pm-status-grid">
                    <div class="pm-pill"><small>{{ __('Website Requests') }}</small><strong>{{ 
                        
                        \Illuminate\Support\Facades\Route::has('ccs.website.index') && \Illuminate\Support\Facades\Schema::hasTable('ccs_website_requests') ? \Illuminate\Support\Facades\DB::table('ccs_website_requests')->where('status','new')->count() : 0
                    }}</strong></div>
                    <div class="pm-pill"><small>{{ __('Unread Notifications') }}</small><strong>{{ $notifications->count() }}</strong></div>
                </div>
                <div class="pm-status-bars">
                    @forelse($statusDisplay as $status => $count)
                        <div class="pm-bar-row">
                            <span>{{ $statusLabel($status) }}</span>
                            <div class="pm-bar"><span style="width: {{ round(($count / $maxStatus) * 100) }}%"></span></div>
                            <strong>{{ $count }}</strong>
                        </div>
                    @empty
                        <div class="pm-empty">{{ __('No status summary is available yet.') }}</div>
                    @endforelse
                </div>
            </section>

            <section class="pm-card">
                <div class="pm-card-head">
                    <div>
                        <h2>{{ __('Notifications') }}</h2>
                        <p>{{ __('Unread items requiring attention') }}</p>
                    </div>
                    <a class="btn secondary" href="{{ route('ccs.notifications.index') }}">{{ __('View Notifications') }}</a>
                </div>
                <div class="pm-notifications">
                    @forelse($notifications->take(4) as $notice)
                        <a class="pm-note" href="{{ $notice['url'] }}">
                            <strong>{{ __($notice['title']) }}</strong>
                            <small>{{ $notice['text'] }}</small>
                        </a>
                    @empty
                        <div class="pm-empty">{{ __('No unread notifications.') }}</div>
                    @endforelse
                </div>
            </section>

            @if($canStock)
            <section class="pm-card">
                <div class="pm-card-head">
                    <div>
                        <h2>{{ __('Parts & Inventory') }}</h2>
                        <p>{{ __('Quick stock health snapshot') }}</p>
                    </div>
                    <a class="btn secondary" href="{{ route('ccs.stock') }}">{{ __('View Stock') }}</a>
                </div>
                <div class="pm-stock-grid">
                    <div class="pm-stock"><small>{{ __('Active Parts') }}</small><strong>{{ $stockSummary->total ?? 0 }}</strong></div>
                    <div class="pm-stock"><small>{{ __('Low Stock') }}</small><strong>{{ $stockSummary->low ?? 0 }}</strong></div>
                    <div class="pm-stock"><small>{{ __('Out of Stock') }}</small><strong>{{ $stockSummary->empty ?? 0 }}</strong></div>
                </div>
                <div class="pm-insight" style="margin-top:14px">
                    <div class="pm-helper">
                        <div>
                            <strong>{{ __('Reorder attention') }}</strong>
                            <small>{{ __('Low stock uses each part\'s reorder level to help the team restock on time.') }}</small>
                        </div>
                        <a href="{{ route('ccs.stock',['low'=>1]) }}">{{ __('View Low Stock') }} →</a>
                    </div>
                </div>
            </section>
            @endif
        </div>
    </section>
</div>
@endsection
