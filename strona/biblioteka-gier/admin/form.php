<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$d = ['tytul' => '', 'producent' => '', 'rok_wydania' => date('Y'), 'platforma' => '', 'id_gatunek' => 0, 'status' => 'Do zagrania', 'opis' => '', 'okladka' => null];

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
    $d['opis']        = mb_substr(trim($_POST['opis'] ?? ''), 0, 2000);
    $usunOkladke      = !empty($_POST['usun_okladke']);

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

    $nowa = null;
    if (!$errors && !$warn) {                                // okładka (walidacja i zapis pliku)
        $cerr = '';
        $nowa = store_cover($_FILES['okladka'] ?? [], $cerr);
        if ($cerr !== '') $errors[] = $cerr;
    }

    if (!$errors && !$warn) {
        $stara = $d['okladka'];
        $okladka = $nowa ?? ($usunOkladke ? null : $stara);
        $opis = $d['opis'] !== '' ? $d['opis'] : null;
        $pdo->beginTransaction();
        if ($id) {
            $pdo->prepare('UPDATE gra SET tytul=?, producent=?, rok_wydania=?, platforma=?, id_gatunek=?, opis=?, okladka=? WHERE id_gry=?')
                ->execute([$d['tytul'], $d['producent'], $d['rok_wydania'], $d['platforma'], $d['id_gatunek'], $opis, $okladka, $id]);
            flash('Dane gry zostały zaktualizowane.');
        } else {                                             // PU-08 dodawanie
            $pdo->prepare('INSERT INTO gra (tytul, producent, rok_wydania, platforma, id_gatunek, opis, okladka) VALUES (?,?,?,?,?,?,?)')
                ->execute([$d['tytul'], $d['producent'], $d['rok_wydania'], $d['platforma'], $d['id_gatunek'], $opis, $okladka]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO biblioteka_gra (id_biblioteka, id_gry) VALUES (?,?)')->execute([ID_BIBLIOTEKI, $id]);
            flash('Nowa gra została dodana.');
        }
        $pdo->commit();
        if ($stara && $okladka !== $stara) remove_cover($stara);   // stary plik już niepotrzebny
        header('Location: ../gra.php?id=' . $id); exit;
    }
}

$title = $id ? 'Edytuj grę' : 'Dodaj nową grę';
require __DIR__ . '/../includes/header.php';
?>
<h1><?= e($title) ?></h1>
<?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>
<?php if ($warn): ?><div class="flash warn"><?= e($warn) ?></div><?php endif; ?>

<form method="post" class="form narrow" enctype="multipart/form-data">
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
  <label>Opis (opcjonalnie)<textarea name="opis" rows="4" maxlength="2000"><?= e((string)$d['opis']) ?></textarea></label>
  <label>Okładka (JPG, PNG lub WebP, do 2 MB)
    <input type="file" name="okladka" accept="image/jpeg,image/png,image/webp"></label>
  <?php if (!empty($d['okladka'])): ?>
    <div class="cur-cover"><img src="<?= e(base() . 'uploads/' . rawurlencode($d['okladka'])) ?>" alt="Obecna okładka">
      <label class="check"><input type="checkbox" name="usun_okladke" value="1"> Usuń obecną okładkę</label></div>
  <?php endif; ?>
  <?php if ($warn): ?><p class="hint">Po ostrzeżeniu wybierz plik okładki jeszcze raz.</p><?php endif; ?>
  <?php if ($warn): ?><label class="check"><input type="checkbox" name="potwierdz" value="1"> Tak, dodaj mimo to</label><?php endif; ?>
  <div class="actions">
    <button class="btn">Zapisz</button>
    <a class="btn ghost" href="../index.php">Anuluj</a>
  </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
