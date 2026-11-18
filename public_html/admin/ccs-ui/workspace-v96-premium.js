
(()=>{
  const toggle = document.querySelector('[data-theme-toggle]');
  if(toggle){
    toggle.addEventListener('click', async (e)=>{
      e.preventDefault();
      const root = document.documentElement;
      const current = root.dataset.ccsTheme || 'dark';
      const lightSet = new Set(['light','skyline','desert','aqua']);
      const next = lightSet.has(current) ? 'dark' : 'light';
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      toggle.disabled = true;
      try{
        const res = await fetch(toggle.dataset.endpoint, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest','Accept':'text/html,application/xhtml+xml'}, body:new URLSearchParams({theme:next})});
        if(!res.ok) throw new Error('Theme update failed');
        root.dataset.ccsTheme = next;
        const label = toggle.querySelector('[data-theme-label]');
        if(label) label.textContent = lightSet.has(next) ? 'Light' : 'Dark';
      }catch(err){
        alert('Could not switch theme. Please try again.');
      }finally{ toggle.disabled = false; }
    });
  }
})();
