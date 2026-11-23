<?php
require_once __DIR__ . '/includes/init.php';
unset($_SESSION['uid'], $_SESSION['login'], $_SESSION['rola']);
session_regenerate_id(true);
flash('Wylogowano.');
header('Location: index.php');
