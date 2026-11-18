/* CCS v97. No third-party libraries; uses the application's existing staff theme setting. */
(() => {
  'use strict';
  const toggle = document.querySelector('[data-theme-toggle]');
  if (!toggle) return;
  const root = document.documentElement;
  const lightThemes = new Set(['light', 'skyline', 'desert', 'aqua']);
  function updateLabel(theme) {
    const isLight = lightThemes.has(theme);
    const label = toggle.querySelector('[data-theme-label]');
    const icon = toggle.querySelector('.pm-v5-switch-icon');
    if (label) label.textContent = isLight ? 'Light' : 'Dark';
    if (icon) icon.textContent = isLight ? '☀' : '☾';
    toggle.setAttribute('aria-pressed', String(isLight));
    toggle.title = isLight ? 'Switch to dark theme' : 'Switch to light theme';
  }
  updateLabel(root.dataset.ccsTheme || 'dark');
  toggle.addEventListener('click', async () => {
    if (toggle.disabled) return;
    const before = root.dataset.ccsTheme || 'dark';
    const next = lightThemes.has(before) ? 'dark' : 'light';
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token || !toggle.dataset.endpoint) return;
    toggle.disabled = true;
    toggle.setAttribute('aria-busy', 'true');
    try {
      const res = await fetch(toggle.dataset.endpoint, {
        method: 'POST', credentials: 'same-origin', redirect: 'follow',
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'},
        body: new URLSearchParams({theme: next}).toString()
      });
      if (!res.ok || /(?:\/login|\/setup)(?:[/?#]|$)/i.test(new URL(res.url).pathname)) {
        throw new Error('Theme preference could not be saved');
      }
      root.dataset.ccsTheme = next;
      updateLabel(next);
    } catch (e) {
      root.dataset.ccsTheme = before;
      updateLabel(before);
      window.alert('Theme could not be saved. Your previous theme is unchanged.');
    } finally {
      toggle.disabled = false;
      toggle.removeAttribute('aria-busy');
    }
  });
})();
