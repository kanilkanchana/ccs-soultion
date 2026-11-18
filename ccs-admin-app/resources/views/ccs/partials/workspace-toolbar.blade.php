@php
$prefTheme = app(\App\Services\CcsAdministration::class)->preferences(auth()->id())['theme'] ?? 'dark';
$isLightTheme = in_array($prefTheme,['light','skyline','desert','aqua'], true);
@endphp
<header class="v70-toolbar no-print">
    <span class="v70-toolbar-title"><span><span class="eyebrow">CCS Solutions / Admin</span><strong>{{ __('Dashboard') }}</strong></span></span>
    <div class="ccs-clock-bar no-print"><span>{{ __('Workspace') }}</span><time id="ccsLiveClock">{{ now()->format('Y-m-d H:i') }}</time></div>
    <script>(()=>{const el=document.getElementById('ccsLiveClock');if(el){function update(){const d=new Date();el.textContent=new Intl.DateTimeFormat(undefined,{year:'numeric',month:'short',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).format(d);el.dateTime=d.toISOString()}update();setInterval(update,1000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)update()})}})();</script>
    <form class="pm-toggle-form" action="{{ route('ccs.settings.theme') }}" method="POST">@csrf
        <label>{{ __('Theme') }}</label>
        <button type="button" class="pm-toggle-button" data-theme-toggle data-endpoint="{{ route('ccs.settings.theme') }}"><span>{{ $isLightTheme ? __('Light') : __('Dark') }}</span><span class="pm-dot" aria-hidden="true"></span><span data-theme-label style="display:none">{{ $isLightTheme ? 'Light' : 'Dark' }}</span></button>
    </form>
    <div class="v70-language-controls">
        <form class="ccs-input-switch" id="ccsInputSwitch" method="POST" action="{{ route('ccs.settings.input-method') }}">@csrf<label for="ccsInputMode"><span aria-hidden="true">⌨</span> {{ __('Input Method') }}</label><select name="input_method" id="ccsInputMode" aria-label="{{ __('Input Method') }}">
@foreach(['english'=>'English','phonetic'=>'Sinhala Phonetic','wijesekara'=>'Sinhala Wijesekara'] as $code=>$label)
<option value="{{ $code }}" @selected(app(\App\Services\CcsAdministration::class)->preferences(auth()->id())['input_method']===$code)>{{ __($label) }}</option>
@endforeach
</select><button type="button" class="ccs-guide-trigger" data-open-phonetic-guide @if(app(\App\Services\CcsAdministration::class)->preferences(auth()->id())['input_method']!=='phonetic') hidden @endif>{{ __('View Phonetic Guide') }}</button><button type="submit" class="ccs-input-save">{{ __('Save Input Method') }}</button><small id="ccsInputStatus" role="status" aria-live="polite"></small></form>
<form id="ccsSystemLanguage" method="POST" action="{{ route('ccs.settings.language') }}">@csrf<label for="ccsSystemLocale">🌐 {{ __('System Language') }}</label><select id="ccsSystemLocale" name="language">
@foreach(config('ccs_admin.locales') as $code=>$label)
<option value="{{ $code }}" @selected(app()->getLocale()===$code)>{{ $label }}</option>
@endforeach
</select><button type="submit">{{ __('Save') }}</button><small id="ccsLanguageStatus" role="status"></small></form></div></header>