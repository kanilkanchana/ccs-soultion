<style>
.phonetic-guide{box-sizing:border-box;position:fixed;inset:18px;width:calc(100vw - 36px);max-width:none;height:calc(100dvh - 36px);max-height:calc(100dvh - 36px);margin:auto;padding:0;border:1px solid #436f60;border-radius:14px;background:#10292b;color:#eaf8ee;overflow:auto;overscroll-behavior:contain}.phonetic-guide:not([open]){display:none}.phonetic-guide::backdrop{background:#000b}.phonetic-guide-head{position:sticky;top:0;z-index:2;padding:16px 24px;background:#10292b;border-bottom:1px solid #35514d;display:flex;align-items:center;justify-content:space-between;gap:16px}.phonetic-guide-head h2{margin:0;font-size:20px}.phonetic-guide-content{padding:18px 24px 26px}.phonetic-guide-intro{color:#b4d3c4}.phonetic-guide-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.phonetic-guide-panel{padding:16px;border:1px solid #35514d;background:#142e30;border-radius:10px;min-width:0}.phonetic-guide-panel h3{margin:0 0 12px;font-size:16px}.phonetic-guide-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 12px}.phonetic-guide-row{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px solid #34504c;min-width:0}.phonetic-guide-row code{font:600 13px ui-monospace,monospace;color:#c4efce;overflow-wrap:anywhere}.phonetic-guide-row strong{font-size:18px;color:#fff}.phonetic-guide .guide-example{grid-column:1/-1}.phonetic-guide [data-close-phonetic-guide]{width:auto}.ccs-input-switch .ccs-guide-trigger{background:#24433e;color:#ddf9ef;min-height:30px;font-size:10px;padding:5px 8px;margin-top:6px}.ccs-input-switch .ccs-guide-trigger[hidden]{display:none}@media(max-width:1000px){.phonetic-guide-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.phonetic-guide{inset:6px;width:calc(100vw - 12px);height:calc(100dvh - 12px);max-height:calc(100dvh - 12px)}.phonetic-guide-head{padding:14px}.phonetic-guide-content{padding:14px}.phonetic-guide-grid{grid-template-columns:1fr}.phonetic-guide .guide-example{grid-column:auto}}
</style>
<dialog id="phoneticGuide" class="phonetic-guide" aria-labelledby="phonetic-guide-title" aria-modal="true">
<header class="phonetic-guide-head"><div><h2 id="phonetic-guide-title">{{ __('Sinhala Phonetic Guide') }}</h2><span class="muted">{{ __('View Phonetic Guide') }}</span></div><button type="button" class="secondary" data-close-phonetic-guide>{{ __('Close') }}</button></header>
<div class="phonetic-guide-content"><p class="phonetic-guide-intro">{{ __('Letters are case-sensitive: a and A are different. Type with an English keyboard, and use Space between words.') }}</p><div class="phonetic-guide-grid">
<section class="phonetic-guide-panel"><h3>{{ __('Vowels') }} / ස්වර</h3><div class="phonetic-guide-list">
@foreach([['a','අ'],['aa','ආ'],['A','ඇ'],['Aa / AA','ඈ'],['i','ඉ'],['ii','ඊ'],['u','උ'],['uu','ඌ'],['R','ඍ'],['Ru','ඎ'],['Lu','ඏ'],['e','එ'],['ee','ඒ'],['ai','ඓ'],['o','ඔ'],['oo','ඕ'],['au / ou','ඖ']] as [$typed,$result])
<div class="phonetic-guide-row"><code>{{ $typed }}</code><strong>{{ $result }}</strong></div>
@endforeach
</div></section>
<section class="phonetic-guide-panel"><h3>{{ __('Consonants') }} / ව්‍යංජන</h3><div class="phonetic-guide-list">
@foreach([['ka','ක'],['kha','ඛ'],['ga','ග'],['gha','ඝ'],['zga','ඟ'],['cha','ච'],['chha','ඡ'],['ja','ජ'],['Ja','ඣ'],['zja','ඦ'],['zka','ඥ'],['zha','ඤ'],['ta','ට'],['Ta','ඨ'],['da','ඩ'],['Da','ඪ'],['zda','ඬ'],['tha','ත'],['thha','ථ'],['dha','ද'],['dhha','ධ'],['zdha','ඳ'],['na','න'],['Na','ණ'],['pa','ප'],['pha','ඵ'],['ba','බ'],['bha','භ'],['Ba','ඹ'],['ma','ම'],['ya','ය'],['ra','ර'],['la','ල'],['La','ළ'],['wa / va','ව'],['sha','ශ'],['Sa / Sha','ෂ'],['sa','ස'],['ha','හ'],['fa','ෆ']] as [$typed,$result])
<div class="phonetic-guide-row"><code>{{ $typed }}</code><strong>{{ $result }}</strong></div>
@endforeach
</div></section>
<section class="phonetic-guide-panel"><h3>{{ __('Common Combinations') }} / පිලි</h3><div class="phonetic-guide-list">
@foreach([['ka','ක'],['kaa','කා'],['kA','කැ'],['kAa / kAA','කෑ'],['ki','කි'],['kii','කී'],['ku','කු'],['kuu','කූ'],['kru','කෘ'],['kruu','කෲ'],['ke','කෙ'],['kee','කේ'],['kai','කෛ'],['ko','කො'],['koo','කෝ'],['kau / kou','කෞ'],['kax / kazn','කං'],['kaH / kaX','කඃ'],['kya','ක්‍ය'],['kra','ක්‍ර'],['kRa','ර්‍ක']] as [$typed,$result])
<div class="phonetic-guide-row"><code>{{ $typed }}</code><strong>{{ $result }}</strong></div>
@endforeach
</div></section>
<section class="phonetic-guide-panel guide-example"><h3>{{ __('Practice Examples') }}</h3><p><code>nimal</code> → නිමල් &nbsp; · &nbsp; <code>kasun</code> → කසුන් &nbsp; · &nbsp; <code>kAama</code> → කෑම</p><p class="muted">{{ __('This guide shows supported shortcuts. Names can be saved in Sinhala or English; use the spelling you want to search later.') }}</p></section>
</div></div></dialog>
<script>
(function(){
 const modal=document.getElementById('phoneticGuide'),picker=document.getElementById('ccsInputMode');if(!modal||!picker)return;
 function syncGuide(){document.querySelectorAll('.ccs-input-switch [data-open-phonetic-guide]').forEach(button=>{button.hidden=picker.value!=='phonetic';});}
 document.querySelectorAll('[data-open-phonetic-guide]').forEach(button=>button.addEventListener('click',()=>{if(!modal.open)modal.showModal();}));
 modal.querySelectorAll('[data-close-phonetic-guide]').forEach(button=>button.addEventListener('click',()=>modal.close()));
 document.addEventListener('ccs-input-mode-changed',syncGuide);syncGuide();
})();
</script>
