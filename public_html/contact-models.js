(() => {
  'use strict';
  const select = document.getElementById('inspection-vehicle');
  const filter = document.getElementById('inspection-model-filter');
  const status = document.getElementById('inspection-model-status');
  const choice = document.getElementById('inspection-model-choice');
  if (!select || !filter || !window.CCS_MODEL_MATCH) return;

  const grouped = new Map();
  for (const vehicle of window.CCS_VEHICLES || []) {
    const label = `${vehicle.make} ${vehicle.model} — ${vehicle.code}`;
    const values = grouped.get(vehicle.make) || new Set();
    values.add(label);
    for (const variant of vehicle.variants || []) values.add(variant);
    grouped.set(vehicle.make, values);
  }
  const groups = [...grouped].map(([label, values]) => ({label, values: [...values]}));
  const other = 'Other hybrid / not listed';

  function showChoice() {
    choice.textContent = select.value;
    choice.hidden = !select.value;
  }

  function render(value) {
    const fragment = document.createDocumentFragment();
    fragment.append(new Option('Select your hybrid', ''));
    let count = 0;
    let found = false;
    for (const group of groups) {
      const values = group.values.filter(item => window.CCS_MODEL_MATCH(item, filter.value));
      if (!values.length) continue;
      const optgroup = document.createElement('optgroup');
      optgroup.label = group.label;
      for (const item of values) optgroup.append(new Option(item, item));
      fragment.append(optgroup);
      count += values.length;
      found ||= values.includes(value);
    }
    if (value && value !== other && !found) fragment.append(new Option(value, value));
    fragment.append(new Option(other, other));
    select.replaceChildren(fragment);
    select.value = value;
    status.textContent = count
      ? `${count} ${filter.value.trim() ? 'matching' : 'available'} model and variant choices`
      : 'No matching model. Try another search or choose Other hybrid.';
    showChoice();
  }

  filter.addEventListener('input', () => render(select.value));
  filter.addEventListener('keydown', event => {
    if (event.key === 'Enter') { event.preventDefault(); select.focus(); }
  });
  select.addEventListener('change', showChoice);
  select.addEventListener('ccs:inspection-vehicle', event => {
    filter.value = '';
    render(event.detail);
  });
  render(select.value);
})();
