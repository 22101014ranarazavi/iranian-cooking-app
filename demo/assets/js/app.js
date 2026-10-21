(function () {
  const form = document.getElementById('picker-form');
  const results = document.getElementById('results');
  const aiResults = document.getElementById('ai-results');
  const countLabel = document.getElementById('selected-count');
  const clearBtn = document.getElementById('clear-btn');

  function selectedIds() {
    return Array.from(form.querySelectorAll('input[type=checkbox]:checked'))
      .map((el) => parseInt(el.value, 10));
  }

  function selectedNames() {
    return Array.from(form.querySelectorAll('input[type=checkbox]:checked'))
      .map((el) => form.querySelector(`label[for="${el.id}"]`)?.textContent.trim())
      .filter(Boolean);
  }

  function updateCount() {
    const n = selectedIds().length;
    countLabel.textContent = n === 0 ? 'چیزی انتخاب نشده' : `${n} ماده انتخاب شده`;
  }

  function cardHtml(r, kind) {
    const missing = kind === 'almost'
      ? `<div class="missing-list">کم داری: ${r.missing_names.map((n) => `<span class="tag">${n}</span>`).join('')}</div>`
      : '';
    return `
      <a class="recipe-card" href="recipe.php?id=${r.id}">
        <div class="card-thumb" style="background:${r.color}22">${r.emoji}</div>
        <div class="card-body">
          <h3>${r.title}</h3>
          <div class="card-meta">${r.category} · ${r.prep_minutes + r.cook_minutes} دقیقه · ${r.servings} نفر</div>
          ${missing}
        </div>
      </a>`;
  }

  function sectionHtml(title, badgeClass, badgeText, items, kind) {
    if (items.length === 0) return '';
    return `
      <div class="results-section">
        <h2>${title} <span class="badge ${badgeClass}">${badgeText}</span></h2>
        <div class="recipe-grid">
          ${items.map((r) => cardHtml(r, kind)).join('')}
        </div>
      </div>`;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  function aiCardHtml(s) {
    const ingredients = (s.ingredients || []).map((i) => `<span class="tag">${escapeHtml(i)}</span>`).join('');
    const steps = (s.steps || []).map((st) => `<li>${escapeHtml(st)}</li>`).join('');
    return `
      <div class="ai-card">
        <h3>${escapeHtml(s.title || 'پیشنهاد')}</h3>
        ${s.note ? `<p class="ai-note">${escapeHtml(s.note)}</p>` : ''}
        ${ingredients ? `<div class="ai-ingredients">${ingredients}</div>` : ''}
        ${steps ? `<ol class="ai-steps">${steps}</ol>` : ''}
      </div>`;
  }

  async function runCatalogSearch() {
    const ids = selectedIds();
    if (ids.length === 0) {
      results.innerHTML = '<div class="empty-state">چند ماده اولیه رو از بالا انتخاب کن تا غذاهای قابل‌تهیه رو نشونت بدیم.</div>';
      return;
    }

    results.innerHTML = '<div class="empty-state">در حال جست‌وجو…</div>';

    const res = await fetch('api/match.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ingredients: ids }),
    });
    const data = await res.json();

    if (data.ready.length === 0 && data.almost.length === 0) {
      results.innerHTML = '<div class="empty-state">با این مواد هنوز غذایی پیدا نشد. چند ماده دیگه هم انتخاب کن.</div>';
      return;
    }

    results.innerHTML =
      sectionHtml('آمادهٔ پخت', 'badge-ready', `${data.ready.length} غذا`, data.ready, 'ready') +
      sectionHtml('با کمی کمبود مواد', 'badge-almost', `${data.almost.length} غذا`, data.almost, 'almost');
  }

  async function runAiSuggest() {
    const names = selectedNames();
    if (names.length === 0) {
      aiResults.innerHTML = '';
      return;
    }

    aiResults.innerHTML = `
      <div class="results-section ai-section">
        <h2>✨ پیشنهاد هوش مصنوعی <span class="badge badge-ai">DeepSeek</span></h2>
        <div class="empty-state">در حال فکر کردن…</div>
      </div>`;

    try {
      const res = await fetch('api/ai-suggest.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ingredients: names }),
      });
      const data = await res.json();

      if (data.error) {
        aiResults.innerHTML = `
          <div class="results-section ai-section">
            <h2>✨ پیشنهاد هوش مصنوعی <span class="badge badge-ai">DeepSeek</span></h2>
            <div class="empty-state">${escapeHtml(data.error)}</div>
          </div>`;
        return;
      }

      if (!data.suggestions || data.suggestions.length === 0) {
        aiResults.innerHTML = '';
        return;
      }

      aiResults.innerHTML = `
        <div class="results-section ai-section">
          <h2>✨ پیشنهاد هوش مصنوعی <span class="badge badge-ai">DeepSeek</span></h2>
          <p class="ai-disclaimer">این پیشنهادها را هوش مصنوعی ساخته و ممکن است در فهرست رسمی دستورها نباشند — قبل از پخت با احتیاط بررسی کنید.</p>
          <div class="ai-grid">${data.suggestions.map(aiCardHtml).join('')}</div>
        </div>`;
    } catch (e) {
      aiResults.innerHTML = `
        <div class="results-section ai-section">
          <h2>✨ پیشنهاد هوش مصنوعی <span class="badge badge-ai">DeepSeek</span></h2>
          <div class="empty-state">اتصال به DeepSeek برقرار نشد.</div>
        </div>`;
    }
  }

  form.addEventListener('change', () => { updateCount(); runCatalogSearch(); });
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    runCatalogSearch();
    runAiSuggest();
  });
  clearBtn.addEventListener('click', () => {
    form.querySelectorAll('input[type=checkbox]:checked').forEach((el) => (el.checked = false));
    updateCount();
    results.innerHTML = '';
    aiResults.innerHTML = '';
  });

  updateCount();
})();
