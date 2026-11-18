(() => {
  'use strict';
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = selector => [...document.querySelectorAll(selector)];
  const motion = matchMedia('(prefers-reduced-motion: reduce)');
  const form = $('#inspection-form');
  const result = $('#inspection-result');
  const date = $('#inspection-date');
  const today = () => new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Colombo', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
  date.min = today();
  function prepareBooking({ service, vehicle } = {}) {
    if (service) $('#inspection-service').value = service;
    if (vehicle) {
      const field = $('#inspection-vehicle');
      field.dispatchEvent(new CustomEvent('ccs:inspection-vehicle', { detail: vehicle }));
      field.value = vehicle;
    }
    result.hidden = true;
    const section = $('#inspection');
    section.scrollIntoView({ behavior: motion.matches ? 'instant' : 'smooth', block: 'start' });
    $('#inspection-vehicle').focus({ preventScroll: true });
  }
  document.addEventListener('ccs:book', event => prepareBooking(event.detail));
  $$('[data-select-vehicle]').forEach(button => button.addEventListener('click', () => prepareBooking({ vehicle: button.dataset.selectVehicle })));
  form.addEventListener('input', () => { result.hidden = true; });
  form.addEventListener('change', () => { result.hidden = true; });
  form.addEventListener('submit', event => {
    event.preventDefault();
    date.min = today();
    if (!form.reportValidity()) return;
    const data = new FormData(form);
    const value = name => String(data.get(name) || '').trim();
    if (value('name').length < 2) { const name = form.elements.namedItem('name'); name.setCustomValidity('Please enter your name.'); name.reportValidity(); name.addEventListener('input', () => name.setCustomValidity(''), { once: true }); return; }
    const lines = [
      'Hello CCS Solutions, I would like to request an inspection.', '',
      `Name: ${value('name')}`, `Contact: ${value('phone')}`,
      `Vehicle: ${value('vehicle')}`,
      ...(value('chassis') ? [`Year / chassis: ${value('chassis')}`] : []),
      `Service: ${value('service')}`,
      ...(value('date') ? [`Preferred date: ${value('date')} (please confirm availability)`] : []),
      ...(value('message') ? ['', `Symptoms / details: ${value('message')}`] : []),
      '', 'Please confirm an available appointment and the next steps.'
    ];
    const message = lines.join('\n');
    $('#inspection-message').textContent = message;
    $('#inspection-whatsapp').href = `https://wa.me/94717937011?text=${encodeURIComponent(message)}`;
    $('#inspection-email').href = `mailto:ccssolutionshybrid@gmail.com?subject=${encodeURIComponent('Inspection request — ' + value('vehicle'))}&body=${encodeURIComponent(message)}`;
    const emailFirst = value('channel') === 'email';
    $('#inspection-whatsapp').classList.toggle('is-secondary', emailFirst);
    $('#inspection-email').classList.toggle('is-secondary', !emailFirst);
    $('#inspection-whatsapp').style.order = emailFirst ? '2' : '1';
    $('#inspection-email').style.order = emailFirst ? '1' : '2';
    result.querySelector('[data-copy-request]').textContent = 'Copy request';
    result.hidden = false;
    result.scrollIntoView({ behavior: motion.matches ? 'instant' : 'smooth', block: 'nearest' });
  });

  const selectors = {
    curtain: '.section-top, .section-heading, .service-card, .expertise-note',
    journey: '.workflow-heading, .workflow-step',
    split: '.section-top, .battery-copy, .battery-visual',
    garage: '.vehicle-heading, .catalog-search-row, .catalog-toolbar, .catalog-grid, .catalog-more',
    rise: '.faq-intro, .faq-list',
    booking: '.inspection-intro, .inspection-form-panel',
    sweep: '.contact-heading, .contact-arrow, .contact-details',
    brands: '.brands-heading, .brand-mark',
    repairs: '.repair-heading, .repair-card, .repair-note',
    reviews: '.reviews-heading, .google-rating, .google-review, .reviews-note',
    map: '.location-heading, .location-frame, .location-bottom',
    owner: '.owner-portrait, .owner-copy'
  };
  let observer;
  function setupMotion() {
    observer?.disconnect();
    document.documentElement.classList.toggle('motion-ready', !motion.matches);
    if (motion.matches || !('IntersectionObserver' in window)) return;
    observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('motion-visible'); observer.unobserve(entry.target); }
    }), { threshold: .07, rootMargin: '0px 0px -4% 0px' });
    $$('[data-section-motion]').forEach(section => {
      [...section.querySelectorAll(selectors[section.dataset.sectionMotion])].forEach((item, index) => {
        item.classList.add('motion-item');
        item.style.setProperty('--motion-delay', `${Math.min(index % 3, 2) * 85}ms`);
        observer.observe(item);
      });
    });
  }
  motion.addEventListener('change', setupMotion);
  setupMotion();
})();
