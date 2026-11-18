/* 71 */
(()=>{'use strict';
 document.querySelectorAll('[data-full-balance]').forEach(b=>b.addEventListener('click',()=>{const input=document.getElementById(b.dataset.amountTarget);if(input){input.value=Number(b.dataset.fullBalance).toFixed(2);input.dispatchEvent(new Event('input',{bubbles:true}));}}));
 document.querySelectorAll('[data-full-target]').forEach(s=>s.addEventListener('change',()=>{if(s.value!=='cash'){const input=document.getElementById(s.dataset.fullTarget);if(input){input.value=Number(s.dataset.fullValue).toFixed(2);input.dispatchEvent(new Event('input',{bubbles:true}));}}}));
 const enabled=document.getElementById('customAccent'),colour=document.getElementById('accentColor'),brightness=document.getElementById('accentBrightness'),out=document.getElementById('accentOutput');
 if(!enabled||!colour||!brightness)return;
 function preview(){out.value=brightness.value+'%';if(!enabled.checked){document.documentElement.style.removeProperty('--t-button');document.documentElement.style.removeProperty('--t-button-ink');return;}const strength=Number(brightness.value)/100,rgb=colour.value.slice(1).match(/../g).map(n=>Math.round(parseInt(n,16)*strength));const lum=rgb.map(n=>{let v=n/255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4}).reduce((a,v,i)=>a+v*[.2126,.7152,.0722][i],0);document.documentElement.style.setProperty('--t-button','#'+rgb.map(n=>n.toString(16).padStart(2,'0')).join(''));document.documentElement.style.setProperty('--t-button-ink',(lum+.05)/.05>=1.05/(lum+.05)?'#101820':'#ffffff');}
 [enabled,colour,brightness].forEach(e=>e.addEventListener('input',preview));
 const reset=document.getElementById('resetThemePreview');if(reset){const original={enabled:enabled.checked,colour:colour.value,brightness:brightness.value,style:document.documentElement.getAttribute('style')||''};reset.addEventListener('click',()=>{enabled.checked=original.enabled;colour.value=original.colour;brightness.value=original.brightness;out.value=original.brightness+'%';document.documentElement.setAttribute('style',original.style);});}
})();

;
/* 70 */
(()=>{'use strict';const form=document.getElementById('ccsSystemLanguage'),select=document.getElementById('ccsSystemLocale');if(!form||!select)return;const button=form.querySelector('button');button.hidden=true;select.addEventListener('change',async()=>{select.disabled=true;const status=document.getElementById('ccsLanguageStatus');status.textContent='…';try{const data=new FormData(form);data.set('language',select.value);const response=await fetch(form.action,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body:data});if(!response.ok)throw Error();location.reload();}catch(e){status.textContent=window.ccsText?window.ccsText('Unable to save. Please try again.'):'Unable to save. Please try again.';button.hidden=false;}finally{select.disabled=false;}});})();

;
/* 68 */
/* Progressive enhancement. Without JS, original tables/actions remain available. */
(()=>{'use strict';
 const t=s=>window.ccsText?window.ccsText(s):s;
 const node=(tag,cls,text)=>{const e=document.createElement(tag);if(cls)e.className=cls;if(text!==undefined)e.textContent=text;return e;};
 document.querySelectorAll('table[data-v68-records]').forEach(table=>{
  if(table.closest('dialog')||table.closest('.v68-master-detail'))return;
  const rows=[...table.tBodies[0]?.rows||[]].filter(r=>r.cells.length>1);
  if(!rows.length)return;
  // Stock editors and table actions have stable existing form handlers. Move their nodes, never clone.
  const isStock=!!table.closest('.stock-panel');
  const panel=table.closest('.directory-panel,.ledger-card,.web-intake-table');
  if(!panel||table.closest('.warranty-dialog'))return;
  const heads=[...table.tHead?.rows[0]?.cells||[]].map(c=>c.textContent.trim());
  if(heads.length<2)return;
  const shell=node('div','v68-master-detail');panel.before(shell);shell.append(panel);
  const side=node('section','v68-inspector');side.setAttribute('aria-label',t('View Details / History'));
  const close=node('button','v68-inspector-close',t('Close'));close.type='button';
  const title=node('h2','',t('View Details / History'));const hint=node('p','',t('Select a record to view details and actions.'));
  const facts=node('dl'),actions=node('div','v68-inspector-actions');side.append(close,title,hint,facts,actions);shell.append(side);
  let active=null,parking=null;
  const restore=()=>{if(parking){while(actions.firstChild)parking.append(actions.firstChild);parking=null;}if(active){active.classList.remove('v68-selected');active=null;}};
  close.addEventListener('click',()=>{const row=active;restore();facts.replaceChildren();hint.hidden=false;side.hidden=true;if(row)row.focus();});
  function select(row){
   if(row===active){side.hidden=false;return;}
   restore();active=row;row.classList.add('v68-selected');side.hidden=false;hint.hidden=true;facts.replaceChildren();
   [...row.cells].slice(0,-1).forEach((cell,i)=>{
    const field=node('div');field.append(node('dt','',heads[i]||t('View')),node('dd','',cell.innerText||cell.textContent));facts.append(field);
    // Keep record links available in the main table as well as in the detail panel.
    [...cell.querySelectorAll('a[href]')].forEach(link=>{if(!link.href.startsWith(location.origin))return;const a=node('a','',link.textContent.trim());a.href=link.href;field.append(a);});
   });
   parking=row.cells[row.cells.length-1];while(parking.firstChild)actions.append(parking.firstChild);
   // Stock details remain collapsed until explicitly opened; no accidental mutation.
   if(!isStock){const a=actions.querySelector('a');if(a)a.classList.add('btn','secondary');}
  }
  table.classList.add('v68-compact-table');
  const secondary=(table.dataset.v68Secondary||'').split(',').filter(Boolean).map(Number);
  secondary.forEach(i=>{if(table.tHead.rows[0].cells[i])table.tHead.rows[0].cells[i].classList.add('v68-secondary-cell');});
  table.tHead.rows[0].cells[heads.length-1].classList.add('v68-action-heading');
  rows.forEach(row=>{
   secondary.forEach(i=>{if(row.cells[i])row.cells[i].classList.add('v68-secondary-cell');});
   row.classList.add('v68-selectable-row');row.cells[row.cells.length-1].classList.add('v68-action-cell');
   // Replace only directory navigation on row background with selection. Links retain normal navigation.
   if(row.dataset.directoryUrl){row.dataset.v68OriginalUrl=row.dataset.directoryUrl;delete row.dataset.directoryUrl;}
   row.tabIndex=0;row.setAttribute('aria-label',t('View')+': '+row.cells[0].textContent.trim());
   row.addEventListener('click',e=>{if(e.target.closest('a,button,input,select,textarea,summary,label'))return;e.preventDefault();e.stopImmediatePropagation();select(row);},true);
   row.addEventListener('keydown',e=>{if((e.key==='Enter'||e.key===' ')&&e.target===row){e.preventDefault();e.stopImmediatePropagation();select(row);}},true);
  });
  select(rows[0]);
 });
 document.querySelectorAll('[data-v68-cards]').forEach(grid=>{
  const cards=[...grid.children].filter(e=>e.matches('article.claim-card,article.warranty-card'));
  if(!cards.length)return;
  const shell=node('div','v68-master-detail v68-cards-workspace');grid.before(shell);shell.append(grid);
  const list=node('div','v68-card-list'),bank=node('div','v68-card-bank');bank.hidden=true;
  const side=node('section','v68-inspector v68-card-detail');side.setAttribute('aria-label',t('View Details / History'));
  let current=null;
  const buttons=[];
  const select=(card,index)=>{if(current)bank.append(current);current=card;side.replaceChildren(card);buttons.forEach((b,i)=>{b.classList.toggle('selected',i===index);b.setAttribute('aria-pressed',String(i===index));});};
  cards.forEach((card,i)=>{
   const claim=grid.dataset.v68Cards==='claims';
   const title=claim?card.querySelector('.claim-customer')?.textContent:card.querySelector('h2')?.textContent;
   const subtitle=claim?card.querySelector('.claim-vehicle')?.textContent:card.querySelector('p')?.textContent;
   const badge=card.querySelector('.claim-badge,.badge')?.textContent;
   const reference=claim?card.querySelector('h2')?.textContent:card.querySelector('strong')?.textContent;
   const button=node('button','v68-record-card');button.type='button';
   const top=node('span','v68-card-head');top.append(node('strong','',title||reference),node('span','status',badge||''));
   button.append(top,node('span','v68-list-subtitle',subtitle||''),node('span','v68-card-foot',reference+' · '+t('View')));
   button.addEventListener('click',()=>select(card,i));buttons.push(button);list.append(button);bank.append(card);
  });
  grid.prepend(list,bank);shell.append(side);select(cards[0],0);
  window.addEventListener('beforeprint',()=>{if(current)bank.append(current);});
  window.addEventListener('afterprint',()=>{if(current)side.append(current);});
 });
 // Sidebar: show current group and keep all other groups one click away.
 document.querySelectorAll('.ccs-sidebar-group').forEach(group=>{group.open=!!group.querySelector('a.active');});
})();

;
/* 85-touch */
(()=>{'use strict';
if(!document.body.classList.contains('ccs-touch-mode'))return;
const $=id=>document.getElementById(id),t=window.ccsText||((s)=>s),make=(tag,cls,text)=>{const n=document.createElement(tag);if(cls)n.className=cls;if(text)n.textContent=t(text);return n;},button=(text,fn)=>{const n=make('button','',text);n.type='button';n.addEventListener('click',fn);return n;};
// Move original form fields into steps; retain their IDs, validation, CSRF and event handlers.
function wizard(form,groups,labels,footer){if(!form||form.dataset.touchWizard)return;form.dataset.touchWizard='1';const nav=make('nav','v80-step-nav');form.prepend(nav);const buttons=labels.map((label,i)=>{const b=button(label,()=>show(i));nav.append(b);return b;});const controls=make('div','v80-step-controls'),prev=button('Back',()=>show(active-1)),next=button('Next',()=>{if(form.classList.contains('rj-form')&&active===0&&!$('rj_vehicle')?.value){if($('rj_vehicle_selected'))$('rj_vehicle_selected').textContent=t('Search and select the customer vehicle first.');$('rj_vehicle_query')?.focus();return;}const bad=[...groups[active].querySelectorAll('input,select,textarea')].find(e=>!e.disabled&&e.type!=='hidden'&&!e.checkValidity());if(bad){bad.reportValidity();return;}show(active+1);});controls.append(prev,next);form.append(controls);let active=0;function show(index){active=Math.max(0,Math.min(groups.length-1,index));groups.forEach((g,i)=>g.classList.toggle('v80-step-hidden',i!==active));buttons.forEach((b,i)=>b.setAttribute('aria-current',i===active?'step':'false'));prev.disabled=active===0;next.hidden=active===groups.length-1;if(footer)footer.hidden=active!==groups.length-1;}form.addEventListener('invalid',e=>{const index=groups.findIndex(g=>g.contains(e.target));if(index>=0)show(index);},true);show(0);}
const job=document.querySelector('.rj-form');if(job){const grid=job.querySelector('.job-field-grid'),more=job.querySelector('.job-more-fields');if(grid&&more){const first=make('section','v80-step'),second=make('section','v80-step');grid.before(first);first.append(grid.firstElementChild);first.after(second);second.append(grid,more);more.open=true;wizard(job,[first,second],['1 · Customer / Vehicle','2 · Repair Details'],job.querySelector('.job-dialog-actions'));}}
const part=document.querySelector('#newStock form[data-part-editor]');if(part){const panes=[make('section','v80-step'),make('section','v80-step'),make('section','v80-step')];const children=[...part.children],footer=part.querySelector('.editor-footer');for(const node of children){if(node===footer||node.matches('input[type=hidden]'))continue;let index=2;if(node.querySelector('[name=sku],[name=barcode],[name=name]'))index=0;else if(node.querySelector('[name=purchase_price_lkr],[name=selling_price_lkr],[name=category],[name=reorder_level]'))index=1;panes[index].append(node);}panes.forEach(p=>part.insertBefore(p,footer));wizard(part,panes,['1 · Part Identity','2 · Price / Details','3 · Stock / Serial'],footer);}

const board=document.querySelector('.jobs-master');if(board){board.classList.add('v82-jobs-board');const list=board.querySelector('.v68-card-list');for(const card of list?.querySelectorAll('.v69-job-card')||[]){if(!card.dataset.jobUrl)continue;card.tabIndex=0;card.addEventListener('click',e=>{if(!e.target.closest('a,button,summary,details,input,select,textarea'))location.assign(card.dataset.jobUrl);});card.addEventListener('keydown',e=>{if(e.target===card&&(e.key==='Enter'||e.key===' ')){e.preventDefault();location.assign(card.dataset.jobUrl);}});}}
for(const table of document.querySelectorAll('.stock-panel table[data-v68-records]')){table.classList.add('v82-stock-cards');const heads=[...table.tHead?.rows[0]?.cells||[]].map(c=>c.textContent.trim());for(const row of table.tBodies[0]?.rows||[]){if(row.cells.length<2)continue;row.classList.add('v82-stock-card');for(const [i,cell]of [...row.cells].entries())cell.dataset.touchLabel=heads[i]||'';}}
function fitWorkspaces(){const main=document.querySelector('.wrap>main');if(!main)return;const bottom=main.getBoundingClientRect().bottom-(parseFloat(getComputedStyle(main).paddingBottom)||0)-8;const nav=document.querySelector('.ccs-touch-topbar');if(nav)document.documentElement.style.setProperty('--v82-nav-bottom',nav.getBoundingClientRect().bottom+'px');for(const box of document.querySelectorAll('.jobs-master,.v68-master-detail')){const available=bottom-box.getBoundingClientRect().top;box.classList.toggle('v82-fitted-workspace',available>=260);box.style.setProperty('--v82-board-height',Math.max(260,available)+'px');}}
window.addEventListener('resize',fitWorkspaces);window.addEventListener('ccs-touch-viewport',fitWorkspaces);window.addEventListener('load',fitWorkspaces);document.addEventListener('toggle',fitWorkspaces,true);if(window.ResizeObserver){const ro=new ResizeObserver(fitWorkspaces);for(const e of document.querySelectorAll('.ccs-touch-topbar,.v70-toolbar'))ro.observe(e);}requestAnimationFrame(fitWorkspaces);
// Keep the full permission-filtered menu in the top bar. Extra links stay in native details.
for(const more of document.querySelectorAll('.ccs-touch-topbar .v69-nav-more')){more.addEventListener('toggle',()=>{if(!more.open)return;for(const other of document.querySelectorAll('.ccs-touch-topbar .v69-nav-more'))if(other!==more)other.open=false;});}

const form=$('posForm');if(!form)return;
const queue=form.querySelector('.v70-job-queue'),catalog=form.querySelector('.v70-items-workspace'),summary=form.querySelector('.pos-summary');if(!queue||!catalog||!summary)return;
document.body.classList.add('v82-touch-pos');
const header=document.querySelector('.ccs-touch-topbar');
const picker=make('dialog','v80-customer-picker');picker.setAttribute('aria-label',t('Select Customer / Job'));const pickerHead=make('div','v80-dialog-heading');pickerHead.append(make('h2','','Select Customer / Job'),button('Close',()=>picker.close()));picker.append(pickerHead,queue);form.append(picker);
const customer=make('div','v80-selected-customer'),customerText=make('div',''),change=button('Select / Change Customer',()=>{picker.showModal();$('openJobPicker')?.focus();});customer.append(customerText,change);summary.prepend(customer);
function updateCustomer(){customerText.textContent=$('selectedJobText')?.textContent||t('Select a customer and ready job');const box=$('v71SelectedCustomer');if(box&&!box.hidden&&box.textContent.trim())customerText.textContent=[...box.children].map(n=>n.textContent).join(' · ');}
$('jobPick')?.addEventListener('change',()=>{updateCustomer();if($('jobPick').value&&picker.open)picker.close();});for(const id of ['selectedJobText','v71SelectedCustomer'])if($(id))new MutationObserver(updateCustomer).observe($(id),{subtree:true,childList:true,characterData:true,attributes:true});updateCustomer();
// Payment buttons update existing bookkeeping fields. They do not process a bank/card transaction.
const actionsOriginal=summary.querySelector('.pos-actions');if(actionsOriginal)summary.append(actionsOriginal);
const options=make('details','v80-invoice-options');options.append(make('summary','','Invoice Options / Discount'));const discount=$('discount');if(discount?.parentElement)options.append(discount.parentElement);for(const cls of ['.v72-payment-ref','.v72-checkout-extra']){const n=summary.querySelector(cls);if(n)options.append(n);}if($('previewBtn'))options.append($('previewBtn'));summary.querySelector('.pos-cart-footer')?.append(options);
const method=$('initialMethod'),credit=$('isCreditSaleCheckbox'),methods=make('div','v80-payment-methods');
const methodButtons=[];for(const [value,label]of [['cash','Cash'],['card','Card'],['bank_transfer','Bank Transfer'],['other','Other'],['credit','Credit Ledger']]){const b=button(label,()=>{if(credit)credit.checked=value==='credit';if(value!=='credit'&&method){method.value=value;method.dispatchEvent(new Event('change',{bubbles:true}));}syncMethods();});b.dataset.method=value;methodButtons.push(b);methods.append(b);}
function syncMethods(){methodButtons.forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.method===(credit?.checked?'credit':method?.value))));}
if(method){method.closest('.v71-methods').prepend(methods);method.classList.add('v80-native-method');method.addEventListener('change',syncMethods);}credit?.addEventListener('change',syncMethods);syncMethods();
const amount=$('givenMoney');if(amount){amount.inputMode='decimal';amount.setAttribute('data-no-touch-keyboard','');const pad=make('div','v80-payment-pad'),quick=make('div','v80-quick-amounts'),keys=make('div','v80-numpad');
 let buffer=amount.value||'',writing=false;amount.addEventListener('input',()=>{if(!writing)buffer=amount.value;});
 const set=value=>{if(!/^\d{0,10}(\.\d{0,2})?$/.test(value))return;buffer=value;amount.value=value.endsWith('.')?value.slice(0,-1):value;writing=true;amount.dispatchEvent(new Event('input',{bubbles:true}));writing=false;};
 quick.append(button('Exact',()=>{const total=Number((($('grandText')?.textContent||'').match(/[0-9][0-9,]*(?:\.[0-9]{1,2})?/)?.[0]||'0').replaceAll(',',''));if(Number.isFinite(total))set(total.toFixed(2));}));for(const n of [1000,2000,5000,10000])quick.append(button('+'+n,()=>set((Number(amount.value||0)+n).toFixed(2))));
 for(const key of ['7','8','9','⌫','4','5','6','C','1','2','3','00','0','.'])keys.append(button(key,()=>{const value=buffer;if(key==='C')set('');else if(key==='⌫')set(value.slice(0,-1));else if(key==='.')set(value.includes('.')?value:(value||'0')+'.');else set((value==='0'?'':value)+key);}));pad.append(quick,keys);
 const paymentDialog=make('dialog','v84-payment-dialog'),heading=make('div','v84-payment-heading'),display=make('output','v84-payment-display');paymentDialog.classList.add('v85-payment-sheet');paymentDialog.setAttribute('aria-label',t('Amount Received Now (Rs.)'));heading.append(make('h2','','Amount Received Now (Rs.)'),button('Close',()=>paymentDialog.close()));display.setAttribute('aria-live','polite');const refreshAmount=()=>{display.textContent='Rs. '+(amount.value||'0');};amount.addEventListener('input',refreshAmount);refreshAmount();paymentDialog.append(heading,display,pad,button('Done',()=>paymentDialog.close()));summary.append(paymentDialog);
 const nativeDevice=()=>/Android|iPhone|iPad|iPod|Mobile|Tablet/i.test(navigator.userAgent)||(navigator.maxTouchPoints>0&&matchMedia('(pointer:coarse) and (max-width:1280px)').matches);const closeAmount=()=>{paymentDialog.close();summary.classList.remove('v95-payment-open');};paymentDialog.addEventListener('close',()=>summary.classList.remove('v95-payment-open'));document.addEventListener('pointerdown',e=>{if(paymentDialog.open&&!paymentDialog.contains(e.target)&&e.target!==amount)closeAmount();},true);paymentDialog.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key==='Escape'){e.preventDefault();closeAmount();}});const openAmount=()=>{if(nativeDevice()){amount.inputMode='decimal';return;}if(!paymentDialog.open){window.ccsHideTouchKeyboard?.();refreshAmount();paymentDialog.show();summary.classList.add('v95-payment-open');}};amount.inputMode=nativeDevice()?'decimal':'none';amount.addEventListener('click',openAmount);amount.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();if(paymentDialog.open)closeAmount();else openAmount();}});paymentDialog.addEventListener('click',e=>{if(e.target===paymentDialog){const r=paymentDialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)paymentDialog.close();}});
 }
// Category pills describe the actual currently loaded stock, never invented products/categories.
const cards=$('stockCards');if(cards){const categories=make('nav','v80-category-tabs');categories.setAttribute('aria-label',t('Categories in loaded results'));catalog.querySelector('.pos-catalog-search')?.before(categories);let selected='';function filter(){for(const card of cards.children)card.hidden=Boolean(selected&&card.dataset.category!==selected);}function refresh(){const values=[...new Set([...cards.children].map(c=>c.dataset.category).filter(Boolean))];categories.replaceChildren();if(selected&&!values.includes(selected))selected='';for(const value of ['',...values]){const b=button(value||'All Categories',()=>{selected=value;filter();for(const n of categories.children)n.setAttribute('aria-pressed',String(n===b));});b.setAttribute('aria-pressed',String(selected===value));categories.append(b);}filter();}new MutationObserver(refresh).observe(cards,{childList:true});refresh();}
// Dedicated layout owns height in touch POS; v73 remains the desktop fitter.
function fit(){const box=form.getBoundingClientRect();const height=Math.max(280,innerHeight-box.top-8-(Number(document.body.dataset.touchKeyboardHeight)||0));form.style.setProperty('--v82-work-height',height+'px');}
window.addEventListener('resize',fit);window.addEventListener('ccs-touch-viewport',fit);window.addEventListener('load',fit);if(window.ResizeObserver&&header)new ResizeObserver(fit).observe(header);fit();
})();

;
/* v95: virtual keyboard. Native keyboards stay available on mobile/tablet. */
(()=>{'use strict';if(!document.body.classList.contains('ccs-touch-v82'))return;
const mobile=()=>navigator.maxTouchPoints>0&&matchMedia('(pointer:coarse) and (max-width:1280px)').matches||/Android|iPhone|iPad|iPod|Mobile|Tablet/i.test(navigator.userAgent);
window.ccsNativeKeyboardDevice=mobile;
const root=document.createElement('section');root.className='v82-keyboard';root.hidden=true;root.setAttribute('aria-label','Touch Keyboard');
const bar=document.createElement('div');bar.className='v82-keyboard-bar';const title=document.createElement('strong');title.textContent='Keyboard';const language=document.createElement('button');language.type='button';language.textContent='🌐';language.setAttribute('aria-label','Keyboard language');const modes=document.createElement('select');modes.setAttribute('aria-label','Keyboard language and input method');for(const [value,text] of [['english','English'],['phonetic','සිංහල · ශබ්දානුකූල'],['wijesekara','සිංහල · විජේසේකර']]){const o=document.createElement('option');o.value=value;o.textContent=text;modes.append(o);}modes.hidden=true;language.onclick=()=>{modes.hidden=!modes.hidden;};modes.onchange=()=>window.ccsInput?.setMode(modes.value);
const hideButton=document.createElement('button');hideButton.type='button';hideButton.textContent='⌄';hideButton.setAttribute('aria-label','Hide Keyboard');hideButton.onclick=hide;bar.append(title,language,modes,hideButton);
const columns=document.createElement('div');columns.className='v82-keyboard-columns';const numbers=document.createElement('div'),letters=document.createElement('div');numbers.className='v82-keyboard-numbers';letters.className='v82-keyboard-letters';columns.append(numbers,letters);root.append(bar,columns);document.body.append(root);
let target=null,shift=false,symbols=false,buffer='',oldMode='';
const eligible=e=>e instanceof HTMLElement&&e.matches('textarea,input:not([type=hidden]):not([type=password]):not([type=checkbox]):not([type=radio]):not([type=file]):not([type=date]):not([type=time]):not([type=color]):not([type=range]):not([type=submit]):not([type=button])')&&!e.disabled&&!e.readOnly&&!e.hasAttribute('data-no-touch-keyboard')&&!e.closest('.v82-keyboard')&&e.id!=='givenMoney';
function emit(){target.dispatchEvent(new Event('input',{bubbles:true}));}
function before(type,data=null){return target.dispatchEvent(new InputEvent('beforeinput',{inputType:type,data,bubbles:true,cancelable:true}));}
function insert(text){if(!eligible(target))return;target.focus({preventScroll:true});if(target.type==='number'){const value=buffer+text;if(!/^-?\d*(\.\d*)?$/.test(value))return;buffer=value;target.value=value.endsWith('.')?value.slice(0,-1):value;emit();return;}for(const char of text){if(!before('insertText',char))continue;const start=target.selectionStart??target.value.length,end=target.selectionEnd??start;if(target.maxLength>0&&target.value.length-end+start+char.length>target.maxLength)continue;target.setRangeText(char,start,end,'end');emit();}}
function erase(){if(!eligible(target))return;target.focus({preventScroll:true});if(target.type==='number'){buffer=buffer.slice(0,-1);target.value=buffer.endsWith('.')?buffer.slice(0,-1):buffer;emit();return;}if(!before('deleteContentBackward'))return;const start=target.selectionStart,end=target.selectionEnd;if(start!==end)target.setRangeText('',start,end,'end');else if(start>0)target.setRangeText('',start-(Array.from(target.value.slice(0,start)).pop()?.length||1),end,'end');emit();}
function finish(){const field=target;hide();if(!eligible(field))return;const e=new KeyboardEvent('keydown',{key:'Enter',code:'Enter',bubbles:true,cancelable:true});if(field.dispatchEvent(e)&&field.form?.method.toLowerCase()==='get')field.form.requestSubmit();}
function key(text,action,cls=''){const b=document.createElement('button');b.type='button';b.textContent=text;b.className=cls;b.onclick=action;return b;}
for(const n of ['7','8','9','4','5','6','1','2','3','0','00','.'])numbers.append(key(n,()=>insert(n)));
const commands=document.createElement('div');commands.className='v82-number-actions';commands.append(key('⌫',erase),key('C',()=>{if(eligible(target)){before('deleteByCut');target.value='';buffer='';emit();}}),key('↵',finish));numbers.append(commands);
function render(){modes.value=window.ccsInput?.mode||'english';letters.replaceChildren();const sinhala=modes.value!=='english'&&window.ccsInput?.eligible(target);for(const chars of symbols?['!@#$%&*()','-_+=/\\:;?','"\',.[]{}']:['QWERTYUIOP','ASDFGHJKL','ZXCVBNM']){const row=document.createElement('div');row.className='v82-letter-row';for(const c of chars){const value=shift?c:c.toLowerCase(),label=sinhala&&!symbols?window.ccsInput.label(value,shift):value;row.append(key(label,()=>insert(value)));}letters.append(row);}const tools=document.createElement('div');tools.className='v82-letter-tools';tools.append(key('⇧',()=>{shift=!shift;render();}),key(symbols?'⌨':'123 / #',()=>{symbols=!symbols;render();}),key(sinhala?'හිස්තැන':'Space',()=>insert(' '),'v82-space'),key('⌫',erase),key('↵',finish));letters.append(tools);}
function resize(){const h=root.hidden?0:Math.ceil(root.getBoundingClientRect().height);document.body.dataset.touchKeyboardHeight=String(h);document.body.style.setProperty('--v82-keyboard-height',h+'px');window.dispatchEvent(new Event('ccs-touch-viewport'));}
function show(field){if(mobile()||!eligible(field))return;if(target!==field&&target)target.inputMode=oldMode;target=field;oldMode=field.inputMode==='none'?'':field.inputMode;field.inputMode='none';buffer=field.value;const dialog=field.closest('dialog[open]');(dialog||document.body).append(root);title.textContent=field.labels?.[0]?.textContent.trim()||field.placeholder||'Keyboard';render();root.hidden=false;document.body.classList.add('v82-keyboard-open');resize();}
function hide(){root.hidden=true;document.body.classList.remove('v82-keyboard-open');if(target)target.inputMode=oldMode;resize();}
root.addEventListener('pointerdown',e=>{if(e.target!==modes){e.preventDefault();}e.stopPropagation();});document.addEventListener('pointerdown',e=>{if(root.contains(e.target))return;if(eligible(e.target))show(e.target);else hide();},true);document.addEventListener('focusin',e=>{if(eligible(e.target))show(e.target);});document.addEventListener('keydown',e=>{if(e.key==='Escape'||e.key==='Enter'&&e.target===target)hide();});document.addEventListener('close',e=>{if(e.target.contains?.(root))hide();},true);document.addEventListener('ccs-input-mode-changed',()=>{render();target?.focus({preventScroll:true});});window.addEventListener('resize',()=>{if(mobile())hide();else resize();});window.ccsHideTouchKeyboard=hide;
})();

;
/* 86-header */
(()=>{'use strict';if(!document.body.classList.contains('ccs-touch-v82'))return;
const bar=document.querySelector('.ccs-touch-topbar');if(!bar)return;
const controls=[['ccsInputSwitch','⌨','Input Method'],['ccsSystemLanguage','🌐','System Language']];
for(const [id,icon,label]of controls){const form=document.getElementById(id);if(!form||form.closest('.v86-language-menu'))continue;const menu=document.createElement('details');menu.className='v86-language-menu';const trigger=document.createElement('summary');trigger.textContent=icon;trigger.title=window.ccsText?.(label)||label;trigger.setAttribute('aria-label',trigger.title);const panel=document.createElement('div');panel.className='v86-language-panel';form.before(menu);menu.append(trigger,panel);panel.append(form);menu.addEventListener('toggle',()=>{if(menu.open)for(const other of bar.querySelectorAll('.v86-language-menu'))if(other!==menu)other.open=false;});}
document.addEventListener('pointerdown',e=>{for(const menu of bar.querySelectorAll('.v86-language-menu[open]'))if(!menu.contains(e.target))menu.open=false;});document.addEventListener('keydown',e=>{if(e.key==='Escape')for(const menu of bar.querySelectorAll('.v86-language-menu[open]')){menu.open=false;menu.querySelector('summary').focus();}});
})();
