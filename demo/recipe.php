<?php
require __DIR__ . '/includes/layout.php';
$recipes = require __DIR__ . '/data/recipes.php';
$ingredientsCatalog = require __DIR__ . '/data/ingredients.php';

$namesById = [];
foreach ($ingredientsCatalog as $group => $items) {
    foreach ($items as $id => $name) {
        $namesById[$id] = $name;
    }
}

$id = (int)($_GET['id'] ?? 0);
$recipe = null;
foreach ($recipes as $r) {
    if ($r['id'] === $id) { $recipe = $r; break; }
}

ircf_header($recipe ? $recipe['title'] : 'یافت نشد');

if (!$recipe) {
    echo '<div class="wrap"><div class="empty-state">این دستور پیدا نشد. <a href="recipes.php">برگشت به فهرست دستورها</a></div></div>';
    ircf_footer();
    return;
}
?>
<div class="wrap">

  <div class="recipe-hero">
    <div class="thumb-xl" style="background:<?= $recipe['color'] ?>22"><?= $recipe['emoji'] ?></div>
    <div>
      <h1><?= htmlspecialchars($recipe['title']) ?></h1>
      <div class="meta-row">
        <span>🍽️ <?= htmlspecialchars($recipe['category']) ?></span>
        <span>⏱ آماده‌سازی <?= $recipe['prep_minutes'] ?> دقیقه</span>
        <span>🔥 پخت <?= $recipe['cook_minutes'] ?> دقیقه</span>
        <span>👥 <?= $recipe['servings'] ?> نفر</span>
        <span>📊 <?= htmlspecialchars($recipe['difficulty']) ?></span>
      </div>
    </div>
  </div>

  <div class="recipe-columns">
    <div class="ingredients-box">
      <h2>مواد لازم</h2>
      <ul>
        <?php foreach ($recipe['ingredients'] as $ing): ?>
          <li>
            <span><?= htmlspecialchars($namesById[$ing['id']] ?? '؟') ?></span>
            <span class="qty"><?= htmlspecialchars($ing['qty']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="steps-box">
      <h2>مراحل پخت</h2>
      <ol>
        <?php foreach ($recipe['steps'] as $step): ?>
          <li><?= htmlspecialchars($step) ?></li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>

</div>
<?php
ircf_footer();
