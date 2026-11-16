<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя

$config = require __DIR__ . '/../includes/config.php';
$videoDir = __DIR__ . '/../uploads/videos/';
$maxSize = 50 * 1024 * 1024; // 50 MB
$allowedMimes = ['video/mp4', 'video/webm', 'video/ogg'];

if (!is_dir($videoDir)) mkdir($videoDir, 0777, true);

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
    $target = $videoDir . uniqid('video_') . '_' . $safeName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $url = $config['base_url'] . 'uploads/videos/' . basename($target);
        echo json_encode(['location' => $url]);
        exit;
    }
}

http_response_code(400);
echo json_encode(['error'=>'Ошибка загрузки видео']);
