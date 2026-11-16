<?php
// $config должен быть определён перед подключением init.php

// Подключаем конфиг
$config = require __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        $config['db_dsn'],
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе: " . $e->getMessage());
}

// Настройки сессии: сброс при закрытии браузера
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 1440);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

// --- Функции блога ---
// --- Автовыход при бездействии ---
$session_timeout = 1800; // 30 минут (можно поменять)

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
    // Сессия истекла — выходим
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: /admin/login.php?expired=1");
    exit;
}

$_SESSION['LAST_ACTIVITY'] = time(); // обновляем таймштамп
