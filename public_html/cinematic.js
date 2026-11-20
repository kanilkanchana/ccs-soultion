(() => {
  'use strict';
  const $ = selector => document.querySelector(selector);
  const $$ = selector => [...document.querySelectorAll(selector)];
  const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));
  const reduced = matchMedia('(prefers-reduced-motion: reduce)');
  const mobile = matchMedia('(max-width: 700px)');
  const motionDisabled = () => reduced.matches || document.documentElement.classList.contains('motion-off');
  const experience = $('#experience');
  const screen = $('#film-screen');
  const film = $('#scroll-film'); // Keep desktop scroll-controlled player intact.
  // Independent mobile playback prevents desktop scroll-seeking and old cached
  // scroll handlers from jumping the phone film straight to chapter 06.
  const mobileFilm = $('#mobile-sequential-film');
  mobileFilm.loop = false;
  if (mobile.matches) film.pause();
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
  let mobileVideoFailed = false;
  let previousMotionMode;
  const header = $('.site-header');
  const fallback = $('#film-fallback');
  const workflowList = $('#workflow-list');
  const workflowRows = $$('[data-workflow-step]');
  const mobileChapterProgress = [];
  let mobileMetrics, mobileMetricsDirty = true, mobileFilmVisible = false, mobileWorkflowVisible = false;
  let mobilePlayPending = false, mobileAutoplayBlocked = false, mobileFilmFinished = false, mobileFilmStarted = false;
  const mobilePlayButton = $('#mobile-film-play');
  function updateMobileFilmUI() {
    if (!mobile.matches || motionDisabled()) return;
    // Always display scenes 01 → 06 according to the actual playing VIDEO CLOCK.
    // Scrolling can neither fast-forward the film nor select a later chapter.
    const progress = mobileFilmFinished ? 1 : clamp(mobileFilm.currentTime / (mobileFilm.duration || 30));
    showChapter(Math.min(5, Math.floor(progress * 6)), progress);
  }
  function syncMobileFilmPlayback() {
    if (!mobile.matches) return;
    const shouldPlay = mobileFilmVisible && loader.hidden && !document.querySelector('dialog[open]') && !mobileFilmFinished && !motionDisabled() && !document.hidden && !mobileVideoFailed;
    if (!shouldPlay) {
      if (!mobileFilm.paused) mobileFilm.pause();
      return;
    }
    if (!mobileFilm.paused || mobilePlayPending || mobileAutoplayBlocked) return;
    mobilePlayPending = true;
    // Avoid browser history restoring the mobile film near the final scene.
    if (!mobileFilmStarted && mobileFilm.currentTime > 0.05) {
      try { mobileFilm.currentTime = 0; } catch { /* The video has not loaded yet. */ }
    }
    mobileFilm.play().then(() => {
      mobileFilmStarted = true;
      mobileAutoplayBlocked = false;
      if (mobilePlayButton) mobilePlayButton.hidden = true;
      $('#film-media-status').textContent = '';
    }).catch(() => {
      // iOS Low Power Mode and some browsers require a user gesture.
      if (mobile.matches && mobileFilmVisible && !document.hidden && !motionDisabled() && !mobileVideoFailed) {
        mobileAutoplayBlocked = true;
        if (mobilePlayButton) mobilePlayButton.hidden = false;
        $('#film-media-status').textContent = 'Tap play to watch the film.';
      }
    }).finally(() => { mobilePlayPending = false; });
  }
  if (mobilePlayButton) mobilePlayButton.addEventListener('click', () => {
    if (!mobile.matches || mobileVideoFailed) return;
    // A replay is always explicitly requested: never restart/loop automatically.
    if (mobileFilmFinished) {
      mobileFilmFinished = false;
      mobileFilm.currentTime = 0;
      showChapter(0, 0);
      mobilePlayButton.textContent = '▶ Play video';
      mobilePlayButton.setAttribute('aria-label', 'Play the complete six-chapter video');
    }
    mobileAutoplayBlocked = false;
    mobilePlayButton.hidden = true;
    syncMobileFilmPlayback();
  });

  function measureMobile() {
    const bounds = experience.getBoundingClientRect();
    const height = experience.offsetHeight;
    mobileMetrics = {
      top: bounds.top + scrollY,
      height,
      header: header.offsetHeight,
      viewport: innerHeight
    };
    mobileMetricsDirty = false;
  }

  function readMobileWorkflow() {
    if (!mobileWorkflowVisible) return null;
    const bounds = workflowList.getBoundingClientRect();
    const passed = workflowRows.map(row => row.getBoundingClientRect().top < innerHeight * .62);
    return { progress: clamp((innerHeight * .62 - bounds.top) / Math.max(1, bounds.height)), passed, current: passed.lastIndexOf(true) };
  }

  function renderMobile() {
    if (mobileMetricsDirty || !mobileMetrics) measureMobile();
    // Read visible geometry before writing any styles; the off-screen workflow costs no layout reads.
    const workflow = readMobileWorkflow();
    const boundsTop = mobileMetrics.top - scrollY;
    const boundsBottom = boundsTop + mobileMetrics.height;
    const staticMode = motionDisabled();
    const viewportOverlap = Math.min(boundsBottom, mobileMetrics.viewport) - Math.max(boundsTop, mobileMetrics.header);
    mobileFilmVisible = !staticMode && viewportOverlap > Math.min(screen.offsetHeight * .6, mobileMetrics.viewport * .6);
    if (workflow) {
      $('#workflow-fill').style.height = `${workflow.progress * 100}%`;
      workflowRows.forEach((row,i) => {
        row.classList.toggle('is-passed',workflow.passed[i]);
        row.classList.toggle('is-current',i === workflow.current);
      });
    }
    if (staticMode) {
      const current = Math.max(0, copies.findLastIndex(copy => copy.getBoundingClientRect().top < mobileMetrics.header + innerHeight * .45));
      const progress = (current + .025) / 6;
      showChapter(Math.min(5, Math.floor(progress * 6)), progress);
    } else {
      updateMobileFilmUI();
    }
    syncMobileFilmPlayback();
    if (boundsTop <= mobileMetrics.header && !intro.paused) intro.pause();
  }

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(entries => {
      mobileWorkflowVisible = entries[0].isIntersecting;
      if (mobile.matches) requestRender();
    },{rootMargin:'160px 0px'}).observe(workflowList);
  } else { mobileWorkflowVisible = true; }

  const invalidateMobile = () => { mobileMetricsDirty = true; requestRender(); };
  mobile.addEventListener('change', () => {
    // Reset mobile playback state when moving between phone and desktop sizes.
    film.pause();
    mobileFilm.pause();
    mobileFilmFinished = false;
    mobileFilmStarted = false;
    if (mobile.matches) {
      // The phone film has its OWN clock and begins from scene 01 even after resize.
      try { mobileFilm.currentTime = 0; } catch { /* Metadata might not be loaded yet. */ }
    }
    else if (mobilePlayButton) mobilePlayButton.hidden = true;
    mobileAutoplayBlocked = false;
    invalidateMobile();
  });
  document.fonts?.ready.then(invalidateMobile);

  function showChapter(index, progress) {
    const next = clamp(index, 0, 5);
    if (next !== activeChapter || previousMotionMode !== motionDisabled()) {
      previousMotionMode = motionDisabled();
      activeChapter = next;
      copies.forEach((copy, i) => {
        copy.classList.toggle('is-active', motionDisabled() || i === next);
        copy.setAttribute('aria-hidden', String(!motionDisabled() && i !== next));
        copy.inert = !motionDisabled() && i !== next;
      });
      chapters.forEach((button, i) => i === next ? button.setAttribute('aria-current', 'step') : button.removeAttribute('aria-current'));
      $('#film-serial').textContent = String(next + 1).padStart(2, '0');
      $('#film-scroll-hint').textContent = mobile.matches && !motionDisabled()
        ? (mobileFilmFinished ? 'FILM COMPLETE · SCROLL TO EXPLORE' : 'AUTO-PLAYING 6 CHAPTERS · NO SCROLL NEEDED')
        : next === 5 ? '↓ SCROLL TO OUR SERVICES' : '↓ SCROLL TO EXPLORE';
      if (!mobile.matches || mobileVideoFailed || !media.classList.contains('has-frame')) fallback.src = `assets/experience/chapter-${next + 1}.jpg`;
    }
    chapters.forEach((button, i) => {
      if (!mobile.matches) button.style.setProperty('--chapter-progress', `${clamp(progress * 6 - i) * 100}%`);
      else {
        const value = Math.round(clamp(progress * 6 - i) * 1000) / 1000;
        if (mobileChapterProgress[i] !== value) {
          mobileChapterProgress[i] = value;
          button.style.setProperty('--mobile-chapter-progress',value);
        }
      }
    });
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
    if (mobile.matches) { renderMobile(); return; }
    updateWorkflow();
    const bounds = experience.getBoundingClientRect();
    const travel = Math.max(1, experience.offsetHeight - screen.offsetHeight);
    const headerHeight = document.querySelector('.site-header').offsetHeight;
    const staticChapter = Math.max(0, copies.findLastIndex(copy => copy.getBoundingClientRect().top < headerHeight + innerHeight * .45));
    const progress = motionDisabled() ? (staticChapter + .025) / 6 : clamp((headerHeight - bounds.top) / travel);
    desiredTime = progress * Math.max(0, (film.duration || 30) - 0.045);
    showChapter(Math.min(5, Math.floor(progress * 6)), progress);
    if (bounds.top < innerHeight * 1.5 && bounds.bottom > -innerHeight) seekLatestFrame();
  }

  function requestRender() {
    if (!framePending) { framePending = true; requestAnimationFrame(render); }
  }

  film.addEventListener('loadedmetadata', () => { if (!mobile.matches) film.pause(); requestRender(); });
  // MOBILE ONLY: media playback drives the six chapter captions in order.
  mobileFilm.addEventListener('loadedmetadata', () => { requestRender(); });
  mobileFilm.addEventListener('ended', () => {
    if (!mobile.matches) return;
    // End on chapter 06. Do not jump back to 01 or advance the page.
    mobileFilmFinished = true;
    mobileAutoplayBlocked = false;
    updateMobileFilmUI();
    $('#film-scroll-hint').textContent = 'FILM COMPLETE · SCROLL TO EXPLORE';
    if (mobilePlayButton) {
      mobilePlayButton.textContent = '↻ Replay all six chapters';
      mobilePlayButton.setAttribute('aria-label', 'Replay the complete six-chapter video');
      mobilePlayButton.hidden = false;
    }
  });
  film.addEventListener('loadeddata', () => {
    if (!mobile.matches) { media.classList.add('has-frame'); $('#film-media-status').textContent = ''; requestRender(); }
  });
  mobileFilm.addEventListener('loadeddata', () => {
    if (mobile.matches) { media.classList.add('has-frame'); $('#film-media-status').textContent = ''; requestRender(); }
  });
  mobileFilm.addEventListener('timeupdate', updateMobileFilmUI);
  film.addEventListener('seeked', () => {
    if (mobile.matches) return;
    if (film.readyState >= 2) media.classList.add('has-frame');
    if (Math.abs(film.currentTime - desiredTime) > 0.07) requestAnimationFrame(seekLatestFrame);
  });
  film.addEventListener('error', () => {
    videoFailed = true;
    if (!mobile.matches) {
      fallback.src = `assets/experience/chapter-${Math.max(0,activeChapter) + 1}.jpg`;
      media.classList.remove('has-frame');
      film.pause();
      $('#film-media-status').textContent = 'Explore the six chapters using the photographs.';
    }
  });
  mobileFilm.addEventListener('error', () => {
    mobileVideoFailed = true;
    if (mobile.matches) {
      fallback.src = `assets/experience/chapter-${Math.max(0,activeChapter) + 1}.jpg`;
      media.classList.remove('has-frame');
      mobileFilm.pause();
      $('#film-media-status').textContent = 'Video unavailable. Please try refreshing the page.';
    }
  });
  film.querySelectorAll('source').forEach(source => source.addEventListener('error', () => { if (film.networkState === 3) film.dispatchEvent(new Event('error')); }));
  mobileFilm.querySelectorAll('source').forEach(source => source.addEventListener('error', () => { if (mobileFilm.networkState === 3) mobileFilm.dispatchEvent(new Event('error')); }));
  window.addEventListener('scroll', requestRender, { passive: true });
  window.addEventListener('resize', invalidateMobile, { passive: true });
  document.addEventListener('visibilitychange', () => { if (document.hidden) { intro.pause(); film.pause(); mobileFilm.pause(); } else if (mobile.matches) requestRender(); });
  reduced.addEventListener('change', () => { if (motionDisabled()) { intro.pause(); film.pause(); mobileFilm.pause(); } invalidateMobile(); });

  document.addEventListener('ccs:motion', () => { if (motionDisabled()) { intro.pause(); film.pause(); mobileFilm.pause(); } invalidateMobile(); });

  let introWasLeft = Boolean(location.hash && location.hash !== '#home');
  let introLaunched = false;
  const leaveIntro = () => { introWasLeft = true; };
  window.addEventListener('wheel', leaveIntro, { passive: true, once: true });
  window.addEventListener('touchmove', leaveIntro, { passive: true, once: true });
  $$('.site-header a, .site-header button, .intro-enter-link').forEach(control => control.addEventListener('click', leaveIntro));
  function enterExperience() {
    introWasLeft = true;
    intro.pause();
    window.scrollTo({ top: experience.getBoundingClientRect().top + scrollY - document.querySelector('.site-header').offsetHeight, behavior: 'instant' });
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
    setTimeout(() => { loader.hidden = true; if (mobile.matches) requestRender(); }, motionDisabled() ? 0 : 680);
    if (skip) { enterExperience(); experience.tabIndex = -1; experience.focus({ preventScroll: true }); }
    else launchIntro();
    requestRender();
  }
  function tickLoader(now) {
    if (loaderFinished) return;
    const elapsed = now - started;
    const ready = intro.readyState >= 2 && $(' .loader-center img').complete && document.fonts.status === 'loaded';
    const minimum = motionDisabled() ? 80 : mobile.matches ? 550 : 1800;
    const target = ready && elapsed > minimum ? 100 : Math.min(92, elapsed / (mobile.matches ? 850 : 1900) * 92);
    counter += (target - counter) * 0.15;
    $('#loader-number').textContent = String(Math.floor(clamp(counter, 0, 100))).padStart(3, '0');
    $('#loader-elapsed').textContent = seconds(elapsed / 1000);
    $('#loader-progress').setAttribute('aria-valuenow', String(Math.floor(counter)));
    $('#loader-progress > span').style.width = `${counter}%`;
    loader.style.setProperty('--load-angle', `${counter * 3.6}deg`);
    const stage = counter < 34 ? 0 : counter < 72 ? 1 : 2;
    if (counter < 99) $('#loader-status').textContent = ['SETTING THE DETAILS IN PLACE','BRINGING THE EXPERIENCE TO LIFE','ALMOST READY. LET’S GET YOU MOVING.'][stage];
    if ((counter > 99.4 && elapsed > minimum) || elapsed > (motionDisabled() ? 300 : mobile.matches ? 2400 : 4200)) { finishLoader(); return; }
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
    setTimeout(() => finishLoader(), motionDisabled() ? 350 : mobile.matches ? 2600 : 4600);
  };
  Promise.resolve(window.ccsWelcomeGate).then(beginLoader);
  showChapter(0, 0);
  requestRender();
})();
