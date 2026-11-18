(() => {
  'use strict';
  const menu = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#mobile-nav');
  function closeMenu(returnFocus = false) {
    nav.hidden = true;
    menu.setAttribute('aria-expanded','false');
    menu.setAttribute('aria-label','මෙනුව විවෘත කරන්න');
    document.body.classList.remove('menu-open');
    if (returnFocus) menu.focus();
  }
  menu.addEventListener('click', () => {
    if (!nav.hidden) { closeMenu(); return; }
    nav.hidden = false;
    menu.setAttribute('aria-expanded','true');
    menu.setAttribute('aria-label','මෙනුව වසන්න');
    document.body.classList.add('menu-open');
  });
  nav.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>closeMenu()));
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!nav.hidden)closeMenu(true);});
  matchMedia('(min-width:1201px)').addEventListener('change',e=>{if(e.matches)closeMenu();});
  document.querySelector('#si-year').textContent=String(new Date().getFullYear());
  const message='ආයුබෝවන් CCS Solutions, මගේ Hybrid රථය පරීක්ෂා කර ගැනීමට අවශ්‍යයි.\n\nරථ මාදිලිය:\nගැටලුව:\nපැමිණීමට කැමති දිනය:';
  document.querySelector('#si-whatsapp').href='https://wa.me/94717937011?text='+encodeURIComponent(message);
  const reduced=matchMedia('(prefers-reduced-motion:reduce)');
  let motionOff=false;
  try{motionOff=localStorage.getItem('ccs-motion-off')==='yes';}catch{}
  if(motionOff)document.documentElement.classList.add('motion-off');
  if('IntersectionObserver'in window&&!reduced.matches&&!motionOff){
    document.body.classList.add('si-motion-ready');
    const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('is-visible');observer.unobserve(entry.target);}}),{threshold:.07});
    document.querySelectorAll('.si-reveal').forEach(e=>observer.observe(e));
  }
})();
