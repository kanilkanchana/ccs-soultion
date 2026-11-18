/* Mobile-only video seeking: one decoder request in flight, always targeting the latest scroll position. */
(() => {
  'use strict';
  function create({ video, active, fps = 24, maxRate = 30, clock = {} }) {
    const now = clock.now || (() => performance.now());
    const requestFrame = clock.requestFrame || (callback => requestAnimationFrame(callback));
    const cancelFrame = clock.cancelFrame || (id => cancelAnimationFrame(id));
    const setTimer = clock.setTimer || ((callback, delay) => setTimeout(callback, delay));
    const clearTimer = clock.clearTimer || (id => clearTimeout(id));
    let desired = 0, lastSeek = -Infinity, frame = null, timer = null;
    const target = () => Math.min(Math.max(0, (video.duration || 30) - 1 / fps), Math.max(0, Math.round(desired * fps) / fps));
    const needsFrame = () => Math.abs(video.currentTime - target()) >= .5 / fps;
    function suspend() {
      if (frame !== null) cancelFrame(frame);
      if (timer !== null) clearTimer(timer);
      frame = timer = null;
    }
    function flush() {
      frame = null;
      if (!active() || video.readyState < 2 || video.seeking || !needsFrame()) return;
      lastSeek = now();
      try { video.currentTime = target(); } catch { /* Keep the existing poster while the decoder becomes ready. */ }
    }
    function queue() {
      if (!active()) { suspend(); return; }
      if (frame !== null || timer !== null || video.readyState < 2 || video.seeking || !needsFrame()) return;
      const delay = 1000 / maxRate - (now() - lastSeek);
      if (delay > 0) {
        timer = setTimer(() => { timer = null; queue(); }, Math.max(1,Math.ceil(delay)));
      } else {
        frame = requestFrame(flush);
      }
    }
    video.addEventListener('seeked', queue);
    video.addEventListener('loadeddata', queue);
    return {
      setTarget(value) { desired = Number.isFinite(value) ? value : 0; queue(); },
      suspend,
      destroy() { suspend(); video.removeEventListener('seeked', queue); video.removeEventListener('loadeddata', queue); }
    };
  }
  window.CCSMobileFilm = { create };
})();
