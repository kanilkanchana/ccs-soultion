@extends('ccs.layout')
@section('title',__('Payment Finalization'))
@section('body-class','ccs-finance-page')
@section('content')
@include('ccs.partials.finance-nav')
<div class="page-head"><div><h1>{{ __('Payment Finalization') }}</h1><p class="muted">{{ __('Review a bill, then open it to finalize, record a payment or print the receipt.') }}</p></div><a class="btn" href="{{ route('ccs.pos') }}">{{ __('← Back to POS') }}</a></div>
<section class="finance-table-card"><form method="GET" action="{{ route('ccs.payments.index') }}" class="finance-filter"><div><label for="paymentSearch">{{ __('Search') }}</label><input id="paymentSearch" name="q" maxlength="150" value="{{ $q }}" placeholder="{{ __('Customer, phone, vehicle, invoice or job number') }}"></div><div><label for="paymentStatus">{{ __('Status') }}</label><select id="paymentStatus" name="status"><option value="">{{ __('All Statuses') }}</option>
@foreach(['draft'=>'Draft','issued'=>'Due','partially_paid'=>'Partially Paid','paid'=>'Paid'] as $value=>$label)
<option value="{{ $value }}" @selected($status===$value)>{{ __($label) }}</option>
@endforeach
</select></div><button type="submit">{{ __('Filter') }}</button><a class="btn secondary" href="{{ route('ccs.payments.index') }}">{{ __('Reset') }}</a></form>
<div class="finance-records-scroll" tabindex="0" aria-label="{{ __('Scrollable invoices') }}"><table class="finance-records"><thead><tr><th>{{ __('Invoice / Job') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Vehicle') }}</th><th>{{ __('Date') }}</th><th class="money">{{ __('Total') }}</th><th class="money">{{ __('Paid') }}</th><th class="money">{{ __('Balance Due') }}</th><th>{{ __('Status') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
@forelse($invoices as $invoice)
<tr><td><strong>{{ $invoice->invoice_number }}</strong><small>{{ $invoice->job_number }}</small></td><td>{{ $invoice->customer_name }}<small>{{ $invoice->customer_code }}</small></td><td>{{ $invoice->registration_number }}</td><td>{{ substr($invoice->issued_at??$invoice->created_at??'',0,10)?:'—' }}</td><td class="money">{{ number_format($invoice->total_lkr,2) }}</td><td class="money">{{ number_format($invoice->paid_lkr,2) }}</td><td class="money">{{ number_format($invoice->due_lkr,2) }}</td><td><span class="finance-state finance-state-{{ $invoice->display_status }}">{{ __($invoice->status==='draft'?'Draft':($invoice->due_lkr<=0?'Paid':($invoice->paid_lkr>0?'Partially Paid':'Due'))) }}</span></td><td><a class="finance-open-bill" href="{{ route('ccs.invoices.show',$invoice->id) }}">{{ __($invoice->status==='draft'?'Finalize':($invoice->due_lkr>0?'Record Payment':'View / Print')) }} →</a></td></tr>
@empty
<tr><td colspan="9" class="muted">{{ __('No matching invoices.') }}</td></tr>
@endforelse
</tbody></table></div><div class="finance-table-footer"><span>{{ $invoices->firstItem()??0 }}–{{ $invoices->lastItem()??0 }} / {{ $invoices->total() }}</span>{{ $invoices->links() }}</div></section>
@endsection
