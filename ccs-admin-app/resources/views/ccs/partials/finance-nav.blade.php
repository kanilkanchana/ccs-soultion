<nav class="finance-shortcuts no-print {{ ($compact??false)?'finance-shortcuts-compact':'' }}" aria-label="{{ __('Finance Navigation') }}">
@foreach([
 ['ccs.payments.index','Payment Finalization','Finalize bills and record payments','01'],
 ['ccs.credit.ledger','Credit Ledger','Customer balances and credit history','02'],
 ['ccs.purchases','Customer Sales & Payments','Sales records and payment history','03']
] as [$target,$label,$description,$number])
<a href="{{ route($target) }}" @class(['finance-shortcut','active'=>request()->routeIs($target)]) @if(request()->routeIs($target)) aria-current="page" @endif><span class="finance-shortcut-number">{{ $number }}</span><span><strong>{{ __($label) }}</strong>@if(!($compact??false))<small>{{ __($description) }}</small>@endif</span><span class="finance-shortcut-arrow" aria-hidden="true">↗</span></a>
@endforeach
</nav>
