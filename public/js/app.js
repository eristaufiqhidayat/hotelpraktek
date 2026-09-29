/* Hotel Praktik Widuri · interaksi kecil di sisi browser.
   Semua data dan aturan ada di server (Laravel); file ini hanya menambah kenyamanan. */
(function () {
  'use strict';

  function $(s, el) { return (el || document).querySelector(s); }

  /* ---------- toast ---------- */
  function armToasts(root) {
    (root || document).querySelectorAll('#toast .toast').forEach(function (el) {
      if (el.dataset.armed) return;
      el.dataset.armed = '1';
      setTimeout(function () { el.remove(); }, el.classList.contains('err') ? 5200 : 3200);
    });
  }
  armToasts();

  /* ---------- modal ---------- */
  var overlay = $('.overlay');
  function closeModal() {
    var o = $('.overlay');
    if (o && o.dataset.close) window.location.href = o.dataset.close;
  }
  if (overlay) {
    var f = overlay.querySelector('input:not([type=hidden]):not([type=radio]):not([readonly]),select,textarea,button.btn,a.btn.pri');
    if (f) f.focus();
    overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });
    // hapus flash struk dari riwayat agar tombol "back" tidak membuka ulang modal
  }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && $('.overlay')) closeModal(); });

  /* ---------- login: pilih siswa / guru ---------- */
  document.addEventListener('change', function (e) {
    var el = e.target;
    if (el.name === 'role') {
      $('#lg-siswa').classList.toggle('hidden', el.value !== 'siswa');
      $('#lg-guru').classList.toggle('hidden', el.value !== 'guru');
      return;
    }
    if (el.matches('[data-toggle-room]')) {
      $('#pos-room').classList.toggle('hidden', el.value !== 'Charge to room');
      return;
    }
    if (el.matches('[data-autosubmit]')) {
      var form = el.form;
      if (form.hasAttribute('data-soft')) softSubmit(form); else form.submit();
    }
  });

  /* ---------- konfirmasi & submit tanpa memuat ulang halaman ---------- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { e.preventDefault(); return; }
    if (form.hasAttribute('data-soft') && window.fetch && window.DOMParser) {
      e.preventDefault();
      softSubmit(form);
    }
  });

  var busy = false;
  function softSubmit(form) {
    if (busy) return;
    busy = true;
    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
      .then(function (r) { return r.text().then(function (t) { return { url: r.url, html: t }; }); })
      .then(function (res) {
        var doc = new DOMParser().parseFromString(res.html, 'text/html');
        var main = doc.querySelector('#main');
        if (!main) { window.location.href = res.url; return; }
        var y = window.scrollY;
        $('#main').innerHTML = main.innerHTML;
        var t = doc.querySelector('#toast');
        if (t) { $('#toast').innerHTML = t.innerHTML; armToasts(); }
        if (res.url && res.url !== window.location.href) history.replaceState(null, '', res.url);
        window.scrollTo(0, y);
      })
      .catch(function () { form.submit(); })
      .then(function () { busy = false; });
  }

  /* ---------- lain-lain ---------- */
  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-print]')) { e.preventDefault(); window.print(); }
  });

  // filter tabel secara langsung saat mengetik (tetap bisa Enter untuk cari di server)
  document.addEventListener('input', function (e) {
    var el = e.target;
    if (!el.matches('[data-live-filter]')) return;
    var q = el.value.trim().toLowerCase();
    var body = $(el.dataset.liveFilter);
    if (!body) return;
    body.querySelectorAll('tr[data-search]').forEach(function (tr) {
      tr.style.display = !q || tr.dataset.search.indexOf(q) >= 0 ? '' : 'none';
    });
  });
})();
