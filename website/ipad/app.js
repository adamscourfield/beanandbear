(function () {
  'use strict';

  var RECIPES = JSON.parse(document.getElementById('recipes-data').textContent);
  var DATA = JSON.parse(document.getElementById('app-data').textContent);
  var CAT_LABEL = { signature: 'Signature', choc: 'Chocolate', fruit: 'Fruit', pud: 'Pudding-inspired' };
  var DEMO_TODAY = '2026-10-11'; // matches the last day of DATA.sales_days — the single "now" for the whole prototype

  // ---- date helpers (all dates are "YYYY-MM-DD" local, no timezone math) ----
  function pad2(n) { return (n < 10 ? '0' : '') + n; }
  function isoOf(d) { return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()); }
  function dateOf(iso) { return new Date(iso + 'T00:00:00'); }
  function addDays(iso, n) { var d = dateOf(iso); d.setDate(d.getDate() + n); return isoOf(d); }
  function addMonths(iso, n) { var d = dateOf(iso); d.setMonth(d.getMonth() + n); return isoOf(d); }
  function addYears(iso, n) { var d = dateOf(iso); d.setFullYear(d.getFullYear() + n); return isoOf(d); }
  function startOfWeek(iso) { var d = dateOf(iso); var dow = d.getDay(); d.setDate(d.getDate() + (dow === 0 ? -6 : 1 - dow)); return isoOf(d); }
  function startOfMonth(iso) { var d = dateOf(iso); return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-01'; }
  function formatSpan(startIso, endIso) {
    var s = dateOf(startIso), e = dateOf(endIso);
    var sMon = s.toLocaleDateString('en-GB', { month: 'short' }), eMon = e.toLocaleDateString('en-GB', { month: 'short' });
    if (sMon === eMon) return s.getDate() + '–' + e.getDate() + ' ' + eMon + ' ' + e.getFullYear();
    return s.getDate() + ' ' + sMon + ' – ' + e.getDate() + ' ' + eMon + ' ' + e.getFullYear();
  }

  function staffById(id) {
    return DATA.staff.find(function (s) { return s.id === id; });
  }
  function initialsAvatar(staff, extra) {
    return '<span class="avatar' + (extra ? ' ' + extra : '') + '" style="background:' + staff.color + '">' + staff.initials + '</span>';
  }
  function money(n) {
    return '£' + Math.round(n).toLocaleString('en-GB');
  }

  // ================= Nav / view switching =================
  function moveNavIndicator(view) {
    var indicator = document.getElementById('nav-indicator');
    var active = document.querySelector('.nav-item[data-view="' + view + '"]');
    if (!indicator || !active) { if (indicator) indicator.style.opacity = '0'; return; }
    indicator.style.opacity = '1';
    indicator.style.transform = 'translateY(' + active.offsetTop + 'px)';
    indicator.style.height = active.offsetHeight + 'px';
  }

  function goTo(view) {
    var target = document.getElementById('view-' + view);
    var current = document.querySelector('.view.active');
    document.querySelectorAll('.nav-item, .bottom-nav button').forEach(function (el) {
      el.classList.toggle('active', el.getAttribute('data-view') === view);
    });
    moveNavIndicator(view);
    sweep();

    function activateNew() {
      document.querySelectorAll('.view').forEach(function (el) {
        el.classList.remove('in', 'out');
        el.classList.toggle('active', el === target);
      });
      if (target) {
        void target.offsetWidth;
        requestAnimationFrame(function () {
          target.classList.add('in');
          animateViewEntrance(view);
        });
      }
    }
    if (current && current !== target) {
      current.classList.add('out');
      setTimeout(activateNew, 120);
    } else {
      activateNew();
    }
  }
  window.goTo = goTo;

  function sweep() {
    var bar = document.getElementById('sweep');
    bar.classList.remove('run');
    void bar.offsetWidth;
    bar.classList.add('run');
  }

  document.querySelectorAll('[data-view]').forEach(function (el) {
    el.addEventListener('click', function () { goTo(el.getAttribute('data-view')); });
  });
  window.addEventListener('resize', function () {
    var active = document.querySelector('.nav-item.active');
    if (active) moveNavIndicator(active.getAttribute('data-view'));
  });

  // ================= Entrance animations =================
  function animateCounts(scope) {
    scope.querySelectorAll('[data-count]').forEach(function (el) {
      var target = parseFloat(el.getAttribute('data-count'));
      if (!isFinite(target)) return;
      var start = performance.now(), dur = 850;
      function tick(now) {
        var p = Math.min(1, (now - start) / dur);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(target * eased);
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }
  function animateBars(scope) {
    scope.querySelectorAll('.bar-fill[data-w]').forEach(function (el, i) {
      el.style.width = '0%';
      setTimeout(function () { el.style.width = el.getAttribute('data-w'); }, 60 + i * 50);
    });
  }
  function animatePops(scope, selector, stagger) {
    scope.querySelectorAll(selector).forEach(function (el, i) {
      el.classList.remove('in');
      void el.offsetWidth;
      setTimeout(function () { el.classList.add('in'); }, 30 + i * (stagger || 30));
    });
  }
  function animateRing(scope) {
    var ring = scope.querySelector('[data-offset]');
    if (!ring) return;
    var full = ring.getAttribute('stroke-dasharray').split(/[ ,]/)[0];
    ring.style.transition = 'none';
    ring.setAttribute('stroke-dashoffset', full);
    void ring.getBoundingClientRect();
    ring.style.transition = 'stroke-dashoffset 1.1s cubic-bezier(.16,1,.3,1)';
    requestAnimationFrame(function () { ring.setAttribute('stroke-dashoffset', ring.getAttribute('data-offset')); });
  }
  function animateViewEntrance(view) {
    var scope = document.getElementById('view-' + view);
    if (!scope) return;
    animateCounts(scope);
    animateBars(scope);
    animateRing(scope);
    if (view === 'recipes') animatePops(scope, '.recipe-card', 14);
    if (view === 'roster') {
      var sel = rosterState.mode === 'week' ? '.shift-cell' : rosterState.mode === 'month' ? '.cal-day' : '.month-card';
      animatePops(scope, sel, rosterState.mode === 'week' ? 10 : 6);
    }
    if (view === 'inventory') animatePops(scope, '#inv-body tr', 12);
    if (view === 'sales') { drawSalesChart(salesRange); animatePops(scope, '.flavour-row', 50); animateThreshold(); }
  }

  // ================= Toast =================
  var toastTimer = null;
  function showToast(msg) {
    var wrap = document.getElementById('toast-wrap');
    document.getElementById('toast-text').textContent = msg;
    wrap.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { wrap.classList.remove('show'); }, 2600);
  }
  window.showToast = showToast;

  // ================= Login gate =================
  var AUTH_KEY = 'bb_store_authed';
  function isAuthed() {
    try { return sessionStorage.getItem(AUTH_KEY) === '1'; } catch (e) { return false; }
  }
  function setAuthed(v) {
    try { if (v) sessionStorage.setItem(AUTH_KEY, '1'); else sessionStorage.removeItem(AUTH_KEY); } catch (e) { /* private mode: falls back to in-memory only */ }
  }
  function showLogin() {
    var screen = document.getElementById('login-screen');
    screen.classList.remove('hide');
    document.getElementById('login-user').value = '';
    document.getElementById('login-pass').value = '';
    document.getElementById('login-error').classList.remove('show');
    setTimeout(function () { document.getElementById('login-user').focus(); }, 300);
  }
  function hideLogin() {
    document.getElementById('login-screen').classList.add('hide');
  }
  window.lockStore = function () {
    setAuthed(false);
    showLogin();
  };
  document.getElementById('login-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var u = document.getElementById('login-user').value.trim();
    var p = document.getElementById('login-pass').value;
    if (u.toLowerCase() === 'admin' && p === '2501') {
      setAuthed(true);
      hideLogin();
      sweep();
    } else {
      var card = document.querySelector('.login-card');
      document.getElementById('login-error').classList.add('show');
      card.classList.remove('shake');
      void card.offsetWidth;
      card.classList.add('shake');
      var passField = document.getElementById('login-pass');
      passField.value = '';
      passField.focus();
    }
  });

  // ================= Who am I (profile switcher) =================
  var currentStaffId = DATA.staff[0].id;
  function renderWhoAmI() {
    var me = staffById(currentStaffId);
    document.getElementById('whoami-avatar').outerHTML = initialsAvatar(me, '').replace('<span class="avatar', '<span class="whoami-avatar" id="whoami-avatar" style="background:' + me.color + '"');
    document.getElementById('whoami-name').textContent = me.name;
    document.getElementById('whoami-role').textContent = me.role;
    var menu = document.getElementById('whoami-menu');
    menu.innerHTML = DATA.staff.map(function (s) {
      return '<button class="whoami-opt" data-sid="' + s.id + '">' + initialsAvatar(s) + '<span>' + s.name + '</span></button>';
    }).join('') + '<div class="whoami-divider"></div>' +
      '<button class="whoami-opt lock" id="lock-btn">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>' +
      '<span>Lock iPad</span></button>';
    menu.querySelectorAll('.whoami-opt[data-sid]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        currentStaffId = btn.getAttribute('data-sid');
        document.getElementById('whoami-wrap').classList.remove('open');
        renderWhoAmI();
        showToast('Signed in as ' + staffById(currentStaffId).name);
      });
    });
    document.getElementById('lock-btn').addEventListener('click', function () {
      document.getElementById('whoami-wrap').classList.remove('open');
      lockStore();
    });
  }
  document.getElementById('whoami-btn').addEventListener('click', function (e) {
    e.stopPropagation();
    document.getElementById('whoami-wrap').classList.toggle('open');
  });
  document.addEventListener('click', function () { document.getElementById('whoami-wrap').classList.remove('open'); });

  // ================= Recipes =================
  var activeCat = 'all';
  document.getElementById('recipe-filters').addEventListener('click', function (e) {
    var btn = e.target.closest('.pill-tab');
    if (!btn) return;
    document.querySelectorAll('#recipe-filters .pill-tab').forEach(function (b) { b.classList.toggle('active', b === btn); });
    activeCat = btn.getAttribute('data-cat');
    renderRecipes();
  });

  function renderRecipes() {
    var q = document.getElementById('recipe-search').value.trim().toLowerCase();
    var grid = document.getElementById('recipe-grid');
    var list = RECIPES.filter(function (r) {
      if (activeCat !== 'all' && r.cat !== activeCat) return false;
      if (q && r.n.toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    if (!list.length) {
      grid.innerHTML = '<div class="empty" style="grid-column:1/-1">No flavours match.</div>';
      return;
    }
    grid.innerHTML = list.map(function (r) {
      return '<div class="recipe-card anim-pop" onclick="openRecipe(\'' + r.key + '\')">' +
        '<div class="recipe-photo"><img src="../assets/img/flavours/' + r.key + '.jpg" alt="" onerror="this.remove()"></div>' +
        '<div class="recipe-body"><div class="recipe-name">' + r.n + '</div><div class="recipe-tag">' + r.tag + '</div></div>' +
        '</div>';
    }).join('');
    animatePops(grid, '.recipe-card', 12);
  }
  window.renderRecipes = renderRecipes;

  function openRecipe(key) {
    var r = RECIPES.find(function (x) { return x.key === key; });
    if (!r) return;
    var groups = [];
    var seen = {};
    r.ing.forEach(function (row) {
      var g = row[0] || '';
      if (!seen[g]) { seen[g] = []; groups.push([g, seen[g]]); }
      seen[g].push(row);
    });
    var ingHtml = groups.map(function (pair) {
      var g = pair[0], rows = pair[1];
      return (g ? '<div class="group-label">' + g + '</div>' : '') +
        '<div class="ing-grid">' + rows.map(function (row) {
          return '<div class="ing-row"><span class="ing-qty">' + (row[1] || '') + '</span><span>' + row[2] + '</span></div>';
        }).join('') + '</div>';
    }).join('');
    var stepsHtml = r.m.map(function (step, i) {
      return '<div class="method-step" onclick="this.classList.toggle(\'done\')"><span class="snum">' + (i + 1) + '</span><span class="stext">' + step + '</span></div>';
    }).join('');
    document.getElementById('recipe-page').innerHTML =
      '<div class="rp-topbar">' +
      '<button class="rp-close" onclick="closeRecipe()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>Close recipe</button>' +
      '<span class="chip gold">' + CAT_LABEL[r.cat] + '</span>' +
      '</div>' +
      '<div class="rp-body">' +
      '<div class="rp-side drawer-body" style="padding:0">' +
      '<div class="rp-hero"><img src="../assets/img/flavours/' + r.key + '.jpg" alt="" onerror="this.closest(\'.rp-hero\').remove()"></div>' +
      '<h2>' + r.n + '</h2><div class="tagline">' + r.tag + '</div>' +
      '<p class="story">' + r.story + '</p>' +
      '<div class="brief-box"><span class="lbl">The point of this flavour</span>' + r.brief + '</div>' +
      '<h3>Ingredients</h3>' + ingHtml +
      '</div>' +
      '<div class="rp-main drawer-body" style="padding:0">' +
      '<h3 style="margin-top:0">Method</h3><div class="method-steps">' + stepsHtml + '</div>' +
      '</div>' +
      '</div>';
    document.getElementById('recipe-page').classList.add('show');
    document.getElementById('recipe-page').scrollTo(0, 0);
  }
  window.openRecipe = openRecipe;
  function closeRecipe() {
    document.getElementById('recipe-page').classList.remove('show');
  }
  window.closeRecipe = closeRecipe;

  // ================= Roster =================
  var ROSTER_MIN = '2026-01-01', ROSTER_MAX = '2026-12-31';
  var rosterState = { mode: 'week', anchor: '2026-10-12' };

  function timeToHours(t) {
    var p = t.split(':');
    return parseInt(p[0], 10) + parseInt(p[1], 10) / 60;
  }
  function rosterEntry(iso) { return DATA.roster[iso] || { shifts: [] }; }

  function setRosterModeTab(mode) {
    document.querySelectorAll('#roster-mode-tabs .pill-tab').forEach(function (b) {
      b.classList.toggle('active', b.getAttribute('data-mode') === mode);
    });
  }

  document.getElementById('roster-mode-tabs').addEventListener('click', function (e) {
    var btn = e.target.closest('.pill-tab');
    if (!btn) return;
    setRosterModeTab(btn.getAttribute('data-mode'));
    rosterState.mode = btn.getAttribute('data-mode');
    renderRoster();
  });
  document.getElementById('roster-prev').addEventListener('click', function () { rosterNav(-1); });
  document.getElementById('roster-next').addEventListener('click', function () { rosterNav(1); });
  document.getElementById('roster-today').addEventListener('click', function () {
    rosterState.anchor = DEMO_TODAY;
    renderRoster();
  });

  function rosterNav(dir) {
    var next = rosterState.mode === 'week' ? addDays(rosterState.anchor, 7 * dir)
      : rosterState.mode === 'month' ? addMonths(rosterState.anchor, dir)
      : addYears(rosterState.anchor, dir);
    if (next < ROSTER_MIN) next = ROSTER_MIN;
    if (next > ROSTER_MAX) next = ROSTER_MAX;
    rosterState.anchor = next;
    renderRoster();
  }

  function renderRoster() {
    var mode = rosterState.mode;
    document.getElementById('roster-week-card').style.display = mode === 'week' ? '' : 'none';
    document.getElementById('roster-month-wrap').style.display = mode === 'month' ? '' : 'none';
    document.getElementById('roster-year-wrap').style.display = mode === 'year' ? '' : 'none';
    document.getElementById('roster-bottom-week').style.display = mode === 'week' ? '' : 'none';
    document.getElementById('roster-bottom-month').style.display = mode === 'month' ? '' : 'none';
    document.getElementById('roster-bottom-year').style.display = mode === 'year' ? '' : 'none';

    var atMin = (mode === 'week' ? addDays(rosterState.anchor, -7) : mode === 'month' ? addMonths(rosterState.anchor, -1) : addYears(rosterState.anchor, -1)) < ROSTER_MIN;
    var atMax = (mode === 'week' ? addDays(rosterState.anchor, 7) : mode === 'month' ? addMonths(rosterState.anchor, 1) : addYears(rosterState.anchor, 1)) > ROSTER_MAX;
    document.getElementById('roster-prev').disabled = atMin;
    document.getElementById('roster-next').disabled = atMax;

    if (mode === 'week') renderRosterWeek();
    else if (mode === 'month') renderRosterMonth();
    else renderRosterYear();
  }

  function renderRosterWeek() {
    var weekStart = startOfWeek(rosterState.anchor);
    var days = []; for (var i = 0; i < 7; i++) days.push(addDays(weekStart, i));
    var weekEnd = days[6];

    document.getElementById('roster-period-label').textContent = 'Week of ' + formatSpan(weekStart, weekEnd);
    document.getElementById('roster-sub').textContent = "Tap an open shift to sign up. Tap a filled shift to see who's on.";

    var grid = document.getElementById('roster-grid');
    grid.innerHTML = days.map(function (iso) {
      var d = dateOf(iso);
      var dayName = d.toLocaleDateString('en-GB', { weekday: 'short' });
      var dateLabel = d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
      var cells = rosterEntry(iso).shifts.map(function (shift, si) {
        if (shift.staff) {
          var s = staffById(shift.staff);
          var mine = shift.staff === currentStaffId ? ' mine' : '';
          return '<div class="shift-cell filled' + mine + '"><span class="stime">' + shift.label + ' · ' + shift.time + '</span>' +
            '<div class="who">' + initialsAvatar(s) + '<span class="wn">' + s.name.split(' ')[0] + '</span></div></div>';
        }
        return '<div class="shift-cell open"><span class="stime">' + shift.label + ' · ' + shift.time + '</span>' +
          '<button class="shift-btn" onclick="claimShift(\'' + iso + '\',' + si + ')">+ Sign up</button></div>';
      }).join('');
      return '<div class="roster-day' + (iso === DEMO_TODAY ? ' today' : '') + '">' +
        '<div class="roster-day-head"><b>' + dayName + '</b><span>' + dateLabel + '</span></div>' + cells + '</div>';
    }).join('');
    animatePops(grid, '.shift-cell', 10);

    document.getElementById('hours-week-label').textContent = 'Hours — ' + formatSpan(weekStart, weekEnd);
    renderHoursList('hours-list', computeHoursForDates(days));

    var prevWeekStart = addDays(weekStart, -7), prevWeekEnd = addDays(weekStart, -1);
    document.getElementById('history-label').textContent = 'Shifts worked — ' + formatSpan(prevWeekStart, prevWeekEnd);
    renderHistoryFor(prevWeekStart, prevWeekEnd);
  }

  function renderRosterMonth() {
    var monthStart = startOfMonth(rosterState.anchor);
    var d = dateOf(monthStart);
    var year = d.getFullYear(), month = d.getMonth();
    var monthLabel = d.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });

    document.getElementById('roster-period-label').textContent = monthLabel;
    document.getElementById('roster-sub').textContent = 'Tap a day to open that week.';

    var firstDow = (dateOf(monthStart).getDay() + 6) % 7; // 0 = Monday
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    var leadStart = addDays(monthStart, -firstDow);
    var cells = [];
    for (var i = 0; i < firstDow; i++) cells.push({ iso: addDays(leadStart, i), outside: true });
    for (var day = 1; day <= daysInMonth; day++) cells.push({ iso: monthStart.slice(0, 8) + pad2(day), outside: false });
    while (cells.length % 7 !== 0) cells.push({ iso: addDays(cells[cells.length - 1].iso, 1), outside: true });

    var weekdayRow = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(function (w) { return '<div class="cal-weekday">' + w + '</div>'; }).join('');
    var dayCells = cells.map(function (c) {
      var shifts = rosterEntry(c.iso).shifts;
      var filled = shifts.filter(function (s) { return s.staff; }).length;
      var dots = shifts.map(function (s) { return '<span class="cal-dot' + (s.staff ? ' filled' : '') + '"></span>'; }).join('');
      var isToday = c.iso === DEMO_TODAY;
      return '<div class="cal-day' + (c.outside ? ' outside' : '') + (isToday ? ' today' : '') + '" onclick="jumpToWeek(\'' + c.iso + '\')">' +
        '<span class="dnum">' + dateOf(c.iso).getDate() + '</span>' +
        '<span class="dhours">' + (shifts.length ? filled + '/' + shifts.length + ' staffed' : '') + '</span>' +
        '<div class="dots">' + dots + '</div></div>';
    }).join('');

    var wrap = document.getElementById('roster-month-wrap');
    wrap.innerHTML = '<div class="cal-grid cal-head">' + weekdayRow + '</div><div class="cal-grid">' + dayCells + '</div>';
    animatePops(wrap, '.cal-day', 5);

    var monthDays = []; for (var dd = 1; dd <= daysInMonth; dd++) monthDays.push(monthStart.slice(0, 8) + pad2(dd));
    document.getElementById('hours-month-label').textContent = 'Hours — ' + monthLabel;
    renderHoursList('hours-month-list', computeHoursForDates(monthDays));

    var openList = [];
    monthDays.forEach(function (iso) {
      rosterEntry(iso).shifts.forEach(function (shift) { if (!shift.staff) openList.push({ iso: iso, shift: shift }); });
    });
    document.getElementById('month-open-count').textContent = openList.length + ' open';
    document.getElementById('month-open-shifts').innerHTML = openList.length ? openList.map(function (o) {
      var label = dateOf(o.iso).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
      return '<div class="mini-row"><span class="mi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg></span>' +
        '<span class="meta"><b>' + label + '</b><span>' + o.shift.label + ' · ' + o.shift.time + '</span></span></div>';
    }).join('') : '<div class="empty" style="margin:8px">Every shift is covered this month.</div>';
  }

  function renderRosterYear() {
    var year = dateOf(rosterState.anchor).getFullYear();
    document.getElementById('roster-period-label').textContent = String(year);
    document.getElementById('roster-sub').textContent = 'Tap a month to open its calendar.';

    var months = [];
    for (var m = 0; m < 12; m++) {
      var monthStart = year + '-' + pad2(m + 1) + '-01';
      var daysInMonth = new Date(year, m + 1, 0).getDate();
      var totalShifts = 0, filledShifts = 0, totalHours = 0, heat = [];
      for (var day = 1; day <= daysInMonth; day++) {
        var shifts = rosterEntry(year + '-' + pad2(m + 1) + '-' + pad2(day)).shifts;
        var filled = shifts.filter(function (s) { return s.staff; }).length;
        totalShifts += shifts.length; filledShifts += filled;
        shifts.forEach(function (s) {
          if (!s.staff) return;
          var p = s.time.split('–').map(function (t) { return t.trim(); });
          totalHours += timeToHours(p[1]) - timeToHours(p[0]);
        });
        var ratio = shifts.length ? filled / shifts.length : 0;
        heat.push(ratio === 0 ? 0 : ratio < 0.4 ? 1 : ratio < 0.7 ? 2 : ratio < 1 ? 3 : 4);
      }
      months.push({ monthStart: monthStart, totalShifts: totalShifts, filledShifts: filledShifts, totalHours: totalHours, heat: heat });
    }

    var wrap = document.getElementById('roster-year-wrap');
    wrap.innerHTML = months.map(function (info) {
      var name = dateOf(info.monthStart).toLocaleDateString('en-GB', { month: 'long' });
      var heatCells = info.heat.map(function (lvl) { return '<span class="heat-cell' + (lvl ? ' l' + lvl : '') + '"></span>'; }).join('');
      var openCount = info.totalShifts - info.filledShifts;
      return '<div class="card month-card anim-pop" onclick="jumpToMonth(\'' + info.monthStart + '\')">' +
        '<h4>' + name + '</h4><div class="month-heat">' + heatCells + '</div>' +
        '<div class="mstat">' + Math.round(info.totalHours) + 'h scheduled · ' + openCount + ' open</div></div>';
    }).join('');
    animatePops(wrap, '.month-card', 18);

    var totalHoursYear = 0, totalShiftsYear = 0, openShiftsYear = 0;
    months.forEach(function (info) {
      totalHoursYear += info.totalHours;
      totalShiftsYear += info.totalShifts;
      openShiftsYear += info.totalShifts - info.filledShifts;
    });
    document.getElementById('year-stat-hours').setAttribute('data-count', Math.round(totalHoursYear));
    document.getElementById('year-stat-shifts').setAttribute('data-count', totalShiftsYear);
    document.getElementById('year-stat-open').setAttribute('data-count', openShiftsYear);
    animateCounts(document.getElementById('roster-bottom-year'));
  }

  window.jumpToWeek = function (iso) {
    rosterState.mode = 'week'; rosterState.anchor = iso;
    setRosterModeTab('week'); renderRoster();
  };
  window.jumpToMonth = function (monthStartIso) {
    rosterState.mode = 'month'; rosterState.anchor = monthStartIso;
    setRosterModeTab('month'); renderRoster();
  };

  function claimShift(iso, si) {
    var entry = DATA.roster[iso];
    if (!entry) return;
    var shift = entry.shifts[si];
    shift.staff = currentStaffId;
    renderRoster();
    showToast("You're on for " + dateOf(iso).toLocaleDateString('en-GB', { weekday: 'long' }) + ' ' + shift.label.toLowerCase() + '.');
  }
  window.claimShift = claimShift;

  function computeHoursForDates(days) {
    var totals = {};
    DATA.staff.forEach(function (s) { totals[s.id] = 0; });
    days.forEach(function (iso) {
      rosterEntry(iso).shifts.forEach(function (shift) {
        if (!shift.staff) return;
        var parts = shift.time.split('–').map(function (t) { return t.trim(); });
        totals[shift.staff] = (totals[shift.staff] || 0) + (timeToHours(parts[1]) - timeToHours(parts[0]));
      });
    });
    return totals;
  }

  function renderHoursList(elId, totals) {
    var max = Math.max.apply(null, Object.values(totals).concat([1]));
    var list = document.getElementById(elId);
    list.innerHTML = DATA.staff.map(function (s) {
      var h = totals[s.id] || 0;
      var pct = Math.round((h / max) * 100);
      return '<div class="hours-row"><span class="label">' + initialsAvatar(s, 'sm') + ' ' + s.name.split(' ')[0] + '</span>' +
        '<div class="track"><div class="bar-fill" data-w="' + pct + '%" style="width:0%;background:' + s.color + '"></div></div>' +
        '<span class="val">' + h.toFixed(1) + 'h</span></div>';
    }).join('');
    animateBars(list);
  }

  function renderHistoryFor(startIso, endIso) {
    var rows = [];
    for (var iso = startIso; iso <= endIso; iso = addDays(iso, 1)) {
      (function (iso) {
        rosterEntry(iso).shifts.forEach(function (shift) {
          if (!shift.staff) return;
          var parts = shift.time.split('–').map(function (t) { return t.trim(); });
          rows.push({ iso: iso, staff: shift.staff, inT: parts[0], outT: parts[1], hours: timeToHours(parts[1]) - timeToHours(parts[0]) });
        });
      })(iso);
    }
    var body = document.getElementById('history-body');
    if (!rows.length) { body.innerHTML = '<tr><td colspan="5"><div class="empty">No shifts worked in this period.</div></td></tr>'; return; }
    body.innerHTML = rows.map(function (r) {
      var s = staffById(r.staff);
      var dateLabel = dateOf(r.iso).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
      return '<tr><td>' + dateLabel + '</td><td>' + initialsAvatar(s, 'sm') + ' ' + s.name + '</td><td>' + r.inT + '</td><td>' + r.outT + '</td><td>' + r.hours.toFixed(1) + '</td></tr>';
    }).join('');
  }

  function openShiftModal() {
    var weekStart = startOfWeek(rosterState.mode === 'week' ? rosterState.anchor : DEMO_TODAY);
    var weekDays = []; for (var i = 0; i < 7; i++) weekDays.push(addDays(weekStart, i));
    var options = DATA.staff.map(function (s) { return '<option value="' + s.id + '">' + s.name + '</option>'; }).join('');
    var dayOptions = weekDays.map(function (iso) {
      return '<option value="' + iso + '">' + dateOf(iso).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' }) + '</option>';
    }).join('');
    showModal('Add a shift', '<div class="stack">' +
      '<label>Day<select class="field" id="ns-day">' + dayOptions + '</select></label>' +
      '<label>Time<input class="field" id="ns-time" placeholder="e.g. 9:00 – 13:00" value="9:00 – 13:00"></label>' +
      '<label>Staff (leave unset to post as open)<select class="field" id="ns-staff"><option value="">— Open shift —</option>' + options + '</select></label>' +
      '</div>',
      function () {
        var iso = document.getElementById('ns-day').value;
        var time = document.getElementById('ns-time').value || '9:00 – 13:00';
        var staffId = document.getElementById('ns-staff').value || null;
        if (!DATA.roster[iso]) DATA.roster[iso] = { shifts: [] };
        DATA.roster[iso].shifts.push({ label: 'Extra', time: time, staff: staffId });
        rosterState.mode = 'week'; rosterState.anchor = iso;
        setRosterModeTab('week');
        renderRoster();
        showToast('Shift added to ' + dateOf(iso).toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'short' }) + '.');
      });
  }
  window.openShiftModal = openShiftModal;

  // ================= Inventory =================
  function renderInventory() {
    var q = document.getElementById('inv-search').value.trim().toLowerCase();
    var body = document.getElementById('inv-body');
    var list = DATA.inventory.filter(function (i) { return !q || i.name.toLowerCase().indexOf(q) !== -1; });
    var lowCount = DATA.inventory.filter(function (i) { return i.low; }).length;
    document.getElementById('inv-count-label').textContent = DATA.inventory.length + ' ingredients tracked, ' + lowCount + ' low';
    var statEl = document.getElementById('stat-lowstock');
    if (statEl) statEl.setAttribute('data-count', lowCount);

    if (!list.length) {
      body.innerHTML = '<tr><td colspan="4"><div class="empty">No ingredients match.</div></td></tr>';
      return;
    }
    body.innerHTML = list.map(function (item) {
      var maxForBar = Math.max(item.stock, item.reorder * 1.8, 1);
      var pct = Math.min(100, Math.round((item.stock / maxForBar) * 100));
      return '<tr><td>' + item.name + (item.low ? ' <span class="flag">Low</span>' : '') + '</td>' +
        '<td><div class="inv-row-main"><div class="inv-bar-wrap' + (item.low ? ' low' : '') + '"><div class="track"><div class="bar-fill" data-w="' + pct + '%" style="width:0%"></div></div></div>' +
        '<span style="white-space:nowrap">' + item.stock + ' ' + item.unit + '</span></div></td>' +
        '<td>' + item.supplier + '</td>' +
        '<td><button class="btn-ghost btn" style="height:28px;padding:0 10px;font-size:11.5px" onclick="quickReceive(\'' + item.name.replace(/'/g, "\\'") + '\')">Receive</button></td></tr>';
    }).join('');
    animateBars(body);
  }
  window.renderInventory = renderInventory;

  function quickReceive(name) {
    var item = DATA.inventory.find(function (i) { return i.name === name; });
    if (!item) return;
    var bump = Math.max(1, Math.round(item.reorder * 0.6));
    item.stock = Math.round((item.stock + bump) * 10) / 10;
    item.low = item.stock <= item.reorder;
    DATA.deliveries.unshift({ date: 'Today', supplier: item.supplier, item: item.name, qty: bump + ' ' + item.unit });
    renderInventory();
    renderDeliveries();
    renderOverview();
    showToast(item.name + ' delivery logged — stock updated.');
  }
  window.quickReceive = quickReceive;

  function renderDeliveries() {
    ['ov-deliveries', 'inv-deliveries'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      var items = DATA.deliveries.slice(0, id === 'ov-deliveries' ? 4 : 8);
      el.innerHTML = items.map(function (d) {
        return '<div class="mini-row"><span class="mi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg></span>' +
          '<span class="meta"><b>' + d.item + '</b><span>' + d.supplier + ' · ' + d.qty + '</span></span><span class="chip">' + d.date + '</span></div>';
      }).join('');
    });
  }

  function openDeliveryModal() {
    var options = DATA.inventory.map(function (i) { return '<option value="' + i.name + '">' + i.name + '</option>'; }).join('');
    showModal('Log a delivery', '<div class="stack">' +
      '<label>Ingredient<select class="field" id="dl-item">' + options + '</select></label>' +
      '<label>Quantity<input class="field" id="dl-qty" placeholder="e.g. 20 L"></label>' +
      '<label>Supplier<input class="field" id="dl-supplier" placeholder="e.g. Medina Dairy"></label>' +
      '</div>',
      function () {
        var itemName = document.getElementById('dl-item').value;
        var qty = document.getElementById('dl-qty').value.trim();
        var supplier = document.getElementById('dl-supplier').value.trim();
        var item = DATA.inventory.find(function (i) { return i.name === itemName; });
        if (item) {
          var num = parseFloat(qty) || Math.max(1, Math.round(item.reorder * 0.6));
          item.stock = Math.round((item.stock + num) * 10) / 10;
          item.low = item.stock <= item.reorder;
          if (supplier) item.supplier = supplier;
        }
        DATA.deliveries.unshift({ date: 'Today', supplier: supplier || (item ? item.supplier : ''), item: itemName, qty: qty || '—' });
        renderInventory();
        renderDeliveries();
        renderOverview();
        showToast('Delivery logged — stock updated.');
      });
  }
  window.openDeliveryModal = openDeliveryModal;

  // ================= Modal =================
  function showModal(title, bodyHtml, onSave) {
    document.getElementById('modal-content').innerHTML =
      '<h3>' + title + '</h3>' + bodyHtml +
      '<div class="modal-actions"><button class="btn-ghost btn" id="modal-cancel">Cancel</button><button class="btn" id="modal-save">Save</button></div>';
    document.getElementById('modal-backdrop').classList.add('show');
    document.getElementById('modal-cancel').addEventListener('click', hideModal);
    document.getElementById('modal-save').addEventListener('click', function () { onSave(); hideModal(); });
  }
  function hideModal() { document.getElementById('modal-backdrop').classList.remove('show'); }
  document.getElementById('modal-backdrop').addEventListener('click', function (e) {
    if (e.target === this) hideModal();
  });

  // Escape closes whatever overlay is open; Enter in a modal text field saves it
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (document.getElementById('modal-backdrop').classList.contains('show')) { hideModal(); return; }
      if (document.getElementById('txn-drawer-backdrop').classList.contains('show')) { closeTransactions(); return; }
      if (document.getElementById('recipe-page').classList.contains('show')) { closeRecipe(); return; }
      document.getElementById('whoami-wrap').classList.remove('open');
      return;
    }
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && document.getElementById('modal-backdrop').classList.contains('show')) {
      var save = document.getElementById('modal-save');
      if (save) { e.preventDefault(); save.click(); }
    }
  });

  // ================= Overview =================
  function sumLastDays(n) {
    return DATA.sales_days.slice(-n).reduce(function (a, d) { return a + d.v; }, 0);
  }
  function sumThisWeek() {
    // last 4 entries ~ Mon..today in our demo (today = last day in sales_days)
    var dow = new Date(DATA.sales_days[DATA.sales_days.length - 1].iso).getDay(); // 0 Sun..6 Sat
    var daysSinceMon = (dow + 6) % 7;
    return sumLastDays(daysSinceMon + 1);
  }
  function sumThisMonth() {
    var lastIso = DATA.sales_days[DATA.sales_days.length - 1].iso;
    var month = lastIso.slice(0, 7);
    return DATA.sales_days.filter(function (d) { return d.iso.slice(0, 7) === month; }).reduce(function (a, d) { return a + d.v; }, 0);
  }
  function monthLabel() {
    var d = new Date(DATA.sales_days[DATA.sales_days.length - 1].iso);
    return d.toLocaleDateString('en-GB', { month: 'long' });
  }

  function renderOverview() {
    var today = DATA.sales_days[DATA.sales_days.length - 1].v;
    var week = sumThisWeek();
    var month = sumThisMonth();
    document.getElementById('ov-today-sales').textContent = money(today);
    document.getElementById('stat-today').textContent = money(today);
    document.getElementById('stat-week').textContent = money(week);
    document.getElementById('stat-month').textContent = money(month);
    document.getElementById('stat-month-label').textContent = monthLabel();
    document.getElementById('sales-today').textContent = money(today);
    document.getElementById('sales-week').textContent = money(week);
    document.getElementById('sales-month').textContent = money(month);
    document.getElementById('sales-month-label').textContent = monthLabel();

    var lowCount = DATA.inventory.filter(function (i) { return i.low; }).length;
    var statLow = document.getElementById('stat-lowstock');
    statLow.setAttribute('data-count', lowCount);
    statLow.textContent = '0';

    // on shift "now": today's filled shifts
    var onNow = rosterEntry(DEMO_TODAY).shifts.filter(function (s) { return s.staff; });
    document.getElementById('ov-onshift-count').textContent = onNow.length + ' today';
    document.getElementById('ov-onshift').innerHTML = onNow.map(function (s) {
      var st = staffById(s.staff);
      return '<div class="on-now-row">' + initialsAvatar(st) + '<div class="on-now-meta"><div class="name">' + st.name + '</div><div class="sub">' + s.label + ' · ' + s.time + '</div></div></div>';
    }).join('') || '<div class="empty" style="margin:8px">No one scheduled yet today.</div>';

    var lowList = DATA.inventory.filter(function (i) { return i.low; }).slice(0, 5);
    document.getElementById('ov-lowstock').innerHTML = lowList.map(function (i) {
      return '<div class="mini-row"><span class="mi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg></span>' +
        '<span class="meta"><b>' + i.name + '</b><span>' + i.stock + ' ' + i.unit + ' left · reorder at ' + i.reorder + '</span></span></div>';
    }).join('') || '<div class="empty" style="margin:8px">Nothing low right now.</div>';
  }

  // ================= Sales chart =================
  var salesRange = 'day';
  document.getElementById('sales-range').addEventListener('click', function (e) {
    var btn = e.target.closest('.pill-tab');
    if (!btn) return;
    document.querySelectorAll('#sales-range .pill-tab').forEach(function (b) { b.classList.toggle('active', b === btn); });
    salesRange = btn.getAttribute('data-range');
    drawSalesChart(salesRange);
  });

  function aggregate(range) {
    var days = DATA.sales_days;
    if (range === 'day') {
      var slice = days.slice(-14);
      document.getElementById('chart-title').textContent = 'Last 14 days';
      return slice.map(function (d) { return { label: d.date, v: d.v }; });
    }
    if (range === 'week') {
      document.getElementById('chart-title').textContent = 'Last 8 weeks';
      var weeks = [];
      for (var i = days.length; i > 0; i -= 7) {
        var chunk = days.slice(Math.max(0, i - 7), i);
        if (!chunk.length) continue;
        var sum = chunk.reduce(function (a, d) { return a + d.v; }, 0);
        weeks.unshift({ label: chunk[0].date.split(' ')[0] + ' ' + chunk[0].date.split(' ')[1], v: sum });
      }
      return weeks.slice(-8);
    }
    // month
    document.getElementById('chart-title').textContent = 'By month';
    var byMonth = {};
    var order = [];
    days.forEach(function (d) {
      var key = d.iso.slice(0, 7);
      if (!byMonth[key]) { byMonth[key] = 0; order.push(key); }
      byMonth[key] += d.v;
    });
    return order.map(function (key) {
      var dt = new Date(key + '-01');
      return { label: dt.toLocaleDateString('en-GB', { month: 'short' }), v: byMonth[key] };
    });
  }

  function smoothPath(points) {
    if (points.length < 2) return '';
    var d = 'M' + points[0][0] + ',' + points[0][1];
    for (var i = 0; i < points.length - 1; i++) {
      var p0 = points[i === 0 ? 0 : i - 1], p1 = points[i], p2 = points[i + 1], p3 = points[i + 2 < points.length ? i + 2 : i + 1];
      var cp1x = p1[0] + (p2[0] - p0[0]) / 6, cp1y = p1[1] + (p2[1] - p0[1]) / 6;
      var cp2x = p2[0] - (p3[0] - p1[0]) / 6, cp2y = p2[1] - (p3[1] - p1[1]) / 6;
      d += ' C' + cp1x + ',' + cp1y + ' ' + cp2x + ',' + cp2y + ' ' + p2[0] + ',' + p2[1];
    }
    return d;
  }

  function drawSalesChart(range) {
    var container = document.getElementById('sales-chart');
    if (!container) return;
    var rows = aggregate(range);
    var vals = rows.map(function (r) { return r.v; });
    var maxV = Math.max.apply(null, vals) * 1.12;
    var w = 460, h = 170, padX = 10, padY = 14, n = rows.length;
    var x = function (i) { return n === 1 ? w / 2 : padX + (i / (n - 1)) * (w - padX * 2); };
    var y = function (v) { return padY + (1 - v / maxV) * (h - padY * 2); };
    var pts = rows.map(function (r, i) { return [x(i), y(r.v)]; });
    var path = smoothPath(pts);
    var area = path + ' L' + x(n - 1) + ',' + (h - padY) + ' L' + x(0) + ',' + (h - padY) + ' Z';
    var grid = [0.25, 0.5, 0.75].map(function (f) {
      var gy = padY + f * (h - padY * 2);
      return '<line x1="' + padX + '" y1="' + gy + '" x2="' + (w - padX) + '" y2="' + gy + '" stroke="var(--line)" stroke-width="1" stroke-dasharray="3 4"/>';
    }).join('');
    var dots = pts.map(function (p) {
      return '<circle class="anim-pop" cx="' + p[0] + '" cy="' + p[1] + '" r="3" fill="var(--gold-tx)" stroke="#fff" stroke-width="1.5"/>';
    }).join('');

    container.innerHTML =
      '<svg id="sales-svg" viewBox="0 0 ' + w + ' ' + h + '" style="width:100%;height:150px;display:block;overflow:visible" preserveAspectRatio="none">' +
      '<defs><linearGradient id="salesFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="var(--gold)" stop-opacity="0.28"/><stop offset="100%" stop-color="var(--gold)" stop-opacity="0"/></linearGradient></defs>' +
      grid +
      '<path id="sales-area" d="' + area + '" fill="url(#salesFill)" stroke="none" style="opacity:0;transition:opacity .7s ease .25s"/>' +
      '<path id="sales-line" d="' + path + '" fill="none" stroke="var(--gold-tx)" stroke-width="2.5" stroke-linecap="round"/>' +
      dots +
      '<line id="sales-crosshair" x1="0" y1="' + padY + '" x2="0" y2="' + (h - padY) + '" stroke="var(--line-strong)" stroke-width="1" opacity="0"/>' +
      '<circle id="sales-hover" r="4.5" fill="var(--gold-tx)" stroke="#fff" stroke-width="2" opacity="0"/>' +
      '<rect x="0" y="0" width="' + w + '" height="' + h + '" fill="transparent" id="sales-hit" style="cursor:crosshair"/>' +
      '</svg><div class="chart-tooltip" id="sales-tooltip"></div>';

    var linePath = document.getElementById('sales-line');
    var len = linePath.getTotalLength();
    linePath.style.transition = 'none';
    linePath.style.strokeDasharray = len;
    linePath.style.strokeDashoffset = len;
    void linePath.getBoundingClientRect();
    linePath.style.transition = 'stroke-dashoffset 1.05s cubic-bezier(.22,.36,.36,1)';
    requestAnimationFrame(function () {
      linePath.style.strokeDashoffset = '0';
      document.getElementById('sales-area').style.opacity = '1';
    });
    animatePops(container, '.anim-pop', 40);

    var hit = document.getElementById('sales-hit');
    var tooltip = document.getElementById('sales-tooltip');
    var crosshair = document.getElementById('sales-crosshair');
    var hover = document.getElementById('sales-hover');
    var svg = document.getElementById('sales-svg');
    function showAt(clientX) {
      var rect = svg.getBoundingClientRect();
      var px = (clientX - rect.left) / rect.width * w;
      var i = n === 1 ? 0 : Math.round(((px - padX) / (w - padX * 2)) * (n - 1));
      i = Math.max(0, Math.min(n - 1, i));
      var dx = x(i);
      crosshair.setAttribute('x1', dx); crosshair.setAttribute('x2', dx); crosshair.setAttribute('opacity', '1');
      hover.setAttribute('cx', dx); hover.setAttribute('cy', y(rows[i].v)); hover.setAttribute('opacity', '1');
      var cRect = container.getBoundingClientRect();
      tooltip.style.left = (rect.left - cRect.left + (dx / w) * rect.width) + 'px';
      tooltip.style.top = (rect.top - cRect.top + (y(rows[i].v) / h) * rect.height - 10) + 'px';
      tooltip.innerHTML = '<b>' + rows[i].label + '</b><br>' + money(rows[i].v);
      tooltip.classList.add('show');
    }
    hit.addEventListener('mousemove', function (e) { showAt(e.clientX); });
    hit.addEventListener('touchstart', function (e) { showAt(e.touches[0].clientX); }, { passive: true });
    hit.addEventListener('touchmove', function (e) { showAt(e.touches[0].clientX); }, { passive: true });
    hit.addEventListener('mouseleave', function () { tooltip.classList.remove('show'); crosshair.setAttribute('opacity', '0'); hover.setAttribute('opacity', '0'); });
  }

  function renderTopFlavours() {
    var el = document.getElementById('top-flavours');
    var max = DATA.top_flavours[0].scoops;
    el.innerHTML = DATA.top_flavours.map(function (f, i) {
      return '<div class="flavour-row anim-fade in"><span class="rank">' + (i + 1) + '</span>' +
        '<div><div style="font-weight:700;font-size:12.5px;margin-bottom:3px">' + f.name + '</div>' +
        '<div class="flavour-bar"><span class="bar-fill" data-w="' + Math.round(f.scoops / max * 100) + '%"></span></div></div>' +
        '<span class="scoops">' + f.scoops + ' scoops</span></div>';
    }).join('');
    animateBars(el);
  }

  function animateThreshold() {
    var target = 7667;
    var monthTotal = sumThisMonth();
    var pct = Math.min(100, Math.round(monthTotal / target * 100));
    document.getElementById('threshold-value').textContent = money(monthTotal) + ' this month';
    var fill = document.getElementById('threshold-fill');
    fill.style.width = '0%';
    setTimeout(function () { fill.style.width = pct + '%'; }, 80);
  }

  // ================= Transactions =================
  var txnRange = '14';
  function transactionsInRange() {
    var days = txnRange === 'today' ? 0 : txnRange === '7' ? 6 : 13;
    var start = addDays(DEMO_TODAY, -days);
    return DATA.transactions.filter(function (t) { return t.iso >= start && t.iso <= DEMO_TODAY; });
  }
  function openTransactions() {
    renderTransactions();
    document.getElementById('txn-drawer').classList.add('show');
    document.getElementById('txn-drawer-backdrop').classList.add('show');
  }
  window.openTransactions = openTransactions;
  function closeTransactions() {
    document.getElementById('txn-drawer').classList.remove('show');
    document.getElementById('txn-drawer-backdrop').classList.remove('show');
  }
  window.closeTransactions = closeTransactions;
  function setTxnRange(r) {
    txnRange = r;
    renderTransactions();
  }
  window.setTxnRange = setTxnRange;

  function renderTransactions() {
    var list = transactionsInRange().slice().sort(function (a, b) {
      if (a.iso !== b.iso) return a.iso < b.iso ? 1 : -1;
      return a.t < b.t ? 1 : -1;
    });
    var total = list.reduce(function (a, t) { return a + t.amt; }, 0);
    var avg = list.length ? total / list.length : 0;
    var rangeLabel = txnRange === 'today' ? 'today' : txnRange === '7' ? 'last 7 days' : 'last 14 days';
    var payClass = { Card: 'card', 'Apple Pay': 'apple', Cash: 'cash' };

    var rows = list.map(function (t) {
      var dateLabel = dateOf(t.iso).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
      return '<tr><td style="white-space:nowrap">' + dateLabel + '</td><td style="white-space:nowrap">' + t.t + '</td><td>' + t.desc + '</td>' +
        '<td><span class="txn-pay ' + payClass[t.pay] + '">' + t.pay + '</span></td>' +
        '<td style="text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap">£' + t.amt.toFixed(2) + '</td></tr>';
    }).join('');

    var tabs = ['today', '7', '14'].map(function (r) {
      var label = r === 'today' ? 'Today' : r === '7' ? 'Last 7 days' : 'Last 14 days';
      return '<button class="pill-tab' + (txnRange === r ? ' active' : '') + '" onclick="setTxnRange(\'' + r + '\')">' + label + '</button>';
    }).join('');

    document.getElementById('txn-drawer').innerHTML =
      '<div class="txn-head">' +
      '<button class="drawer-close plain" onclick="closeTransactions()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>' +
      '<span class="eyebrow">Point of sale</span><h2>Transactions</h2>' +
      '<div class="pill-tabs" style="margin-top:14px;display:inline-flex">' + tabs + '</div>' +
      '<div class="txn-summary">' +
      '<div><b>' + list.length + '</b><span>Transactions</span></div>' +
      '<div><b>' + money(total) + '</b><span>Total, ' + rangeLabel + '</span></div>' +
      '<div><b>£' + avg.toFixed(2) + '</b><span>Average</span></div>' +
      '</div></div>' +
      '<div class="txn-body"><div style="overflow-x:auto">' +
      '<table class="data"><thead><tr><th>Date</th><th>Time</th><th>Items</th><th>Payment</th><th style="text-align:right">Amount</th></tr></thead>' +
      '<tbody>' + (rows || '<tr><td colspan="5"><div class="empty">No transactions in this period.</div></td></tr>') + '</tbody></table>' +
      '</div></div>';
  }

  // ================= Init =================
  function init() {
    renderWhoAmI();
    renderRecipes();
    renderRoster();
    renderInventory();
    renderDeliveries();
    renderOverview();
    renderTopFlavours();

    var today = dateOf(DEMO_TODAY);
    document.getElementById('ov-date').textContent = today.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });

    var allTxns = DATA.transactions;
    var txnAvg = allTxns.reduce(function (a, t) { return a + t.amt; }, 0) / allTxns.length;
    document.getElementById('txn-avg').textContent = '£' + txnAvg.toFixed(2);

    moveNavIndicator('overview');
    var overviewView = document.getElementById('view-overview');
    requestAnimationFrame(function () {
      overviewView.classList.add('in');
      animateViewEntrance('overview');
    });

    if (isAuthed()) {
      var screen = document.getElementById('login-screen');
      screen.style.transition = 'none';
      screen.classList.add('hide');
    } else {
      showLogin();
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
