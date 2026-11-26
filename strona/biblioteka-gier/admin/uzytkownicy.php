<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
$title = 'Użytkownicy';
$moje = (int)$_SESSION['uid'];

function liczba_adminow(PDO $pdo, int $pomin = 0): int {
    $st = $pdo->prepare("SELECT COUNT(*) FROM uzytkownik WHERE rola = 'admin' AND id <> ?");
    $st->execute([$pomin]);
    return (int)$st->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $akcja = $_POST['akcja'] ?? '';
    $uid = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT * FROM uzytkownik WHERE id = ?');
    $st->execute([$uid]);
    $u = $st->fetch();

    if (!$u) {
        flash('Nie znaleziono użytkownika.', 'err');
    } elseif ($akcja === 'zapisz') {
        $login = trim($_POST['login'] ?? '');
        $rola = ($_POST['rola'] ?? '') === 'admin' ? 'admin' : 'user';
        $du = $pdo->prepare('SELECT COUNT(*) FROM uzytkownik WHERE login = ? AND id <> ?');
        $du->execute([$login, $uid]);
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $login)) {
            flash('Login: 3–30 znaków, litery bez polskich znaków, cyfry i podkreślnik.', 'err');
        } elseif ($du->fetchColumn()) {
            flash('Login „' . $login . '” jest już zajęty.', 'err');
        } elseif ($u['rola'] === 'admin' && $rola !== 'admin' && liczba_adminow($pdo, $uid) === 0) {
            flash('Musi zostać co najmniej jeden administrator.', 'err');
        } else {
            $pdo->prepare('UPDATE uzytkownik SET login = ?, rola = ? WHERE id = ?')->execute([$login, $rola, $uid]);
            if ($uid === $moje) { $_SESSION['login'] = $login; $_SESSION['rola'] = $rola; }
            flash('Zapisano zmiany użytkownika ' . $login . '.');
        }
    } elseif ($akcja === 'haslo') {
        $h = (string)($_POST['haslo'] ?? '');
        if (strlen($h) < 6) {
            flash('Nowe hasło musi mieć co najmniej 6 znaków.', 'err');
        } else {
            $pdo->prepare('UPDATE uzytkownik SET haslo_hash = ? WHERE id = ?')->execute([password_hash($h, PASSWORD_DEFAULT), $uid]);
            flash('Hasło użytkownika ' . $u['login'] . ' zostało zmienione.');
        }
    } elseif ($akcja === 'usun') {
        if ($uid === $moje) {
            flash('Nie możesz usunąć własnego konta.', 'err');
        } elseif ($u['rola'] === 'admin' && liczba_adminow($pdo, $uid) === 0) {
            flash('Musi zostać co najmniej jeden administrator.', 'err');
        } else {
            $gry = $pdo->prepare('SELECT id_gry FROM oceny WHERE id_uzytkownik = ?');
            $gry->execute([$uid]);
            $gry = $gry->fetchAll(PDO::FETCH_COLUMN);
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM oceny WHERE id_uzytkownik = ?')->execute([$uid]);
            $pdo->prepare('DELETE FROM uzytkownik WHERE id = ?')->execute([$uid]);
            foreach ($gry as $idg) przelicz_ocene($pdo, (int)$idg);
            $pdo->commit();
            flash('Usunięto użytkownika ' . $u['login'] . ' razem z jego ocenami.');
        }
    }
    header('Location: uzytkownicy.php');
    exit;
}

$lista = $pdo->query('SELECT u.id, u.login, u.rola, u.utworzono, COUNT(o.id_gry) AS ile
                      FROM uzytkownik u LEFT JOIN oceny o ON o.id_uzytkownik = u.id
                      GROUP BY u.id, u.login, u.rola, u.utworzono ORDER BY u.login')->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<h1>Użytkownicy</h1>
<p class="hint-row">Konta zapisane w bazie: <?= count($lista) ?>.
  <a class="btn small ghost" href="eksport.php">Pobierz kopię danych (JSON)</a></p>

<ul class="users">
<?php foreach ($lista as $u): $swoj = (int)$u['id'] === $moje; ?>
  <li>
    <form method="post" class="u-main">
      <?= csrf_field() ?><input type="hidden" name="akcja" value="zapisz"><input type="hidden" name="id" value="<?= $u['id'] ?>">
      <label>Login<input name="login" value="<?= e($u['login']) ?>" required maxlength="30"></label>
      <label>Rola
        <select name="rola">
          <option value="user" <?= $u['rola'] === 'user' ? 'selected' : '' ?>>Użytkownik</option>
          <option value="admin" <?= $u['rola'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
        </select></label>
      <button class="btn small">Zapisz</button>
    </form>
    <div class="u-meta"><?= ocen((int)$u['ile']) ?>, konto od <?= e(substr((string)$u['utworzono'], 0, 10)) ?><?= $swoj ? ', to Ty' : '' ?></div>
    <form method="post" class="u-pass">
      <?= csrf_field() ?><input type="hidden" name="akcja" value="haslo"><input type="hidden" name="id" value="<?= $u['id'] ?>">
      <input type="password" name="haslo" placeholder="Nowe hasło" minlength="6" required autocomplete="new-password" aria-label="Nowe hasło dla <?= e($u['login']) ?>">
      <button class="btn small ghost">Ustaw hasło</button>
    </form>
    <?php if (!$swoj): ?>
    <form method="post" data-confirm="Usunąć konto „<?= e($u['login']) ?>” razem z jego ocenami?">
      <?= csrf_field() ?><input type="hidden" name="akcja" value="usun"><input type="hidden" name="id" value="<?= $u['id'] ?>">
      <button class="btn small danger">Usuń konto</button>
    </form>
    <?php endif; ?>
  </li>
<?php endforeach; ?>
</ul>
<?php require __DIR__ . '/../includes/footer.php'; ?>
