<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$d = ['tytul' => '', 'producent' => '', 'rok_wydania' => date('Y'), 'platforma' => '', 'id_gatunek' => 0, 'status' => 'Do zagrania'];

if ($id) {                                                   // PU-09 edycja
    $st = $pdo->prepare('SELECT * FROM gra WHERE id_gry = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { flash('Nie znaleziono gry.', 'err'); header('Location: ../index.php'); exit; }
    $d = $row;
}

$errors = []; $warn = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $d['tytul']       = trim($_POST['tytul'] ?? '');
    $d['producent']   = trim($_POST['producent'] ?? '');
    $d['rok_wydania'] = (int)($_POST['rok_wydania'] ?? 0);
    $d['platforma']   = trim($_POST['platforma'] ?? '');
    $d['id_gatunek']  = (int)($_POST['id_gatunek'] ?? 0);

    if ($d['tytul'] === '')     $errors[] = 'Podaj tytuł.';
    if ($d['producent'] === '') $errors[] = 'Podaj producenta.';
    if ($d['platforma'] === '') $errors[] = 'Podaj platformę.';
    if ($d['rok_wydania'] < 1970 || $d['rok_wydania'] > (int)date('Y') + 2) $errors[] = 'Podaj poprawny rok wydania.';
    $g = $pdo->prepare('SELECT COUNT(*) FROM gatunek WHERE id_gatunek = ?');
    $g->execute([$d['id_gatunek']]);
    if (!$g->fetchColumn()) $errors[] = 'Wybierz gatunek.';

    if (!$errors) {                                          // ostrzeżenie o duplikacie
        $du = $pdo->prepare('SELECT COUNT(*) FROM gra WHERE tytul = ? AND id_gry <> ?');
        $du->execute([$d['tytul'], $id]);
        if ($du->fetchColumn() && empty($_POST['potwierdz'])) {
            $warn = 'Gra o takim tytule już istnieje. Zaznacz potwierdzenie i zapisz ponownie, aby ją mimo to dodać.';
        }
    }

    if (!$errors && !$warn) {
        $pdo->beginTransaction();
        if ($id) {
            $pdo->prepare('UPDATE gra SET tytul=?, producent=?, rok_wydania=?, platforma=?, id_gatunek=? WHERE id_gry=?')
                ->execute([$d['tytul'], $d['producent'], $d['rok_wydania'], $d['platforma'], $d['id_gatunek'], $id]);
            flash('Dane gry zostały zaktualizowane.');
        } else {                                             // PU-08 dodawanie
            $pdo->prepare('INSERT INTO gra (tytul, producent, rok_wydania, platforma, id_gatunek) VALUES (?,?,?,?,?)')
                ->execute([$d['tytul'], $d['producent'], $d['rok_wydania'], $d['platforma'], $d['id_gatunek']]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO biblioteka_gra (id_biblioteka, id_gry) VALUES (?,?)')->execute([ID_BIBLIOTEKI, $id]);
            flash('Nowa gra została dodana.');
        }
        $pdo->commit();
        header('Location: ../gra.php?id=' . $id); exit;
    }
}

$title = $id ? 'Edytuj grę' : 'Dodaj nową grę';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= e($title) ?></h1>
<?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>
<?php if ($warn): ?><div class="flash warn"><?= e($warn) ?></div><?php endif; ?>

<form method="post" class="form narrow">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
  <label>Tytuł<input name="tytul" required maxlength="150" value="<?= e($d['tytul']) ?>"></label>
  <label>Producent<input name="producent" required maxlength="100" value="<?= e($d['producent']) ?>"></label>
  <label>Rok wydania<input type="number" name="rok_wydania" required min="1970" max="<?= date('Y') + 2 ?>" value="<?= (int)$d['rok_wydania'] ?>"></label>
  <label>Platforma<input name="platforma" required maxlength="50" list="platformy" value="<?= e($d['platforma']) ?>">
    <datalist id="platformy"><option>PC</option><option>PlayStation</option><option>Xbox</option><option>Nintendo Switch</option></datalist></label>
  <label>Gatunek
    <select name="id_gatunek" required>
      <option value="">— wybierz —</option>
      <?php foreach (gatunki($pdo) as $g): ?>
        <option value="<?= $g['id_gatunek'] ?>" <?= (int)$d['id_gatunek'] === (int)$g['id_gatunek'] ? 'selected' : '' ?>><?= e($g['nazwa']) ?></option>
      <?php endforeach; ?>
    </select></label>
  <?php if ($warn): ?><label class="check"><input type="checkbox" name="potwierdz" value="1"> Tak, dodaj mimo to</label><?php endif; ?>
  <div class="actions">
    <button class="btn">Zapisz</button>
    <a class="btn ghost" href="../index.php">Anuluj</a>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
