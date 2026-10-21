(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var wrap = document.getElementById('ircf-steps-wrap');
    var addBtn = document.getElementById('ircf-add-step');
    if (!wrap || !addBtn) return;

    function renumber() {
      Array.prototype.forEach.call(wrap.querySelectorAll('.ircf-step-row'), function (row, i) {
        row.querySelector('.ircf-step-num').textContent = i + 1;
      });
    }

    addBtn.addEventListener('click', function () {
      var row = document.createElement('div');
      row.className = 'ircf-step-row';
      row.innerHTML =
        '<span class="ircf-step-num"></span>' +
        '<textarea name="ircf_steps[]" rows="2"></textarea>' +
        '<button type="button" class="button ircf-remove-step">حذف</button>';
      wrap.appendChild(row);
      renumber();
      row.querySelector('textarea').focus();
    });

    wrap.addEventListener('click', function (e) {
      if (e.target.classList.contains('ircf-remove-step')) {
        var rows = wrap.querySelectorAll('.ircf-step-row');
        if (rows.length > 1) {
          e.target.closest('.ircf-step-row').remove();
          renumber();
        } else {
          e.target.closest('.ircf-step-row').querySelector('textarea').value = '';
        }
      }
    });
  });
})();
