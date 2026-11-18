(() => {
  const select = document.getElementById('inspection-vehicle');
  if (!select || !Array.isArray(window.CCS_VEHICLES)) return;
  const selected = select.value;
  select.replaceChildren(new Option('Select your vehicle', ''));
  window.CCS_VEHICLES.forEach(vehicle => { const value = `${vehicle.make} ${vehicle.model} — ${vehicle.code}`; select.add(new Option(value, value)); });
  select.add(new Option('Other hybrid / not listed', 'Other hybrid / not listed'));
  if (selected && ![...select.options].some(option => option.value === selected)) select.add(new Option(selected,selected));
  select.value = selected;
})();
