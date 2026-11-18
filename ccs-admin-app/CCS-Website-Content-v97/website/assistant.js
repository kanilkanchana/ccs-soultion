(() => {
  'use strict';
  const topics = window.CCS_ASSISTANT_DATA || [];
  if (!topics.length) return;
  const botIcon = '<img class="assistant-robot" src="assets/assistant/ccs-robot.svg" alt="" width="36" height="36">';
  const dock = document.createElement('aside');
  dock.className = 'assistant-dock';
  dock.setAttribute('aria-label', 'CCS website assistant');
  dock.innerHTML = `
    <div class="assistant-greeting" id="assistant-greeting"><button type="button" data-chat-open>Hi! Need a hand with your hybrid?<strong>Ask me about CCS <span aria-hidden="true">↗</span></strong></button><button type="button" id="assistant-greeting-close" aria-label="Dismiss assistant greeting">×</button></div>
    <section class="assistant-panel" id="assistant-panel" role="dialog" aria-modal="false" aria-labelledby="assistant-title" hidden>
      <header class="assistant-heading"><span class="assistant-avatar">${botIcon}</span><div><h2 id="assistant-title">CCS Assistant</h2><p><i aria-hidden="true"></i> Practical hybrid & everyday tips</p></div><button id="assistant-close" type="button" aria-label="Close assistant">×</button></header>
      <div class="assistant-intro"><span>HYBRID CARE. A CLEARER START.</span><button type="button" id="assistant-reset" aria-label="Start a new chat">New chat ↻</button></div>
      <div class="assistant-messages" id="assistant-messages" role="log" aria-label="Conversation with CCS Assistant" aria-live="polite" aria-relevant="additions"></div>
      <div class="assistant-quick" aria-label="Suggested questions"><button type="button" data-topic="services">Our services</button><button type="button" data-topic="booking">Book a visit</button><button type="button" data-topic="offers">Offers</button><button type="button" data-topic="location">Find us</button><button type="button" data-topic="maintaining">Car tips</button></div>
      <form class="assistant-form" id="assistant-form"><label for="assistant-input" class="sr-only">Ask about CCS Solutions</label><input id="assistant-input" name="question" type="text" maxlength="350" autocomplete="off" placeholder="Ask a question…" required><button type="submit" aria-label="Send question"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 7-7 7 7M12 5v15"/></svg></button></form>
      <div class="assistant-foot"><span id="assistant-engine" role="status">Quick answers ready</span><span>Your chat stays on this device</span></div>
    </section>
    <button type="button" class="assistant-toggle" id="assistant-toggle" aria-expanded="false" aria-controls="assistant-panel" aria-label="Open CCS website assistant">${botIcon}<span class="assistant-toggle-label">Ask CCS</span></button>`;
  document.body.append(dock);
  const $ = selector => dock.querySelector(selector);
  const panel = $('#assistant-panel'), toggle = $('#assistant-toggle'), input = $('#assistant-input');
  const messages = $('#assistant-messages'), engineStatus = $('#assistant-engine');
  let worker, aiReady = false, started = false, sequence = 0, busy = false, initTimer;
  const pending = new Map();
  const byId = id => topics.find(topic => topic.id === id);
  const normalized = text => text.toLowerCase().normalize('NFKD').replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
  const external = href => /^https?:/.test(href);
  const contactLinks = [['View CCS services', 'index.html#services'], ['Contact the workshop', 'contact.html']];
  const fallback = { answer: 'I’m a local information assistant, not a live AI or a substitute for a mechanic. I can help with CCS services, practical driving and maintenance questions, and some everyday topics. Could you ask a little more specifically? For an exact vehicle diagnosis or current information, please contact CCS.', links: contactLinks };

  function appendMessage(text, role = 'assistant', links = []) {
    const article = document.createElement('div');
    article.className = `assistant-message from-${role}`;
    const name = document.createElement('small'); name.textContent = role === 'assistant' ? 'CCS ASSISTANT' : 'YOU';
    const paragraph = document.createElement('p'); paragraph.textContent = text;
    article.append(name, paragraph);
    if (links.length) {
      const list = document.createElement('div'); list.className = 'assistant-links';
      links.forEach(([label, href]) => {
        const link = document.createElement('a'); link.textContent = label + ' ↗'; link.href = href;
        if (external(href)) { link.target = '_blank'; link.rel = 'noopener noreferrer'; }
        else link.addEventListener('click', () => setOpen(false));
        list.append(link);
      });
      article.append(list);
    }
    messages.append(article);
    while (messages.children.length > 40) messages.firstElementChild.remove();
    messages.scrollTop = messages.scrollHeight;
    return article;
  }
  function welcome() {
    messages.replaceChildren();
    appendMessage('Hello! I’m your CCS website assistant. Ask me about CCS services, practical car questions, daily driving tips or simple everyday questions. How can I help today?');
  }
  function stopAI() {
    clearTimeout(initTimer); worker?.terminate(); worker = null; aiReady = false;
    engineStatus.textContent = 'Quick answers ready';
    pending.forEach(resolve => resolve(null)); pending.clear();
  }
  function startAI() {
    if (started) return;
    started = true;
    // Direct file:// opening cannot create module workers; the included FAQ guide still works.
    if (!/^https?:$/.test(location.protocol) || !window.Worker || !window.WebAssembly) return;
    engineStatus.textContent = 'Preparing local AI…';
    try {
      worker = new Worker(new URL('assistant-worker.js', document.baseURI), { type: 'module' });
      initTimer = setTimeout(stopAI, 60000);
      worker.addEventListener('error', event => { console.warn('CCS assistant worker:', event.message); stopAI(); });
      worker.addEventListener('message', ({ data }) => {
        if (data.type === 'ready') { clearTimeout(initTimer); aiReady = true; engineStatus.textContent = 'Local AI ready'; }
        if (data.type === 'progress') engineStatus.textContent = `Preparing local AI · ${data.value}%`;
        if (data.type === 'answer') { pending.get(data.requestId)?.(data.id); pending.delete(data.requestId); }
        if (data.type === 'error') {
          console.warn('CCS assistant:', data.message);
          if (data.requestId) { pending.get(data.requestId)?.(null); pending.delete(data.requestId); }
          else stopAI();
        }
      });
      worker.postMessage({ type: 'init', topics: topics.map(({ id, examples }) => ({ id, examples })) });
    } catch { stopAI(); }
  }
  function dismissGreeting() {
    $('#assistant-greeting').hidden = true;
    try { sessionStorage.setItem('ccs-assistant-greeting', 'dismissed'); } catch {}
  }
  function setOpen(open, returnFocus = false) {
    panel.hidden = !open; toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close CCS website assistant' : 'Open CCS website assistant');
    dock.classList.toggle('is-open', open);
    if (open) {
      dismissGreeting();
      if (document.querySelector('#whatsapp-toggle')?.getAttribute('aria-expanded') === 'true') document.querySelector('#whatsapp-close')?.click();
      startAI();
      // Preserve the useful conversation area on phones instead of opening the keyboard immediately.
      if (matchMedia('(min-width: 701px)').matches) input.focus({ preventScroll: true });
      else $('#assistant-close').focus({ preventScroll: true });
    } else if (returnFocus) toggle.focus({ preventScroll: true });
  }
  function priorityMatch(text) {
    const terms = normalized(text).split(' ');
    const lower = text.toLowerCase().trim();
    if (/^(what (is|s) (the )?time|current time|tell me the time|what day is it|what is today.s date|today.s date)/i.test(lower)) {
      const now = new Date();
      return { answer: `Your device shows ${now.toLocaleTimeString([], {hour:'numeric',minute:'2-digit'})} on ${now.toLocaleDateString([], {weekday:'long',day:'numeric',month:'long',year:'numeric'})}.`, links:[] };
    }
    if (/\b(joke|something funny|make me laugh)\b/i.test(lower)) return {answer: 'Why did the mechanic become a musician? Because they were great at tuning things! 🚗', links:[]};
    if (/\b(weather|forecast|temperature outside)\b/i.test(lower)) return {answer:'I can’t check live weather or forecasts. Please use a current weather service before planning your trip, especially during heavy rain.',links:[]};
    const math = lower.match(/(?:what(?: is|.s)?|calculate|solve)?\s*(-?\d+(?:\.\d+)?)\s*(\+|\-|\*|x|×|\/|÷)\s*(-?\d+(?:\.\d+)?)/);
    if (math) {
      const x=Number(math[1]), y=Number(math[3]);
      const result = ({'+':()=>x+y,'-':()=>x-y,'*':()=>x*y,'x':()=>x*y,'×':()=>x*y,'/':()=>x/y,'÷':()=>x/y})[math[2]]();
      return {answer: y===0 && '/÷'.includes(math[2]) ? 'Division by zero is undefined.' : `${math[1]} ${math[2]} ${math[3]} = ${Number(result.toPrecision(12))}`,links:[]};
    }
    if (/\b(how are you|who are you|what is your name)\b/i.test(lower)) return {answer:'I’m the CCS Solutions website assistant. I can answer common workshop and maintenance questions with curated information, plus a few everyday questions. I do not browse the web or diagnose vehicles.',links:[]};
    if (/\b(emergency|smoke from|on fire|brakes failed|brakes not working|burning smell)\b/i.test(lower)) return {answer:'This may be a safety emergency. Pull over safely, stop using the vehicle, keep others away from any smoke or heat and contact local emergency or roadside services. Do not attempt high-voltage repairs. CCS can discuss the vehicle after the immediate danger has passed.',links:[['Contact CCS','contact.html']]};
    const has = words => words.some(word => terms.includes(word));
    if (has(['ignore', 'instructions', 'prompt', 'jailbreak']) || /\b(step by step|diy|do it myself)\b/.test(lower) && /\b(high.voltage|hybrid battery|orange cable)\b/.test(lower)) return byId('hv-safety');
    if (/^(hi|hello|hey|good morning|good afternoon|good evening)[ !.]*$/i.test(text)) return { answer: 'Hello! Welcome to CCS Solutions. I can help you find services, offers, directions or the inspection form. What would you like to know?', links: contactLinks };
    if (/^(thanks|thank you|thankyou|cheers)[ !.]*$/i.test(text)) return { answer: 'You’re welcome. If you need workshop advice, the CCS team can help by phone, WhatsApp or email.', links: byId('contact').links };
    for (const [id, words] of [
      ['warranty', ['warranty','guarantee','coverage','claim']],
      ['offers', ['offer','offers','discount','discounts','promotion','promotions']],
      ['pricing', ['price','cost','costs','pricing','quote','quotation','rupees','lkr']],
      ['hours', ['hours','opening','closing','today','tomorrow','sunday','saturday']],
      ['contact', ['email','mail','whatsapp','phone','number','telephone']],
      ['login', ['login','password','register','account']],
      ['overheat', ['overheating','overheat','overheated']],
      ['flat', ['puncture','punctured']],
      ['twelvevolt', ['12v','12-volt']],
      ['hv-safety', ['high-voltage','electrocution']],
      ['tyres', ['tyres','tire','tread','tires']],
      ['location', ['location','address','directions','located']]
    ]) if (has(words)) return byId(id);
    // Exact model / chassis matching remains grounded in the actual directory.
    const vehicle = (window.CCS_VEHICLES || []).find(v => {
      const names = [v.model.replace(/Hybrid|Plug-in|e-POWER/gi, '').trim(), ...v.code.split(/\s*\/\s*/), v.id];
      return names.filter(name => name.length >= 3).some(name => normalized(text).includes(normalized(name)));
    });
    if (vehicle && !has(['battery','abs','brake','diagnostics','scan'])) return {
      answer: `${vehicle.make} ${vehicle.model} (${vehicle.code}) appears in the CCS model directory. Please share the exact year, chassis and concern so the workshop can confirm service availability and parts compatibility.`,
      links: [['Browse car models', 'models.html'], ['Enquire about this model', `contact.html?vehicle=${encodeURIComponent(vehicle.make + ' ' + vehicle.model + ' — ' + vehicle.code)}#inspection`]]
    };
    return null;
  }
  function quickMatch(text) {
    const words = new Set(normalized(text).split(' '));
    const ranked = topics.map(topic => ({ topic, score: topic.keywords.reduce((n, word) => n + (words.has(word) ? 1 : 0), 0) })).sort((a, b) => b.score - a.score);
    return ranked[0]?.score ? ranked[0].topic : fallback;
  }
  async function semanticMatch(text) {
    const requestId = ++sequence;
    return new Promise(resolve => {
      const timeout = setTimeout(() => { pending.delete(requestId); resolve(null); }, 8000);
      pending.set(requestId, id => { clearTimeout(timeout); resolve(id); });
      worker.postMessage({ type: 'query', text, requestId });
    });
  }
  async function ask(text, topicId) {
    text = text.trim().slice(0, 350); if (!text || busy) return;
    busy = true; input.value = ''; $('#assistant-form button').disabled = true;
    appendMessage(text, 'user');
    let answer = topicId ? byId(topicId) : priorityMatch(text);
    if (!answer && aiReady) {
      const waiting = appendMessage('Finding the relevant CCS information…'); waiting.classList.add('is-thinking');
      const id = await semanticMatch(text); waiting.remove();
      answer = id ? byId(id) : fallback;
    }
    if (!answer) answer = quickMatch(text);
    appendMessage(answer.answer, 'assistant', answer.links);
    busy = false; $('#assistant-form button').disabled = false;
  }
  toggle.addEventListener('click', () => setOpen(panel.hidden, !panel.hidden));
  $('[data-chat-open]').addEventListener('click', () => setOpen(true));
  $('#assistant-greeting-close').addEventListener('click', dismissGreeting);
  $('#assistant-close').addEventListener('click', () => setOpen(false, true));
  $('#assistant-reset').addEventListener('click', () => { if (!busy) welcome(); });
  $('#assistant-form').addEventListener('submit', event => { event.preventDefault(); ask(input.value); });
  dock.querySelectorAll('[data-topic]').forEach(button => button.addEventListener('click', () => ask(byId(button.dataset.topic).examples[0], button.dataset.topic)));
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && !panel.hidden) { event.preventDefault(); setOpen(false, true); } });
  document.querySelector('#whatsapp-toggle')?.addEventListener('click', () => setOpen(false));
  document.querySelector('.menu-toggle')?.addEventListener('click', () => setOpen(false));
  document.querySelectorAll('dialog').forEach(dialog => new MutationObserver(() => { if (dialog.open) setOpen(false); }).observe(dialog, { attributes: true, attributeFilter: ['open'] }));
  try { if (sessionStorage.getItem('ccs-assistant-greeting')) $('#assistant-greeting').hidden = true; } catch {}
  welcome();
})();
