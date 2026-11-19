(() => {
  'use strict';
  const grid = document.getElementById('catalog-grid');
  const vehicles = window.CCS_VEHICLES;
  if (!grid || !Array.isArray(vehicles)) return;

  const home = grid.dataset.mode === 'home';
  const filters = document.getElementById('model-filters');
  const search = document.getElementById('model-search');
  const count = document.getElementById('catalog-count');
  const empty = document.getElementById('catalog-empty');
  const browse = document.getElementById('catalog-browse');
  const loadMore = document.getElementById('catalog-load-more');
  const makes = [...new Set(vehicles.map(vehicle => vehicle.make))];
  const evidence = { L: 'Sri Lanka evidence', O: 'Import / offer', G: 'Global reference' };
  const escape = value => String(value).replace(/[&<>"']/g, char => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
  }[char]));

  let make = new URLSearchParams(location.search).get('make');
  if (!makes.includes(make)) make = 'all';
  let matches = [];
  let shown = 0;
  const batch = home ? 6 : 24;

  // These catalogue entries have no local photo yet. Use current model photos
  // from the official Toyota source pages (with local white-background fallbacks).
  const selectedPhotos = [
    { match: /prius\s*(phv|prime)/i, image: 'https://www.tochigitoyota.com/file/cms/-1/9496/zvw52-ahxgb_0_30.png', fallback: 'prius.webp' },
    { match: /probox/i, image: 'https://global.toyota/pages/news/images/2018/11/19/1330/rendition/20181119_01_12_W610_H407.jpg', fallback: 'wagon-hybrid.webp' },
    { match: /corolla\s+sedan/i, image: 'https://cdn2.modera.org/resized/1152x/i/toyota-rsdm/dpackage/corolla_2/exteriors/040/b70/g11/1024x768_20.png', fallback: 'axio.webp' }
  ];

  function card(vehicle) {
    const label = `${vehicle.make} ${vehicle.model} — ${vehicle.code}`;
    const selectedPhoto = selectedPhotos.find(photo => photo.match.test(vehicle.model || ''));
    const fallbackImage = selectedPhoto
      ? `assets/vehicles/${selectedPhoto.fallback}`
      : vehicle.photo ? `assets/vehicles/${encodeURIComponent(vehicle.photo)}` : '';
    const image = selectedPhoto?.image || vehicle.image || fallbackImage;
    const fullImage = vehicle.full_image || image;
    const visual = image ? `
      <a class="catalog-card-photo" href="${escape(fullImage)}" target="_blank" rel="noopener">
        <img src="${escape(image)}" data-catalog-fallback="${escape(fallbackImage)}" alt="Representative photo of ${escape(vehicle.alt || vehicle.photoLabel || label)}" loading="lazy" decoding="async">
      </a>` : `
      <div class="catalog-card-photo catalog-photo-unavailable">
        <svg viewBox="0 0 100 48" aria-hidden="true"><path d="M10 35v-9l10-3 12-15h34l12 15 12 4v8h-9m-14 0H34m-14 0H10M20 23h58"/><circle cx="27" cy="35" r="7"/><circle cx="74" cy="35" r="7"/></svg>
        <span>Photo unavailable</span>
      </div>`;
    const action = home
      ? `<button type="button" data-catalog-inspect="${escape(label)}">Ask about this hybrid <span aria-hidden="true">↗</span></button>`
      : `<a href="contact.html?vehicle=${encodeURIComponent(label)}#inspection">Select for inspection <span aria-hidden="true">↗</span></a>`;
    const note = !home && vehicle.note
      ? `<details class="catalog-variant-note"><summary>Variant notes</summary><p>${escape(vehicle.note)}</p></details>`
      : '';
    const badge = !home && evidence[vehicle.evidence]
      ? `<span class="catalog-evidence" data-evidence="${vehicle.evidence}">${evidence[vehicle.evidence]}</span>`
      : '';
    return `<article class="catalog-card">
      ${visual}
      <div class="catalog-card-copy">
        <div class="catalog-card-meta"><span class="catalog-make">${escape(vehicle.make)} / ${escape(vehicle.type)}</span>${badge}</div>
        <h3>${escape(vehicle.model)}</h3>
        <span class="catalog-code">${escape(vehicle.code)}</span>
        ${note}
        <div class="catalog-card-action">${action}</div>
      </div>
    </article>`;
  }

  function appendBatch() {
    const first = shown;
    shown = Math.min(shown + batch, matches.length);
    grid.insertAdjacentHTML('beforeend', matches.slice(first, shown).map(card).join(''));
    const results = shown < matches.length ? `Showing ${shown} of ${matches.length}` : `${matches.length} models`;
    count.textContent = `${results} / ${make === 'all' ? `${makes.length} makes` : make}`;
    if (loadMore) {
      loadMore.hidden = shown === matches.length;
      loadMore.textContent = `Show ${Math.min(batch, matches.length - shown)} more models`;
    }
  }

  function render() {
    matches = vehicles.filter(vehicle => (make === 'all' || vehicle.make === make) &&
      window.CCS_MODEL_MATCH([vehicle.make, vehicle.model, vehicle.code, vehicle.type, ...(vehicle.variants || [])].join(' '), search.value));
    shown = 0;
    grid.replaceChildren();
    appendBatch();
    empty.hidden = matches.length > 0;
    for (const button of filters.querySelectorAll('button')) {
      button.setAttribute('aria-pressed', String(button.dataset.catalogMake === make));
    }
    if (browse) browse.href = `models.html${make === 'all' ? '' : '?make=' + encodeURIComponent(make)}`;
  }

  filters.innerHTML = ['all', ...makes].map(name =>
    `<button type="button" data-catalog-make="${escape(name)}">${name === 'all' ? 'All hybrids' : escape(name)}</button>`
  ).join('');
  filters.addEventListener('click', event => {
    const button = event.target.closest('[data-catalog-make]');
    if (button) { make = button.dataset.catalogMake; render(); }
  });
  document.addEventListener('ccs:filter-make', event => {
    if (makes.includes(event.detail)) { make = event.detail; search.value = ''; render(); }
  });
  search.addEventListener('input', render);
  loadMore?.addEventListener('click', () => {
    const first = shown;
    appendBatch();
    grid.children[first]?.querySelector('a, button')?.focus();
  });
  // Try the bundled white-background reference when an admin override is stale.
  // If neither image exists, replace the link with a clear accessible fallback.
  grid.addEventListener('error', event => {
    const image = event.target;
    if (!(image instanceof HTMLImageElement) || !image.hasAttribute('data-catalog-fallback')) return;
    const fallback = image.dataset.catalogFallback;
    if (fallback && image.dataset.fallbackTried !== 'true' &&
        image.src !== new URL(fallback, document.baseURI).href) {
      image.dataset.fallbackTried = 'true';
      image.src = fallback;
      return;
    }
    const photo = image.closest('.catalog-card-photo');
    if (!photo) return;
    const placeholder = document.createElement('div');
    placeholder.className = 'catalog-card-photo catalog-photo-unavailable';
    placeholder.setAttribute('role', 'img');
    placeholder.setAttribute('aria-label', 'Photo unavailable');
    const message = document.createElement('span');
    message.textContent = 'Photo unavailable';
    placeholder.append(message);
    photo.replaceWith(placeholder);
  }, true);

  grid.addEventListener('click', event => {
    const button = event.target.closest('[data-catalog-inspect]');
    if (button) document.dispatchEvent(new CustomEvent('ccs:book', { detail: { vehicle: button.dataset.catalogInspect } }));
  });
  render();
})();
