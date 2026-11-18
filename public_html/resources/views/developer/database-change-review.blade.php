@extends('developer.layout')
@section('title','Review database change')
@section('content')
<h1>Review {{ $plan['action'] }} · ccs_website_requests</h1><p>Preview expires in 10 minutes. A full database backup is taken before applying. Concurrent changes invalidate this preview.</p>
<section class="dev-card"><p><strong>Reason:</strong> {{ $plan['reason'] }}</p><div class="dev-table-wrap"><table><thead><tr><th>Column</th><th>Before</th><th>After</th></tr></thead><tbody>
@foreach(array_unique(array_merge(array_keys($plan['before']??[]),array_keys($plan['after']??[]))) as $key)
@if($plan['action']==='delete'||array_key_exists($key,$plan['after']??[]))
<tr><td>{{ $key }}</td><td>{{ \Illuminate\Support\Str::limit((string)($plan['before'][$key]??'NULL'),4000) }}</td><td>{{ $plan['action']==='delete'?'[DELETED]':\Illuminate\Support\Str::limit((string)($plan['after'][$key]??'NULL'),4000) }}</td></tr>
@endif
@endforeach
</tbody></table></div></section>
<form class="dev-card" method="POST" action="{{ route('developer.database.apply') }}" autocomplete="off">@csrf<input type="hidden" name="operation_id" value="{{ $plan['operation_id'] }}"><label>Type <strong>{{ strtoupper($plan['action']).' '.($plan['row_id']??'NEW') }}</strong><input name="phrase" required data-input-english></label><label><input type="checkbox" name="confirm" value="1" required> I checked the change and authorize it.</label><label>Current developer password<input name="current_password" type="password" required autocomplete="current-password"></label><button>Back up & apply change</button> <a class="dev-btn dev-secondary" href="{{ route('developer.database',['table'=>'ccs_website_requests']) }}">Cancel</a></form>
@endsection
