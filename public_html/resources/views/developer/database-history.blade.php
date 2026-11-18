@extends('developer.layout')
@section('title','Database change history')
@section('content')
<div class="dev-head"><h1>Database change history</h1><a class="dev-btn dev-secondary" href="{{ route('developer.database') }}">Database Explorer</a></div><p>Before/after values are accessible only through the developer portal. This page provides no edit/delete action for audit records.</p>
@forelse($events as $event)
<section class="dev-card"><h2>{{ $event->action }} · {{ $event->table_name }} #{{ $event->row_id }}</h2><p>{{ $event->developer_name??'Developer #'.$event->developer_account_id }} · {{ $event->created_at }} UTC</p><p>{{ $event->reason }}</p><p>Safety backup: {{ $event->backup_id??'Not applicable — permission change' }}</p><details><summary>Before / after</summary><div class="dev-grid"><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $event->before_json??'NULL' }}</pre><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $event->after_json??'NULL' }}</pre></div></details></section>
@empty<p>No database changes recorded.</p>@endforelse
<div class="dev-actions">@if($events->previousPageUrl())<a class="dev-btn" href="{{ $events->previousPageUrl() }}">Previous</a>@endif<span>Page {{ $events->currentPage() }}</span>@if($events->nextPageUrl())<a class="dev-btn" href="{{ $events->nextPageUrl() }}">Next</a>@endif</div>
@endsection
