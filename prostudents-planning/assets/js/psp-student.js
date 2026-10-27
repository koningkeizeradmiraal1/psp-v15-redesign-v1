/* PSP — Mijn Rooster */
(function () {
  'use strict';

  function esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  var ajaxUrl = (typeof pspStudent !== 'undefined') ? pspStudent.ajaxUrl : '/wp-admin/admin-ajax.php';
  var globalNonce = (typeof pspStudent !== 'undefined') ? pspStudent.nonce : '';

  /* ═══ Init ═══ */
  function init() {
    var wrap = document.getElementById('psp-mijn-rooster');
    if (!wrap) return;

    // Tabs
    wrap.querySelectorAll('.psp-rtab').forEach(function (btn) {
      btn.addEventListener('click', function () {
        wrap.querySelectorAll('.psp-rtab').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        wrap.querySelectorAll('.psp-rtab-panel').forEach(function (p) { p.style.display = 'none'; });
        var panel = document.getElementById('psp-rtab-' + btn.dataset.tab);
        if (panel) panel.style.display = '';
      });
    });

    // WB bevestig-knoppen (server-side gerenderd, direct koppelen)
    wrap.querySelectorAll('.psp-wb-bevestig-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var wbId  = btn.dataset.id;
        var nonce = btn.dataset.nonce || globalNonce;
        btn.disabled = true;
        btn.textContent = '…';

        var fd = new FormData();
        fd.append('action', 'psp_wb_bevestig');
        fd.append('nonce',  nonce);
        fd.append('wb_id',  wbId);

        fetch(ajaxUrl, { method: 'POST', body: fd })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            var kaart = document.getElementById('psp-wb-kaart-' + wbId);
            if (res && res.success) {
              if (kaart) kaart.innerHTML = '<div style="padding:20px;color:#15803d;font-weight:600;font-size:1rem">&#10003; Bedankt! Je hebt de werkbevestiging bevestigd.</div>';
              // Verberg sectie als geen openstaande meer
              var sectie  = document.getElementById('psp-wb-sectie');
              var overige = sectie ? sectie.querySelectorAll('.psp-wb-bevestig-btn:not([disabled])') : [];
              if (sectie && !overige.length) setTimeout(function () { sectie.style.display = 'none'; }, 2000);
            } else {
              btn.disabled = false;
              btn.textContent = '✓ Gelezen en akkoord';
              var msg = (res && res.data && res.data.message) ? res.data.message : 'Bevestigen mislukt, probeer opnieuw.';
              alert(msg);
            }
          })
          .catch(function () {
            btn.disabled = false;
            btn.textContent = '✓ Gelezen en akkoord';
            alert('Netwerkfout, probeer opnieuw.');
          });
      });
    });

    // Detailmodal sluiten
    var sluit = document.getElementById('psp-dm-sluit');
    var modal = document.getElementById('psp-dienst-modal');
    if (sluit) sluit.addEventListener('click', function () { if (modal) modal.style.display = 'none'; });
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });

    // Diensten laden via AJAX
    laadDiensten();
  }

  /* ═══ Diensten ═══ */
  function laadDiensten() {
    var nonce = globalNonce || ((document.getElementById('psp_student_nonce') || {}).value || '');
    var fd = new FormData();
    fd.append('action', 'psp_mijn_diensten');
    fd.append('nonce',  nonce);

    fetch(ajaxUrl, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || !res.success) { toonFout('Rooster kon niet worden geladen.'); return; }
        renderDiensten('psp-diensten-komend',   res.data.komend,   true);
        renderDiensten('psp-diensten-verleden', res.data.verleden, false);
      })
      .catch(function () { toonFout('Netwerkfout.'); });
  }

  function renderDiensten(containerId, lijst, isKomend) {
    var el = document.getElementById(containerId);
    if (!el) return;
    if (!lijst || !lijst.length) {
      el.innerHTML = '<p class="psp-rooster-leeg">' + (isKomend ? 'Je hebt nog geen komende diensten.' : 'Geen verleden diensten.') + '</p>';
      return;
    }
    el.innerHTML = lijst.map(function (d) {
      return '<div class="psp-dienst-kaart psp-kaart-klikbaar" data-dienst=\'' + JSON.stringify(d).replace(/'/g,"&#39;") + '\'>'
        + '<div class="psp-dienst-datum">' + esc(d.datum_nl) + '</div>'
        + '<p class="psp-dienst-naam">' + esc(d.opdrachtgever) + (d.titel ? ' <span style="font-weight:400;color:#666">&mdash; ' + esc(d.titel) + '</span>' : '') + '</p>'
        + '<div class="psp-dienst-meta">'
        + '<span>&#128336; ' + esc(d.van) + ' &ndash; ' + esc(d.tot) + '</span>'
        + (d.locatie   ? '<span>&#128205; ' + esc(d.locatie)   + '</span>' : '')
        + (d.type_werk ? '<span>&#128188; ' + esc(d.type_werk) + '</span>' : '')
        + '</div>'
        + '<div class="psp-kaart-klik-hint">Klik voor details &rsaquo;</div>'
        + '</div>';
    }).join('');

    el.querySelectorAll('.psp-dienst-kaart').forEach(function (kaart) {
      kaart.addEventListener('click', function () {
        try { openModal(JSON.parse(kaart.dataset.dienst)); } catch(e) {}
      });
    });
  }

  function openModal(d) {
    var modal  = document.getElementById('psp-dienst-modal');
    var titel  = document.getElementById('psp-dm-titel');
    var info   = document.getElementById('psp-dm-info');
    if (!modal || !titel || !info) return;
    titel.textContent = d.opdrachtgever + (d.titel ? ' — ' + d.titel : '');
    info.innerHTML =
      '<div class="psp-dm-rij"><span class="psp-dm-label">&#128197; Datum</span><span>' + esc(d.datum_nl) + '</span></div>'
      + '<div class="psp-dm-rij"><span class="psp-dm-label">&#128336; Tijd</span><span>' + esc(d.van) + ' – ' + esc(d.tot) + '</span></div>'
      + '<div class="psp-dm-rij"><span class="psp-dm-label">&#127970; Opdrachtgever</span><span>' + esc(d.opdrachtgever) + '</span></div>'
      + (d.locatie    ? '<div class="psp-dm-rij"><span class="psp-dm-label">&#128205; Locatie</span><span>'      + esc(d.locatie)    + '</span></div>' : '')
      + (d.type_werk  ? '<div class="psp-dm-rij"><span class="psp-dm-label">&#128188; Werkzaamheden</span><span>' + esc(d.type_werk)  + '</span></div>' : '')
      + (d.omschrijving ? '<div class="psp-dm-rij" style="align-items:flex-start"><span class="psp-dm-label">&#128221; Info</span><span style="white-space:pre-wrap">' + esc(d.omschrijving) + '</span></div>' : '');
    modal.style.display = 'flex';
  }

  function toonFout(msg) {
    document.querySelectorAll('.psp-rooster-laden').forEach(function (el) { el.textContent = msg; el.style.color = '#c00'; });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
