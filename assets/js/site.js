/* Extra Printers — public site behaviour (no framework). */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var track = function (ev, p) { try { window.xpTrack && window.xpTrack(ev, p || {}); } catch (e) {} };

  function toast(msg) {
    var t = $('#toast'); if (!t) return;
    t.textContent = msg; t.classList.add('show');
    clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove('show'); }, 3200);
  }
  window.xpToast = toast;

  function post(path, data) {
    var body = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
    body.append('_csrf', XP.csrf);
    return fetch(XP.url + '/' + path, { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .catch(function () { return { ok: false, error: 'Network problem. Please try again.' }; });
  }

  function setCount(n) {
    var b = $('#cartCount'); if (!b) return;
    b.textContent = n; b.classList.toggle('is-empty', !n);
  }

  // ── Theme ─────────────────────────────────────────────────────
  var themeBtn = $('#themeBtn');
  if (themeBtn) themeBtn.addEventListener('click', function () {
    var r = document.documentElement;
    var dark = r.dataset.theme ? r.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    r.dataset.theme = dark ? 'light' : 'dark';
    try { localStorage.setItem('xp-theme', r.dataset.theme); } catch (e) {}
  });

  // ── Mobile navigation ─────────────────────────────────────────
  var nav = $('#mainNav'), burger = $('#navBurger'), scrim;
  function closeNav() { nav.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); if (scrim) { scrim.remove(); scrim = null; } }
  if (burger && nav) {
    burger.addEventListener('click', function () {
      if (nav.classList.contains('open')) return closeNav();
      nav.classList.add('open'); burger.setAttribute('aria-expanded', 'true');
      scrim = document.createElement('div'); scrim.className = 'nav-scrim'; scrim.onclick = closeNav; document.body.appendChild(scrim);
    });
    $$('.mainnav__item > .mainnav__link', nav).forEach(function (a) {
      a.addEventListener('click', function (e) {
        if (matchMedia('(max-width: 980px)').matches) { e.preventDefault(); a.parentNode.classList.toggle('open'); }
      });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav.classList.contains('open')) closeNav(); });
  }

  // ── Click tracking (WhatsApp buttons etc.) ────────────────────
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-track]'); if (!el) return;
    track(el.dataset.track, { product: el.dataset.product || '' });
  });

  // ── Paper shop filter chips (home page) ───────────────────────
  $$('[data-filter-group]').forEach(function (group) {
    var grid = $('#' + group.dataset.filterGroup);
    group.addEventListener('click', function (e) {
      var b = e.target.closest('button[data-filter]'); if (!b) return;
      $$('button', group).forEach(function (x) { x.classList.toggle('is-on', x === b); x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      $$('[data-cat]', grid).forEach(function (card) { card.hidden = b.dataset.filter !== 'all' && card.dataset.cat !== b.dataset.filter; });
    });
  });

  // ── Product page ──────────────────────────────────────────────
  var form = $('#addForm');
  if (form) {
    track('view_item', { item_name: form.dataset.name });
    $$('input[type=radio]', form).forEach(function (r) {
      r.addEventListener('change', function () {
        var box = $('[data-custom-for="' + r.name + '"]', form);
        if (box) { box.classList.toggle('show', r.value === '__custom' && r.checked); if (r.value === '__custom') $('input', box).focus(); }
      });
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('[type=submit]', form); btn.disabled = true;
      post('ajax/cart.php', new FormData(form)).then(function (r) {
        btn.disabled = false;
        if (!r.ok) { toast(r.error || 'Could not add that.'); return; }
        setCount(r.count);
        track('add_to_cart', { item_name: form.dataset.name });
        window.location = XP.url + '/cart.php?added=' + encodeURIComponent(r.key);
      });
    });
  }
  $$('.gallery__thumbs button').forEach(function (b) {
    b.addEventListener('click', function () {
      $('.gallery__main img').src = b.dataset.src;
      $$('.gallery__thumbs button').forEach(function (x) { x.classList.toggle('is-on', x === b); });
    });
  });

  // ── Cart: remove lines, upload and remove artwork ─────────────
  document.addEventListener('click', function (e) {
    var rm = e.target.closest('[data-remove-line]');
    if (rm) {
      e.preventDefault();
      if (!confirm('Remove this product from your quote?')) return;
      post('ajax/cart.php', { action: 'remove', key: rm.dataset.removeLine }).then(function (r) {
        if (r.ok) { setCount(r.count); var l = rm.closest('.line'); l.parentNode.removeChild(l); if (!r.count) location.reload(); }
      });
    }
    var rf = e.target.closest('[data-remove-file]');
    if (rf) {
      e.preventDefault();
      post('ajax/upload.php', { action: 'remove', key: rf.dataset.key, file: rf.dataset.removeFile }).then(function (r) {
        if (r.ok) rf.closest('li').remove();
      });
    }
  });

  function fileRow(name, size) {
    var li = document.createElement('li');
    li.innerHTML = '<i class="fa-solid fa-file"></i><span class="f-name"></span><small></small><span class="bar"><i></i></span>';
    $('.f-name', li).textContent = name; $('small', li).textContent = size;
    li.classList.add('is-up');
    return li;
  }
  function sizeLabel(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }

  function upload(drop, file) {
    var list = $('.files', drop.parentNode), key = drop.dataset.key;
    var li = fileRow(file.name, sizeLabel(file.size)); list.appendChild(li);
    if (file.size > XP.maxMb * 1048576) {
      li.classList.remove('is-up'); $('.bar', li).outerHTML = '<span class="c-magenta small">Over ' + XP.maxMb + ' MB, add a link in the notes</span>'; return;
    }
    var fd = new FormData(); fd.append('artwork', file); fd.append('key', key); fd.append('_csrf', XP.csrf);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', XP.url + '/ajax/upload.php');
    xhr.upload.onprogress = function (ev) { if (ev.lengthComputable) $('.bar i', li).style.width = Math.round(ev.loaded / ev.total * 100) + '%'; };
    xhr.onload = function () {
      var r = {}; try { r = JSON.parse(xhr.responseText); } catch (e) { r = { ok: false, error: 'Upload failed (the file may be too big for the server).' }; }
      li.classList.remove('is-up');
      if (!r.ok) { $('.bar', li).outerHTML = '<span class="c-magenta small"></span>'; $('.c-magenta', li).textContent = r.error; return; }
      $('.bar', li).outerHTML = '<button type="button" aria-label="Remove file" data-key="' + key + '" data-remove-file="' + r.file.id + '"><i class="fa-solid fa-xmark"></i></button>';
      track('file_upload', { file_extension: file.name.split('.').pop() });
    };
    xhr.onerror = function () { li.classList.remove('is-up'); $('.bar', li).outerHTML = '<span class="c-magenta small">Upload failed. Try again.</span>'; };
    xhr.send(fd);
  }

  $$('.drop').forEach(function (drop) {
    var input = $('input[type=file]', drop);
    drop.addEventListener('click', function (e) { if (e.target !== input) input.click(); });
    drop.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
    input.addEventListener('change', function () { Array.prototype.forEach.call(input.files, function (f) { upload(drop, f); }); input.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
    drop.addEventListener('drop', function (e) { Array.prototype.forEach.call(e.dataTransfer.files, function (f) { upload(drop, f); }); });
  });

  // Save line notes as the customer types (debounced)
  $$('textarea[data-line-notes]').forEach(function (t) {
    var h; t.addEventListener('input', function () {
      clearTimeout(h); h = setTimeout(function () { post('ajax/cart.php', { action: 'notes', key: t.dataset.lineNotes, notes: t.value }); }, 600);
    });
  });

  // Lead conversion on the thank-you page
  var lead = $('[data-lead-ref]');
  if (lead) track('generate_lead', { ref: lead.dataset.leadRef, currency: 'ZAR', value: 250 });

  // Busy buttons on normal forms
  $$('form[data-busy]').forEach(function (f) {
    f.addEventListener('submit', function () { var b = $('[type=submit]', f); if (b) { b.disabled = true; b.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending…'; } });
  });
})();
