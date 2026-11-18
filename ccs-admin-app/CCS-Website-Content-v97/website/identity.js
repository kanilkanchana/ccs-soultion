(() => {
  'use strict';
  const motion = matchMedia('(prefers-reduced-motion: reduce)');
  const contact = document.getElementById('quick-contact');
  const toggle = document.getElementById('whatsapp-toggle');
  const panel = document.getElementById('whatsapp-panel');
  const close = document.getElementById('whatsapp-close');
  function setContact(open, returnFocus = false) {
    toggle.setAttribute('aria-expanded', String(open));
    panel.hidden = !open;
    if (open) close.focus({ preventScroll: true });
    else if (returnFocus) toggle.focus({ preventScroll: true });
  }
  toggle.addEventListener('click', () => setContact(panel.hidden));
  close.addEventListener('click', () => setContact(false, true));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !panel.hidden) { event.preventDefault(); setContact(false, true); }
  });
  document.addEventListener('pointerdown', event => {
    if (!panel.hidden && !contact.contains(event.target)) setContact(false);
  });
  document.querySelector('.menu-toggle')?.addEventListener('click', () => setContact(false));
  document.querySelectorAll('dialog').forEach(dialog => new MutationObserver(() => {
    if (dialog.open) setContact(false);
  }).observe(dialog, { attributes: true, attributeFilter: ['open'] }));

  const journey = document.getElementById('journey');
  const video = document.getElementById('interior-video');
  if (!journey || !video) return;
  let inView = false;
  function syncMotion() {
    const stopped = motion.matches || document.hidden || !inView || document.documentElement.classList.contains('motion-off');
    if (stopped) { video.pause(); return; }
    if (!video.querySelector('source').src) {
      video.querySelector('source').src = video.querySelector('source').dataset.src;
      video.load();
    }
    video.play().catch(() => {});
  }
  motion.addEventListener('change', syncMotion);
  document.addEventListener('visibilitychange', syncMotion);
  document.addEventListener('ccs:motion', syncMotion);
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(entries => { inView = entries[0].isIntersecting; syncMotion(); }, { threshold: .05 }).observe(journey);
  } else { inView = true; syncMotion(); }
})();
