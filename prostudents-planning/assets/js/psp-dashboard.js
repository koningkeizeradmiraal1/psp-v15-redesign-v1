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

  var DAG_KEYS  = ['ma','di','wo','do','vr','za','zo'];
  var DAG_NAMES = { ma:'Ma', di:'Di', wo:'Wo', do:'Do', vr:'Vr', za:'Za', zo:'Zo' };
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
    if (btn.dataset.tab === 'evenementen' && !_evLoaded) initEvenementenTab();
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
      loadWeekEvenementenStrip(fmt(state.week));
      setLoader(false);
    }, function () { toast('Laden mislukt.', 'error'); setLoader(false); });
  }

  function loadWeekEvenementenStrip(weekStart) {
    ajax('psp_week_evenementen', { week_start: weekStart }, function (data) {
      renderWeekEvStrip(data.items || []);
    });
  }

  function renderWeekEvStrip(items) {
    var el = document.getElementById('psp-week-ev-strip');
    if (!el) return;
    if (!items.length) { el.innerHTML = ''; return; }
    var byDate = {};
    items.forEach(function (ev) {
      if (!byDate[ev.datum]) byDate[ev.datum] = [];
      byDate[ev.datum].push(ev);
    });
    var dates = Object.keys(byDate).sort();
    var html = '<div class="psp-week-ev-strip"><span class="psp-week-ev-label">🗓 Evenementen</span>';
    dates.forEach(function (d) {
      var byOg = {};
      byDate[d].forEach(function (ev) {
        if (!byOg[ev.opdrachtgever]) byOg[ev.opdrachtgever] = [];
        byOg[ev.opdrachtgever].push(ev);
      });
      html += '<div class="psp-week-ev-dag"><span class="psp-week-ev-dag-datum">' + esc(formatDatumNL(d)) + '</span>';
      Object.keys(byOg).sort().forEach(function (og) {
        var evs = byOg[og];
        var tip = evs.map(function (e) { return (e.medewerker || '?') + ': ' + e.dienst_info; }).join('\n');
        html += '<span class="psp-week-ev-og" title="' + esc(tip) + '">' + esc(og) + ' <em>(' + evs.length + '</em>)</span>';
      });
      html += '</div>';
    });
    html += '</div>';
    el.innerHTML = html;
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
    var map = {0:'zo',1:'ma',2:'di',3:'wo',4:'do',5:'vr',6:'za'};
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

  /* ════ Inplannen-view (weekgrid: studenten × dagen) ════ */
  function renderInplannenView(diensten, studenten) {
    if (!diensten)  diensten  = state.data.diensten;
    if (!studenten) studenten = state.data.beschikbaarheid;

    var gridWrap  = document.getElementById('psp-inplannen-grid-wrap');
    var openLijst = document.getElementById('psp-inplannen-open-lijst');
    var summaryEl = document.getElementById('psp-inplannen-summary');

    var openCount = diensten.filter(function (d) { return !isIngepland(d); }).length;
    if (summaryEl) summaryEl.textContent = studenten.length + ' student(en) · ' + diensten.length + ' dienst(en) · ' + openCount + ' open';

    // Datum per dag-key voor deze week
    var dates = {}; DAG_KEYS.forEach(function (dk, i) { dates[dk] = fmt(addDays(state.week, i)); });

    // Diensten per datum, voor snelle lookup
    var dpd = {};
    diensten.forEach(function (d) { if (!dpd[d.datum]) dpd[d.datum] = []; dpd[d.datum].push(d); });

    if (!studenten.length) {
      gridWrap.innerHTML = '<p class="psp-empty-msg">Geen beschikbaarheid ingediend deze week.</p>';
    } else {
      var sorted = studenten.slice().sort(function (a, b) { return a.naam.localeCompare(b.naam); });

      var html = '<table class="psp-ip-grid"><thead><tr><th class="psp-ip-grid-namecol">Student</th>';
      DAG_KEYS.forEach(function (dk) {
        html += '<th>' + DAG_NAMES[dk] + '<br><small style="font-weight:400;opacity:.7">' + fmtNL(new Date(dates[dk])) + '</small></th>';
      });
      html += '</tr></thead><tbody>';

      sorted.forEach(function (s) {
        html += '<tr><td class="psp-ip-grid-namecol"><strong>' + esc(s.naam) + '</strong>';
        if (s.vaardigheden && s.vaardigheden.length) html += renderVaardigheden(s.vaardigheden);
        html += '</td>';
        DAG_KEYS.forEach(function (dk) {
          var datum = dates[dk];
          var dag = s.dagen[dk];
          if (!dag) { html += '<td class="psp-ip-cell niet">–</td>'; return; }

          // Is de student op deze datum al ingepland?
          var kopDienstId = null;
          Object.keys(s.koppelingen).forEach(function (did) {
            var d = diensten.find(function (x) { return x.id === parseInt(did); });
            if (d && d.datum === datum) kopDienstId = parseInt(did);
          });

          if (kopDienstId) {
            var d = diensten.find(function (x) { return x.id === kopDienstId; });
            html += '<td class="psp-ip-cell ingepland">' +
              '<div class="psp-ip-chip"><strong>' + esc(d.opdrachtgever) + '</strong><br>' + d.tijdstip_van + '–' + d.tijdstip_tot +
              '<button class="psp-ip-chip-x" data-dienst-id="' + kopDienstId + '" title="Koppeling verwijderen">✕</button></div></td>';
          } else {
            var openHier = (dpd[datum] || []).filter(function (x) { return !isIngepland(x); }).length;
            if (openHier > 0) {
              html += '<td class="psp-ip-cell beschikbaar klikbaar" data-student-id="' + s.id + '" data-datum="' + datum + '">' +
                '<div class="psp-ip-beschik-tijd">' + dag.van + '–' + dag.tot + '</div>' +
                '<div class="psp-ip-plus">+ ' + openHier + ' open</div></td>';
            } else {
              html += '<td class="psp-ip-cell beschikbaar"><div class="psp-ip-beschik-tijd">' + dag.van + '–' + dag.tot + '</div></td>';
            }
          }
        });
        html += '</tr>';
      });
      html += '</tbody></table>';
      gridWrap.innerHTML = html;

      // Klikbare cel (beschikbaar + er is minstens 1 open dienst die dag) → koppelmodal
      gridWrap.querySelectorAll('.psp-ip-cell.klikbaar').forEach(function (cell) {
        cell.addEventListener('click', function () {
          var sid = parseInt(cell.dataset.studentId);
          var s = studenten.find(function (x) { return x.id === sid; });
          if (s) openKoppelModal(s, cell.dataset.datum, diensten);
        });
      });
      // Ontkoppel-knop op een ingeplande cel
      gridWrap.querySelectorAll('.psp-ip-chip-x').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          if (!confirm('Koppeling verwijderen?')) return;
          ajax('psp_ontkoppel', { dienst_id: btn.dataset.dienstId }, function () {
            toast('Koppeling verwijderd.', 'success'); loadWeek();
          }, function (msg) { toast(msg || 'Mislukt.', 'error'); });
        });
      });
    }

    // Diensten waarvoor helemaal niemand beschikbaar is (los van al ingepland of niet)
    var volledigOpen = diensten.filter(function (d) {
      if (isIngepland(d)) return false;
      var dk = dagKey(d.datum);
      return !studenten.some(function (s) { return !!s.dagen[dk]; });
    });
    if (!openLijst) return;
    if (!volledigOpen.length) {
      openLijst.innerHTML = '<p class="psp-empty-msg">Geen — voor elke openstaande dienst is er minstens één beschikbare student.</p>';
    } else {
      volledigOpen.sort(function (a, b) { return a.datum.localeCompare(b.datum); });
      openLijst.innerHTML = volledigOpen.map(function (d) {
        return '<div class="psp-ip-open-card"><strong>' + esc(d.titel) + '</strong> — ' + esc(d.opdrachtgever) +
          '<div class="psp-ip-dienst-meta">' + fmtNL(new Date(d.datum)) + ' · ' + d.tijdstip_van + '–' + d.tijdstip_tot + (d.locatie ? ' · ' + esc(d.locatie) : '') + '</div></div>';
      }).join('');
    }
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
        if (btn.dataset.stab === 'opdrachtgevers') laadOpdrachtgevers();
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

  /* ════════════════════════════════════════════════════
     AANMELDINGEN
  ════════════════════════════════════════════════════ */
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
        el.innerHTML = '<p class="psp-empty-msg">&#10003; Geen openstaande aanmeldingen.</p>';
        return;
      }
      var html = '<table class="psp-table"><thead><tr>'
        + '<th>Naam</th><th>E-mail</th><th>Telefoon</th><th>Aangemeld op</th><th>Actie</th>'
        + '</tr></thead><tbody>';
      data.forEach(function (r) {
        html += '<tr>'
          + '<td><strong>' + esc(r.naam) + '</strong></td>'
          + '<td>' + esc(r.email) + '</td>'
          + '<td>' + esc(r.telefoon || '—') + '</td>'
          + '<td>' + esc(r.datum || '') + '</td>'
          + '<td style="white-space:nowrap;display:flex;gap:4px">'
          + '<button class="psp-btn-sm psp-btn-primary psp-goedkeur-btn" data-id="' + r.user_id + '" data-naam="' + esc(r.naam) + '">&#10003; Goedkeuren</button>'
          + '<button class="psp-btn-sm psp-btn-danger psp-afwijzen-btn" data-id="' + r.user_id + '" data-naam="' + esc(r.naam) + '">&#10005; Afwijzen</button>'
          + '</td></tr>';
      });
      html += '</tbody></table>';
      el.innerHTML = html;

      el.querySelectorAll('.psp-goedkeur-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Aanmelding van ' + btn.dataset.naam + ' goedkeuren?\nEr wordt een welkomstmail verstuurd.')) return;
          btn.disabled = true; btn.textContent = '…';
          ajax('psp_goedkeur_aanmelding', { user_id: btn.dataset.id }, function (res) {
            toast(res.message, 'success');
            laadAanmeldingen();
            laadAanmeldingenBadge();
            laadStudentenTab();
          }, function (msg) { toast(msg || 'Mislukt.', 'error'); btn.disabled = false; btn.textContent = '✓ Goedkeuren'; });
        });
      });

      el.querySelectorAll('.psp-afwijzen-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Aanmelding van ' + btn.dataset.naam + ' AFWIJZEN en verwijderen?')) return;
          btn.disabled = true; btn.textContent = '…';
          ajax('psp_wijs_af_aanmelding', { user_id: btn.dataset.id }, function (res) {
            toast(res.message, 'success');
            laadAanmeldingen();
            laadAanmeldingenBadge();
          }, function (msg) { toast(msg || 'Mislukt.', 'error'); btn.disabled = false; btn.textContent = '✗ Afwijzen'; });
        });
      });
    }, function () {
      el.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }

  /* ════════════════════════════════════════════════════
     OPDRACHTGEVERS
  ════════════════════════════════════════════════════ */
  var _ogInit = false;

  function laadOpdrachtgevers() {
    var el = document.getElementById('psp-opdrachtgevers-lijst');
    if (!el) return;
    el.innerHTML = '<p class="psp-empty-msg">Laden&#8230;</p>';

    if (!_ogInit) {
      _ogInit = true;
      var nieuwBtn = document.getElementById('psp-og-nieuw-btn');
      if (nieuwBtn) nieuwBtn.addEventListener('click', function () { openOgModal(null); });

      document.querySelectorAll('[data-modal="psp-modal-og"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          document.getElementById('psp-modal-og').style.display = 'none';
        });
      });

      var form = document.getElementById('psp-og-form');
      if (form) form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = document.getElementById('psp-og-opslaan-btn');
        btn.disabled = true; btn.textContent = '…';
        var fd = {
          id:             document.getElementById('psp-og-id').value,
          naam:           document.getElementById('psp-og-naam').value,
          contactpersoon: document.getElementById('psp-og-contact').value,
          email:          document.getElementById('psp-og-email').value,
          telefoon:       document.getElementById('psp-og-telefoon').value,
          adres:          document.getElementById('psp-og-adres').value,
          notities:       document.getElementById('psp-og-notities').value,
        };
        ajax('psp_save_opdrachtgever', fd, function (res) {
          toast(res.message || '✓ Opgeslagen.', 'success');
          document.getElementById('psp-modal-og').style.display = 'none';
          laadOpdrachtgevers();
          btn.disabled = false; btn.textContent = 'Opslaan';
        }, function (msg) {
          toast(msg || 'Opslaan mislukt.', 'error');
          btn.disabled = false; btn.textContent = 'Opslaan';
        });
      });
    }

    ajax('psp_get_opdrachtgevers', {}, function (data) {
      if (!Array.isArray(data) || !data.length) {
        el.innerHTML = '<p class="psp-empty-msg">Nog geen opdrachtgevers toegevoegd.</p>';
        return;
      }
      var html = '<table class="psp-table"><thead><tr>'
        + '<th>Naam</th><th>Contactpersoon</th><th>E-mail</th><th>Telefoon</th><th>Acties</th>'
        + '</tr></thead><tbody>';
      data.forEach(function (r) {
        html += '<tr>'
          + '<td><strong>' + esc(r.naam) + '</strong>'
          + (r.adres ? '<br><small style="color:#888">' + esc(r.adres) + '</small>' : '')
          + '</td>'
          + '<td>' + esc(r.contactpersoon || '—') + '</td>'
          + '<td>' + (r.email ? '<a href="mailto:' + esc(r.email) + '">' + esc(r.email) + '</a>' : '—') + '</td>'
          + '<td>' + esc(r.telefoon || '—') + '</td>'
          + '<td style="white-space:nowrap;display:flex;gap:4px">'
          + '<button class="psp-btn-sm psp-btn-ghost psp-og-edit-btn" data-id="' + r.id + '">✎ Bewerken</button>'
          + '<button class="psp-btn-sm psp-btn-danger psp-og-del-btn" data-id="' + r.id + '" data-naam="' + esc(r.naam) + '">✕ Verwijder</button>'
          + '</td></tr>';
      });
      html += '</tbody></table>';
      el.innerHTML = html;

      // Bewerken
      el.querySelectorAll('.psp-og-edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var id = btn.dataset.id;
          var r  = data.find(function (x) { return String(x.id) === String(id); });
          if (r) openOgModal(r);
        });
      });

      // Verwijderen
      el.querySelectorAll('.psp-og-del-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Opdrachtgever "' + btn.dataset.naam + '" verwijderen?')) return;
          btn.disabled = true;
          ajax('psp_delete_opdrachtgever', { id: btn.dataset.id }, function (res) {
            toast(res.message || 'Verwijderd.', 'success');
            laadOpdrachtgevers();
          }, function (msg) { toast(msg || 'Mislukt.', 'error'); btn.disabled = false; });
        });
      });
    }, function () {
      el.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }

  function openOgModal(r) {
    var modal = document.getElementById('psp-modal-og');
    if (!modal) return;
    document.getElementById('psp-modal-og-title').textContent = r ? 'Opdrachtgever bewerken' : 'Nieuwe opdrachtgever';
    document.getElementById('psp-og-id').value       = r ? r.id : '';
    document.getElementById('psp-og-naam').value     = r ? r.naam : '';
    document.getElementById('psp-og-contact').value  = r ? (r.contactpersoon || '') : '';
    document.getElementById('psp-og-email').value    = r ? (r.email || '') : '';
    document.getElementById('psp-og-telefoon').value = r ? (r.telefoon || '') : '';
    document.getElementById('psp-og-adres').value    = r ? (r.adres || '') : '';
    document.getElementById('psp-og-notities').value = r ? (r.notities || '') : '';
    var saveBtn = document.getElementById('psp-og-opslaan-btn');
    if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Opslaan'; }
    modal.style.display = 'flex';
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
  var _studentNieuwInit  = false;

  function laadStudentenTab() {
    var el = document.getElementById('psp-studenten-accounts-lijst');
    if (!el) return;
    el.innerHTML = '<p class="psp-empty-msg">Laden&#8230;</p>';
    _studentenGeladen = false;

    if (!_studentNieuwInit) {
      _studentNieuwInit = true;

      var nieuwBtn = document.getElementById('psp-student-nieuw-btn');
      if (nieuwBtn) nieuwBtn.addEventListener('click', function () {
        document.getElementById('psp-student-nieuw-form').reset();
        document.getElementById('psp-modal-nieuwe-student').style.display = 'flex';
      });

      document.querySelectorAll('[data-modal="psp-modal-nieuwe-student"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          document.getElementById('psp-modal-nieuwe-student').style.display = 'none';
        });
      });

      var nieuwForm = document.getElementById('psp-student-nieuw-form');
      if (nieuwForm) nieuwForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var naam  = document.getElementById('psp-student-nieuw-naam').value.trim();
        var email = document.getElementById('psp-student-nieuw-email').value.trim();
        if (!email) { toast('E-mailadres is verplicht.', 'error'); return; }

        var btn = document.getElementById('psp-student-nieuw-opslaan-btn');
        btn.disabled = true; btn.textContent = '…';
        ajax('psp_maak_student_account', { email: email, naam: naam }, function (res) {
          document.getElementById('psp-modal-nieuwe-student').style.display = 'none';
          document.getElementById('psp-acc-login').textContent  = res.login;
          document.getElementById('psp-acc-email').textContent  = res.email;
          document.getElementById('psp-acc-ww').textContent     = res.wachtwoord;
          var urlEl = document.getElementById('psp-acc-url');
          urlEl.href = res.login_url; urlEl.textContent = res.login_url;
          document.getElementById('psp-modal-account').style.display = 'flex';
          btn.disabled = false; btn.textContent = 'Account aanmaken';
          laadStudentenTab();
        }, function (msg) {
          toast(msg || 'Account aanmaken mislukt.', 'error');
          btn.disabled = false; btn.textContent = 'Account aanmaken';
        });
      });
    }

    ajax('psp_get_studenten', {}, function (data) {
      _studentenGeladen = true;
      if (!Array.isArray(data) || !data.length) {
        el.innerHTML = '<p class="psp-empty-msg">Geen studenten gevonden in de beschikbaarheidslijst.</p>';
        return;
      }

      var html = '<table class="psp-table"><thead><tr>'
        + '<th>Naam</th><th>E-mail</th><th>Account</th><th>Vaardigheden</th><th></th>'
        + '</tr></thead><tbody>';

      // Toon vaardigheidsbadges
      function vaardBadges(vaard) {
        if (!vaard || typeof vaard !== 'object' || Array.isArray(vaard)) return '<span style="color:#bbb;font-size:.78rem">—</span>';
        var def = (pspDash && pspDash.vaardighedenDef) ? pspDash.vaardighedenDef : {};
        var badges = [];
        Object.keys(vaard).forEach(function(k) {
          var v = vaard[k];
          if (!v || v === '0' || v === '') return;
          if (!def[k]) return;
          var label = def[k].label;
          if (v === '1') {
            badges.push('<span class="psp-vaard-badge">' + esc(label) + '</span>');
          } else {
            badges.push('<span class="psp-vaard-badge">' + esc(label) + ': ' + esc(v) + '</span>');
          }
        });
        return badges.length ? badges.join('') : '<span style="color:#bbb;font-size:.78rem">Geen</span>';
      }

      data.forEach(function (s) {
        html += '<tr data-user-id="' + (s.user_id || 0) + '" data-email="' + esc(s.email) + '" data-naam="' + esc(s.naam) + '">'
          + '<td><strong>' + esc(s.naam) + '</strong></td>'
          + '<td><small>' + esc(s.email) + '</small></td>'
          + '<td>' + ( s.has_account
              ? '<span class="psp-badge-groen">&#10003; Actief</span>'
              : '<button class="psp-btn-sm psp-btn-primary psp-maak-acc-btn">Account aanmaken</button>' )
          + '</td>'
          + '<td class="psp-vaard-badges-cel">' + vaardBadges(s.vaardigheden) + '</td>'
          + '<td style="white-space:nowrap;display:flex;gap:4px;flex-wrap:wrap">'
          + ( s.has_account
              ? '<button class="psp-btn-sm psp-btn-ghost psp-edit-vaard-btn" '
                + 'data-user-id="' + (s.user_id||0) + '" '
                + 'data-naam="' + esc(s.naam) + '" '
                + 'data-vaard=\'' + JSON.stringify(s.vaardigheden||{}).replace(/\'/g,"&#39;") + '\''
                + '>✎ Vaardigheden</button>'
                + '<button class="psp-btn-sm psp-btn-ghost psp-stuur-welkom-btn" '
                + 'data-user-id="' + (s.user_id||0) + '" '
                + 'data-naam="' + esc(s.naam) + '"'
                + '>&#9993; Welkomsmail</button>'
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
            laadStudentenTab();
          }, function (msg) {
            toast(msg || 'Account aanmaken mislukt.', 'error');
            btn.disabled = false; btn.textContent = 'Account aanmaken';
          });
        });
      });

      // Vaardigheden bewerken -> modal
      el.querySelectorAll('.psp-edit-vaard-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          openVaardModal(
            parseInt(btn.dataset.userId || 0),
            btn.dataset.naam,
            JSON.parse(btn.dataset.vaard || '{}')
          );
        });
      });

      // Welkomsmail versturen
      el.querySelectorAll('.psp-stuur-welkom-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('Welkomsmail sturen naar ' + btn.dataset.naam + '?')) return;
          btn.disabled = true; btn.textContent = '…';
          ajax('psp_stuur_welkomstmail', { user_id: btn.dataset.userId }, function (res) {
            toast(res.message || '✓ Welkomsmail verstuurd.', 'success');
            btn.disabled = false; btn.innerHTML = '&#9993; Welkomsmail';
          }, function (msg) {
            toast(msg || 'Versturen mislukt.', 'error');
            btn.disabled = false; btn.innerHTML = '&#9993; Welkomsmail';
          });
        });
      });

    }, function () {
      el.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }

  /* -- Vaardigheden modal ------------------------------------------------- */
  function openVaardModal(userId, naam, huidig) {
    var def   = (pspDash && pspDash.vaardighedenDef) ? pspDash.vaardighedenDef : {};
    var modal = document.getElementById('psp-modal-vaardigheden');
    if (!modal) return;
    document.getElementById('psp-vaard-modal-naam').textContent = naam;
    var form = document.getElementById('psp-vaard-modal-form');
    form.dataset.userId = userId;

    var groepen = {};
    Object.keys(def).forEach(function(k) {
      var g = def[k].groep || 'Overig';
      if (!groepen[g]) groepen[g] = [];
      groepen[g].push(k);
    });

    var html = '';
    Object.keys(groepen).forEach(function(groep) {
      html += '<div class="psp-vaard-groep"><div class="psp-vaard-groep-titel">' + esc(groep) + '</div><div class="psp-vaard-groep-items">';
      groepen[groep].forEach(function(k) {
        var veld = def[k];
        var val  = (huidig && huidig[k] !== undefined) ? huidig[k] : '';
        if (veld.type === 'checkbox') {
          var chk = (val === '1') ? 'checked' : '';
          html += '<label class="psp-vaard-check-lbl"><input type="checkbox" name="vaard[' + esc(k) + ']" value="1" ' + chk + '> ' + esc(veld.label) + '</label>';
        } else if (veld.type === 'select') {
          html += '<label class="psp-vaard-select-lbl">' + esc(veld.label) + ' <select name="vaard[' + esc(k) + ']">';
          Object.keys(veld.opties).forEach(function(ov) {
            html += '<option value="' + esc(ov) + '"' + (val === ov ? ' selected' : '') + '>' + esc(veld.opties[ov]) + '</option>';
          });
          html += '</select></label>';
        } else {
          html += '<label class="psp-vaard-text-lbl">' + esc(veld.label) + ' <textarea name="vaard[' + esc(k) + ']" rows="3">' + esc(val) + '</textarea></label>';
        }
      });
      html += '</div></div>';
    });
    form.querySelector('.psp-vaard-velden').innerHTML = html;
    modal.style.display = 'flex';
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Sluiten vaardigheden modal
    var sluitVaard = document.getElementById('psp-vaard-modal-sluit');
    if (sluitVaard) sluitVaard.addEventListener('click', function() {
      document.getElementById('psp-modal-vaardigheden').style.display = 'none';
    });
    // Opslaan vaardigheden
    var opslaanVaard = document.getElementById('psp-vaard-modal-opslaan');
    if (opslaanVaard) opslaanVaard.addEventListener('click', function() {
      var form   = document.getElementById('psp-vaard-modal-form');
      var userId = parseInt(form.dataset.userId || 0);
      var def    = (pspDash && pspDash.vaardighedenDef) ? pspDash.vaardighedenDef : {};
      var vaard  = {};
      // Checkboxes
      form.querySelectorAll('input[type=checkbox][name^="vaard["]').forEach(function(inp) {
        var k = inp.name.replace('vaard[','').replace(']','');
        vaard[k] = inp.checked ? '1' : '0';
      });
      // Selects
      form.querySelectorAll('select[name^="vaard["]').forEach(function(sel) {
        var k = sel.name.replace('vaard[','').replace(']','');
        vaard[k] = sel.value;
      });
      // Textareas
      form.querySelectorAll('textarea[name^="vaard["]').forEach(function(ta) {
        var k = ta.name.replace('vaard[','').replace(']','');
        vaard[k] = ta.value;
      });
      opslaanVaard.disabled = true; opslaanVaard.textContent = '…';
      ajax('psp_save_student_vaardigheden', { user_id: userId, vaardigheden: vaard }, function(res) {
        toast(res.message || '✓ Opgeslagen.', 'success');
        document.getElementById('psp-modal-vaardigheden').style.display = 'none';
        laadStudentenTab();
        opslaanVaard.disabled = false; opslaanVaard.textContent = 'Opslaan';
      }, function(msg) {
        toast(msg || 'Opslaan mislukt.', 'error');
        opslaanVaard.disabled = false; opslaanVaard.textContent = 'Opslaan';
      });
    });
  });

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

  var WB_DEFAULT_ONDERWERP = 'Bevestiging dienst {datum}';
  var WB_DEFAULT_INHOUD =
    'Beste {naam},\n\n' +
    'Hierbij bevestigen wij jouw dienst bij {opdrachtgever}:\n\n' +
    'Datum:      {datum}\n' +
    'Tijdstip:   {van} – {tot}\n' +
    'Locatie:    {locatie}\n' +
    'Type werk:  {type_werk}\n\n' +
    'Klik op de onderstaande link om te bevestigen dat je deze werkbevestiging hebt ontvangen en gelezen:\n' +
    '{bevestig_link}\n\n' +
    'Met vriendelijke groet,\nProStudents';

  function openWbModal(r) {
    // Bij een NIEUWE template vullen we het veld al met de standaardtekst + variabelen in
    // (als echte inhoud, niet als grijze placeholder) — medewerkers kennen de {variabelen}
    // niet uit zichzelf en deze tekst mag dus niet verdwijnen zodra ze gaan typen.
    document.getElementById('psp-wb-id').value          = r ? r.id : '';
    document.getElementById('psp-wb-opdrachtgever').value = r ? (r.opdrachtgever || '') : '';
    document.getElementById('psp-wb-naam').value        = r ? (r.naam || '') : '';
    document.getElementById('psp-wb-onderwerp').value   = r ? (r.onderwerp || '') : WB_DEFAULT_ONDERWERP;
    document.getElementById('psp-wb-inhoud').value      = r ? (r.inhoud || '') : WB_DEFAULT_INHOUD;
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
    r.push('Je kunt ook inloggen op het portaal en daar op “Gelezen en akkoord” klikken.');
    r.push('');
    r.push('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    r.push('');
    r.push('Met vriendelijke groet,');
    r.push('');
    r.push('ProStudents');
    r.push('📞 050 – 311 23 22  |  📧 info@prostudents.nl');
    r.push('');
    return r.join('\n');
  }

  /* ════ WB stuur modal openen (vanuit koppel-flow én kaartknop) ════ */
  function toonWbStuurModal(opts) {
    var dienstId = opts.dienst_id;
    var email    = opts.student_email;
    var naam     = opts.student_naam;
    var og       = opts.opdrachtgever;

    var modal = document.getElementById('psp-modal-wb-stuur');
    if (!modal) return;
    modal.dataset.dienstId      = dienstId;
    modal.dataset.studentEmail  = email;
    modal.dataset.opdrachtgever = og;

    document.getElementById('psp-wbs-dienst-id').value     = dienstId;
    document.getElementById('psp-wbs-student-email').value  = email;
    document.getElementById('psp-wbs-opdrachtgever').value  = og;
    document.getElementById('psp-wbs-aan').value            = naam + ' <' + email + '>';

    var statusEl = document.getElementById('psp-wbs-status-info');
    if (statusEl) statusEl.style.display = 'none';

    ajax('psp_wb_templates_voor_og', { opdrachtgever: og, dienst_id: dienstId }, function (res) {
      var templates = Array.isArray(res.templates) ? res.templates : [];
      var bestaande = res.bestaande || null;
      var templBtns = document.getElementById('psp-wbs-template-btns');
      var templWrap = document.getElementById('psp-wbs-template-keuze');

      if (templates.length && templBtns && templWrap) {
        var ctx = { naam: naam, og: og, bevestig_link: '' };
        templBtns.innerHTML = templates.map(function (t) {
          return '<button type="button" class="psp-btn-sm psp-btn-ghost psp-wbs-templ-btn" data-id="' + t.id + '">' + esc(t.naam) + '</button>';
        }).join('');
        templWrap.style.display = '';
        templBtns.querySelectorAll('.psp-wbs-templ-btn').forEach(function (tb) {
          tb.addEventListener('click', function () {
            var t = templates.find(function (x) { return String(x.id) === tb.dataset.id; });
            if (!t) return;
            document.getElementById('psp-wbs-onderwerp').value = replacePlaceholders(t.onderwerp, ctx);
            document.getElementById('psp-wbs-inhoud').value    = replacePlaceholders(t.inhoud, ctx);
          });
        });
      } else if (templWrap) {
        templWrap.style.display = 'none';
      }

      if (bestaande) {
        document.getElementById('psp-wbs-onderwerp').value = bestaande.onderwerp || '';
        document.getElementById('psp-wbs-inhoud').value    = bestaande.inhoud    || '';
        if (statusEl) {
          statusEl.style.display    = '';
          statusEl.style.background = bestaande.status === 'bevestigd' ? '#f0fdf4' : '#fffbeb';
          statusEl.style.color      = bestaande.status === 'bevestigd' ? '#15803d' : '#92400e';
          statusEl.textContent = bestaande.status === 'bevestigd'
            ? '\u2713 Student heeft bevestigd op ' + (bestaande.bevestigd_op || '').substring(0, 16)
            : '\u{1F4E7} Eerder verstuurd op ' + (bestaande.verzonden_op || '').substring(0, 16);
        }
      } else {
        document.getElementById('psp-wbs-onderwerp').value = 'Werkbevestiging ' + og;
        document.getElementById('psp-wbs-inhoud').value    = defaultWbInhoud({ naam: naam, og: og, bevestig_link: '[BEVESTIG LINK]' });
      }

      modal.style.display = 'flex';
    }, function () {
      document.getElementById('psp-wbs-onderwerp').value = 'Werkbevestiging ' + og;
      document.getElementById('psp-wbs-inhoud').value    = defaultWbInhoud({ naam: naam, og: og, bevestig_link: '[BEVESTIG LINK]' });
      modal.style.display = 'flex';
    });
  }

  /* ════════════════════════════════════════════════════
     WERKBEVESTIGING VERSTUREN — modal init
  ════════════════════════════════════════════════════ */
  (function () {
    document.addEventListener('DOMContentLoaded', function () {

      // Sluit-knoppen
      document.querySelectorAll('[data-modal="psp-modal-wb-stuur"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          document.getElementById('psp-modal-wb-stuur').style.display = 'none';
        });
      });

      // Open modal via kaartknop (event delegation) — roept toonWbStuurModal aan
      document.body.addEventListener('click', function (e) {
        var btn = e.target.closest('.psp-wb-stuur-kaart-btn');
        if (!btn) return;
        toonWbStuurModal({
          dienst_id:     btn.dataset.dienstId,
          student_email: btn.dataset.email,
          student_naam:  btn.dataset.naam,
          opdrachtgever: btn.dataset.og,
        });
      });

      // Versturen
      var stuurBtn = document.getElementById('psp-wbs-stuur-btn');
      if (stuurBtn) {
        stuurBtn.addEventListener('click', function () {
          var modal = document.getElementById('psp-modal-wb-stuur');
          var fd = {
            dienst_id:     modal.dataset.dienstId     || document.getElementById('psp-wbs-dienst-id').value,
            student_email: modal.dataset.studentEmail || document.getElementById('psp-wbs-student-email').value,
            opdrachtgever: modal.dataset.opdrachtgever|| document.getElementById('psp-wbs-opdrachtgever').value,
            onderwerp:     document.getElementById('psp-wbs-onderwerp').value,
            inhoud:        document.getElementById('psp-wbs-inhoud').value,
          };
          stuurBtn.disabled = true; stuurBtn.textContent = '…';
          ajax('psp_wb_stuur', fd, function (res) {
            modal.style.display = 'none';
            toast(res.message || '✓ Verstuurd.', 'success');
            loadWeek();
            stuurBtn.disabled = false; stuurBtn.innerHTML = '📨 Versturen';
          }, function (msg) {
            toast(msg || 'Versturen mislukt.', 'error');
            stuurBtn.disabled = false; stuurBtn.innerHTML = '📨 Versturen';
          });
        });
      }
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
        el.innerHTML = '<p class="psp-empty-msg">✓ Geen nieuwe bevestigingen.</p>';
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

  /* ════════════════════════════════════════════════════
     RAPPORTAGE / URENOVERZICHT
  ════════════════════════════════════════════════════ */
  var _rapInit = false;

  function initRapportageTab() {
    laadUrenoverzicht();
    if (_rapInit) return;
    _rapInit = true;
    var btn = document.getElementById('psp-rap-laad-btn');
    if (btn) btn.addEventListener('click', function () { laadUrenoverzicht(); });
  }

  function laadUrenoverzicht() {
    var wrap = document.getElementById('psp-rapportage-wrap');
    if (!wrap) return;
    var jaarEl = document.getElementById('psp-rap-jaar');
    var jaar   = jaarEl ? jaarEl.value : new Date().getFullYear();
    wrap.innerHTML = '<p class="psp-empty-msg">Laden&#8230;</p>';
    ajax('psp_urenoverzicht', { jaar: jaar }, function (data) {
      if (!Array.isArray(data) || !data.length) {
        wrap.innerHTML = '<p class="psp-empty-msg">Geen gegevens gevonden voor ' + esc(String(jaar)) + '.</p>';
        return;
      }
      var html = '<table class="psp-table"><thead><tr>'
        + '<th>Student</th><th>Opdrachtgever</th><th>Diensten</th><th>Uren</th>'
        + '</tr></thead><tbody>';
      data.forEach(function (r) {
        html += '<tr>'
          + '<td>' + esc(r.student_email || '') + '</td>'
          + '<td>' + esc(r.opdrachtgever || '') + '</td>'
          + '<td style="text-align:center">' + esc(String(r.aantal_diensten || 0)) + '</td>'
          + '<td style="text-align:center"><strong>' + esc(String(r.uren || '0')) + '</strong></td>'
          + '</tr>';
      });
      html += '</tbody></table>';
      wrap.innerHTML = html;
    }, function () {
      wrap.innerHTML = '<p class="psp-empty-msg" style="color:#c00">Laden mislukt.</p>';
    });
  }

  /* ══════════════════════════════════════════════════════════════
     EVENEMENTEN TAB
  ══════════════════════════════════════════════════════════════ */
  var _evLoaded = false;
  var _evFilter = '';

  function initEvenementenTab() {
    _evLoaded = true;

    // Filter dropdown
    document.getElementById('psp-ev-filter-og').addEventListener('change', function() {
      _evFilter = this.value;
      loadEvenementen();
    });

    // Nieuw-knop
    document.getElementById('psp-ev-nieuw-btn').addEventListener('click', function() {
      openEvModal(null);
    });

    // Opslaan
    document.getElementById('psp-ev-opslaan-btn').addEventListener('click', saveEvenement);

    // Verwijderen
    document.getElementById('psp-ev-verwijder-btn').addEventListener('click', function() {
      var id = document.getElementById('psp-ev-id').value;
      if (!id || !confirm('Weet je zeker dat je dit evenement wilt verwijderen?')) return;
      ajax('psp_delete_evenement', {id: id}, function() {
        closeModal('psp-modal-evenement');
        loadEvenementen();
        toast('Evenement verwijderd.');
      });
    });

    loadEvenementen();
  }

  function loadEvenementen() {
    ajax('psp_get_evenementen', {og: _evFilter}, function(data) {
      // Update filter dropdown
      var sel = document.getElementById('psp-ev-filter-og');
      var cur = sel.value;
      sel.innerHTML = '<option value="">— Alle —</option>';
      (data.opdrachtgevers || []).forEach(function(og) {
        var opt = document.createElement('option');
        opt.value = og; opt.textContent = og;
        if (og === cur) opt.selected = true;
        sel.appendChild(opt);
      });
      renderEvenementen(data.items || []);
    });
  }

  function renderEvenementen(items) {
    var lijst = document.getElementById('psp-ev-lijst');
    var count = document.getElementById('psp-ev-count');
    if (!items.length) {
      lijst.innerHTML = '<p class="psp-empty-msg">Geen komende evenementen.</p>';
      count.textContent = '';
      return;
    }
    count.textContent = items.length + ' evenement' + (items.length !== 1 ? 'en' : '');

    // Groepeer op ISO-week
    var weken = {};
    items.forEach(function(item) {
      var w = getISOWeekKey(item.datum);
      if (!weken[w]) weken[w] = {label: getISOWeekLabel(item.datum), ogs: {}};
      var og = item.opdrachtgever || '(geen)';
      if (!weken[w].ogs[og]) weken[w].ogs[og] = [];
      weken[w].ogs[og].push(item);
    });

    var html = '';
    Object.keys(weken).sort().forEach(function(wk) {
      var week = weken[wk];
      html += '<div class="psp-ev-week-header">' + escHtml(week.label) + '</div>';
      Object.keys(week.ogs).sort().forEach(function(og) {
        html += '<div class="psp-ev-og-naam">' + escHtml(og) + '</div>';
        html += '<table class="psp-ev-tabel"><thead><tr>';
        html += '<th>Datum</th><th>Medewerker</th><th>Dienst</th><th>Notities</th><th></th>';
        html += '</tr></thead><tbody>';
        week.ogs[og].forEach(function(item) {
          var med = item.medewerker
            ? escHtml(item.medewerker)
            : '<span class="psp-ev-med-leeg">Nog in te vullen</span>';
          html += '<tr>';
          html += '<td>' + formatDatumNL(item.datum) + '</td>';
          html += '<td>' + med + '</td>';
          html += '<td>' + escHtml(item.dienst_info) + '</td>';
          html += '<td>' + escHtml(item.notities || '') + '</td>';
          html += '<td><button class="psp-ev-edit-btn" data-id="' + item.id + '" title="Bewerken">&#9998;</button></td>';
          html += '</tr>';
        });
        html += '</tbody></table>';
      });
    });
    lijst.innerHTML = html;

    // Edit buttons
    lijst.querySelectorAll('.psp-ev-edit-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var id = parseInt(this.dataset.id);
        var item = items.find(function(i) { return parseInt(i.id) === id; });
        if (item) openEvModal(item);
      });
    });
  }

  function openEvModal(item) {
    document.getElementById('psp-modal-ev-title').textContent = item ? 'Evenement bewerken' : 'Nieuw evenement';
    document.getElementById('psp-ev-id').value      = item ? item.id        : '';
    document.getElementById('psp-ev-datum').value   = item ? item.datum     : '';
    document.getElementById('psp-ev-og').value      = item ? item.opdrachtgever : '';
    document.getElementById('psp-ev-medewerker').value = item ? item.medewerker : '';
    document.getElementById('psp-ev-info').value    = item ? item.dienst_info : '';
    document.getElementById('psp-ev-notities').value = item ? item.notities  : '';
    document.getElementById('psp-ev-verwijder-btn').style.display = item ? '' : 'none';
    openModal('psp-modal-evenement');
  }

  function saveEvenement() {
    var id   = document.getElementById('psp-ev-id').value;
    var datum = document.getElementById('psp-ev-datum').value;
    if (!datum) { alert('Vul een datum in.'); return; }
    var payload = {
      id:             id,
      datum:          datum,
      opdrachtgever:  document.getElementById('psp-ev-og').value,
      medewerker:     document.getElementById('psp-ev-medewerker').value,
      dienst_info:    document.getElementById('psp-ev-info').value,
      notities:       document.getElementById('psp-ev-notities').value,
    };
    ajax('psp_save_evenement', payload, function() {
      closeModal('psp-modal-evenement');
      loadEvenementen();
      toast(id ? 'Evenement bijgewerkt.' : 'Evenement aangemaakt.');
    });
  }

  function getISOWeekKey(dateStr) {
    var d = new Date(dateStr);
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() + 4 - (d.getDay() || 7));
    var y = d.getFullYear();
    var w = Math.ceil(((d - new Date(y, 0, 1)) / 86400000 + 1) / 7);
    return y + '-' + String(w).padStart(2, '0');
  }

  function getISOWeekLabel(dateStr) {
    var d = new Date(dateStr);
    d.setHours(0, 0, 0, 0);
    var day = d.getDay() || 7;
    d.setDate(d.getDate() + 4 - day);
    var y = d.getFullYear();
    var w = Math.ceil(((d - new Date(y, 0, 1)) / 86400000 + 1) / 7);
    // Monday of that week
    var mon = new Date(d); mon.setDate(d.getDate() - 3);
    var sun = new Date(mon); sun.setDate(mon.getDate() + 6);
    var fmt = function(dt) {
      return dt.getDate() + ' ' + ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'][dt.getMonth()];
    };
    return 'Week ' + w + ' (' + y + ')  •  ' + fmt(mon) + ' – ' + fmt(sun);
  }

  function formatDatumNL(dateStr) {
    var d = new Date(dateStr + 'T00:00:00');
    var dag = ['zo','ma','di','wo','do','vr','za'][d.getDay()];
    var mnd = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'][d.getMonth()];
    return dag + ' ' + d.getDate() + ' ' + mnd;
  }

  function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }


})();
