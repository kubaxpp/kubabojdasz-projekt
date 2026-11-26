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
        $kom = trim($_POST['komentarz'] ?? '');
        $kom = $kom !== '' ? mb_substr($kom, 0, 500) : null;
        if ($o >= 1 && $o <= 10) {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO oceny (id_gry, id_uzytkownik, ocena, komentarz) VALUES (?,?,?,?)
                           ON DUPLICATE KEY UPDATE ocena = VALUES(ocena), komentarz = VALUES(komentarz)')
                ->execute([$id, $_SESSION['uid'], $o, $kom]);
            przelicz_ocene($pdo, $id);
            $pdo->commit();
            flash('Ocena zapisana, średnia zaktualizowana.');
        }
    } elseif ($akcja === 'usun_ocene') {
        $uid = (int)($_POST['uid'] ?? 0);
        if (is_admin() || $uid === (int)$_SESSION['uid']) {     // admin albo autor opinii
            $pdo->prepare('DELETE FROM oceny WHERE id_gry = ? AND id_uzytkownik = ?')->execute([$id, $uid]);
            przelicz_ocene($pdo, $id);
            flash('Opinia została usunięta.');
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

$st = $pdo->prepare('SELECT u.id AS uid, u.login, o.ocena, o.komentarz FROM oceny o JOIN uzytkownik u ON u.id = o.id_uzytkownik
                     WHERE o.id_gry = ? ORDER BY o.ocena DESC, u.login');
$st->execute([$id]);
$oceny = $st->fetchAll();
$ile = count($oceny);
$moja = false;
$mojKom = '';
foreach ($oceny as $o) if (is_logged() && $o['login'] === $_SESSION['login']) { $moja = $o['ocena']; $mojKom = (string)$o['komentarz']; }

$title = $gra['tytul'];
require __DIR__ . '/includes/header.php';
?>
<p class="back"><a href="index.php">Wróć na półkę</a></p>
<section class="detail case" style="--g:<?= genre_color($gra['gatunek']) ?>">
  <div class="spine"><?= e($gra['gatunek']) ?></div>
  <div class="body">
  <?= cover($gra) ?>
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

  <?php if (!empty($gra['opis'])): ?><p class="opis"><?= nl2br(e($gra['opis'])) ?></p><?php endif; ?>

  <?php if ($oceny): ?>
    <section class="reviews" aria-label="Opinie użytkowników">
      <h2>Opinie użytkowników</h2>
      <ul>
      <?php foreach ($oceny as $o): ?>
        <li>
          <div class="rv-head"><b class="rv-score"><?= (int)$o['ocena'] ?></b><span><?= e($o['login']) ?></span>
            <?php if (is_admin() || (is_logged() && (int)$o['uid'] === (int)$_SESSION['uid'])): ?>
              <form method="post" data-confirm="Usunąć tę opinię?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="akcja" value="usun_ocene"><input type="hidden" name="uid" value="<?= (int)$o['uid'] ?>">
                <button class="linkbtn">Usuń</button>
              </form>
            <?php endif; ?>
          </div>
          <?php if ($o['komentarz'] !== null && $o['komentarz'] !== ''): ?><p><?= nl2br(e($o['komentarz'])) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
      </ul>
    </section>
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
      <label class="kom">Opinia (opcjonalnie)
        <textarea name="komentarz" rows="3" maxlength="500" placeholder="Co sądzisz o tej grze?"><?= e($mojKom) ?></textarea></label>
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
