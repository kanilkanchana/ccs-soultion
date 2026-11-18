(() => {
  'use strict';
  const promo = document.getElementById('welcome-promo');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const slides = [
    { image:'hybrid-parts', alt:'CCS Solutions car parts and accessories promotion: 20% off selected car parts. Call 071 793 7011.', kicker:'01 / HYBRID PARTS & ACCESSORIES', title:'THE RIGHT PART.<br><em>A BETTER START.</em>', description:'Explore hybrid batteries, ABS components, inverter parts, sensors and accessories. Ask the team about the selected parts in this offer.', service:'Brand-new batteries and parts' },
    { image:'abs-service', alt:'CCS Solutions ABS service promotion: 20% off ABS parts and repairs. Call 071 793 7011.', kicker:'02 / ABS PARTS & REPAIRS', title:'CONFIDENCE.<br><em>IN EVERY STOP.</em>', description:'From ABS diagnosis to modules, sensors and brake components. Talk to the team about your warning lights and the ABS service promotion.', service:'ABS and brake systems' }
  ];
  let current = 0, paused = false, timer;
  function schedule() {
    clearTimeout(timer);
    if (!promo?.open) return;
    const disabled = reduced.matches || document.documentElement.classList.contains('motion-off');
    const control = document.getElementById('promo-pause');
    control.disabled = disabled;
    control.textContent = disabled ? 'Motion off' : paused ? 'Play slides' : 'Pause slides';
    control.setAttribute('aria-pressed', String(paused || disabled));
    if (!paused && !disabled && !document.hidden) timer = setTimeout(() => { show(current + 1); schedule(); },5500);
  }
  function show(index) {
    current = (index + slides.length) % slides.length;
    const slide = slides[current];
    const art = document.getElementById('promo-art');
    art.src = `assets/promotions/${slide.image}.webp`;
    art.alt = slide.alt;
    document.getElementById('promo-full-art').href = `assets/promotions/${slide.image}.png`;
    document.getElementById('promo-title').innerHTML = slide.title;
    document.getElementById('promo-kicker').textContent = slide.kicker;
    document.getElementById('promo-description').textContent = slide.description;
    document.getElementById('promo-count').textContent = `${String(current+1).padStart(2,'0')} / 02`;
    promo.querySelector('.campaign-saving>span').innerHTML = current === 0 ? 'OFF<br>SELECTED PARTS' : 'OFF<br>ABS PARTS & REPAIRS';
    promo.querySelectorAll('[data-promo-slide]').forEach(button => button.setAttribute('aria-pressed',String(Number(button.dataset.promoSlide) === current)));
    promo.classList.remove('art-changing');
    requestAnimationFrame(() => promo.classList.add('art-changing'));
  }
  window.ccsWelcomeGate = new Promise(resolve => {
    if (!promo) { resolve(); return; }
    const forced = new URLSearchParams(location.search).has('show_offer');
    let seen = false;
    try { seen = sessionStorage.getItem('ccs-welcome-v4') === 'seen'; } catch {}
    if ((seen || location.hash) && !forced) { resolve(); return; }
    document.body.classList.add('promo-open');
    document.getElementById('site-loader').hidden = true;
    promo.addEventListener('close', () => {
      document.body.classList.remove('promo-open');
      clearTimeout(timer);
      try { sessionStorage.setItem('ccs-welcome-v4', 'seen'); } catch {}
      resolve();
    }, { once: true });
    promo.querySelector('[data-promo-continue]').addEventListener('click', () => promo.close());
    promo.querySelector('[data-promo-close]').addEventListener('click', () => promo.close());
    promo.querySelector('[data-promo-inspection]').addEventListener('click', () => {
      document.getElementById('inspection-service').value = slides[current].service;
      location.hash = 'inspection';
      promo.close();
    });
    promo.querySelectorAll('[data-promo-slide]').forEach(button => button.addEventListener('click', () => { show(Number(button.dataset.promoSlide)); schedule(); }));
    promo.querySelector('[data-promo-next]').addEventListener('click', () => { show(current+1); schedule(); });
    document.getElementById('promo-pause').addEventListener('click', () => { paused = !paused; schedule(); });
    promo.addEventListener('keydown', event => {
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') { event.preventDefault(); show(current + (event.key === 'ArrowRight' ? 1 : -1)); schedule(); }
    });
    document.addEventListener('visibilitychange', schedule);
    document.addEventListener('ccs:motion', schedule);
    reduced.addEventListener('change', schedule);
    new Image().src = 'assets/promotions/abs-service.webp';
    promo.showModal();
    schedule();
  });
})();
