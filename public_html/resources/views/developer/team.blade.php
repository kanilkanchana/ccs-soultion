@extends('developer.layout')
@section('title','Developer Team')
@section('content')
<div class="dev-head"><div><h1>Developer Team</h1><p class="dev-muted">Create a separate account for each of your three team members.</p></div><button onclick="document.getElementById('new-member').showModal()">+ Add Developer</button></div>
<section class="dev-card"><div class="dev-table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Login</th><th>Data maintenance</th><th>Action</th></tr></thead><tbody>
@foreach($accounts as $member)<tr><td>{{ $member->name }}@if($member->can_manage_accounts)<br><small>Team administrator</small>@endif</td><td>{{ $member->email }}</td><td>{{ $member->active ? 'Enabled' : 'Disabled' }}</td><td>{{ $member->can_clear_data ? 'Allowed' : 'Restricted' }}</td><td>@if(!$member->can_manage_accounts && $member->id !== $developer->id)<button class="dev-secondary" onclick="document.getElementById('member-{{ $member->id }}').showModal()">Edit / Reset / Disable</button>@else<span class="dev-muted">Server terminal manages this account</span>@endif</td></tr>@endforeach
</tbody></table></div></section>
<dialog id="new-member" class="dev-dialog">@include('developer.partials.member-form',['member'=>null])</dialog>
@foreach($accounts as $member)@if(!$member->can_manage_accounts && $member->id !== $developer->id)<dialog id="member-{{ $member->id }}" class="dev-dialog">@include('developer.partials.member-form',['member'=>$member])</dialog>@endif
@endforeach
@if($errors->any())<p class="dev-muted">Open the member form again to retry. Password fields are cleared after validation errors.</p>@endif
@endsection
