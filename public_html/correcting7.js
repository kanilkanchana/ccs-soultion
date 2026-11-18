(() => {
  'use strict';
  const dialog = document.querySelector('#company-artwork-dialog');
  if (!dialog) return;
  const image = document.querySelector('#artwork-dialog-image');
  const title = document.querySelector('#artwork-dialog-title');
  const source = document.querySelector('#artwork-dialog-source');
  document.querySelectorAll('[data-artwork]').forEach(button => {
    button.addEventListener('click', () => {
      const card = button.closest('.company-artwork-card');
      const original = card.querySelector('img');
      const link = card.querySelector('.artwork-source');
      image.src = original.getAttribute('src'); image.alt = original.alt;
      title.textContent = card.querySelector('h3').textContent;
      source.href = link.href;
      source.textContent = link.textContent;
      if (link.target === '_blank') {source.target = '_blank'; source.rel = 'noopener noreferrer';}
      else {source.removeAttribute('target'); source.removeAttribute('rel');}
      dialog.showModal();
    });
  });
  document.querySelector('#artwork-dialog-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const box = dialog.getBoundingClientRect();
    if (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom) dialog.close();
  });
})();
