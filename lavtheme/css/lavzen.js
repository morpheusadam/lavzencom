/* ============================================================================
   LAVZEN · Behaviour  (lavzen.js)
   ----------------------------------------------------------------------------
   Dependency-free. Only behaviours the pages actually use:
     · theme toggle (dark default, persisted, system-aware)
     · glass-intensity control (live --lav-glass-intensity)
     · mobile nav drawer
     · FAQ accordion (single-open, native <details>)
     · scroll-reveal (IntersectionObserver, opacity/transform only)
   Everything respects prefers-reduced-motion. No handler is bound that nothing
   triggers. Exposes a tiny API on window.lavzen.
   ============================================================================ */
(function () {
  "use strict";

  var root = document.documentElement;
  var STORE_THEME = "lav-theme";
  var STORE_INTENSITY = "lav-intensity";
  var reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ---- THEME ------------------------------------------------------------- */
  function systemTheme() {
    return (window.matchMedia && window.matchMedia("(prefers-color-scheme: light)").matches) ? "light" : "dark";
  }
  function applyTheme(theme) {
    // Default brand theme is dark; only "light" needs the attribute.
    if (theme === "light") { root.setAttribute("data-theme", "light"); }
    else { root.removeAttribute("data-theme"); }
  }
  function initTheme() {
    var saved = null;
    try { saved = localStorage.getItem(STORE_THEME); } catch (e) {}
    applyTheme(saved || "dark");           // brand default = dark
  }
  function toggleTheme() {
    var current = root.getAttribute("data-theme") === "light" ? "light" : "dark";
    var next = current === "light" ? "dark" : "light";
    applyTheme(next);
    try { localStorage.setItem(STORE_THEME, next); } catch (e) {}
    return next;
  }

  /* ---- GLASS INTENSITY --------------------------------------------------- */
  function applyIntensity(val) {
    var n = Math.max(0.5, Math.min(1.5, parseFloat(val) || 1));
    root.style.setProperty("--lav-glass-intensity", String(n));
    return n;
  }
  function initIntensity() {
    var saved = null;
    try { saved = localStorage.getItem(STORE_INTENSITY); } catch (e) {}
    if (saved) { applyIntensity(saved); }
  }

  /* ---- DOM-READY wiring --------------------------------------------------- */
  function ready(fn) {
    if (document.readyState !== "loading") { fn(); }
    else { document.addEventListener("DOMContentLoaded", fn); }
  }

  ready(function () {
    /* theme toggle button(s) */
    document.querySelectorAll("[data-lav-theme-toggle]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var next = toggleTheme();
        btn.setAttribute("aria-pressed", String(next === "light"));
      });
    });

    /* display-control popover (theme + intensity) */
    document.querySelectorAll("[data-lav-display]").forEach(function (ctl) {
      var trigger = ctl.querySelector("[data-lav-display-trigger]");
      if (trigger) {
        trigger.addEventListener("click", function () {
          var open = ctl.classList.toggle("is-open");
          trigger.setAttribute("aria-expanded", String(open));
        });
        document.addEventListener("click", function (e) {
          if (!ctl.contains(e.target)) { ctl.classList.remove("is-open"); trigger.setAttribute("aria-expanded", "false"); }
        });
      }
      var range = ctl.querySelector("[data-lav-intensity]");
      if (range) {
        var cur = root.style.getPropertyValue("--lav-glass-intensity") || "1";
        range.value = parseFloat(cur) || 1;
        range.addEventListener("input", function () {
          var n = applyIntensity(range.value);
          try { localStorage.setItem(STORE_INTENSITY, String(n)); } catch (e) {}
        });
      }
    });

    /* mobile nav drawer */
    document.querySelectorAll("[data-lav-nav-toggle]").forEach(function (btn) {
      var drawer = document.getElementById(btn.getAttribute("aria-controls"));
      if (!drawer) { return; }
      btn.addEventListener("click", function () {
        var open = drawer.classList.toggle("is-open");
        btn.setAttribute("aria-expanded", String(open));
      });
      drawer.addEventListener("click", function (e) { if (e.target.closest("a")) { drawer.classList.remove("is-open"); btn.setAttribute("aria-expanded", "false"); } });
      document.addEventListener("keydown", function (e) { if (e.key === "Escape") { drawer.classList.remove("is-open"); btn.setAttribute("aria-expanded", "false"); } });
    });

    /* FAQ accordion — single-open (native <details>) */
    var faqItems = document.querySelectorAll("[data-lav-faq] > details");
    faqItems.forEach(function (d) {
      d.addEventListener("toggle", function () {
        if (d.open) {
          faqItems.forEach(function (other) { if (other !== d) { other.open = false; } });
        }
      });
    });

    /* smooth-scroll: move focus to target for keyboard/SR users after the jump */
    document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(function (a) {
      a.addEventListener("click", function (e) {
        var id = a.getAttribute("href").slice(1);
        var target = document.getElementById(id);
        if (!target) { return; }
        e.preventDefault();
        target.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "start" });
        target.setAttribute("tabindex", "-1");
        target.focus({ preventScroll: true });
        if (history.replaceState) { history.replaceState(null, "", "#" + id); }
      });
    });

    /* scroll-reveal */
    if (!reduceMotion && "IntersectionObserver" in window) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
          if (en.isIntersecting) { en.target.classList.add("is-in"); io.unobserve(en.target); }
        });
      }, { threshold: 0.12, rootMargin: "0px 0px -8% 0px" });
      document.querySelectorAll(".lav-reveal").forEach(function (el) { io.observe(el); });
    } else {
      document.querySelectorAll(".lav-reveal").forEach(function (el) { el.classList.add("is-in"); });
    }
  });

  /* run theme + intensity ASAP (before DOMContentLoaded) to avoid a flash */
  initTheme();
  initIntensity();

  /* public API */
  window.lavzen = { toggleTheme: toggleTheme, setTheme: applyTheme, setIntensity: applyIntensity };
})();

/* ── LAVZEN background mouse-parallax (appended; vanilla, rAF, friction) ───── */
(function () {
  "use strict";
  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
  if (window.matchMedia("(hover: none), (pointer: coarse)").matches) return; // touch → static
  var fx = 0, fy = 0, x = 0, y = 0, friction = 1 / 30, body = document.body;
  function loop() {
    x += (fx - x) * friction;
    y += (fy - y) * friction;
    body.style.setProperty("--bgx", x.toFixed(2) + "px");
    body.style.setProperty("--bgy", y.toFixed(2) + "px");
    window.requestAnimationFrame(loop);
  }
  window.addEventListener("mousemove", function (e) {
    var mx = Math.max(-100, Math.min(100, window.innerWidth / 2 - e.clientX));
    var my = Math.max(-100, Math.min(100, window.innerHeight / 2 - e.clientY));
    fx = (20 * mx) / 100;   // ±20px horizontal
    fy = (10 * my) / 100;   // ±10px vertical
  }, { passive: true });
  loop();
})();
