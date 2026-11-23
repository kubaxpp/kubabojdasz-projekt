<?php
require_once __DIR__ . '/includes/init.php';
$title = 'Załóż konto';
$errors = [];
$login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $h1 = (string)($_POST['haslo'] ?? '');
    $h2 = (string)($_POST['haslo2'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $login)) $errors[] = 'Login: 3–30 znaków, tylko litery bez polskich znaków, cyfry i podkreślnik.';
    if (strlen($h1) < 6) $errors[] = 'Hasło musi mieć co najmniej 6 znaków.';
    if ($h1 !== $h2)     $errors[] = 'Hasła nie są takie same.';
    if (!$errors) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM uzytkownik WHERE login = ?');
        $st->execute([$login]);
        if ($st->fetchColumn()) $errors[] = 'Ten login jest już zajęty.';
    }
    if (!$errors) {
        $pdo->prepare('INSERT INTO uzytkownik (login, haslo_hash, rola) VALUES (?,?,\'user\')')
            ->execute([$login, password_hash($h1, PASSWORD_DEFAULT)]);
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$pdo->lastInsertId();
        $_SESSION['login'] = $login;
        $_SESSION['rola'] = 'user';
        flash('Konto zostało utworzone. Możesz oceniać gry.');
        header('Location: index.php'); exit;
    }
}
require __DIR__ . '/includes/header.php';
?>
<h1>Załóż konto</h1>
<?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>
<form method="post" class="form narrow">
  <?= csrf_field() ?>
  <label>Login<input name="login" required maxlength="30" value="<?= e($login) ?>" autocomplete="username"></label>
  <label>Hasło<input type="password" name="haslo" required minlength="6" autocomplete="new-password"></label>
  <label>Powtórz hasło<input type="password" name="haslo2" required minlength="6" autocomplete="new-password"></label>
  <button class="btn">Załóż konto</button>
  <p class="hint">Masz już konto? <a href="login.php">Zaloguj się</a>.</p>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
