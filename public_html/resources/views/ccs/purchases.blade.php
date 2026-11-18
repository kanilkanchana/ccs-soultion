@extends('ccs.layout')
@section('title',__('Customer Sales & Payments'))
@section('body-class','ccs-finance-page')
@section('content')
@include('ccs.partials.finance-nav')
<style>
.credit-report{font-family:system-ui,sans-serif;color:#effbf4;display:grid;gap:22px}.credit-card{background:#0c1f30;border:1px solid #1a3c5c;border-radius:12px;padding:22px}.credit-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}.credit-toolbar h1{color:#ffcc00;margin:0;font-size:24px}.credit-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.credit-button{display:inline-block;padding:9px 14px;background:#183c38;color:#effbf4;border:1px solid #4b7770;border-radius:6px;text-decoration:none;cursor:pointer}.credit-actions input{padding:9px;background:#091b1c;color:#ffcc00;border:1px solid #4b7770;border-radius:6px}.credit-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px}.credit-summary strong{display:block;font-size:26px;color:#b5f5c9;margin:10px 0}.credit-summary small,.credit-report small{color:#a6c4b9}.credit-card h2{font-size:18px;color:#87eec2;margin:0 0 16px}.credit-table-wrap{overflow-x:auto}.credit-table{width:100%;border-collapse:collapse;font-size:13px}.credit-table th{background:#1a3c5c;color:#87eec2;text-align:left;padding:12px;white-space:nowrap}.credit-table td{padding:12px;border-bottom:1px solid #234c70}.credit-money{text-align:right;white-space:nowrap}.credit-empty{text-align:center;color:#a6c4b9}.credit-due{color:#ffaaaa;font-weight:bold}.credit-paid{color:#b5f5c9}.credit-table a{color:#ffcc00}.credit-report p{margin:0;color:#a6c4b9}
.book-editor{min-width:240px;margin-top:10px}.book-editor label{display:block;margin:8px 0;color:#a6c4b9}.book-editor input{display:block;box-sizing:border-box;width:100%;padding:8px;margin-top:4px;background:#091b1c;border:1px solid #4b7770;border-radius:5px;color:#effbf4}.book-editor summary{cursor:pointer;color:#87eec2}
@media print{.no-print{display:none!important}.credit-report,.credit-card{color:#000;background:#fff}.credit-card{border:1px solid #aaa}.credit-card h2,.credit-summary strong,.credit-report small,.credit-due,.credit-paid,.credit-table a{color:#000}.credit-table th{background:#eee;color:#000}}

body:has(.credit-report){overflow-y:auto}main:has(.credit-report){min-width:0;width:100%;max-width:100%;align-self:flex-start}.credit-report{width:100%;min-width:0}.credit-card,#sales-payments-live{min-width:0}#sales-payments-live{display:grid;gap:22px}.credit-table-wrap{width:100%;max-width:100%;max-height:clamp(230px,42vh,440px);overflow:auto;scrollbar-gutter:stable;border:1px solid #234c70;border-radius:7px}.credit-table{min-width:1000px}.credit-table thead th{position:sticky;top:0;z-index:1;background:#1a3c5c}.payment-status{display:inline-block;padding:5px 9px;border-radius:5px;white-space:nowrap;font-size:12px;font-weight:bold}.payment-status-paid{background:#184636;color:#b5f5c9}.payment-status-partial{background:#4b3d16;color:#ffe4a0}.payment-status-due{background:#62262b;color:#ffaaaa}.credit-summary{min-width:0}.credit-toolbar h1{overflow-wrap:anywhere}@media(max-width:600px){.credit-card{padding:12px}.credit-summary{grid-template-columns:1fr}.credit-actions{width:100%}.credit-toolbar h1{font-size:21px}}
@media print{.credit-table-wrap{max-height:none!important;overflow:visible!important;border:0}.credit-table{min-width:0;font-size:9px}.credit-table th,.credit-table td{white-space:normal;padding:5px}.credit-table thead th{position:static}.payment-status{background:transparent;color:black;padding:0}.credit-report,#sales-payments-live{display:block}.credit-card{margin-bottom:14px}}
</style>
<div class="credit-report">
    <div class="credit-card credit-toolbar">
        <h1>{{ __('Customer Sales & Payments') }}</h1>
        <div class="credit-actions no-print">
            <a class="credit-button" href="{{ route('ccs.pos') }}">{{ __('← POS') }}</a>
            <a class="credit-button" href="{{ route('ccs.purchases') }}">{{ __('All Records') }}</a>
            <form method="GET" action="{{ route('ccs.purchases') }}">
                <label for="credit-date">{{ __('Date') }}</label>
                <input id="credit-date" type="date" name="date" value="{{ $selectedDateStr }}" onchange="this.form.submit()">
                <button class="credit-button" type="submit">{{ __('Filter') }}</button>
            </form>
            <button class="credit-button" type="button" id="refreshReport">{{ __('Refresh') }}</button><span id="reportRefreshStatus" role="status" style="font-size:12px"></span>
            <button class="credit-button" type="button" onclick="window.print()">{{ __('Print / PDF') }}</button>
        </div>
    </div>
    <p>Use Edit Details on a saved item to fill missing Year, Condition and Supplier. New item details come from POS.</p>
    <p>{{ __('All finalized sales and payments are shown, including fully paid cash bills. Draft and void bills are excluded.') }}</p>
    @if(session('success'))<div class="credit-card credit-paid">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="credit-card credit-due">{{ $errors->first() }}</div>@endif
    <div id="sales-payments-live"><div class="credit-summary">
        <div class="credit-card">{{ __($selectedDateStr ? 'Daily Sales' : 'Total Sales') }}<strong>Rs. {{ number_format($salesTotal, 2) }}</strong><small>{{ count($dailySales) }} invoices · {{ $dateLabel }}</small></div>
        <div class="credit-card">{{ __('Total Outstanding Dues') }}<strong style="color:#ffaaaa">Rs. {{ number_format($totalOutstandingDue, 2) }}</strong><small>{{ __('Customers owe this amount · all dates') }}</small></div>
        <div class="credit-card">{{ __($selectedDateStr ? 'Daily Payments Received' : 'Total Payments Received') }}<strong>Rs. {{ number_format($paymentsTotal, 2) }}</strong><small>{{ count($paymentHistory) }} receipts · {{ $dateLabel }}</small></div>
    </div>
    <section class="credit-card">
        <h2>{{ __('Daily Sale Records') }} ({{ $dateLabel }})</h2>
        <p style="margin-bottom:14px">Price is the item line total before invoice discounts. Payment Date is the latest actual receipt date. Invoice balances and all receipt dates appear below.</p>
        <div class="credit-table-wrap" tabindex="0" role="region" aria-label="{{ __('Scrollable records') }}"><table class="credit-table">
            <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Model / Module') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Year') }}</th><th>{{ __('Condition') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Price (Rs.)') }}</th><th>{{ __('Payment Status') }}</th><th>{{ __('Payment Date') }}</th><th>{{ __('Customer / Invoice') }}</th></tr></thead>
            <tbody>
            @forelse($dailySales as $sale)
                @forelse($sale->book_items as $item)
                <tr>
                    <td>{{ $sale->sale_date }}</td>
                    <td>{{ $item->description }}
@if(($item->item_origin ?? '') === 'customer_supplied')
<br><small>{{ __('Customer Supplied Part') }}</small>@endif
@if($item->serial_number ?? null)
<br><small>{{ __('Serial Number') }}: {{ $item->serial_number }}</small>@endif</td>
                    <td>{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</td>
                    <td>{{ $item->item_year ?: '—' }}</td>
                    <td>{{ $item->item_condition ?: '—' }}</td>
                    <td>{{ $item->item_supplier ?: '—' }}</td>
                    <td class="credit-money">{{ number_format($item->line_total_lkr, 2) }}</td>
                    <td><span class="payment-status {{ $sale->balance <= 0 ? 'payment-status-paid' : ($sale->paid_amount > 0 ? 'payment-status-partial' : 'payment-status-due') }}">{{ __($sale->balance <= 0 ? 'Paid' : ($sale->paid_amount > 0 ? 'Partially Paid' : 'Due')) }}</span></td>
                    <td>{{ $sale->last_payment_date ?: '—' }}@if($sale->last_payment_date)<br><small>{{ $sale->balance > 0 ? 'Last payment · balance still due' : 'Last payment · settled' }}</small>@endif</td>
                    <td>{{ $sale->customer_name }}<br><a href="{{ route('ccs.invoices.show', $sale->id) }}">{{ $sale->invoice_number }}</a>
                        <details class="book-editor no-print"><summary>{{ __('Edit Details') }}</summary>
                            <form method="POST" action="{{ route('ccs.invoice-items.details', $item->id) }}">
                                @csrf
                                <label>{{ __('Year') }}<input name="item_year" maxlength="50" value="{{ $item->item_year }}" placeholder="e.g. 2007 / 2008"></label>
                                <label>{{ __('Condition') }}<input name="item_condition" maxlength="80" value="{{ $item->item_condition }}" placeholder="e.g. Recon / Brand New"></label>
                                <label>{{ __('Supplier') }}<input name="item_supplier" maxlength="150" value="{{ $item->item_supplier }}" placeholder="{{ __('Supplier name') }}"></label>
                                <button type="submit" class="credit-button">{{ __('Save Details') }}</button>
                            </form>
                        </details>
                    </td>
                </tr>
                @empty
                <tr><td>{{ $sale->sale_date }}</td><td colspan="6">{{ __('No saved line items for this invoice.') }}</td><td>{{ __($sale->balance <= 0 ? 'Paid' : ($sale->paid_amount > 0 ? 'Partially Paid' : 'Due')) }}</td><td>{{ $sale->last_payment_date ?: '—' }}</td><td><a href="{{ route('ccs.invoices.show', $sale->id) }}">{{ $sale->invoice_number }}</a></td></tr>
                @endforelse
            @empty
                <tr><td class="credit-empty" colspan="10">{{ __('No sales for this selection. Use All Records to view other dates.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <section class="credit-card">
        <h2>{{ __('Total Outstanding Dues') }} — Rs. {{ number_format($totalOutstandingDue, 2) }}</h2>
        <div class="credit-table-wrap" tabindex="0" role="region" aria-label="{{ __('Scrollable records') }}"><table class="credit-table">
            <thead><tr><th>{{ __('Invoice / Date') }}</th><th>{{ __('Customer / NIC') }}</th><th>{{ __('Phone / Address') }}</th><th>{{ __('Original Credit') }}</th><th>{{ __('Paid Amount') }}</th><th>{{ __('Outstanding Balance') }}</th><th class="no-print">{{ __('Action') }}</th></tr></thead>
            <tbody>
            @forelse($outstandingDues as $due)
                <tr><td>{{ $due->invoice_number }}<br><small>{{ $due->sale_date }}</small></td><td>{{ $due->customer_name }}<br><small>{{ $due->nic ?? '—' }}</small></td><td>{{ $due->phone }}<br><small>{{ $due->address ?? '—' }}</small></td><td class="credit-money">{{ number_format($due->total_lkr, 2) }}</td><td class="credit-money credit-paid">{{ number_format($due->paid_amount, 2) }}</td><td class="credit-money credit-due">{{ number_format($due->balance, 2) }}</td><td class="no-print"><a href="{{ route('ccs.invoices.show', $due->id) }}">{{ __('View / Receive Payment →') }}</a></td></tr>
            @empty
                <tr><td class="credit-empty" colspan="7">{{ __('No outstanding customer credit balances.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <section class="credit-card">
        <h2>{{ __('Daily Payment History') }} ({{ $dateLabel }})</h2>
        <div class="credit-table-wrap" tabindex="0" role="region" aria-label="{{ __('Scrollable records') }}"><table class="credit-table">
            <thead><tr><th>{{ __('Payment Date') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Payment Amount') }}</th><th>{{ __('Method / Reference') }}</th><th>{{ __('Previous Balance') }}</th><th>{{ __('Balance After Payment') }}</th><th>{{ __('Payment Status') }}</th></tr></thead>
            <tbody>
            @forelse($paymentHistory as $payment)
                <tr><td>{{ $payment->paid_at }}</td><td>{{ $payment->customer_name }}</td><td><a href="{{ route('ccs.invoices.show', $payment->invoice_id) }}">{{ $payment->invoice_number }}</a></td><td class="credit-money credit-paid">{{ number_format($payment->amount_lkr, 2) }}</td><td>{{ ucwords(str_replace('_', ' ', $payment->method)) }}<br><small>{{ $payment->reference ?? '—' }}</small></td><td class="credit-money">{{ number_format($payment->previous_balance, 2) }}</td><td class="credit-money">{{ number_format($payment->remaining_balance, 2) }}</td><td><span class="payment-status {{ $payment->remaining_balance <= 0 ? 'payment-status-paid' : 'payment-status-partial' }}">{{ __($payment->payment_status) }}</span></td></tr>
            @empty
                <tr><td class="credit-empty" colspan="8">{{ __('No customer payments for this selection.') }}</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</div>
</div>
<script>
(()=>{
 const button=document.getElementById('refreshReport'),indicator=document.getElementById('reportRefreshStatus');
 let busy=false;
 const text=value=>window.ccsText?window.ccsText(value):value;
 async function refresh(){
  if(busy||document.hidden||document.querySelector('.book-editor[open]')||document.activeElement?.matches('input,textarea,select'))return;
  busy=true;button.disabled=true;
  const current=document.getElementById('sales-payments-live');
  const positions=[...current.querySelectorAll('.credit-table-wrap')].map(el=>({top:el.scrollTop,left:el.scrollLeft}));
  try{
   const response=await fetch(window.location.href,{credentials:'same-origin',cache:'no-store',headers:{'X-Requested-With':'XMLHttpRequest'}});
   if(!response.ok||response.redirected)throw Error('Refresh failed');
   const next=new DOMParser().parseFromString(await response.text(),'text/html').getElementById('sales-payments-live');
   if(!next)throw Error('No report');
   // Do not replace a form opened while the request was in flight.
   if(document.querySelector('.book-editor[open]')||document.activeElement?.matches('input,textarea,select'))return;
   current.replaceWith(next);
   next.querySelectorAll('.credit-table-wrap').forEach((el,i)=>{el.scrollTop=positions[i]?.top||0;el.scrollLeft=positions[i]?.left||0;});
   indicator.textContent=text('Updated')+' '+new Date().toLocaleTimeString(document.documentElement.lang==='si'?'si-LK':'en-LK');
  }catch(error){indicator.textContent=text('Refresh failed. Please reload.');}
  finally{busy=false;button.disabled=false;}
 }
 button.addEventListener('click',refresh);
 setInterval(refresh,30000);
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
})();
</script>
@endsection
