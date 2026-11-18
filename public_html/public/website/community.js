(() => {
  'use strict';
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  document.querySelectorAll('[data-brand-target]').forEach(link => link.addEventListener('click', event => {
    event.preventDefault();
    document.dispatchEvent(new CustomEvent('ccs:filter-make', { detail: link.dataset.brandTarget }));
    document.getElementById('vehicles').scrollIntoView({ behavior: reduced.matches ? 'instant' : 'smooth', block: 'start' });
    const heading = document.getElementById('vehicles-title');
    heading.tabIndex = -1;
    heading.focus({ preventScroll: true });
  }));
  const rail = document.getElementById('review-rail');
  if (!rail) return;
  const cards = [...rail.querySelectorAll('.google-review')];
  const carousel = rail.closest('.review-carousel');
  const previous = document.getElementById('reviews-prev');
  const next = document.getElementById('reviews-next');
  const pause = document.getElementById('reviews-pause');
  const count = document.getElementById('reviews-counter');
  let current = 0, paused = false, inView = false, dragging = false, timer;
  const progress = document.querySelector('.review-slide-progress');
  function move(index) {
    current = (index + cards.length) % cards.length;
    rail.scrollTo({ left: cards[current].offsetLeft, behavior: reduced.matches || document.documentElement.classList.contains('motion-off') ? 'instant' : 'smooth' });
    count.textContent = `${String(current + 1).padStart(2, '0')} / ${String(cards.length).padStart(2, '0')}`;
  }
  function syncTimer() {
    clearTimeout(timer);
    progress?.classList.remove('is-running');
    const disabled = reduced.matches || document.documentElement.classList.contains('motion-off');
    pause.disabled = disabled;
    pause.textContent = disabled ? 'Motion off' : paused ? 'Play slideshow' : 'Pause slideshow';
    pause.setAttribute('aria-pressed', String(paused || disabled));
    if (!paused && !disabled && inView && !dragging && !document.hidden) {
      if (progress) { void progress.offsetWidth; progress.classList.add('is-running'); }
      timer = setTimeout(() => { move(current + 1); syncTimer(); }, 6500);
    }
  }
  previous.addEventListener('click', () => { move(current - 1); syncTimer(); });
  next.addEventListener('click', () => { move(current + 1); syncTimer(); });
  pause.addEventListener('click', () => { paused = !paused; syncTimer(); });
  rail.addEventListener('scroll', () => {
    const step = cards[1] ? cards[1].offsetLeft - cards[0].offsetLeft : rail.clientWidth;
    current = Math.max(0, Math.min(cards.length - 1, Math.round(rail.scrollLeft / step)));
    count.textContent = `${String(current + 1).padStart(2, '0')} / ${String(cards.length).padStart(2, '0')}`;
  }, { passive: true });
  rail.addEventListener('keydown', event => {
    if (event.target === rail && ['ArrowLeft', 'ArrowRight'].includes(event.key)) { event.preventDefault(); move(current + (event.key === 'ArrowRight' ? 1 : -1)); syncTimer(); }
  });
  rail.addEventListener('pointerdown', () => { dragging = true; syncTimer(); });
  window.addEventListener('pointerup', () => { if (dragging) { dragging = false; syncTimer(); } });
  new IntersectionObserver(entries => { inView = entries[0].isIntersecting; syncTimer(); }, { threshold: .25 }).observe(rail);
  reduced.addEventListener('change', syncTimer);
  document.addEventListener('visibilitychange', syncTimer);
  document.addEventListener('ccs:motion', syncTimer);
  window.addEventListener('resize', () => move(current));
  syncTimer();
})();
