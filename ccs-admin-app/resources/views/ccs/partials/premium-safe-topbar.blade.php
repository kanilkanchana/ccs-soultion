{{-- Premium presentation header. Reuses the existing ccs.settings.theme POST action. --}}
@php
    $pmTheme = $ccsTheme ?? 'default';
    $pmIsLight = in_array($pmTheme, ['light','skyline','desert','aqua'], true);
    $pmNextTheme = $pmIsLight ? 'dark' : 'light';
@endphp
<div class="pm-topbar no-print" aria-label="{{ __('Workspace appearance') }}">
  <div class="pm-location">
    <span class="pm-location-symbol" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11 12 4l8 7v9H4z"/><path d="M10 20v-6h4v6"/></svg></span>
    <span><small>CCS SOLUTIONS <span aria-hidden="true">/</span> ADMIN</small><strong>@yield('title', __('Dashboard'))</strong></span>
  </div>
  <div class="pm-top-actions">
    <span class="pm-live-flag"><i></i>{{ __('Workspace') }}</span>
    <form method="POST" action="{{ route('ccs.settings.theme') }}" class="pm-theme-form" data-pm-theme>
      @csrf
      <input type="hidden" name="theme" value="{{ $pmNextTheme }}">
      <button type="submit" class="pm-theme-button" title="{{ $pmIsLight ? __('Switch to dark mode') : __('Switch to light mode') }}" aria-label="{{ $pmIsLight ? __('Switch to dark mode') : __('Switch to light mode') }}" data-light-label="{{ __('Light') }}" data-dark-label="{{ __('Dark') }}" data-to-light="{{ __('Switch to light mode') }}" data-to-dark="{{ __('Switch to dark mode') }}">
        <svg class="pm-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.5 1.5m11.2 11.2 1.5 1.5M2 12h2m16 0h2M4.9 19.1l1.5-1.5M17.6 6.4l1.5-1.5"/></svg>
        <svg class="pm-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M20 14.5A8.8 8.8 0 0 1 9.5 4a8.8 8.8 0 1 0 10.5 10.5Z"/></svg>
        <span class="pm-theme-label">{{ $pmIsLight ? __('Light') : __('Dark') }}</span>
        <span class="pm-switch" aria-hidden="true"><i></i></span>
      </button>
      <span class="pm-theme-message" role="status" aria-live="polite"></span>
    </form>
    <span class="pm-account" title="{{ auth('web')->user()?->name ?? __('Staff') }}">{{ mb_strtoupper(mb_substr(auth('web')->user()?->name ?? 'S',0,1)) }}</span>
  </div>
</div>
