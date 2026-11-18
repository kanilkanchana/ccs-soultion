@extends('ccs.layout')  
@section('title','Credit Ledger Management')  
@section('body-class','ccs-finance-page')
@section('content')
@include('ccs.partials.finance-nav')  
<style>  
.ccs-ledger{--paper:#0c1f30;--edge:#1a3c5c;--ink:#effbf4;--muted:#a6c4b9;color:var(--ink); font-family: system-ui, sans-serif;}  
.ledger-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.ledger-header h1 { margin: 0; color: #ffcc00; font-size: 24px; }
.ledger-card { background: var(--paper); border: 1px solid var(--edge); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
.table-wrap { overflow-x: auto; }
.ledger-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.ledger-table th { background: #1a3c5c; color: #87eec2; padding: 12px 10px; text-align: left; }
.ledger-table td { padding: 12px 10px; border-bottom: 1px dashed #234c70; vertical-align: middle; }
.badge-due { background: #62262b; color: #ffaaaa; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.badge-paid { background: #184636; color: #b5f5c9; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
.btn-action { background: #ffcc00; color: #000; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; }
</style>

<div class="ccs-ledger">
    <div class="ledger-header">
        <h1>{{ __('Credit Ledger Management (ණය කළමනාකරණය)') }}</h1>
        <a href="{{ route('ccs.pos') }}" style="background: #27504b; color: #fff; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-weight: bold;">{{ __('← Back to POS') }}</a>
    </div>

    <div class="ledger-card">
        <form method="GET" action="{{ route('ccs.credit.ledger') }}" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px"><input name="q" value="{{ $search }}" maxlength="150" style="flex:1;min-width:240px" placeholder="{{ __('Search name, customer ID, NIC, phone, plate, job or invoice') }}"><button type="submit">{{ __('Search') }}</button><a class="btn secondary" href="{{ route('ccs.credit.ledger') }}">{{ __('Reset') }}</a></form>
        <div class="table-wrap">
            <table class="ledger-table" data-v68-records data-v68-secondary="2,3">
                <thead>
                    <tr>
                        <th>Invoice / Job</th>
                        <th>{{ __('Customer Name') }}</th>
                        <th>{{ __('NIC Number') }}</th>
                        <th>{{ __('Contact & Address') }}</th>
                        <th>{{ __('Original Credit') }}</th>
                        <th>{{ __('Paid Amount') }}</th>
                        <th>{{ __('Outstanding Balance') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th style="text-align: right;">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditInvoices as $ci)
                    <tr>
                        <td style="font-weight: bold; color: #b4f6c8;">{{ $ci->invoice_number }}<br><small>{{ $ci->registration_number }}</small></td>
                        <td>{{ $ci->customer_name }}</td>
                        <td>{{ $ci->nic ?? 'N/A' }}</td>
                        <td>{{ $ci->phone }}<br><small style="color: #a6c4b9;">{{ $ci->address ?? 'N/A' }}</small></td>
                        <td>Rs. {{ number_format($ci->total_lkr, 2) }}</td>
                        <td style="color: #b5f5c9;">Rs. {{ number_format($ci->paid_amount, 2) }}</td>
                        <td style="color: #ffaaaa; font-weight: bold;">Rs. {{ number_format($ci->balance, 2) }}</td>
                        <td>
                            @if($ci->balance <= 0)
                                <span class="badge-paid">PAID</span>
                            @else
                                <span class="badge-due">DUE</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('ccs.invoices.show', $ci->id) }}" class="btn-action">View / Pay →</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align: center; color: #8ea8a2; padding: 25px;">No active credit transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection