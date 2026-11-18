@extends('ccs.layout')
@section('title',__('Customer Warranties'))
@section('body-class','ccs-directory-page')
@section('content')
@include('ccs.partials.directory-styles')
@include('ccs.partials.editor-styles')
<style>
.warranty-toolbar>div{min-width:180px}.warranty-toolbar input,.warranty-toolbar select{width:100%;min-width:0}.warranty-cards{grid-template-columns:repeat(auto-fit,minmax(min(100%,330px),1fr))!important}.warranty-card{padding:17px!important}.warranty-card h2{line-height:1.4}.warranty-card .actions .btn{font-size:12px;padding:9px 11px}.warranty-card .actions{align-items:center}.warranty-card details{margin-top:12px}.warranty-facts{gap:12px!important}.warranty-dialog{box-sizing:border-box}.warranty-dialog .warranty-row>div{min-width:0}.warranty-dialog input,.warranty-dialog select,.warranty-dialog textarea{box-sizing:border-box;max-width:100%}.warranty-dialog .package details>form{padding-top:8px}@media(min-width:1500px){.warranty-cards{grid-template-columns:repeat(3,minmax(0,1fr))!important}}@media(max-width:600px){.warranty-toolbar>div{flex-basis:100%}.warranty-toolbar>button{width:100%}.page-head .actions{display:flex;flex-wrap:wrap}}

.warranty-card{background:#14262e!important;border-color:#2e454c!important}.warranty-dialog .package{background:#14262e;border:1px solid #2e454c;border-radius:12px;padding:18px;margin:12px 0}.warranty-dialog .package>strong{font-size:17px;display:block;margin-bottom:7px}.warranty-dialog .package small{color:#9bb7aa}.warranty-card .actions{display:flex;flex-wrap:wrap;gap:8px}.stock-panel input[type=checkbox]{width:auto}

.warranty-toolbar{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.warranty-toolbar>div{flex:1;min-width:200px}.warranty-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,350px),1fr));gap:16px;margin-top:18px}.warranty-card{border:1px solid #37675c;border-radius:14px;background:linear-gradient(145deg,#102e33,#132823);padding:20px;min-width:0}.warranty-card h2{font-size:18px;margin:12px 0}.warranty-card p{overflow-wrap:anywhere}.warranty-card .badge{display:inline-block;background:#28523f;border-radius:18px;padding:5px 10px;font-size:12px}.warranty-facts{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:18px 0}.warranty-facts small{display:block;color:#acc7bd}.warranty-balance{border-top:1px solid #37675c;border-bottom:1px solid #37675c;padding:12px 0;margin:14px 0}.warranty-dialog{width:min(800px,94vw);max-height:90vh;overflow:auto;border:1px solid #436f60;border-radius:14px;padding:22px;background:#10292b;color:#eaf8ee}.warranty-dialog::backdrop{background:#0009}.warranty-dialog textarea{width:100%;min-height:90px;background:#091c1e;color:inherit}.warranty-dialog .dialog-head{display:flex;justify-content:space-between;align-items:center;gap:10px}.warranty-dialog .package{border-bottom:1px solid #37675c;padding:14px 0}.warranty-dialog form{margin:12px 0}.warranty-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.warranty-card summary,.warranty-dialog summary{cursor:pointer}.warranty-card form button,.warranty-dialog form button{margin-top:12px}.warranty-dialog:not([open]){display:none}@media(max-width:500px){.warranty-facts,.warranty-row{grid-template-columns:1fr}}
</style>
<div class="page-head"><div><h1>{{ __('Customer Warranties') }}</h1><p class="muted">{{ __('Stock part warranties are added automatically when a bill is finalized.') }}</p></div><div class="actions"><a class="btn secondary" href="{{ route('ccs.stock') }}">{{ __('Parts & Stock') }}</a><a class="btn secondary" href="{{ route('ccs.customer-access.serial-preview',['customer_id'=>$selectedCustomer,'serial_number'=>$q]) }}">{{ __('Customer Serial Search') }}</a><button type="button" id="openPackages">{{ __('Warranty Packages') }}</button><button type="button" id="openManualWarranty">{{ __('+ Add Other Warranty') }}</button></div></div>
<div class="actions" style="margin-bottom:16px"><a class="btn secondary" href="{{ route('ccs.claims.index') }}">{{ __('Manage Warranty Claims') }}</a>
@if($selectedCustomer)
<a class="btn" target="_blank" rel="noopener" href="{{ route('ccs.warranties.customer-preview',['customer_id'=>$selectedCustomer]) }}">{{ __('Preview Customer Warranty Page') }}</a>
@endif
</div>
<section class="card"><form method="GET" action="{{ route('ccs.warranties') }}"><div class="warranty-toolbar"><div><label for="customerFilterText">{{ __('Find Customer') }}</label><input id="customerFilterText" maxlength="150" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="warrantyCustomerResults" placeholder="{{ __('Name, customer ID or phone') }}"><div id="warrantyCustomerResults" hidden style="max-height:260px;overflow:auto;margin-top:8px;border:1px solid #436f60;border-radius:8px;padding:8px"></div><small id="warrantyCustomerSearchStatus" role="status" aria-live="polite"></small><label for="warrantyCustomer">{{ __('Select Customer') }}</label><select name="customer_id" id="warrantyCustomer"><option value="">{{ __('All Customers') }}</option>
@foreach($customers as $customer)
<option value="{{ $customer->id }}" @selected($selectedCustomer===$customer->id)>{{ $customer->name }} · {{ $customer->customer_code }} · {{ $customer->phone }}</option>
@endforeach
</select></div><div><label>{{ __('Filter Cards') }}</label><input name="q" maxlength="150" value="{{ $q }}" placeholder="{{ __('Vehicle plate, serial, job or package') }}"></div><button type="submit">{{ __('View Warranty Cards') }}</button></div></form></section>

@if($selectedCustomer && $partsWithoutCards->isNotEmpty())
<section class="directory-panel" style="margin-bottom:18px"><h2 class="directory-section-title">{{ __('Parts without a warranty card') }}</h2><p style="padding:0 18px">{{ __('A saved serial does not automatically include warranty. Check the package agreed for this sale.') }}</p><div class="directory-scroll"><table class="directory-table"><thead><tr><th>{{ __('Part / Serial') }}</th><th>{{ __('Job / Invoice') }}</th><th>{{ __('Warranty Status') }}</th><th>{{ __('Action') }}</th></tr></thead><tbody>
@foreach($partsWithoutCards as $part)
<tr><td>{{ $part->description }}<small>{{ $part->serial_number ?: '—' }}</small></td><td>{{ $part->registration_number }}<small>{{ $part->job_number }} · <a href="{{ route('ccs.invoices.show',$part->invoice_id) }}">{{ $part->invoice_number }}</a></small></td><td>{{ __($part->sale_warranty_snapshot ? 'Sale package saved, but no card recorded. Ask the owner to check.' : 'No warranty package was saved with this sale.') }}</td><td><button type="button" class="secondary" data-warranty-part="{{ $part->id }}" data-warranty-job="{{ $part->repair_job_id }}">{{ __('Review / Add Warranty') }}</button></td></tr>
@endforeach
</tbody></table></div><p class="muted" style="padding:0 18px">{{ __('Up to 50 recent parts are shown. Adding warranty requires the agreed package and owner approval; existing sales are not changed automatically.') }}</p></section>
@endif

<p class="muted">{{ $entries->total() }} {{ __('warranty cards') }}</p><div class="warranty-cards" data-v68-cards="warranties">
@forelse($entries as $entry)
@php
$snapshot=json_decode($entry->terms_snapshot,true)??[];
$approved=$entry->approved_by_owner && $entry->active && ($snapshot['provisional']??true)===false;
$card=$entry->card_status;
$labels=['pending'=>'Pending confirmation','no_warranty'=>'No Warranty','not_started'=>'Not started','time_elapsed'=>'Time limit elapsed','time_remaining'=>'Time remaining','no_time_limit'=>'Mileage / agreed conditions'];
$benefits=$entry->benefits;
@endphp
<article class="warranty-card"><div style="display:flex;gap:8px;justify-content:space-between;flex-wrap:wrap"><strong>{{ $card['card_number'] }}</strong><span class="badge">{{ __($approved?($labels[$card['state']]??'Approved'):'Pending confirmation') }}</span></div><h2>{{ $snapshot['name']??$entry->rule_name }}</h2><p><strong>{{ $entry->customer_name }}</strong><br>{{ $entry->registration_number }} · {{ $entry->make }} {{ $entry->model }}</p><p>{{ $snapshot['covered_description']??__('Job / service warranty') }}<br><strong>{{ $snapshot['covered_serial']??'' }}</strong><small> · {{ $entry->job_number }}</small></p>
<div class="warranty-facts"><div><small>{{ __('Given / Start Date') }}</small><strong>{{ $entry->starts_on??'—' }}</strong></div><div><small>{{ __('Ends On') }}</small><strong>{{ $entry->expires_on??'—' }}</strong></div><div><small>{{ __('Warranty Range') }}</small><strong>{{ ($snapshot['coverage_kind']??'')==='no_warranty'?__('No Warranty'):((int)($snapshot['duration_months']??0)>0?$snapshot['duration_months'].' '.__('months'):'—').' / '.((int)($snapshot['distance_km']??0)>0?number_format($snapshot['distance_km']).' km':'—') }}</strong></div><div><small>{{ __('Time Left') }}</small><strong>{{ $approved && $card['remaining_days']!==null?$card['remaining_days'].' '.__('days'): '—' }}</strong></div></div>
@if($entry->mileage_limit_km!==null)
<p>{{ __('Mileage Limit') }}: {{ number_format($entry->mileage_limit_km) }} km<br>{{ __('Distance Left at Saved Mileage') }}: {{ $approved && $card['remaining_km']!==null?number_format($card['remaining_km']).' km':__('Current mileage not available') }}</p>
@endif
@if($benefits['included']>0)
<div class="warranty-balance"><strong>{{ __('Free Services Remaining') }}: {{ $benefits['remaining'] }} / {{ $benefits['included'] }}</strong><br><small>{{ __('Used') }}: {{ $benefits['used'] }}</small></div>
@endif
<details><summary>{{ __('Conditions & History') }}</summary><p style="white-space:pre-wrap">{{ $snapshot['terms']??'—' }}</p><small>{{ __('Warranty Limit Basis') }}: {{ __(['time_first'=>'Time based','distance_first'=>'Distance based','whichever_first'=>'Whichever comes first','custom'=>'Custom - explain in conditions'][$snapshot['limit_basis']??'']??'Needs owner confirmation') }}</small>
@foreach($benefits['visits'] as $visit)
<p>{{ $visit->service_date }} · {{ $visit->job_number }} · {{ $visit->mileage_km??'—' }} km</p>
@endforeach
</details>
@if(!$approved)
<p class="muted">{{ $snapshot['approval_pending_reason']??__('This card is not published to the customer yet.') }}</p>
@endif
@if($canApprove && ($snapshot['provisional']??true)!==false)
<details><summary>{{ __('Confirm This Card') }}</summary><form method="POST" action="{{ route('ccs.warranties.approve',$entry->id) }}">
@csrf
@if((int)($snapshot['distance_km']??0)>0 && $entry->mileage_limit_km===null)
<label>{{ __('Starting Mileage (km)') }}</label><input name="starting_mileage_km" type="number" min="0" max="2000000" required>
@endif
<label><input type="checkbox" name="confirmation" value="1" required style="width:auto"> {{ __('Confirm saved terms and dates') }}</label><button type="submit">{{ __('Confirm Warranty') }}</button></form></details>
@endif
@if($approved && $benefits['remaining']>0)
<details style="margin-top:12px"><summary>{{ __('Record Free Service') }}</summary><form method="POST" action="{{ route('ccs.warranties.free-services',$entry->id) }}">
@csrf
<label>{{ __('Service Job (same vehicle)') }}</label><select name="repair_job_id" required><option value="">{{ __('Choose Job') }}</option>
@foreach($jobs->where('vehicle_id',$entry->vehicle_id) as $serviceJob)
<option value="{{ $serviceJob->id }}">{{ $serviceJob->job_number }}</option>
@endforeach
</select><label>{{ __('Service Date') }}</label><input name="service_date" type="date" required max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}"><label>{{ __('Mileage (km)') }}</label><input name="mileage_km" type="number" min="0" max="2000000"><label><input type="checkbox" name="confirmation" value="1" required style="width:auto"> {{ __('Service completed and eligibility checked against the agreed conditions.') }}</label><button type="submit">{{ __('Record Free Service') }}</button></form></details>
@endif
@if($approved)
@php
$phone=preg_replace('/\D/','',$entry->customer_whatsapp?:$entry->customer_phone);
if(strlen($phone)===10 && str_starts_with($phone,'0'))$phone='94'.substr($phone,1);
$message="CCS Warranty ".$card['card_number']."\nCustomer: ".$entry->customer_name."\nVehicle: ".$entry->registration_number."\nPart: ".($snapshot['covered_description']??'Job / service')."\nSerial: ".($snapshot['covered_serial']??'—')."\nPackage: ".($snapshot['name']??$entry->rule_name)."\nStart: ".$entry->starts_on."\nEnds: ".($entry->expires_on??'See mileage / conditions')."\nDuration: ".($snapshot['duration_months']??0)." months; distance: ".($snapshot['distance_km']??0)." km\nMileage limit: ".($entry->mileage_limit_km??'—')."\nFree services remaining: ".$benefits['remaining']." / ".$benefits['included']."\nConditions: ".($snapshot['terms']??'—');
@endphp
<div class="actions" style="margin-top:14px"><a class="btn secondary" href="{{ route('ccs.claims.index',['warranty_id'=>$entry->id]) }}">{{ __('New Warranty Claim') }}</a><a class="btn secondary" target="_blank" rel="noopener" href="{{ route('ccs.warranties.print',$entry->id) }}">{{ __('Print Warranty / Save PDF') }}</a>
@if(strlen($phone)>=8 && strlen($phone)<=15)
<!-- CCS_CARE_V3_WARRANTY -->@include('ccs.partials.care-icon',['careKind'=>'serial','careId'=>$entry->id])
@else
<small>{{ __('Add a valid customer phone / WhatsApp number to share.') }}</small>
@endif
<a href="{{ route('ccs.customers.serial-records',$entry->customer_id) }}">{{ __('All Customer Serial Records') }}</a> @if(!empty($snapshot['covered_serial']))<a href="{{ route('ccs.customers.serial-records',['id'=>$entry->customer_id,'serial'=>$snapshot['covered_serial']]) }}">{{ __('View This Serial') }}</a>@endif</div>
@endif
<p class="muted" style="font-size:11px">{{ __('Time and distance shown are recorded limits. Coverage depends on the agreed conditions.') }}</p></article>
@empty
<section class="card"><p>{{ __('No matching warranty cards for this customer.') }}</p></section>
@endforelse
</div><div style="margin-top:18px">{{ $entries->links() }}</div>
<dialog class="warranty-dialog editor-window" id="packagesDialog"><div class="dialog-head"><h2>{{ __('Warranty Packages') }}</h2><button type="button" data-close="packagesDialog">{{ __('Close') }}</button></div><p class="muted">{{ __('Default packages are prepared. Review once, then choose an approved package in Parts & Stock. Add a new package whenever terms differ.') }}</p>
<div class="package-list">
@foreach($rules as $rule)
<section class="package"><strong>{{ $rule->name }}</strong><small>{{ __($rule->package_key?'Default package':'Custom package') }}</small><small> · {{ __($rule->approved_by_owner&&$rule->active?'Approved':'Draft') }}</small><p>{{ $rule->duration_months??0 }} {{ __('months') }} · {{ $rule->distance_km??'—' }} km · {{ $rule->free_service_count }} {{ __('free services') }}</p><details><summary>{{ __('Conditions') }}</summary><p style="white-space:pre-wrap">{{ $rule->terms }}</p></details>
@if($canApprove)
<button type="button" class="secondary" data-package-edit="editPackage{{ $rule->id }}">{{ __('Edit Package Details') }}</button>
@if(!$rule->approved_by_owner)
<form method="POST" action="{{ route('ccs.warranties.rules.approve',$rule->id) }}">
@csrf
<input type="hidden" name="limit_basis" value="{{ $rule->limit_basis }}"><label><input type="checkbox" name="confirmation" value="1" required style="width:auto"> {{ __('I confirm these warranty conditions are owner-approved.') }}</label><button type="submit">{{ __('Approve Package') }}</button></form>
@endif
@endif
</section>
@endforeach
</div>
<p><button type="button" id="openNewPackage">{{ __('+ Add New Package') }}</button></p>
@if(!$canApprove)
<p class="muted">{{ __('Package approval is available to the designated owner account.') }}</p>
@endif
</dialog>
<dialog class="warranty-dialog editor-window" id="newPackageDialog"><div class="dialog-head"><h2>{{ __('+ Add New Package') }}</h2><button type="button" data-close="newPackageDialog">{{ __('Close') }}</button></div><form method="POST" action="{{ route('ccs.warranties.rules.store') }}">
@csrf
<label>{{ __('Package Name') }}</label><input name="name" required maxlength="255" value="{{ old('name') }}"><label>{{ __('Category') }}</label><select name="category"><option value="hybrid_battery">{{ __('Hybrid Battery') }}</option><option value="abs">ABS</option><option value="other">{{ __('Other Service') }}</option></select><label>{{ __('Coverage Type') }}</label><select name="coverage_kind"><option value="standard">Other</option><option value="reconditioned_battery">Reconditioned battery</option><option value="new_battery">Brand-new battery</option><option value="module">Module</option><option value="abs">ABS</option><option value="no_warranty">No warranty</option></select><div class="warranty-row"><div><label>{{ __('Duration (months)') }}</label><input name="duration_months" type="number" min="0" max="120"></div><div><label>{{ __('Distance (km)') }}</label><input name="distance_km" type="number" min="0" max="1000000"></div></div><label>{{ __('Included Free Services') }}</label><input name="free_service_count" type="number" min="0" max="20" value="0"><label>{{ __('Warranty Limit Basis') }}</label><select name="limit_basis" required><option value="">{{ __('Choose confirmed rule') }}</option><option value="time_first">{{ __('Time based') }}</option><option value="distance_first">{{ __('Distance based') }}</option><option value="whichever_first">{{ __('Whichever comes first') }}</option><option value="custom">{{ __('Custom - explain in conditions') }}</option></select><label>{{ __('Conditions / Exclusions') }}</label><textarea name="terms" required maxlength="5000"></textarea><div class="editor-footer"><button type="submit">{{ __('Save Package Draft') }}</button></div></form></dialog>
@if($canApprove)
@foreach($rules as $rule)
<dialog class="warranty-dialog editor-window" id="editPackage{{ $rule->id }}"><div class="dialog-head"><h2>{{ __('Edit Package Details') }} · {{ $rule->name }}</h2><button type="button" data-close="editPackage{{ $rule->id }}">{{ __('Close') }}</button></div><form method="POST" action="{{ route('ccs.warranties.rules.update',$rule->id) }}">
@csrf<input type="hidden" name="package_editor_id" value="{{ $rule->id }}">
<label>{{ __('Package Name') }}</label><input name="name" required maxlength="255" value="{{ $rule->name }}">
@if($rule->approved_by_owner)
<p class="muted">{{ __('Saving creates a new draft revision. Approve and select it in stock for future sales.') }}</p>
@endif
<div class="warranty-row"><div><label>{{ __('Duration (months)') }}</label><input name="duration_months" type="number" min="0" max="120" value="{{ $rule->duration_months }}"></div><div><label>{{ __('Distance (km)') }}</label><input name="distance_km" type="number" min="0" max="1000000" value="{{ $rule->distance_km }}"></div></div><label>{{ __('Included Free Services') }}</label><input name="free_service_count" type="number" min="0" max="20" value="{{ $rule->free_service_count }}"><label>{{ __('Warranty Limit Basis') }}</label><select name="limit_basis" required>
@foreach(['time_first'=>'Time based','distance_first'=>'Distance based','whichever_first'=>'Whichever comes first','custom'=>'Custom - explain in conditions'] as $value=>$label)
<option value="{{ $value }}" @selected($rule->limit_basis===$value)>{{ __($label) }}</option>
@endforeach
</select><label>{{ __('Conditions') }}</label><textarea name="terms" required maxlength="5000">{{ $rule->terms }}</textarea><div class="editor-footer"><button type="submit">{{ __('Save Package') }}</button></div></form></dialog>
@endforeach
@endif
<dialog class="warranty-dialog editor-window" id="manualWarrantyDialog"><div class="dialog-head"><h2>{{ __('Add Other Warranty') }}</h2><button type="button" data-close="manualWarrantyDialog">{{ __('Close') }}</button></div><p class="muted">{{ __('Use this for manual parts or services. Stock sales already create their cards automatically.') }}</p><form method="POST" action="{{ route('ccs.warranties.assign') }}">
@csrf
<label>{{ __('Select Customer') }}</label><select id="manualWarrantyCustomer" required><option value="">{{ __('Select Customer') }}</option>
@foreach($customers as $person)<option value="{{ $person->id }}" @selected($selectedCustomer===$person->id)>{{ $person->name }} · {{ $person->customer_code }} · {{ $person->phone }}</option>@endforeach
</select><p id="manualWarrantyStatus" class="muted" role="status"></p><label>{{ __('Repair Job') }}</label><select id="warrantyJob" name="repair_job_id" required><option value="">{{ __('Choose Job') }}</option>
@foreach($jobs as $job)
<option value="{{ $job->id }}" data-mileage="{{ $job->mileage_at_service_km }}" @selected((int)old('repair_job_id',$selectedJob)===$job->id)>{{ $job->job_number }} · {{ $job->registration_number }}</option>
@endforeach
</select><label>{{ __('Covered Battery / Part') }}</label><select id="warrantyPart" name="invoice_item_id"><option value="">{{ __('Whole job / saved battery module') }}</option></select><label>{{ __('Covered Serial (optional)') }}</label><input name="covered_serial" id="coveredSerial" maxlength="100" value="{{ old('covered_serial') }}"><label>{{ __('Warranty Package') }}</label><select name="warranty_rule_id" required><option value="">{{ __('Choose Rule') }}</option>
@foreach($rules as $rule)
<option value="{{ $rule->id }}">{{ $rule->name }} · {{ __($rule->approved_by_owner&&$rule->active?'Approved':'Draft') }}</option>
@endforeach
</select><div class="warranty-row"><div><label>{{ __('Start Date') }}</label><input name="starts_on" type="date" required value="{{ old('starts_on',today()->toDateString()) }}"></div><div><label>{{ __('Starting Mileage (km)') }}</label><input id="warrantyMileage" name="starting_mileage_km" type="number" min="0" max="2000000" value="{{ old('starting_mileage_km') }}"></div></div>
@if($canApprove)
<label><input style="width:auto" type="checkbox" name="issue_now" value="1"> {{ __('Issue approved warranty now') }}</label>
@endif
<div class="editor-footer"><button type="submit">{{ __('Save Warranty') }}</button></div></form></dialog>
<script>
(()=>{
const $=id=>document.getElementById(id),customer=$('warrantyCustomer'),allCustomers=[...customer.options].filter(option=>option.value).map(option=>({id:option.value,label:option.textContent}));
const search=$('customerFilterText'),results=$('warrantyCustomerResults'),searchStatus=$('warrantyCustomerSearchStatus'),t=value=>window.ccsText?window.ccsText(value):value;
let customerMatches=[];
function chooseCustomer(id){customer.value=String(id);customer.form.querySelector('[name=q]').value='';customer.form.requestSubmit();}
function findCustomers(){const q=search.value.trim().toLocaleLowerCase();results.replaceChildren();results.hidden=!q;search.setAttribute('aria-expanded',q?'true':'false');if(!q){searchStatus.textContent='';customerMatches=[];return;}
 const digits=q.replace(/[^0-9]/g,'');customerMatches=allCustomers.filter(option=>option.label.toLocaleLowerCase().includes(q)||(digits.length>=4&&/^[+0-9 ()-]+$/.test(q)&&option.label.split(' · ').at(-1).replace(/[^0-9]/g,'').includes(digits)));
 searchStatus.textContent=customerMatches.length?customerMatches.length+' '+t('matching customers. Choose the correct ID / phone.') : t('No matching customers.');
 for(const option of customerMatches){const button=document.createElement('button');button.type='button';button.className='secondary';button.textContent=option.label;button.style.cssText='display:block;width:100%;text-align:left;margin:4px 0';button.addEventListener('click',()=>chooseCustomer(option.id));results.append(button);}
}
search.addEventListener('input',findCustomers);search.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();if(customerMatches.length===1)chooseCustomer(customerMatches[0].id);else results.querySelector('button')?.focus();}if(event.key==='Escape'){results.hidden=true;search.setAttribute('aria-expanded','false');}});
customer.addEventListener('change',()=>chooseCustomer(customer.value));
$('openNewPackage').onclick=()=>{$('packagesDialog').close();$('newPackageDialog').showModal();};$('openPackages').onclick=()=>$('packagesDialog').showModal();$('openManualWarranty').onclick=()=>{$('manualWarrantyDialog').showModal();if(!$('manualWarrantyCustomer').value)$('manualWarrantyStatus').textContent=t('Select a customer to load their jobs and parts.');};document.querySelectorAll('[data-package-edit]').forEach(button=>button.addEventListener('click',()=>{$('packagesDialog').close();$(button.dataset.packageEdit).showModal();}));document.querySelectorAll('[data-close]').forEach(button=>button.onclick=()=>$(button.dataset.close).close());
let parts={{ Illuminate\Support\Js::from($partChoices) }},oldPart={{ Illuminate\Support\Js::from(old('invoice_item_id','')) }},jobs=$('warrantyJob'),partSelect=$('warrantyPart');
function fillParts(selected=''){partSelect.replaceChildren();const blank=document.createElement('option');blank.value='';blank.textContent='Whole job / saved battery module';partSelect.append(blank);for(const part of parts){if(String(part.repair_job_id)!==jobs.value)continue;const option=document.createElement('option');option.value=part.id;option.textContent=part.description+(part.serial_number?' · '+part.serial_number:'');option.dataset.serial=part.serial_number||'';partSelect.append(option);}partSelect.value=String(selected);if(partSelect.selectedIndex<0)partSelect.value='';}
jobs.onchange=()=>{$('warrantyMileage').value=jobs.selectedOptions[0]?.dataset.mileage||'';$('coveredSerial').value='';fillParts();};partSelect.onchange=()=>{$('coveredSerial').value=partSelect.selectedOptions[0]?.dataset.serial||'';};fillParts(oldPart);if(!$('warrantyMileage').value)$('warrantyMileage').value=jobs.selectedOptions[0]?.dataset.mileage||'';
let manualVersion=0;const manualSave=$('manualWarrantyDialog').querySelector('button[type=submit]');
$('manualWarrantyCustomer').addEventListener('change',async()=>{
 const version=++manualVersion,id=$('manualWarrantyCustomer').value;manualSave.disabled=false;jobs.disabled=false;jobs.replaceChildren();const blank=document.createElement('option');blank.value='';blank.textContent=t('Choose Job');jobs.append(blank);parts=[];fillParts();$('coveredSerial').value='';$('warrantyMileage').value='';
 if(!id){$('manualWarrantyStatus').textContent=t('Select a customer to load their jobs and parts.');return;}
 jobs.disabled=true;manualSave.disabled=true;$('manualWarrantyStatus').textContent=t('Loading jobs…');
 try{const response=await fetch({{ Illuminate\Support\Js::from(route('ccs.warranties.customer-jobs')) }}+'?'+new URLSearchParams({customer_id:id}),{credentials:'same-origin',headers:{Accept:'application/json'},cache:'no-store'});if(!response.ok)throw Error(t('Unable to load jobs. Please try again.'));const body=JSON.parse((await response.text()).trim().replace(/^\uFEFF/,''));if(version!==manualVersion)return;parts=body.parts;for(const job of body.jobs){const option=document.createElement('option');option.value=job.id;option.textContent=job.job_number+' · '+job.registration_number;option.dataset.mileage=job.mileage_at_service_km??'';jobs.append(option);}$('manualWarrantyStatus').textContent=body.jobs.length?'':t('This customer has no repair jobs yet. Add a job first.');}catch(error){if(version===manualVersion)$('manualWarrantyStatus').textContent=error.message;}finally{if(version===manualVersion){jobs.disabled=false;manualSave.disabled=false;}}
});
document.querySelectorAll('[data-warranty-part]').forEach(button=>button.addEventListener('click',()=>{jobs.value=button.dataset.warrantyJob;jobs.onchange();fillParts(button.dataset.warrantyPart);partSelect.onchange();$('manualWarrantyDialog').showModal();}));
const packageEditor={{ Illuminate\Support\Js::from((int)old('package_editor_id',0)) }};if(packageEditor&&$('editPackage'+packageEditor))$('editPackage'+packageEditor).showModal();
const openPackages={{ Illuminate\Support\Js::from(request()->boolean('packages') || (bool)old('name')) }};if(openPackages&&!packageEditor){if({{ Illuminate\Support\Js::from((bool)old('name')) }})$('newPackageDialog').showModal();else $('packagesDialog').showModal();}
const openManual={{ Illuminate\Support\Js::from((bool)old('repair_job_id')) }};if(openManual&&!openPackages)$('manualWarrantyDialog').showModal();
})();
</script>
@endsection
