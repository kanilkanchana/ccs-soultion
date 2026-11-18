(() => {
  'use strict';
  const $ = (s, root = document) => root.querySelector(s);
  const $$ = (s, root = document) => [...root.querySelectorAll(s)];
  const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (!motion.matches && 'IntersectionObserver' in window) {
    document.documentElement.classList.add('js-motion');
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('in'); observer.unobserve(entry.target); }
    }), { threshold: 0.1 });
    $$('.reveal').forEach(el => observer.observe(el));
  }
  let scrollTick = false;
  const updateProgress = () => {
    const height = document.documentElement.scrollHeight - innerHeight;
    $('.scroll-progress').style.width = `${height > 0 ? Math.min(100, Math.max(0, scrollY / height * 100)) : 0}%`;
    scrollTick = false;
  };
  window.addEventListener('scroll', () => { if (!scrollTick) { requestAnimationFrame(updateProgress); scrollTick = true; } }, { passive: true });
  window.addEventListener('resize', updateProgress);
  updateProgress();

  const menu = $('.menu-toggle');
  const mobileNav = $('#mobile-nav');
  const closeMenu = () => { menu.setAttribute('aria-expanded', 'false'); menu.setAttribute('aria-label', 'Open navigation'); mobileNav.hidden = true; document.body.classList.remove('menu-open'); };
  menu.addEventListener('click', () => {
    const open = menu.getAttribute('aria-expanded') !== 'true';
    menu.setAttribute('aria-expanded', String(open));
    menu.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    mobileNav.hidden = !open;
    document.body.classList.toggle('menu-open', open);
  });
  $$('#mobile-nav a').forEach(a => a.addEventListener('click', closeMenu));
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !mobileNav.hidden) { closeMenu(); menu.focus(); } });
  window.matchMedia('(min-width: 1001px)').addEventListener('change', e => { if (e.matches) closeMenu(); });

  const enquiry = $('#enquiry-dialog');
  const serviceDialog = $('#service-dialog');
  let currentService = 'General inspection';
  const openEnquiry = (service = 'General inspection') => {
    const wasMobile = !mobileNav.hidden;
    closeMenu();
    if (serviceDialog.open) serviceDialog.close();
    if (service === 'General inspection') { document.dispatchEvent(new CustomEvent('ccs:book', { detail: { service } })); return; }
    $('#enquiry-service').value = service;
    if (!$('#enquiry-service').value) $('#enquiry-service').value = 'General inspection';
    $('.form-feedback').textContent = '';
    enquiry.showModal();
    if (wasMobile) enquiry.addEventListener('close', () => menu.focus(), { once: true });
  };
  $$('[data-enquire]').forEach(button => button.addEventListener('click', () => openEnquiry(button.dataset.enquire)));
  $$('dialog').forEach(dialog => {
    $('[data-close]', dialog)?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => { if (event.target === dialog) { const b = dialog.getBoundingClientRect(); if (event.clientX < b.left || event.clientX > b.right || event.clientY < b.top || event.clientY > b.bottom) dialog.close(); } });
  });
  $('#enquiry-form').addEventListener('submit', event => {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form.reportValidity()) return;
    const data = new FormData(form);
    const name = String(data.get('name')).trim();
    const vehicle = String(data.get('vehicle')).trim();
    if (name.length < 2 || vehicle.length < 2) { $('.form-feedback').textContent = 'Please enter your name and vehicle model.'; return; }
    const message = `Hello CCS Solutions, I would like to enquire about ${data.get('service')}.\n\nName: ${name}\nVehicle: ${vehicle}${String(data.get('message')).trim() ? '\nDetails: ' + String(data.get('message')).trim() : ''}\n\nPlease let me know the next available inspection time or quotation details.`;
    const email = event.submitter?.value === 'email';
    const url = email ? `mailto:ccssolutionshybrid@gmail.com?subject=${encodeURIComponent('Service enquiry — ' + vehicle)}&body=${encodeURIComponent(message)}` : `https://wa.me/94717937011?text=${encodeURIComponent(message)}`;
    const link = document.createElement('a'); link.href = url;
    if (!email) { link.target = '_blank'; link.rel = 'noopener noreferrer'; }
    link.click();
    $('.form-feedback').replaceChildren(document.createTextNode(email ? 'Your enquiry is ready in your email app. Press Send there. If no app opens, email ccssolutionshybrid@gmail.com. ' : 'Your enquiry is ready. Press Send in WhatsApp. If it did not open, '));
    const fallback = document.createElement('a'); fallback.href = url;
    if (!email) { fallback.target = '_blank'; fallback.rel = 'noopener noreferrer'; }
    fallback.textContent = email ? 'Open email again ↗' : 'Open WhatsApp ↗'; $('.form-feedback').append(fallback);
  });
  const services = {
  "battery": {
    "title": "HYBRID BATTERY CARE.",
    "request": "Hybrid battery care",
    "serial": "01 / HYBRID BATTERY CARE",
    "image": "battery",
    "alt": "High voltage battery pack in a hybrid vehicle",
    "description": "Understand the condition of your hybrid battery before choosing a repair or replacement. System scans, condition checks and the findings from your vehicle guide the next step: servicing, module work, reconditioning or a complete replacement.",
    "symptoms": [
      "Hybrid-system or battery warning messages",
      "A noticeable change in fuel use or available electric assistance",
      "Battery charge display changing more quickly than usual",
      "Battery cooling fan running frequently or more loudly"
    ],
    "items": [
      "Battery diagnosis, scanning and health testing",
      "Module / cell assessment and replacement where suitable",
      "Reconditioning, balancing and cleaning",
      "Battery cooling fan and cooling-system service",
      "New, reconditioned or tested used replacement options",
      "Removal, installation and relevant post-work checks"
    ],
    "models": "Toyota Prius, Aqua, Auris, Camry and Sai; Lexus CT 200h; Honda Fit, Fit Shuttle, Freed, Shuttle, Vezel, Grace and Civic Hybrid; Mazda Axela Hybrid; selected Suzuki mild-hybrid variants. Battery type and chassis compatibility are checked first.",
    "time": "An estimate is provided after the initial assessment. Battery testing, balancing or parts sourcing may require a longer workshop stay; confirm the available inspection slot and expected handover time.",
    "before": "Share your model, chassis, warning messages and any recent repair history. Keep a note of when the symptoms appear.",
    "after": "Discuss the test findings, work carried out, relevant checks and any care or warranty details that apply to the selected parts.",
    "related": [
      "diagnostics",
      "abs"
    ]
  },
  "abs": {
    "title": "ABS & BRAKE SYSTEMS.",
    "request": "ABS and brake systems",
    "serial": "02 / ABS & BRAKING",
    "image": "brakes",
    "alt": "Technician checking a brake disc and wheel assembly",
    "description": "Find the cause of an ABS warning or braking concern with the relevant system checks. The repair route depends on the fault and may involve sensors, the ABS pump, the control module or other brake components.",
    "symptoms": [
      "ABS, brake or stability-system warning lights",
      "Unusual pump cycling, noises or changes in pedal feel",
      "Braking behaviour that feels different from normal",
      "Stored ABS fault codes or a recurring warning after previous work"
    ],
    "items": [
      "ABS scan and fault investigation",
      "ABS pump and module assessment",
      "ABS module repair or replacement where suitable",
      "Pump repair or replacement options",
      "Wheel-speed sensor checks and replacement",
      "Brake-system repair, bleeding and relevant final checks"
    ],
    "models": "Selected Toyota / Lexus, Honda, Suzuki and Mazda models shown in our vehicle gallery. ABS unit, sensor and brake-system fitment depend on the exact chassis and variant.",
    "time": "The workshop confirms an estimate after fault checks. A sensor repair and an ABS-unit replacement can involve different work and parts lead times; ask for the inspection duration and repair schedule.",
    "before": "Describe the warning lights, pedal changes or noises and any recent brake work. Tell the team if the vehicle has been advised to remain off the road.",
    "after": "Review the identified fault, repaired or replaced parts, relevant brake-system checks and any further recommendations.",
    "related": [
      "diagnostics",
      "battery"
    ]
  },
  "diagnostics": {
    "title": "CLEARER DIAGNOSTICS.",
    "request": "Vehicle diagnostics",
    "serial": "03 / SCANNING & DIAGNOSTICS",
    "image": "diagnostics",
    "alt": "Mechanic using diagnostic equipment to check vehicle systems",
    "description": "A warning code is a starting point. Scanning and targeted electrical or component checks help the team connect your symptoms with the likely cause, then discuss the appropriate repair and parts options with you.",
    "symptoms": [
      "Check-engine, hybrid-system or electrical warning messages",
      "Starting issues or intermittent electrical behaviour",
      "Changes in performance with no clear cause",
      "Repeated warning codes after a previous repair"
    ],
    "items": [
      "Vehicle scanning and fault-code review",
      "Relevant electrical and system checks",
      "ECU-related diagnosis and repair options",
      "Battery and ABS system checks",
      "Assessment of related pumps and supported components",
      "A findings discussion and recommended next steps"
    ],
    "models": "Toyota, Lexus, Honda, selected Suzuki and Mazda models in the gallery. Available scanning and checks are confirmed for the vehicle’s year, chassis and systems.",
    "time": "Request an initial assessment slot. The team provides a further time estimate if intermittent faults, additional testing or component access require more investigation.",
    "before": "Bring details of warning messages, fault codes, when the problem occurs and any recent parts or electrical work.",
    "after": "Receive an explanation of the findings, the next checks or repair options, and any parts or compatibility questions to resolve.",
    "related": [
      "battery",
      "abs"
    ]
  }
};
  let currentServiceKey = 'battery';
  const fillList = (selector, items) => $(selector).replaceChildren(...items.map(text => { const li = document.createElement('li'); li.textContent = text; return li; }));
  function showService(key) {
    const data = services[key];
    if (!data) return;
    currentServiceKey = key;
    currentService = data.request;
    $('#service-title').textContent = data.title;
    $('#service-description').textContent = data.description;
    $('#service-detail-serial').textContent = data.serial;
    const image = $('#service-detail-image');
    image.src = `assets/${data.image}.webp`;
    image.srcset = data.image === 'brakes' ? 'assets/brakes.webp 1067w, assets/brakes-4k.webp 3651w' : `assets/${data.image}.webp 1600w, assets/${data.image}-4k.webp 3840w`;
    image.sizes = '(max-width: 700px) 94vw, 43vw';
    image.alt = data.alt;
    fillList('#service-symptoms', data.symptoms);
    fillList('#service-inclusions', data.items);
    $('#service-models').textContent = data.models;
    $('#service-time').textContent = data.time;
    $('#service-before').textContent = data.before;
    $('#service-after').textContent = data.after;
    $('#service-whatsapp').href = `https://wa.me/94717937011?text=${encodeURIComponent(`Hello CCS Solutions, I would like to ask about ${data.request}. My vehicle model is: `)}`;
    $('#service-related').replaceChildren(...data.related.map(key => { const button = document.createElement('button'); button.type = 'button'; button.dataset.relatedService = key; button.textContent = services[key].request + ' ↗'; return button; }));
    if (!serviceDialog.open) serviceDialog.showModal();
    else if (!motion.matches) $('.service-detail-content').animate([{ opacity: .2, transform: 'translateX(24px)' }, { opacity: 1, transform: 'translateX(0)' }], { duration: 370, easing: 'cubic-bezier(.2,.7,.3,1)' });
    serviceDialog.scrollTop = 0;
  }
  $$('[data-service]').forEach(button => button.addEventListener('click', () => showService(button.dataset.service)));
  $('#service-related').addEventListener('click', event => { const button = event.target.closest('[data-related-service]'); if (button) { showService(button.dataset.relatedService); $('#service-title').tabIndex = -1; $('#service-title').focus({ preventScroll: true }); } });
  $('#service-request').addEventListener('click', () => openEnquiry(currentService));
  $('#service-book').addEventListener('click', () => { serviceDialog.close(); document.dispatchEvent(new CustomEvent('ccs:book', { detail: { service: services[currentServiceKey].request } })); });
  $('#credits-button')?.addEventListener('click', () => $('#credits-dialog').showModal());

  const batteryOptions = {
    new: { tag: 'A NEW BEGINNING', title: 'Brand-new batteries & parts.', text: 'Ask about a new replacement battery, battery modules or ABS components matched to your vehicle. Request a quote for current availability and warranty details.', enquiry: 'Brand-new batteries and parts' },
    reconditioned: { tag: 'ANOTHER WAY FORWARD', title: 'Reconditioned batteries & parts.', text: 'Explore reconditioned replacement options for your hybrid. Discuss the condition, compatibility, testing and applicable warranty with CCS Solutions before choosing.', enquiry: 'Reconditioned batteries and parts' },
    tested: { tag: 'A CONSIDERED ALTERNATIVE', title: 'Tested used batteries & parts.', text: 'Ask about tested used batteries, modules, ABS units, pumps and sensors. The workshop can confirm suitable options, stock, condition and any available warranty for your vehicle.', enquiry: 'Tested used batteries and parts' }
  };
  const tabs = $$('[data-battery]');
  const conditionVisual = $('#battery-condition-visual');
  const conditionPresets = {
    new: { mode: '01 / BRAND NEW', headline: 'NEW ENERGY.<br>A FRESH START.', signals: ['NEW COMPONENTS', 'FRESH START', 'VEHICLE MATCH'], label: 'Illustrative new hybrid battery with cells illuminating in sequence' },
    reconditioned: { mode: '02 / RECONDITIONED', headline: 'CELL CARE.<br>RENEWED.', signals: ['INSPECT', 'REBALANCE', 'RECONDITION'], label: 'Illustrative reconditioned hybrid battery with cell levels balancing and a sweep of light' },
    tested: { mode: '03 / TESTED USED', headline: 'CHECKED.<br>CONSIDERED.', signals: ['INSPECT', 'TEST', 'SELECT'], label: 'Illustrative tested used hybrid battery with a scanning light and verification marks' }
  };
  let batteryMotionPaused = false;
  const motionToggle = $('#battery-motion-toggle');
  function updateBatteryMotion() {
    conditionVisual.classList.toggle('motion-paused', batteryMotionPaused || motion.matches);
    motionToggle.hidden = motion.matches;
    motionToggle.setAttribute('aria-pressed', String(batteryMotionPaused));
    motionToggle.setAttribute('aria-label', batteryMotionPaused ? 'Play battery animation' : 'Pause battery animation');
    motionToggle.innerHTML = batteryMotionPaused ? '▶ <span>Play motion</span>' : 'Ⅱ <span>Pause motion</span>';
  }
  motionToggle.addEventListener('click', () => { batteryMotionPaused = !batteryMotionPaused; updateBatteryMotion(); });
  motion.addEventListener('change', updateBatteryMotion);
  if ('IntersectionObserver' in window) new IntersectionObserver(entries => {
    conditionVisual.classList.toggle('animation-idle', !entries[0].isIntersecting);
  }, { threshold: .1 }).observe(conditionVisual);
  else conditionVisual.classList.remove('animation-idle');
  updateBatteryMotion();
  function chooseBattery(tab, focus = false) {
    const data = batteryOptions[tab.dataset.battery];
    const visual = conditionPresets[tab.dataset.battery];
    tabs.forEach(button => { button.setAttribute('aria-selected', String(button === tab)); button.tabIndex = button === tab ? 0 : -1; });
    $('#battery-tag').textContent = data.tag;
    $('#battery-option-title').textContent = data.title;
    $('#battery-option-text').textContent = data.text;
    $('#battery-enquire').dataset.enquire = data.enquiry;
    $('#battery-panel').setAttribute('aria-labelledby', tab.id);
    conditionVisual.dataset.condition = tab.dataset.battery;
    $('.condition-scene').setAttribute('aria-label', visual.label);
    $('#condition-mode').textContent = visual.mode;
    $('#condition-headline').innerHTML = visual.headline;
    visual.signals.forEach((signal, i) => { $(`#condition-signal-${i + 1}`).replaceChildren(document.createElement('i'), document.createTextNode(signal)); });
    if (!motion.matches) {
      $('#battery-panel').animate([{ opacity: .25, transform: 'translateY(7px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 300, easing: 'ease-out' });
      $('.condition-scene').animate([{ opacity: .3, transform: 'translateY(9px)' }, { opacity: 1, transform: 'translateY(0)' }], { duration: 550, easing: 'cubic-bezier(.2,.65,.3,1)' });
    }
    if (focus) tab.focus();
  }
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => chooseBattery(tab));
    tab.addEventListener('keydown', event => {
      let target;
      if (event.key === 'ArrowRight') target = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') target = (index - 1 + tabs.length) % tabs.length;
      if (event.key === 'Home') target = 0;
      if (event.key === 'End') target = tabs.length - 1;
      if (target !== undefined) { event.preventDefault(); chooseBattery(tabs[target], true); }
    });
  });
  $('#year').textContent = new Date().getFullYear();
})();
