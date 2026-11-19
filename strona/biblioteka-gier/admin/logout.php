<?php
require_once __DIR__ . '/../includes/init.php';
unset($_SESSION['admin']);
session_regenerate_id(true);
flash('Wylogowano.');
header('Location: ../index.php');
