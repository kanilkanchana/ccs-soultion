(() => {
  'use strict';
  const $ = selector => document.querySelector(selector);
  const $$ = selector => [...document.querySelectorAll(selector)];
  const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const motionDisabled = () => reduced.matches || document.documentElement.classList.contains('motion-off');
  const experience = $('#experience');
  const screen = $('#film-screen');
  const film = $('#scroll-film');
  const media = $('#film-media');
  const intro = $('#intro-video');
  const loader = $('#site-loader');
  const copies = $$('[data-chapter-copy]');
  const chapters = $$('[data-film-chapter]');
  const seconds = value => `00:${String(Math.floor(Math.max(0, value))).padStart(2, '0')}`;
  let activeChapter = -1;
  let desiredTime = 0;
  let framePending = false;
  let videoFailed = false;
  let manualChapter = 0;

  function showChapter(index, progress) {
    const next = clamp(index, 0, 5);
    if (next !== activeChapter) {
      activeChapter = next;
      copies.forEach((copy, i) => {
        copy.classList.toggle('is-active', i === next);
        copy.setAttribute('aria-hidden', String(i !== next));
        copy.inert = i !== next;
      });
      chapters.forEach((button, i) => i === next ? button.setAttribute('aria-current', 'step') : button.removeAttribute('aria-current'));
      $('#film-serial').textContent = String(next + 1).padStart(2, '0');
      $('#film-scroll-hint').textContent = next === 5 ? '↓ SCROLL TO OUR SERVICES' : '↓ SCROLL TO EXPLORE';
      $('#film-fallback').src = `assets/experience/chapter-${next + 1}.jpg`;
    }
    chapters.forEach((button, i) => button.style.setProperty('--chapter-progress', `${clamp(progress * 6 - i) * 100}%`));
  }

  function seekLatestFrame() {
    if (videoFailed || film.readyState < 1 || film.seeking) return;
    const target = clamp(desiredTime, 0, Math.max(0, (film.duration || 30) - 0.045));
    if (Math.abs(film.currentTime - target) < 0.045) return;
    try { film.currentTime = target; } catch { /* The chapter still remains visible while media loads. */ }
  }

  function updateWorkflow() {
    const list = $('#workflow-list');
    const bounds = list.getBoundingClientRect();
    const progress = clamp((innerHeight * 0.62 - bounds.top) / Math.max(1, bounds.height));
    $('#workflow-fill').style.height = `${progress * 100}%`;
    const rows = $$('[data-workflow-step]');
    let current = -1;
    rows.forEach((row, i) => {
      const passed = row.getBoundingClientRect().top < innerHeight * 0.62;
      row.classList.toggle('is-passed', passed);
      if (passed) current = i;
    });
    rows.forEach((row, i) => row.classList.toggle('is-current', i === current));
  }

  function render() {
    framePending = false;
    updateWorkflow();
    const bounds = experience.getBoundingClientRect();
    const travel = Math.max(1, experience.offsetHeight - screen.offsetHeight);
    const progress = motionDisabled() ? (manualChapter + 0.025) / 6 : clamp(-bounds.top / travel);
    desiredTime = progress * Math.max(0, (film.duration || 30) - 0.045);
    showChapter(Math.min(5, Math.floor(progress * 6)), progress);
    if (bounds.top < innerHeight * 1.5 && bounds.bottom > -innerHeight) seekLatestFrame();
  }

  function requestRender() {
    if (!framePending) { framePending = true; requestAnimationFrame(render); }
  }

  function goToChapter(index) {
    manualChapter = index;
    const origin = experience.getBoundingClientRect().top + scrollY;
    const travel = Math.max(0, experience.offsetHeight - screen.offsetHeight);
    const progress = (index + 0.025) / 6;
    if (motionDisabled()) {
      desiredTime = progress * Math.max(0, (film.duration || 30) - 0.045);
      showChapter(index, progress);
      seekLatestFrame();
    } else {
      // Direct chapter selection avoids animating through several unrelated chapters.
      window.scrollTo({ top: origin + travel * progress, behavior: 'instant' });
      requestRender();
    }
  }

  chapters.forEach(button => button.addEventListener('click', () => goToChapter(Number(button.dataset.filmChapter))));
  film.addEventListener('loadedmetadata', () => { film.pause(); requestRender(); });
  film.addEventListener('loadeddata', () => { media.classList.add('has-frame'); $('#film-media-status').textContent = ''; requestRender(); });
  film.addEventListener('seeked', () => {
    if (film.readyState >= 2) media.classList.add('has-frame');
    if (Math.abs(film.currentTime - desiredTime) > 0.07) requestAnimationFrame(seekLatestFrame);
  });
  film.addEventListener('error', () => { videoFailed = true; media.classList.remove('has-frame'); film.pause(); $('#film-media-status').textContent = 'Explore the six chapters using the photographs.'; });
  film.querySelectorAll('source').forEach(source => source.addEventListener('error', () => { if (film.networkState === 3) film.dispatchEvent(new Event('error')); }));
  window.addEventListener('scroll', requestRender, { passive: true });
  window.addEventListener('resize', requestRender, { passive: true });
  document.addEventListener('visibilitychange', () => { if (document.hidden) { intro.pause(); film.pause(); } });
  reduced.addEventListener('change', () => { if (motionDisabled()) { intro.pause(); film.pause(); } requestRender(); });

  document.addEventListener('ccs:motion', () => { if (motionDisabled()) { intro.pause(); film.pause(); } requestRender(); });

  let introWasLeft = Boolean(location.hash && location.hash !== '#home');
  let introLaunched = false;
  const leaveIntro = () => { introWasLeft = true; };
  window.addEventListener('wheel', leaveIntro, { passive: true, once: true });
  window.addEventListener('touchmove', leaveIntro, { passive: true, once: true });
  $$('.site-header a, .site-header button, .intro-enter-link').forEach(control => control.addEventListener('click', leaveIntro));
  function enterExperience() {
    introWasLeft = true;
    intro.pause();
    window.scrollTo({ top: experience.getBoundingClientRect().top + scrollY, behavior: 'instant' });
    requestRender();
  }
  async function launchIntro(replay = false) {
    if (!replay && (introLaunched || introWasLeft || motionDisabled())) return;
    introLaunched = true;
    if (replay) { intro.currentTime = 0; introWasLeft = false; }
    try { await intro.play(); } catch { /* The poster and native scrolling remain available. */ }
  }
  intro.addEventListener('timeupdate', () => {
    const rail = $('#intro-progress');
    if (rail) rail.style.width = `${clamp(intro.currentTime / (intro.duration || 7.1)) * 100}%`;
  });
  intro.addEventListener('ended', () => {
    if (!introWasLeft && scrollY < innerHeight * 0.25 && !document.querySelector('dialog[open]')) enterExperience();
  });
  new IntersectionObserver(entries => {
    if (!entries[0].isIntersecting && introLaunched) { intro.pause(); introWasLeft = true; }
  }, { threshold: 0.1 }).observe($('#home'));

  // The counter is bounded: delayed or failed media never blocks the homepage.
  let loaderFinished = false;
  let counter = 0;
  let loaderFrame;
  let started = performance.now();
  const pageRegions = $$('main, .site-header, .site-footer, #mobile-nav, #quick-contact');

  function finishLoader(skip = false) {
    if (loaderFinished) return;
    loaderFinished = true;
    cancelAnimationFrame(loaderFrame);
    $('#loader-number').textContent = '100';
    $('#loader-progress').setAttribute('aria-valuenow', '100');
    $('#loader-progress > span').style.width = '100%';
    $('#loader-status').textContent = 'READY. LET’S GET YOU MOVING.';
    loader.classList.add('is-finished');
    document.body.classList.remove('is-loading');
    pageRegions.forEach(region => { region.inert = false; });
    if (!skip && location.hash && location.hash !== '#home') {
      const target = document.getElementById(location.hash.slice(1));
      if (target) requestAnimationFrame(() => target.scrollIntoView({ behavior: 'instant', block: 'start' }));
    }
    setTimeout(() => { loader.hidden = true; }, motionDisabled() ? 0 : 680);
    if (skip) { enterExperience(); $('.film-logo').focus({ preventScroll: true }); }
    else launchIntro();
    requestRender();
  }
  function tickLoader(now) {
    if (loaderFinished) return;
    const elapsed = now - started;
    const ready = intro.readyState >= 2 && $(' .loader-center img').complete && document.fonts.status === 'loaded';
    const minimum = motionDisabled() ? 80 : 1800;
    const target = ready && elapsed > minimum ? 100 : Math.min(92, elapsed / 1900 * 92);
    counter += (target - counter) * 0.15;
    $('#loader-number').textContent = String(Math.floor(clamp(counter, 0, 100))).padStart(3, '0');
    $('#loader-elapsed').textContent = seconds(elapsed / 1000);
    $('#loader-progress').setAttribute('aria-valuenow', String(Math.floor(counter)));
    $('#loader-progress > span').style.width = `${counter}%`;
    loader.style.setProperty('--load-angle', `${counter * 3.6}deg`);
    const stage = counter < 34 ? 0 : counter < 72 ? 1 : 2;
    $$('[data-load-stage]').forEach((item,index) => item.classList.toggle('is-active',index <= stage));
    if (counter < 99) $('#loader-status').textContent = ['SETTING THE DETAILS IN PLACE','BRINGING THE EXPERIENCE TO LIFE','ALMOST READY. LET’S GET YOU MOVING.'][stage];
    if ((counter > 99.4 && elapsed > minimum) || elapsed > (motionDisabled() ? 300 : 4200)) { finishLoader(); return; }
    loaderFrame = requestAnimationFrame(tickLoader);
  }
  $('#loader-skip').addEventListener('click', () => finishLoader(true));
  const beginLoader = () => {
    started = performance.now();
    introWasLeft ||= Boolean(location.hash && location.hash !== '#home');
    loader.hidden = false;
    pageRegions.forEach(region => { region.inert = true; });
    document.body.classList.add('is-loading');
    loaderFrame = requestAnimationFrame(tickLoader);
    setTimeout(() => finishLoader(), motionDisabled() ? 350 : 4600);
  };
  Promise.resolve(window.ccsWelcomeGate).then(beginLoader);
  showChapter(0, 0);
  requestRender();
})();
