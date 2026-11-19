<?php
// Instalator: tworzy bazę i tabele z sql/schema.sql i pokazuje ewentualny błąd.
require_once __DIR__ . '/config.php';
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><meta charset="utf-8"><body style="font:16px system-ui;max-width:700px;margin:40px auto">';
echo '<h1>Instalacja bazy</h1>';
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    exit('<p style="color:#c00">Nie można połączyć z MySQL: ' . htmlspecialchars($e->getMessage()) . '</p><p>Czy MySQL jest uruchomiony w XAMPP? Sprawdź dane w config.php.</p>');
}
$sql = file_get_contents(__DIR__ . '/sql/schema.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql);              // usuń komentarze
$stmts = array_filter(array_map('trim', explode(';', $sql)));
$ok = true;
foreach ($stmts as $s) {
    try { $pdo->exec($s); }
    catch (PDOException $e) {
        $ok = false;
        echo '<p style="color:#c00"><b>Błąd:</b> ' . htmlspecialchars($e->getMessage()) . '</p><pre>' . htmlspecialchars(mb_substr($s, 0, 300)) . '</pre>';
        break;
    }
}
if ($ok) echo '<p style="color:#0a0"><b>Gotowe!</b> Baza i tabele zostały utworzone.</p><p><a href="index.php">Przejdź do biblioteki gier →</a></p>';
