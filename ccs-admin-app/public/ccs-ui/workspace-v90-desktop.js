/* 73 */
(()=>{'use strict';const frame=document.getElementById('posForm');if(!frame)return;const body=document.body,summary=frame.querySelector('.pos-summary'),footer=summary?.querySelector('.pos-cart-footer'),catalog=frame.querySelector('.pos-catalog');if(!summary||!footer||!catalog)return;let pending=false;
function fit(){pending=false;const top=frame.getBoundingClientRect().top+window.scrollY;const height=Math.max(0,window.innerHeight-top-12-(document.querySelector('.v79-touch-sale-bar')?.getBoundingClientRect().height||0));const summaryHead=summary.querySelector('.v68-checkout-title')?.getBoundingClientRect().height||0;const cartHead=summary.querySelector('.pos-cart-header')?.getBoundingClientRect().height||0;const catalogHeaders=[...catalog.children].filter(e=>!e.classList.contains('pos-catalog-body')&&e.tagName!=='DIALOG').reduce((s,e)=>s+e.getBoundingClientRect().height+(parseFloat(getComputedStyle(e).marginTop)||0)+(parseFloat(getComputedStyle(e).marginBottom)||0),0);const minHeight=Math.max(footer.getBoundingClientRect().height+summaryHead+cartHead+95,catalogHeaders+120);const enabled=window.innerWidth>=1200&&height>=minHeight;frame.style.setProperty('--v73-workspace-height',Math.floor(height)+'px');body.classList.toggle('ccs-pos-fit',enabled);}
const request=()=>{if(!pending){pending=true;requestAnimationFrame(fit);}};window.addEventListener('resize',request);window.addEventListener('load',request);document.addEventListener('toggle',request,true);if(window.ResizeObserver){const observer=new ResizeObserver(request);observer.observe(footer);observer.observe(document.querySelector('.v70-toolbar'));}request();})();

;
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
/* 75 */
(()=>{'use strict';
const main=document.querySelector('.wrap>main'),content=document.getElementById('ccsPageContent');
if(!main||!content||document.body.classList.contains('ccs-pos-page'))return;
const selector='.directory-scroll,.stock-scroll,.web-intake-table,.serial-records-scroll,.jobs-table-scroll,.finance-records-scroll,.table-wrap,.v68-inspector,.v68-card-detail,.settings-main';
let queued=false;
function fit(){queued=false;
 // Small screens retain natural flow inside the content area, so stacked forms stay reachable.
 document.body.classList.toggle('ccs-region-scroll',window.innerWidth>=761);
 const enabled=document.body.classList.contains('ccs-region-scroll');
 const bottom=content.getBoundingClientRect().bottom-12;
 function available(region){
  let reserve=0;
  for(let e=region;e&&e!==content;e=e.parentElement){
   const style=getComputedStyle(e);
   reserve+=(parseFloat(style.marginBottom)||0);
   if(e!==region)reserve+=(parseFloat(style.paddingBottom)||0)+(parseFloat(style.borderBottomWidth)||0);
  }
  return bottom-region.getBoundingClientRect().top-reserve;
 }
 const jobs=content.querySelector('.jobs-master');
 if(jobs){const height=available(jobs);const wide=window.innerWidth>1050&&height>=240;
 jobs.classList.toggle('v75-jobs-fit',enabled&&wide);jobs.style.setProperty('--v75-region-height',Math.max(240,height)+'px');
 const side=jobs.querySelector('.job-side');if(side){side.classList.toggle('v75-scroll-region',enabled&&!wide);side.style.setProperty('--v75-region-height','65dvh');}}
 const regions=[...content.querySelectorAll(selector)].filter(e=>!e.closest('dialog')&&!e.closest('[hidden]'));
 for(const region of regions){
 // Only the outer record/details region scrolls; no duplicate nested scrollbars.
 const nested=regions.some(other=>other!==region&&other.contains(region));
 const height=available(region);
 region.classList.toggle('v75-scroll-region',enabled&&!nested);
 region.style.setProperty('--v75-region-height',Math.max(180,height)+'px');
 }
}
function request(){if(!queued){queued=true;requestAnimationFrame(fit);}}
window.addEventListener('resize',request);window.addEventListener('load',request);
document.addEventListener('toggle',request,true);
// Page scripts may create record inspectors after this script starts.
const observer=new MutationObserver(records=>{if(records.some(r=>r.type==='childList'))request();});
observer.observe(content,{childList:true,subtree:true});
if(window.ResizeObserver){const resize=new ResizeObserver(request);resize.observe(main);const toolbar=main.querySelector('.v70-toolbar');if(toolbar)resize.observe(toolbar);}
request();
})();
