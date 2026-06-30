/* ProStudents Planning — Admin */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initKoppelButtons();
    initVerwijderButtons();
    initVoorkeurTooltips();
  });

  /* ── Koppel student aan dienst ── */
  var pendingKoppel = null;

  function initKoppelButtons() {
    document.querySelectorAll('.psp-koppel-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        pendingKoppel = {
          beschikbaarheid_id: this.dataset.beschikbaarheidId,
          dienst_id:          this.dataset.dienstId,
          naam:               this.dataset.naam,
          dienst:             this.dataset.dienst,
        };
        var vraag = document.getElementById('psp-koppel-vraag');
        vraag.textContent = 'Weet je zeker dat je ' + pendingKoppel.naam + ' wilt koppelen aan "' + pendingKoppel.dienst + '"? De student ontvangt automatisch een bevestigingsmail.';
        document.getElementById('psp-koppel-bevestiging').style.display = 'flex';
      });
    });

    var okBtn = document.getElementById('psp-koppel-ok');
    if (okBtn) {
      okBtn.addEventListener('click', function () {
        if (!pendingKoppel) return;
        document.getElementById('psp-koppel-bevestiging').style.display = 'none';
        okBtn.disabled = true;
        okBtn.textContent = 'Bezig…';

        var body = new URLSearchParams({
          action:              'psp_koppel_student',
          nonce:               pspAdmin.nonce,
          beschikbaarheid_id:  pendingKoppel.beschikbaarheid_id,
          dienst_id:           pendingKoppel.dienst_id,
        });

        fetch(pspAdmin.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.success) {
              showAdminNotice('success', data.data.message);
              setTimeout(function () { location.reload(); }, 1800);
            } else {
              showAdminNotice('error', (data.data && data.data.message) || 'Koppelen mislukt.');
              okBtn.disabled = false;
              okBtn.textContent = 'Bevestigen + mail sturen';
            }
          })
          .catch(function () {
            showAdminNotice('error', 'Verbindingsfout. Probeer opnieuw.');
            okBtn.disabled = false;
            okBtn.textContent = 'Bevestigen + mail sturen';
          });
      });
    }

    var annuleerBtn = document.getElementById('psp-koppel-annuleer');
    if (annuleerBtn) {
      annuleerBtn.addEventListener('click', function () {
        pendingKoppel = null;
        document.getElementById('psp-koppel-bevestiging').style.display = 'none';
      });
    }
  }

  /* ── Koppeling verwijderen ── */
  function initVerwijderButtons() {
    document.querySelectorAll('.psp-verwijder-koppeling').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!confirm('Koppeling verwijderen? De dienst wordt weer als "open" gemarkeerd.')) return;
        var dienstId = this.dataset.dienstId;
        var body = new URLSearchParams({
          action:    'psp_verwijder_koppeling',
          nonce:     pspAdmin.nonce,
          dienst_id: dienstId,
        });
        fetch(pspAdmin.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.success) { location.reload(); }
            else { alert('Verwijderen mislukt.'); }
          });
      });
    });
  }

  /* ── Voorkeur tooltip ── */
  function initVoorkeurTooltips() {
    document.querySelectorAll('.psp-voorkeur').forEach(function (el) {
      el.addEventListener('click', function () {
        alert('Voorkeur: ' + this.title);
      });
    });
  }

  /* ── Admin notice ── */
  function showAdminNotice(type, msg) {
    var existing = document.querySelector('.psp-admin-notice');
    if (existing) existing.remove();
    var cls  = type === 'success' ? 'notice-success' : 'notice-error';
    var div  = document.createElement('div');
    div.className = 'notice ' + cls + ' is-dismissible psp-admin-notice';
    div.style.marginTop = '10px';
    div.innerHTML = '<p>' + msg + '</p>';
    var wrap = document.querySelector('.psp-wrap');
    if (wrap) wrap.prepend(div);
  }
})();
