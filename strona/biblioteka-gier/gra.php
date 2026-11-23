<?php
require_once __DIR__ . '/includes/init.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$load = function () use ($pdo, $id) {
    $st = $pdo->prepare('SELECT g.*, ga.nazwa AS gatunek FROM gra g
                         JOIN gatunek ga ON ga.id_gatunek = g.id_gatunek WHERE g.id_gry = ?');
    $st->execute([$id]);
    return $st->fetch();
};
$gra = $load();
if (!$gra) { http_response_code(404); $title = 'Nie znaleziono'; require __DIR__ . '/includes/header.php'; echo '<p class="empty">Nie ma takiej gry.</p>'; require __DIR__ . '/includes/footer.php'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require_login();
    $akcja = $_POST['akcja'] ?? '';

    if ($akcja === 'ocena') {                                   // PU-05
        $o = (int)($_POST['ocena'] ?? 0);
        if ($o >= 1 && $o <= 10) {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO oceny (id_gry, id_uzytkownik, ocena) VALUES (?,?,?)
                           ON DUPLICATE KEY UPDATE ocena = VALUES(ocena)')
                ->execute([$id, $_SESSION['uid'], $o]);
            $pdo->prepare('UPDATE gra SET ocena = (SELECT ROUND(AVG(ocena),1) FROM oceny WHERE id_gry = ?) WHERE id_gry = ?')
                ->execute([$id, $id]);
            $pdo->commit();
            flash('Ocena zapisana, średnia zaktualizowana.');
        }
    } elseif ($akcja === 'status') {                            // PU-06
        $s = $_POST['status'] ?? '';
        if (in_array($s, STATUSY, true)) {
            $pdo->prepare('UPDATE gra SET status = ? WHERE id_gry = ?')->execute([$s, $id]);
            flash('Status zmieniony na: ' . $s);
        }
    }
    header('Location: gra.php?id=' . $id);
    exit;
}

$st = $pdo->prepare('SELECT u.login, o.ocena FROM oceny o JOIN uzytkownik u ON u.id = o.id_uzytkownik
                     WHERE o.id_gry = ? ORDER BY o.ocena DESC, u.login');
$st->execute([$id]);
$oceny = $st->fetchAll();
$ile = count($oceny);
$moja = false;
foreach ($oceny as $o) if (is_logged() && $o['login'] === $_SESSION['login']) $moja = $o['ocena'];

$title = $gra['tytul'];
require __DIR__ . '/includes/header.php';
?>
<p class="back"><a href="index.php">Wróć na półkę</a></p>
<section class="detail case" style="--g:<?= genre_color($gra['gatunek']) ?>">
  <div class="spine"><?= e($gra['gatunek']) ?></div>
  <div class="body">
  <span class="badge <?= status_class($gra['status']) ?>"><?= e($gra['status']) ?></span>
  <h1><?= e($gra['tytul']) ?></h1>
  <dl>
    <dt>Producent</dt><dd><?= e($gra['producent']) ?></dd>
    <dt>Rok wydania</dt><dd><?= (int)$gra['rok_wydania'] ?></dd>
    <dt>Platforma</dt><dd><?= e($gra['platforma']) ?></dd>
    <dt>Gatunek</dt><dd><?= e($gra['gatunek']) ?></dd>
    <dt>Średnia ocen</dt>
    <dd><?= $gra['ocena'] !== null ? '★ ' . number_format((float)$gra['ocena'], 1, ',', '') . ' / 10 (' . ocen($ile) . ')' : 'Brak ocen' ?></dd>
  </dl>

  <?php if ($oceny): ?>
    <ul class="votes" aria-label="Oceny użytkowników">
      <?php foreach ($oceny as $o): ?><li><span><?= e($o['login']) ?></span><b><?= (int)$o['ocena'] ?></b></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (is_logged()): ?>
  <div class="panels">
    <form method="post" class="panel">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="akcja" value="ocena">
      <h3>Twoja ocena <?= $moja ? '(teraz: ' . (int)$moja . ')' : '' ?></h3>
      <div class="scale">
        <?php for ($i = 1; $i <= 10; $i++): ?>
          <label><input type="radio" name="ocena" value="<?= $i ?>" <?= (int)$moja === $i ? 'checked' : '' ?> required><span><?= $i ?></span></label>
        <?php endfor; ?>
      </div>
      <button class="btn"><?= $moja ? 'Zmień ocenę' : 'Zapisz ocenę' ?></button>
    </form>

    <form method="post" class="panel">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="akcja" value="status">
      <h3>Zmień status</h3>
      <select name="status">
        <?php foreach (STATUSY as $s): ?><option <?= $gra['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
      </select>
      <button class="btn">Zapisz status</button>
    </form>
  </div>
  <?php else: ?>
  <p class="login-hint"><a href="login.php">Zaloguj się</a> lub <a href="register.php">załóż konto</a>, żeby ocenić grę i zmienić jej status.</p>
  <?php endif; ?>

  <?php if (is_admin()): ?>
  <div class="actions">
    <a class="btn ghost" href="admin/form.php?id=<?= $id ?>">Edytuj grę</a>
    <form method="post" action="admin/delete.php" data-confirm="Czy na pewno chcesz usunąć tę grę?">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <button class="btn danger">Usuń grę</button>
    </form>
  </div>
  <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
