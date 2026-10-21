(function () {
  var form = document.getElementById('ircf-picker-form');
  if (!form) return;

  var results = document.getElementById('ircf-results');
  var aiResults = document.getElementById('ircf-ai-results');
  var countLabel = document.getElementById('ircf-selected-count');
  var clearBtn = document.getElementById('ircf-clear-btn');

  function selectedIds() {
    return Array.prototype.slice
      .call(form.querySelectorAll('input[type=checkbox]:checked'))
      .map(function (el) { return parseInt(el.value, 10); });
  }

  function selectedNames() {
    return Array.prototype.slice
      .call(form.querySelectorAll('input[type=checkbox]:checked'))
      .map(function (el) {
        var label = form.querySelector('label[for="' + el.id + '"]');
        return label ? label.textContent.trim() : null;
      })
      .filter(Boolean);
  }

  function updateCount() {
    var n = selectedIds().length;
    countLabel.textContent = n === 0 ? 'چیزی انتخاب نشده' : n + ' ماده انتخاب شده';
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function cardHtml(r, kind) {
    var thumb = r.thumbnail
      ? '<img src="' + r.thumbnail + '" alt="">'
      : '<span class="ircf-thumb-fallback">🍽️</span>';
    var missing = kind === 'almost'
      ? '<div class="ircf-missing-list">کم داری: ' +
        r.missing_names.map(function (n) { return '<span class="ircf-tag">' + escapeHtml(n) + '</span>'; }).join('') +
        '</div>'
      : '';
    return (
      '<a class="ircf-recipe-card" href="' + r.permalink + '">' +
        '<div class="ircf-card-thumb">' + thumb + '</div>' +
        '<div class="ircf-card-body">' +
          '<h3>' + escapeHtml(r.title) + '</h3>' +
          '<div class="ircf-card-meta">' + (r.prep_minutes + r.cook_minutes) + ' دقیقه' + (r.servings ? ' · ' + r.servings + ' نفر' : '') + '</div>' +
          missing +
        '</div>' +
      '</a>'
    );
  }

  function sectionHtml(title, badgeClass, badgeText, items, kind) {
    if (items.length === 0) return '';
    return (
      '<div class="ircf-results-section">' +
        '<h2>' + title + ' <span class="ircf-badge ' + badgeClass + '">' + badgeText + '</span></h2>' +
        '<div class="ircf-recipe-grid">' + items.map(function (r) { return cardHtml(r, kind); }).join('') + '</div>' +
      '</div>'
    );
  }

  function aiCardHtml(s) {
    var ingredients = (s.ingredients || []).map(function (i) { return '<span class="ircf-tag">' + escapeHtml(i) + '</span>'; }).join('');
    var steps = (s.steps || []).map(function (st) { return '<li>' + escapeHtml(st) + '</li>'; }).join('');
    return (
      '<div class="ircf-ai-card">' +
        '<h3>' + escapeHtml(s.title || 'پیشنهاد') + '</h3>' +
        (s.note ? '<p class="ircf-ai-note">' + escapeHtml(s.note) + '</p>' : '') +
        (ingredients ? '<div class="ircf-ai-ingredients">' + ingredients + '</div>' : '') +
        (steps ? '<ol class="ircf-ai-steps">' + steps + '</ol>' : '') +
      '</div>'
    );
  }

  function runCatalogSearch() {
    var ids = selectedIds();
    if (ids.length === 0) {
      results.innerHTML = '<div class="ircf-empty">چند ماده اولیه رو از بالا انتخاب کن تا غذاهای قابل‌تهیه رو نشونت بدیم.</div>';
      return;
    }

    results.innerHTML = '<div class="ircf-empty">در حال جست‌وجو…</div>';

    fetch(IRCF.restUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ingredients: ids }),
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.ready.length === 0 && data.almost.length === 0) {
          results.innerHTML = '<div class="ircf-empty">با این مواد هنوز غذایی پیدا نشد. چند ماده دیگه هم انتخاب کن.</div>';
          return;
        }
        results.innerHTML =
          sectionHtml('آمادهٔ پخت', 'ircf-badge-ready', data.ready.length + ' غذا', data.ready, 'ready') +
          sectionHtml('با کمی کمبود مواد', 'ircf-badge-almost', data.almost.length + ' غذا', data.almost, 'almost');
      });
  }

  function runAiSuggest() {
    var names = selectedNames();
    if (names.length === 0) {
      aiResults.innerHTML = '';
      return;
    }

    var header = '<h2>✨ پیشنهاد هوش مصنوعی <span class="ircf-badge ircf-badge-ai">DeepSeek</span></h2>';
    aiResults.innerHTML = '<div class="ircf-results-section ircf-ai-section">' + header + '<div class="ircf-empty">در حال فکر کردن…</div></div>';

    fetch(IRCF.aiRestUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ingredients: names }),
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.error) {
          aiResults.innerHTML = '<div class="ircf-results-section ircf-ai-section">' + header + '<div class="ircf-empty">' + escapeHtml(data.error) + '</div></div>';
          return;
        }
        if (!data.suggestions || data.suggestions.length === 0) {
          aiResults.innerHTML = '';
          return;
        }
        aiResults.innerHTML =
          '<div class="ircf-results-section ircf-ai-section">' + header +
          '<p class="ircf-ai-disclaimer">این پیشنهادها را هوش مصنوعی ساخته و ممکن است در فهرست رسمی دستورها نباشند — قبل از پخت با احتیاط بررسی کنید.</p>' +
          '<div class="ircf-ai-grid">' + data.suggestions.map(aiCardHtml).join('') + '</div></div>';
      })
      .catch(function () {
        aiResults.innerHTML = '<div class="ircf-results-section ircf-ai-section">' + header + '<div class="ircf-empty">اتصال به DeepSeek برقرار نشد.</div></div>';
      });
  }

  form.addEventListener('change', function () { updateCount(); runCatalogSearch(); });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    runCatalogSearch();
    runAiSuggest();
  });
  clearBtn.addEventListener('click', function () {
    Array.prototype.forEach.call(form.querySelectorAll('input[type=checkbox]:checked'), function (el) { el.checked = false; });
    updateCount();
    results.innerHTML = '';
    aiResults.innerHTML = '';
  });

  updateCount();
})();
