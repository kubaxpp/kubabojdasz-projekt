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
    if ($pdo->query("SHOW TABLES LIKE 'uzytkownik'")->rowCount() === 0) { header('Location: ' . base() . 'install.php'); exit; }
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
