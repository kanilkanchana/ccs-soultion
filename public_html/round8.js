(() => {
  'use strict';
  // Enhance the visual hierarchy while retaining the actual working source links.
  document.querySelectorAll('.site-footer').forEach(footer => {
    const links = footer.querySelector('.social-links');
    if (!links || footer.querySelector('.social-invite')) return;
    const heading = document.createElement('div');
    heading.className = 'social-invite';
    const lhs = document.createElement('div');
    const kicker = document.createElement('p'); kicker.className = 'social-overline'; kicker.textContent = 'STAY IN THE KNOW / CCS SOLUTIONS';
    const h = document.createElement('h2'); h.innerHTML = 'MORE THAN A WORKSHOP.<br><em>STAY CONNECTED.</em>';
    lhs.append(kicker,h);
    const sub = document.createElement('p'); sub.textContent = 'See workshop moments, practical hybrid care tips and the latest updates from our team.';
    heading.append(lhs,sub); links.before(heading);
    links.querySelectorAll('.social-link').forEach(link => {
      const name = link.classList.contains('social-facebook') ? 'Facebook' : 'TikTok';
      const small = link.querySelector('small');
      if (small) small.textContent = name === 'Facebook' ? 'Visit our official Facebook page' : 'Watch our latest workshop videos';
    });
  });
  const icon = 'assets/assistant/ccs-robot.svg';
  document.querySelectorAll('.assistant-toggle,.assistant-avatar').forEach(el => {
    const svg = el.querySelector('svg');
    if (svg) { const img = document.createElement('img'); img.src = icon; img.alt = ''; img.className='assistant-robot'; svg.replaceWith(img); }
  });
})();