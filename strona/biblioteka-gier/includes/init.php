<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

session_start();

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    if ($pdo->query("SHOW TABLES LIKE 'uzytkownik'")->rowCount() === 0
        || $pdo->query("SHOW COLUMNS FROM gra LIKE 'opis'")->rowCount() === 0) { header('Location: ' . base() . 'install.php'); exit; }
} catch (PDOException $e) {
    if ($e->getCode() == 1049) { header('Location: ' . base() . 'install.php'); exit; } // brak bazy
    http_response_code(500);
    exit('<h1>Brak połączenia z bazą danych</h1><p>Uruchom MySQL w XAMPP i otwórz <a href="install.php">install.php</a>.</p>');
}

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Nieprawidłowy token formularza.');
    }
}

function is_logged(): bool { return !empty($_SESSION['uid']); }
function is_admin(): bool { return is_logged() && ($_SESSION['rola'] ?? '') === 'admin'; }
function require_login(): void {
    if (!is_logged()) { flash('Zaloguj się, aby to zrobić.', 'warn'); header('Location: ' . base() . 'login.php'); exit; }
}
function require_admin(): void {
    if (!is_admin()) { flash('Ta strona jest tylko dla administratora.', 'warn'); header('Location: ' . base() . 'login.php'); exit; }
}

// ścieżka bazowa aplikacji (np. /biblioteka-gier/)
function base(): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $dir = preg_replace('#/admin$#', '', $dir);
    return rtrim($dir, '/') . '/';
}

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'] = [$msg, $type]; }


// odmiana: 1 ocena, 2-4 oceny, 5+ ocen
function ocen(int $n): string {
    $m10 = $n % 10; $m100 = $n % 100;
    if ($n === 1) return '1 ocena';
    return $n . ($m10 >= 2 && $m10 <= 4 && ($m100 < 12 || $m100 > 14) ? ' oceny' : ' ocen');
}

function status_class(string $s): string {
    return ['Ukończona' => 'done', 'W trakcie' => 'doing', 'Do zagrania' => 'todo'][$s] ?? 'todo';
}

// kolor grzbietu pudełka zależny od gatunku
function genre_color(string $nazwa): string {
    $map = ['FPS' => '#E5484D', 'RPG' => '#7B5CFA', 'Wyścigowe' => '#F97316', 'Sportowe' => '#0E9F6E'];
    if (isset($map[$nazwa])) return $map[$nazwa];
    $extra = ['#2563EB', '#DB2777', '#0891B2', '#CA8A04'];
    return $extra[crc32($nazwa) % count($extra)];
}

function gatunki(PDO $pdo): array {
    return $pdo->query('SELECT * FROM gatunek ORDER BY nazwa')->fetchAll();
}

// ---- okładki gier ----
const UPLOAD_DIR = __DIR__ . '/../uploads/';

// Zapisuje wgraną okładkę. Zwraca nazwę pliku, a gdy nic nie wgrano albo jest błąd – null (błąd w $err).
function store_cover(array $file, string &$err): ?string {
    $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($code === UPLOAD_ERR_NO_FILE) return null;
    if ($code !== UPLOAD_ERR_OK) { $err = 'Nie udało się wgrać okładki.'; return null; }
    if ($file['size'] > 2 * 1024 * 1024) { $err = 'Okładka może mieć maksymalnie 2 MB.'; return null; }
    $info = @getimagesize($file['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if (!$ext) { $err = 'Okładka musi być obrazem JPG, PNG lub WebP.'; return null; }
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) { $err = 'Nie można zapisać okładki w folderze uploads.'; return null; }
    return $name;
}

function remove_cover(?string $f): void {
    if ($f && preg_match('/^[a-f0-9]{16}\.(jpg|png|webp)$/', $f)) @unlink(UPLOAD_DIR . $f);
}

// okładka albo kolorowy placeholder z pierwszą literą tytułu
function cover(array $g): string {
    if (!empty($g['okladka'])) {
        return '<div class="cover"><img src="' . e(base() . 'uploads/' . rawurlencode($g['okladka'])) . '" alt="Okładka gry ' . e($g['tytul']) . '" loading="lazy"></div>';
    }
    return '<div class="cover ph" aria-hidden="true"><span>' . e(mb_strtoupper(mb_substr($g['tytul'], 0, 1))) . '</span></div>';
}

function przelicz_ocene(PDO $pdo, int $id): void {
    $pdo->prepare('UPDATE gra SET ocena = (SELECT ROUND(AVG(ocena),1) FROM oceny WHERE id_gry = ?) WHERE id_gry = ?')->execute([$id, $id]);
}
