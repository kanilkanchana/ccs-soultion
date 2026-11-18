/* CCS Premium v3: presentation-only theme toggle; existing server route persists theme. */
(() => {
  'use strict';
  const LIGHT = new Set(['light','skyline','desert','aqua']);
  const root = document.documentElement;
  const themeForm = document.querySelector('form[data-pm-theme]');
  if (!themeForm) return;
  const control = themeForm.querySelector('button[type=submit]');
  const input = themeForm.querySelector('input[name=theme]');
  const label = themeForm.querySelector('.pm-theme-label');
  const message = themeForm.querySelector('.pm-theme-message');
  if (!control || !input) return;
  function update(theme) {
    root.dataset.ccsTheme = theme;
    root.dataset.pmTheme = LIGHT.has(theme) ? 'light' : 'dark';
    const light = LIGHT.has(theme);
    input.value = light ? 'dark' : 'light';
    if (label) label.textContent = light ? control.dataset.lightLabel : control.dataset.darkLabel;
    control.title = light ? control.dataset.toDark : control.dataset.toLight;
    control.setAttribute('aria-label', control.title);
  }
  update(root.dataset.ccsTheme || 'default');
  themeForm.addEventListener('submit', async (event) => {
    if (!window.fetch || !window.FormData) return; // Native form still works.
    event.preventDefault();
    if (control.disabled) return;
    const before = root.dataset.ccsTheme;
    const next = input.value;
    const payload = new FormData(themeForm);
    control.disabled = true;
    if (message) message.textContent = 'Saving theme…';
    update(next);
    try {
      const response = await fetch(themeForm.action, {
        method:'POST', body:payload, credentials:'same-origin', redirect:'follow',
        headers:{'Accept':'text/html','X-Requested-With':'XMLHttpRequest'}
      });
      const dest = new URL(response.url,location.href);
      if (!response.ok || /\/(login|logout)(?:\/|$)/i.test(dest.pathname) ||
          dest.origin !== location.origin || response.redirected && !/\/(ccs|t)(?:\/|$)/.test(dest.pathname)) {
        throw new Error('Theme could not be saved');
      }
      location.reload(); // Read back the saved staff preference.
    } catch(e) {
      update(before);
      control.disabled = false;
      if (message) message.textContent = 'Could not save. Check connection and try again.';
    }
  });
})();
