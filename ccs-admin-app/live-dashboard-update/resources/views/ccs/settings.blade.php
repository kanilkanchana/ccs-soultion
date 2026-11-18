@extends('ccs.layout')
@section('title',__('System Settings'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
@include('ccs.partials.editor-styles')
<style>
.settings-layout{display:grid;grid-template-columns:240px minmax(0,1fr);gap:24px;align-items:start;min-height:calc(100dvh - 150px)}.settings-nav{position:sticky;top:18px;min-width:0;background:#151d26;border:1px solid #303b49;border-radius:14px;padding:16px;min-height:calc(100dvh - 170px)}.settings-nav h2{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:#9ebfb0;padding:6px 10px;margin:0}.settings-nav a{display:flex;align-items:center;gap:10px;padding:10px;border-radius:7px;color:#cee6da;font-size:13px;line-height:1.4}.settings-nav a:hover,.settings-nav a:focus-visible,.settings-nav a.is-active{background:#28363d;color:#eaffed}.settings-nav a.is-active{box-shadow:inset 3px 0 #f7c947}.settings-nav .settings-brand{display:block;padding:8px 10px 16px;border-bottom:1px solid #314047;margin-bottom:12px;color:#f7d25d;font-weight:800}.settings-nav .settings-brand small{display:block;color:#a9bdbc;font-size:11px;font-weight:400}.settings-nav .settings-icon{width:22px;text-align:center;color:#a9babc;flex:none}.settings-nav a.is-active .settings-icon{color:#f7c947}.settings-nav hr{border:0;border-top:1px solid #31514c;margin:10px 0}.settings-main{min-width:0;display:grid;gap:16px}.settings-main .card{margin:0;scroll-margin-top:18px;background:#151d26;border-color:#303b49}.settings-main [hidden]{display:none!important}.settings-main .settings-card-title{margin:0 0 4px;font-size:20px}.settings-main .settings-card-subtitle{color:#a9bdbc;margin:0 0 18px}.settings-preferences-panel:not([hidden]){display:block}.settings-nojs .settings-preferences-panel{display:block}.settings-main h2{font-size:19px;margin-top:0}.settings-main h3{font-size:16px;margin:0 0 8px}.settings-main input[type=checkbox]{width:auto}.settings-main label{margin-top:12px}.settings-main button{margin-top:12px}.settings-content{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 18px}.settings-content label{margin:0}.settings-subsection{padding:18px 0;border-top:1px solid #31514c;margin-top:20px;scroll-margin-top:18px}.settings-note{max-width:75ch}.settings-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 16px}.settings-checks label{display:flex;gap:9px;align-items:center;margin:0}.settings-checks input{flex-shrink:0}.settings-inline-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}.settings-inline-actions button{width:auto}@media(max-width:900px){.settings-layout{grid-template-columns:1fr}.settings-layout{min-height:0}.settings-nav{position:static;display:flex;gap:6px;overflow-x:auto;align-items:center;min-height:0}.settings-nav .settings-brand{display:none}.settings-nav h2,.settings-nav hr{display:none}.settings-nav a{white-space:nowrap;flex-shrink:0;background:#173531}.settings-content,.settings-checks{grid-template-columns:1fr}}
</style>
<div class="directory-head"><div><h1>{{ __('System Settings') }}</h1><p>{{ __('Manage staff, security, notifications, languages and printing.') }}</p></div></div>
<div class="settings-layout">
<nav class="settings-nav" aria-label="{{ __('Settings Categories') }}">
<span class="settings-brand">CCS SOLUTIONS<small>{{ __('System Settings') }}</small></span>
<h2>{{ __('Management') }}</h2>
<!-- CCS_WEBSITE_SETTINGS_LINK -->@if(\Illuminate\Support\Facades\Route::has('ccs.settings.website.index') && $owner)<a href="{{ route('ccs.settings.website.index') }}"><span class="settings-icon">↗</span>Website Connection</a>@endif
@if($owner)
@if(\Illuminate\Support\Facades\Route::has('ccs.settings.whatsapp.index'))<a href="{{ route('ccs.settings.whatsapp.index') }}"><span class="settings-icon">◉</span>WhatsApp API &amp; Messages</a>@endif
<a href="#settings-users" data-settings-tab="settings-users"><span class="settings-icon">♙</span>{{ __('Users & Roles') }}</a>
@endif
<a href="#settings-security" data-settings-tab="settings-security"><span class="settings-icon">◇</span>{{ __('Security') }}</a>
<h2>{{ __('System') }}</h2>
<a href="#settings-theme" data-settings-tab="settings-theme"><span class="settings-icon">◐</span>{{ __('Appearance & Themes') }}</a>
<a href="#settings-language" data-settings-tab="settings-language"><span class="settings-icon">🌐</span>{{ __('System Language') }}</a>
<a href="#settings-keyboard" data-settings-tab="settings-keyboard"><span class="settings-icon">⌨</span>{{ __('Keyboard Input Method') }}</a>
<a href="#settings-scanner" data-settings-tab="settings-scanner"><span class="settings-icon">▦</span>{{ __('Scanner Input') }}</a>
<a href="#settings-printing" data-settings-tab="settings-printing"><span class="settings-icon">▣</span>{{ __('Printing & Labels') }}</a>
<a href="#settings-notifications" data-settings-tab="settings-notifications"><span class="settings-icon">◉</span>{{ __('In-App Notifications') }}</a>
<hr><h2>{{ __('Management') }}</h2><a href="{{ route('ccs.notifications.index') }}"><span class="settings-icon">◉</span>{{ __('Notifications') }}</a>
@if($owner)
<a href="{{ route('ccs.settings.backups') }}"><span class="settings-icon">▣</span>{{ __('Backup & Restore') }}</a><a href="{{ route('ccs.settings.audit') }}"><span class="settings-icon">☷</span>{{ __('Audit Log') }}</a>
@endif
</nav>
<div class="settings-main settings-nojs" id="settingsMain">
@if($owner)
@include('ccs.partials.settings-staff')
@endif
<section class="card" id="settings-theme" data-settings-pane><h2>{{ __('Appearance & Themes') }}</h2><p class="muted settings-note">{{ __('Choose a theme for your staff account. Preview it, then save.') }}</p><form method="POST" action="{{ route('ccs.settings.theme') }}">@csrf<label for="themeSelect">{{ __('Theme') }}</label><select id="themeSelect" name="theme">
@foreach(['default'=>'CCS Default','default2'=>'CCS Default 2 · Yellow / Midnight','light'=>'Light','dark'=>'Dark','skyline'=>'Skyline','vegan'=>'Vegan','neon'=>'Neon','eggplant'=>'Eggplant','nikita'=>'Nikita','desert'=>'Desert','aqua'=>'Aqua'] as $themeKey=>$themeLabel)
<option value="{{ $themeKey }}" @selected(($preferences['theme'] ?? 'default')===$themeKey)>{{ $themeLabel }}</option>
@endforeach
</select><div class="v71-accent-settings"><label><input type="hidden" name="custom_accent" value="0"><input type="checkbox" id="customAccent" name="custom_accent" value="1" @checked($preferences['custom_accent']??false)> {{ __('Custom button colour') }}</label><label for="accentColor">{{ __('Button colour') }}</label><input id="accentColor" type="color" name="accent_color" value="{{ preg_match('/^#[0-9a-fA-F]{6}$/',$preferences['accent_color']??'')?$preferences['accent_color']:'#FACD29' }}"><label for="accentBrightness">{{ __('Brightness') }} <output id="accentOutput">{{ (int)($preferences['accent_brightness']??80) }}%</output></label><input id="accentBrightness" type="range" name="accent_brightness" min="40" max="100" value="{{ (int)($preferences['accent_brightness']??80) }}"><small>{{ __('Preview, then save. Applies to your account only.') }}</small></div><div class="settings-inline-actions"><button type="submit">{{ __('Save Theme') }}</button><button type="button" class="secondary" id="resetThemePreview">{{ __('Cancel Preview') }}</button></div></form><p class="muted">{{ __('Theme affects the staff workspace; invoices and printed documents keep their print layout.') }}</p></section>
<script>(()=>{const s=document.getElementById('themeSelect'),initial=document.documentElement.dataset.ccsTheme;s.addEventListener('change',()=>{document.documentElement.dataset.ccsTheme=s.value});document.getElementById('resetThemePreview').addEventListener('click',()=>{s.value=initial;document.documentElement.dataset.ccsTheme=initial})})();</script>
<section class="card" id="settings-language" data-settings-pane><h2>{{ __('System Language') }}</h2><p class="muted settings-note">{{ __('Choose the language used for system labels and buttons.') }}</p><form method="POST" action="{{ route('ccs.settings.language') }}">@csrf<label for="system-language">{{ __('System Language') }}</label><select id="system-language" name="language">
@foreach(config('ccs_admin.locales') as $code=>$label)
<option value="{{ $code }}" @selected(app()->getLocale()===$code)>{{ $label }}</option>
@endforeach
</select><button>{{ __('Save Language') }}</button></form><p class="muted">{{ __('Customer details, actual serials and saved warranty terms are not translated.') }}</p></section>
<section class="card" id="settings-keyboard" data-settings-pane><h2>{{ __('Keyboard Input Method') }}</h2><p class="muted settings-note">{{ __('Choose how you type. This is separate from the system and invoice languages.') }}</p><form method="POST" action="{{ route('ccs.settings.input-method') }}">@csrf<label for="settings-input-method">{{ __('Input Method') }}</label><select id="settings-input-method" name="input_method" data-input-setting>
@foreach(['english'=>'English','phonetic'=>'Sinhala Phonetic','wijesekara'=>'Sinhala Wijesekara'] as $code=>$label)
<option value="{{ $code }}" @selected($preferences['input_method']===$code)>{{ __($label) }}</option>
@endforeach
</select><div class="settings-inline-actions"><button>{{ __('Save Input Method') }}</button><button type="button" class="secondary" data-open-phonetic-guide>{{ __('View Phonetic Guide') }}</button></div></form><label for="input-practice">{{ __('Typing practice') }}</label><input id="input-practice" type="text" data-sinhala-input="true" autocomplete="off" placeholder="ka · kA · kAa · kya"><p class="muted">{{ __('Serials, barcodes, passwords, phone numbers and amounts stay in English. Use English mode for scanner searches.') }}</p></section>
<section class="card" id="settings-security" data-settings-pane><h2>{{ __('Security') }}</h2><p>{{ __('Login attempts are limited. Disabled accounts and revoked sessions cannot access staff pages.') }}</p><a class="btn secondary" href="{{ route('profile.edit') }}">{{ __('Change My Password') }}</a>
@if($owner)
<form method="POST" action="{{ route('ccs.settings.security') }}">@csrf<label>{{ __('Automatic Logout After Inactivity (minutes)') }}<input name="idle_minutes" type="number" min="15" max="240" required value="{{ $idle }}"></label><button>{{ __('Save Security') }}</button></form>
@else
<p class="muted">{{ __('Only an owner can change security settings or staff roles.') }}</p>
@endif
</section>
<section class="card" id="settings-preferences" data-settings-preferences><h2>{{ __('Scanner, Printing & Notifications') }}</h2><p class="muted">{{ __('These preferences are saved for your staff account.') }}</p><form method="POST" action="{{ route('ccs.settings.preferences') }}">@csrf
<div class="settings-subsection settings-preferences-panel" id="settings-scanner" data-settings-pane><h3>{{ __('Scanner Input') }}</h3><label>{{ __('Scanner Input') }}<select name="scanner">
@foreach(['camera'=>'Camera Barcode / QR','reader'=>'USB / Bluetooth Reader','manual'=>'Manual Code Entry'] as $code=>$label)
<option value="{{ $code }}" @selected($preferences['scanner']===$code)>{{ __($label) }}</option>
@endforeach
</select></label></div>
<div class="settings-subsection settings-preferences-panel" id="settings-printing" data-settings-pane><h3>{{ __('Printing & Labels') }}</h3><p class="muted">{{ __('Each invoice can use its own print language and paper size. Select the physical printer in the browser print dialog.') }}</p><div class="settings-content"><label>{{ __('Default Invoice Paper') }}<select name="paper">
@foreach(['a4'=>'A4','80mm'=>'80 mm receipt','58mm'=>'58 mm receipt'] as $code=>$label)
<option value="{{ $code }}" @selected($preferences['paper']===$code)>{{ $label }}</option>
@endforeach
</select></label><label>{{ __('Default Invoice Language') }}<select name="invoice_locale">
@foreach(config('ccs_admin.locales') as $code=>$label)
<option value="{{ $code }}" @selected($preferences['invoice_locale']===$code)>{{ $label }}</option>
@endforeach
</select></label><label>{{ __('Label Width (mm)') }}<input name="label_width" type="number" required min="25" max="100" value="{{ $preferences['label_width'] }}"></label><label>{{ __('Label Height (mm)') }}<input name="label_height" type="number" required min="15" max="100" value="{{ $preferences['label_height'] }}"></label></div></div>
<div class="settings-subsection settings-preferences-panel" id="settings-notifications" data-settings-pane><h3>{{ __('In-App Notifications') }}</h3><p class="muted">{{ __('Alerts appear inside this system. No WhatsApp or SMS is sent by these settings.') }}</p><div class="settings-checks">
@foreach(['notify_jobs'=>'New Repair Jobs','notify_stock'=>'Low / Empty Stock','notify_warranty'=>'Warranty Dates Within 10 Days','notify_claims'=>'Open Warranty Claims'] as $key=>$label)
<label><input name="{{ $key }}" type="checkbox" value="1" @checked($preferences[$key])> {{ __($label) }}</label>
@endforeach
</div></div><button>{{ __('Save Preferences') }}</button></form></section>
</div></div>
<script>
(function(){
 const main=document.getElementById('settingsMain'),tabs=Array.from(document.querySelectorAll('[data-settings-tab]')),panes=Array.from(main.querySelectorAll('[data-settings-pane]')),prefs=main.querySelector('[data-settings-preferences]');
 const prefIds=['settings-scanner','settings-printing','settings-notifications'];
 function activate(id){if(!tabs.some(tab=>tab.dataset.settingsTab===id))id=tabs[0].dataset.settingsTab;panes.forEach(pane=>{pane.hidden=pane.id!==id;});prefs.hidden=!prefIds.includes(id);tabs.forEach(tab=>{const active=tab.dataset.settingsTab===id;tab.classList.toggle('is-active',active);if(active)tab.setAttribute('aria-current','page');else tab.removeAttribute('aria-current');});try{sessionStorage.setItem('ccs-settings-section',id);}catch(e){}return id;}
 let remembered='';try{remembered=sessionStorage.getItem('ccs-settings-section')||'';}catch(e){}
 main.classList.remove('settings-nojs');activate(location.hash.slice(1)||remembered||tabs[0].dataset.settingsTab);
 tabs.forEach(tab=>tab.addEventListener('click',function(event){event.preventDefault();const id=activate(this.dataset.settingsTab);history.replaceState(null,'','#'+id);}));
 window.addEventListener('hashchange',()=>activate(location.hash.slice(1)));
 prefs.addEventListener('invalid',function(event){const pane=event.target.closest('[data-settings-pane]');if(pane)activate(pane.id);},true);
 document.querySelectorAll('[data-open-staff]').forEach(button=>button.addEventListener('click',()=>{const dialog=document.getElementById(button.dataset.openStaff);if(dialog&&!dialog.open)dialog.showModal();}));
 document.querySelectorAll('[data-close-staff]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog').close()));
 @if($errors->any() && old('settings_staff_dialog'))
 const retryId = @json(old('settings_staff_dialog'));
 const retryDialog=document.getElementById(retryId);if(retryDialog){activate('settings-users');if(!retryDialog.open)retryDialog.showModal();}
 @endif
})();
</script>
@endsection
