(() => {
  'use strict';
  const $ = selector => document.querySelector(selector);
  const config = window.CCS_AUTH_CONFIG || {};
  const url = String(config.supabaseUrl || '').replace(/\/+$/, '');
  const anonKey = String(config.publicAnonKey || '');
  const configured = /^https:\/\/[\w.-]+\.supabase\.co$/.test(url) && anonKey.length > 20;
  if (location.hostname.endsWith('.chatgpt.site')) document.querySelector('#hosted-site-login').hidden = false;
  const feedback = $('#login-feedback');
  const form = $('#login-form');
  const identifier = $('#login-identifier');
  const password = $('#login-password');
  const confirm = $('#confirm-password');
  const register = $('#mode-register');
  const signin = $('#mode-signin');
  const sendButton = $('#login-submit');
  let isRegister = false;
  function report(message, type = 'info') {
    feedback.textContent = message;
    feedback.className = `login-feedback is-${type}`;
    feedback.hidden = false;
  }
  const toggleLoading = busy => {
    sendButton.disabled = busy || !configured;
    sendButton.setAttribute('aria-busy', String(busy));
  };
  function setMode(toRegister) {
    isRegister = toRegister;
    signin.classList.toggle('is-selected', !isRegister);
    register.classList.toggle('is-selected', isRegister);
    signin.setAttribute('aria-pressed', String(!isRegister));
    register.setAttribute('aria-pressed', String(isRegister));
    $('#form-title').innerHTML = isRegister ? 'Join <em>CCS.</em>' : 'Welcome <em>back.</em>';
    $('#form-description').textContent = isRegister ? 'Create an account using your email address.' : 'Sign in to your CCS visit planner.';
    $('#identifier-label').textContent = isRegister ? 'Email address' : 'Email, phone or vehicle number';
    identifier.placeholder = isRegister ? 'you@example.com' : 'you@example.com / 071… / CAA-1234';
    identifier.autocomplete = isRegister ? 'email' : 'username';
    $('#identifier-hint').textContent = isRegister ? 'You may need to confirm your email before signing in.' : 'Vehicle number login needs a workshop-verified plate linked to your account.';
    $('#confirm-password-field').hidden = !isRegister;
    confirm.required = isRegister;
    password.autocomplete = isRegister ? 'new-password' : 'current-password';
    $('#forgot-password').hidden = isRegister;
    sendButton.innerHTML = `${isRegister ? 'Create account' : 'Sign in'} <span aria-hidden="true">↗</span>`;
    feedback.hidden = true;
  }
  signin.addEventListener('click', () => setMode(false));
  register.addEventListener('click', () => setMode(true));
  $('#toggle-password').addEventListener('click', () => {
    const show = password.type === 'password';
    password.type = show ? 'text' : 'password';
    $('#toggle-password').setAttribute('aria-pressed', String(show));
    $('#toggle-password').setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });
  if (configured) $('#login-config-message').hidden = true;
  if (!configured) {
    $('#login-config-message').hidden = false;
    toggleLoading(false);
    $('#forgot-password').disabled = true;
  }
  const headers = () => ({ 'Content-Type': 'application/json', apikey: anonKey, Authorization: `Bearer ${anonKey}` });
  async function call(path, payload, overrideHeaders) {
    const res = await fetch(`${url}${path}`, { method: 'POST', headers: overrideHeaders || headers(), body: JSON.stringify(payload) });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.msg || data.message || data.error_description || data.error || 'Unable to complete your request. Please try again.');
    return data;
  }
  function setSession(data) {
    if (!data?.access_token) return false;
    const session = { access_token: data.access_token, refresh_token: data.refresh_token || '', expires_at: Date.now() + (Number(data.expires_in) || 3600) * 1000 };
    sessionStorage.setItem('ccs-auth-session', JSON.stringify(session));
    return true;
  }
  const normalizePhone = value => {
    const v = value.replace(/[\s()\-.]/g, '');
    if (/^0[0-9]{9}$/.test(v)) return '+94' + v.slice(1);
    if (/^94[0-9]{9}$/.test(v)) return '+' + v;
    return v;
  };
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  async function submit(event) {
    event.preventDefault();
    if (!configured) { report('Account sign-in requires a configured secure authentication service. Contact the workshop for access.','error'); return; }
    const id = identifier.value.trim();
    const secret = password.value;
    if (!id || secret.length < 8) { report('Enter a valid identifier and a password of at least 8 characters.','error'); return; }
    if (isRegister && !emailPattern.test(id)) { report('Account registration requires an email address.','error'); return; }
    if (isRegister && secret !== confirm.value) { report('Both passwords must match.','error'); return; }
    toggleLoading(true); feedback.hidden = true;
    try {
      let response;
      if (isRegister) {
        response = await call('/auth/v1/signup', { email: id, password: secret, data: { app: 'CCS Solutions' } });
        if (setSession(response)) { location.assign('portal.html'); return; }
        report('If the address can receive confirmation emails, check its inbox to finish creating your account.','success');
      } else {
        const isEmail = emailPattern.test(id);
        const phone = normalizePhone(id);
        if (isEmail) response = await call('/auth/v1/token?grant_type=password', {email:id, password:secret});
        else if (/^\+[1-9]\d{7,14}$/.test(phone)) response = await call('/auth/v1/token?grant_type=password', {phone, password:secret});
        else if (/^[a-z0-9\s-]{3,20}$/i.test(id)) {
          // Vehicle plates are resolved *only on the server*, after workshop verification.
          response = await call('/functions/v1/plate-login', { action:'login', plate:id, password:secret });
        } else throw new Error('Enter a valid email address, phone number, or registered vehicle number.');
        if (!setSession(response)) throw new Error('Could not start an authenticated session.');
        location.assign('portal.html');
      }
    } catch (err) {
      report(/Invalid login credentials|Invalid credentials|plate not found/i.test(err.message) ? 'Sign-in failed. Please check the details or contact CCS Solutions.' : err.message, 'error');
    } finally { toggleLoading(false); }
  }
  form.addEventListener('submit', submit);
  $('#forgot-password').addEventListener('click', async () => {
    if (!configured) return;
    const id = identifier.value.trim();
    if (!emailPattern.test(id)) { report('Enter your account email address above, then select Forgot password.','error'); identifier.focus(); return; }
    toggleLoading(true);
    try {
      const redirect = location.protocol === 'https:' ? `${location.origin}${location.pathname}` : undefined;
      await call('/auth/v1/recover', {email:id, ...(redirect ? {redirect_to:redirect} : {})});
      report('If this email is registered, password reset instructions will arrive in its inbox.','success');
    } catch { report('Unable to request a reset right now. Please contact the workshop.','error'); }
    finally { toggleLoading(false); }
  });
  // The client requested a clean, automatic photo panel with no visible gallery UI.
  const slides = [...document.querySelectorAll('[data-login-slide]')];
  const gallery = $('.login-visual');
  const shell = $('.login-shell');
  const slideshow = $('.login-slides');
  const reduced = matchMedia('(prefers-reduced-motion:reduce)');
  let current = 0, timer;
  function syncMotion() {
    clearTimeout(timer);
    const paused = reduced.matches || document.documentElement.classList.contains('motion-off') ||
      document.hidden || gallery.matches(':hover') || shell.contains(document.activeElement);
    slideshow.classList.toggle('is-paused', paused);
    if (!paused && slides.length > 1) timer = setTimeout(() => {
      current = (current + 1) % slides.length;
      slides.forEach((slide,i) => slide.classList.toggle('is-active',i === current));
      syncMotion();
    },8000);
  }
  gallery.addEventListener('pointerenter',syncMotion);
  gallery.addEventListener('pointerleave',syncMotion);
  shell.addEventListener('focusin',syncMotion);
  shell.addEventListener('focusout',()=>queueMicrotask(syncMotion));
  reduced.addEventListener('change',syncMotion);
  document.addEventListener('visibilitychange',syncMotion);
  document.addEventListener('ccs:motion',syncMotion);
  syncMotion();
})();
