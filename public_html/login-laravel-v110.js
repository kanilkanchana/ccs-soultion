(() => {
'use strict';
const button=document.getElementById('toggle-password'), password=document.getElementById('password');
button?.addEventListener('click',()=>{const show=password.type==='password';password.type=show?'text':'password';button.textContent=show?'Hide':'Show';button.setAttribute('aria-pressed',String(show));button.setAttribute('aria-label',show?'Hide password':'Show password');});
const slides=[...document.querySelectorAll('[data-login-slide]')];let current=0;
if(slides.length>1 && !matchMedia('(prefers-reduced-motion: reduce)').matches)setInterval(()=>{if(document.hidden)return;slides[current].classList.remove('is-active');current=(current+1)%slides.length;slides[current].classList.add('is-active');},6500);
})();
