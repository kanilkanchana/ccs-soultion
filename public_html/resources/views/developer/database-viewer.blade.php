@extends('developer.layout')
@section('title','Database Explorer')
@section('content')
<style>.db-layout{display:grid;grid-template-columns:240px minmax(0,1fr);gap:18px}.db-tables{display:grid;gap:7px}.db-tables a{color:inherit;padding:7px 10px;text-decoration:none;border-radius:5px;overflow-wrap:anywhere}.db-tables a[aria-current=page]{background:#315d50}.db-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:end}.db-toolbar label{flex:1;min-width:130px;margin:0}.db-grid{overflow:auto;max-height:65vh;border:1px solid #31514d}.db-grid td{white-space:pre-wrap;min-width:120px;max-width:380px;word-break:break-word}.db-grid th{white-space:nowrap}.db-pages{display:flex;gap:20px;margin:16px 0}.db-pages a{color:#a1ecb7}@media(max-width:900px){.db-layout{grid-template-columns:1fr}.db-tables{display:none}}</style>
<div class="dev-head"><div><h1>Database Explorer</h1><span class="dev-muted">{{ $driver }} · Controlled access · 50 rows per page</span></div><a class="dev-btn dev-secondary" href="{{ request()->fullUrl() }}">Refresh</a></div>
<p class="dev-muted">Browse tables, structure and rows. Passwords, tokens, OTPs and stored session/configuration blobs are masked. Write actions are limited by table policy and your individual permissions. No SQL execution or schema editing.</p>
<div class="dev-actions" style="margin-bottom:18px">
@if($controlsReady)
<a class="dev-btn dev-secondary" href="{{ route('developer.database.history') }}">Change history</a>
@if($developer->can_manage_accounts??false)<a class="dev-btn dev-secondary" href="{{ route('developer.database.permissions') }}">Write permissions</a>@endif
@if($table==='ccs_website_requests'&&$permissions['add'])<a class="dev-btn" href="{{ route('developer.database.change',['action'=>'add']) }}">+ Add request</a>@endif
@else<p>Install the database-controls migration to enable permission management.</p>@endif
</div>
@if($table!=='ccs_website_requests')<p class="dev-muted">This table is read-only here. Use the existing Customers, Vehicles, Job Cards, POS, Payments, Inventory or Settings screens to maintain their related records.</p>@endif
<div class="db-layout"><aside class="dev-card"><h2>Tables ({{ $tables->count() }})</h2><nav class="db-tables">@foreach($tables as $name)<a href="{{ route('developer.database',['table'=>$name]) }}" @if($table===$name) aria-current="page" @endif>{{ $name }}</a>@endforeach</nav></aside>
<section style="min-width:0"><form class="dev-card db-toolbar" method="GET"><label>Table<select name="table" onchange="location.href='{{ route('developer.database') }}?table='+encodeURIComponent(this.value)">@foreach($tables as $name)<option @selected($table===$name)>{{ $name }}</option>@endforeach</select></label><label>Filter column<select name="column"><option value="">Select column</option>@foreach($columns as $c)@unless(\App\Http\Controllers\CcsDatabaseViewerController::sensitive($c['name']))<option value="{{ $c['name'] }}" @selected(request('column')===$c['name'])>{{ $c['name'] }}</option>@endunless
@endforeach</select></label><label>Contains<input name="q" value="{{ request('q') }}" maxlength="150"></label><button>Filter</button><a class="dev-btn dev-secondary" href="{{ route('developer.database',['table'=>$table]) }}">Reset</a></form>
<h2>{{ $table }} <small class="dev-muted">{{ $count }} matching rows</small></h2><div class="db-grid"><table><thead><tr>@if($table==='ccs_website_requests')<th>Actions</th>@endif
@foreach($columns as $c)<th>@if(!\App\Http\Controllers\CcsDatabaseViewerController::sensitive($c['name']))<a style="color:inherit" href="{{ request()->fullUrlWithQuery(['sort'=>$c['name'],'direction'=>request('direction')==='asc'?'desc':'asc','page'=>1]) }}">{{ $c['name'] }} ↕</a>@else{{ $c['name'] }} 🔒@endif</th>@endforeach</tr></thead><tbody>@if($rows)@forelse($rows as $row)<tr>
@if($table==='ccs_website_requests')<td>
@if(empty($row->repair_job_id)&&empty($row->customer_id)&&empty($row->vehicle_id)&&$row->status!=='converted')
@if($permissions['edit'])<a class="dev-btn dev-secondary" href="{{ route('developer.database.change',['action'=>'edit','id'=>$row->id]) }}">Edit</a>@endif
@if($permissions['delete'])<a class="dev-btn dev-secondary" href="{{ route('developer.database.change',['action'=>'delete','id'=>$row->id]) }}">Delete</a>@endif
@else<span>Linked — protected</span>@endif
</td>@endif
@foreach($columns as $c)<td>@if(\App\Http\Controllers\CcsDatabaseViewerController::sensitive($c['name']))[MASKED]@else<?php $value=$row->{$c['name']} ?? null; ?>{{ $value===null?'NULL':\Illuminate\Support\Str::limit((string)$value,1000) }}@endif</td>@endforeach</tr>@empty<tr><td colspan="{{ max(1,count($columns)) }}">No matching rows.</td></tr>@endforelse @else<tr><td>No readable columns.</td></tr>@endif</tbody></table></div>
@if($rows)<div class="db-pages">@if($rows->previousPageUrl())<a href="{{ $rows->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $rows->currentPage() }} / {{ $rows->lastPage() }}</span>@if($rows->nextPageUrl())<a href="{{ $rows->nextPageUrl() }}">Next →</a>@endif</div>@endif
<details class="dev-card"><summary>Table structure / columns</summary><table><thead><tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr></thead><tbody>@foreach($columns as $c)<tr><td>{{ $c['name'] }}</td><td>{{ $c['type'] }}</td><td>{{ $c['nullable']?'Yes':'No' }}</td><td>{{ \App\Http\Controllers\CcsDatabaseViewerController::sensitive($c['name'])?'[MASKED]':($c['default']===null?'NULL':$c['default']) }}</td></tr>@endforeach</tbody></table></details></section></div>
@endsection
