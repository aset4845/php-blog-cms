<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя

$action  = $_POST['action'] ?? 'create';
$title   = trim($_POST['title'] ?? '');
$content = $_POST['content'] ?? '';
$author  = trim($_POST['author'] ?? 'Неизвестный автор');
$header_button_id = !empty($_POST['header_button_id']) ? (int)$_POST['header_button_id'] : null;

if (empty($title) || empty($content)) {
    die('Заголовок и контент обязательны');
}

// --- Используем уникальный slug ---
$slug = slugifyUnique($title);

try {
    $pdo->beginTransaction();

    if ($action === 'create') {
        $stmt = $pdo->prepare('
            INSERT INTO posts (title, slug, content, author, header_button_id, created_at) 
            VALUES (:t, :s, :c, :a, :hb, NOW())
        ');
        $stmt->execute([
            ':t'  => $title,
            ':s'  => $slug,
            ':c'  => $content,
            ':a'  => $author,
            ':hb' => $header_button_id
        ]);
        $postId = $pdo->lastInsertId();

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new Exception("Не указан ID поста");

        $stmt = $pdo->prepare('
            UPDATE posts 
            SET title = :t, slug = :s, content = :c, author = :a, header_button_id = :hb 
            WHERE id = :id
        ');
        $stmt->execute([
            ':t'  => $title,
            ':s'  => $slug,
            ':c'  => $content,
            ':a'  => $author,
            ':hb' => $header_button_id,
            ':id' => $id
        ]);
        $postId = $id;
    }

    // --- Обработка новых загруженных изображений ---
    if (!empty($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $fileArr = [
                    'name' => $_FILES['images']['name'][$key],
                    'tmp_name' => $tmpName,
                    'size' => $_FILES['images']['size'][$key],
                    'error' => $_FILES['images']['error'][$key]
                ];
                $uploaded = save_uploaded_file($fileArr, 'image', $config);
                $stmtm = $pdo->prepare('
                    INSERT INTO media (post_id, filename, original_name, mime, type) 
                    VALUES (:p, :f, :o, :m, :t)
                ');
                $stmtm->execute([
                    ':p' => $postId,
                    ':f' => $uploaded['filename'],
                    ':o' => $uploaded['original'],
                    ':m' => $uploaded['mime'],
                    ':t' => 'image'
                ]);
            }
        }
    }

    // --- Обработка новых загруженных видео ---
    if (!empty($_FILES['videos'])) {
        foreach ($_FILES['videos']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['videos']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $fileArr = [
                    'name' => $_FILES['videos']['name'][$key],
                    'tmp_name' => $tmpName,
                    'size' => $_FILES['videos']['size'][$key],
                    'error' => $_FILES['videos']['error'][$key]
                ];
                $uploaded = save_uploaded_file($fileArr, 'video', $config);
                $stmtm = $pdo->prepare('
                    INSERT INTO media (post_id, filename, original_name, mime, type) 
                    VALUES (:p, :f, :o, :m, :t)
                ');
                $stmtm->execute([
                    ':p' => $postId,
                    ':f' => $uploaded['filename'],
                    ':o' => $uploaded['original'],
                    ':m' => $uploaded['mime'],
                    ':t' => 'video'
                ]);
            }
        }
    }

    $pdo->commit();
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Ошибка: " . $e->getMessage());
}
