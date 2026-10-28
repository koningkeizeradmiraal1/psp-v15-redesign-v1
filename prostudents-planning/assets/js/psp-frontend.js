/* ProStudents Planning — Frontend */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initCheckboxes();
    initFormSubmit();
    initLockedDays();
    initDagDatums();
  });

  /* ── Dag-checkboxes: tijd-inputs aan/uit ── */
  function initCheckboxes() {
    document.querySelectorAll('.psp-dag-checkbox').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var row = this.closest('.psp-dag-row');
        if (!row) return;
        row.querySelectorAll('.psp-time').forEach(function (inp) {
          inp.disabled = !cb.checked;
        });
        row.classList.toggle('psp-dag-actief', cb.checked);
      });
    });
  }

  /* ── Dagen die al ingepland zijn of binnen 24 uur vallen: grijs/disabled tonen ── */
  function initLockedDays() {
    var weekSelect = document.getElementById('psp-week');
    if (!weekSelect) return;

    function applyLockedDays() {
      var data  = (window.pspLockedData && window.pspLockedData[weekSelect.value]) || {};
      var eigen = (window.pspEigenData  && window.pspEigenData[weekSelect.value])  || {};
      document.querySelectorAll('.psp-dag-row').forEach(function (row) {
        var dag      = row.dataset.dag;
        var checkbox = row.querySelector('.psp-dag-checkbox');
        var vanInput = row.querySelector('input[name$="_van"]');
        var totInput = row.querySelector('input[name$="_tot"]');
        var lockIcon = row.querySelector('.psp-dag-lock');
        var locked   = Object.prototype.hasOwnProperty.call(data, dag);

        // Eerder doorgegeven beschikbaarheid voorinvullen (zodat extra dagen toevoegen niets wist)
        if (!locked) {
          if (eigen[dag]) {
            checkbox.checked = true;
            vanInput.value   = eigen[dag].van;
            totInput.value   = eigen[dag].tot;
          } else {
            checkbox.checked = false;
            vanInput.value   = '09:00';
            totInput.value   = '17:00';
          }
          row.classList.toggle('psp-dag-actief', checkbox.checked);
        }

        checkbox.disabled = locked;
        if (lockIcon) lockIcon.style.display = locked ? 'inline' : 'none';
        row.classList.toggle('psp-dag-locked', locked);

        if (locked) {
          var waarde = data[dag];
          if (waarde) {
            checkbox.checked = true;
            vanInput.value   = waarde.van;
            totInput.value   = waarde.tot;
            row.classList.add('psp-dag-actief');
          } else {
            checkbox.checked = false;
            row.classList.remove('psp-dag-actief');
          }
          vanInput.disabled = true;
          totInput.disabled = true;
        } else {
          vanInput.disabled = !checkbox.checked;
          totInput.disabled = !checkbox.checked;
        }
      });
    }

    var nietCb = document.getElementById('psp-niet-beschikbaar');

    // "Niet beschikbaar" aan: alle nog aanpasbare dagen uitschakelen
    function applyNiet() {
      if (!nietCb || !nietCb.checked) return;
      var data = (window.pspLockedData && window.pspLockedData[weekSelect.value]) || {};
      document.querySelectorAll('.psp-dag-row').forEach(function (row) {
        if (Object.prototype.hasOwnProperty.call(data, row.dataset.dag)) return; // vergrendeld: blijft staan
        var cb = row.querySelector('.psp-dag-checkbox');
        cb.checked = false; cb.disabled = true;
        row.querySelectorAll('.psp-time').forEach(function (i) { i.disabled = true; });
        row.classList.remove('psp-dag-actief');
        row.style.opacity = '.5';
      });
    }

    function volledig() {
      document.querySelectorAll('.psp-dag-row').forEach(function (r) { r.style.opacity = ''; });
      if (nietCb) nietCb.checked = !!(window.pspNietData && window.pspNietData[weekSelect.value]);
      applyLockedDays();
      applyNiet();
    }

    if (nietCb) nietCb.addEventListener('change', function () {
      document.querySelectorAll('.psp-dag-row').forEach(function (r) { r.style.opacity = ''; });
      applyLockedDays();
      applyNiet();
    });

    volledig();
    weekSelect.addEventListener('change', volledig);
  }

  /* ── Datum bij elke dag (bijv. "Maandag 2 nov") op basis van de gekozen week ── */
  function initDagDatums() {
    var weekSelect = document.getElementById('psp-week');
    if (!weekSelect) return;
    var MAANDEN = ['jan','feb','mrt','apr','mei','jun','jul','aug','sep','okt','nov','dec'];
    var OFFSET  = { ma:0, di:1, wo:2, do:3, vr:4, za:5, zo:6 };

    function update() {
      var p = weekSelect.value.split('-');           // YYYY-MM-DD (maandag van de week)
      if (p.length !== 3) return;
      document.querySelectorAll('.psp-dag-row').forEach(function (row) {
        var span = row.querySelector('.psp-dag-check span');
        if (!span) return;
        if (!span.dataset.naam) span.dataset.naam = span.textContent.trim();
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10) + (OFFSET[row.dataset.dag] || 0));
        span.textContent = span.dataset.naam + ' ' + d.getDate() + ' ' + MAANDEN[d.getMonth()];
      });
    }

    update();
    weekSelect.addEventListener('change', update);
  }

  /* ── AJAX submit ── */
  function initFormSubmit() {
    var form = document.getElementById('psp-form');
    if (!form) return;

    var errorDiv  = document.getElementById('psp-error');
    var succesDiv = document.getElementById('psp-succes');
    var submitBtn = document.getElementById('psp-submit');

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      // Verberg eerdere berichten
      errorDiv.style.display  = 'none';
      succesDiv.style.display = 'none';

      submitBtn.disabled = true;
      submitBtn.querySelector('.psp-btn-text').style.display    = 'none';
      submitBtn.querySelector('.psp-btn-loading').style.display = 'inline';

      var formData = new FormData(form);
      formData.append('action', 'psp_submit_beschikbaarheid');
      // psp_nonce is al in de FormData via het hidden field uit wp_nonce_field

      fetch(pspData.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            form.style.display      = 'none';
            if (data.data && data.data.message) {
              succesDiv.innerHTML = '<strong>&#10003; Verwerkt!</strong> ' + data.data.message;
            }
            succesDiv.style.display = 'block';
            window.scrollTo({ top: succesDiv.getBoundingClientRect().top + window.scrollY - 100, behavior: 'smooth' });
          } else {
            var msg = (data.data && data.data.message) ? data.data.message : 'Er is iets misgegaan.';
            errorDiv.textContent    = msg;
            errorDiv.style.display  = 'block';
            resetBtn();
          }
        })
        .catch(function () {
          errorDiv.textContent    = 'Verbindingsfout. Controleer je internet en probeer opnieuw.';
          errorDiv.style.display  = 'block';
          resetBtn();
        });

      function resetBtn() {
        submitBtn.disabled = false;
        submitBtn.querySelector('.psp-btn-text').style.display    = 'inline';
        submitBtn.querySelector('.psp-btn-loading').style.display = 'none';
      }
    });
  }
})();
