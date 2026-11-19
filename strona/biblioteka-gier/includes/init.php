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
    if ($pdo->query("SHOW TABLES LIKE 'gra'")->rowCount() === 0) { header('Location: ' . base() . 'install.php'); exit; }
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

function is_admin(): bool { return !empty($_SESSION['admin']); }
function require_admin(): void {
    if (!is_admin()) { header('Location: ' . base() . 'admin/login.php'); exit; }
}

// ścieżka bazowa aplikacji (np. /biblioteka-gier/)
function base(): string {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $dir = preg_replace('#/admin$#', '', $dir);
    return rtrim($dir, '/') . '/';
}

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'] = [$msg, $type]; }

// identyfikator gościa do ocen (jedna ocena na grę)
function visitor_id(): string {
    if (empty($_COOKIE['gid'])) {
        $id = bin2hex(random_bytes(16));
        setcookie('gid', $id, time() + 60 * 60 * 24 * 365, '/');
        $_COOKIE['gid'] = $id;
    }
    return preg_replace('/[^a-f0-9]/', '', (string)$_COOKIE['gid']);
}

function status_class(string $s): string {
    return ['Ukończona' => 'done', 'W trakcie' => 'doing', 'Do zagrania' => 'todo'][$s] ?? 'todo';
}

function gatunki(PDO $pdo): array {
    return $pdo->query('SELECT * FROM gatunek ORDER BY nazwa')->fetchAll();
}
