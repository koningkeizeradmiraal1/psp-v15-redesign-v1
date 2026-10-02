/* ProStudents Planning — Frontend */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initCheckboxes();
    initFormSubmit();
    initLockedDays();
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
      var data = (window.pspLockedData && window.pspLockedData[weekSelect.value]) || {};
      document.querySelectorAll('.psp-dag-row').forEach(function (row) {
        var dag      = row.dataset.dag;
        var checkbox = row.querySelector('.psp-dag-checkbox');
        var vanInput = row.querySelector('input[name$="_van"]');
        var totInput = row.querySelector('input[name$="_tot"]');
        var lockIcon = row.querySelector('.psp-dag-lock');
        var locked   = Object.prototype.hasOwnProperty.call(data, dag);

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

    applyLockedDays();
    weekSelect.addEventListener('change', applyLockedDays);
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
