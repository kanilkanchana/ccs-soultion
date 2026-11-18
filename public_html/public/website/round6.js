(() => {
  'use strict';
  const $ = selector => document.querySelector(selector);
  const $$ = selector => [...document.querySelectorAll(selector)];
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const dialog = $('#social-player-dialog');
  const videos = window.CCS_SOCIAL_VIDEOS || [];
  if (dialog) {
    const frame = $('#social-player-frame');
    const close = () => { frame.replaceChildren(); dialog.close(); };
    $$('[data-social-play]').forEach(button => button.addEventListener('click', () => {
      const video = videos.find(item => item.id === button.dataset.socialPlay);
      if (!video) return;
      $('#social-player-title').textContent = video.title;
      const iframe = document.createElement('iframe');
      iframe.src = video.embed_url;
      iframe.title = `${video.title} — CCS Solutions on ${video.platform}`;
      iframe.allow = 'autoplay; encrypted-media; fullscreen; picture-in-picture';
      iframe.allowFullscreen = true;
      iframe.referrerPolicy = 'strict-origin-when-cross-origin';
      frame.replaceChildren(iframe);
      $('#social-player-original').href = video.url;
      $('#social-player-original').firstChild.textContent = `Watch original on ${video.platform} `;
      dialog.showModal();
    }));
    $('#social-player-close').addEventListener('click',close);
    dialog.addEventListener('cancel', () => frame.replaceChildren());
    dialog.addEventListener('close', () => { if (!dialog.open) frame.replaceChildren(); });
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const rect = dialog.getBoundingClientRect();
      if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) close();
    });
    $$('[data-social-filter]').forEach(button => button.addEventListener('click', () => {
      $$('[data-social-filter]').forEach(item => item.setAttribute('aria-pressed',String(item === button)));
      const filter = button.dataset.socialFilter;
      let shown = 0;
      $$('.social-video-card').forEach(card => {
        card.hidden = filter !== 'all' && card.dataset.socialPlatform !== filter;
        if (!card.hidden) { shown++; card.classList.add('page-visible'); }
      });
      $('#social-video-count').textContent = `${String(shown).padStart(2,'0')} ${filter === 'all' ? 'WORKSHOP VIDEOS' : filter.toUpperCase() + ' VIDEOS'}`;
    }));
  }
  const ambient = $$('.ambient-video');
  const toggle = $('.ambient-toggle');
  let manuallyPaused = false;
  const inView = new WeakMap();
  const blocked = () => manuallyPaused || reduced.matches || document.documentElement.classList.contains('motion-off') || document.hidden;
  function updateAmbient() {
    const motionOff = reduced.matches || document.documentElement.classList.contains('motion-off');
    if (toggle) {
      toggle.disabled = motionOff;
      toggle.textContent = motionOff ? 'Background motion off' : manuallyPaused ? 'Play background' : 'Pause background';
      toggle.setAttribute('aria-pressed',String(motionOff || manuallyPaused));
    }
    ambient.forEach(video => {
      if (blocked() || !inView.get(video)) video.pause();
      else video.play().catch(() => {});
    });
  }
  if (ambient.length) {
    const observer = new IntersectionObserver(entries => { entries.forEach(entry => inView.set(entry.target,entry.isIntersecting)); updateAmbient(); },{threshold:.1});
    ambient.forEach(video => observer.observe(video));
    toggle?.addEventListener('click', () => { manuallyPaused = !manuallyPaused; updateAmbient(); });
    reduced.addEventListener('change',updateAmbient);
    document.addEventListener('ccs:motion',updateAmbient);
    document.addEventListener('visibilitychange',updateAmbient);
    updateAmbient();
  }
})();
