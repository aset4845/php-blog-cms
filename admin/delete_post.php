<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя

$id = (int) ($_GET['id'] ?? 0);
if (!$id) { 
    header('Location: dashboard.php'); 
    exit; 
}

// delete related media files
$stmt = $pdo->prepare('SELECT filename, type FROM media WHERE post_id = :p');
$stmt->execute([':p' => $id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $path = ($m['type'] === 'image' ? $config['upload']['images_dir'] : $config['upload']['videos_dir']) . '/' . $m['filename'];
    if (file_exists($path)) {
        @unlink($path);
    }
}

// delete cover
$st = $pdo->prepare('SELECT cover FROM posts WHERE id = :id');
$st->execute([':id' => $id]);
$p = $st->fetch(PDO::FETCH_ASSOC);
if (!empty($p['cover'])) { 
    @unlink($config['upload']['images_dir'] . '/' . $p['cover']); 
    @unlink($config['upload']['images_dir'] . '/thumb_' . $p['cover']); 
}

// delete media records
$pdo->prepare('DELETE FROM media WHERE post_id = :id')->execute([':id' => $id]);

// delete post
$pdo->prepare('DELETE FROM posts WHERE id = :id')->execute([':id' => $id]);

header('Location: dashboard.php');
exit;
