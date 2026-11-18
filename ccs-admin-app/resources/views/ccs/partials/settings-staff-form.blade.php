<header class="dialog-head"><h2>{{ $staff ? __('Edit User / Reset Password') : __('Add Staff User') }}</h2><button type="button" class="secondary" data-close-staff>{{ __('Close') }}</button></header>
<form method="POST" action="{{ route('ccs.settings.user-save') }}">@csrf
<input type="hidden" name="settings_staff_dialog" value="{{ $dialogName }}">
@if($staff)
<input type="hidden" name="id" value="{{ $staff->id }}">
@endif
@if($errors->any() && old('settings_staff_dialog')===$dialogName)
<div class="notice danger" role="alert"><strong>{{ __('Please correct:') }}</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<div class="stock-fields"><label>{{ __('Name') }}<input name="name" maxlength="150" required value="{{ old('settings_staff_dialog')===$dialogName ? old('name',$staff->name ?? '') : ($staff->name ?? '') }}"></label><label>Email<input name="email" type="email" required maxlength="255" value="{{ old('settings_staff_dialog')===$dialogName ? old('email',$staff->email ?? '') : ($staff->email ?? '') }}"></label><label>{{ __('Role') }}<select name="role">
@foreach(['cashier','technician','owner'] as $role)
<option value="{{ $role }}" @selected((old('settings_staff_dialog')===$dialogName ? old('role',$staff ? app(\App\Services\CcsAdministration::class)->role($staff) : 'cashier') : ($staff ? app(\App\Services\CcsAdministration::class)->role($staff) : 'cashier'))===$role)>{{ __($role==='cashier'?'Staff / Cashier':ucfirst($role)) }}</option>
@endforeach
</select></label><label><input name="active" value="1" type="checkbox" @checked(old('settings_staff_dialog')===$dialogName ? old('active') : ($staff ? $staff->ccs_active : true))> {{ __('Active') }}</label><label>{{ $staff ? __('New Password (leave blank to keep)') : __('Password') }}<input name="password" type="password" @if(!$staff) required @endif minlength="12" maxlength="128" autocomplete="new-password"></label><label>{{ __('Confirm Password') }}<input name="password_confirmation" type="password" @if(!$staff) required @endif minlength="12" maxlength="128" autocomplete="new-password"></label></div>
<p class="muted">{{ __('Staff / Cashier includes stock management. Technician has a separate jobs and available-parts view.') }}</p>
<div class="editor-footer"><button type="button" class="secondary" data-close-staff>{{ __('Close') }}</button><button>{{ __('Save User') }}</button></div>
</form>
