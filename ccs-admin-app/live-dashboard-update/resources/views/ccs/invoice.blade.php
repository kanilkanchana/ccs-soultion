@extends('ccs.layout')  
@section('title', 'Invoice & Payments')  
@section('body-class','ccs-invoice-page')
@section('content')
<!-- CCS_CARE_V3_INVOICE -->@if(in_array($invoice->status,['issued','partially_paid','paid'],true))<div class="no-print" style="margin-bottom:12px">@include('ccs.partials.care-icon',['careKind'=>'invoice','careId'=>$invoice->id])</div>@endif
@php
$printPreferences=app(\App\Services\CcsAdministration::class)->preferences(auth()->id());
@endphp
<details class="v71-invoice-tools no-print" @if($errors->any()) open @endif><summary>{{ __('Invoice Print Settings') }} / {{ __('Battery Modules / Serial Requirements') }}</summary><section class="card no-print" id="invoicePrintSettings"><h2>{{ __('Invoice Print Settings') }}</h2><p class="muted">{{ __('This language applies only to this invoice. System language stays unchanged.') }}</p><form method="POST" action="{{ route('ccs.invoices.print-settings',$invoice->id) }}" class="actions">@csrf<label>{{ __('Invoice Language') }}<select name="print_locale">
@foreach(config('ccs_admin.locales') as $code=>$label)
<option value="{{ $code }}" @selected(($invoice->print_locale?:$printPreferences['invoice_locale'])===$code)>{{ $label }}</option>
@endforeach
</select></label><label>{{ __('Paper Size') }}<select name="print_paper">
@foreach(['a4'=>'A4','80mm'=>'80 mm','58mm'=>'58 mm'] as $code=>$label)
<option value="{{ $code }}" @selected(($invoice->print_paper?:$printPreferences['paper'])===$code)>{{ $label }}</option>
@endforeach
</select></label><button>{{ __('Save Print Preferences') }}</button><a class="btn secondary" target="_blank" rel="noopener" href="{{ route('ccs.invoices.print',$invoice->id) }}">{{ __('Print / Save as PDF') }}</a></form></section>

<section class="card no-print"><strong>{{ __('Battery Modules / Serial Requirements') }}</strong><p>{{ __('Battery module readings entered in POS are saved in the Job Card. If readings are missing, complete them here before finalizing. Draft printing is a preview only.') }}</p><a class="btn secondary" href="{{ route('ccs.jobs.battery',$invoice->repair_job_id) }}">{{ __('Open Service / Parts Record') }}</a></section>
@if($invoice->status==='draft')
@php
$serialLines=\Illuminate\Support\Facades\DB::table('invoice_items')->where('invoice_id',$invoice->id)->where('stock_managed',false)->where(function($q){$q->where('requires_serial',true)->orWhere('is_battery_module',true);})->get();
@endphp
@if($serialLines->isNotEmpty())
<section class="card no-print"><h2>{{ __('Complete Actual Serials') }}</h2><form method="POST" action="{{ route('ccs.invoices.complete-serials',$invoice->id) }}">
@csrf
@foreach($serialLines as $serialLine)
<label>{{ $serialLine->description }}<input name="serials[{{ $serialLine->id }}]" value="{{ $serialLine->serial_number }}" maxlength="100" required></label>
@endforeach
<button type="submit">{{ __('Save Draft Serials') }}</button></form></section>
@endif
@endif


</details>
@include('ccs.partials.finance-nav')  
@php  
 $payments = \Illuminate\Support\Facades\DB::table('payments')->where('invoice_id',$invoice->id)->orderBy('paid_at')->orderBy('id')->get();  
 $paid = $payments->sum(fn($p) => (int) round((float)$p->amount_lkr * 100)) / 100;  
 $balance = max(0, round((float)$invoice->total_lkr - $paid, 2));  
 $isDraft = $invoice->status === 'draft';  
 $isIssued = in_array($invoice->status, ['issued', 'partially_paid', 'paid'], true);  
@endphp  
<style>  
.ccs-billing{--paper:#10292a;--edge:#315451;--ink:#effbf4;--muted:#a6c4b9;color:var(--ink); font-family: system-ui, sans-serif;}  
.ccs-billing *{box-sizing:border-box}.ccs-billing .bill-head{display:flex;gap:14px;justify-content:space-between;align-items:center;margin-bottom:12px}.ccs-billing h1{font-size:24px;margin:0}.ccs-billing h2{font-size:16px;margin:0 0 12px}.ccs-billing .bill-head small{color:var(--muted)}  
.ccs-billing .bill-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(310px,.95fr);gap:14px}.ccs-billing .bill-card{border:1px solid var(--edge);border-radius:12px;padding:16px;background:var(--paper);min-width:0}.ccs-billing .bill-kv{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin:14px 0}.ccs-billing .bill-kv div{font-size:13px}.ccs-billing .bill-kv span{display:block;color:var(--muted);font-size:11px;margin-bottom:3px}  
.ccs-billing .bill-table-wrap{overflow:auto;max-height:32vh}.ccs-billing table{border-collapse:collapse;width:100%;font-size:12px}.ccs-billing th,.ccs-billing td{padding:10px 8px;border-bottom:1px dashed var(--edge);text-align:left}.ccs-billing th:last-child,.ccs-billing td:last-child{text-align:right}.ccs-billing .bill-amount{display:flex;justify-content:space-between;gap:8px;margin:10px 0;font-size:13px}.ccs-billing .bill-due{border:1px solid #528c74;background:#184636;padding:13px;border-radius:10px}.ccs-billing .bill-due strong{font-size:22px;color:#b5f5c9}.ccs-billing .bill-status{font-size:12px;padding:5px 10px;border-radius:18px;background:#264943;white-space:nowrap; text-transform: uppercase; font-weight: bold;}  
.ccs-billing input,.ccs-billing select{width:100%;min-height:39px;padding:9px 10px;background:#091c1d;color:#effbf4;border:1px solid #4b7770;border-radius:7px}.ccs-billing label{display:block;font-size:12px;margin:10px 0 5px;color:var(--muted)}.ccs-billing .bill-cols{display:grid;grid-template-columns:1fr 1fr;gap:10px}.ccs-billing button,.ccs-billing .bill-link{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:10px 15px;border-radius:8px;background:#b7f1c6;color:#0c3022;border:0;font-weight:700;text-decoration:none;cursor:pointer}.ccs-billing .bill-secondary{background:#27504b;color:white;border:1px solid #55847c}.ccs-billing .bill-actions{display:flex;gap:8px;flex-wrap:wrap}.ccs-billing .bill-note{color:var(--muted);font-size:12px}.ccs-billing .bill-alert{border:1px solid #b88b46;border-radius:8px;padding:10px;font-size:13px;margin-bottom:12px}  

/* Warranty Box Styling */
.bill-warranty { margin-bottom: 15px; padding: 12px; background: #163625; border: 1px solid #528c74; border-radius: 8px; }
.bill-warranty h3 { margin: 0 0 5px; font-size: 14px; color: #b5f5c9; }
.bill-warranty div { font-size: 12px; color: #a6c4b9; }

@media (max-width:900px){.ccs-billing .bill-grid{grid-template-columns:1fr}}@media (max-width:470px){.ccs-billing .bill-kv,.ccs-billing .bill-cols{grid-template-columns:1fr}.ccs-billing .bill-head{align-items:start;flex-direction:column}}  
@media print{  
 @page{size:A4;margin:13mm}  
 body,html{background:#fff!important;color:#111!important}.ccs-billing{color:#111!important}.ccs-billing .bill-grid{display:block}.ccs-billing .bill-card{background:white!important;color:#111!important;border:0!important;padding:8px 0!important}.ccs-billing .bill-table-wrap{max-height:none;overflow:visible}.ccs-billing th,.ccs-billing td{border-color:#ccc!important}.ccs-billing .bill-kv span,.ccs-billing .bill-note{color:#444!important}.ccs-billing .bill-status{background:white!important;color:#111!important;border:1px solid #bbb!important}.ccs-billing .bill-due{background:white!important;border:1px solid #aaa!important}.ccs-billing .bill-due strong{color:#111!important}.ccs-billing .no-print,.no-print,aside,nav{display:none!important}.ccs-billing .bill-head{margin:0!important}.ccs-billing .bill-amount b,.ccs-billing small{color:#111!important}.ccs-billing tr{break-inside:avoid}.ccs-billing th,.ccs-billing td{white-space:normal}footer{display:none!important}  
 .bill-warranty { background: white !important; border: 1px dashed #444 !important; color: #111 !important; }
 .bill-warranty h3 { color: #111 !important; }
 .bill-warranty div { color: #444 !important; }
}  
</style>  
<div class="ccs-billing">  
 <div class="bill-head"><div><h1>Invoice {{ $invoice->invoice_number }}</h1><small>CCS Solutions · Workshop Billing</small></div><div class="bill-actions no-print"><a class="bill-link bill-secondary" href="{{ route('ccs.pos') }}">{{ __('← Back to POS') }}</a><a class="bill-link bill-secondary" href="{{ route('ccs.warranties',['job_id'=>$invoice->repair_job_id]) }}">{{ __('Add / View Warranty') }}</a><button type="button" onclick="window.open('{{ route('ccs.invoices.print',$invoice->id) }}','_blank')" style="background: #ffcc00;">Print / Save PDF</button></div></div>  
 @if(session('payment_change_lkr') > 0)<div class="bill-alert no-print">{{ __('Change') }}: Rs. {{ number_format(session('payment_change_lkr'),2) }}</div>@endif
 @if(session('success'))<div class="bill-alert no-print" role="status" style="background: #184636; color: #b5f5c9; border-color: #528c74;">{{ session('success') }}</div>@endif  
 @if($errors->any())<div class="bill-alert no-print" role="alert" style="background: #62262b; color: #ffaaaa; border-color: #92383f;">@foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>@endif  
 <div class="bill-grid">  
 <section class="bill-card" aria-label="Invoice details">  
 <div style="display:flex;justify-content:space-between;gap:8px;align-items:center"><h2>Invoice Details</h2><span class="bill-status">{{ strtoupper(str_replace('_',' ', $invoice->status)) }}</span></div>  
 @if($isDraft)<p class="bill-note" style="color:#ffcc00">DRAFT · Not final and not proof of payment</p>@endif  
 <div class="bill-kv"><div><span>{{ __('Customer') }}</span>{{ $invoice->customer_name }}<br>{{ $invoice->phone }}</div><div><span>Vehicle</span>{{ $invoice->make }} {{ $invoice->model }}<br>{{ $invoice->registration_number }}</div><div><span>Repair Job</span>{{ $invoice->job_number }}</div><div><span>Issued Date</span>{{ $invoice->issued_at ? \Illuminate\Support\Carbon::parse($invoice->issued_at)->format('d M Y') : 'Not finalized yet' }}</div></div>  

 @foreach($invoiceWarranties as $warranty)
 @php
$warrantyTerms=json_decode($warranty->terms_snapshot,true) ?? [];
@endphp
 <div class="bill-warranty">
    <h3>{{ $warranty->rule_name }}</h3><small>{{ $warrantyTerms['covered_description'] ?? '' }} {{ $warrantyTerms['covered_serial'] ?? '' }}</small>
    <div>
        Valid from: <strong>{{ \Illuminate\Support\Carbon::parse($warranty->starts_on)->format('d M Y') }}</strong> 
        @if($warranty->expires_on) to <strong>{{ \Illuminate\Support\Carbon::parse($warranty->expires_on)->format('d M Y') }}</strong> @endif
        @if($warranty->duration_months) ({{ $warranty->duration_months }} Months) @endif
        @if($warranty->mileage_limit_km) | Mileage Limit: <strong>{{ number_format($warranty->mileage_limit_km) }} km</strong> @endif
    </div>
    <p>{{ __('Free Services') }}: {{ $warranty->benefits['used'] }} / {{ $warranty->benefits['included'] }} · {{ __('Remaining') }}: {{ $warranty->benefits['remaining'] }}</p>
    <p style="white-space:pre-wrap">{{ $warrantyTerms['terms'] ?? '' }}</p>
    <small>{{ __('Warranty Limit Basis') }}: {{ $warrantyTerms['limit_basis'] ?? '—' }}</small>
 </div>
 @endforeach

 <div class="bill-table-wrap">
 <table>
 <thead><tr><th>{{ __('Service / Part') }}</th><th>{{ __('Qty') }}</th><th>Unit (Rs.)</th><th>Amount (Rs.)</th></tr></thead>
 <tbody>
 @foreach($items as $item)
 <tr>
 <td>
 {{ $item->description }}
 @if(($item->item_origin ?? '') === 'customer_supplied')
 <br><small>{{ __('Customer Supplied Part') }}</small>
 @endif
 @if($item->serial_number ?? null)
 <br><strong>{{ __('Serial Number') }}: {{ $item->serial_number }}</strong>
 @endif
 </td>
 <td>{{ $item->quantity }}</td>
 <td>{{ number_format($item->unit_price_lkr,2) }}</td>
 <td>{{ number_format($item->line_total_lkr,2) }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 <div class="bill-amount" style="margin-top: 20px;"><span>{{ __('Subtotal') }}</span><b>Rs. {{ number_format($invoice->subtotal_lkr,2) }}</b></div>  
 <div class="bill-amount"><span>Discount</span><b style="color: #ffcc00;">- Rs. {{ number_format($invoice->discount_lkr,2) }}</b></div>  
 <div class="bill-amount" style="border-top: 1px solid #315451; padding-top: 10px;"><span>{{ __('Grand Total') }}</span><b style="font-size: 16px;">Rs. {{ number_format($invoice->total_lkr,2) }}</b></div>  
 <div class="bill-amount"><span>{{ __('Paid So Far') }}</span><b style="color: #a4eac1;">Rs. {{ number_format($paid,2) }}</b></div>  
 <div class="bill-due" style="margin-top: 15px;"><div class="bill-amount"><span>{{ __('Remaining Balance Due') }}</span><strong>Rs. {{ number_format($balance,2) }}</strong></div></div>  
 <h2 style="margin-top:24px">{{ __('Payment History') }}</h2>
 <div class="bill-table-wrap"><table><thead><tr><th>{{ __('Payment Date') }}</th><th>{{ __('Payment Method') }}</th><th>{{ __('Payment Amount') }}</th><th>{{ __('Balance After Payment') }}</th></tr></thead><tbody>
 @php
$runningPaidCents=0;
@endphp
 @forelse($payments as $receipt)
 @php
$runningPaidCents+=(int)round((float)$receipt->amount_lkr*100);
@endphp
 <tr><td>{{ Illuminate\Support\Carbon::parse($receipt->paid_at)->format('d M Y H:i') }}</td><td>{{ __(ucwords(str_replace('_',' ',$receipt->method))) }}@if($receipt->reference)<br><small>{{ $receipt->reference }}</small>@endif</td><td>Rs. {{ number_format($receipt->amount_lkr,2) }} <!-- CCS_CARE_V3_PAYMENT -->@include('ccs.partials.care-icon',['careKind'=>'payment','careId'=>$receipt->id])</td><td>Rs. {{ number_format(max(0,(int)round((float)$invoice->total_lkr*100)-$runningPaidCents)/100,2) }}</td></tr>
 @empty<tr><td colspan="4">{{ __('No payments yet.') }}</td></tr>@endforelse
 </tbody></table></div>
 <p class="bill-note" style="margin-top: 15px;">Stock-selected parts reserve available stock when the invoice is saved. Manual service rows do not change stock.</p>  
 </section>  
 
 <aside class="bill-card no-print" aria-label="Invoice payment actions">
 @if($isDraft && $items->contains(fn($item)=>(bool)$item->stock_managed))
 <details style="margin-bottom:18px"><summary>{{ __('Cancel Draft & Release Stock') }}</summary>
 <form method="POST" action="{{ route('ccs.stock.cancel-draft',$invoice->id) }}">@csrf<label><input type="checkbox" name="confirmation" value="1" required style="width:auto;min-height:0">{{ __('Cancel this unpaid draft and return reserved stock.') }}</label><button type="submit">{{ __('Release Reserved Stock') }}</button></form>
 </details>
 @endif
  
 @if($isDraft)  
 <h2>Finalize Invoice to Accept Payments</h2><p class="bill-note">Review customer, line items, amount and discount before finalizing. This version does not support editing an issued invoice.</p>  
 <form action="{{route('ccs.invoices.finalize',$invoice->id)}}" method="POST" style="margin-top: 20px;">@csrf
 <input type="hidden" name="request_token" value="{{ old('request_token',(string) Illuminate\Support\Str::uuid()) }}">
 <label for="finalizeReceived">{{ __('Amount Received Now (Rs.)') }}</label>
 <input id="finalizeReceived" name="amount_received" type="number" min="0" step="0.01" value="{{ old('amount_received',session('ccs_pos_payment.'.$invoice->id.'.amount',0)) }}">
 <label for="finalizeMethod">{{ __('Payment Method') }}</label>
 <select id="finalizeMethod" name="method"><option value="cash" @selected(old('method',session('ccs_pos_payment.'.$invoice->id.'.method','cash'))==='cash')>{{ __('Cash') }}</option><option value="bank_transfer" @selected(old('method',session('ccs_pos_payment.'.$invoice->id.'.method','cash'))==='bank_transfer')>{{ __('Bank Transfer') }}</option><option value="card" @selected(old('method',session('ccs_pos_payment.'.$invoice->id.'.method','cash'))==='card')>{{ __('Card') }}</option><option value="other" @selected(old('method',session('ccs_pos_payment.'.$invoice->id.'.method','cash'))==='other')>{{ __('Other') }}</option></select>
   
<label>{{ __('Reference (optional)') }}<input name="reference" maxlength="100" value="{{ old('reference',session('ccs_pos_payment.'.$invoice->id.'.reference','')) }}"></label>
 <label><input type="checkbox" name="confirmation" value="1" required style="width:auto;min-height:0;margin-right:6px">I checked this invoice and approve finalizing it</label>  
 <button type="submit" style="background: #ffcc00; color: #000; width: 100%; margin-top: 10px;" onclick="return confirm('Finalize this invoice? It cannot be returned to draft in this version.')">Finalize Invoice</button>  
 </form>  
 
 @elseif($isIssued)  
 <h2>Record Payment</h2>  
 @if($balance > 0)  
 <div style="background: #184636; padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px dashed #528c74; text-align: center;">
     <span style="color: #a6c4b9; font-size: 13px; display: block; margin-bottom: 5px;">Current Remaining Due</span>
     <strong style="color: #ffaaaa; font-size: 22px;">Rs. {{ number_format($balance, 2) }}</strong>
 </div>

 <form method="POST" action="{{route('ccs.invoices.payments.store',$invoice->id)}}" id="paymentForm">@csrf  
 <input type="hidden" name="request_token" value="{{ \Illuminate\Support\Str::uuid() }}">  
 
 
 <label for="tenderedAmount">Amount given by customer (Rs.)</label>
 <div class="v71-amount-action"><input id="tenderedAmount" name="amount_lkr" type="number" min="0.01" step="0.01" placeholder="Eg: 15000" required style="border-color: #ffcc00; width: 100%; padding: 10px; background: #091c1d; color: #fff; border-radius: 6px; font-size: 15px; font-weight: bold;"></div>  
 
 <!-- CHANGE CALCULATION BOX -->
 <div style="display:flex; justify-content: space-between; align-items: center; margin-top: 10px; background: #264943; padding: 12px; border-radius: 6px;">
     <span style="color: #a6c4b9; font-size: 13px;">Change (Ithuru):</span>
     <strong id="changeAmount" style="color: #b5f5c9; font-size: 18px;">Rs. 0.00</strong>
 </div>

 <div class="bill-cols" style="margin-top: 15px;">
    <div>
        <label for="paymentMethod">Method</label>
        <select id="paymentMethod" name="method" required>
            <option value="cash">{{ __('Cash') }}</option>
            <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
            <option value="card">{{ __('Card') }}</option>
            <option value="other">{{ __('Other') }}</option>
        </select>
    </div>
    <div>
        <label for="paymentReference">Reference (optional)</label>
        <input id="paymentReference" name="reference" maxlength="100" placeholder="Receipt / transfer ref">
    </div>
 </div>  
 <button type="submit" id="paymentSave" style="width:100%;margin-top:15px; background: #a1ecb7; color: #062019;">Record Payment</button>  
 </form>  
 <p class="bill-note" style="margin-top: 10px;">{{ __('Enter this payment only. Partial payments reduce the balance; cash above the balance is returned as change.') }}</p>  
 <script>
    const balanceDue = {{ $balance }};
    document.getElementById('tenderedAmount').addEventListener('input', function() {
        let tendered = parseFloat(this.value) || 0;
        if (tendered >= balanceDue) {
            document.getElementById('changeAmount').innerText = 'Rs. ' + (tendered - balanceDue).toLocaleString('en-LK', {minimumFractionDigits: 2});

        } else {
            document.getElementById('changeAmount').innerText = 'Rs. 0.00';

        }
    });

    document.getElementById('paymentForm')?.addEventListener('submit', function(e){
        let tendered = parseFloat(document.getElementById('tenderedAmount').value) || 0;
        if (tendered <= 0) {
            e.preventDefault();
            alert('Please enter a valid amount.');
            return;
        }
        document.getElementById('paymentSave').disabled=true; 
        document.getElementById('paymentSave').innerText='Saving...';
    });
 </script>  
 @else
 <div style="background: #184636; padding: 20px; border-radius: 8px; text-align: center; border: 1px solid #528c74;">
    <strong style="color: #b5f5c9; font-size: 18px;">Fully Paid!</strong>
    <p class="bill-note" style="margin-top: 5px;">No outstanding balance for this invoice.</p>
 </div>
 @endif  
 @endif  

 </aside>  
 </div>  
</div>  
@if(request()->boolean('print'))
<script>window.addEventListener('load',()=>window.print(),{once:true});</script>
@endif
@endsection