<?php
require_once __DIR__ . '/../includes/init.php';
$title = 'Logowanie';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare('SELECT * FROM administrator WHERE login = ?');
    $st->execute([trim($_POST['login'] ?? '')]);
    $a = $st->fetch();
    if ($a && password_verify($_POST['haslo'] ?? '', $a['haslo_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $a['id'];
        flash('Zalogowano jako administrator.');
        header('Location: ../index.php'); exit;
    }
    $err = 'Błędny login lub hasło.';
}
require __DIR__ . '/../includes/header.php';
?>
<h1>Panel administratora</h1>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="form narrow">
  <?= csrf_field() ?>
  <label>Login<input name="login" required autofocus></label>
  <label>Hasło<input type="password" name="haslo" required></label>
  <button class="btn">Zaloguj</button>
  <p class="hint">Domyślnie: admin / admin123</p>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
