<!doctype html><html lang="{{ str_replace('_','-',app()->getLocale()) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $invoice->invoice_number }}</title><style>body{font:14px system-ui,'Nirmala UI','Latha',sans-serif;color:#111;background:white;margin:20px}main{max-width:800px;margin:auto}h1{font-size:22px}table{border-collapse:collapse;width:100%;margin:16px 0}th,td{padding:8px 5px;border-bottom:1px solid #ccc;text-align:left;overflow-wrap:anywhere}th:last-child,td:last-child{text-align:right}.summary{margin-left:auto;max-width:320px}.summary p{display:flex;justify-content:space-between;gap:12px}.terms{white-space:pre-wrap;overflow-wrap:anywhere;font-size:12px}.warranty{border:1px solid #aaa;padding:10px;margin:12px 0;break-inside:avoid}.actions{margin-bottom:18px}tr{break-inside:avoid}@media print{.actions{display:none}body{margin:0}@page{size:{{ $paper==='a4'?'A4':($paper==='80mm'?'80mm 297mm':'58mm 297mm') }};margin:{{ $paper==='a4'?'12mm':'3mm' }}}}
@if($paper!=='a4')
body{font-size:10px}main{width:{{ $paper==='80mm'?'74mm':'52mm' }}}h1{font-size:16px}th,td{padding:5px 2px}.terms{font-size:10px}small{font-size:9px}
@endif
</style></head><body><main><div class="actions"><button type="button" onclick="window.print()">{{ __('Print / Save as PDF') }}</button></div><h1>CCS Solutions</h1><h2>{{ __('Invoice') }} {{ $invoice->invoice_number }}</h2><p>{{ __(ucwords(str_replace('_',' ',$invoice->status))) }}</p>
@if($invoice->status==='draft')
<strong>{{ __('DRAFT · Not final and not proof of payment') }}</strong>
@endif
<p>{{ __('Customer') }}: {{ $invoice->customer_name }} · {{ $invoice->phone }}<br>{{ __('Vehicle') }}: {{ $invoice->registration_number }} · {{ $invoice->make }} {{ $invoice->model }}<br>{{ __('Job') }}: {{ $invoice->job_number }}<br>{{ __('Date') }}: {{ $invoice->issued_at??$invoice->created_at }}</p><table><thead><tr><th>{{ __('Service / Part') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Unit Price') }}</th><th>{{ __('Amount') }}</th></tr></thead><tbody>
@foreach($items as $item)
<tr><td>{{ $item->description }}
@if($item->item_year||$item->item_condition||$item->item_supplier)
<br><small>{{ implode(' · ',array_filter([$item->item_year,$item->item_condition,$item->item_supplier])) }}</small>
@endif
@if(($item->item_origin??'')==='customer_supplied')
<br><small>{{ __('Customer Supplied Part') }}</small>
@endif
@if($item->serial_number)
<br><small>{{ __('Serial Number') }}: {{ $item->serial_number }}</small>
@endif
</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price_lkr,2) }}</td><td>{{ number_format($item->line_total_lkr,2) }}</td></tr>
@endforeach
</tbody></table><div class="summary"><p><span>{{ __('Subtotal') }}</span><strong>{{ number_format($invoice->subtotal_lkr,2) }}</strong></p><p><span>{{ __('Discount') }}</span><strong>{{ number_format($invoice->discount_lkr,2) }}</strong></p><p><span>{{ __('Total') }} (LKR)</span><strong>{{ number_format($invoice->total_lkr,2) }}</strong></p><p><span>{{ __('Paid') }}</span><strong>{{ number_format($paid,2) }}</strong></p><p><span>{{ __('Balance Due') }}</span><strong>{{ number_format(max(0,$invoice->total_lkr-$paid),2) }}</strong></p></div><h3>{{ __('Payment History') }}</h3><table><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Method') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Balance Due') }}</th></tr></thead><tbody>
@php($running=0)
@forelse($payments as $payment)
@php($running=round($running+$payment->amount_lkr,2))
<tr><td>{{ $payment->paid_at }}</td><td>{{ __($payment->method) }}<br><small>{{ $payment->reference }}</small></td><td>{{ number_format($payment->amount_lkr,2) }}</td><td>{{ number_format(max(0,$invoice->total_lkr-$running),2) }}</td></tr>
@empty
<tr><td colspan="4">{{ __('No payments yet.') }}</td></tr>
@endforelse
</tbody></table>
@foreach($invoiceWarranties as $warranty)
@php($terms=json_decode($warranty->terms_snapshot,true)??[])
<section class="warranty"><strong>{{ __('Warranty') }}: {{ $terms['name']??$warranty->rule_name }}</strong><p>{{ $terms['covered_description']??'' }} · {{ $terms['covered_serial']??'' }}</p><p>{{ __('Start Date') }}: {{ $warranty->starts_on }} · {{ __('Ends On') }}: {{ $warranty->expires_on??'—' }}<br>{{ __('Duration (months)') }}: {{ $terms['duration_months']??'—' }} · {{ __('Distance (km)') }}: {{ $terms['distance_km']??'—' }}<br>{{ __('Mileage Limit') }}: {{ $warranty->mileage_limit_km??'—' }}<br>{{ __('Free Services Remaining') }}: {{ $warranty->benefits['remaining'] }} / {{ $warranty->benefits['included'] }}</p><p>{{ __('Warranty Limit Basis') }}: {{ __(['time_first'=>'Time based','distance_first'=>'Distance based','whichever_first'=>'Whichever comes first','custom'=>'Custom - explain in conditions'][$terms['limit_basis']??'']??'See conditions') }}</p><p class="terms">{{ $terms['terms']??'—' }}</p></section>
@endforeach
</main></body></html>
