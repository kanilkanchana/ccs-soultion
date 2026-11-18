@extends('ccs.layout')  
@section('title','Easy POS Billing')
@section('body-class','ccs-pos-page')  
@section('content')  
<style>  
.ccs-pos{--pos-surface:#10282a;--pos-edge:#284c4b;--pos-bright:#b4f6c8;--pos-soft:#a9c9c1;color:#ebf8f3; font-family: system-ui, sans-serif;}  
.ccs-pos *{box-sizing:border-box}
.ccs-pos .pos-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:13px}
.ccs-pos .pos-title h1{font-size:clamp(20px,2.1vw,29px);margin:0}
.ccs-pos .pos-status{color:#a4eac1;font-size:12px;background:#183b32;padding:7px 12px;border-radius:30px}  
.ccs-pos .pos-frame{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(280px,0.85fr);gap:13px;min-height:0}  
.ccs-pos .pos-panel{background:var(--pos-surface);border:1px solid var(--pos-edge);border-radius:13px;padding:16px;min-width:0;min-height:0}  
.ccs-pos .pos-panel h2{font-size:15px;margin:0 0 10px}
.ccs-pos .pos-row{display:grid;grid-template-columns:minmax(0,2.2fr) minmax(0,.7fr) minmax(0,1fr) auto;gap:9px;align-items:end}
.ccs-pos .pos-job-row{display:grid;grid-template-columns:minmax(0,1fr);gap:10px;margin-bottom:9px}  
.ccs-pos label{font-size:11px;letter-spacing:.02em;margin:0 0 5px;display:block;color:var(--pos-soft)}
.ccs-pos input,.ccs-pos select{min-height:39px;padding:9px 10px;background:#091b1c;border:1px solid #335e58;color:#f1fff9;border-radius:7px;font:inherit;font-size:13px;min-width:0}
.ccs-pos input:focus,.ccs-pos select:focus{outline:2px solid #a0eac0;outline-offset:1px}
.ccs-pos input[type=number]{font-variant-numeric:tabular-nums}  
.ccs-pos .pos-add,.ccs-pos .pos-primary,.ccs-pos .pos-action,.ccs-pos .pos-small{min-height:39px;font-size:12px;border-radius:7px;padding:9px 12px;font-weight:800; cursor:pointer;}
.ccs-pos .pos-primary{width:100%;background:#a1ecb7;color:#062019;border:none;}
.ccs-pos .pos-action{background:#244640;color:#e5fff4;border:1px solid #41675f;width:100%}
.ccs-pos .pos-small{background:#244640;color:#e5fff4;border:none;}  
.ccs-pos .pos-items{margin-top:14px;border:1px solid #244a47;border-radius:9px;overflow:auto;max-height:230px}
.ccs-pos table{width:100%;table-layout:auto;border-collapse:collapse;}
.ccs-pos .pos-items table{min-width:850px}
.ccs-pos .pos-items th{white-space:nowrap;overflow-wrap:normal;word-break:normal}
.ccs-pos .pos-items td{overflow-wrap:break-word;word-break:normal}
.ccs-pos .pos-items th:first-child{min-width:210px}
.ccs-pos .pos-items td:nth-child(2),.ccs-pos .pos-items td:nth-child(6),.ccs-pos .pos-items td:nth-child(7){white-space:nowrap}
.ccs-pos .pos-meta-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:12px 0}
@media(max-width:550px){.ccs-pos .pos-meta-row{grid-template-columns:1fr}}
.ccs-pos td,.ccs-pos th{white-space:normal;overflow-wrap:anywhere;font-size:12px;padding:10px 9px;border-bottom:1px solid #284c4b;}
.ccs-pos td:last-child,.ccs-pos th:last-child{text-align:right}
.ccs-pos .pos-remove{background:none;color:#ffaaaa;padding:3px 7px;border:0;font-size:18px}
.ccs-pos .pos-empty{color:#9dbcb5;text-align:center;padding:17px;font-size:12px}
.ccs-pos .pos-hint{font-size:11px;color:#91aba5;margin:9px 0 0}  
.ccs-pos .pos-summary{display:flex;flex-direction:column;gap:10px}
.ccs-pos .pos-money{display:flex;justify-content:space-between;align-items:center;gap:10px;font-variant-numeric:tabular-nums;font-size:13px}
.ccs-pos .pos-money strong{font-size:17px}
.ccs-pos .pos-total{background:#1b4939;border:1px solid #367a57;border-radius:9px;padding:13px}
.ccs-pos .pos-total .pos-money strong{color:#b6ffd0;font-size:24px}
.ccs-pos .pos-divider{height:1px;background:#34544e}
.ccs-pos .pos-money.positive strong{color:#aaf4c2}
.ccs-pos .pos-money.negative strong{color:#ffb4a8}
.ccs-pos .pos-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:auto}
.ccs-pos .pos-actions .full{grid-column:1/-1}
.ccs-pos .pos-subtext{font-size:11px;color:#a4bfb7;margin:3px 0}
.ccs-pos .pos-foot{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-top:12px}
.ccs-pos .pos-foot button{font-size:12px}  
.ccs-pos .pos-layer{display:none;position:fixed;inset:0;background:rgba(0,0,0,.72);z-index:1000;align-items:center;justify-content:center;padding:18px}.ccs-pos .pos-layer.open{display:flex}
.ccs-pos .pos-dialog{width:min(730px,100%);max-height:90vh;overflow:auto;background:#11282a;border:1px solid #47776d;border-radius:13px;padding:20px;box-shadow:0 18px 75px #0009}
.ccs-pos .pos-dialog.large-dialog { width: min(1050px, 100%); height: 90vh; display: flex; flex-direction: column; overflow: hidden; padding-bottom: 25px; }
.ccs-pos .pos-dialog header{display:flex;justify-content:space-between;align-items:center;gap:12px}
.ccs-pos .pos-dialog h2{font-size:21px}
.ccs-pos .pos-dialog table{table-layout:auto}
.ccs-pos .pos-dialog th,.ccs-pos .pos-dialog td{padding:9px}
.ccs-pos .pos-dialog-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:14px}
.ccs-pos .pos-toast{display:none;color:#ffc6b6;font-size:12px;margin:8px 0}
.ccs-pos .pos-toast.show{display:block}  

/* TOP MENU BAR */
.top-menu-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: #0b1a26; padding: 10px 15px; border-radius: 8px; border: 1px solid #1a3c5c; flex-wrap: wrap; gap: 10px; }
.dropdown-group { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.custom-select-wrapper { position: relative; display: inline-block; }
.custom-dropdown { background: #10282a; color: #b4f6c8; border: 1px solid #284c4b; padding: 8px 30px 8px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; appearance: none; -webkit-appearance: none; transition: 0.2s; }
.custom-dropdown:focus { outline: 1px solid #ffcc00; border-color: #ffcc00; }
.custom-select-wrapper::after { content: '▼'; font-size: 10px; color: #ffcc00; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; }
.custom-dropdown option { background: #10282a; color: #a9c9c1; padding: 10px; }
.custom-dropdown option:hover, .custom-dropdown option:focus, .custom-dropdown option:checked { background: #183b32; color: #ffffff; box-shadow: inset 4px 0 0 #ffffff; }
#liveClock { color: #ffcc00; font-weight: bold; font-size: 13px; letter-spacing: 0.5px; }

/* MODAL & SCROLLABLE STYLING */
.due-list-item { background: #0c1f30; border: 1px solid #1a3c5c; padding: 12px; border-radius: 6px; margin-bottom: 10px; cursor: pointer; transition: 0.2s; }
.due-list-item:hover, .due-list-item.selected { border-color: #ffcc00; background: #132f4c; }
.badge-draft { background: #5a4b10; color: #ffcc00; padding: 3px 8px; border-radius: 4px; font-size: 10px; }
.badge-due { background: #62262b; color: #ffaaaa; padding: 3px 8px; border-radius: 4px; font-size: 10px; }
.badge-done { background: #184636; color: #b5f5c9; padding: 3px 8px; border-radius: 4px; font-size: 10px; }
.detail-box { border: 2px solid #1a3c5c; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: #091b1c; }
.detail-box-label { font-size: 12px; color: #8ea8a2; display: block; margin-bottom: 5px; }
@media print {
    body * { visibility: hidden; }
    #printableModalContent, #printableModalContent * { visibility: visible; }
    #printableModalContent { position: absolute; left: 0; top: 0; width: 100%; background: white !important; color: black !important; padding: 20px; }
    .no-print { display: none !important; }
}

@media(max-width:1080px){.ccs-pos .pos-frame{grid-template-columns:1fr}.ccs-pos .pos-row{grid-template-columns:minmax(0,2fr) 80px minmax(95px,1fr) auto}.ccs-pos .pos-items{max-height:250px}}  
@media(max-width:550px){.ccs-pos .pos-row{grid-template-columns:1fr 1fr}.ccs-pos .pos-row>div:first-child{grid-column:1/-1}.ccs-pos .pos-add{grid-column:2}.ccs-pos .pos-panel{padding:12px}.ccs-pos .pos-title{align-items:start;flex-direction:column}}  
.ccs-pos .pos-stock-picker{padding:14px;border:1px solid #528c74;border-radius:8px;margin:12px 0;background:#13352c}.ccs-pos .pos-stock-picker summary{cursor:pointer;font-weight:bold;color:#a1ecb7}.ccs-pos .pos-stock-search{display:flex;align-items:end;gap:10px;margin:10px 0;flex-wrap:wrap}.ccs-pos .pos-stock-search>input,.ccs-pos .pos-stock-search>div{flex:1;min-width:120px}.ccs-pos .pos-stock-picker label{margin-top:10px}
.ccs-pos .pos-manual-entry{margin:12px 0;padding:12px;border:1px dashed #4b7770;border-radius:7px}.ccs-pos .pos-manual-entry>summary{cursor:pointer;color:#a1ecb7;font-weight:bold}.ccs-pos #manualMetadata{margin:10px 0}.ccs-pos #manualMetadata>summary{cursor:pointer;color:#a6c4b9;font-size:12px}.fast-pos-dialog{width:min(760px,94vw);max-height:85vh;padding:20px;background:#102629;color:#effbf4;border:1px solid #528c74;border-radius:12px;font-family:system-ui,'Nirmala UI',sans-serif}.fast-pos-dialog::backdrop{background:#000b}.fast-pos-dialog h2{margin:0;font-size:20px;color:#a1ecb7}.fast-pos-dialog .dialog-top{display:flex;justify-content:space-between;align-items:center;gap:12px}.fast-pos-dialog input{width:100%;padding:12px;background:#08191e;color:#effbf4;border:1px solid #528c74;border-radius:6px;margin:14px 0}.fast-pos-dialog button{background:#a1ecb7;border:0;border-radius:6px;padding:9px 13px;color:#062019;cursor:pointer}.job-picker-results{max-height:55vh;overflow:auto}.job-picker-customer{padding:12px;margin-top:10px;background:#153a34;border-radius:7px}.job-picker-customer strong{color:#ffcc00}.job-picker-vehicle{font-size:13px;color:#a6c4b9;margin:8px 0}.job-picker-option{display:block;width:100%;text-align:left;margin:6px 0;background:#224b43!important;color:#effbf4!important}.fast-pos-dialog video{width:100%;max-height:45vh;background:#000;border-radius:8px}.fast-pos-dialog .scan-help{color:#a6c4b9;font-size:13px}

/* Full-height catalogue and cart, scoped to POS. */
body.ccs-pos-page .wrap{height:100dvh;min-height:0;overflow:hidden}body.ccs-pos-page .wrap>aside{width:210px;padding:20px 14px;overflow:auto}body.ccs-pos-page main{max-width:none;min-width:0;width:auto;flex:1;margin:0;padding:0;display:flex;flex-direction:column;overflow:hidden}body.ccs-pos-page main>footer{display:none}body.ccs-pos-page main>.notice{flex-shrink:0;margin:6px 12px}
.ccs-pos{flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden;background:#0c161c}.ccs-pos .top-menu-bar{flex-shrink:0;margin:0;border-radius:0;border-width:0 0 1px;padding:9px 20px;background:#102128}.ccs-pos .pos-title{flex-shrink:0;margin:0;padding:14px 20px;border-bottom:1px solid #29413f}.ccs-pos .pos-title h1{font-size:23px}.ccs-pos .pos-frame{flex:1;min-height:0;grid-template-columns:minmax(0,1fr) clamp(340px,29vw,420px);gap:0}.ccs-pos .pos-panel{border:0;border-radius:0;padding:0}.ccs-pos .pos-catalog{display:flex;flex-direction:column;overflow:hidden;min-width:0;background:#0c161c}.ccs-pos .pos-catalog-header{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px 20px 8px;flex-shrink:0}.ccs-pos .pos-catalog-header h2{font-size:18px;margin:0}.ccs-pos .pos-catalog-header .pos-hint{margin-top:4px}.ccs-pos .pos-catalog-search{margin:8px 20px 12px;flex-wrap:nowrap;flex-shrink:0}.ccs-pos .pos-catalog-search>input{min-width:0;flex:1}.ccs-pos .pos-catalog-tabs{display:flex;align-items:center;gap:8px;padding:0 20px 12px;flex-shrink:0;flex-wrap:wrap;border-bottom:1px solid #29413f}.ccs-pos .pos-catalog-tabs button{border-radius:25px;padding:8px 13px;background:#18292c;color:#a7c2ba;font-size:12px}.ccs-pos .pos-catalog-tabs button.active{background:#b8ebc7;color:#102c21}.ccs-pos #catalogCount{font-size:11px;color:#9ab2ab;margin-left:auto}.ccs-pos .pos-catalog-body{overflow:auto;min-height:0;flex:1;padding:18px 20px 24px}.ccs-pos .pos-product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;align-content:start}.ccs-pos .pos-product-card{display:flex;flex-direction:column;align-items:flex-start;gap:7px;text-align:left;min-width:0;min-height:160px;background:#14252b;color:#ecf8f3;border:1px solid #2c4247;border-radius:12px;padding:16px;position:relative}.ccs-pos .pos-product-card:hover,.ccs-pos .pos-product-card:focus-visible{border-color:#addfbc;background:#1c3338;outline-offset:2px}.ccs-pos .pos-product-icon{font-size:17px;padding:7px 10px;background:#22473c;border-radius:8px;color:#bcf1ca}.ccs-pos .pos-product-plus{position:absolute;right:13px;top:13px;font-size:21px;font-weight:400;color:#b5cec4}.ccs-pos .pos-product-card strong{font-size:14px;line-height:1.4;overflow-wrap:anywhere}.ccs-pos .pos-product-meta{font-size:11px;color:#a2b8b0;font-weight:400;overflow-wrap:anywhere}.ccs-pos .pos-product-price{font-size:17px;margin-top:auto;color:#d8f2df;font-variant-numeric:tabular-nums}.ccs-pos .pos-manual-entry{margin:0 0 16px;background:#13292c}.ccs-pos .pos-manual-entry:not([open]){display:none}.ccs-pos .pos-summary{background:#101f25;border-left:1px solid #314448;gap:0;overflow:hidden}.ccs-pos .pos-cart-header{padding:15px 18px 10px;flex-shrink:0;border-bottom:1px solid #314448}.ccs-pos .pos-cart-heading{display:flex;align-items:center;gap:10px;margin-bottom:14px}.ccs-pos .pos-cart-heading h2{font-size:17px;margin:0;flex:1}.ccs-pos #cartCount{font-size:11px;color:#acc7bd}.ccs-pos .pos-cart-heading button{min-height:28px;padding:4px 8px;font-size:11px}.ccs-pos .pos-job-row{margin:0}.ccs-pos .pos-job-row>div{position:relative}.ccs-pos #inlineJobDropdown{position:absolute;top:100%;left:0;right:0;z-index:40;background:#15372f;box-shadow:0 12px 30px #0009}.ccs-pos #selectedJobText{overflow-wrap:anywhere;margin-top:6px;font-size:11px}.ccs-pos .pos-items{margin:0;border:0;border-radius:0;max-height:none;min-height:60px;flex:1;overflow:auto}.ccs-pos .pos-cart-list{padding:0 18px}.ccs-pos .pos-cart-empty{text-align:center;color:#93aca4;padding:55px 20px;font-size:13px}.ccs-pos .pos-cart-item{padding:15px 0;border-bottom:1px solid #29413f}.ccs-pos .pos-cart-item-top{display:flex;gap:10px;align-items:start;justify-content:space-between}.ccs-pos .pos-cart-item strong{font-size:13px;line-height:1.5;overflow-wrap:anywhere}.ccs-pos .pos-cart-item small{display:block;font-size:11px;color:#9bb9af;margin:4px 0;overflow-wrap:anywhere}.ccs-pos .pos-cart-item-bottom{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-top:9px}.ccs-pos .pos-qty-stepper{display:flex;align-items:center;gap:10px;font-size:13px}.ccs-pos .pos-qty-stepper button{padding:3px 9px;min-height:27px;border:1px solid #37564e;border-radius:5px;background:#1d3534;color:#e6f6ed}.ccs-pos .pos-qty-stepper button:disabled{opacity:.35;cursor:default}.ccs-pos .pos-cart-footer{flex-shrink:0;padding:14px 18px 16px;border-top:1px solid #314448;background:#17282e;display:flex;flex-direction:column;gap:10px}.ccs-pos .pos-payment-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px}.ccs-pos .pos-payment-fields input{width:100%;min-height:36px;padding:7px 9px}.ccs-pos .pos-payment-fields label{font-size:11px;min-height:16px}.ccs-pos .pos-total{padding:10px 0;border:0;border-top:1px solid #36534b;border-radius:0;background:none}.ccs-pos .pos-total .pos-money strong{font-size:24px;color:#c4f5cb}.ccs-pos .pos-credit-switch{display:flex;align-items:center;gap:8px;margin:0;font-size:12px;color:#cee2d4}.ccs-pos .pos-credit-switch input{width:17px;height:17px;min-height:0;flex-shrink:0;accent-color:#b9edc8}.ccs-pos .pos-actions{margin:0;grid-template-columns:1fr}.ccs-pos .pos-actions .pos-action{min-height:31px;padding:6px}.ccs-pos .pos-actions .pos-primary{min-height:44px;font-size:14px}.ccs-pos .pos-subtext{font-size:10px;line-height:1.45;margin:0}.ccs-pos .pos-stock-dialog{width:min(480px,94vw);max-height:88dvh;overflow:auto;border:1px solid #528c74;border-radius:13px;padding:22px;background:#11292c;color:#e8f7ee}.ccs-pos button:disabled{opacity:.45;cursor:default}.ccs-pos .pos-stock-dialog::backdrop{background:#000a}.ccs-pos .pos-stock-dialog .dialog-top{display:flex;align-items:center;gap:12px;justify-content:space-between;margin-bottom:18px}.ccs-pos .pos-stock-dialog .dialog-top h2{margin:0;font-size:20px}.ccs-pos .pos-stock-dialog label{margin-top:12px}.ccs-pos .pos-stock-dialog:not([open]){display:none}.ccs-pos .pos-stock-dialog [hidden]{display:none!important}
@media(min-width:761px) and (max-width:1200px){body.ccs-pos-page .wrap>aside{width:190px;padding:16px 10px}.ccs-pos .pos-frame{grid-template-columns:minmax(0,1fr) 340px}.ccs-pos .pos-product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ccs-pos .pos-catalog-header{padding:14px 14px 6px}.ccs-pos .pos-catalog-body{padding:14px}.ccs-pos .pos-catalog-search{margin-left:14px;margin-right:14px}.ccs-pos .pos-catalog-header .pos-small{max-width:150px}.ccs-pos .pos-catalog-tabs{padding-left:14px;padding-right:14px}}
@media(min-width:761px) and (max-height:760px){.ccs-pos .top-menu-bar{padding-top:6px;padding-bottom:6px}.ccs-pos .pos-title{padding-top:9px;padding-bottom:9px}.ccs-pos .pos-title h1{font-size:20px}.ccs-pos .pos-cart-footer{gap:6px;padding:10px 14px}.ccs-pos .pos-cart-header{padding:10px 14px 8px}.ccs-pos .pos-cart-heading{margin-bottom:8px}.ccs-pos .pos-subtext{display:none}}
@media(min-width:761px) and (max-width:980px){body.ccs-pos-page .wrap>aside{width:150px;padding:14px 8px}body.ccs-pos-page .wrap>aside a{font-size:12px;padding:10px 6px}.ccs-pos .pos-frame{grid-template-columns:minmax(0,1fr) 330px}.ccs-pos .pos-product-grid{grid-template-columns:1fr}.ccs-pos .pos-catalog-header{flex-wrap:wrap}}
@media(max-width:760px){body.ccs-pos-page .wrap{height:auto;min-height:100dvh;overflow:visible;display:block}body.ccs-pos-page .wrap>aside{width:100%;padding:10px;overflow:visible}body.ccs-pos-page main{display:block;overflow:visible}.ccs-pos{display:block;overflow:visible}.ccs-pos .pos-frame{display:flex;flex-direction:column}.ccs-pos .pos-catalog{max-height:none}.ccs-pos .pos-catalog-body{max-height:55vh;min-height:160px}.ccs-pos .pos-product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ccs-pos .pos-summary{border-left:0;border-top:1px solid #314448}.ccs-pos .pos-items{max-height:45vh;min-height:120px}.ccs-pos .pos-catalog-header{padding:14px}.ccs-pos .pos-title{flex-direction:row;padding:12px 14px}.ccs-pos .pos-title .pos-status{font-size:10px;padding:5px 8px}.ccs-pos .pos-catalog-search{flex-wrap:wrap;margin:10px 14px}.ccs-pos .pos-catalog-search>input{flex-basis:100%}.ccs-pos .pos-catalog-body{padding:14px}.ccs-pos .top-menu-bar{padding:8px 14px}.ccs-pos .pos-product-card{min-height:145px;padding:12px}.ccs-pos .pos-product-card strong{font-size:12px}}
@media(min-width:761px) and (max-height:600px){body.ccs-pos-page .wrap{height:auto;min-height:100dvh;overflow:visible}body.ccs-pos-page main{overflow:visible}.ccs-pos{overflow:visible}.ccs-pos .pos-frame{min-height:650px}.ccs-pos .pos-catalog-body{max-height:600px}.ccs-pos .pos-items{max-height:380px}}
</style>  

<div class="ccs-pos" id="ccsPos">  
 
 <div class="pos-finance-bar">
 @include('ccs.partials.finance-nav',['compact'=>true])

 </div>

 <div class="pos-title"><h1>{{ __('Point of Sale') }}</h1><span class="pos-status">{{ __('New Sale') }}</span></div>  
 <form method="POST" action="{{ route('ccs.pos.save') }}" id="posForm" class="pos-frame" novalidate>@csrf  
 <section class="pos-panel v70-job-queue"><div class="pos-catalog-header"><h2>{{ __('Job Cards') }}</h2><button type="button" class="v70-queue-refresh secondary" id="v70RefreshQueue">↻ {{ __('Refresh') }}</button></div> <div class="pos-job-row"><div><label>{{ __('Customer / Vehicle / Job *') }}</label><input id="openJobPicker" autocomplete="off" maxlength="150" role="combobox" aria-expanded="false" aria-controls="inlineJobResults" placeholder="{{ __('Customer name, ID, phone, car model, plate or job number') }}"><div id="inlineJobDropdown" hidden style="max-height:320px;overflow:auto;border:1px solid #53766e;padding:10px;border-radius:8px"><div id="inlineJobResults"></div><p id="inlineJobMessage" role="status"></p></div><button id="restoreJobSelection" type="button" class="pos-small" hidden>{{ __('Keep previous job') }}</button><p id="selectedJobText" class="pos-hint">{{ __('No customer / vehicle selected') }}</p>
 <div class="pos-hint" style="display:flex;gap:12px;flex-wrap:wrap"><a href="{{ route('ccs.jobs',['new'=>1]) }}">{{ __('+ New Repair Job') }}</a><a href="{{ route('ccs.customers',['register'=>1]) }}">{{ __('+ Register Customer / Vehicle') }}</a></div>
 <select id="jobPick" name="repair_job_id" hidden aria-hidden="true"><option value="">{{ __('Choose a repair job') }}</option>@foreach($jobs as $job)<option value="{{ $job->id }}" @disabled(!\App\Services\CcsJobBillingGate::allowed($job->status??null)) @selected(\App\Services\CcsJobBillingGate::allowed($job->status??null) && ($preselectedJob->id ?? old('repair_job_id')) == $job->id)>{{ $job->job_number }} · {{ $job->registration_number }} · {{ $job->customer_name }}</option>@endforeach</select></div></div>

 <div class="v70-queue-tabs"><button type="button" data-queue-filter="ready" class="active">{{ __('Ready') }} / {{ __('Completed') }}</button><button type="button" data-queue-filter="service">{{ __('In Service') }}</button></div><p class="v70-queue-hint">{{ __('Only Ready / Completed jobs can be billed.') }}</p><div id="v71SelectedCustomer" hidden aria-live="polite"></div><div id="v70JobQueue"></div><a class="btn secondary" href="{{ route('ccs.jobs') }}">{{ __('Job Cards') }} →</a></section><div class="v70-items-workspace"><section class="pos-panel pos-catalog" aria-label="Parts and services">
 <div class="pos-catalog-header"><div><h2>{{ __('Parts & Services') }}</h2><p class="pos-hint">{{ __('Select a part to add it to the current sale.') }}</p></div><button type="button" class="pos-small" id="openManualEntry">{{ __('+ Add Service / New Part') }}</button></div>
 @if($stockEnabled)
 <div class="pos-stock-search pos-catalog-search"><input id="stockQuery" placeholder="{{ __('Search part, code or serial') }}" maxlength="150" autocomplete="off"><button type="button" class="pos-small" id="scanStock">{{ __('Scan Barcode / QR') }}</button><button type="button" class="pos-small" id="stockSearch">{{ __('Search') }}</button></div>
 <div class="pos-catalog-tabs" role="group" aria-label="Stock filters"><button type="button" data-stock-filter="all" class="active">{{ __('All Parts') }}</button><button type="button" data-stock-filter="serial">{{ __('Serialized Parts') }}</button><button type="button" data-stock-filter="quantity">{{ __('Other Parts') }}</button><span id="catalogCount"></span></div>
 @endif
 <div class="pos-catalog-body">
 <details id="manualEntry" class="pos-manual-entry" @if(!$stockEnabled) open @endif><summary>{{ __('+ Add Service / New Part') }}</summary>
 <label for="entryType">{{ __('Entry Type') }}</label><select id="entryType"><option value="service">{{ __('Service / Labour') }}</option><option value="manual_part">{{ __('New / Manual Part') }}</option><option value="customer_supplied">{{ __('Customer Supplied Part') }}</option></select>
 <div id="manualSerialRow" hidden><label for="entrySerial">{{ __('Actual Serial Number') }}</label><div class="pos-stock-search"><input id="entrySerial" maxlength="100" autocomplete="off"><button type="button" class="pos-small" id="scanManual">{{ __('Scan Barcode / QR') }}</button></div><small>{{ __('Customer supplied/manual parts do not reduce shop stock. Use the actual serial label, not just a model barcode.') }}</small></div>
 <div id="partRequirementOptions"><label><input type="checkbox" id="entryRequiresSerial" style="width:auto"> {{ __('This part has a serial number') }}</label><label><input type="checkbox" id="entryIsModule" style="width:auto"> {{ __('Battery Module — serial and job voltage required') }}</label><div class="pos-row"><label>{{ __('Module Position') }}<input id="entryModulePosition" type="number" min="1" max="100"></label><label>{{ __('Measured Voltage (V)') }}<input id="entryModuleVoltage" type="number" min="0" max="99999.999" step="0.001"></label></div><small>{{ __('For modules, enter readings here or save them in the Battery Job Card.') }}</small></div>
 <details id="manualMetadata"><summary>{{ __('More Part Details (optional)') }}</summary>
 <div class="pos-meta-row"><div><label for="entryYear">{{ __('Item Year (optional)') }}</label><input id="entryYear" type="text" maxlength="50" placeholder="e.g. 2007 / 2008"></div><div><label for="entryCondition">{{ __('Condition (optional)') }}</label><input id="entryCondition" list="itemConditionOptions" type="text" maxlength="80" placeholder="e.g. Recon / Brand New"></div><div><label for="entrySupplier">{{ __('Supplier (optional)') }}</label><input id="entrySupplier" type="text" maxlength="150" placeholder="{{ __('Supplier name') }}"></div></div>
 </details>
 <div class="pos-row"><div><label for="entryDesc">{{ __('Service / Part') }}</label><input id="entryDesc" type="text" maxlength="200" placeholder="e.g. Battery module replacement" autocomplete="off"></div><div><label for="entryQty">{{ __('Qty') }}</label><input id="entryQty" type="number" min="0.01" max="10000" step="0.01" value="1"></div><div><label for="entryPrice">{{ __('Unit price (Rs.)') }}</label><input id="entryPrice" type="number" min="0" max="99999999" step="0.01" value="0"></div><button class="pos-add" type="button" id="addItem">{{ __('+ Add') }}</button></div>  
 <datalist id="itemConditionOptions"><option value="Recon"><option value="Brand New"><option value="Used"></datalist>
 <p class="pos-hint">{{ __('Enter Year, Condition and Supplier before clicking + Add. These details are saved with each item.') }}</p>
 </details>

 @if($stockEnabled)
 <div id="stockCards" class="pos-product-grid" aria-live="polite"></div>
 <p id="stockPickerMessage" class="pos-hint" role="status">{{ __('Loading available parts…') }}</p>
 <a id="stockMissingLink" href="{{ route('ccs.stock') }}" target="_blank" rel="noopener" hidden>{{ __('Register a New Shop Part') }} →</a>
 @else
 <p class="pos-hint">{{ __('Stock selection is disabled. Add a service or manual part above, or enable stock in Parts & Stock.') }}</p><a href="{{ route('ccs.stock') }}">{{ __('Parts & Stock') }} →</a>
 @endif
 </div>
 @if($stockEnabled)
 <dialog id="stockSelectDialog" class="pos-stock-dialog"><div class="dialog-top"><h2 id="stockSelectName">{{ __('Select Part') }}</h2><button type="button" class="pos-small" id="closeStockSelect">{{ __('Close') }}</button></div>
 <select id="stockPart" hidden aria-hidden="true"><option value=""></option></select>
 <div id="stockSerialMethodRow" hidden><label for="stockSerialMethod">{{ __('Unit Number Entry') }}</label><select id="stockSerialMethod"><option value="new">{{ __('Enter number from opened pack') }}</option><option value="saved">{{ __('Select a saved unit number') }}</option></select></div><div id="stockSealedRow" hidden><label for="stockSealedSerial">{{ __('Serial inside pack — enter actual serial after opening') }}</label><input id="stockSealedSerial" maxlength="100" autocomplete="off"><small>{{ __('Enter the number printed inside the pack. One unit per item.') }}</small></div><div id="stockSerialRow" hidden><label for="stockUnit">{{ __('Select Unit Number') }}</label><select id="stockUnit"></select></div>
 <div id="stockModuleFields" hidden><div class="pos-row"><div><label for="stockModulePosition">{{ __('Module Position') }}</label><input id="stockModulePosition" type="number" min="1" max="100" step="1"></div><div><label for="stockModuleVoltage">{{ __('Measured Voltage (V)') }}</label><input id="stockModuleVoltage" type="number" min="0" max="99999.999" step="0.001"></div></div><p id="stockModuleHint" class="pos-hint">{{ __('Enter readings here if they are not saved in the Job Card.') }}</p></div>
 <div class="pos-stock-search"><div><label for="stockQuantity">{{ __('Quantity') }}</label><input id="stockQuantity" type="number" min="1" max="10000" value="1"></div><div><label>{{ __('Selling Price (Rs.)') }}</label><strong id="stockPrice">—</strong></div></div>
 <p id="stockWarranty" class="pos-hint"></p><p id="stockSelectMessage" class="pos-toast" role="status"></p><button type="button" id="addStockPart" class="pos-primary" style="margin-top:16px">{{ __('Add Selected Part') }}</button>
 </dialog>
 @endif
 </section>
 </div><section class="pos-panel pos-summary" aria-label="Checkout & Summary"><div class="pos-cart-heading v68-checkout-title"><h2>{{ __('Checkout & Summary') }}</h2></div>
 <section class="pos-panel pos-current" aria-label="Current sale">
 <div class="pos-cart-header"><div class="pos-cart-heading"><h2>{{ __('Current Sale') }}</h2><span id="cartCount">0 {{ __('items') }}</span><button type="button" class="pos-small" id="clearItems">{{ __('Clear') }}</button></div>
<div class="pos-toast" id="posMessage" role="alert"></div></div>
 <div class="pos-items"><div id="itemRows" class="pos-cart-list" aria-live="polite"></div></div>
 <div id="postLines"></div><input type="hidden" name="initial_request_token" value="{{ old('initial_request_token',(string) Illuminate\Support\Str::uuid()) }}">  

 </section><div class="pos-cart-footer">
 <div class="pos-money"><span>{{ __('Subtotal') }}</span><strong id="subtotalText">Rs. 0.00</strong></div>
 <div class="pos-payment-fields"><div><label for="discount">{{ __('Discount (Rs.)') }}</label><input name="discount" id="discount" type="number" min="0" step="0.01" value="{{ old('discount',0) }}"></div><div><label for="givenMoney">{{ __('Amount Received Now (Rs.)') }}</label><input id="givenMoney" name="initial_received" type="number" min="0" step="0.01" value="{{ old('initial_received',0) }}"></div></div>
 <div class="pos-total"><div class="pos-money"><span>{{ __('Grand Total') }}</span><strong id="grandText">Rs. 0.00</strong></div></div>
 <div class="pos-money" id="changeRow"><span id="changeLabel">{{ __('Balance Due') }}</span><strong id="changeText">Rs. 0.00</strong></div>
 <div class="v71-methods"><label for="initialMethod">{{ __('Payment Method') }}</label><select id="initialMethod" name="initial_method"><option value="cash">{{ __('Cash') }}</option><option value="card" @selected(old('initial_method')==='card')>{{ __('Card') }}</option><option value="bank_transfer" @selected(old('initial_method')==='bank_transfer')>{{ __('Bank Transfer') }}</option><option value="other" @selected(old('initial_method')==='other')>{{ __('Other') }}</option></select><details class="v72-payment-ref"><summary>{{ __('Reference (optional)') }}</summary><label for="initialReference">{{ __('Reference (optional)') }}</label><input id="initialReference" name="initial_reference" maxlength="100" value="{{ old('initial_reference') }}" placeholder="{{ __('Receipt / transfer ref') }}"></details></div>
 <details class="v72-checkout-extra"><summary>{{ __('More Details (optional)') }}</summary><label class="pos-credit-switch" for="isCreditSaleCheckbox"><input type="checkbox" id="isCreditSaleCheckbox" name="is_credit_sale" value="1"><span>{{ __('Mark as Credit Payment (ණයට ලබා දීම)') }}</span></label>
 <p class="pos-subtext">{{ __('Review the amount on the next screen. Payment is recorded only after finalization.') }}</p>
 <div class="pos-row" style="margin-bottom:10px"><label for="invoiceLanguage">{{ __('Invoice Language') }}</label><select name="invoice_locale" id="invoiceLanguage" aria-label="Invoice language">
@foreach(config('ccs_admin.locales') as $code=>$label)
<option value="{{ $code }}" @selected(old('invoice_locale',app(\App\Services\CcsAdministration::class)->preferences(auth()->id())['invoice_locale'])===$code)>{{ $label }}</option>
@endforeach
</select></div>
</details><div class="pos-actions"><button type="button" class="pos-action" id="previewBtn">{{ __('Preview Invoice') }}</button><button type="submit" class="pos-primary" id="saveBtn">{{ __('Review & Finalize →') }}</button></div>
 </div>
 <input type="hidden" id="finalNic" name="credit_nic"><input type="hidden" id="finalAddress" name="credit_address"><input type="hidden" id="finalPhone" name="credit_phone">
 </section>
 </form>

 <!-- CREDIT CONFIRMATION MODAL -->
 <div class="pos-layer" id="creditConfirmModal" role="dialog" aria-modal="true">
    <div class="pos-dialog" style="width: min(500px, 100%);">
        <header style="margin-bottom: 15px;">
            <h2 style="color: #ffcc00;">{{ __('Confirm Credit Customer Details') }}</h2>
            <button class="pos-small" type="button" data-close="creditConfirmModal" style="background: #62262b; border: 1px solid #ffaaaa; color: #fff;">{{ __('✕ Cancel') }}</button>
        </header>

        <p style="color: #a9c9c1; font-size: 13px; margin-bottom: 15px;">කරුණාකර ණයට භාණ්ඩ ලබාගන්නා පාරිභෝගිකයාගේ විස්තර තහවුරු කරන්න.</p>

        <div style="margin-bottom: 15px;">
            <label style="color: #b4f6c8; font-size: 12px; margin-bottom: 5px; display: block;">{{ __('Customer Name') }}</label>
            <input type="text" id="modalCustName" readonly style="width: 100%; border: 1px solid #4b7770; background: #07131e; color: #8ea8a2;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="color: #b4f6c8; font-size: 12px; margin-bottom: 5px; display: block;">{{ __('NIC Number *') }}</label>
            <input type="text" id="modalNic" placeholder="Eg: 200312345678 or 981234567V" style="width: 100%; border: 1px solid #ffcc00;">
        </div>
        
        <div style="margin-bottom: 15px;">
            <label style="color: #b4f6c8; font-size: 12px; margin-bottom: 5px; display: block;">{{ __('Contact Number *') }}</label>
            <input type="text" id="modalPhone" placeholder="Eg: 0771234567" style="width: 100%; border: 1px solid #ffcc00;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="color: #b4f6c8; font-size: 12px; margin-bottom: 5px; display: block;">{{ __('Address *') }}</label>
            <input type="text" id="modalAddress" placeholder="Enter full address" style="width: 100%; border: 1px solid #ffcc00;">
        </div>

        <button type="button" id="confirmCreditBtn" class="pos-primary" style="width: 100%; background: #a1ecb7; color: #062019; font-size: 14px; padding: 12px;">{{ __('Confirm & Save Transaction →') }}</button>
    </div>
 </div>

 <!-- DUE BILLS / PAYMENT FINALIZATION MODAL -->
 <div class="pos-layer" id="dueBillsModal" role="dialog" aria-modal="true">
    <div class="pos-dialog large-dialog" id="printableModalContent">
        <header style="margin-bottom: 15px; flex-shrink: 0;">
            <h2 style="color: #ffcc00;">Finalization & Payment Menu</h2>
            <div style="display: flex; gap: 8px;" class="no-print">
                <a class="pos-small" id="d_print_receipt" href="#" target="_blank" rel="noopener" aria-disabled="true" style="background:#ffcc00;color:#000;pointer-events:none;opacity:.5">🖨 Print Receipt</a>
                <button class="pos-small" type="button" data-close="dueBillsModal" style="background: #62262b; border: 1px solid #ffaaaa; color: #fff;">✕ Close</button>
            </div>
        </header>

        <div style="display: grid; grid-template-columns: 0.8fr 1.2fr; gap: 20px; flex: 1; min-height: 0; overflow: hidden;">
            
            <div style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
                <div style="margin-bottom: 10px;" class="no-print">
                    <input type="text" id="dueSearchInput" placeholder="Search by name, plate or invoice..." style="width: 100%; border: 1px solid #ffcc00;" onkeyup="filterDueList()">
                </div>

                <div style="background: #091b1c; border: 1px solid #284c4b; border-radius: 8px; padding: 15px; flex: 1; overflow-y: auto;" id="dueListContainer">
                    @if(isset($dueInvoices) && count($dueInvoices) > 0)
                        @foreach($dueInvoices as $di)
                        <div class="due-list-item" 
                             data-search="{{ strtolower($di->invoice_number.' '.$di->customer_name.' '.$di->registration_number) }}"
                             onclick="selectDueInvoice(this, '{{ route('ccs.invoices.show', $di->id) }}', '{{ number_format($di->total_lkr, 2) }}', '{{ number_format($di->balance, 2) }}', '{{ base64_encode(json_encode($di->items)) }}', '{{ base64_encode(json_encode($di->payments_history)) }}')">
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <div style="font-weight: bold; color: #fff; font-size: 14px;">{{ $di->customer_name }}</div>
                                <div>
                                    @if($di->status === 'draft')
                                        <span class="badge-draft">Draft</span>
                                    @elseif($di->balance <= 0)
                                        <span class="badge-done">{{ __('Paid') }}</span>
                                    @else
                                        <span class="badge-due">{{ __('Due') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #a9c9c1;">{{ $di->registration_number }} | {{ $di->invoice_number }}</div>
                        </div>
                        @endforeach
                    @else
                        <div class="pos-empty">No invoices found.</div>
                    @endif
                </div>
            </div>

            <div style="display: flex; flex-direction: column; height: 100%; background: #0c202a; border: 1px solid #1a3c5c; border-radius: 8px; padding: 15px; overflow-y: auto;">
                
                <div id="dueDetailsEmpty" style="color: #9dbcb5; text-align: center; margin-top: auto; margin-bottom: auto; font-size: 14px;">
                    Select a history item from the left to view details
                </div>
                
                <div id="dueDetailsContent" style="display: none; flex-direction: column; height: 100%;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;" class="no-print">
                        <span class="due-label" style="margin:0;">Invoice Info</span>
                        <button type="button" class="pos-small" style="padding: 4px 10px; font-size: 11px; background: #244640; border-radius: 4px;" onclick="unselectDue()">← Back (Clear)</button>
                    </div>

                    <div style="display: flex; gap: 15px; flex-shrink: 0; margin-bottom: 15px;">
                        <div class="detail-box" style="flex: 1; margin-bottom: 0;">
                            <span class="detail-box-label">Date of money (Payments)</span>
                            <div id="d_payment_history" style="flex: 1; max-height: 100px; overflow-y: auto;"></div>
                        </div>
                        <div class="detail-box" style="flex: 1; margin-bottom: 0;">
                            <span class="detail-box-label">Date of next given day</span>
                            <div id="d_next_date" style="font-size: 15px; font-weight: bold; color: #a4eac1;">(Not scheduled)</div>
                        </div>
                    </div>
                    
                    <div class="detail-box" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; border-color: #335e58; min-height: 120px;">
                        <span class="detail-box-label" style="color: #ffcc00; margin-bottom: 10px;">Transaction Receipt (Items)</span>
                        <div id="d_receipt_items" style="flex: 1;"></div>
                    </div>
                    
                    <div style="margin-top: 10px; display: flex; gap: 15px; flex-shrink: 0;">
                        <div class="detail-box" style="flex: 1; border-color: #367a57; margin-bottom: 0;">
                            <span class="detail-box-label">Total</span>
                            <div id="d_total" style="font-size: 18px; font-weight: bold; color: #b5f5c9;">-</div>
                        </div>
                        
                        <div class="detail-box" style="flex: 1; border-color: #62262b; margin-bottom: 0;">
                            <span class="detail-box-label" style="color: #ffaaaa;">Dev / Due</span>
                            <div id="d_due" style="font-size: 20px; font-weight: bold; color: #ffaaaa;">-</div>
                        </div>
                    </div>

                    <a href="#" id="d_finalize_btn" class="no-print" style="background: #ffcc00; color: #000; padding: 12px; font-size: 15px; font-weight: bold; text-decoration: none; border-radius: 8px; margin-top: 15px; text-align: center; width: 100%; transition: 0.2s; flex-shrink: 0;">
                        Finalize / Go to Full Page →
                    </a>
                </div>
            </div>
        </div>
    </div>
 </div>

 <div class="pos-layer" id="previewLayer" role="dialog" aria-modal="true" aria-labelledby="previewHeading"><div class="pos-dialog"><header><h2 id="previewHeading">Invoice Preview · Unsaved</h2><button class="pos-small" type="button" data-close="previewLayer">✕ Close</button></header><div id="previewBody"></div><div class="pos-dialog-actions"><button type="button" class="pos-action" style="width:auto" data-close="previewLayer">Back to Edit</button><button type="button" class="pos-primary" id="previewSave">Save Draft</button></div></div></div>  
</div>  


<dialog id="jobPickerDialog" class="fast-pos-dialog" aria-labelledby="jobPickerTitle"><div class="dialog-top"><h2 id="jobPickerTitle">{{ __('Search Customer / Vehicle') }}</h2><button type="button" id="closeJobPicker">{{ __('Close') }}</button></div><input id="jobPickerSearch" placeholder="{{ __('Customer name, ID, phone, car model, plate or job number') }}" autocomplete="off"><div id="jobPickerResults" class="job-picker-results"></div><p id="jobPickerMessage" class="scan-help" role="status"></p><a href="{{ route('ccs.jobs') }}">{{ __('No open job? Add a new repair job first.') }}</a></dialog>
<dialog id="codeScannerDialog" class="fast-pos-dialog" aria-labelledby="scannerTitle"><div class="dialog-top"><h2 id="scannerTitle">{{ __('Scan Barcode / QR') }}</h2><button type="button" id="closeCodeScanner">{{ __('Close') }}</button></div><video id="codeScannerVideo" autoplay playsinline muted></video><p id="codeScannerMessage" class="scan-help" role="status"></p><p class="scan-help">{{ __('You can also use a USB/Bluetooth scanner in the search field, or type the printed code.') }}</p></dialog>
<script>
let isCreditConfirming = false;
const form = document.getElementById('posForm');

// Manage form submission for credit tick logic
form.addEventListener('submit', function(e) {
    if(!validate()){ e.preventDefault(); return; }
    
    const isCreditChecked = document.getElementById('isCreditSaleCheckbox').checked;
    
    if (isCreditChecked && !isCreditConfirming) {
        e.preventDefault(); // Stop form submission for now
        
        // Extract Customer Name from Dropdown to show in popup
        const jobSelect = document.getElementById('jobPick');
        const selectedText = jobSelect.options[jobSelect.selectedIndex].text;
        let custName = selectedText;
        if(selectedText.includes('·')) {
            const parts = selectedText.split('·');
            if(parts.length >= 3) {
                custName = parts[2].trim();
            }
        }
        document.getElementById('modalCustName').value = custName;
        
        // Open the Credit details modal
        openLayer('creditConfirmModal');
        return;
    }
    
    // Normal save processing
    if (document.getElementById('saveBtn').disabled) { e.preventDefault(); return; }
    document.getElementById('saveBtn').disabled = true;
    document.getElementById('saveBtn').textContent = 'Saving…';
});

// Handling Credit Confirm Details Modal Button
document.getElementById('confirmCreditBtn').addEventListener('click', function() {
    const nicVal = document.getElementById('modalNic').value.trim();
    const addressVal = document.getElementById('modalAddress').value.trim();
    const phoneVal = document.getElementById('modalPhone').value.trim();
    
    if(!nicVal) { alert("Please enter the NIC Number."); return; }
    
    // Assign values to hidden inputs
    document.getElementById('finalNic').value = nicVal;
    document.getElementById('finalAddress').value = addressVal;
    document.getElementById('finalPhone').value = phoneVal;
    
    this.disabled = true;
    this.textContent = 'Processing...';
    
    isCreditConfirming = true;
    form.submit();
});

function handleFinancialSelect(selectObj) {
    const val = selectObj.value;
    if (!val) return;
    if (val === 'dueBillsModal') {
        openLayer('dueBillsModal');
    } else if (val.startsWith('http')) {
        window.location.href = val;
    }
    selectObj.value = "";
}

function handleProcurementSelect(selectObj) {
    const val = selectObj.value;
    if (!val) return;
    if (val.startsWith('http')) {
        window.location.href = val;
    }
    selectObj.value = "";
}

window.addEventListener('pageshow', function(event) {
    if (event.persisted) window.location.reload();
});

function filterDueList() {
    const input = document.getElementById('dueSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.due-list-item');
    items.forEach(item => {
        if (item.getAttribute('data-search').includes(input)) item.style.display = 'block';
        else item.style.display = 'none';
    });
}

function selectDueInvoice(element, url, total, due, itemsBase64, paymentsBase64) {
    const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const decodeJson=value=>JSON.parse(new TextDecoder().decode(Uint8Array.from(atob(value),char=>char.charCodeAt(0))));
    document.querySelectorAll('.due-list-item').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    
    document.getElementById('dueDetailsEmpty').style.display = 'none';
    document.getElementById('dueDetailsContent').style.display = 'flex';
    
    document.getElementById('d_total').innerText = 'Rs. ' + total;
    document.getElementById('d_due').innerText = 'Rs. ' + due;
    document.getElementById('d_finalize_btn').href = url;
    const printLink=document.getElementById('d_print_receipt');printLink.href=url+'?print=1';printLink.style.pointerEvents='auto';printLink.style.opacity='1';printLink.setAttribute('aria-disabled','false');

    const items = decodeJson(itemsBase64);
    let itemsHtml = '<table style="width:100%; border-collapse: collapse; font-size: 12px; color: #ebf8f3;">';
    itemsHtml += '<tr style="border-bottom: 1px solid #284c4b;"><th style="text-align:left; padding:5px 0;">{{ __('Item') }}</th><th style="text-align:center; padding:5px;">{{ __('Qty') }}</th><th style="text-align:right; padding:5px 0;">{{ __('Amount') }}</th></tr>';
    
    if(items.length === 0) {
        itemsHtml += '<tr><td colspan="3" style="text-align:center; padding: 15px; color:#a9c9c1;">No items found.</td></tr>';
    } else {
        items.forEach(i => {
            itemsHtml += `<tr>
                <td style="padding:8px 0; border-bottom: 1px dashed #1a3c5c;">${escapeHtml(i.description)}</td>
                <td style="text-align:center; padding:8px 5px; border-bottom: 1px dashed #1a3c5c;">${Number(i.quantity)}</td>
                <td style="text-align:right; padding:8px 0; border-bottom: 1px dashed #1a3c5c;">Rs. ${Number(i.line_total_lkr).toLocaleString()}</td>
            </tr>`;
        });
    }
    itemsHtml += '</table>';
    document.getElementById('d_receipt_items').innerHTML = itemsHtml;

    const payments = decodeJson(paymentsBase64);
    let payHtml = '<table style="width:100%; border-collapse: collapse; font-size: 11px; color: #ebf8f3;">';
    
    if(payments.length === 0) {
        payHtml += '<tr><td style="text-align:center; padding: 15px; color:#a9c9c1;">No payments yet.</td></tr>';
    } else {
        payments.forEach(p => {
            payHtml += `<tr>
                <td style="padding:5px 0; border-bottom: 1px dashed #1a3c5c;">${p.date}</td>
                <td style="text-align:right; padding:5px 0; border-bottom: 1px dashed #1a3c5c; color: #b4f6c8;">Rs. ${Number(p.amount).toLocaleString()}</td>
            </tr>`;
        });
    }
    payHtml += '</table>';
    document.getElementById('d_payment_history').innerHTML = payHtml;
}

function unselectDue() {
    document.querySelectorAll('.due-list-item').forEach(el => el.classList.remove('selected'));
    document.getElementById('dueDetailsContent').style.display = 'none';
    document.getElementById('dueDetailsEmpty').style.display = 'block';
}

(()=>{  
'use strict';  
const root=document.getElementById('ccsPos');  
const $=id=>document.getElementById(id);const items=[];
const previousLines={{ Illuminate\Support\Js::from(old('description',[])) }},previousData={{ Illuminate\Support\Js::from(session()->getOldInput()) }};
if(Array.isArray(previousLines))previousLines.slice(0,20).forEach((desc,i)=>items.push({desc:String(desc),qty:Number(previousData.quantity?.[i]||1),price:Number(previousData.unit_price?.[i]||0),serial:previousData.manual_serial?.[i]||previousData.display_serial?.[i]||'',stockPartId:previousData.inventory_item_id?.[i]||null,stockUnitId:previousData.stock_unit_id?.[i]||null,origin:previousData.item_origin?.[i]||'service',requiresSerial:String(previousData.requires_serial?.[i])==='1',isModule:String(previousData.is_battery_module?.[i])==='1',modulePosition:previousData.module_position?.[i]??null,moduleVoltage:previousData.module_voltage?.[i]??null,saleWarrantyRuleId:previousData.expected_warranty_rule_id?.[i]||null,year:previousData.item_year?.[i]||'',condition:previousData.item_condition?.[i]||'',supplier:previousData.item_supplier?.[i]||''}));  
const money=n=>'Rs. '+Number(n).toLocaleString('en-LK',{minimumFractionDigits:2,maximumFractionDigits:2});  
const numeric=v=>{const n=Number(v);return Number.isFinite(n)?n:0};  
const el=(tag,txt)=>{const node=document.createElement(tag);node.textContent=txt;return node};  
function alertUser(msg){$('posMessage').textContent=window.ccsText?window.ccsText(msg):msg;$('posMessage').classList.add('show');if($('manualEntry').open)$('entryDesc').focus();else if($('stockQuery'))$('stockQuery').focus();}  
function totals(){const subtotal=Math.round(items.reduce((sum,it)=>sum+Math.round(it.qty*it.price*100),0))/100;const discount=Math.max(0,numeric($('discount').value));const grand=Math.max(0,subtotal-discount);const given=Math.max(0,numeric($('givenMoney').value));$('subtotalText').textContent=money(subtotal);$('grandText').textContent=money(grand);$('changeLabel').textContent=given>=grand?'Change':'Balance Due';$('changeText').textContent=money(Math.abs(grand-given));$('changeRow').classList.toggle('positive',given>=grand);$('changeRow').classList.toggle('negative',given<grand);$('discount').setCustomValidity(discount>subtotal?'Discount cannot exceed subtotal':'');return {subtotal,discount,grand,given};}  
function adjustQuantity(index,delta){
 const item=items[index];if(!item||item.serial||item.saleWarrantyRuleId)return;
 const qty=Math.round((item.qty+delta)*100)/100;if(qty<=0||qty>10000)return;
 if(item.stockPartId){const other=items.filter((it,i)=>i!==index&&String(it.stockPartId)===String(item.stockPartId)).reduce((sum,it)=>sum+it.qty,0);if(other+qty>Number(item.maxAvailable??10000))return alertUser('Not enough available stock.');}
 item.qty=qty;render();
}
function render(){
 const area=$('itemRows'),hidden=$('postLines');area.replaceChildren();hidden.replaceChildren();
 $('saveBtn').disabled=items.length===0;$('previewBtn').disabled=items.length===0;
 $('cartCount').textContent=items.length+' '+(window.ccsText?window.ccsText('items'):'items');
 if(!items.length){const empty=el('div',window.ccsText?window.ccsText('Select parts above, or add a service.'):'Select parts above, or add a service.');empty.className='pos-cart-empty';area.append(empty);}
 items.forEach((it,i)=>{
  const row=el('article','');row.className='pos-cart-item';const top=el('div','');top.className='pos-cart-item-top';const description=el('div','');description.append(el('strong',it.desc));if(it.serial)description.append(el('small','SN: '+it.serial));if(it.isModule&&it.moduleVoltage!==null&&it.moduleVoltage!==undefined&&it.moduleVoltage!=='')description.append(el('small','Position '+it.modulePosition+' · '+it.moduleVoltage+' V'));if(it.saleWarrantyName)description.append(el('small',it.saleWarrantyName));
  if(it.year||it.condition||it.supplier){const more=el('details','');more.append(el('summary',window.ccsText?window.ccsText('Part details'):'Part details'),el('small',[it.year,it.condition,it.supplier].filter(Boolean).join(' · ')));description.append(more);}
  const remove=el('button','×');remove.type='button';remove.className='pos-remove';remove.setAttribute('aria-label','Remove '+it.desc);remove.onclick=()=>{items.splice(i,1);render();};top.append(description,remove);
  const bottom=el('div','');bottom.className='pos-cart-item-bottom';const stepper=el('div','');stepper.className='pos-qty-stepper';
  const minus=el('button','−'),plus=el('button','+');minus.type=plus.type='button';minus.setAttribute('aria-label','Reduce quantity of '+it.desc);plus.setAttribute('aria-label','Increase quantity of '+it.desc);minus.disabled=Boolean(it.serial||it.saleWarrantyRuleId||it.qty<=1);plus.disabled=Boolean(it.serial||it.saleWarrantyRuleId);minus.onclick=()=>adjustQuantity(i,-1);plus.onclick=()=>adjustQuantity(i,1);stepper.append(minus,el('span',String(it.qty)),plus);
  const amount=el('strong',money(it.qty*it.price));bottom.append(stepper,amount);row.append(top,el('small',money(it.price)+' × '+it.qty),bottom);area.append(row);
  [['description[]',it.desc],['quantity[]',it.qty],['unit_price[]',it.price],['item_year[]',it.year||''],['item_condition[]',it.condition||''],['item_supplier[]',it.supplier||''],['expected_warranty_rule_id[]',it.saleWarrantyRuleId||''],['inventory_item_id[]',it.stockPartId||''],['stock_unit_id[]',it.stockUnitId||''],['manual_serial[]',it.stockPartId&&it.stockUnitId?'':(it.serial||'')],['display_serial[]',it.serial||''],['module_voltage[]',it.moduleVoltage??''],['module_position[]',it.modulePosition??''],['requires_serial[]',it.requiresSerial?'1':'0'],['is_battery_module[]',it.isModule?'1':'0'],['item_origin[]',it.origin||'service']].forEach(([name,value])=>{const input=document.createElement('input');input.type='hidden';input.name=name;input.value=value;hidden.append(input);});
 });totals();
}
function add(){const desc=$('entryDesc').value.trim(),qty=numeric($('entryQty').value),price=numeric($('entryPrice').value);$('posMessage').classList.remove('show');if(!desc||desc.length>200)return alertUser('Enter a service or part name.');if(!Number.isFinite(qty)||qty<=0||qty>10000)return alertUser('Enter a valid quantity.');if(!Number.isFinite(price)||price<0||price>99999999)return alertUser('Enter a valid unit price.');if(items.length>=20)return alertUser('Maximum 20 items per invoice.');const origin=$('entryType').value,serial=origin==='service'?'':$('entrySerial').value.trim();if(serial&&qty!==1)return alertUser('Add each serialized part with quantity 1.');if(serial&&items.some(it=>it.serial&&it.serial.toUpperCase()===serial.toUpperCase()))return alertUser('That serial is already on this invoice.');items.push({desc,qty,price,origin,serial,requiresSerial:origin!=='service'&&($('entryRequiresSerial').checked||$('entryIsModule').checked),isModule:origin!=='service'&&$('entryIsModule').checked,modulePosition:origin!=='service'&&$('entryIsModule').checked&&$('entryModulePosition').value!==''?Number($('entryModulePosition').value):null,moduleVoltage:origin!=='service'&&$('entryIsModule').checked&&$('entryModuleVoltage').value!==''?Number($('entryModuleVoltage').value):null,year:$('entryYear').value.trim(),condition:$('entryCondition').value.trim(),supplier:$('entrySupplier').value.trim()});$('entryYear').value='';$('entryCondition').value='';$('entrySupplier').value='';$('entryDesc').value='';$('entryQty').value='1';$('entryPrice').value='0';$('entrySerial').value='';render();$('entryDesc').focus()}  
window.validate = function(){if(!$('jobPick').value){$('openJobPicker').focus();$('openJobPicker').click();return false}if(!items.length){alertUser('Add at least one item before saving.');return false}const {subtotal,discount}=totals();if(discount>subtotal){$('discount').reportValidity();return false}return true}  
window.openLayer = function(id){$(id).classList.add('open');$(id).querySelector('[data-close]')?.focus()}
window.closeLayer = function(id){$(id).classList.remove('open')}  
function preview(){if(!validate())return;const data=totals(),area=$('previewBody');area.replaceChildren();const heading=el('p','CCS SOLUTIONS · Draft Preview (not saved)');heading.className='pos-subtext';area.append(heading);const choice=$('jobPick');area.append(el('p','Job: '+choice.options[choice.selectedIndex].text));
const warrantyChoice=$('warranty_rule_id');
if(warrantyChoice && warrantyChoice.value) { area.append(el('p', 'Attached Warranty: ' + warrantyChoice.options[warrantyChoice.selectedIndex].text)); }
const table=document.createElement('table'),head=document.createElement('thead'),hr=document.createElement('tr');['Service / Part','Qty','Unit','Amount'].forEach(t=>hr.append(el('th',t)));head.append(hr);table.append(head);const tb=document.createElement('tbody');items.forEach(it=>{const tr=document.createElement('tr');[it.desc+(it.serial?' · SN: '+it.serial:''),String(it.qty),money(it.price),money(it.qty*it.price)].forEach(t=>tr.append(el('td',t)));tb.append(tr)});table.append(table);area.append(table);area.append(el('p','Subtotal: '+money(data.subtotal)));area.append(el('p','Discount: '+money(data.discount)));const total=el('h2','TOTAL: '+money(data.grand));area.append(total);area.append(el('p','Given money: '+money(data.given)+' (recorded when saved)'));area.append(el('p',(data.given>=data.grand?'Change: ':'Balance due: ')+money(Math.abs(data.grand-data.given))));area.append(el('p','Save the invoice to record the payment, then open the saved invoice to print.'));openLayer('previewLayer')}  
$('openManualEntry').addEventListener('click',()=>{$('manualEntry').open=!$('manualEntry').open;if($('manualEntry').open){$('manualEntry').scrollIntoView({block:'nearest'});$('entryDesc').focus();}});
$('addItem').addEventListener('click',add);$('entryDesc').addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();add()}});$('entryPrice').addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();add()}});$('discount').addEventListener('input',totals);$('givenMoney').addEventListener('input',totals);$('previewBtn').addEventListener('click',preview);$('clearItems').addEventListener('click',()=>{if(items.length&&confirm('Remove all invoice items?')){items.length=0;render()}});root.querySelectorAll('[data-close]').forEach(b=>b.addEventListener('click',()=>closeLayer(b.dataset.close)));root.querySelectorAll('.pos-layer').forEach(layer=>layer.addEventListener('click',e=>{if(e.target===layer)closeLayer(layer.id)}));document.addEventListener('keydown',e=>{if(e.key==='Escape')root.querySelectorAll('.pos-layer.open').forEach(layer=>closeLayer(layer.id))});$('previewSave').addEventListener('click',()=>{
    closeLayer('previewLayer');
    form.requestSubmit();
});

const stockSearch=$('stockSearch');
if(stockSearch){
 const catalogUrl={{ Illuminate\Support\Js::from(route('ccs.stock.catalog')) }};
 let catalog=[],searchVersion=0,stockFilter='all',stockInputTimer;
 const stockDialog=$('stockSelectDialog');
 let readingVersion=0;
 async function loadModuleReading(){
  const version=++readingVersion,part=catalog.find(p=>String(p.id)===$('stockPart').value),module=Boolean(Number(part?.is_battery_module));
  $('stockModuleFields').hidden=!module;$('stockModulePosition').value='';$('stockModuleVoltage').value='';
  if(!module)return;
  $('stockModuleHint').textContent=window.ccsText('Enter readings here if they are not saved in the Job Card.');
  const unit=part.units.find(u=>String(u.id)===$('stockUnit').value),job=$('jobPick').value;
  if(!unit||!job)return;
  try{const response=await fetch({{ Illuminate\Support\Js::from(route('ccs.stock.module-reading')) }}+'?'+new URLSearchParams({job_id:job,serial:unit.serial_number}),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});if(!response.ok)return;const body=await response.json();if(version!==readingVersion)return;if(body.reading){$('stockModulePosition').value=body.reading.position;$('stockModuleVoltage').value=body.reading.voltage??'';$('stockModuleHint').textContent=window.ccsText('Saved Job Card reading loaded. Check it before adding.');}}catch(error){if(version===readingVersion)$('stockModuleHint').textContent=window.ccsText('Unable to load saved readings. Enter the measured values here.');}
 }
 $('stockModulePosition').addEventListener('input',()=>++readingVersion);$('stockModuleVoltage').addEventListener('input',()=>++readingVersion);$('stockUnit').addEventListener('change',loadModuleReading);$('jobPick').addEventListener('change',()=>{if(stockDialog.open)loadModuleReading();});

 const stockMessage=message=>{const translated=window.ccsText?window.ccsText(message):message;$('stockPickerMessage').textContent=translated;if(stockDialog.open){$('stockSelectMessage').textContent=translated;$('stockSelectMessage').classList.add('show');}};
 $('closeStockSelect').addEventListener('click',()=>stockDialog.close());stockDialog.addEventListener('keydown',event=>{if(event.key==='Enter'&&event.target.tagName!=='BUTTON'){event.preventDefault();$('addStockPart').focus();}});
 function renderCatalog(){
  const area=$('stockCards');area.replaceChildren();const visible=catalog.filter(part=>stockFilter==='all'||(stockFilter==='serial'?Boolean(Number(part.has_serial)):!Boolean(Number(part.has_serial))));
  $('catalogCount').textContent=visible.length+' '+(window.ccsText?window.ccsText('shown'):'shown');
  for(const part of visible){const card=el('button','');card.type='button';card.className='pos-product-card';card.dataset.category=part.category||'';card.setAttribute('aria-label',(Number(part.has_serial)?'Select serial for ':'Add ')+part.name);const icon=el('span',Number(part.has_serial)?'▣':'▦');icon.className='pos-product-icon';const plus=el('span','+');plus.className='pos-product-plus';const code=el('span',part.sku+' · '+part.quantity_on_hand+' '+(window.ccsText?window.ccsText('available'):'available'));code.className='pos-product-meta';card.append(icon,plus,el('strong',part.name),code);if(part.item_year||part.condition){const meta=el('span',[part.item_year,part.condition].filter(Boolean).join(' · '));meta.className='pos-product-meta';card.append(meta);}const price=el('span',money(part.selling_price_lkr));price.className='pos-product-price';card.append(price);if(part.warranty_package){const warranty=el('span',part.warranty_package);warranty.className='pos-product-meta';card.append(warranty);}card.addEventListener('click',()=>{$('stockPart').value=String(part.id);showPart();if(Number(part.has_serial)){stockDialog.showModal();$('stockUnit').focus();}else $('addStockPart').click();});area.append(card);}
  if(!visible.length){const empty=el('p',window.ccsText?window.ccsText('No available parts match this filter. Search another code or name.'):'No available parts match this filter. Search another code or name.');empty.className='pos-hint';area.append(empty);}
 }
 document.querySelectorAll('[data-stock-filter]').forEach(button=>button.addEventListener('click',()=>{stockFilter=button.dataset.stockFilter;document.querySelectorAll('[data-stock-filter]').forEach(tab=>tab.classList.toggle('active',tab===button));renderCatalog();}));
 function syncSerialEntry(){const part=catalog.find(p=>String(p.id)===$('stockPart').value),sealed=Boolean(Number(part?.serial_on_sale)),saved=sealed&&$('stockSerialMethod').value==='saved';$('stockSealedRow').hidden=!sealed||saved;$('stockSerialRow').hidden=!part||!Number(part.has_serial)||(sealed&&!saved);if(sealed&&!saved)$('stockUnit').value='';if(saved)$('stockSealedSerial').value='';}
 $('stockSerialMethod').addEventListener('change',()=>{syncSerialEntry();loadModuleReading();});
 function showPart(){$('stockSelectMessage').textContent='';$('stockSelectMessage').classList.remove('show');const part=catalog.find(p=>String(p.id)===$('stockPart').value);const serialized=part&&Boolean(Number(part.has_serial));$('stockSerialMethod').value='new';$('stockSerialMethodRow').hidden=!(part&&Number(part.serial_on_sale)&&part.units.length);$('stockSealedSerial').value='';$('stockQuantity').disabled=Boolean(serialized||(part&&part.default_warranty_rule_id));$('stockQuantity').value='1';$('stockUnit').replaceChildren();$('stockSelectName').textContent=part?part.name:'Select Part';$('stockWarranty').textContent=part?(part.warranty_package?'Warranty: '+part.warranty_package+' · Card created on finalization.':'No automatic warranty package assigned.') : '';if(part){$('stockPrice').textContent=money(part.selling_price_lkr);if(serialized){const placeholder=el('option',window.ccsText('Select Unit Number'));placeholder.value='';$('stockUnit').append(placeholder);}for(const unit of part.units){const option=el('option',unit.serial_number);option.value=unit.id;$('stockUnit').append(option);}const scanned=$('stockQuery').value.trim().toUpperCase();const match=part.units.find(unit=>unit.serial_number.toUpperCase()===scanned);if(match){$('stockUnit').value=String(match.id);$('stockSerialMethod').value='saved';}if(Boolean(Number(part.is_battery_module)))$('stockWarranty').textContent+=' Module readings can be entered here and saved with the invoice.';}else $('stockPrice').textContent='—';syncSerialEntry();loadModuleReading();}
 async function searchStock(){const version=++searchVersion;stockSearch.disabled=true;stockMessage('Searching stock…');try{const normalized=window.ccsParsePartCode?window.ccsParsePartCode($('stockQuery').value):$('stockQuery').value.trim();$('stockQuery').value=normalized;const response=await fetch(catalogUrl+'?'+new URLSearchParams({q:normalized}),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});if(!response.ok)throw Error('Unable to load stock.');const body=await response.json();if(version!==searchVersion)return;catalog=body.data;$('stockMissingLink').hidden=catalog.length>0;const scanned=$('stockQuery').value.trim().toUpperCase();const rank=p=>p.units.some(u=>u.serial_number.toUpperCase()===scanned)?3:(String(p.barcode||'').toUpperCase()===scanned?2:(p.sku.toUpperCase()===scanned?1:0));catalog.sort((a,b)=>rank(b)-rank(a));$('stockPart').replaceChildren();for(const part of catalog){const option=el('option',part.name+' · '+part.sku+' · '+part.quantity_on_hand+' available');option.value=part.id;$('stockPart').append(option);}showPart();renderCatalog();const serialMatch=catalog.find(p=>p.units.some(unit=>unit.serial_number.toUpperCase()===scanned));if(serialMatch){$('stockPart').value=String(serialMatch.id);showPart();if(!stockDialog.open)stockDialog.showModal();$('stockUnit').focus();}stockMessage(body.enabled?(catalog.length?'Select a card to add. Search to find more parts (up to 25 matches shown).':'No available matching stock.'):'Stock trial is disabled. Use manual entry.');if(serialMatch)stockMessage('Confirm the actual serial, then add the part.');}catch(error){if(version===searchVersion)stockMessage(error.message);}finally{if(version===searchVersion)stockSearch.disabled=false;}}
 stockSearch.addEventListener('click',searchStock);window.ccsFindStockCode=code=>{$('stockQuery').value=code;searchStock();};$('stockQuery').addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();searchStock();}});$('stockPart').addEventListener('change',showPart);$('stockQuery').addEventListener('input',()=>{clearTimeout(stockInputTimer);stockInputTimer=setTimeout(searchStock,300);});
 $('addStockPart').addEventListener('click',()=>{const part=catalog.find(p=>String(p.id)===$('stockPart').value);if(!part)return stockMessage('Search and choose an available part.');const serialized=Boolean(Number(part.has_serial)),unit=serialized?part.units.find(u=>String(u.id)===$('stockUnit').value):null;const qty=serialized?1:Number($('stockQuantity').value);const module=Boolean(Number(part.is_battery_module)),position=$('stockModulePosition').value,voltage=$('stockModuleVoltage').value;if(module&&(!position||voltage===''))return stockMessage('Enter the module position and measured voltage.');if(module&&(!Number.isInteger(Number(position))||Number(position)<1||Number(position)>100||!Number.isFinite(Number(voltage))||Number(voltage)<0||Number(voltage)>99999.999))return stockMessage('Enter a valid module position and voltage.');if(module&&items.some(it=>it.isModule&&Number(it.modulePosition)===Number(position)))return stockMessage('Two modules cannot use the same position.');if(!Number.isInteger(qty)||qty<1)return stockMessage('Enter a whole quantity.');if(part.default_warranty_rule_id&&qty!==1)return stockMessage('Add each warranty-covered part as a separate row.');const sealed=Boolean(Number(part.serial_on_sale))&&$('stockSerialMethod').value!=='saved',entered=$('stockSealedSerial').value.trim(),actualSerial=unit?.serial_number||(sealed?entered:'');if(serialized&&!unit&&!sealed)return stockMessage('Select an available serial.');if(serialized&&!actualSerial)return stockMessage('Open the pack and enter its actual serial.');if(actualSerial&&items.some(item=>item.serial&&item.serial.toUpperCase()===actualSerial.toUpperCase()))return stockMessage('That serial is already on this invoice.');if(unit&&items.some(item=>String(item.stockUnitId)===String(unit.id)))return stockMessage('That serial is already added.');const inCart=items.filter(item=>String(item.stockPartId)===String(part.id)).reduce((sum,item)=>sum+item.qty,0);if(inCart+qty>Number(part.quantity_on_hand))return stockMessage('Not enough available stock.');if(items.length>=20)return stockMessage('Maximum 20 invoice items.');items.push({desc:part.name,qty,price:Number(part.selling_price_lkr),year:part.item_year||'',condition:part.condition||'',supplier:part.supplier||'',saleWarrantyName:part.warranty_package||'',saleWarrantyRuleId:part.default_warranty_rule_id||null,maxAvailable:Number(part.quantity_on_hand),modulePosition:module?Number(position):null,moduleVoltage:module?Number(voltage):null,requiresSerial:Boolean(Number(part.has_serial)),isModule:Boolean(Number(part.is_battery_module)),stockPartId:part.id,stockUnitId:unit?.id||null,serial:actualSerial});render();if(stockDialog.open)stockDialog.close();stockMessage('Part added to current sale.');});
 searchStock();
}
render();  
})();  
</script>  
<script>
(()=>{
 const $=id=>document.getElementById(id),jobDialog=$('jobPickerDialog'),scanDialog=$('codeScannerDialog');
 const searchUrl={{ Illuminate\Support\Js::from(route('ccs.pos.jobs.search')) }};
 const text=value=>window.ccsText?window.ccsText(value):value;
 let jobVersion=0,jobTimer,stream=null,scanVersion=0,scanTimer,scanTarget='stock',confirmedJobId=$('jobPick').value;
 const element=(tag,value)=>{const node=document.createElement(tag);node.textContent=value;return node;};
 function showSelection(job=null){const option=$('jobPick').selectedOptions[0];const id=$('jobPick').value;$('selectedJobText').textContent=id&&option?option.textContent:text('No customer / vehicle selected');if(id&&option)$('openJobPicker').value=option.textContent;const box=$('v71SelectedCustomer');if(box){box.replaceChildren();box.hidden=!id;const record=job||(typeof queueJobs!=='undefined'?queueJobs.find(j=>String(j.id)===id):null);if(record){box.append(element('strong',record.customer_name),element('small',[record.customer_code,record.phone].filter(Boolean).join(' · ')),element('small',record.registration_number+' · '+record.job_number));}}}
 function chooseJob(job){
  if(!['ready','completed'].includes(job.status)){ $('inlineJobMessage').textContent=text('Save the repair status as Ready or Completed before billing this job.');return;}

  if(confirmedJobId&&confirmedJobId!==String(job.id)&&$('postLines').querySelector('input')&&!confirm(text('Use the current invoice items for this different customer / job?'))){restoreSelection();return;}
  const changed=confirmedJobId!==String(job.id);
  let option=[...$('jobPick').options].find(option=>option.value===String(job.id));
  if(!option){option=element('option','');option.value=job.id;$('jobPick').append(option);}
  option.disabled=false;option.textContent=job.job_number+' · '+job.registration_number+' · '+job.customer_name;$('jobPick').value=String(job.id);
  if(changed){for(const id of ['finalNic','finalAddress','finalPhone','modalNic','modalAddress','modalPhone'])if($(id))$(id).value='';
  if($('isCreditSaleCheckbox'))$('isCreditSaleCheckbox').checked=false;
  if(typeof isCreditConfirming!=='undefined')isCreditConfirming=false;}
  confirmedJobId=String(job.id);$('restoreJobSelection').hidden=true;showSelection(job);renderQueue();$('jobPick').dispatchEvent(new Event('change'));$('openJobPicker').value=option.textContent;hideJobs();if(jobDialog.open)jobDialog.close();if($('stockQuery'))$('stockQuery').focus();
 }
 function restoreSelection(){++jobVersion;clearTimeout(jobTimer);$('jobPick').value=confirmedJobId;$('restoreJobSelection').hidden=true;showSelection();hideJobs();$('jobPick').dispatchEvent(new Event('change'));}
 $('restoreJobSelection').addEventListener('click',restoreSelection);
 let lastJobs=[];
 function hideJobs(){ $('inlineJobDropdown').hidden=true;$('openJobPicker').setAttribute('aria-expanded','false'); }
 function renderJobs(jobs,target){
  target.replaceChildren();
  for(const job of jobs){
   const button=element('button',job.customer_name+' · '+job.customer_code+' · '+(job.phone||'')+' — '+job.registration_number+' · '+job.make+' '+job.model+' — '+job.job_number+' · '+(job.service_type||'Repair job'));
   button.type='button';button.className='job-picker-option';button.style.width='100%';button.style.textAlign='left';
   if(!['ready','completed'].includes(job.status)){button.disabled=true;button.title=text('Save the repair status as Ready or Completed before billing this job.');const manage=element('a',text('Repair Status')+' →');manage.href={{ Illuminate\Support\Js::from(route('ccs.jobs')) }}+'?'+new URLSearchParams({job_id:job.id});target.append(button,manage);}else{button.addEventListener('click',()=>chooseJob(job));target.append(button);}
  }
 }
 let queueJobs={{ Illuminate\Support\Js::from($jobs) }},queueFilter='ready';
 function renderQueue(){const area=$('v70JobQueue');if(!area)return;area.replaceChildren();const q=$('openJobPicker').value.trim().toLowerCase();const shown=queueJobs.filter(j=>queueFilter==='ready'?['ready','completed'].includes(j.status):!['ready','completed'].includes(j.status));for(const job of shown){const card=element('div','');card.className='v70-queue-card';if(String(job.id)===$('jobPick').value)card.classList.add('selected');card.append(element('strong',job.customer_name),element('small',[job.customer_code,job.phone].filter(Boolean).join(' · ')),element('small',job.registration_number+' · '+job.job_number),element('small',String(job.status||'').replaceAll('_',' ')));if(['ready','completed'].includes(job.status)){const select=element('button',text(String(job.id)===$('jobPick').value?'Selected':'Select'));select.type='button';select.addEventListener('click',()=>chooseJob(job));card.append(select);}else{const a=element('a',text('Repair Status')+' →');a.href={{ Illuminate\Support\Js::from(route('ccs.jobs')) }}+'?'+new URLSearchParams({job_id:job.id});card.append(a);}area.append(card);}if(!shown.length)area.append(element('p',text('No matching jobs.')));}
 $('v70RefreshQueue')?.addEventListener('click',async()=>{const b=$('v70RefreshQueue');b.disabled=true;try{const r=await fetch(searchUrl+'?'+new URLSearchParams({q:''}),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});if(!r.ok)throw Error();const body=await r.json();queueJobs=body.data;const selected=queueJobs.find(j=>String(j.id)===$('jobPick').value);if(selected&&!['ready','completed'].includes(selected.status)){$('jobPick').value='';confirmedJobId='';showSelection();$('jobPick').dispatchEvent(new Event('change'));}renderQueue();}catch(e){$('inlineJobMessage').textContent=text('Unable to load jobs.');$('inlineJobDropdown').hidden=false;}finally{b.disabled=false;}});
 document.querySelectorAll('[data-queue-filter]').forEach(b=>b.addEventListener('click',()=>{queueFilter=b.dataset.queueFilter;document.querySelectorAll('[data-queue-filter]').forEach(x=>x.classList.toggle('active',x===b));renderQueue();}));renderQueue();
 function duplicateNames(jobs,q){
  if(!q)return false;
  const names=new Map();
  for(const job of jobs){const name=job.customer_name.trim().toLocaleLowerCase();if(!name.includes(q.toLocaleLowerCase()))continue;if(!names.has(name))names.set(name,new Set());names.get(name).add(String(job.customer_id));}
  return [...names.values()].some(ids=>ids.size>1);
 }
 async function searchJobs(submit=false){
  const version=++jobVersion,q=$('openJobPicker').value.trim();
  $('inlineJobDropdown').hidden=false;$('openJobPicker').setAttribute('aria-expanded','true');$('inlineJobMessage').textContent=text('Searching…');
  try{
   const response=await fetch(searchUrl+'?'+new URLSearchParams({q}),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}});
   if(!response.ok)throw Error('Unable to load jobs.');const body=await response.json();if(version!==jobVersion)return;
   lastJobs=body.data;queueJobs=lastJobs;renderQueue();renderJobs(lastJobs,$('inlineJobResults'));
   $('inlineJobMessage').textContent=lastJobs.length?(lastJobs.length+' '+text('open jobs shown. Narrow the search if needed.')):text('No open jobs found. Add a new repair job first.');
   if(submit && lastJobs.length===1){chooseJob(lastJobs[0]);return;}
   if(submit && duplicateNames(lastJobs,q)){
    renderJobs(lastJobs,$('jobPickerResults'));$('jobPickerMessage').textContent='Choose the customer using their ID, phone and vehicle.';$('jobPickerSearch').value=q;hideJobs();if(!jobDialog.open)jobDialog.showModal();
   }
  }catch(error){if(version===jobVersion)$('inlineJobMessage').textContent=error.message;}
 }
 $('openJobPicker').addEventListener('click',()=>searchJobs());
 $('openJobPicker').addEventListener('input',()=>{++jobVersion;clearTimeout(jobTimer);$('jobPick').value='';$('selectedJobText').textContent=text('Select a search result before saving this sale.');$('restoreJobSelection').hidden=!confirmedJobId;$('jobPick').dispatchEvent(new Event('change'));jobTimer=setTimeout(()=>searchJobs(),200);});
 $('openJobPicker').addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();clearTimeout(jobTimer);searchJobs(true);}if(event.key==='Escape')hideJobs();if(event.key==='ArrowDown'){event.preventDefault();$('inlineJobResults').querySelector('button')?.focus();}});
 document.addEventListener('click',event=>{if(!event.target.closest('.pos-job-row'))hideJobs();});
 $('closeJobPicker').addEventListener('click',()=>jobDialog.close());
 $('jobPickerSearch').addEventListener('input',()=>{const q=$('jobPickerSearch').value.trim().toLocaleLowerCase();renderJobs(lastJobs.filter(job=>[job.customer_name,job.customer_code,job.phone,job.registration_number,job.job_number].join(' ').toLocaleLowerCase().includes(q)),$('jobPickerResults'));});
 $('jobPickerSearch').addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();$('jobPickerResults').querySelector('button')?.focus();}});showSelection();
 function syncManual(){const isService=$('entryType').value==='service';$('partRequirementOptions').hidden=isService;$('manualSerialRow').hidden=isService;$('manualMetadata').hidden=isService;if(isService){$('entrySerial').value='';$('entryYear').value='';$('entryCondition').value='';$('entrySupplier').value='';}}
 $('entryType').addEventListener('change',syncManual);syncManual();
 window.ccsParsePartCode=raw=>{
  const value=String(raw||'').trim();if(!value)return '';
  let code=value;
  if(value.startsWith('{')){let parsed;try{parsed=JSON.parse(value);}catch(error){throw Error('This QR is not a valid product code.');}code=parsed.serial_number||parsed.serial||parsed.barcode||parsed.sku||parsed.code;if(typeof code!=='string')throw Error('QR needs a serial, barcode or product code.');}
  else if(/^https?:\/\//i.test(value)){const url=new URL(value);code=['serial_number','serial','barcode','sku','code'].map(key=>url.searchParams.get(key)).find(Boolean);if(!code)throw Error('This QR is a website link, not a registered part code. Enter the actual serial or product code.');}
  code=code.trim();if(code.length>150)throw Error('Scanned code is too long.');return code;
 };
 function stopScanner(){scanVersion++;clearTimeout(scanTimer);if(stream){stream.getTracks().forEach(track=>track.stop());stream=null;}$('codeScannerVideo').srcObject=null;}
 async function startScanner(target){
  if(window.ccsScannerMode && window.ccsScannerMode!=='camera'){const input=target==='stock'?$('stockQuery'):$('entrySerial');input.focus();if(target==='stock')$('stockPickerMessage').textContent=text('Scan with your USB/Bluetooth reader or type the code, then press Enter.');else $('posMessage').textContent=text('Scan with your USB/Bluetooth reader or type the code, then press Enter.');return;}
  stopScanner();scanTarget=target;scanDialog.showModal();const version=scanVersion;
  const status=$('codeScannerMessage');status.textContent=text('Starting camera…');
  if(!('BarcodeDetector' in window)||!navigator.mediaDevices?.getUserMedia){status.textContent=text('Camera scanning is not available here. Use a USB/Bluetooth scanner or type the code.');return;}
  try{
   const supported=await BarcodeDetector.getSupportedFormats();const formats=['qr_code','code_128','code_39','ean_13','ean_8','upc_a','upc_e','itf','data_matrix'].filter(format=>supported.includes(format));if(!formats.length)throw Error('No supported camera barcode formats.');
   if(version!==scanVersion||!scanDialog.open)return;const detector=new BarcodeDetector({formats});const camera=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});
   if(version!==scanVersion||!scanDialog.open){camera.getTracks().forEach(track=>track.stop());return;}stream=camera;const video=$('codeScannerVideo');video.srcObject=stream;await video.play();status.textContent=text('Point the camera at the barcode or QR label.');
   async function detect(){if(version!==scanVersion||!scanDialog.open)return;try{const values=await detector.detect(video);if(version!==scanVersion)return;if(values.length){const code=window.ccsParsePartCode(values[0].rawValue);if(scanTarget==='manual'){if(code.length>100)throw Error('Serial number is too long.');$('entrySerial').value=code;}else if(window.ccsFindStockCode)window.ccsFindStockCode(code);scanDialog.close();return;}}catch(error){status.textContent=error.message;}if(version===scanVersion)scanTimer=setTimeout(detect,250);}
   detect();
  }catch(error){if(version===scanVersion){if(stream)stream.getTracks().forEach(track=>track.stop());stream=null;status.textContent=text('Camera could not start. Use a USB/Bluetooth scanner or type the code.');}}
 }
 $('entrySerial').addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();try{const serial=window.ccsParsePartCode($('entrySerial').value);if(serial.length>100)throw Error('Serial number is too long.');$('entrySerial').value=serial;$('entryDesc').focus();}catch(error){$('posMessage').textContent=error.message;$('posMessage').classList.add('show');}}});
 $('scanStock')?.addEventListener('click',()=>startScanner('stock'));$('scanManual').addEventListener('click',()=>startScanner('manual'));$('closeCodeScanner').addEventListener('click',()=>scanDialog.close());scanDialog.addEventListener('close',stopScanner);window.addEventListener('pagehide',stopScanner);document.addEventListener('visibilitychange',()=>{if(document.hidden&&scanDialog.open)scanDialog.close();});
})();
</script>

<script>
if({{ Illuminate\Support\Js::from(request()->query('panel')==='payments') }})window.openLayer('dueBillsModal');
</script>

@endsection