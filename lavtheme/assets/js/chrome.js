/**
 * LAVZEN home chrome — topbar popovers + mobile menu behaviour.
 *
 * Self-contained (no dependency on the legacy single-page `go()` helper): the
 * top-nav links navigate normally. Handles the notifications/account popovers,
 * the mobile menu toggle, and the rail's mobile profile/search shortcuts.
 */
(function () {
  'use strict';

  /* — header popovers (notifications, account) — */
  var groups = [
    { btn: document.getElementById('notifBtn'), pop: document.getElementById('notifPop') },
    { btn: document.getElementById('avatarBtn'), pop: document.getElementById('acctPop') }
  ].filter(function (g) { return g.btn && g.pop; });

  function closeAll(except) {
    groups.forEach(function (g) {
      if (g.pop !== except) {
        g.pop.classList.remove('open');
        g.btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  groups.forEach(function (g) {
    g.btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = !g.pop.classList.contains('open');
      closeAll(g.pop);
      g.pop.classList.toggle('open', open);
      g.btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    g.pop.addEventListener('click', function (e) { e.stopPropagation(); });
  });
  document.addEventListener('click', function () { closeAll(null); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAll(null); });

  /* — mobile menu toggle — */
  var topnav = document.getElementById('topnav');
  var toggle = document.getElementById('menuToggle');
  if (topnav && toggle) {
    toggle.addEventListener('click', function () {
      var open = topnav.classList.toggle('open');
      toggle.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  /* — rail mobile shortcuts (only present/visible where the rail renders) — */
  var searchBtn = document.getElementById('mobileSearchBtn');
  if (searchBtn) {
    searchBtn.addEventListener('click', function () {
      var input = document.getElementById('hero-q');
      if (input) {
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(function () { input.focus(); }, 420);
      }
    });
  }
  var profileBtn = document.getElementById('mobileProfileBtn');
  if (profileBtn) {
    profileBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var avatar = document.getElementById('avatarBtn');
      if (avatar) { avatar.click(); }
    });
  }
})();
