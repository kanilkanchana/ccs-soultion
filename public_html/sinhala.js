(() => {
  'use strict';
  const menu = document.querySelector('.menu-toggle');
  const nav = document.querySelector('#mobile-nav');
  function closeMenu(returnFocus = false) {
    if (!nav || !menu) return;
    nav.hidden = true;
    menu.setAttribute('aria-expanded','false');
    menu.setAttribute('aria-label','මෙනුව විවෘත කරන්න');
    document.body.classList.remove('menu-open');
    if (returnFocus) menu.focus();
  }
  if (menu && nav) {
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
  }
  const yearEl = document.querySelector('#si-year');
  if (yearEl) yearEl.textContent = String(new Date().getFullYear());
  const wa = document.querySelector('#si-whatsapp');
  if (wa) {
    const message='ආයුබෝවන් CCS Solutions, මගේ Hybrid රථය පරීක්ෂා කර ගැනීමට අවශ්‍යයි.\n\nරථ මාදිලිය:\nගැටලුව:\nපැමිණීමට කැමති දිනය:';
    wa.href='https://wa.me/94717937011?text='+encodeURIComponent(message);
  }
  const reduced=matchMedia('(prefers-reduced-motion:reduce)');
  let motionOff=false;
  try{motionOff=localStorage.getItem('ccs-motion-off')==='yes';}catch{}
  if(motionOff)document.documentElement.classList.add('motion-off');
  if('IntersectionObserver'in window&&!reduced.matches&&!motionOff){
    document.body.classList.add('si-motion-ready');
    const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('is-visible');observer.unobserve(entry.target);}}),{threshold:.07});
    document.querySelectorAll('.si-reveal').forEach(e=>observer.observe(e));
  }
  const SI_VIDEOS = [
    {
      id: 'v1',
      num: '01',
      title: 'Hybrid Battery සෛල පරීක්ෂාව සහ Cell Balancing.',
      kicker: 'බැටරි නඩත්තුව සහ සවිස්තරාත්මක පරීක්ෂාව',
      desc: 'දෝෂ සහිත මොඩියුල හඳුනාගෙන, නිරවද්‍ය ඉලෙක්ට්‍රොනික මිනුම් සහ Balance කිරීමේ ක්‍රියාවලිය සජීවීව වැඩමුළුවේදී.',
      badge: '01 / BATTERY SERVICE',
      tag: 'සැබෑ වැඩමුළු අත්දැකීම (v1)',
      poster: 'assets/experience/v1-poster.jpg',
      mp4: 'assets/experience/v1.mp4',
      mov: 'assets/experience/v1.MOV'
    },
    {
      id: 'v2',
      num: '02',
      title: 'රාගම වැඩමුළුව වෙත පැමිණෙන Hybrid රථ.',
      kicker: 'CCS Solutions රාගම සේවා පරිශ්‍රය',
      desc: 'විශ්වාසනීය තාක්ෂණික විසඳුම් සහ ආරක්ෂිත සේවා පරිශ්‍රය තුළ ඔබේ රථය සඳහා ලැබෙන ඉහළම සැලකිල්ල.',
      badge: '02 / WORKSHOP PREMISES',
      tag: 'වැඩමුළු පරිශ්‍රය (v2)',
      poster: 'assets/experience/v2-poster.jpg',
      mp4: 'assets/experience/v2.mp4',
      mov: 'assets/experience/v2.MOV'
    },
    {
      id: 'v3',
      num: '03',
      title: 'Hybrid Battery Pack එක රථය තුළ නිවැරදිව සවිකිරීම.',
      kicker: 'රථය තුළ සවිකිරීම සහ පද්ධති පරීක්ෂාව',
      desc: 'සම්පූර්ණ පද්ධතිය නැවත සවිකර පරිගණකගත දෝෂ පරීක්ෂාව (Computerized Diagnostics) සමඟ අවසන් තහවුරු කිරීම.',
      badge: '03 / VEHICLE INSTALLATION',
      tag: 'රථයේ සවිකිරීම (v3)',
      poster: 'assets/experience/v3-poster.jpg',
      mp4: 'assets/experience/v3.mp4',
      mov: 'assets/experience/v3.MOV'
    }
  ];

  const ambientVid = document.querySelector('#si-ambient-video');
  const vidCtrl = document.querySelector('#si-video-ctrl');
  const soundCtrl = document.querySelector('#si-sound-ctrl');
  const prevClipBtn = document.querySelector('#si-prev-clip');
  const nextClipBtn = document.querySelector('#si-next-clip');
  const clipIndicator = document.querySelector('#si-clip-indicator');
  const videoTabs = document.querySelectorAll('.si-video-tab');
  const videoTag = document.querySelector('#si-video-tag');
  const videoBadge = document.querySelector('#si-video-badge');
  const videoKicker = document.querySelector('#si-video-kicker');
  const videoHeading = document.querySelector('#si-video-heading');
  const videoDesc = document.querySelector('#si-video-desc');

  let currentVidIndex = 0;

  function updatePlayBtn(isPlaying) {
    if (!vidCtrl) return;
    const icon = vidCtrl.querySelector('.si-ctrl-icon');
    const text = vidCtrl.querySelector('.si-ctrl-text');
    if (isPlaying) {
      if (icon) icon.textContent = '❚❚';
      if (text) text.textContent = 'වීඩියෝව නවත්වන්න';
      vidCtrl.setAttribute('aria-label', 'වීඩියෝව නවත්වන්න');
    } else {
      if (icon) icon.textContent = '▶';
      if (text) text.textContent = 'ධාවනය කරන්න';
      vidCtrl.setAttribute('aria-label', 'වීඩියෝව ධාවනය කරන්න');
    }
  }

  function updateSoundBtn(isMuted) {
    if (!soundCtrl) return;
    const icon = soundCtrl.querySelector('.si-ctrl-icon');
    const text = soundCtrl.querySelector('.si-ctrl-text');
    if (isMuted) {
      soundCtrl.classList.remove('is-unmuted');
      if (icon) icon.textContent = '🔇';
      if (text) text.textContent = 'ශබ්දය අක්‍රියයි';
      soundCtrl.setAttribute('aria-label', 'ශබ්දය ක්‍රියාත්මක කරන්න');
    } else {
      soundCtrl.classList.add('is-unmuted');
      if (icon) icon.textContent = '🔊';
      if (text) text.textContent = 'ශබ්දය සහිතයි';
      soundCtrl.setAttribute('aria-label', 'ශබ්දය අක්‍රිය කරන්න');
    }
  }

  function switchVideo(index, autoPlay = true) {
    if (!ambientVid) return;
    if (index < 0) index = SI_VIDEOS.length - 1;
    if (index >= SI_VIDEOS.length) index = 0;
    currentVidIndex = index;
    const item = SI_VIDEOS[currentVidIndex];

    if (videoTag) videoTag.innerHTML = '<i aria-hidden="true"></i> ' + item.tag;
    if (videoBadge) videoBadge.textContent = item.badge;
    if (videoKicker) videoKicker.textContent = item.kicker;
    if (videoHeading) videoHeading.textContent = item.title;
    if (videoDesc) videoDesc.textContent = item.desc;
    if (clipIndicator) clipIndicator.textContent = item.num + ' / 03';

    videoTabs.forEach((tab, idx) => {
      const active = idx === currentVidIndex;
      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    ambientVid.poster = item.poster;

    const canPlayMov = ambientVid.canPlayType('video/quicktime');
    const srcToUse = (ambientVid.canPlayType('video/mp4') || !canPlayMov) ? item.mp4 : item.mov;
    ambientVid.src = srcToUse;
    ambientVid.load();

    if (autoPlay) {
      ambientVid.play().then(() => updatePlayBtn(true)).catch(() => updatePlayBtn(false));
    } else {
      updatePlayBtn(false);
    }
  }

  if (ambientVid) {
    const unavailable = document.createElement('p');
    unavailable.setAttribute('role', 'status');
    unavailable.style.cssText = 'position:absolute;bottom:8px;left:16px;right:16px;z-index:5;color:#fff;background:#111c;padding:8px;border-radius:6px';
    unavailable.textContent = 'මෙම වීඩියෝව දැනට ලබා ගත නොහැක.';
    unavailable.hidden = true;
    ambientVid.parentElement.append(unavailable);
    ambientVid.addEventListener('error', () => { unavailable.hidden = false; updatePlayBtn(false); });
    ambientVid.addEventListener('loadeddata', () => { unavailable.hidden = true; });
    ambientVid.querySelectorAll('source').forEach(source => source.addEventListener('error', () => {
      if (ambientVid.networkState === HTMLMediaElement.NETWORK_NO_SOURCE) unavailable.hidden = false;
    }));
    ambientVid.muted = true;
    const initialPlay = ambientVid.play();
    if (initialPlay !== undefined) {
      initialPlay.then(() => updatePlayBtn(true)).catch(() => updatePlayBtn(false));
    }

    ambientVid.addEventListener('play', () => updatePlayBtn(true));
    ambientVid.addEventListener('pause', () => updatePlayBtn(false));
    ambientVid.addEventListener('click', () => {
      if (ambientVid.paused) {
        ambientVid.play().catch(() => {});
      } else {
        ambientVid.pause();
      }
    });
    ambientVid.addEventListener('ended', () => {
      switchVideo(currentVidIndex + 1, true);
    });

    if (vidCtrl) {
      vidCtrl.addEventListener('click', () => {
        if (ambientVid.paused) {
          ambientVid.play().catch(() => {});
        } else {
          ambientVid.pause();
        }
      });
    }

    if (soundCtrl) {
      soundCtrl.addEventListener('click', () => {
        ambientVid.muted = !ambientVid.muted;
        updateSoundBtn(ambientVid.muted);
      });
    }

    if (prevClipBtn) {
      prevClipBtn.addEventListener('click', () => switchVideo(currentVidIndex - 1, true));
    }

    if (nextClipBtn) {
      nextClipBtn.addEventListener('click', () => switchVideo(currentVidIndex + 1, true));
    }

    videoTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const idx = parseInt(tab.dataset.videoIndex, 10);
        if (!isNaN(idx)) switchVideo(idx, true);
      });
    });
  }
})();
