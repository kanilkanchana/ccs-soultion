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
@foreach(['cashier','technician','manager','owner','legacy'] as $role)
<option value="{{ $role }}" @selected((old('settings_staff_dialog')===$dialogName ? old('role',$staff ? app(\App\Services\CcsAdministration::class)->role($staff) : 'cashier') : ($staff ? app(\App\Services\CcsAdministration::class)->role($staff) : 'cashier'))===$role)>{{ __(ucfirst($role)) }}</option>
@endforeach
</select></label><label><input name="active" value="1" type="checkbox" @checked(old('settings_staff_dialog')===$dialogName ? old('active') : ($staff ? $staff->ccs_active : true))> {{ __('Active') }}</label><label>{{ $staff ? __('New Password (leave blank to keep)') : __('Password') }}<input name="password" type="password" @if(!$staff) required @endif minlength="12" maxlength="128" autocomplete="new-password"></label><label>{{ __('Confirm Password') }}<input name="password_confirmation" type="password" @if(!$staff) required @endif minlength="12" maxlength="128" autocomplete="new-password"></label></div>
<fieldset style="margin:18px 0;padding:14px;border:1px solid #526e65;border-radius:9px"><legend>{{ __('Cashier stock permissions') }}</legend><p class="muted">{{ __('Owner can enable only the stock actions needed for this cashier. Changes take effect after sign in again.') }}</p><input type="hidden" name="stock_permissions_present" value="1">
@php $staffStockPermissions=$staff?(app(\App\Services\CcsAdministration::class)->preferences($staff->id)['owner_permissions'] ?? []):[]; @endphp
@foreach(['stock_create'=>'Add new parts','stock_update'=>'Edit part details and prices','stock_receive'=>'Receive stock','stock_serial_manage'=>'Edit, remove and restore available serials'] as $permission=>$label)
<label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="permissions[{{ $permission }}]" value="1" style="width:auto" @checked(old('settings_staff_dialog')===$dialogName ? old('permissions.'.$permission) : ($staffStockPermissions[$permission] ?? false))> {{ __($label) }}</label>
@endforeach
<p class="muted">{{ __('These permissions apply to cashier accounts only. Backup, users and warranty decisions remain owner-only.') }}</p></fieldset>
<div class="editor-footer"><button type="button" class="secondary" data-close-staff>{{ __('Close') }}</button><button>{{ __('Save User') }}</button></div>
</form>
