<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя

$config = require __DIR__ . '/../includes/config.php';
$imageDir = __DIR__ . '/../uploads/images/';
$maxSize = 10 * 1024 * 1024; // 10 MB
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!is_dir($imageDir)) mkdir($imageDir, 0777, true);

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Ошибка загрузки файла']);
        exit;
    }

    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'Файл слишком большой']);
        exit;
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowedMimes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Недопустимый тип файла']);
        exit;
    }

    $safeName = preg_replace('/[^A-Za-z0-9\-_\.]/', '_', basename($file['name']));
    $target = $imageDir . uniqid('img_') . '_' . $safeName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $url = $config['base_url'] . 'uploads/images/' . basename($target);
        echo json_encode(['location' => $url]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error'=>'Ошибка загрузки изображения']);
