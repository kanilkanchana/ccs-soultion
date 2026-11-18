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
