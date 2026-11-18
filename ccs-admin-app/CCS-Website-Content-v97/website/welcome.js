(() => {
  'use strict';
  const promo = document.getElementById('welcome-promo');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const slides = window.CCS_CONTENT ? window.CCS_CONTENT.ads : [
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
    art.src = (slide.image.startsWith('https://')||slide.image.startsWith('assets/'))?slide.image:`assets/promotions/${slide.image}.webp`;
    art.alt = slide.alt;
    document.getElementById('promo-full-art').href = slide.full_image||art.src;
    const title=document.getElementById('promo-title');title.replaceChildren(document.createTextNode(slide.title));if(slide.subtitle){const em=document.createElement('em');em.textContent=slide.subtitle;title.append(document.createElement('br'),em);}
    document.getElementById('promo-kicker').textContent = slide.kicker;
    document.getElementById('promo-description').textContent = slide.description;
    document.getElementById('promo-count').textContent = `${String(current+1).padStart(2,'0')} / ${String(slides.length).padStart(2,'0')}`;
    const saving=promo.querySelector('.campaign-saving');saving.hidden=!Number(slide.discount);saving.querySelector('strong').replaceChildren(document.createTextNode(String(slide.discount||'')));const percent=document.createElement('small');percent.textContent='%';saving.querySelector('strong').append(percent);saving.querySelector('span').textContent=slide.discount_label||'OFF';promo.querySelector('.campaign-terms').textContent=slide.terms||'';promo.querySelector('[data-promo-inspection]').firstChild.textContent=(slide.cta_label||'Ask about this offer')+' ';
    promo.querySelectorAll('[data-promo-slide]').forEach(button => button.setAttribute('aria-pressed',String(Number(button.dataset.promoSlide) === current)));
    promo.classList.remove('art-changing');
    requestAnimationFrame(() => promo.classList.add('art-changing'));
  }
  window.ccsWelcomeGate = new Promise(resolve => {
    if (!promo||!slides.length) { if(promo)promo.hidden=true;resolve(); return; }const dots=promo.querySelector('.campaign-dots');dots.replaceChildren();slides.forEach((slide,i)=>{const b=document.createElement('button');b.type='button';b.dataset.promoSlide=String(i);b.setAttribute('aria-label','Show promotion '+(i+1));dots.append(b);});show(0);
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
      if(slides[current].cta_url){location.assign(slides[current].cta_url);promo.close();return;}const service=document.getElementById('inspection-service');if(service){const text=slides[current].service||slides[current].title;if(![...service.options].some(o=>o.value===text))service.add(new Option(text,text));service.value=text;}
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
    if(slides[1])new Image().src=slides[1].image;
    promo.showModal();
    schedule();
  });
})();
