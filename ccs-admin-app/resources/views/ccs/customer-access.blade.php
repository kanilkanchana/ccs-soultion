@extends('ccs.layout')
@section('title',__('Customer Portal Access'))
@section('content')
<div class="page-head"><h1>{{ __('Customer Portal Access') }}</h1><a class="btn secondary" href="{{ route('ccs.settings') }}">{{ __('System Settings') }}</a></div>
<p><a class="btn secondary" href="{{ route('ccs.customer-access.serial-preview') }}">Test Serial Lookup / Serial records පරීක්ෂා කරන්න →</a></p>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr));align-items:start">
<section class="card"><h2>{{ __('Create / Reset Customer Login') }}</h2>
<p class="muted">{{ __('Choose the customer and set a username and password. Saving resets their password and signs out old sessions.') }}</p>
<form method="POST" action="{{ route('ccs.customer-access.save') }}">@csrf
<label for="accessCustomer">{{ __('Select Customer') }}</label>
<select id="accessCustomer" name="customer_id" required><option value="">{{ __('Choose a customer') }}</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" data-code="{{ strtolower($customer->customer_code) }}" @selected(old('customer_id')==$customer->id)>{{ $customer->name }} · {{ $customer->phone }} · {{ $customer->customer_code }}</option>@endforeach</select>
<label for="accessUsername">{{ __('Username') }}</label><input id="accessUsername" name="username" required minlength="3" maxlength="80" pattern="[a-z0-9._\-]+" value="{{ old('username') }}" autocomplete="off"><small>a-z, 0-9, dot, underscore or hyphen. No spaces.</small>
<label for="accessPassword">{{ __('Password') }} (12+ characters)</label><input id="accessPassword" type="password" name="password" required minlength="12" maxlength="200" autocomplete="new-password">
<label for="accessConfirm">{{ __('Confirm Password') }}</label><input id="accessConfirm" type="password" name="password_confirmation" required minlength="12" maxlength="200" autocomplete="new-password">
<button type="submit" style="margin-top:18px">{{ __('Save Customer Access') }}</button>
</form>
<p class="muted">{{ __('Give login details to the customer privately. Passwords are never displayed after saving.') }}</p>
<p><a href="{{ route('customer.login') }}" target="_blank" rel="noopener">{{ __('Open Customer Login') }} →</a></p>
</section>
<section class="card"><h2>{{ __('Existing Customer Access') }}</h2><div class="table-wrap" style="max-height:65vh;overflow:auto"><table><thead><tr><th>{{ __('Customer') }}</th><th>{{ __('Username') }}</th><th>{{ __('Status') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
@forelse($accounts as $account)<tr><td>{{ $account->name }}<br><small>{{ $account->phone }}</small></td><td>{{ $account->username }}</td><td>{{ __($account->active?'Active':'Stopped') }}</td><td>@if($account->active)
<button type="button" class="secondary portal-whatsapp" data-phone="{{ $account->phone }}" data-name="{{ $account->name }}" style="margin-bottom:8px">WhatsApp Service Link</button>
<form method="POST" action="{{ route('ccs.customer-access.revoke',$account->id) }}">@csrf<button class="secondary" type="submit" onclick="return confirm('Stop this customer login?')">{{ __('Stop Access') }}</button></form>@else<small>{{ __('Reset the password to enable access again.') }}</small>@endif</td></tr>@empty<tr><td colspan="4">{{ __('No customer logins yet.') }}</td></tr>@endforelse
</tbody></table></div></section>
</div>
<script>
(()=>{
 const portalLink={{ Illuminate\Support\Js::from(route('customer.home')) }};
 document.querySelectorAll('.portal-whatsapp').forEach(button=>button.addEventListener('click',()=>{
  const host=new URL(portalLink).hostname;
  if(['localhost','127.0.0.1','::1','[::1]'].includes(host)){alert('This is a local test link. Host the system with HTTPS before sharing it with customers.');return;}
  let phone=(button.dataset.phone||'').replace(/\D/g,'');if(phone.startsWith('00'))phone=phone.slice(2);if(/^0[0-9]{9}$/.test(phone))phone='94'+phone.slice(1);
  if(!/^[1-9][0-9]{7,14}$/.test(phone)){alert('Please correct this customer phone number, including the country code.');return;}
  const message='Hello '+button.dataset.name+', your CCS Solutions service book is available here: '+portalLink+'\nSign in with your customer login to view your vehicle service history, serial numbers and approved warranty details. Contact the workshop if you need login access.';
  window.open('https://wa.me/'+phone+'?text='+encodeURIComponent(message),'_blank','noopener,noreferrer');
 }));
 const usernames={{ Illuminate\Support\Js::from($accounts->pluck('username','customer_id')) }};document.getElementById('accessCustomer').addEventListener('change',function(){document.getElementById('accessUsername').value=usernames[this.value]||this.selectedOptions[0]?.dataset.code||'';document.getElementById('accessPassword').value='';document.getElementById('accessConfirm').value='';});})();
</script>
@endsection
