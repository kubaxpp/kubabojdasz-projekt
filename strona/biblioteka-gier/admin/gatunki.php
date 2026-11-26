<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$title = 'Gatunki';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $akcja = $_POST['akcja'] ?? '';
    $gid = (int)($_POST['id'] ?? 0);
    $nazwa = trim($_POST['nazwa'] ?? '');

    if ($akcja === 'dodaj' || $akcja === 'zmien') {
        if (mb_strlen($nazwa) < 2 || mb_strlen($nazwa) > 40) {
            flash('Nazwa gatunku musi mieć od 2 do 40 znaków.', 'err');
        } else {
            $du = $pdo->prepare('SELECT COUNT(*) FROM gatunek WHERE nazwa = ? AND id_gatunek <> ?');
            $du->execute([$nazwa, $gid]);
            if ($du->fetchColumn()) {
                flash('Gatunek „' . $nazwa . '” już istnieje.', 'err');
            } elseif ($akcja === 'dodaj') {
                $pdo->prepare('INSERT INTO gatunek (nazwa) VALUES (?)')->execute([$nazwa]);
                flash('Dodano gatunek: ' . $nazwa);
            } else {
                $pdo->prepare('UPDATE gatunek SET nazwa = ? WHERE id_gatunek = ?')->execute([$nazwa, $gid]);
                flash('Zmieniono nazwę gatunku.');
            }
        }
    } elseif ($akcja === 'usun') {
        $c = $pdo->prepare('SELECT COUNT(*) FROM gra WHERE id_gatunek = ?');
        $c->execute([$gid]);
        if ($c->fetchColumn()) {
            flash('Nie można usunąć gatunku, do którego należą gry. Najpierw zmień gatunek tych gier.', 'err');
        } else {
            $pdo->prepare('DELETE FROM gatunek WHERE id_gatunek = ?')->execute([$gid]);
            flash('Gatunek został usunięty.');
        }
    }
    header('Location: gatunki.php');
    exit;
}

$lista = $pdo->query('SELECT ga.*, COUNT(g.id_gry) AS ile FROM gatunek ga
                      LEFT JOIN gra g ON g.id_gatunek = ga.id_gatunek
                      GROUP BY ga.id_gatunek, ga.nazwa ORDER BY ga.nazwa')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>Gatunki</h1>

<form method="post" class="filters">
  <?= csrf_field() ?><input type="hidden" name="akcja" value="dodaj">
  <input name="nazwa" required minlength="2" maxlength="40" placeholder="Nowy gatunek, np. Przygodowe" aria-label="Nazwa nowego gatunku">
  <button class="btn">Dodaj gatunek</button>
</form>

<ul class="genres">
<?php foreach ($lista as $g): ?>
  <li style="--g:<?= genre_color($g['nazwa']) ?>">
    <form method="post" class="g-edit">
      <?= csrf_field() ?><input type="hidden" name="akcja" value="zmien"><input type="hidden" name="id" value="<?= $g['id_gatunek'] ?>">
      <input name="nazwa" value="<?= e($g['nazwa']) ?>" required minlength="2" maxlength="40" aria-label="Nazwa gatunku">
      <button class="btn small ghost">Zapisz</button>
    </form>
    <span class="g-count"><?= (int)$g['ile'] ?> <?= (int)$g['ile'] === 1 ? 'gra' : 'gier' ?></span>
    <form method="post" data-confirm="Usunąć gatunek „<?= e($g['nazwa']) ?>”?">
      <?= csrf_field() ?><input type="hidden" name="akcja" value="usun"><input type="hidden" name="id" value="<?= $g['id_gatunek'] ?>">
      <button class="btn small danger">Usuń</button>
    </form>
  </li>
<?php endforeach; ?>
</ul>
<?php require __DIR__ . '/../includes/footer.php'; ?>
