(() => {
 'use strict';
 const cfg=window.CCS_CONNECT||{}, $=(s,r=document)=>r.querySelector(s), base=(cfg.apiBase||'').replace(/\/$/,''), prefix=cfg.apiPrefix||'/website-api';
 const node=(tag,text,cls)=>{const e=document.createElement(tag);if(text!==undefined)e.textContent=text;if(cls)e.className=cls;return e;};
 const status=(el,text,state='')=>{el.hidden=false;el.textContent=text;el.dataset.state=state;};
 async function api(path,body){
  const headers={Accept:'application/json'},options={method:body?'POST':'GET',mode:'cors',credentials:'omit',headers};if(body){headers['Content-Type']='application/json';options.body=JSON.stringify(body);}
  const abort=new AbortController();options.signal=abort.signal;const timeout=setTimeout(()=>abort.abort(),20000);
  try{const r=await fetch(base+prefix+path,options);const d=await r.json().catch(()=>null);if(!r.ok||!d){const errors=Object.values(d?.errors||{}).flat();throw Error(errors[0]||d?.message||'Online service is temporarily unavailable. Please call or use WhatsApp.');}return d;}
  catch(e){if(e.name==='AbortError')throw Error('The request timed out. It may have been received. Retry without changing the details to avoid a duplicate, or call the workshop.');if(e instanceof TypeError)throw Error('Online service is temporarily unavailable. Please call or use WhatsApp.');throw e;}finally{clearTimeout(timeout);}
 }
 const successfulPayload=new WeakMap();
 const settings=api('/config');settings.catch(()=>{});
 const uuid=()=>typeof crypto.randomUUID==='function'?crypto.randomUUID():'10000000-1000-4000-8000-100000000000'.replace(/[018]/g,c=>(c^crypto.getRandomValues(new Uint8Array(1))[0]&15>>c/4).toString(16));
 const value=(form,name)=>String(new FormData(form).get(name)||'').trim();
 function field(name,label,type='text',required=false){const l=node('label',label),i=document.createElement('input');i.name=name;i.type=type;i.required=required;i.maxLength=type==='tel'?30:100;l.append(i);return l;}
 function consent(form){let c=form.elements.namedItem('consent');if(c){c.closest('label').querySelector('span')?.replaceChildren(document.createTextNode('I agree to share these details with CCS Solutions for review and follow-up.'));return;}
  const label=node('label',undefined,'ccs-connect-check');c=document.createElement('input');c.type='checkbox';c.name='consent';c.required=true;label.append(c,node('span','I agree to share these details with CCS Solutions for review and follow-up.'));form.insertBefore(label,form.querySelector('button[type=submit]'));
 }
 const forms=[['#inspection-form','inspection'],['#enquiry-form','quote'],['#warranty-enquiry','warranty'],['#customer-support-form','complaint']];
 forms.forEach(([selector,kind])=>{
  const form=$(selector);if(!form)return;form.dataset.ccsKind=kind;
  const extra=node('div',undefined,'ccs-connect-extra');
  if(!form.elements.namedItem('phone'))extra.append(field('phone','Contact phone','tel',true));
  if(!form.elements.namedItem('whatsapp'))extra.append(field('whatsapp','WhatsApp number (optional)','tel'));
  if(!form.elements.namedItem('registration_number'))extra.append(field('registration_number','Number plate (optional)'));
  if(kind==='inspection'){
   const label=node('label','Request type'),sel=document.createElement('select');sel.name='request_kind';for(const [v,t] of [['inspection','Inspection / booking request'],['quote','Quotation request']])sel.add(new Option(t,v));label.append(sel);extra.append(label,field('preferred_time','Preferred time (optional)','time'));
  }
  const button=form.querySelector('button[type=submit]');form.insertBefore(extra,button);consent(form);
  if(kind==='inspection'){const label=node('label',undefined,'ccs-connect-check'),i=document.createElement('input');i.name='reminder_consent';i.type='checkbox';label.append(i,node('span','You may contact me with service reminders (optional).'));form.insertBefore(label,button);}
  const honey=node('label','Leave this field empty','ccs-connect-honey'),hi=document.createElement('input');hi.name='website';hi.tabIndex=-1;hi.autocomplete='off';honey.setAttribute('aria-hidden','true');honey.append(hi);form.append(honey);
  const feedback=node('div',undefined,'ccs-connect-status');feedback.role='status';feedback.setAttribute('aria-live','polite');feedback.hidden=true;form.append(feedback);form._ccsFeedback=feedback;
  const span=button.querySelector('span');if(span)button.firstChild.textContent=kind==='inspection'?'Send request ':kind==='warranty'?'Send warranty enquiry ':'Send enquiry ';else button.textContent='Send request';
  if(kind==='inspection')form.querySelector('.inspection-form-note').textContent='Your request is saved for workshop review. An appointment is confirmed only after the workshop agrees a date and time with you.';
  if(kind==='warranty'){const small=form.querySelector('small');if(small)small.textContent='This saves an enquiry for the workshop. It does not approve warranty eligibility or create a warranty claim.';}
 });
 document.addEventListener('submit',async event=>{
  const form=event.target;if(!form.dataset?.ccsKind)return;event.preventDefault();event.stopImmediatePropagation();if(!form.reportValidity())return;
  const button=form.querySelector('button[type=submit]'),feedback=form._ccsFeedback;button.disabled=true;status(feedback,'Saving your request…');
  const kind=value(form,'request_kind')||value(form,'support_kind')||form.dataset.ccsKind;
  const data={submission_id:form.dataset.submissionId||(form.dataset.submissionId=uuid()),kind,name:value(form,'name'),phone:value(form,'phone'),whatsapp:value(form,'whatsapp')||null,vehicle:value(form,'vehicle')||null,registration_number:value(form,'registration_number')||null,chassis:value(form,'chassis')||null,service:value(form,'service')||null,reference:value(form,'reference')||null,message:value(form,'message')||null,preferred_date:value(form,'date')||null,preferred_time:value(form,'preferred_time')||null,contact_consent:!!form.elements.namedItem('consent')?.checked,reminder_consent:!!form.elements.namedItem('reminder_consent')?.checked,source_page:location.pathname.split('/').pop()||'index.html',website:value(form,'website')};
  const signature=JSON.stringify({...data,submission_id:undefined});if(form.dataset.received==='yes'&&successfulPayload.get(form)!==signature){data.submission_id=uuid();form.dataset.submissionId=data.submission_id;}
  try{
   await settings.catch(()=>api('/config'));const result=await api('/requests',data);status(feedback,'Request received: '+result.reference+'. '+result.message,'success');
   const message=['CCS Solutions request '+result.reference,'Name: '+data.name,'Contact: '+data.phone,'Vehicle: '+(data.vehicle||data.registration_number||'Not supplied'),data.message||''].join('\n');
   const preview=form.id==='inspection-form'?$('#inspection-result'):form.id==='warranty-enquiry'?$('#warranty-result'):null;
   if(preview){preview.hidden=false;const pre=preview.querySelector('pre');if(pre)pre.textContent=message;const a=preview.querySelector('a');if(a){a.href='https://wa.me/94717937011?text='+encodeURIComponent(message);a.firstChild.textContent='Optional WhatsApp follow-up ';}const strong=preview.querySelector('strong');if(strong)strong.textContent='REQUEST RECEIVED';const hint=preview.querySelector('span:not([aria-hidden])');if(hint)hint.textContent='Saved in the workshop inbox. No WhatsApp message was sent automatically.';}
   if(!preview){const a=node('a','Optional WhatsApp follow-up ↗','ccs-connect-support-link');a.href='https://wa.me/94717937011?text='+encodeURIComponent(message);a.target='_blank';a.rel='noopener noreferrer';feedback.append(document.createElement('br'),a);}
   // Require deliberate edits for a new submission. Retrying the same request keeps its unique key.
   form.dataset.received='yes';successfulPayload.set(form,signature);
  }catch(e){status(feedback,e.message,'error');const a=node('a','Contact CCS on WhatsApp ↗','ccs-connect-support-link');a.href='https://wa.me/94717937011?text='+encodeURIComponent('Hello CCS Solutions, I need help submitting a '+kind+' request.');a.target='_blank';a.rel='noopener noreferrer';feedback.append(document.createElement('br'),a);}
  finally{button.disabled=false;}
 },true);
 forms.forEach(([selector])=>$(selector)?.addEventListener('input',()=>{const f=$(selector);if(f.dataset.received==='yes'){delete f.dataset.submissionId;delete f.dataset.received;}}));
 // This ZIP uses Laravel customer access, not the private preview platform's ChatGPT login.
 const signIn=$('#customer-sign-in');if(signIn){signIn.href='portal.html';signIn.removeAttribute('target');signIn.querySelector('span').textContent='Find my vehicle records';$('#sign-in-note').textContent='Search limited vehicle and warranty records when online lookup is enabled. No ChatGPT account is needed.';}
 const footer=$('.site-pages-footer');if(footer&&!footer.querySelector('a[href="support.html"]')){const a=node('a','Customer support');a.href='support.html';footer.append(a);}
 const portal=location.pathname.endsWith('/portal.html');const warrantyPage=location.pathname.endsWith('/warranty.html');
 if(portal||warrantyPage){
  const section=node('section',undefined,'ccs-record-wrap');section.id='customer-records';section.innerHTML='<div class="ccs-record-panel"><p class="eyebrow">YOUR CCS RECORDS</p><h2>VEHICLE &amp; WARRANTY LOOKUP</h2><p id="ccs-lookup-help">Check the records saved by the workshop. This search does not verify your identity.</p><form class="ccs-record-form" id="ccs-record-form"><label>Vehicle number plate<input name="plate" required maxlength="30" autocomplete="off" placeholder="Vehicle number"></label><label id="ccs-serial-label">Installed serial / warranty card reference<input name="serial" maxlength="100" autocomplete="off" placeholder="Part serial or CCS-W…"></label><button class="button" type="submit">Find records ↗</button></form><div class="ccs-connect-status" id="ccs-lookup-status" role="status" aria-live="polite" hidden></div><div class="ccs-record-links"><a href="support.html">Ask about a repair / warranty ↗</a><a id="ccs-private-access" href="#">Private customer login ↗</a></div><div id="ccs-record-results" class="ccs-record-grid" hidden></div></div>';
  const hero=$('.page-hero');if(hero)hero.insertAdjacentElement('afterend',section);else $('#main').prepend(section);
  $('#ccs-private-access').href=base+(cfg.customerLoginPath||'/customer/login');
  const form=$('#ccs-record-form'),out=$('#ccs-record-results'),feedback=$('#ccs-lookup-status');
  settings.then(s=>{form.elements.serial.required=s.lookup_mode==='plate_serial';if(s.lookup_mode==='plate_only')$('#ccs-serial-label').firstChild.textContent='Serial / warranty reference (optional)';if(s.lookup_mode==='disabled')status(feedback,'Online record lookup is not enabled yet. Please contact the workshop.');else $('#ccs-lookup-help').textContent=s.lookup_mode==='plate_serial'?'Enter the vehicle plate and a serial or warranty card reference belonging to that vehicle.':'Enter the vehicle plate to view limited public service and warranty records. No OTP is required.';}).catch(()=>status(feedback,'Online records are temporarily unavailable. Contact the workshop directly.','error'));
  form.addEventListener('submit',async e=>{e.preventDefault();if(!form.reportValidity())return;const b=form.querySelector('button');b.disabled=true;out.hidden=true;out.replaceChildren();status(feedback,'Finding records…');
   try{await settings.catch(()=>api('/config'));const d=await api('/records',{plate:value(form,'plate'),serial:value(form,'serial')||null});status(feedback,[d.vehicle.registration_number,d.vehicle.make,d.vehicle.model,d.vehicle.manufacture_year].filter(Boolean).join(' · '),'success');
    const group=(title)=>{const box=node('article',undefined,'ccs-record-card');box.append(node('h3',title));out.append(box);return box;};
    const w=group('Warranty cards');if(!d.warranties.length)w.append(node('p','No approved warranty cards are available for this vehicle.'));
    d.warranties.forEach(c=>{const item=node('div',undefined,'ccs-record-item');item.append(node('strong',c.name),node('code',c.card_number),node('p',(c.part||'Service warranty')+(c.serial?' · '+c.serial:'')),node('small','Status: '+String(c.state||'See conditions').replaceAll('_',' ')),node('small','Start: '+(c.starts_on||'See conditions')+' · End: '+(c.expires_on||'See mileage / conditions')),node('small','Distance: '+(c.distance_km?Number(c.distance_km).toLocaleString()+' km':'See conditions')+' · '+(c.remaining_days===null?'See coverage conditions':c.remaining_days+' days remaining')));if(c.terms){const det=node('details');det.append(node('summary','Saved warranty conditions'),node('p',c.terms));item.append(det);}w.append(item);});
    const parts=group('Part serials');if(!d.parts.length)parts.append(node('p','No finalized serial records found.'));d.parts.forEach(p=>{const item=node('div',undefined,'ccs-record-item');item.append(node('strong',p.description),node('code',p.serial_number),node('small',p.job_number));parts.append(item);});
    const jobs=group('Job status');if(!d.jobs.length)jobs.append(node('p','No active job records found.'));d.jobs.forEach(j=>{const item=node('div',undefined,'ccs-record-item');item.append(node('strong',j.job_number),node('p',j.status.replaceAll('_',' ')),node('small',j.received_at||''));jobs.append(item);});
    const note=group('Need help?');note.append(node('p',d.notice));const a=node('a','Send a customer support request ↗');a.href='support.html';note.append(a);out.hidden=false;
   }catch(err){status(feedback,err.message,'error');}finally{b.disabled=false;}
  });
 }
})();
