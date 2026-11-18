(() => {
  'use strict';
  const config = window.CCS_AUTH_CONFIG || {};
  const url = String(config.supabaseUrl || '').replace(/\/+$/, '');
  const anon = String(config.publicAnonKey || '');
  if (!url || !anon) return;
  let session;
  try { session = JSON.parse(sessionStorage.getItem('ccs-auth-session') || '{}'); } catch { session = {}; }
  if (!session.access_token || session.expires_at < Date.now()) {
    sessionStorage.removeItem('ccs-auth-session');
    return;
  }
  const note = document.querySelector('.portal-note');
  if (!note) return;
  const account = document.createElement('section'); account.className = 'ccs-account-panel';
  const heading = document.createElement('h2'); heading.textContent = 'YOUR ACCOUNT';
  const status = document.createElement('p'); status.textContent = 'Signed in securely. Your vehicle notes below still remain only in this browser.';
  const wrap = document.createElement('div'); wrap.className = 'ccs-account-controls';
  const plate = document.createElement('input'); plate.type = 'text'; plate.autocomplete='off'; plate.placeholder='Your vehicle number (e.g. CAA-1234)'; plate.maxLength=20;plate.setAttribute('aria-label','Vehicle registration number');
  const link = document.createElement('button'); link.type='button';link.textContent='Request vehicle-number login';
  const logout = document.createElement('button'); logout.type='button';logout.textContent='Sign out';
  const result = document.createElement('p');result.setAttribute('role','status');
  wrap.append(plate,link,logout); account.append(heading,status,wrap,result); note.before(account);
  // Verified phone linking is performed by Supabase Auth (requires SMS provider configuration).
  const phoneBox = document.createElement('div'); phoneBox.className = 'ccs-phone-controls';
  const phone = document.createElement('input'); phone.type='tel';phone.placeholder='Mobile number (071… or +94…)';phone.setAttribute('aria-label','Mobile number to verify');
  const sendCode = document.createElement('button');sendCode.type='button';sendCode.textContent='Send phone verification code';
  const code = document.createElement('input');code.type='text';code.inputMode='numeric';code.autocomplete='one-time-code';code.placeholder='Verification code';code.setAttribute('aria-label','Verification code');code.hidden=true;
  const verify = document.createElement('button');verify.type='button';verify.textContent='Verify number';verify.hidden=true;
  phoneBox.append(phone,sendCode,code,verify);account.append(phoneBox);
  const normalizePhone = value => {const v=value.replace(/[\s().-]/g,'');return /^0[0-9]{9}$/.test(v)?'+94'+v.slice(1):/^94[0-9]{9}$/.test(v)?'+'+v:v;};
  let pendingPhone = '';
  sendCode.addEventListener('click',async()=>{
    const v=normalizePhone(phone.value.trim());
    if (!/^\+[1-9]\d{7,14}$/.test(v)){result.textContent='Enter a valid Sri Lankan or international mobile number.';return;}
    sendCode.disabled=true;
    try {
      const res=await fetch(url+'/auth/v1/user',{method:'PUT',headers:{'Content-Type':'application/json',apikey:anon,Authorization:'Bearer '+session.access_token},body:JSON.stringify({phone:v})});
      if (!res.ok) throw new Error('Phone updates must be enabled in your account provider.');
      pendingPhone=v;code.hidden=false;verify.hidden=false;
      result.textContent='If SMS verification is configured, enter the code sent to this number. The number cannot be used to sign in until it is verified.';
    }catch(err){result.textContent=err.message;}
    finally{sendCode.disabled=false;}
  });
  verify.addEventListener('click',async()=>{
    if(!pendingPhone||!code.value.trim())return;
    verify.disabled=true;
    try {
      const res=await fetch(url+'/auth/v1/verify',{method:'POST',headers:{'Content-Type':'application/json',apikey:anon},body:JSON.stringify({type:'phone_change',phone:pendingPhone,token:code.value.trim()})});
      if (!res.ok) throw new Error('The code could not be verified. Check it or request a new one.');
      result.textContent='Phone number verified. You can use it for sign-in after your authentication provider has activated phone/password login.';
      code.hidden=true;verify.hidden=true;
    }catch(err){result.textContent=err.message;}
    finally{verify.disabled=false;}
  });
  link.addEventListener('click', async () => {
    if (!plate.value.trim()) {result.textContent = 'Enter your vehicle registration first.';return;}
    link.disabled = true;
    try {
      const res = await fetch(url+'/functions/v1/plate-login', {
        method:'POST',headers:{'Content-Type':'application/json',apikey:anon,Authorization:'Bearer '+session.access_token},
        body:JSON.stringify({action:'link',plate:plate.value.trim()})
      });
      const data = await res.json();
      result.textContent = data.message || 'Please contact CCS to verify your vehicle.';
    } catch { result.textContent='Could not submit your registration. Please contact CCS.'; }
    finally {link.disabled=false;}
  });
  logout.addEventListener('click', async () => {
    try { await fetch(url+'/auth/v1/logout',{method:'POST',headers:{apikey:anon,Authorization:'Bearer '+session.access_token}}); }catch{}
    sessionStorage.removeItem('ccs-auth-session'); location.assign('login.html');
  });
})();
