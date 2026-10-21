<?php
/**
 * Minimal shared header/footer for the demo pages.
 * Not a templating engine on purpose — this is a throwaway demo shell,
 * the real site's markup will come from the WordPress theme.
 */

function ircf_header(string $title, string $active = ''): void
{
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    ?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> — آشپزی ایرانی</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
  <div class="wrap header-inner">
    <a class="logo" href="<?= $base ?>/index.php">🍲 آشپزی ایرانی</a>
    <nav class="main-nav">
      <a href="<?= $base ?>/index.php" class="<?= $active === 'home' ? 'active' : '' ?>">پیشنهاد غذا</a>
      <a href="<?= $base ?>/recipes.php" class="<?= $active === 'recipes' ? 'active' : '' ?>">همهٔ دستورها</a>
    </nav>
  </div>
</header>
<main>
<?php
}

function ircf_footer(): void
{
    ?>
</main>
<footer class="site-footer">
  <div class="wrap">
    <p>نسخهٔ نمایشی — این دمو با PHP ساده اجرا می‌شود؛ نسخهٔ نهایی به‌صورت افزونهٔ وردپرس روی سایت شما نصب خواهد شد.</p>
  </div>
</footer>
</body>
</html>
<?php
}
