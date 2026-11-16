<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

// Вызываем функцию выхода
logout_user();

header('Location: login.php');
exit;
