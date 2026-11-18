<aside class="ccs-sidebar" aria-label="{{ __('Main Navigation') }}">
<svg width="0" height="0" style="position:absolute;pointer-events:none" aria-hidden="true"><defs>
<symbol id="ccs-nav-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
<symbol id="ccs-nav-cart" viewBox="0 0 24 24"><path d="M3 3h2l3 12h11l2-9H6M9 19h.01M18 19h.01"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></symbol>
<symbol id="ccs-nav-job" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 9h6M9 13h6M9 17h4"/></symbol>
<symbol id="ccs-nav-users" viewBox="0 0 24 24"><circle cx="10" cy="7" r="4"/><path d="M3 21v-2a7 7 0 0 1 14 0v2M17 4a4 4 0 0 1 0 7M21 21v-2a7 7 0 0 0-3-5"/></symbol>
<symbol id="ccs-nav-box" viewBox="0 0 24 24"><path d="m12 2 9 5v10l-9 5-9-5V7l9-5Zm0 10v10M3 7l9 5 9-5M7 4l10 6"/></symbol>
<symbol id="ccs-nav-shield" viewBox="0 0 24 24"><path d="m12 2 9 4v6c0 5-9 10-9 10S3 17 3 12V6l9-4Z"/><path d="m8 12 3 3 5-6"/></symbol>
<symbol id="ccs-nav-money" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M5 9h1M18 15h1"/></symbol>
<symbol id="ccs-nav-settings" viewBox="0 0 24 24"><path d="m9 3 1-1h4l1 3 3 1 3 1v4l-2 2-1 3-1 3h-4l-2-2-3-1-3-1v-4l2-2 1-3 1-3Z"/><circle cx="12" cy="12" r="3"/></symbol>
<symbol id="ccs-nav-out" viewBox="0 0 24 24"><path d="M9 3H4v18h5M9 12h12m-5-5 5 5-5 5"/></symbol>
</defs></svg>
<div class="ccs-sidebar-brand"><a href="{{ route('ccs.dashboard') }}" class="ccs-brand-link"><span class="ccs-brand-mark">CCS</span><span><strong>SOLUTIONS</strong><small>{{ __('Workshop Management') }}</small></span></a><button type="button" id="sidebarToggle" class="ccs-sidebar-toggle" aria-expanded="false" aria-controls="sidebarContent">☰<span class="sr-only">{{ __('Toggle Menu') }}</span></button></div>
<div id="sidebarContent" class="ccs-sidebar-content">
@php
$navSections=[
 ['Main Operations',[
 ['ccs.dashboard','Dashboard','grid',['ccs.dashboard']],
 ['ccs.website.index','Website Requests','users',['ccs.website.*']],
 ['ccs.jobs','Client Job Cards','job',['ccs.jobs*']],
 ['ccs.pos','POS / Billing','cart',['ccs.pos*','ccs.invoices.*']],
 ]],
 ['Workshop Management',[
 ['ccs.customers','Customers & Cars','users',['ccs.customers*','ccs.vehicles.*']],
 ['ccs.stock','Parts & Inventory','box',['ccs.stock*']],
 ['ccs.credit.ledger','Credit Ledger (A/R)','money',['ccs.credit.ledger','ccs.payments.*','ccs.purchases']],
 ['ccs.warranties','Warranty & Claims','shield',['ccs.warranties*','ccs.claims.*']],
 ]],
 ['System',[
 ['ccs.settings','Settings & Workshop','settings',['ccs.settings*','ccs.notifications.*','ccs.customer-access.*']],
 ]],
];
$extraLinks=[
 'ccs.customers'=>[['ccs.vehicles.index','Vehicles']],
 'ccs.credit.ledger'=>[['ccs.payments.index','Payment Finalization'],['ccs.purchases','Customer Sales & Payments']],
 'ccs.warranties'=>[['ccs.claims.index','Warranty Claims']],
 'ccs.settings'=>[['ccs.notifications.index','Notifications'],['ccs.customer-access.index','Customer Access']],
];
@endphp
<nav class="v69-sidebar-nav" aria-label="{{ __('Main Navigation') }}">
@foreach($navSections as [$section,$items])
<section class="v69-nav-section"><h2>{{ __($section) }}</h2>
@foreach($items as [$target,$label,$icon,$patterns])
@if(\Illuminate\Support\Facades\Route::has($target) && app(\App\Services\CcsAdministration::class)->can(auth()->user(),$target))
@php($navActive=request()->routeIs(...$patterns))
<div class="v69-nav-item"><a href="{{ route($target) }}" @class(['v69-nav-link','active'=>$navActive]) @if($navActive) aria-current="page" @endif><svg class="ccs-nav-icon" aria-hidden="true"><use href="#ccs-nav-{{ $icon }}"/></svg><span>{{ __($label) }}</span></a>
@if(isset($extraLinks[$target]))
<details class="v69-nav-more"><summary aria-label="{{ __($label) }} · {{ __('More Details (optional)') }}"><span aria-hidden="true">⌄</span></summary><div>
@foreach($extraLinks[$target] as [$extraRoute,$extraLabel])
@if(\Illuminate\Support\Facades\Route::has($extraRoute) && app(\App\Services\CcsAdministration::class)->can(auth()->user(),$extraRoute) && $extraRoute!=='ccs.stock.labels')
<a href="{{ route($extraRoute) }}" @if(request()->routeIs($extraRoute)) aria-current="page" @endif>{{ __($extraLabel) }}@if($extraRoute==='ccs.notifications.index')<small id="staffNotificationCount"></small>@endif</a>
@endif
@endforeach
</div></details>
@endif
</div>
@endif
@endforeach
</section>
@endforeach
</nav>
<div class="ccs-sidebar-profile"><span class="ccs-profile-avatar">{{ mb_strtoupper(mb_substr(auth('web')->user()?->name??'S',0,1)) }}</span><div><strong>{{ auth('web')->user()?->name??__('Staff') }}</strong><small>{{ __(ucfirst(app(\App\Services\CcsAdministration::class)->role(auth('web')->user()))) }}</small></div><form method="POST" action="{{ route('ccs.logout') }}">@csrf<button type="submit" aria-label="{{ __('Log out') }}" title="{{ __('Log out') }}"><svg class="ccs-nav-icon" aria-hidden="true"><use href="#ccs-nav-out"/></svg></button></form></div>
</div>
</aside>
<script>
(()=>{const button=document.getElementById('sidebarToggle'),sidebar=button.closest('.ccs-sidebar');button.addEventListener('click',()=>{const open=sidebar.dataset.open!=='true';sidebar.dataset.open=String(open);button.setAttribute('aria-expanded',String(open));});})();
</script>

<script>
(()=>{const badge=document.getElementById('staffNotificationCount');if(!badge)return;let loading=false;async function refresh(){if(loading||document.hidden)return;loading=true;try{const r=await fetch({{ Illuminate\Support\Js::from(route('ccs.notifications.count')) }},{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});if(r.status===401){clearInterval(timer);return;}if(!r.ok)return;const data=await r.json();badge.textContent=data.unread?' ('+data.unread+')':'';}catch(error){}finally{loading=false;}}const timer=setInterval(refresh,60000);refresh();})();
</script>
