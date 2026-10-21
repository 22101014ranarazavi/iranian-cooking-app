<?php
require __DIR__ . '/includes/layout.php';
$ingredients = require __DIR__ . '/data/ingredients.php';

ircf_header('پیشنهاد غذا بر اساس مواد موجود', 'home');
?>
<div class="wrap">

  <div class="hero">
    <h1>چی تو یخچالت داری؟ ما غذاش رو پیدا می‌کنیم 🍲</h1>
    <p>مواد اولیه‌ای که الان در خانه داری رو انتخاب کن تا غذاهایی که می‌تونی همین حالا بپزی، یا با یکی‌دو ماده کم، پیشنهاد بدیم.</p>
  </div>

  <form id="picker-form" class="picker">
    <?php foreach ($ingredients as $group => $items): ?>
      <div class="picker-group">
        <h3><?= htmlspecialchars($group) ?></h3>
        <div class="chip-grid">
          <?php foreach ($items as $id => $name): ?>
            <div class="chip">
              <input type="checkbox" name="ingredients[]" value="<?= $id ?>" id="ing-<?= $id ?>">
              <label for="ing-<?= $id ?>"><?= htmlspecialchars($name) ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="picker-actions">
      <button type="submit" class="btn">🔍 پیشنهاد غذا بده</button>
      <button type="button" id="clear-btn" class="btn btn-ghost">پاک کردن انتخاب‌ها</button>
      <span class="selected-count" id="selected-count">چیزی انتخاب نشده</span>
    </div>
  </form>

  <div id="results"></div>
  <div id="ai-results"></div>

</div>

<script src="assets/js/app.js"></script>
<?php
ircf_footer();
