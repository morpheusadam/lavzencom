/**
 * LAVZEN marketplace home page — front-end behaviour.
 *
 * Self-contained vanilla JS for the home template only (enqueued by inc/home.php
 * on the front page). Four independent IIFEs: category marquee pause toggle,
 * hero search ("/" focus shortcut + animated placeholder), and the product-rail
 * arrow controls + save (wishlist) buttons. No dependencies.
 */
(function () {
  'use strict';

  /* — category marquee: explicit pause / play toggle (WCAG 2.2.2) — */
  (function () {
    var m = document.querySelector('.marquee');
    if (!m) return;
    var btn = m.querySelector('.marquee__toggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var paused = m.getAttribute('data-paused') === 'true';
      m.setAttribute('data-paused', paused ? 'false' : 'true');
      btn.setAttribute('aria-pressed', paused ? 'false' : 'true');
      btn.setAttribute('aria-label', paused ? 'Pause moving categories' : 'Play moving categories');
    });
  })();

  /* — hero search: "/" to focus, Esc to leave, animated placeholder — */
  (function () {
    var input = document.getElementById('hero-q');
    if (!input) return;

    document.addEventListener('keydown', function (e) {
      var t = (document.activeElement && document.activeElement.tagName || '').toLowerCase();
      if (e.key === '/' && document.activeElement !== input && t !== 'input' && t !== 'textarea' && t !== 'select') {
        e.preventDefault();
        input.focus();
      } else if (e.key === 'Escape' && document.activeElement === input) {
        input.blur();
      }
    });

    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var items = ['a voice-cloning model', 'an n8n lead-gen automation', 'a RAG knowledge pack', 'an edge AI dev kit', 'a Claude MCP server', 'a prompt engineer', 'a fine-tuned LoRA'];
    var base = 'Search for ', i = 0, ch = 0, deleting = false, paused = false;

    input.addEventListener('focus', function () { paused = true; input.placeholder = 'Search anything in AI…'; });
    input.addEventListener('blur', function () { if (!input.value) paused = false; });

    function tick() {
      var delay = deleting ? 40 : 72;
      if (!paused && !input.value) {
        var w = items[i];
        if (!deleting) {
          ch++;
          input.placeholder = base + w.slice(0, ch);
          if (ch >= w.length) { deleting = true; return setTimeout(tick, 1700); }
        } else {
          ch--;
          input.placeholder = base + w.slice(0, ch);
          if (ch <= 0) { deleting = false; i = (i + 1) % items.length; delay = 420; }
        }
      }
      setTimeout(tick, delay);
    }
    setTimeout(tick, 800);
  })();

  /* — product rails: arrow buttons, edge-disable, hide arrows when nothing to scroll — */
  (function () {
    document.querySelectorAll('[data-rail]').forEach(function (rail) {
      var vp = rail.querySelector('[data-viewport]');
      var prev = rail.querySelector('[data-prev]'), next = rail.querySelector('[data-next]');
      if (!vp) return;
      function step() {
        var c = vp.querySelector('.card,.ccard');
        var w = c ? c.getBoundingClientRect().width : 280;
        return (w + 16) * 1.4;
      }
      if (prev) prev.addEventListener('click', function () { vp.scrollBy({ left: -step(), behavior: 'smooth' }); });
      if (next) next.addEventListener('click', function () { vp.scrollBy({ left: step(), behavior: 'smooth' }); });
      function upd() {
        var max = vp.scrollWidth - vp.clientWidth - 2;
        if (prev) prev.disabled = vp.scrollLeft <= 0;
        if (next) next.disabled = vp.scrollLeft >= max;
      }
      vp.addEventListener('scroll', upd, { passive: true });
      if (vp.scrollWidth <= vp.clientWidth + 2) {
        if (prev) prev.style.display = 'none';
        if (next) next.style.display = 'none';
      }
      upd();
    });

    /* — save (wishlist) toggle — */
    document.querySelectorAll('[data-save]').forEach(function (b) {
      b.addEventListener('click', function () {
        b.setAttribute('aria-pressed', b.getAttribute('aria-pressed') === 'true' ? 'false' : 'true');
      });
    });
  })();
})();
