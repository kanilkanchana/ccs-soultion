/* Shared source for .store and .lk: the current hostname selects its own admin. */
(() => {
  'use strict';
  const host = location.hostname.replace(/^www\./, '');
  if (!['ccssolutions.store', 'ccssolutions.lk'].includes(host)) {
    window.CCS_CONTENT = {ads:[],models:[],videos:[],offers:[]};
    window.CCS_FEED_FAILED = true;
    return;
  }
  window.CCS_ADMIN_ORIGIN = 'https://admin.' + host;
  document.write('<script src="' + window.CCS_ADMIN_ORIGIN + '/website-content.js?v=110" defer onerror="window.CCS_CONTENT={ads:[],models:[],videos:[],offers:[]};window.CCS_FEED_FAILED=true"><\/script>');
  document.addEventListener('DOMContentLoaded', () => {
    if (!window.CCS_FEED_FAILED) return;
    const message = document.createElement('p');
    message.setAttribute('role','status');
    message.textContent = 'The workshop catalogue is temporarily unavailable. Please call 071 793 7011.';
    message.style.cssText = 'padding:12px;margin:0;background:#342b14;color:#fff';
    (document.querySelector('#catalog-grid') || document.querySelector('main'))?.prepend(message);
  });
})();
