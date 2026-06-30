/* ProStudents Planning — Frontend */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initCheckboxes();
    initFormSubmit();
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
