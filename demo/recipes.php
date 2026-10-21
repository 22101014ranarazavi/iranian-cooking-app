<?php
require __DIR__ . '/includes/layout.php';
$recipes = require __DIR__ . '/data/recipes.php';

$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');
$categories = array_values(array_unique(array_column($recipes, 'category')));

$filtered = array_filter($recipes, function ($r) use ($q, $cat) {
    if ($cat !== '' && $r['category'] !== $cat) return false;
    if ($q !== '' && !str_contains($r['title'], $q)) return false;
    return true;
});

ircf_header('همهٔ دستورها', 'recipes');
?>
<div class="wrap">
  <div class="hero" style="padding-top:28px">
    <h1>همهٔ دستورهای غذا</h1>
    <p>دنبال یک غذای خاصی؟ از اینجا بگرد یا بر اساس دسته فیلتر کن.</p>
  </div>

  <form class="filters" method="get">
    <input type="text" name="q" placeholder="جست‌وجوی نام غذا…" value="<?= htmlspecialchars($q) ?>">
    <select name="cat" onchange="this.form.submit()">
      <option value="">همهٔ دسته‌ها</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">جست‌وجو</button>
  </form>

  <?php if (empty($filtered)): ?>
    <div class="empty-state">چیزی پیدا نشد.</div>
  <?php else: ?>
    <div class="recipe-grid" style="margin-bottom:40px">
      <?php foreach ($filtered as $r): ?>
        <a class="recipe-card" href="recipe.php?id=<?= $r['id'] ?>">
          <div class="card-thumb" style="background:<?= $r['color'] ?>22"><?= $r['emoji'] ?></div>
          <div class="card-body">
            <h3><?= htmlspecialchars($r['title']) ?></h3>
            <div class="card-meta"><?= htmlspecialchars($r['category']) ?> · <?= $r['prep_minutes'] + $r['cook_minutes'] ?> دقیقه · <?= $r['servings'] ?> نفر</div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php
ircf_footer();
