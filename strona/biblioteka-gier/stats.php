<?php
require_once __DIR__ . '/includes/init.php';
$title = 'Statystyki';

$st = $pdo->prepare("SELECT COUNT(*) AS wszystkie,
        COALESCE(SUM(g.status='Ukończona'),0) AS ukonczone,
        COALESCE(SUM(g.status='W trakcie'),0) AS w_trakcie,
        COALESCE(SUM(g.status='Do zagrania'),0) AS do_zagrania,
        ROUND(AVG(g.ocena),2) AS srednia
      FROM gra g JOIN biblioteka_gra bg ON bg.id_gry = g.id_gry AND bg.id_biblioteka = ?");
$st->execute([ID_BIBLIOTEKI]);
$s = $st->fetch();

$gat = $pdo->prepare('SELECT ga.nazwa, COUNT(bg.id_gry) AS ile FROM gatunek ga
        LEFT JOIN gra g ON g.id_gatunek = ga.id_gatunek
        LEFT JOIN biblioteka_gra bg ON bg.id_gry = g.id_gry AND bg.id_biblioteka = ?
        GROUP BY ga.id_gatunek, ga.nazwa ORDER BY ga.nazwa');
$gat->execute([ID_BIBLIOTEKI]);
$gat = $gat->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<h1>Statystyki biblioteki</h1>
<div class="stats">
  <div class="stat"><b><?= (int)$s['wszystkie'] ?></b><span>Wszystkie gry</span></div>
  <div class="stat done"><b><?= (int)$s['ukonczone'] ?></b><span>Ukończone</span></div>
  <div class="stat doing"><b><?= (int)$s['w_trakcie'] ?></b><span>W trakcie</span></div>
  <div class="stat todo"><b><?= (int)$s['do_zagrania'] ?></b><span>Do zagrania</span></div>
  <div class="stat"><b><?= $s['srednia'] !== null ? number_format((float)$s['srednia'], 2, ',', '') : '–' ?></b><span>Średnia ocen</span></div>
</div>

<h2>Gry według gatunku</h2>
<ul class="bars">
<?php $max = max(1, ...array_column($gat, 'ile')); foreach ($gat as $g): ?>
  <li><span><?= e($g['nazwa']) ?></span>
      <i style="width:<?= round($g['ile'] / $max * 100) ?>%"></i><em><?= (int)$g['ile'] ?></em></li>
<?php endforeach; ?>
</ul>
<?php require __DIR__ . '/includes/footer.php'; ?>
