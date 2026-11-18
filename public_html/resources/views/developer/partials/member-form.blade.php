<form method="POST" action="{{ route('developer.team.save') }}">@csrf
<header><h2>{{ $member ? 'Edit Developer Member' : 'Add Developer Member' }}</h2><button type="button" class="dev-secondary" onclick="this.closest('dialog').close()">Close</button></header>
<div class="dev-body">
@if($member)<input type="hidden" name="id" value="{{ $member->id }}">@endif
<div class="dev-grid"><label>Member name<input name="name" required maxlength="150" value="{{ $member->name ?? '' }}" autocomplete="off"></label><label>Developer email<input type="email" name="email" required maxlength="255" value="{{ $member->email ?? '' }}" autocomplete="off"></label><label>{{ $member ? 'New password (leave blank to keep current)' : 'New private password (16–64 characters)' }}<input type="password" name="password" autocomplete="new-password" minlength="16" maxlength="64" @if(!$member) required @endif></label><label>Repeat new password<input type="password" name="password_confirmation" autocomplete="new-password" minlength="16" maxlength="64" @if(!$member) required @endif></label></div>
<label class="dev-check"><input type="checkbox" name="active" value="1" @checked($member ? $member->active : true)> Enable this member's developer login</label>
<label class="dev-check"><input type="checkbox" name="can_clear_data" value="1" @checked($member && $member->can_clear_data)> Allow business data clearing, test imports and database backups</label>
<p class="dev-muted">Members cannot create other developer accounts. Each person gets a separate login and activity history. Clearing permission also allows access to full database backups.</p>
<label>Your current developer password<input type="password" name="current_password" required autocomplete="current-password"></label>
</div><footer><span class="dev-muted">Saving revokes this member's old sessions and reviews.</span><button>Save Developer Member</button></footer></form>
