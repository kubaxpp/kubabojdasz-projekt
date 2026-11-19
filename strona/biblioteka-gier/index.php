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

require __DIR__ . '/includes/header.php';
?>
<h1>Lista gier</h1>

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
  <p class="count">Znaleziono: <?= count($gry) ?></p>
  <div class="grid">
  <?php foreach ($gry as $g): ?>
    <article class="card">
      <span class="badge <?= status_class($g['status']) ?>"><?= e($g['status']) ?></span>
      <h2><a href="gra.php?id=<?= $g['id_gry'] ?>"><?= e($g['tytul']) ?></a></h2>
      <p class="meta"><?= e($g['producent']) ?> · <?= (int)$g['rok_wydania'] ?></p>
      <p class="meta"><?= e($g['platforma']) ?> · <span class="tag"><?= e($g['gatunek']) ?></span></p>
      <p class="rating"><?= $g['ocena'] !== null ? '★ ' . number_format((float)$g['ocena'], 1, ',', '') . ' / 10' : 'Brak ocen' ?></p>
      <div class="actions">
        <a class="btn small" href="gra.php?id=<?= $g['id_gry'] ?>">Szczegóły</a>
        <?php if (is_admin()): ?>
          <a class="btn small ghost" href="admin/form.php?id=<?= $g['id_gry'] ?>">Edytuj</a>
          <form method="post" action="admin/delete.php" data-confirm="Czy na pewno chcesz usunąć tę grę?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $g['id_gry'] ?>">
            <button class="btn small danger">Usuń</button>
          </form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
