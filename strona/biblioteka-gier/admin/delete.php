<?php
require_once __DIR__ . '/../includes/init.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../index.php'); exit; }
csrf_check();                                                // PU-10 (potwierdzenie robi JS w app.js)
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT okladka FROM gra WHERE id_gry = ?');
$st->execute([$id]);
$okladka = $st->fetchColumn();
$pdo->prepare('DELETE FROM gra WHERE id_gry = ?')->execute([$id]);
remove_cover($okladka ?: null);
flash('Gra została usunięta.');
header('Location: ../index.php');
