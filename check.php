<?php
$config = require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/init.php';

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ?");
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    echo "<h1>Пост не найден</h1>";
    exit;
}

// --- Получаем медиа этого поста ---
$mediaStmt = $pdo->prepare("SELECT * FROM media WHERE post_id = ? ORDER BY id ASC");
$mediaStmt->execute([$post['id']]);
$postMedia = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Вспомогательные функции ---
function parse_youtube_id($url) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]+)/', $url, $m)) return $m[1];
    return '';
}
function parse_vimeo_id($url) {
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) return $m[1];
    return '';
}

// --- Рендеринг контента с шорткодами ---
function renderMediaInContent($content, $postMedia) {
    return preg_replace_callback('/\[(img|video|youtube|vimeo)(\d*)\=?([^\]]*)\]/i', function($matches) use ($postMedia) {
        $type = strtolower($matches[1]);
        $index = $matches[2] !== '' ? (int)$matches[2] : null;
        $url = trim($matches[3] ?? '');

        // Локальные фото/видео
        if (($type === 'img' || $type === 'video') && $index !== null && isset($postMedia[$index])) {
            $media = $postMedia[$index];
            if ($type === 'img' && $media['type'] === 'image') {
                return '<img src="../uploads/images/' . htmlspecialchars($media['filename']) . '" alt="" style="display:block;margin:15px auto;max-width:90%;border-radius:10px;">';
            }
            if ($type === 'video' && $media['type'] === 'video') {
                return '<video controls style="display:block;margin:15px auto;max-width:90%;border-radius:10px;">
                            <source src="../uploads/videos/' . htmlspecialchars($media['filename']) . '" type="' . htmlspecialchars($media['mime']) . '">
                        </video>';
            }
        }

        // Внешние фото
        if ($type === 'img' && $url) {
            return '<img src="' . htmlspecialchars($url) . '" alt="" style="display:block;margin:15px auto;max-width:90%;border-radius:10px;">';
        }

        // Внешние mp4
        if ($type === 'video' && $url) {
            return '<video controls style="display:block;margin:15px auto;max-width:90%;border-radius:10px;">
                        <source src="' . htmlspecialchars($url) . '" type="video/mp4">
                    </video>';
        }

        // YouTube
        if ($type === 'youtube' && $url) {
            return '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . htmlspecialchars(parse_youtube_id($url)) . '" frameborder="0" allowfullscreen style="display:block;margin:15px auto;max-width:90%;border-radius:10px;"></iframe>';
        }

        // Vimeo
        if ($type === 'vimeo' && $url) {
            return '<iframe width="560" height="315" src="https://player.vimeo.com/video/' . htmlspecialchars(parse_vimeo_id($url)) . '" frameborder="0" allowfullscreen style="display:block;margin:15px auto;max-width:90%;border-radius:10px;"></iframe>';
        }

        return '';
    }, $content);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['title']) ?></title>
    <link rel="stylesheet" href="<?= $config['base_url'] ?>public/style.css">
    <style>
        .container { max-width: 90%; /* сузил ширину, чтобы текст удобнее читался */ margin: auto; padding: 0 15px; } /* Reset */ * { margin: 0; padding: 0; box-sizing: border-box; } body { font-family: "Segoe UI", Tahoma, sans-serif; background: #f5f6fa; color: #333; line-height: 1.6; } /* --- Контейнер --- */ .container { max-width: 90%; margin: 0 auto; padding: 20px; } /* --- Header --- */ header { position: relative; top: 20px; margin: 0 auto; width: 99%; background: linear-gradient(135deg, #000000, #000000); padding: 20px; border-radius: 12px; margin-bottom: 40px; color: white; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.15); } header h1 { margin: 0; } header h1 a { text-decoration: none; color: #fff; } header nav { margin-top: 20px; display: flex; flex-wrap: wrap; /* перенос строк */ justify-content: space-evenly; /* равномерное распределение */ gap: 12px; } header nav a { flex: 1 1 auto; /* кнопки тянутся */ min-width: 120px; /* минимальная ширина */ text-align: center; padding: 10px 18px; background: rgba(255,255,255,0.15); color: #fff; text-decoration: none; border-radius: 6px; transition: 0.3s; } header nav a:hover, header nav a.active { background: rgba(255,255,255,0.35); } header nav a:hover, header nav a.active { background: rgba(255,255,255,0.35); } /* --- Посты --- */ .posts { display: flex; flex-direction: column; gap: 25px; } /* --- Карточка поста --- */ .post { background: #fff; border-radius: 12px; box-shadow: 0 3px 12px rgba(0,0,0,0.08); padding: 20px; transition: transform 0.2s, box-shadow 0.2s; position: relative; } .post:hover { transform: translateY(-4px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); } .post h2 { margin-bottom: 12px; font-size: 20px; } .post h2 a { text-decoration: none; color: #333; transition: color 0.2s; text-align: center; } .post h2 a:hover { color: #007BFF; } /* --- Медиа внутри поста --- */ .post img, .post video, .post iframe { display: block; width: 50%; aspect-ratio: 16 / 9; height: auto; object-fit: cover; border-radius: 10px; margin: 15px auto; } /* --- Превью текста --- */ .post p { color: #555; margin-top: 8px; } /* --- Кликабельная ссылка на весь пост --- */ .post a.post-full-link { position: absolute; inset: 0; text-indent: -9999px; z-index: 1; } /* --- Карта --- */ iframe.map { width: 100%; border: 0; border-radius: 10px; margin-top: 40px; height: 400px; } /* --- Responsive --- */ @media (max-width: 600px) { header h1 a { font-size: 20px; } .post h2 { font-size: 18px; } .post img, .post video, .post iframe { max-height: 250px; } }
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: #f5f6fa;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }
        h1 {
            margin-bottom: 20px;
            font-size: 28px;
            text-align: center;
        }
        .meta {
            color: #777;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }
        .content img, 
        .content video, 
        .content iframe {
            display: block;
            margin: 15px auto;
            max-width: 90%;
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <div class="meta">
        Автор: <?= htmlspecialchars($post['author']) ?> | <?= date("d.m.Y H:i", strtotime($post['created_at'])) ?>
    </div>
    <div class="content">
        <?= renderMediaInContent($post['content'], $postMedia) ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
