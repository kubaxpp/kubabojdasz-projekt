<?php
// Kopia danych w JSON (bez haseł). Tylko dla administratora.
require_once __DIR__ . '/../includes/init.php';
require_admin();

$dane = [
    'wygenerowano' => date('c'),
    'gatunki'      => $pdo->query('SELECT id_gatunek, nazwa FROM gatunek ORDER BY id_gatunek')->fetchAll(),
    'gry'          => $pdo->query('SELECT id_gry, tytul, producent, rok_wydania, platforma, ocena, status, opis, okladka, id_gatunek FROM gra ORDER BY id_gry')->fetchAll(),
    'uzytkownicy'  => $pdo->query('SELECT id, login, rola, utworzono FROM uzytkownik ORDER BY id')->fetchAll(),
    'oceny'        => $pdo->query('SELECT id_gry, id_uzytkownik, ocena, komentarz FROM oceny ORDER BY id_gry, id_uzytkownik')->fetchAll(),
];
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="biblioteka-gier-' . date('Y-m-d') . '.json"');
echo json_encode($dane, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
