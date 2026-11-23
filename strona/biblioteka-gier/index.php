<?php
require_once __DIR__ . '/includes/init.php';
$title = 'Lista gier';

$q      = trim($_GET['q'] ?? '');
$gat    = (int)($_GET['gatunek'] ?? 0);
$status = $_GET['status'] ?? '';
if (!in_array($status, STATUSY, true)) $status = '';

$sql = 'SELECT g.*, ga.nazwa AS gatunek
        FROM gra g
        JOIN gatunek ga ON ga.id_gatunek = g.id_gatunek
        JOIN biblioteka_gra bg ON bg.id_gry = g.id_gry AND bg.id_biblioteka = :bib
        WHERE 1=1';
$p = [':bib' => ID_BIBLIOTEKI];
if ($q !== '')      { $sql .= ' AND g.tytul LIKE :q'; $p[':q'] = '%' . addcslashes($q, '%_\\') . '%'; }
if ($gat > 0)       { $sql .= ' AND g.id_gatunek = :gat'; $p[':gat'] = $gat; }
if ($status !== '') { $sql .= ' AND g.status = :st'; $p[':st'] = $status; }
$sql .= ' ORDER BY g.tytul';

$st = $pdo->prepare($sql);
$st->execute($p);
$gry = $st->fetchAll();

$podium = $pdo->prepare('SELECT g.id_gry, g.tytul, g.ocena, ga.nazwa AS gatunek,
          (SELECT COUNT(*) FROM oceny o WHERE o.id_gry = g.id_gry) AS ile
        FROM gra g JOIN gatunek ga ON ga.id_gatunek = g.id_gatunek
        JOIN biblioteka_gra bg ON bg.id_gry = g.id_gry AND bg.id_biblioteka = ?
        WHERE g.ocena IS NOT NULL
        ORDER BY g.ocena DESC, ile DESC, g.tytul LIMIT 3');
$podium->execute([ID_BIBLIOTEKI]);
$podium = $podium->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="hero">
  <h1>Półka z grami</h1>
  <p>Szukaj po tytule, filtruj po gatunku i statusie, oceniaj to, w co grałeś.</p>
</div>

<?php if ($podium):
  // kolejność wizualna: 2. miejsce, 1. miejsce, 3. miejsce
  $slots = [1 => $podium[1] ?? null, 0 => $podium[0], 2 => $podium[2] ?? null]; ?>
<section class="podium" aria-label="Trzy najwyżej oceniane gry">
  <h2>Podium</h2>
  <div class="steps">
  <?php foreach ($slots as $i => $g): if (!$g): ?><div class="step empty-step"></div><?php continue; endif; ?>
    <a class="step place<?= $i + 1 ?>" href="gra.php?id=<?= $g['id_gry'] ?>" style="--g:<?= genre_color($g['gatunek']) ?>">
      <span class="pt"><?= e($g['tytul']) ?></span>
      <span class="ps"><b><?= number_format((float)$g['ocena'], 1, ',', '') ?></b><small>/10, <?= ocen((int)$g['ile']) ?></small></span>
      <span class="block"><?= $i + 1 ?></span>
    </a>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<form class="filters" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Szukaj po nazwie…">
  <select name="gatunek">
    <option value="0">Wszystkie gatunki</option>
    <?php foreach (gatunki($pdo) as $g): ?>
      <option value="<?= $g['id_gatunek'] ?>" <?= $gat == $g['id_gatunek'] ? 'selected' : '' ?>><?= e($g['nazwa']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">Każdy status</option>
    <?php foreach (STATUSY as $s): ?>
      <option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn">Filtruj</button>
  <?php if ($q !== '' || $gat || $status !== ''): ?><a class="btn ghost" href="index.php">Wyczyść</a><?php endif; ?>
</form>

<?php if (!$gry): ?>
  <p class="empty"><?= ($q !== '') ? 'Nie znaleziono gry.' : 'Brak gier w wybranych kryteriach.' ?></p>
<?php else: ?>
  <p class="count">Gier na półce: <?= count($gry) ?></p>
  <div class="grid">
  <?php foreach ($gry as $g): ?>
    <article class="case" style="--g:<?= genre_color($g['gatunek']) ?>">
      <div class="spine"><?= e($g['gatunek']) ?></div>
      <div class="body">
        <h2><a href="gra.php?id=<?= $g['id_gry'] ?>"><?= e($g['tytul']) ?></a></h2>
        <p class="meta"><?= e($g['producent']) ?>, <?= (int)$g['rok_wydania'] ?></p>
        <p class="meta"><?= e($g['platforma']) ?></p>
        <a class="more" href="gra.php?id=<?= $g['id_gry'] ?>">Szczegóły i ocena</a>
        <div class="foot-row">
          <span class="badge <?= status_class($g['status']) ?>"><?= e($g['status']) ?></span>
          <span class="score"><?php if ($g['ocena'] !== null): ?><b><?= number_format((float)$g['ocena'], 1, ',', '') ?></b><small>/10</small><?php else: ?><small>brak ocen</small><?php endif; ?></span>
        </div>
        <?php if (is_admin()): ?>
        <div class="actions">
          <a class="btn small ghost" href="admin/form.php?id=<?= $g['id_gry'] ?>">Edytuj</a>
          <form method="post" action="admin/delete.php" data-confirm="Czy na pewno chcesz usunąć tę grę?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $g['id_gry'] ?>">
            <button class="btn small danger">Usuń</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
