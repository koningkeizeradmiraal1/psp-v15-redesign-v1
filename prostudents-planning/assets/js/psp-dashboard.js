/* Pro Students Planning — Front-end Dashboard v1.4 */
(function () {
  'use strict';

  var PSP_VAARDIGHEDEN = {
    catering:            'Catering',
    lopen_met_3_borden:  'Lopen met 3 borden',
    lopen_met_plateau:   'Lopen met plateau',
    housekeeping:        'Housekeeping',
    schoonmaak:          'Schoonmaak',
    productiewerk:       'Productiewerk',
    inpakwerkzaamheden:  'Inpakwerkzaamheden',
    bediening:           'Bediening',
    bar:                 'Bar',
  };

  var DAG_KEYS  = ['ma','di','wo','do','vr','za'];
  var DAG_NAMES = { ma:'Ma', di:'Di', wo:'Wo', do:'Do', vr:'Vr', za:'Za' };
  var MONTHS    = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
  var VAARDIGHEDEN_LABELS = {
    catering:'Catering', lopen_met_3_borden:'3 borden', lopen_met_plateau:'Plateau',
    housekeeping:'Housekeeping', schoonmaak:'Schoonmaak', productiewerk:'Productiewerk',
    inpakwerkzaamheden:'Inpakwerk', bediening:'Bediening', bar:'Bar',
  };

  var state = {
    week:                mondayOfCurrentWeek(),
    data:                { beschikbaarheid: [], diensten: [] },
    selectedDienst:      null,
    filterOpdrachtgever: '',
    filterVaardigheid:   '',
    // Inplannen-tab selecties
    ipDienst:  null,
    ipStudent: null,
  };

  /* ── Status helpers ── */
  function isIngepland(d) { return !!(d.koppeling); }
  function dienstBadgeCls(d)  { return isIngepland(d) ? 'psp-badge-ingepland' : 'psp-badge-open'; }
  function dienstBadgeTxt(d)  { return isIngepland(d) ? '✓ Ingepland' : 'Open'; }

  /* ════ Init ════ */
  document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('psp-fullpage');
    var adminBar = document.getElementById('wpadminbar');
    if (adminBar) document.documentElement.style.setProperty('--psp-adminbar', adminBar.offsetHeight + 'px');
    addToast();
    initTabs();
    initWeekNav();
    initDienstModal();
    initBeschikbaarheidModal();
    initFilter();
    initTariefModal();
    loadWeek();
    laadTarievenBadge();
  });

  /* ════ Tabs ════ */
  function initTabs() {
    document.querySelectorAll('.psp-tab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.psp-tab').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('.psp-tab-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = document.getElementById('psp-tab-' + btn.dataset.tab);
        if (panel) panel.style.display = '';
        if (btn.dataset.tab === 'diensten')  renderDienstenTabel();
        if (btn.dataset.tab === 'studenten') renderStudentenTabel();
        if (btn.dataset.tab === 'inplannen') renderInplannenView();
        if (btn.dataset.tab === 'beheer')    initBeheerTab();
      });
    });
  }

  /* ════ Week nav ════ */
  function initWeekNav() {
    document.getElementById('psp-prev-week').addEventListener('click', function () { state.week = addDays(state.week, -7); loadWeek(); });
    document.getElementById('psp-next-week').addEventListener('click', function () { state.week = addDays(state.week,  7); loadWeek(); });
  }

  function loadWeek() {
    setLoader(true);
    ajax('psp_week_data', { week_start: fmt(state.week) }, function (data) {
      state.data = data; state.selectedDienst = null; state.ipDienst = null; state.ipStudent = null;
      updateWeekLabel(); updateFilterDropdown(); applyFilter();
      setLoader(false);
    }, function () { toast('Laden mislukt.', 'error'); setLoader(false); });
  }

  function updateWeekLabel() {
    document.getElementById('psp-week-label').textContent = fmtNL(state.week) + ' – ' + fmtNL(addDays(state.week, 5));
  }

  /* ════ Filter ════ */
  function initFilter() {
    function updateClearBtn() {
      document.getElementById('psp-filter-clear').style.display =
        (state.filterOpdrachtgever || state.filterVaardigheid) ? '' : 'none';
    }
    document.getElementById('psp-filter-opdrachtgever').addEventListener('change', function () {
      state.filterOpdrachtgever = this.value; updateClearBtn(); applyFilter();
    });
    document.getElementById('psp-filter-vaardigheid').addEventListener('change', function () {
      state.filterVaardigheid = this.value; updateClearBtn(); applyFilter();
    });
    document.getElementById('psp-filter-clear').addEventListener('click', function () {
      state.filterOpdrachtgever = ''; state.filterVaardigheid = '';
      document.getElementById('psp-filter-opdrachtgever').value = '';
      document.getElementById('psp-filter-vaardigheid').value   = '';
      this.style.display = 'none';
      document.getElementById('psp-filter-result').textContent = '';
      applyFilter();
    });
  }

  function updateFilterDropdown() {
    var sel = document.getElementById('psp-filter-opdrachtgever');
    var cur = sel.value;
    var names = [];
    state.data.diensten.forEach(function (d) { if (names.indexOf(d.opdrachtgever) === -1) names.push(d.opdrachtgever); });
    names.sort();
    sel.innerHTML = '<option value="">— Alle —</option>' +
      names.map(function (n) { return '<option value="' + esc(n) + '"' + (n === cur ? ' selected' : '') + '>' + esc(n) + '</option>'; }).join('');
    if (names.indexOf(cur) === -1) { state.filterOpdrachtgever = ''; document.getElementById('psp-filter-clear').style.display = 'none'; }
  }

  function applyFilter() {
    var og = state.filterOpdrachtgever, vf = state.filterVaardigheid;
    var fd = og ? state.data.diensten.filter(function (d) { return d.opdrachtgever === og; }) : state.data.diensten;
    var resultEl = document.getElementById('psp-filter-result');
    if (og || vf) {
      var open = fd.filter(function (d) { return !isIngepland(d); }).length;
      resultEl.textContent = fd.length + ' dienst(en) — ' + open + ' open, ' + (fd.length - open) + ' ingepland';
    } else { resultEl.textContent = ''; }
    var fs = state.data.beschikbaarheid;
    if (og) {
      var rd = {};
      fd.forEach(function (d) { var dk = dagKey(d.datum); if (dk) rd[dk] = true; });
      fs = fs.filter(function (s) { return Object.keys(rd).some(function (dk) { return !!s.dagen[dk]; }); });
    }
    if (vf) { fs = fs.filter(function (s) { return s.vaardigheden && s.vaardigheden.indexOf(vf) !== -1; }); }
    renderSidebar(fd);
    renderGrid(fd, fs);
    var at = document.querySelector('.psp-tab.active');
    if (at) {
      if (at.dataset.tab === 'diensten')  renderDienstenTabel(fd);
      if (at.dataset.tab === 'studenten') renderStudentenTabel(fs);
      if (at.dataset.tab === 'inplannen') renderInplannenView(fd, fs);
    }
  }

  function dagKey(datum) {
    var map = {1:'ma',2:'di',3:'wo',4:'do',5:'vr',6:'za'};
    return map[new Date(datum).getDay()] || null;
  }

  function renderVaardigheden(v) {
    if (!v || !v.length) return '';
    return '<div class="psp-vaard-tags">' + v.map(function (x) {
      return '<span class="psp-vaard-tag">' + esc(VAARDIGHEDEN_LABELS[x] || x) + '</span>';
    }).join('') + '</div>';
  }

  /* ════ Sidebar (weekrooster-tab) ════ */
  function renderSidebar(diensten) {
    if (diensten === undefined) diensten = state.data.diensten;
    var list = document.getElementById('psp-diensten-lijst');
    var openTotaal = diensten.filter(function (d) { return !isIngepland(d); }).length;
    var hdr = document.getElementById('psp-sidebar-open-count');
    if (hdr) { hdr.textContent = openTotaal > 0 ? openTotaal + ' open' : ''; hdr.style.display = openTotaal > 0 ? '' : 'none'; }
    if (!diensten.length) { list.innerHTML = '<p class="psp-empty-msg">Geen diensten.</p>'; return; }

    // Groepeer per opdrachtgever, open eerst
    var groepen = {};
    diensten.forEach(function (d) { var og = d.opdrachtgever || '?'; if (!groepen[og]) groepen[og] = []; groepen[og].push(d); });
    var namen = Object.keys(groepen).sort();
    var html = '';
    namen.forEach(function (og) {
      var groep = groepen[og];
      groep.sort(function (a, b) { return (isIngepland(a) ? 1 : 0) - (isIngepland(b) ? 1 : 0); });
      var nOpen = groep.filter(function (d) { return !isIngepland(d); }).length;
      html += '<div class="psp-groep">';
      html += '<div class="psp-groep-header">' + esc(og.toUpperCase());
      if (nOpen) html += ' <span class="psp-groep-open-badge">' + nOpen + ' open</span>';
      html += '</div>';
      groep.forEach(function (d) {
        var sel = (state.selectedDienst && state.selectedDienst.id === d.id) ? ' selected' : '';
        html += '<div class="psp-dienst-card ' + (isIngepland(d) ? 'ingepland' : 'open') + sel + '" data-id="' + d.id + '">' +
          '<button class="psp-dienst-card-edit" data-id="' + d.id + '" title="Bewerken">✏</button>' +
          '<div class="psp-dienst-card-title">' + esc(d.titel) + '</div>' +
          '<div class="psp-dienst-card-meta">' + fmtNL(new Date(d.datum)) + ' · ' + d.tijdstip_van + '–' + d.tijdstip_tot + (d.locatie ? '<br>' + esc(d.locatie) : '') + '</div>' +
          '<span class="psp-dienst-card-badge ' + dienstBadgeCls(d) + '">' + dienstBadgeTxt(d) + '</span>' +
          (isIngepland(d) && d.koppeling ? '<div class="psp-dienst-card-student">👤 ' + esc(d.koppeling.naam) + '</div>' : '<div class="psp-open-hint">Klik om studenten te zien</div>') +
          (isIngepland(d) && d.koppeling ? wbKnopHtml(d) : '') +
          '</div>';
      });
      html += '</div>';
    });
    list.innerHTML = html;
    // WB knop click handler (moet VOOR kaart-click staan)
    list.querySelectorAll('.psp-wb-stuur-kaart-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        toonWbStuurModal({
          dienst_id:     btn.dataset.dienstId,
          student_email: btn.dataset.email,
          student_naam:  btn.dataset.naam,
          opdrachtgever: btn.dataset.og,
        });
      });
    });

    list.querySelectorAll('.psp-dienst-card').forEach(function (card) {
      card.addEventListener('click', function (e) {
        if (e.target.closest('.psp-dienst-card-edit')) return;
        if (e.target.closest('.psp-wb-stuur-kaart-btn')) return;
        var id = parseInt(card.dataset.id);
        var d = state.data.diensten.find(function (x) { return x.id === id; });
        state.selectedDienst = (state.selectedDienst && state.selectedDienst.id === id) ? null : d;
        applyFilter();
      });
    });
    list.querySelectorAll('.psp-dienst-card-edit').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var d = state.data.diensten.find(function (x) { return x.id === parseInt(btn.dataset.id); });
        if (d) openDienstModal(d);
      });
    });
  }

  /* ════ Grid (weekrooster-tab) ════ */
  function renderGrid(diensten, studenten) {
    if (!diensten)  diensten  = state.data.diensten;
    if (!studenten) studenten = state.data.beschikbaarheid;
    var wrap = document.getElementById('psp-grid-wrap');
    if (!studenten.length) {
      wrap.innerHTML = '<p class="psp-empty-msg" style="padding:40px">' +
        (state.filterOpdrachtgever ? 'Geen studenten beschikbaar voor ' + esc(state.filterOpdrachtgever) + '.' : 'Geen beschikbaarheid ingediend.') + '</p>';
      return;
    }
    var dates = {}; DAG_KEYS.forEach(function (dk, i) { dates[dk] = addDays(state.week, i); });
    var dpd = {};
    diensten.forEach(function (d) { if (!dpd[d.datum]) dpd[d.datum] = []; dpd[d.datum].push(d); });
    var openPerDag = {};
    DAG_KEYS.forEach(function (dk) { openPerDag[dk] = (dpd[fmt(dates[dk])] || []).filter(function (d) { return !isIngepland(d); }).length; });

    var html = '<table class="psp-grid"><thead><tr><th class="psp-col-naam">Student</th>';
    DAG_KEYS.forEach(function (dk) {
      var sel = state.selectedDienst && fmt(dates[dk]) === state.selectedDienst.datum;
      html += '<th' + (sel ? ' class="psp-th-selected"' : '') + '>' + DAG_NAMES[dk] + '<br><small style="font-weight:400;opacity:.7">' + fmtNL(dates[dk]) + '</small>';
      if (openPerDag[dk]) html += '<br><span class="psp-dag-open-badge">' + openPerDag[dk] + ' open</span>';
      html += '</th>';
    });
    html += '</tr></thead><tbody>';

    studenten.forEach(function (s) {
      html += '<tr><td class="psp-col-naam"><div style="display:flex;align-items:flex-start;justify-content:space-between;gap:4px"><div>' +
        '<div class="psp-student-naam">' + esc(s.naam) + '</div>' +
        '<div class="psp-student-meta">' + esc(s.email) + '</div>' +
        (s.telefoon ? '<div class="psp-student-meta">📞 ' + esc(s.telefoon) + '</div>' : '') +
        (s.voorkeur ? '<div class="psp-student-voorkeur" title="' + esc(s.voorkeur) + '">💬 Voorkeur</div>' : '') +
        renderVaardigheden(s.vaardigheden) +
        '</div><button class="psp-delete-student" data-id="' + s.id + '" title="Verwijderen">🗑</button></div></td>';

      DAG_KEYS.forEach(function (dk) {
        var dag = s.dagen[dk], datum = fmt(dates[dk]);
        var linked = null;
        Object.keys(s.koppelingen).forEach(function (did) {
          var d = state.data.diensten.find(function (x) { return x.id === parseInt(did); });
          if (d && d.datum === datum) linked = d;
        });
        var open = (dpd[datum] || []).filter(function (d) { return !isIngepland(d); });
        var hl = state.selectedDienst && state.selectedDienst.datum === datum;
        var cls = 'psp-cell ';
        if (linked) cls += 'psp-cell-ingepland';
        else if (dag && hl) cls += 'psp-cell-highlight';
        else if (dag && open.length) cls += 'psp-cell-beschikbaar psp-cell-heeft-open';
        else if (dag) cls += 'psp-cell-beschikbaar';
        else cls += 'psp-cell-leeg';

        html += '<td class="' + cls + '" data-student="' + s.id + '" data-datum="' + datum + '">';
        if (linked) {
          html += '<div class="psp-assigned-dienst">' + esc(linked.opdrachtgever) + '</div>' +
            '<div class="psp-assigned-time">' + linked.tijdstip_van + '–' + linked.tijdstip_tot + '</div>' +
            '<div class="psp-cell-actions">' +
            '<button class="psp-unassign-btn" data-dienst-id="' + linked.id + '">✕</button>' +
            '<button class="psp-ziek-btn" data-dienst-id="' + linked.id + '" data-student-id="' + s.id + '" data-student-naam="' + esc(s.naam) + '">🤒 Ziek</button>' +
            '</div>';
        } else if (dag) {
          html += '<div class="psp-cell-time">' + dag.van + '–' + dag.tot + '</div>';
          var kanIn = open.length > 0 || (state.selectedDienst && state.selectedDienst.datum === datum && !isIngepland(state.selectedDienst));
          if (kanIn) html += '<button class="psp-assign-btn' + (hl ? ' psp-assign-btn-highlight' : '') + '" data-student-id="' + s.id + '" data-datum="' + datum + '">' +
            (open.length === 1 ? '📌 Inplannen' : '📌 Inplannen (' + open.length + ')') + '</button>';
        } else {
          html += '<span class="psp-cell-nvt">—</span>';
        }
        html += '</td>';
      });
      html += '</tr>';
    });
    html += '</tbody></table>';
    wrap.innerHTML = html;

    wrap.querySelectorAll('.psp-assign-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var s = state.data.beschikbaarheid.find(function (x) { return x.id === parseInt(btn.dataset.studentId); });
        if (s) openKoppelModal(s, btn.dataset.datum, diensten);
      });
    });
    wrap.querySelectorAll('.psp-unassign-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!confirm('Koppeling verwijderen?')) return;
        ajax('psp_ontkoppel', { dienst_id: btn.dataset.dienstId }, function () { toast('Koppeling verwijderd.', 'success'); loadWeek(); });
      });
    });
    wrap.querySelectorAll('.psp-ziek-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var d = state.data.diensten.find(function (x) { return x.id === parseInt(btn.dataset.dienstId); });
        if (d) openVervangerModal(d, parseInt(btn.dataset.studentId), btn.dataset.studentNaam);
      });
    });
    wrap.querySelectorAll('.psp-delete-student').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var s = state.data.beschikbaarheid.find(function (x) { return x.id === parseInt(btn.dataset.id); });
        if (!confirm('Beschikbaarheid van ' + (s ? s.naam : 'student') + ' verwijderen?')) return;
        ajax('psp_delete_beschikbaarheid', { beschikbaarheid_id: btn.dataset.id }, function () { toast('Verwijderd.', 'success'); loadWeek(); });
      });
    });
  }

  /* ════ Inplannen-view (studenten links, diensten rechts) ════ */
  function renderInplannenView(diensten, studenten) {
    if (!diensten)  diensten  = state.data.diensten;
    if (!studenten) studenten = state.data.beschikbaarheid;

    var sLijst = document.getElementById('psp-inplannen-studenten-lijst');
    var dLijst = document.getElementById('psp-inplannen-diensten-lijst');
    var sCount = document.getElementById('psp-inplannen-student-count');
    var dCount = document.getElementById('psp-inplannen-dienst-count');

    // Tel open diensten
    var openDiensten = diensten.filter(function (d) { return !isIngepland(d); });
    if (dCount) dCount.textContent = openDiensten.length + ' open';

    // ── RECHTS: diensten per datum ──
    var dates = {}; DAG_KEYS.forEach(function (dk, i) { dates[dk] = addDays(state.week, i); });
    var dpd = {};
    diensten.forEach(function (d) { if (!dpd[d.datum]) dpd[d.datum] = []; dpd[d.datum].push(d); });

    var dHtml = '';
    // Bepaal welke datums beschikbaar zijn voor geselecteerde student
    var studentBeschDatums = {};
    if (state.ipStudent) {
      DAG_KEYS.forEach(function (dk, i) {
        if (state.ipStudent.dagen[dk]) studentBeschDatums[fmt(addDays(state.week, i))] = state.ipStudent.dagen[dk];
      });
    }

    DAG_KEYS.forEach(function (dk, i) {
      var datum = fmt(addDays(state.week, i));
      var dagsD = dpd[datum];
      if (!dagsD || !dagsD.length) return;
      dagsD.sort(function (a, b) { return (isIngepland(a) ? 1 : 0) - (isIngepland(b) ? 1 : 0); });
      dHtml += '<div class="psp-ip-daggroep">';
      dHtml += '<div class="psp-ip-dagheader">' + DAG_NAMES[dk] + ' ' + fmtNL(addDays(state.week, i)) + '</div>';
      dagsD.forEach(function (d) {
        var sel = state.ipDienst && state.ipDienst.id === d.id;
        var studentBeschOp = state.ipStudent && studentBeschDatums[datum];
        var dimmed = state.ipStudent && !studentBeschDatums[datum];
        var cls = 'psp-ip-dienst-card' + (isIngepland(d) ? ' ingepland' : ' open') + (sel ? ' selected' : '') + (dimmed ? ' dimmed' : '');
        dHtml += '<div class="' + cls + '" data-id="' + d.id + '">';
        dHtml += '<div class="psp-ip-dienst-title">' + esc(d.titel) + '</div>';
        dHtml += '<div class="psp-ip-dienst-meta">' + esc(d.opdrachtgever) + ' · ' + d.tijdstip_van + '–' + d.tijdstip_tot + (d.locatie ? ' · ' + esc(d.locatie) : '') + '</div>';
        dHtml += '<span class="psp-dienst-card-badge ' + dienstBadgeCls(d) + '">' + dienstBadgeTxt(d) + '</span>';
        if (isIngepland(d) && d.koppeling) {
          dHtml += ' <span class="psp-ip-student-naam">👤 ' + esc(d.koppeling.naam) + '</span>';
        }
        // Als zowel student als dienst geselecteerd zijn en student beschikbaar is op deze dag → inplannen-knop
        if (!isIngepland(d) && state.ipStudent && studentBeschOp) {
          dHtml += '<button class="psp-ip-koppel-btn" data-dienst-id="' + d.id + '" data-student-id="' + state.ipStudent.id + '">' +
            '✓ Nu inplannen: ' + esc(state.ipStudent.naam) + '</button>';
        } else if (!isIngepland(d)) {
          dHtml += '<button class="psp-ip-select-btn" data-id="' + d.id + '">Kies student →</button>';
        }
        dHtml += '</div>';
      });
      dHtml += '</div>';
    });
    if (!dHtml) dHtml = '<p class="psp-empty-msg">Geen diensten deze week.</p>';
    dLijst.innerHTML = dHtml;

    // ── LINKS: studenten ──
    // Filteren op beschikbaarheid als er een dienst is geselecteerd
    var gefilterdeStudenten = studenten;
    if (state.ipDienst) {
      var dDk = dagKey(state.ipDienst.datum);
      gefilterdeStudenten = studenten.filter(function (s) { return !!s.dagen[dDk]; });
    }
    if (sCount) sCount.textContent = gefilterdeStudenten.length + ' student(en)';

    // Controleer wie al ingepland is op geselecteerde dienst
    var ingeplandSid = state.ipDienst && state.ipDienst.koppeling ? state.ipDienst.koppeling.beschikbaarheid_id : null;

    var sHtml = '';
    gefilterdeStudenten.forEach(function (s) {
      var sel = state.ipStudent && state.ipStudent.id === s.id;
      var isIp = ingeplandSid === s.id;
      // Controleer conflicten: student al ingepland op andere dienst dezelfde dag als ipDienst
      var conflict = false;
      if (state.ipDienst && !isIp) {
        Object.keys(s.koppelingen).forEach(function (did) {
          var d = state.data.diensten.find(function (x) { return x.id === parseInt(did); });
          if (d && d.datum === state.ipDienst.datum) conflict = true;
        });
      }

      var cls = 'psp-ip-student-card' + (sel ? ' selected' : '') + (isIp ? ' ingepland' : '') + (conflict ? ' conflict' : '');
      sHtml += '<div class="' + cls + '" data-id="' + s.id + '">';
      sHtml += '<div class="psp-ip-student-naam">' + esc(s.naam);
      if (isIp) sHtml += ' <span class="psp-badge-ingepland psp-dienst-card-badge">Ingepland</span>';
      if (conflict) sHtml += ' <span style="font-size:.65rem;color:#c0392b">⚠ Bezet</span>';
      sHtml += '</div>';
      sHtml += '<div class="psp-ip-student-meta">' + esc(s.email) + (s.telefoon ? ' · ' + esc(s.telefoon) : '') + '</div>';
      // Beschikbaarheidsdagen
      sHtml += '<div class="psp-ip-dagen">';
      DAG_KEYS.forEach(function (dk, i) {
        var dag = s.dagen[dk];
        var datum = fmt(addDays(state.week, i));
        var heeftOpenDienst = (dpd[datum] || []).some(function (d) { return !isIngepland(d); });
        var isGeselecteerdeDag = state.ipDienst && state.ipDienst.datum === datum;
        var dagCls = 'psp-ip-dag' + (dag ? (heeftOpenDienst ? ' heeft-open' : ' beschikbaar') : ' niet') + (isGeselecteerdeDag && dag ? ' geselecteerd' : '');
        sHtml += '<span class="' + dagCls + '" title="' + (dag ? dag.van + '–' + dag.tot : 'niet beschikbaar') + '">' + DAG_NAMES[dk] + '</span>';
      });
      sHtml += '</div>';
      if (s.vaardigheden && s.vaardigheden.length) sHtml += renderVaardigheden(s.vaardigheden);
      sHtml += '</div>';
    });
    if (!sHtml) sHtml = '<p class="psp-empty-msg">' + (state.ipDienst ? 'Geen studenten beschikbaar op ' + fmtNL(new Date(state.ipDienst.datum)) + '.' : 'Geen beschikbaarheid ingediend.') + '</p>';
    sLijst.innerHTML = sHtml;

    // ── Events: dienst selecteren ──
    dLijst.querySelectorAll('.psp-ip-dienst-card').forEach(function (card) {
      card.addEventListener('click', function (e) {
        if (e.target.closest('.psp-ip-koppel-btn') || e.target.closest('.psp-ip-select-btn')) return;
        var id = parseInt(card.dataset.id);
        var d  = state.data.diensten.find(function (x) { return x.id === id; });
        state.ipDienst = (state.ipDienst && state.ipDienst.id === id) ? null : d;
        renderInplannenView(diensten, studenten);
      });
    });
    // ── Events: student selecteren ──
    sLijst.querySelectorAll('.psp-ip-student-card').forEach(function (card) {
      card.addEventListener('click', function () {
        var id = parseInt(card.dataset.id);
        var s  = state.data.beschikbaarheid.find(function (x) { return x.id === id; });
        state.ipStudent = (state.ipStudent && state.ipStudent.id === id) ? null : s;
        renderInplannenView(diensten, studenten);
      });
    });
    // ── Events: direct inplannen ──
    dLijst.querySelectorAll('.psp-ip-koppel-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        btn.disabled = true; btn.textContent = '…';
        ajax('psp_koppel', { beschikbaarheid_id: btn.dataset.studentId, dienst_id: btn.dataset.dienstId }, function (res) {
          toast(res.message, 'success');
          var _did = btn.dataset.dienstId;
          state.ipDienst = null; state.ipStudent = null;
          loadWeek();
          if (res.eerste_keer) toonTariefModal(res.student_email, res.opdrachtgever, res.student_naam);
          // WB popup tonen na inplannen (alleen preview, niets wordt verstuurd)
          setTimeout(function () {
            toonWbStuurModal({ dienst_id: _did, student_email: res.email, student_naam: res.naam, opdrachtgever: res.opdrachtgever });
          }, res.eerste_keer ? 800 : 400);
        }, function (msg) { toast(msg || 'Inplannen mislukt.', 'error'); btn.disabled = false; });
      });
    });
    // ── Events: "Kies student" knop op dienst-kaart ──
    dLijst.querySelectorAll('.psp-ip-select-btn').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var id = parseInt(btn.dataset.id);
        state.ipDienst = state.data.diensten.find(function (x) { return x.id === id; });
        renderInplannenView(diensten, studenten);
        // Scroll linker panel naar boven
        var sL = document.getElementById('psp-inplannen-studenten-lijst');
        if (sL) sL.scrollTop = 0;
      });
    });
  }

  /* ════ Vervanger modal ════ */
  function openVervangerModal(dienst, ziekSid, ziekNaam) {
    var infoEl = document.getElementById('psp-vervanger-info');
    var lijstEl = document.getElementById('psp-vervanger-lijst');
    var dk = dagKey(dienst.datum);
    infoEl.innerHTML = '<div class="psp-vervanger-dienst"><strong>' + esc(dienst.titel) + '</strong> — ' + esc(dienst.opdrachtgever) + '<br>' + fmtNL(new Date(dienst.datum)) + ' · ' + dienst.tijdstip_van + '–' + dienst.tijdstip_tot + '</div>' +
      '<p class="psp-ziek-melding">🤒 <strong>' + esc(ziekNaam) + '</strong> is ziek. Kies een vervanger:</p>';
    var kand = state.data.beschikbaarheid.filter(function (s) {
      if (s.id === ziekSid || !s.dagen[dk]) return false;
      var dag = s.dagen[dk];
      if (dag.van > dienst.tijdstip_van || dag.tot < dienst.tijdstip_tot) return false;
      return !Object.keys(s.koppelingen).some(function (did) {
        var d = state.data.diensten.find(function (x) { return x.id === parseInt(did); });
        return d && d.datum === dienst.datum && parseInt(did) !== dienst.id;
      });
    });
    if (!kand.length) { lijstEl.innerHTML = '<p class="psp-empty-msg" style="color:#c0392b">Geen vervangers gevonden.</p>'; }
    else {
      lijstEl.innerHTML = kand.map(function (s) {
        return '<div class="psp-koppel-optie"><div class="psp-koppel-optie-info"><strong>' + esc(s.naam) + '</strong><br><span class="psp-koppel-optie-tijd">Beschikbaar ' + s.dagen[dk].van + '–' + s.dagen[dk].tot + '</span></div>' +
          '<button class="psp-koppel-btn-do" data-student-id="' + s.id + '">Inplannen</button></div>';
      }).join('');
      lijstEl.querySelectorAll('.psp-koppel-btn-do').forEach(function (btn) {
        btn.addEventListener('click', function () {
          btn.disabled = true; btn.textContent = '…';
          ajax('psp_ontkoppel', { dienst_id: dienst.id }, function () {
            ajax('psp_koppel', { beschikbaarheid_id: btn.dataset.studentId, dienst_id: dienst.id }, function (res) {
              toast(res.message, 'success'); document.getElementById('psp-modal-vervanger').style.display = 'none'; loadWeek();
              if (res.eerste_keer) toonTariefModal(res.student_email, res.opdrachtgever, res.student_naam);
              setTimeout(function () {
                toonWbStuurModal({ dienst_id: dienst.id, student_email: res.email, student_naam: res.naam, opdrachtgever: res.opdrachtgever });
              }, res.eerste_keer ? 800 : 400);
            }, function (msg) { toast(msg || 'Mislukt.', 'error'); btn.disabled = false; btn.textContent = 'Inplannen'; });
          });
        });
      });
    }
    document.getElementById('psp-modal-vervanger').style.display = 'flex';
  }

  /* ════ Koppel modal ════ */
  function openKoppelModal(student, datum, diensten) {
    if (!diensten) diensten = state.data.diensten;
    var dk = dagKey(datum), dag = student.dagen[dk];
    var open = diensten.filter(function (d) { return d.datum === datum && !isIngepland(d); });
    if (state.selectedDienst && state.selectedDienst.datum === datum && !isIngepland(state.selectedDienst)) {
      open.sort(function (a) { return a.id === state.selectedDienst.id ? -1 : 1; });
    }
    document.getElementById('psp-koppel-title').textContent = 'Student inplannen';
    document.getElementById('psp-koppel-info').innerHTML = '<strong>' + esc(student.naam) + '</strong> is beschikbaar' + (dag ? ' van <strong>' + dag.van + '–' + dag.tot + '</strong>' : '') + ' op ' + fmtNL(new Date(datum));
    var optiesEl = document.getElementById('psp-koppel-opties');
    if (!open.length) { optiesEl.innerHTML = '<p class="psp-empty-msg">Geen open diensten op ' + fmtNL(new Date(datum)) + '.</p>'; }
    else {
      optiesEl.innerHTML = open.map(function (d) {
        var prim = state.selectedDienst && d.id === state.selectedDienst.id;
        return '<div class="psp-koppel-optie' + (prim ? ' psp-koppel-optie-selected' : '') + '">' +
          '<div class="psp-koppel-optie-info"><strong>' + esc(d.titel) + '</strong> — ' + esc(d.opdrachtgever) +
          '<div class="psp-koppel-optie-tijd">⏰ ' + d.tijdstip_van + '–' + d.tijdstip_tot + (d.locatie ? ' · ' + esc(d.locatie) : '') + '</div></div>' +
          '<button class="psp-koppel-btn-do' + (prim ? ' psp-koppel-btn-primary' : '') + '" data-dienst-id="' + d.id + '" data-student-id="' + student.id + '">' +
          (prim ? '✓ Inplannen' : 'Inplannen') + '</button></div>';
      }).join('');
      optiesEl.querySelectorAll('.psp-koppel-btn-do').forEach(function (btn) {
        btn.addEventListener('click', function () {
          btn.disabled = true; btn.textContent = '…';
          ajax('psp_koppel', { beschikbaarheid_id: btn.dataset.studentId, dienst_id: btn.dataset.dienstId }, function (res) {
            toast(res.message, 'success'); document.getElementById('psp-modal-koppel').style.display = 'none'; loadWeek();
            if (res.eerste_keer) toonTariefModal(res.student_email, res.opdrachtgever, res.student_naam);
            setTimeout(function () {
              toonWbStuurModal({ dienst_id: btn.dataset.dienstId, student_email: res.email, student_naam: res.naam, opdrachtgever: res.opdrachtgever });
            }, res.eerste_keer ? 800 : 400);
          }, function (msg) { toast(msg || 'Mislukt.', 'error'); btn.disabled = false; btn.textContent = 'Inplannen'; });
        });
      });
    }
    document.getElementById('psp-modal-koppel').style.display = 'flex';
  }

  /* ════ Diensten tabel ════ */
  function renderDienstenTabel(diensten) {
    if (!diensten) diensten = state.data.diensten;
    var wrap = document.getElementById('psp-diensten-tabel-wrap');
    if (!diensten.length) { wrap.innerHTML = '<div class="psp-panel-body"><p class="psp-empty-msg">Geen diensten.</p></div>'; return; }
    var html = '<table class="psp-table"><thead><tr><th>Datum</th><th>Dienst</th><th>Opdrachtgever</th><th>Tijd</th><th>Status</th><th>Ingepland</th><th></th></tr></thead><tbody>';
    diensten.forEach(function (d) {
      var kop = d.koppeling;
      html += '<tr><td>' + fmtNL(new Date(d.datum)) + '</td>' +
        '<td><strong>' + esc(d.titel) + '</strong>' + (d.type_werk ? '<br><small>' + esc(d.type_werk) + '</small>' : '') + '</td>' +
        '<td>' + esc(d.opdrachtgever) + '</td>' +
        '<td>' + d.tijdstip_van + '–' + d.tijdstip_tot + '</td>' +
        '<td><span class="psp-status-badge ' + dienstBadgeCls(d) + '">' + dienstBadgeTxt(d) + '</span></td>' +
        '<td>' + (kop ? '<strong>' + esc(kop.naam) + '</strong><br><small>' + esc(kop.email) + '</small>' : '<span style="color:#ccc">—</span>') + '</td>' +
        '<td><button class="psp-tbl-action" data-id="' + d.id + '">Bewerken</button> <button class="psp-tbl-del" data-id="' + d.id + '" data-naam="' + esc(d.titel) + '">🗑</button></td></tr>';
    });
    html += '</tbody></table>';
    wrap.innerHTML = '<div class="psp-panel-body">' + html + '</div>';
    wrap.querySelectorAll('.psp-tbl-action').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var d = state.data.diensten.find(function (x) { return x.id === parseInt(btn.dataset.id); });
        if (d) openDienstModal(d);
      });
    });
    wrap.querySelectorAll('.psp-tbl-del').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!confirm('Dienst "' + btn.dataset.naam + '" verwijderen?')) return;
        ajax('psp_delete_dienst', { dienst_id: btn.dataset.id }, function () { toast('Verwijderd.', 'success'); loadWeek(); });
      });
    });
  }

  /* ════ Studenten tabel ════ */
  function renderStudentenTabel(studenten) {
    if (!studenten) studenten = state.data.beschikbaarheid;
    var wrap = document.getElementById('psp-studenten-tabel-wrap');
    if (!studenten.length) { wrap.innerHTML = '<div class="psp-panel-body"><p class="psp-empty-msg">Geen beschikbaarheid.</p></div>'; return; }
    var html = '<table class="psp-table"><thead><tr><th>Naam</th><th>E-mail</th><th>Tel</th>';
    DAG_KEYS.forEach(function (dk) { html += '<th>' + DAG_NAMES[dk] + '</th>'; });
    html += '<th>Ervaring</th><th>Opmerkingen</th><th></th></tr></thead><tbody>';
    studenten.forEach(function (s) {
      html += '<tr><td><strong>' + esc(s.naam) + '</strong></td><td><a href="mailto:' + esc(s.email) + '">' + esc(s.email) + '</a></td><td>' + esc(s.telefoon || '—') + '</td>';
      DAG_KEYS.forEach(function (dk) {
        var dag = s.dagen[dk];
        html += '<td>' + (dag ? dag.van + '–' + dag.tot : '<span style="color:#ddd">—</span>') + '</td>';
      });
      html += '<td>' + (s.vaardigheden && s.vaardigheden.length ? renderVaardigheden(s.vaardigheden) : '—') + '</td>' +
        '<td><small>' + esc(s.voorkeur || '—') + '</small></td>' +
        '<td style="display:flex;gap:4px">' +
        '<button class="psp-besch-edit-btn psp-tbl-action" data-id="' + s.id + '" title="Bewerken">✎</button>' +
        '<button class="psp-tbl-del" data-id="' + s.id + '" data-naam="' + esc(s.naam) + '">🗑</button>' +
        '</td></tr>';
    });
    html += '</tbody></table>';
    wrap.innerHTML = '<div class="psp-panel-body">' + html + '</div>';
    wrap.querySelectorAll('.psp-tbl-del').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!confirm('Verwijder beschikbaarheid van ' + btn.dataset.naam + '?')) return;
        ajax('psp_delete_beschikbaarheid', { beschikbaarheid_id: btn.dataset.id }, function () { toast('Verwijderd.', 'success'); loadWeek(); });
      });
    });
    wrap.querySelectorAll('.psp-besch-edit-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var s = state.data.beschikbaarheid.find(function (x) { return x.id === parseInt(btn.dataset.id); });
        if (s) openBeschikbaarheidModal(s);
      });
    });
  }

  /* ════ Beschikbaarheid modal (medewerker) ════ */
  var _beschStudenten = [];

  function initBeschikbaarheidModal() {
    var addBtn = document.getElementById('psp-beschikbaar-toevoegen-btn');
    if (addBtn) addBtn.addEventListener('click', function () { openBeschikbaarheidModal(null); });

    document.querySelectorAll('.psp-besch-dag-check').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var dag = cb.dataset.dag;
        var van = document.querySelector('.psp-besch-van[data-dag="' + dag + '"]');
        var tot = document.querySelector('.psp-besch-tot[data-dag="' + dag + '"]');
        if (van) { van.disabled = !cb.checked; van.style.opacity = cb.checked ? '1' : '.4'; }
        if (tot) { tot.disabled = !cb.checked; tot.style.opacity = cb.checked ? '1' : '.4'; }
      });
    });

    var sel = document.getElementById('psp-besch-student-select');
    if (sel) sel.addEventListener('change', function () {
      var val = sel.value;
      var hw = document.getElementById('psp-besch-handmatig-wrap');
      if (val === '__handmatig__') {
        hw.style.display = '';
        document.getElementById('psp-besch-naam').value = '';
        document.getElementById('psp-besch-email').value = '';
      } else if (val) {
        hw.style.display = 'none';
        var st = _beschStudenten.find(function (x) { return x.email === val; });
        if (st) {
          document.getElementById('psp-besch-naam').value  = st.naam;
          document.getElementById('psp-besch-email').value = st.email;
        }
      } else {
        hw.style.display = 'none';
      }
    });

    var opslaanBtn = document.getElementById('psp-besch-opslaan-btn');
    if (opslaanBtn) opslaanBtn.addEventListener('click', saveBeschikbaarheid);
  }

  function openBeschikbaarheidModal(student) {
    var modal = document.getElementById('psp-beschikbaar-modal');
    if (!modal) return;

    document.getElementById('psp-beschikbaar-modal-titel').textContent =
      student ? 'Beschikbaarheid bewerken' : 'Beschikbaarheid toevoegen';
    document.getElementById('psp-besch-id').value      = student ? student.id : 0;
    document.getElementById('psp-besch-week').value    = student ? student.week_start : fmt(state.week);
    document.getElementById('psp-besch-telefoon').value = student ? (student.telefoon || '') : '';
    document.getElementById('psp-besch-voorkeur').value = student ? (student.voorkeur || '') : '';

    var sel = document.getElementById('psp-besch-student-select');
    var hw  = document.getElementById('psp-besch-handmatig-wrap');

    function vullDropdown(studenten) {
      _beschStudenten = studenten;
      var opties = '<option value="">— Selecteer student —</option>';
      studenten.forEach(function (s) {
        var selected = student && s.email === student.email ? ' selected' : '';
        opties += '<option value="' + esc(s.email) + '"' + selected + '>' + esc(s.naam) + ' (' + esc(s.email) + ')</option>';
      });
      opties += '<option value="__handmatig__">+ Handmatig invullen</option>';
      sel.innerHTML = opties;
    }

    hw.style.display = 'none';
    document.getElementById('psp-besch-naam').value  = student ? (student.naam  || '') : '';
    document.getElementById('psp-besch-email').value = student ? (student.email || '') : '';

    if (_beschStudenten.length) {
      vullDropdown(_beschStudenten);
    } else {
      ajax('psp_get_studenten', {}, function (res) { vullDropdown(res); });
    }

    DAG_KEYS.forEach(function (dk) {
      var cb  = document.querySelector('.psp-besch-dag-check[data-dag="' + dk + '"]');
      var van = document.querySelector('.psp-besch-van[data-dag="' + dk + '"]');
      var tot = document.querySelector('.psp-besch-tot[data-dag="' + dk + '"]');
      if (!cb) return;
      var dag = student && student.dagen ? student.dagen[dk] : null;
      cb.checked = !!dag;
      if (van) { van.value = dag ? dag.van : '08:00'; van.disabled = !dag; van.style.opacity = dag ? '1' : '.4'; }
      if (tot) { tot.value = dag ? dag.tot : '17:00'; tot.disabled = !dag; tot.style.opacity = dag ? '1' : '.4'; }
    });

    modal.style.display = 'flex';
  }

  function saveBeschikbaarheid() {
    var id    = document.getElementById('psp-besch-id').value;
    var selVal = document.getElementById('psp-besch-student-select').value;
    var naam, email;

    if (selVal && selVal !== '__handmatig__') {
      var st = _beschStudenten.find(function (x) { return x.email === selVal; });
      naam  = st ? st.naam : '';
      email = selVal;
    } else {
      naam  = document.getElementById('psp-besch-naam').value.trim();
      email = document.getElementById('psp-besch-email').value.trim();
    }

    var weekStart = document.getElementById('psp-besch-week').value;
    if (!naam || !email || !weekStart) { toast('Naam, e-mail en week zijn verplicht.', 'error'); return; }

    var dagen = {};
    DAG_KEYS.forEach(function (dk) {
      var cb  = document.querySelector('.psp-besch-dag-check[data-dag="' + dk + '"]');
      var van = document.querySelector('.psp-besch-van[data-dag="' + dk + '"]');
      var tot = document.querySelector('.psp-besch-tot[data-dag="' + dk + '"]');
      if (cb && cb.checked && van && tot) dagen[dk] = { van: van.value, tot: tot.value };
    });

    var btn = document.getElementById('psp-besch-opslaan-btn');
    btn.disabled = true; btn.textContent = 'Bezig…';

    ajax('psp_save_beschikbaarheid_admin', {
      id: id, naam: naam, email: email,
      telefoon:   document.getElementById('psp-besch-telefoon').value.trim(),
      week_start: weekStart,
      dagen:      JSON.stringify(dagen),
      voorkeur:   document.getElementById('psp-besch-voorkeur').value.trim(),
    }, function (res) {
      btn.disabled = false; btn.textContent = 'Opslaan';
      document.getElementById('psp-beschikbaar-modal').style.display = 'none';
      toast(res && res.message ? res.message : 'Opgeslagen.', 'success');
      loadWeek();
    }, function (err) {
      btn.disabled = false; btn.textContent = 'Opslaan';
      toast((err && err.message) ? err.message : 'Fout bij opslaan.', 'error');
    });
  }

  /* ════ Dienst modal ════ */
  function initDienstModal() {
    ['psp-nieuw-dienst-btn','psp-nieuw-dienst-btn2','psp-nieuw-dienst-btn3'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('click', function () { openDienstModal(null); });
    });
    document.querySelectorAll('.psp-modal-close, [data-modal]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var id = btn.dataset.modal || (btn.closest('.psp-modal') && btn.closest('.psp-modal').id);
        if (id) { var m = document.getElementById(id); if (m) m.style.display = 'none'; }
      });
    });
    document.querySelectorAll('.psp-modal').forEach(function (m) {
      m.addEventListener('click', function (e) { if (e.target === m) m.style.display = 'none'; });
    });
    document.getElementById('psp-dienst-form').addEventListener('submit', function (e) {
      e.preventDefault();
      var data = {}; new FormData(e.target).forEach(function (v, k) { data[k] = v; });
      var btn = document.getElementById('psp-dienst-save-btn');
      btn.disabled = true; btn.textContent = 'Opslaan…';
      ajax('psp_save_dienst', data, function () {
        toast('Dienst opgeslagen.', 'success');
        document.getElementById('psp-modal-dienst').style.display = 'none';
        btn.disabled = false; btn.textContent = 'Opslaan'; loadWeek();
      }, function (msg) { toast(msg || 'Opslaan mislukt.', 'error'); btn.disabled = false; btn.textContent = 'Opslaan'; });
    });
    document.getElementById('psp-dienst-delete-btn').addEventListener('click', function () {
      var id = document.getElementById('psp-dienst-id').value;
      if (!id || !confirm('Dienst verwijderen?')) return;
      ajax('psp_delete_dienst', { dienst_id: id }, function () {
        toast('Verwijderd.', 'success'); document.getElementById('psp-modal-dienst').style.display = 'none'; loadWeek();
      });
    });
  }

  function openDienstModal(dienst) {
    var form = document.getElementById('psp-dienst-form');
    form.reset();
    document.getElementById('psp-modal-dienst-title').textContent = dienst ? 'Dienst bewerken' : 'Nieuwe dienst';
    document.getElementById('psp-dienst-delete-btn').style.display = dienst ? '' : 'none';
    if (dienst) {
      ['dienst_id','titel','opdrachtgever','datum','tijdstip_van','tijdstip_tot','locatie','type_werk','omschrijving'].forEach(function (k) {
        var el = form.querySelector('[name="' + k + '"]');
        if (el) el.value = dienst[k] || '';
      });
    } else {
      form.querySelector('[name="dienst_id"]').value = '';
      form.querySelector('[name="datum"]').value = fmt(state.week);
    }
    document.getElementById('psp-modal-dienst').style.display = 'flex';
    setTimeout(function () { form.querySelector('[name="titel"]').focus(); }, 50);
  }

  /* ════ AJAX ════ */
  function ajax(action, data, onSuccess, onError) {
    var body = new URLSearchParams();
    Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    body.append('action', action);
    body.append('nonce', getNonce());
    var url = (typeof pspDash !== 'undefined' && pspDash.ajaxUrl) ? pspDash.ajaxUrl : '/wp-admin/admin-ajax.php';
    fetch(url, { method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body.toString() })
      .then(function (r) { return r.json(); })
      .then(function (res) { if (res.success) { if (onSuccess) onSuccess(res.data); } else { if (onError) onError(res.data && res.data.message); } })
      .catch(function () { if (onError) onError('Verbindingsfout.'); });
  }

  function getNonce() { var el = document.getElementById('psp_dash_nonce'); return el ? el.value : ''; }
  function setLoader(show) {
    document.getElementById('psp-loader').style.display = show ? '' : 'none';
    var r = document.getElementById('psp-tab-rooster'); if (r) r.style.opacity = show ? '.4' : '';
  }
  function addToast() { var t = document.createElement('div'); t.id = 'psp-toast'; document.body.appendChild(t); }
  function toast(msg, type) {
    var t = document.getElementById('psp-toast');
    t.textContent = msg; t.className = type || ''; t.classList.add('show');
    clearTimeout(t._timer); t._timer = setTimeout(function () { t.classList.remove('show'); }, 3500);
  }
  function mondayOfCurrentWeek() { var d = new Date(), dow = d.getDay(); return addDays(d, dow === 0 ? -6 : 1 - dow); }
  function addDays(d, n) { var r = new Date(d); r.setDate(r.getDate() + n); return r; }
  function fmt(d) { var dt = new Date(d); return dt.getFullYear() + '-' + String(dt.getMonth()+1).padStart(2,'0') + '-' + String(dt.getDate()).padStart(2,'0'); }
  function fmtNL(d) { var dt = new Date(d); return dt.getDate() + ' ' + MONTHS[dt.getMonth()]; }
  function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
  /* ════════════════════════════════════════════════════
     TARIEFMELDING MODAL
  ════════════════════════════════════════════════════ */
  var _tariefCtx = {};

  function toonTariefModal(email, opdrachtgever, naam) {
    _tariefCtx = { email: email, opdrachtgever: opdrachtgever };
    var tekst = document.getElementById('psp-tarief-tekst');
    if (tekst) tekst.textContent = (naam || email) + ' werkt voor het eerst bij ' + opdrachtgever + '.';
    var modal = document.getElementById('psp-modal-tarief');
    if (modal) modal.style.display = 'flex';
    // Wis vorige invoer
    var u = document.getElementById('psp-tarief-uurtarief');
    var l = document.getElementById('psp-tarief-loon');
    if (u) u.value = ''; if (l) l.value = '';
  }

  function initTariefModal() {
    var saveBtn = document.getElementById('psp-tarief-save-btn');
    var skipBtn = document.getElementById('psp-tarief-skip-btn');
    if (!saveBtn) return;

    saveBtn.addEventListener('click', function () {
      var uurtarief = parseFloat(document.getElementById('psp-tarief-uurtarief').value) || 0;
      var loon      = parseFloat(document.getElementById('psp-tarief-loon').value) || 0;
      saveBtn.disabled = true;
      ajax('psp_save_tarief', {
        student_email:  _tariefCtx.email,
        opdrachtgever:  _tariefCtx.opdrachtgever,
        uurtarief:      uurtarief,
        loon:           loon
      }, function (res) {
        toast(res.message || '\u2713 Doorgegeven aan flexexpert.', 'success');
        document.getElementById('psp-modal-tarief').style.display = 'none';
        saveBtn.disabled = false;
        laadTarievenBadge();
      }, function (msg) {
        toast(msg || 'Opslaan mislukt.', 'error');
        saveBtn.disabled = false;
      });
    });

    skipBtn.addEventListener('click', function () {
      document.getElementById('psp-modal-tarief').style.display = 'none';
      toast('Tarief wordt later ingevuld. Zie Beheer-tab.', 'info');
      laadTarievenBadge();
    });
  }

  /* ════════════════════════════════════════════════════
     BEHEER TAB
  ════════════════════════════════════════════════════ */
  var _beheerInit = false;

  function initBeheerTab() {
    if (_beheerInit) return;
    _beheerInit = true;

    // Subtab switching
    document.querySelectorAll('.psp-stab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.psp-stab').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        document.querySelectorAll('.psp-stab-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = document.getElementById('psp-stab-' + btn.dataset.stab);
        if (panel) panel.style.display = '';
        if (btn.dataset.stab === 'tarieven')       laadTarievenTodo();
        if (btn.dataset.stab === 'aanmeldingen')   laadAanmeldingen();
        if (btn.dataset.stab === 'studenten')      laadStudentenTab();
        if (btn.dataset.stab === 'werkbevestiging') initWbTab();
        if (btn.dataset.stab === 'bevestigingen')  laadWbBevestigingen();
        if (btn.dataset.stab === 'rapportage')     initRapportageTab();
      });
    });

    laadTarievenTodo();
    laadAanmeldingenBadge();
  }

  function laadTarievenBadge() {
    ajax('psp_tarieven_todo', {}, function (data) {
      var n = Array.isArray(data) ? data.length : 0;
      var badge = document.getElementById('psp-tarieven-badge');
      if (badge) { badge.textContent = n; badge.style.display = n ? '' : 'none'; }
    });
  }

  function laadTarievenTodo() {
    var el = document.getElementById('psp-tarieven-todo-lijst');
    if (!el) return;
    el.innerHTML = '<p class="psp-empty-msg">Laden&#8230;</p>';

    ajax('psp_tarieven_todo', {}, function (data) {
      if (!Array.isArray(data) || !data.length) {
        el.innerHTML = '<p class="psp-empty-msg">&#10003; Geen openstaande tariefmeldingen.</p>';
        var badge = document.getElementById('psp-tarieven-badge');
        if (badge) badge.style.display = 'none';
        return;
      }
      var badge = document.getElementById('psp-tarieven-badge');
      if (badge) { badge.textContent = data.length; badge.style.display = ''; }

      var html = '<table class="psp-table"><thead><tr>'
        + '<th>Student</th><th>Opdrachtgever</th><th>Datum koppeling</th><th>Actie</th>'
        + '</tr></thead><tbody>';
      data.forEach(function (r) {
        html += '<tr>'
          + '<td><strong>' + esc(r.student_naam) + '</strong><br><small>' + esc(r.student_email) + '</small></td>'
          + '<td>' + esc(r.opdrachtgever) + '</td>'
          + '<td>' + esc(r.datum) + '</td>'
          + '<td><button class="psp-btn-primary psp-btn-sm psp-tarief-invul-btn"'
          + ' data-email="' + esc(r.student_email) + '"'
          + ' data-opdrachtgever="' + esc(r.opdrachtgever) + '"'
          + ' data-naam="' + esc(r.student_naam) + '">Invullen</button></td>'
          + '</tr>';
      });
      html += '</tbody></table>';
      el.innerHTML = html;

      el.querySelectorAll('.psp-tarief-invul-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          toonTariefModal(btn.dataset.email, btn.dataset.opdrachtgever, btn.dataset.naam);
        });
      });
    }, function () {
      el.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }


  /* ════════════════════════════════════════════════════
     STUDENT ACCOUNTS TAB
  ════════════════════════════════════════════════════ */
  var _studentenGeladen = false;

  function laadStudentenTab() {
    var el = document.getElementById('psp-studenten-accounts-lijst');
    if (!el) return;
    el.innerHTML = '<p class="psp-empty-msg">Laden&#8230;</p>';
    _studentenGeladen = false;

    ajax('psp_get_studenten', {}, function (data) {
      _studentenGeladen = true;
      if (!Array.isArray(data) || !data.length) {
        el.innerHTML = '<p class="psp-empty-msg">Geen studenten gevonden in de beschikbaarheidslijst.</p>';
        return;
      }

      var html = '<table class="psp-table"><thead><tr>'
        + '<th>Naam</th><th>E-mail</th><th>Account</th><th>Vaardigheden</th><th></th>'
        + '</tr></thead><tbody>';

      data.forEach(function (s) {
        var vaardLijst = Object.keys(PSP_VAARDIGHEDEN).map(function (k) {
          var checked = s.vaardigheden && s.vaardigheden.indexOf(k) > -1 ? 'checked' : '';
          return '<label style="display:inline-flex;align-items:center;gap:4px;margin:2px 6px 2px 0;font-size:.78rem">'
            + '<input type="checkbox" class="psp-vaard-check" data-key="' + esc(k) + '" ' + checked + '> '
            + esc(PSP_VAARDIGHEDEN[k]) + '</label>';
        }).join('');

        html += '<tr data-user-id="' + (s.user_id || 0) + '" data-email="' + esc(s.email) + '" data-naam="' + esc(s.naam) + '">'
          + '<td><strong>' + esc(s.naam) + '</strong></td>'
          + '<td>' + esc(s.email) + '</td>'
          + '<td>' + ( s.has_account
              ? '<span class="psp-badge-groen">&#10003; Actief</span>'
              : '<button class="psp-btn-sm psp-btn-primary psp-maak-acc-btn">Account aanmaken</button>' )
          + '</td>'
          + '<td>' + ( s.has_account
              ? '<div class="psp-vaard-wrap">' + vaardLijst + '</div>'
              : '<span style="color:#aaa;font-size:.78rem">Maak eerst een account aan</span>' )
          + '</td>'
          + '<td>' + ( s.has_account
              ? '<button class="psp-btn-sm psp-btn-ghost psp-sla-vaard-btn">Opslaan</button>'
              : '' )
          + '</td>'
          + '</tr>';
      });
      html += '</tbody></table>';
      el.innerHTML = html;

      // Account aanmaken
      el.querySelectorAll('.psp-maak-acc-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var tr    = btn.closest('tr');
          var email = tr.dataset.email;
          var naam  = tr.dataset.naam;
          btn.disabled = true; btn.textContent = '…';
          ajax('psp_maak_student_account', { email: email, naam: naam }, function (res) {
            document.getElementById('psp-acc-login').textContent  = res.login;
            document.getElementById('psp-acc-email').textContent  = res.email;
            document.getElementById('psp-acc-ww').textContent     = res.wachtwoord;
            var urlEl = document.getElementById('psp-acc-url');
            urlEl.href = res.login_url; urlEl.textContent = res.login_url;
            document.getElementById('psp-modal-account').style.display = 'flex';
            laadStudentenTab(); // herlaad lijst
          }, function (msg) {
            toast(msg || 'Account aanmaken mislukt.', 'error');
            btn.disabled = false; btn.textContent = 'Account aanmaken';
          });
        });
      });

      // Vaardigheden opslaan
      el.querySelectorAll('.psp-sla-vaard-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var tr      = btn.closest('tr');
          var userId  = parseInt(tr.dataset.userId || tr.dataset.user_id || 0);
          var checks  = tr.querySelectorAll('.psp-vaard-check:checked');
          var vaard   = Array.from(checks).map(function (c) { return c.dataset.key; });
          btn.disabled = true; btn.textContent = '…';
          ajax('psp_save_student_vaardigheden', { user_id: userId, vaardigheden: vaard }, function (res) {
            toast(res.message || '\u2713 Opgeslagen.', 'success');
            btn.disabled = false; btn.textContent = 'Opslaan';
          }, function (msg) {
            toast(msg || 'Opslaan mislukt.', 'error');
            btn.disabled = false; btn.textContent = 'Opslaan';
          });
        });
      });
    }, function () {
      el.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }

  // Account modal sluiten
  document.addEventListener('DOMContentLoaded', function () {
    var sluitBtn = document.getElementById('psp-acc-sluit-btn');
    if (sluitBtn) sluitBtn.addEventListener('click', function () {
      document.getElementById('psp-modal-account').style.display = 'none';
    });
  });


  /* ════════════════════════════════════════════════════
     WERKBEVESTIGING TEMPLATES
  ════════════════════════════════════════════════════ */
  var _wbInit = false;

  function initWbTab() {
    laadWbTemplates();

    if (_wbInit) return;
    _wbInit = true;

    // Nieuwe template knop
    var nieuwBtn = document.getElementById('psp-wb-nieuw-btn');
    if (nieuwBtn) nieuwBtn.addEventListener('click', function () { openWbModal(null); });

    // Filter
    var filter = document.getElementById('psp-wb-og-filter');
    if (filter) filter.addEventListener('change', function () { laadWbTemplates(); });

    // Modal sluiten
    document.querySelectorAll('[data-modal="psp-modal-wb"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('psp-modal-wb').style.display = 'none';
      });
    });

    // Formulier submit
    var form = document.getElementById('psp-wb-form');
    if (form) form.addEventListener('submit', function (e) {
      e.preventDefault();
      var saveBtn = form.querySelector('[type="submit"]');
      saveBtn.disabled = true;
      var fd = {
        wb_id:         document.getElementById('psp-wb-id').value,
        opdrachtgever: document.getElementById('psp-wb-opdrachtgever').value,
        naam:          document.getElementById('psp-wb-naam').value,
        onderwerp:     document.getElementById('psp-wb-onderwerp').value,
        inhoud:        document.getElementById('psp-wb-inhoud').value,
      };
      ajax('psp_wb_opslaan', fd, function () {
        document.getElementById('psp-modal-wb').style.display = 'none';
        toast('✓ Template opgeslagen.', 'success');
        laadWbTemplates();
      }, function (msg) {
        toast(msg || 'Opslaan mislukt.', 'error');
        saveBtn.disabled = false;
      });
    });

    // Verwijder knop
    var delBtn = document.getElementById('psp-wb-delete-btn');
    if (delBtn) delBtn.addEventListener('click', function () {
      if (!confirm('Template verwijderen?')) return;
      var id = document.getElementById('psp-wb-id').value;
      ajax('psp_wb_verwijder', { wb_id: id }, function () {
        document.getElementById('psp-modal-wb').style.display = 'none';
        toast('Template verwijderd.', 'info');
        laadWbTemplates();
      });
    });
  }

  function laadWbTemplates() {
    var el = document.getElementById('psp-wb-lijst');
    if (!el) return;
    el.innerHTML = '<p class="psp-empty-msg">Laden…</p>';
    var og = '';
    var filter = document.getElementById('psp-wb-og-filter');
    if (filter) og = filter.value;

    ajax('psp_wb_laad', { og: og }, function (data) {
      // Update filter opties
      var filterEl = document.getElementById('psp-wb-og-filter');
      if (filterEl && Array.isArray(data.ogs)) {
        var huidig = filterEl.value;
        filterEl.innerHTML = '<option value="">— Alle —</option>';
        data.ogs.forEach(function (o) {
          var opt = document.createElement('option');
          opt.value = o; opt.textContent = o;
          if (o === huidig) opt.selected = true;
          filterEl.appendChild(opt);
        });
      }

      var rows = data.rows || [];
      if (!rows.length) {
        el.innerHTML = '<p class="psp-empty-msg">Geen templates gevonden. Klik op "+ Nieuwe template" om er een aan te maken.</p>';
        return;
      }

      // Groepeer per opdrachtgever
      var groepen = {};
      rows.forEach(function (r) {
        var og = r.opdrachtgever || '(geen)';
        if (!groepen[og]) groepen[og] = [];
        groepen[og].push(r);
      });

      var html = '';
      Object.keys(groepen).sort().forEach(function (og) {
        html += '<div class="psp-wb-groep">';
        html += '<h4 class="psp-wb-groep-titel">' + esc(og) + '</h4>';
        html += '<div class="psp-wb-kaarten">';
        groepen[og].forEach(function (r) {
          var preview = (r.inhoud || '').replace(/<[^>]+>/g, '').substring(0, 120);
          if ((r.inhoud || '').length > 120) preview += '…';
          html += '<div class="psp-wb-kaart">'
            + '<div class="psp-wb-kaart-header">'
            + '<strong>' + esc(r.naam) + '</strong>'
            + '<button class="psp-btn-icon psp-wb-edit-btn" data-id="' + r.id + '" title="Bewerken">✎</button>'
            + '</div>'
            + (r.onderwerp ? '<div class="psp-wb-onderwerp">📧 ' + esc(r.onderwerp) + '</div>' : '')
            + '<div class="psp-wb-preview">' + esc(preview) + '</div>'
            + '</div>';
        });
        html += '</div></div>';
      });
      el.innerHTML = html;

      // Bewerkknopjes
      el.querySelectorAll('.psp-wb-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.dataset.id;
          var r = rows.find(function (x) { return String(x.id) === String(id); });
          if (r) openWbModal(r);
        });
      });
    }, function () {
      el.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }

  function openWbModal(r) {
    document.getElementById('psp-wb-id').value          = r ? r.id : '';
    document.getElementById('psp-wb-opdrachtgever').value = r ? (r.opdrachtgever || '') : '';
    document.getElementById('psp-wb-naam').value        = r ? (r.naam || '') : '';
    document.getElementById('psp-wb-onderwerp').value   = r ? (r.onderwerp || '') : '';
    document.getElementById('psp-wb-inhoud').value      = r ? (r.inhoud || '') : '';
    document.getElementById('psp-modal-wb-title').textContent = r ? 'Template bewerken' : 'Nieuwe template';
    var delBtn = document.getElementById('psp-wb-delete-btn');
    if (delBtn) delBtn.style.display = r ? '' : 'none';
    var form = document.getElementById('psp-wb-form');
    if (form) { var sub = form.querySelector('[type="submit"]'); if (sub) sub.disabled = false; }
    document.getElementById('psp-modal-wb').style.display = '';
  }


  /* ════════════════════════════════════════════════════
     WERKBEVESTIGING VERSTUREN (recruiter)
  ════════════════════════════════════════════════════ */

  function wbKnopHtml(d) {
    if (!d.koppeling) return '';
    var status = d.wb_status;
    var cls    = status === 'bevestigd' ? 'psp-btn-wb-bevestigd'
               : status === 'verzonden' ? 'psp-btn-wb-verzonden'
               : 'psp-btn-ghost';
    var label  = status === 'bevestigd' ? '\u2713 Bevestigd'
               : status === 'verzonden' ? '📧 Opnieuw sturen'
               : '📧 WB sturen';
    return '<button class="psp-wb-stuur-kaart-btn psp-btn-sm ' + cls + '"'
      + ' data-dienst-id="' + d.id + '"'
      + ' data-email="' + esc(d.koppeling.email) + '"'
      + ' data-naam="' + esc(d.koppeling.naam) + '"'
      + ' data-og="' + esc(d.opdrachtgever) + '"'
      + ' title="Werkbevestiging">' + label + '</button>';
  }

  function replacePlaceholders(tekst, ctx) {
    if (!tekst) return '';
    var datum_nl = ctx.datum ? new Date(ctx.datum + 'T00:00:00').toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '';
    return tekst
      .replace(/\{naam\}/g,           ctx.naam || '')
      .replace(/\{datum\}/g,          datum_nl || ctx.datum || '')
      .replace(/\{van\}/g,            ctx.van  || '')
      .replace(/\{tot\}/g,            ctx.tot  || '')
      .replace(/\{opdrachtgever\}/g,  ctx.og   || '')
      .replace(/\{locatie\}/g,        ctx.locatie  || '')
      .replace(/\{type_werk\}/g,      ctx.type_werk || '')
      .replace(/\{bevestig_link\}/g,  ctx.bevestig_link || '');
  }

  function defaultWbInhoud(ctx) {
    var datum_nl = ctx.datum ? new Date(ctx.datum + 'T00:00:00').toLocaleDateString('nl-NL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '';
    var r = [];
    r.push('Beste ' + ctx.naam + ',');
    r.push('');
    r.push('Hierbij ontvang je de werkbevestiging voor jouw aanstaande dienst bij ' + ctx.og + '.');
    r.push('Lees deze bevestiging zorgvuldig door en klik onderaan op de bevestigingslink.');
    r.push('');
    r.push('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    r.push('DIENSTINFORMATIE');
    r.push('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    r.push('');
    r.push('🏢  Opdrachtgever : ' + ctx.og);
    r.push('📅  Datum          : ' + datum_nl);
    r.push('\u23f0  Tijdstip       : ' + ctx.van + ' \u2013 ' + ctx.tot);
    if (ctx.locatie)   r.push('📍  Locatie        : ' + ctx.locatie);
    if (ctx.type_werk) r.push('🦹  Type werk      : ' + ctx.type_werk);
    r.push('');
    r.push('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    r.push('WAT JE MOET WETEN');
    r.push('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    r.push('');
    r.push('\u2022 Zorg dat je op tijd aanwezig bent (minimaal 10 minuten voor aanvang).');
    r.push('\u2022 Draag geschikte werkkleding tenzij anders afgesproken.');
    r.push('\u2022 Heb je vragen of kun je onverhoopt niet komen? Neem dan direct contact op');
    r.push('  met ProStudents via info@prostudents.nl of 050 \u2013 311 23 22.');
    r.push('');
    r.push('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    r.push('\u2705 BEVESTIG JE WERKBEVESTIGING');
    r.push('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    r.push('');
    r.push('Klik op de onderstaande link om te bevestigen dat je deze werkbevestiging hebt ontvangen en gelezen:');
    r.push('');
    r.push(ctx.bevestig_link);
    r.push('');
    r.push('Je kunt ook inloggen op het portaal en daar op "Gelezen en akkoord" klikken.');
    r.push('');
    r.push('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    r.push('');
    r.push('Met vriendelijke groet,');
    r.push('');
    r.push('ProStudents Uitzendbureau Groningen');
    r.push('T: 050 \u2013 311 23 22');
    r.push('E: info@prostudents.nl');
    r.push('W: www.prostudents.nl');
    r.push('Atoomweg 6b, 9743 AK Groningen');
    return r.join('\n');
  }

  function toonWbStuurModal(params) {
    var dienstId     = String(params.dienst_id || '');
    var studentEmail = String(params.student_email || '');
    var studentNaam  = String(params.student_naam || params.naam || '');
    var og           = String(params.opdrachtgever || '');

    if (!dienstId || !studentEmail) return;

    // Haal dienst op uit state voor echte waarden
    var dienst = null;
    if (state.data && state.data.diensten) {
      dienst = state.data.diensten.find(function (d) { return String(d.id) === dienstId; });
    }

    var ctx = {
      naam:          studentNaam,
      datum:         dienst ? dienst.datum         : '',
      van:           dienst ? dienst.tijdstip_van.substring(0, 5) : '',
      tot:           dienst ? dienst.tijdstip_tot.substring(0, 5) : '',
      og:            og,
      locatie:       dienst ? (dienst.locatie   || '') : '',
      type_werk:     dienst ? (dienst.type_werk || '') : '',
      bevestig_link: (typeof pspDash !== 'undefined' && pspDash.mijnRoosterUrl) ? pspDash.mijnRoosterUrl : window.location.origin + '/mijn-rooster/',
    };

    document.getElementById('psp-wbs-dienst-id').value    = dienstId;
    document.getElementById('psp-wbs-student-email').value = studentEmail;
    document.getElementById('psp-wbs-opdrachtgever').value = og;
    document.getElementById('psp-wbs-aan').value           = studentNaam + ' <' + studentEmail + '>';

    ajax('psp_wb_templates_voor_og', { opdrachtgever: og, dienst_id: dienstId }, function (data) {
      var templates = data.templates || [];
      var bestaande = data.bestaande;

      // Startinhoud: bestaande WB > eerste template > standaard
      var startTemplate = bestaande || (templates.length ? templates[0] : null);
      var rawOnderwerp  = startTemplate ? (startTemplate.onderwerp || '') : 'Werkbevestiging {datum} – {opdrachtgever}';
      var rawInhoud     = startTemplate ? (startTemplate.inhoud    || '') : '';

      // Vul standaard inhoud als template leeg is
      if (!rawInhoud) rawInhoud = defaultWbInhoud(ctx);

      // Vervang placeholders
      document.getElementById('psp-wbs-onderwerp').value = replacePlaceholders(rawOnderwerp, ctx);
      document.getElementById('psp-wbs-inhoud').value    = replacePlaceholders(rawInhoud,    ctx);

      // Status info
      var statusInfo = document.getElementById('psp-wbs-status-info');
      if (bestaande) {
        var statusTxt = bestaande.status === 'bevestigd'
          ? '\u2713 Student heeft deze werkbevestiging bevestigd op ' + (bestaande.bevestigd_op || '').substring(0, 16) + '.'
          : '📧 Eerder verstuurd op ' + (bestaande.verzonden_op || '').substring(0, 16) + '. Je kunt de inhoud aanpassen en opnieuw versturen.';
        statusInfo.textContent  = statusTxt;
        statusInfo.style.display   = '';
        statusInfo.style.background = bestaande.status === 'bevestigd' ? '#f0fdf4' : '#fff7ed';
        statusInfo.style.color      = bestaande.status === 'bevestigd' ? '#166534' : '#9a3412';
      } else {
        statusInfo.style.display = 'none';
      }

      // Template-keuzeknoppen (indien meerdere templates)
      var keuzeDiv  = document.getElementById('psp-wbs-template-keuze');
      var keuzeList = document.getElementById('psp-wbs-template-btns');
      if (templates.length > 1) {
        keuzeList.innerHTML = '';
        templates.forEach(function (t) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'psp-btn-ghost psp-btn-sm';
          btn.textContent = t.naam;
          btn.addEventListener('click', function () {
            document.getElementById('psp-wbs-onderwerp').value = replacePlaceholders(t.onderwerp || '', ctx);
            document.getElementById('psp-wbs-inhoud').value    = replacePlaceholders(t.inhoud    || '', ctx);
          });
          keuzeList.appendChild(btn);
        });
        keuzeDiv.style.display = '';
      } else {
        keuzeDiv.style.display = 'none';
      }

      document.getElementById('psp-modal-wb-stuur').style.display = '';
      // Ghost-click beveiliging: disable stuurBtn kort na openen zodat een
      // lingerende klik van "Inplannen" hem niet per ongeluk triggert.
      var _sBtn = document.getElementById('psp-wbs-stuur-btn');
      if (_sBtn) {
        _sBtn.disabled = true;
        setTimeout(function () { _sBtn.disabled = false; }, 900);
      }
    });
  }


  // Initialiseer WB stuur modal events (eenmalig)
  (function () {
    document.addEventListener('DOMContentLoaded', function () {
      // Sluiten
      document.querySelectorAll('[data-modal="psp-modal-wb-stuur"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          document.getElementById('psp-modal-wb-stuur').style.display = 'none';
        });
      });

      // Verstuur knop
      var stuurBtn = document.getElementById('psp-wbs-stuur-btn');
      if (stuurBtn) stuurBtn.addEventListener('click', function () {
        stuurBtn.disabled = true;
        var fd = {
          dienst_id:     document.getElementById('psp-wbs-dienst-id').value,
          student_email: document.getElementById('psp-wbs-student-email').value,
          opdrachtgever: document.getElementById('psp-wbs-opdrachtgever').value,
          onderwerp:     document.getElementById('psp-wbs-onderwerp').value,
          inhoud:        document.getElementById('psp-wbs-inhoud').value,
        };
        ajax('psp_wb_stuur', fd, function (res) {
          document.getElementById('psp-modal-wb-stuur').style.display = 'none';
          toast(res.message || '\u2713 Verstuurd.', 'success');
          loadWeek(); // herlaad zodat WB status in kaart zichtbaar wordt
          stuurBtn.disabled = false;
        }, function (msg) {
          toast(msg || 'Versturen mislukt.', 'error');
          stuurBtn.disabled = false;
        });
      });
    });
  })();

  /* ════════════════════════════════════════════════════
     BEVESTIGINGEN OVERZICHT (Beheer subtab)
  ════════════════════════════════════════════════════ */

  function laadWbBevestigingen() {
    var el = document.getElementById('psp-wb-bevestigingen-lijst');
    if (!el) return;
    el.innerHTML = '<p class="psp-empty-msg">Laden&#8230;</p>';
    ajax('psp_wb_bevestigingen', {}, function (data) {
      if (!Array.isArray(data) || !data.length) {
        el.innerHTML = '<p class="psp-empty-msg">\u2713 Geen nieuwe bevestigingen.</p>';
        return;
      }
      var html = '<table class="psp-table"><thead><tr>'
        + '<th>Student</th><th>Opdrachtgever</th><th>Dienst datum</th><th>Bevestigd op</th>'
        + '</tr></thead><tbody>';
      data.forEach(function (r) {
        html += '<tr>'
          + '<td>' + esc(r.student_email) + '</td>'
          + '<td>' + esc(r.opdrachtgever) + '</td>'
          + '<td>' + esc(r.datum) + '</td>'
          + '<td><strong style="color:#15803d">' + esc((r.bevestigd_op || '').substring(0, 16)) + '</strong></td>'
          + '</tr>';
      });
      html += '</tbody></table>';
      el.innerHTML = html;
    }, function () {
      el.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }


  /* \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550
     AANMELDINGEN (Beheer subtab)
  \u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550\u2550 */

  function laadAanmeldingenBadge() {
    ajax('psp_get_aanmeldingen', {}, function (data) {
      var n = Array.isArray(data) ? data.length : 0;
      var badge = document.getElementById('psp-aanmeldingen-badge');
      if (badge) { badge.textContent = n; badge.style.display = n ? '' : 'none'; }
    });
  }

  function laadAanmeldingen() {
    var el = document.getElementById('psp-aanmeldingen-lijst');
    if (!el) return;
    el.innerHTML = '<p class="psp-empty-msg">Laden&#8230;</p>';
    ajax('psp_get_aanmeldingen', {}, function (data) {
      if (!Array.isArray(data) || !data.length) {
        el.innerHTML = '<p class="psp-empty-msg">Geen nieuwe aanmeldingen.</p>';
        laadAanmeldingenBadge();
        return;
      }
      var html = '<table class="psp-table"><thead><tr>'
        + '<th>Naam</th><th>E-mail</th><th>Telefoon</th><th>Datum</th><th style="width:190px">Actie</th>'
        + '</tr></thead><tbody>';
      data.forEach(function (r) {
        html += '<tr data-uid="' + r.user_id + '">'
          + '<td><strong>' + esc(r.naam) + '</strong></td>'
          + '<td>' + esc(r.email) + '</td>'
          + '<td>' + esc(r.telefoon || '\u2014') + '</td>'
          + '<td>' + esc(r.datum) + '</td>'
          + '<td style="display:flex;gap:6px">'
          + '<button class="psp-btn-primary psp-btn-sm psp-aanm-goed" data-uid="' + r.user_id + '" data-naam="' + esc(r.naam) + '">\u2713 Goedkeuren</button>'
          + '<button class="psp-tbl-del psp-aanm-af" data-uid="' + r.user_id + '" data-naam="' + esc(r.naam) + '" title="Afwijzen">\u2717</button>'
          + '</td></tr>';
      });
      html += '</tbody></table>';
      el.innerHTML = html;

      el.querySelectorAll('.psp-aanm-goed').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Account van ' + btn.dataset.naam + ' goedkeuren en welkomstmail sturen?')) return;
          btn.disabled = true; btn.textContent = '\u2026';
          ajax('psp_goedkeur_aanmelding', { user_id: btn.dataset.uid }, function (res) {
            toast(res.message || '\u2713 Goedgekeurd.', 'success');
            laadAanmeldingen();
          }, function (msg) {
            toast(msg || 'Mislukt.', 'error');
            btn.disabled = false; btn.textContent = '\u2713 Goedkeuren';
          });
        });
      });

      el.querySelectorAll('.psp-aanm-af').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Aanmelding van ' + btn.dataset.naam + ' afwijzen en account verwijderen?')) return;
          btn.disabled = true;
          ajax('psp_wijs_af_aanmelding', { user_id: btn.dataset.uid }, function (res) {
            toast(res.message || 'Afgewezen.', 'success');
            laadAanmeldingen();
          }, function (msg) {
            toast(msg || 'Mislukt.', 'error');
            btn.disabled = false;
          });
        });
      });

      laadAanmeldingenBadge();
    }, function () {
      el.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }

  /* ════════════════════════════════════════════════════
     RAPPORTAGE — uren per week + per klant
  ════════════════════════════════════════════════════ */

  var _rapportageInit = false;

  function initRapportageTab() {
    if (!_rapportageInit) {
      _rapportageInit = true;
      var btn = document.getElementById('psp-rap-laad-btn');
      if (btn) btn.addEventListener('click', laadUrenoverzicht);
      // Direct laden voor huidig jaar
      laadUrenoverzicht();
    }
  }

  function laadUrenoverzicht() {
    var wrap = document.getElementById('psp-rapportage-wrap');
    if (!wrap) return;
    var jaar = (document.getElementById('psp-rap-jaar') || {}).value || new Date().getFullYear();
    wrap.innerHTML = '<p class="psp-empty-msg">Laden&#8230;</p>';

    ajax('psp_urenoverzicht', { jaar: jaar }, function (data) {
      var totaal    = data.totaal    || {};
      var perWeek   = data.per_week  || [];
      var perKlant  = data.per_klant || [];

      if (!perWeek.length && !perKlant.length) {
        wrap.innerHTML = '<p class="psp-empty-msg">Geen ingeplande diensten gevonden voor ' + jaar + '.</p>';
        return;
      }

      // ── Totalen balk ──────────────────────────────────────────────
      var html = '<div class="psp-rap-totalen">'
        + '<div class="psp-rap-totaal-item"><span class="psp-rap-getal">' + (totaal.uren || 0) + '</span><span class="psp-rap-label">totaal uren</span></div>'
        + '<div class="psp-rap-totaal-item"><span class="psp-rap-getal">' + (totaal.diensten || 0) + '</span><span class="psp-rap-label">diensten</span></div>'
        + '<div class="psp-rap-totaal-item"><span class="psp-rap-getal">' + (totaal.studenten || 0) + '</span><span class="psp-rap-label">unieke studenten</span></div>'
        + '</div>';

      // ── Twee kolommen ─────────────────────────────────────────────
      html += '<div class="psp-rap-kolommen">';

      // Linker kolom: per klant
      html += '<div class="psp-rap-kolom">'
        + '<h4 class="psp-rap-kop">Uren per opdrachtgever</h4>'
        + '<table class="psp-table psp-rap-tabel"><thead><tr>'
        + '<th>Opdrachtgever</th><th style="text-align:right">Uren</th><th style="text-align:right">Diensten</th>'
        + '</tr></thead><tbody>';

      var maxUren = perKlant.length ? parseFloat(perKlant[0].uren) : 1;
      perKlant.forEach(function (r) {
        var pct = Math.round((parseFloat(r.uren) / maxUren) * 100);
        html += '<tr>'
          + '<td><div style="display:flex;flex-direction:column;gap:3px">'
          + '<span style="font-weight:600;font-size:.88rem">' + esc(r.opdrachtgever) + '</span>'
          + '<div style="height:4px;border-radius:2px;background:#fce8f2;width:100%">'
          + '<div style="height:4px;border-radius:2px;background:#d31775;width:' + pct + '%"></div></div>'
          + '</div></td>'
          + '<td style="text-align:right;font-weight:700;color:#d31775">' + r.uren + '</td>'
          + '<td style="text-align:right;color:#888">' + r.diensten + '</td>'
          + '</tr>';
      });
      html += '</tbody></table></div>';

      // Rechter kolom: per week
      html += '<div class="psp-rap-kolom">'
        + '<h4 class="psp-rap-kop">Uren per week</h4>'
        + '<table class="psp-table psp-rap-tabel"><thead><tr>'
        + '<th>Week</th><th>Periode</th><th style="text-align:right">Uren</th><th style="text-align:right">Diensten</th>'
        + '</tr></thead><tbody>';

      perWeek.forEach(function (r) {
        var d   = new Date(r.week_start);
        var dVr = new Date(d); dVr.setDate(d.getDate() + 4);
        var maStr = d.getDate() + ' ' + MONTHS[d.getMonth()];
        var vrStr = dVr.getDate() + ' ' + MONTHS[dVr.getMonth()];
        html += '<tr>'
          + '<td><span style="font-weight:700">Wk ' + r.week_nr + '</span></td>'
          + '<td style="color:#888;font-size:.82rem">' + maStr + ' – ' + vrStr + '</td>'
          + '<td style="text-align:right;font-weight:700;color:#d31775">' + r.uren + '</td>'
          + '<td style="text-align:right;color:#888">' + r.diensten + '</td>'
          + '</tr>';
      });
      html += '</tbody></table></div>';

      html += '</div>'; // psp-rap-kolommen
      wrap.innerHTML = html;
    }, function () {
      wrap.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }

  function esc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

})();
