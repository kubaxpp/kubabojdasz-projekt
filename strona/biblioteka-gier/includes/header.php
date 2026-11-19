<?php /** @var string $title */ ?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Biblioteka gier') ?> – Biblioteka gier</title>
  <link rel="stylesheet" href="<?= base() ?>assets/style.css">
</head>
<body>
<header class="top">
  <div class="wrap bar">
    <a class="logo" href="<?= base() ?>index.php">🎮 Biblioteka gier</a>
    <button class="burger" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')">☰</button>
    <nav>
      <a href="<?= base() ?>index.php">Lista gier</a>
      <a href="<?= base() ?>stats.php">Statystyki</a>
      <?php if (is_admin()): ?>
        <a href="<?= base() ?>admin/form.php">+ Dodaj grę</a>
        <a href="<?= base() ?>admin/logout.php">Wyloguj</a>
      <?php else: ?>
        <a href="<?= base() ?>admin/login.php">Panel admina</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap">
<?php if (!empty($_SESSION['flash'])): [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="flash <?= e($t) ?>"><?= e($m) ?></div>
<?php endif; ?>
