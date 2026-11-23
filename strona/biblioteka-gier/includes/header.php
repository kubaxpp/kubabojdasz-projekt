<?php /** @var string $title */ $cur = basename($_SERVER['SCRIPT_NAME']); ?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Biblioteka gier') ?> – Biblioteka gier</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base() ?>assets/style.css">
</head>
<body>
<header class="top">
  <div class="wrap bar">
    <a class="logo" href="<?= base() ?>index.php">
      <svg width="34" height="30" viewBox="0 0 34 30" aria-hidden="true"><rect x="1" y="6" width="6" height="22" fill="#E5484D"/><rect x="9" y="2" width="6" height="26" fill="#7B5CFA"/><rect x="17" y="8" width="6" height="20" fill="#F97316"/><rect x="25" y="4" width="6" height="24" fill="#0E9F6E"/><path d="M0 29h34" stroke="#101828" stroke-width="2"/></svg>
      <span>Biblioteka gier</span></a>
    <button class="burger" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')">☰</button>
    <nav>
      <a href="<?= base() ?>index.php"<?= $cur==='index.php'||$cur==='gra.php' ? ' aria-current="page"' : '' ?>>Półka</a>
      <a href="<?= base() ?>stats.php"<?= $cur==='stats.php' ? ' aria-current="page"' : '' ?>>Statystyki</a>
      <?php if (is_logged()): ?>
        <?php if (is_admin()): ?><a href="<?= base() ?>admin/form.php">+ Dodaj grę</a><?php endif; ?>
        <span class="who"><?= e($_SESSION['login']) ?><?= is_admin() ? ' (admin)' : '' ?></span>
        <a href="<?= base() ?>logout.php">Wyloguj</a>
      <?php else: ?>
        <a href="<?= base() ?>login.php"<?= $cur==='login.php' ? ' aria-current="page"' : '' ?>>Zaloguj</a>
        <a href="<?= base() ?>register.php"<?= $cur==='register.php' ? ' aria-current="page"' : '' ?>>Załóż konto</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap">
<?php if (!empty($_SESSION['flash'])): [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="flash <?= e($t) ?>"><?= e($m) ?></div>
<?php endif; ?>
