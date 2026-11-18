@extends('ccs.layout')
@section('title',__('Users & Roles'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
<div class="directory-head"><h1>{{ __('Users & Roles') }}</h1><a class="btn secondary" href="{{ route('ccs.settings') }}">{{ __('System Settings') }}</a></div><p class="muted">Owner: full settings and approvals. Manager: daily operations. Cashier: sales/payments and stock lookup. Technician: jobs/customer records and claim findings. Legacy: existing staff access, with owner-only settings protected.</p>
<section class="card"><h2>{{ __('Add Staff User') }}</h2><form method="POST" action="{{ route('ccs.settings.user-save') }}">@csrf
<div class="directory-fields"><label>{{ __('Name') }}<input name="name" maxlength="150" required></label><label>Email<input name="email" type="email" required maxlength="255"></label><label>{{ __('Role') }}<select name="role"><option value="cashier">Staff / Cashier</option><option value="technician">Technician</option><option value="owner">Owner</option></select></label><label>{{ __('Active') }}<input name="active" value="1" type="checkbox" checked style="width:auto"></label><label>{{ __('Password') }}<input name="password" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></label><label>{{ __('Confirm Password') }}<input name="password_confirmation" type="password" required minlength="12" maxlength="128" autocomplete="new-password"></label></div><button>{{ __('Save User') }}</button></form></section>
<div class="directory-panel"><div class="directory-scroll"><table class="directory-table"><thead><tr><th>{{ __('Name') }}</th><th>Email</th><th>{{ __('Role') }}</th><th>{{ __('Status') }}</th><th>{{ __('Edit') }}</th></tr></thead><tbody>
@foreach($users as $staff)
<tr><td>{{ $staff->name }}</td><td>{{ $staff->email }}</td><td>{{ __(ucfirst(app(\App\Services\CcsAdministration::class)->role($staff))) }}</td><td>{{ __($staff->ccs_active?'Active':'Disabled') }}</td><td><details><summary>{{ __('Edit User / Reset Password') }}</summary><form method="POST" action="{{ route('ccs.settings.user-save') }}">@csrf<input type="hidden" name="id" value="{{ $staff->id }}"><label>{{ __('Name') }}<input name="name" required value="{{ $staff->name }}"></label><label>Email<input name="email" type="email" required value="{{ $staff->email }}"></label><label>{{ __('Role') }}<select name="role">
@foreach(['owner','cashier','technician'] as $role)
<option value="{{ $role }}" @selected(app(\App\Services\CcsAdministration::class)->role($staff)===$role)>{{ __($role==='cashier'?'Staff / Cashier':ucfirst($role)) }}</option>
@endforeach
</select></label><label><input name="active" type="checkbox" value="1" @checked($staff->ccs_active) style="width:auto"> {{ __('Active') }}</label><label>{{ __('New Password (leave blank to keep)') }}<input name="password" type="password" minlength="12" maxlength="128" autocomplete="new-password"></label><label>{{ __('Confirm Password') }}<input name="password_confirmation" type="password" minlength="12" maxlength="128" autocomplete="new-password"></label><button>{{ __('Save User') }}</button></form></details></td></tr>
@endforeach
</tbody></table></div><div class="directory-footer">{{ $users->links() }}</div></div>
@endsection
