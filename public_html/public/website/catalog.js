(() => {
  'use strict';
  const grid = document.getElementById('catalog-grid');
  const vehicles = window.CCS_VEHICLES;
  if (!grid || !Array.isArray(vehicles)) return;
  const home = grid.dataset.mode === 'home';
  const filterList = document.getElementById('model-filters');
  const search = document.getElementById('model-search');
  const count = document.getElementById('catalog-count');
  const empty = document.getElementById('catalog-empty');
  const more = document.getElementById('catalog-browse');
  const makes = [...new Set(vehicles.map(vehicle => vehicle.make))];
  let selected = new URLSearchParams(location.search).get('make') || 'all';
  if (!makes.includes(selected)) selected = 'all';
  const text = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
  function render() {
    const query = search.value.toLocaleLowerCase().trim();
    const matches = vehicles.filter(vehicle => (selected === 'all' || selected === vehicle.make) && `${vehicle.make} ${vehicle.model} ${vehicle.code} ${vehicle.type}`.toLocaleLowerCase().includes(query));
    const shown = home ? matches.slice(0,6) : matches;
    grid.innerHTML = shown.map(vehicle => {
      const label = `${vehicle.make} ${vehicle.model} — ${vehicle.code}`;
      const action = home ? `<button type="button" data-catalog-inspect="${text(label)}">Ask about this hybrid <span aria-hidden="true">↗</span></button>` : `<a href="contact.html?vehicle=${encodeURIComponent(label)}#inspection">Ask about this hybrid <span aria-hidden="true">↗</span></a>`;
      return `<article class="catalog-card"><a class="catalog-card-photo" href="assets/vehicles/${vehicle.id}.png" target="_blank" rel="noopener" aria-label="View ${text(label)} photo"><img src="assets/vehicles/${vehicle.id}.webp" alt="Representative ${text(vehicle.make)} ${text(vehicle.model)} ${text(vehicle.code)} hybrid vehicle" width="900" height="600" loading="lazy"></a><div class="catalog-card-copy"><span class="catalog-make">${text(vehicle.make.toUpperCase())} / ${text(vehicle.type.toUpperCase())}</span><h3>${text(vehicle.model.toUpperCase())}</h3><span class="catalog-code">${text(vehicle.code)}</span>${action}</div></article>`;
    }).join('');
    filterList.querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.catalogMake === selected)));
    count.textContent = `${home && matches.length > 6 ? `SHOWING 06 OF ${matches.length}` : `${String(matches.length).padStart(2,'0')} MODELS`} / ${selected === 'all' ? `${String(makes.length).padStart(2,'0')} MAKES` : selected.toUpperCase()}`;
    empty.hidden = matches.length > 0;
    if (more) more.href = `models.html${selected === 'all' ? '' : `?make=${encodeURIComponent(selected)}`}`;
  }
  filterList.innerHTML = ['all',...makes].map(make => `<button type="button" data-catalog-make="${text(make)}" aria-pressed="${selected === make}">${make === 'all' ? 'All hybrids' : text(make)}</button>`).join('');
  filterList.addEventListener('click', event => { const button = event.target.closest('[data-catalog-make]'); if (button) { selected = button.dataset.catalogMake; render(); } });
  document.addEventListener('ccs:filter-make', event => { if (makes.includes(event.detail)) { selected = event.detail; search.value = ''; render(); } });
  search.addEventListener('input', render);
  grid.addEventListener('click', event => { const button = event.target.closest('[data-catalog-inspect]'); if (button) document.dispatchEvent(new CustomEvent('ccs:book', { detail: { vehicle: button.dataset.catalogInspect } })); });
  render();
})();
