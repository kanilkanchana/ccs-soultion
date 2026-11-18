(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = selector => [...document.querySelectorAll(selector)];
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  if (document.body.classList.contains('internal-page')) {
    const menu = $('.menu-toggle');
    const nav = $('#mobile-nav');
    function closeMenu() {
      nav.hidden = true;
      menu.setAttribute('aria-expanded', 'false');
      menu.setAttribute('aria-label', 'Open navigation');
      document.body.classList.remove('menu-open');
    }
    menu.addEventListener('click', () => {
      const open = nav.hidden;
      nav.hidden = !open;
      menu.setAttribute('aria-expanded', String(open));
      menu.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      document.body.classList.toggle('menu-open', open);
    });
    $$('#mobile-nav a').forEach(link => link.addEventListener('click', closeMenu));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !nav.hidden) { closeMenu(); menu.focus(); } });
    matchMedia('(min-width: 1001px)').addEventListener('change', event => { if (event.matches) closeMenu(); });
    const progress = $('.scroll-progress');
    const update = () => {
      const height = document.documentElement.scrollHeight - innerHeight;
      progress.style.width = `${height > 0 ? Math.min(100, scrollY / height * 100) : 0}%`;
      $('.site-header').classList.toggle('is-scrolled', scrollY > 20);
    };
    window.addEventListener('scroll', update, { passive: true });
    update();
    $$('.page-video').forEach(video => video.addEventListener('play', () => $$('.page-video').forEach(other => { if (video !== other) other.pause(); })));
    $$('[data-video-filter]').forEach(button => button.addEventListener('click', () => {
      $$('[data-video-filter]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
      $$('.video-story').forEach(card => {
        card.hidden = button.dataset.videoFilter !== 'all' && card.dataset.kind !== button.dataset.videoFilter;
        if (card.hidden) card.querySelector('video')?.pause();
      });
    }));
    const inspection = $('#inspection-form');
    if (inspection) {
      const params = new URLSearchParams(location.search);
      for (const key of ['vehicle', 'service']) {
        const field = inspection.elements.namedItem(key);
        const value = params.get(key);
        if (value && field) {
          if (![...field.options].some(option => option.value === value) && key === 'vehicle') field.add(new Option(value, value));
          field.value = value;
        }
      }
    }
  }
  const preference = $('#motion-preference');
  function updateMotion(off) {
    document.documentElement.classList.toggle('motion-off', off);
    if (preference) { preference.setAttribute('aria-pressed', String(off)); preference.textContent = off ? 'Enable motion ↗' : 'Reduce motion ↗'; }
    if (off) $$('video').forEach(video => video.pause());
    document.dispatchEvent(new CustomEvent('ccs:motion'));
  }
  let motionOff = false;
  try { motionOff = localStorage.getItem('ccs-motion-off') === 'yes'; } catch {}
  updateMotion(motionOff);
  preference?.addEventListener('click', () => {
    motionOff = !document.documentElement.classList.contains('motion-off');
    try { localStorage.setItem('ccs-motion-off', motionOff ? 'yes' : 'no'); } catch {}
    updateMotion(motionOff);
  });
  if ('IntersectionObserver' in window && !reduced.matches) {
    document.documentElement.classList.add('page-motion-ready');
    const observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('page-visible'); observer.unobserve(entry.target); } }), { threshold: .08 });
    $$('.page-reveal').forEach(element => observer.observe(element));
  }
  const warranty = $('#warranty-enquiry');
  warranty?.addEventListener('submit', event => {
    event.preventDefault();
    if (!warranty.reportValidity()) return;
    const data = new FormData(warranty);
    const lines = ['Hello CCS Solutions, I would like to ask about warranty coverage.', '', `Name: ${data.get('name')}`, `Vehicle: ${data.get('vehicle')}`, `Invoice / job reference: ${data.get('reference') || 'Not provided'}`, `Details: ${data.get('message')}`, '', 'Please review the applicable written warranty and advise the next steps.'];
    const message = lines.join('\n');
    $('#warranty-preview').textContent = message;
    $('#warranty-whatsapp').href = `https://wa.me/94717937011?text=${encodeURIComponent(message)}`;
    $('#warranty-result').hidden = false;
    $('#warranty-result').scrollIntoView({ behavior: reduced.matches ? 'instant' : 'smooth', block: 'nearest' });
  });
  const note = $('#vehicle-note');
  if (note) {
    try { note.value = localStorage.getItem('ccs-vehicle-note') || ''; } catch {}
    $('#save-note').addEventListener('click', () => {
      try { localStorage.setItem('ccs-vehicle-note', note.value.trim()); $('#note-status').textContent = 'Saved on this device.'; }
      catch { $('#note-status').textContent = 'This browser could not save the note. You can still copy it.'; }
    });
    $('#clear-note').addEventListener('click', () => {
      note.value = '';
      try { localStorage.removeItem('ccs-vehicle-note'); } catch {}
      $('#note-status').textContent = 'Vehicle note cleared from this device.';
    });
  }
  const signIn = $('#customer-sign-in');
  if (signIn && !location.hostname.endsWith('.chatgpt.site')) {
    signIn.href = 'https://ccs-hybrid-home-1027.richswan129.chatgpt.site/signin-with-chatgpt?return_to=%2Fportal.html';
    $('#sign-in-note').textContent = 'Sign-in opens the hosted CCS site. Access is limited to accounts invited to this private preview.';
  }
  if ($('#year')) $('#year').textContent = String(new Date().getFullYear());
})();
