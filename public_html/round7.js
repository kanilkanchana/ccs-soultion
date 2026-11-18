(() => {
  'use strict';
  const header = document.querySelector('.site-header');
  let tick = false;
  const updateHeader = () => { header?.classList.toggle('is-scrolled', scrollY > 20); tick = false; };
  window.addEventListener('scroll', () => { if (!tick) { tick = true; requestAnimationFrame(updateHeader); } }, { passive: true });
  updateHeader();
  document.querySelectorAll('[data-copy-request]').forEach(button => button.addEventListener('click', async () => {
    const text = document.getElementById(button.dataset.copyRequest)?.textContent || '';
    if (!text) return;
    try {
      if (!navigator.clipboard?.writeText) throw new Error('Clipboard unavailable');
      await navigator.clipboard.writeText(text);
      button.textContent = 'Request copied ✓';
    } catch {
      const range = document.createRange();
      range.selectNodeContents(document.getElementById(button.dataset.copyRequest));
      const selection = window.getSelection(); selection.removeAllRanges(); selection.addRange(range);
      button.textContent = 'Message selected — copy it';
    }
  }));
})();
