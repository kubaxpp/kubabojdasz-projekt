<?php
// Instalator: nowa baza (schema.sql) albo aktualizacja istniejącej (aktualizacja.sql).
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
$istnieje = fn(string $t) => $pdo->query("SHOW TABLES FROM `" . DB_NAME . "` LIKE '$t'")->rowCount() > 0;
$baza = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'")->rowCount() > 0;
$aktualizacja = $baza && $istnieje('gra');                   // gry już są – nie kasujemy ich
$plik = $aktualizacja ? 'aktualizacja.sql' : 'schema.sql';
echo '<p>Tryb: <b>' . ($aktualizacja ? 'aktualizacja (gry zostają)' : 'nowa instalacja') . '</b></p>';

$sql = file_get_contents(__DIR__ . '/sql/' . $plik);
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
$ok = true;
foreach (array_filter(array_map('trim', explode(';', $sql))) as $s) {
    try {
        if ($s === "SELECT 'migracja_administratora'") {      // przenieś stare konto admina
            if ($istnieje('administrator')) {
                $pdo->exec("INSERT IGNORE INTO uzytkownik (login, haslo_hash, rola) SELECT login, haslo_hash, 'admin' FROM administrator");
                $pdo->exec('DROP TABLE administrator');
            }
            continue;
        }
        $pdo->exec($s);
    } catch (PDOException $e) {
        $ok = false;
        echo '<p style="color:#c00"><b>Błąd:</b> ' . htmlspecialchars($e->getMessage()) . '</p><pre>' . htmlspecialchars(mb_substr($s, 0, 300)) . '</pre>';
        break;
    }
}
if ($ok) echo '<p style="color:#0a0"><b>Gotowe!</b></p><p><a href="index.php">Przejdź do biblioteki gier →</a></p>';
