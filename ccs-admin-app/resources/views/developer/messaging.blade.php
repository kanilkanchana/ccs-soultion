@extends('developer.layout')
@section('title','Messaging Controls')
@section('content')
<div class="dev-head"><h1>Messaging &amp; Customer Access</h1><a class="dev-btn dev-secondary" href="{{ route('developer.home') }}">Maintenance</a></div>
<div class="dev-notice">Local development only. API sending still uses the pinned Meta test sender and one verified TEST customer. Approval of templates is separate.</div>
<form method="POST" action="{{ route('developer.messaging.save') }}" autocomplete="off" data-input-english>@csrf
<div class="dev-grid"><section class="dev-card"><h2>WhatsApp controls</h2>
@foreach(['api_enabled'=>'Allow official WhatsApp API sending (master switch)','manual_enabled'=>'Show manual WhatsApp icon actions','auto_invoice'=>'Allow automatic invoice + PDF','auto_payment'=>'Allow automatic payment confirmations','auto_status'=>'Allow automatic repair assignment / status updates','auto_serial'=>'Allow automatic serial / warranty messages'] as $key=>$label)
<label class="dev-check"><input type="checkbox" name="{{ $key }}" value="1" @checked($flags[$key])><span>{{ $label }}</span></label>
@endforeach
<p class="dev-muted">Automatic messages also require the owner Auto-send switch, matching templates and API master ON. Manual app/Web sharing needs staff to press Send; no API call is made.</p></section>
<section class="dev-card dev-danger"><h2>Customer lookup</h2><label class="dev-check"><input type="checkbox" name="direct_lookup" value="1" @checked($flags['direct_lookup'])><span>Skip OTP: open TEST customer records directly by vehicle number</span></label>
<p>Anyone using this local page with the plate can see that TEST customer's vehicles, invoice balances, serials and warranty data. This does not verify identity. Public production access is not enabled by this update.</p>
<label class="dev-check"><input type="checkbox" name="ack_direct" value="1"><span>I understand and authorize direct access to the configured TEST data.</span></label>
<p class="dev-muted">OFF = WhatsApp OTP required. API OFF + direct lookup ON works without a token. Changing controls revokes existing customer grants.</p></section></div>
<section class="dev-card"><label>Current developer password<input type="password" name="current_password" required autocomplete="current-password"></label><button>Save developer controls</button></section></form>
@endsection
