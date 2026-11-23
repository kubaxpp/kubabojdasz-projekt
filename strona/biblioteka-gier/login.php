<?php
require_once __DIR__ . '/includes/init.php';
$title = 'Logowanie';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare('SELECT * FROM uzytkownik WHERE login = ?');
    $st->execute([trim($_POST['login'] ?? '')]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['haslo'] ?? '', $u['haslo_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        $_SESSION['login'] = $u['login'];
        $_SESSION['rola'] = $u['rola'];
        flash('Zalogowano jako ' . $u['login'] . '.');
        header('Location: index.php'); exit;
    }
    $err = 'Błędny login lub hasło.';
}
require __DIR__ . '/includes/header.php';
?>
<h1>Logowanie</h1>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="form narrow">
  <?= csrf_field() ?>
  <label>Login<input name="login" required autofocus autocomplete="username"></label>
  <label>Hasło<input type="password" name="haslo" required autocomplete="current-password"></label>
  <button class="btn">Zaloguj</button>
  <p class="hint">Nie masz konta? <a href="register.php">Załóż je</a>.</p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
