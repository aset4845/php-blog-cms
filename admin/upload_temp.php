<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя

// --- Настройки ---
$maxImageSize = 5 * 1024 * 1024;  // 5 MB
$maxVideoSize = 50 * 1024 * 1024; // 50 MB
$allowedImageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedVideoMimes = ['video/mp4', 'video/webm', 'video/ogg'];

// --- Пути к папкам ---
$baseUploadDir = __DIR__ . '/../uploads';
$imageDir = $baseUploadDir . '/images';
$videoDir = $baseUploadDir . '/videos';

foreach ([$imageDir, $videoDir] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        die("Не удалось создать папку: $dir");
    }
    if (!is_writable($dir)) die("Папка недоступна для записи: $dir");
}

// --- Очистка имени файла ---
function cleanFileName($name) {
    return preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $name);
}

// --- Уникальный слаг ---
function generateUniqueSlug($pdo, $title) {
    $baseSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
    $slug = $baseSlug;
    $i = 1;
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
    while(true){
        $stmt->execute([$slug]);
        if(!$stmt->fetch()) break;
        $slug = $baseSlug . '-' . $i;
        $i++;
    }
    return $slug;
}

// --- Массивы для сохранённых файлов ---
$uploadedImages = [];
$uploadedVideos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $author = $_POST['author'] ?? 'Неизвестный автор';
    $headerButtonId = !empty($_POST['header_button_id']) ? (int)$_POST['header_button_id'] : null;
    $slug = generateUniqueSlug($pdo, $title);

    if (empty($title) || empty($content)) die("Заголовок и контент обязательны");

    // --- Создаём пост ---
    $stmt = $pdo->prepare(
        "INSERT INTO posts (slug, title, content, author, header_button_id, created_at) 
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$slug, $title, $content, $author, $headerButtonId]);
    $postId = $pdo->lastInsertId();

    // --- Функция сохранения файлов ---
    function saveFile($file, $type, $dir, $allowedMimes, $maxSize, $postId, $pdo) {
        if ($file['error'] !== UPLOAD_ERR_OK) return "Ошибка загрузки: " . $file['name'];
        if ($file['size'] > $maxSize) return "Файл слишком большой: " . $file['name'];

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowedMimes)) return "Недопустимый тип файла: " . $file['name'];

        $filename = uniqid(($type==='image'?'img_':'vid_')) . '_' . preg_replace('/[^A-Za-z0-9\-_\.]/', '_', basename($file['name']));
        $dest = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) return "Не удалось сохранить файл: " . $file['name'];

        $stmt = $pdo->prepare(
            "INSERT INTO media (post_id, filename, original_name, mime, type, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$postId, $filename, $file['name'], $mime, $type]);

        return true;
    }

    // --- Сохраняем изображения ---
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $i => $name) {
            $fileArr = [
                'name' => $_FILES['images']['name'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'size' => $_FILES['images']['size'][$i],
                'error' => $_FILES['images']['error'][$i]
            ];
            $result = saveFile($fileArr, 'image', $imageDir, $allowedImageMimes, $maxImageSize, $postId, $pdo);
            if ($result !== true) echo "<p style='color:red;'>$result</p>";
            else $uploadedImages[] = $fileArr['name'];
        }
    }

    // --- Сохраняем видео ---
    if (!empty($_FILES['videos']['name'][0])) {
        foreach ($_FILES['videos']['name'] as $i => $name) {
            $fileArr = [
                'name' => $_FILES['videos']['name'][$i],
                'tmp_name' => $_FILES['videos']['tmp_name'][$i],
                'size' => $_FILES['videos']['size'][$i],
                'error' => $_FILES['videos']['error'][$i]
            ];
            $result = saveFile($fileArr, 'video', $videoDir, $allowedVideoMimes, $maxVideoSize, $postId, $pdo);
            if ($result !== true) echo "<p style='color:red;'>$result</p>";
            else $uploadedVideos[] = $fileArr['name'];
        }
    }

    header('Location: new_post.php?success=1');
    exit;
}

// --- Получаем разделы ---
$sections = $pdo->query("SELECT id, title FROM header_buttons ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
