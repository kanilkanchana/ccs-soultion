@extends('ccs.layout')
@section('title',__('All Customer Serial Records'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
<style>
.serial-records-head{display:flex;align-items:flex-start;justify-content:space-between;gap:15px;flex-wrap:wrap}.serial-records-head .actions{margin-top:6px}.serial-records-filter{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.serial-records-filter label{flex:1 1 260px;margin:0}.serial-records-filter label span{display:block;margin-bottom:6px}.serial-records-filter input{width:100%;margin:0}.serial-records-filter button,.serial-records-filter a{width:auto}.serial-records-table td{white-space:normal;overflow-wrap:anywhere}.serial-records-table small{display:block;color:#a6c4b9}.serial-records-table .serial-code{font-weight:700}.serial-records-scroll{overflow:auto;max-height:65dvh}.serial-records-table{min-width:760px}.serial-records-hint{color:#a6c4b9;line-height:1.6}@media(max-width:700px){.serial-records-filter label,.serial-records-filter button,.serial-records-filter a{flex:1 1 100%}.serial-records-scroll{max-height:70dvh}}
</style>
<div class="directory-head serial-records-head"><div><h1>{{ __('All Customer Serial Records') }}</h1><p>{{ $customer->name }} · {{ $customer->customer_code }} @if($customer->phone) · {{ $customer->phone }} @endif</p></div><div class="actions"><a class="btn" target="_blank" rel="noopener" href="{{ route('ccs.customers.serial-records',['id'=>$customer->id,'serial'=>$serial,'print'=>1]) }}">{{ __('Print / Save as PDF') }}</a><a class="btn secondary" href="{{ route('ccs.customers.show',$customer->id) }}">{{ __('Customer Profile') }}</a><a class="btn secondary" href="{{ route('ccs.warranties',['customer_id'=>$customer->id]) }}">{{ __('Customer Warranties') }}</a></div></div>
<section class="card"><label for="serialCustomerSearch">Customer සොයන්න / Search customer</label><input id="serialCustomerSearch" type="search" autocomplete="off" maxlength="100" placeholder="නම / Customer ID / Phone / Vehicle No" style="width:100%;margin-top:8px"><div id="serialCustomerStatus" role="status" style="margin-top:8px"></div><div id="serialCustomerResults" style="display:grid;gap:6px;max-height:260px;overflow:auto"></div></section>
<section class="card"><form class="serial-records-filter" method="GET" action="{{ route('ccs.customers.serial-records',$customer->id) }}"><label><span>{{ __('Search Exact Serial') }}</span><input name="serial" maxlength="100" value="{{ $serial }}" autocomplete="off" placeholder="{{ __('Actual serial number') }}"></label><button>{{ __('Search') }}</button><a class="btn secondary" href="{{ route('ccs.customers.serial-records',$customer->id) }}">{{ __('Show All') }}</a></form><p class="serial-records-hint">{{ __('Showing serial records for this customer across their vehicles and jobs. The same serial can appear more than once for different visits or sources.') }}</p></section>
<section class="directory-panel"><h2 class="directory-section-title">{{ __('Serial Records') }} · {{ $records->total() }}</h2><div class="v68-serial-grid">
@forelse($records as $row)
<article class="v68-record-card"><div class="v68-card-head"><div class="v68-person"><span class="v68-avatar" aria-hidden="true">▣</span><div><h2>{{ $row->part_name }}</h2><small>{{ $row->serial_number }}</small></div></div></div>
<div class="v68-card-facts"><div><small>{{ __('Vehicle') }}</small><strong>{{ $row->registration_number }}</strong><small>{{ $row->make }} {{ $row->model }}</small></div><div><small>{{ __('Job No:') }} / {{ __('Service Date:') }}</small><strong>{{ $row->job_number }}</strong><small>{{ $row->recorded_at ?: '—' }}</small></div></div>
<div class="v68-serial-reading"><small>{{ __('Reading / Details') }}</small><br>
@if($row->source==='battery_module')
{{ __('Module Position') }} {{ $row->position }} · {{ $row->voltage === null ? __('Unmeasured') : $row->voltage.' V' }}<br><small>{{ __('Battery service reading') }}</small>
@else
@if($row->is_battery_module){{ __('Measured voltage not recorded') }} @endif @if($row->item_condition){{ __('Condition') }}: {{ $row->item_condition }} @endif @if($row->item_year) · {{ __('Year') }}: {{ $row->item_year }} @endif @if(!$row->is_battery_module && !$row->item_condition && !$row->item_year){{ __('Finalized invoice part') }}@endif
@endif
</div><a class="btn secondary" href="{{ $row->source==='battery_module' ? route('ccs.jobs.battery',['id'=>$row->job_id,'record'=>$row->service_id]) : route('ccs.jobs.battery',$row->job_id) }}">{{ __('Service / Parts Record') }} →</a></article>
@empty
<p>{{ __('No serial records found for this customer.') }}</p>
@endforelse
</div><div class="directory-footer">{{ $records->links() }}</div></section>
<script>
(()=>{const field=document.getElementById('serialCustomerSearch'),list=document.getElementById('serialCustomerResults'),status=document.getElementById('serialCustomerStatus');let timer,version=0;const endpoint=@json(route('ccs.serial-records.customer-search')), base=@json(url(request()->is('t','t/*')?'/t/customers':'/ccs/customers'));field.addEventListener('input',()=>{clearTimeout(timer);const n=++version,q=field.value.trim();list.replaceChildren();status.textContent='';if(q.length<2)return;timer=setTimeout(async()=>{status.textContent='Searching…';try{const res=await fetch(endpoint+'?'+new URLSearchParams({q}),{credentials:'same-origin',headers:{Accept:'application/json'},cache:'no-store'});if(!res.ok)throw Error();const data=await res.json();if(n!==version)return;status.textContent=data.data.length?'Select customer / Customer තෝරන්න':'No customers found';for(const c of data.data){const a=document.createElement('a');a.className='btn secondary';a.href=base+'/'+encodeURIComponent(c.id)+'/serial-records';a.textContent=[c.name,c.customer_code,c.phone].filter(Boolean).join(' · ');list.appendChild(a)}}catch(e){if(n===version)status.textContent='Search failed. Please try again.'}},250)});})();
</script>
@endsection
