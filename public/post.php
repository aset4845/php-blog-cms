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

// Загружаем кнопки меню
$buttons = $pdo->query("SELECT * FROM header_buttons ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Загружаем медиа
$mediaStmt = $pdo->prepare("SELECT * FROM media WHERE post_id = ? ORDER BY id ASC");
$mediaStmt->execute([$post['id']]);
$postMedia = $mediaStmt->fetchAll(PDO::FETCH_ASSOC);

// Загружаем комментарии
$commentsStmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at DESC");
$commentsStmt->execute([$post['id']]);
$comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка формы комментариев
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = $_POST['author'] ?? 'Аноним';
    $content = $_POST['content'] ?? '';
    if (trim($content) !== '') {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, author, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$post['id'], $author, $content]);
        header("Location: post.php?slug=" . urlencode($slug));
        exit;
    }
}

// --- Вспомогательные функции ---
function parse_youtube_id($url) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]+)/', $url, $m)) return $m[1];
    return '';
}
function parse_vimeo_id($url) {
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) return $m[1];
    return '';
}

// --- Рендеринг контента ---
function renderMediaInContent($content, $postMedia) {
    return preg_replace_callback('/\[(img|video|youtube|vimeo)(\d*)\=?([^\]]*)\]/i', function($matches) use ($postMedia) {
        $type = strtolower($matches[1]);
        $index = $matches[2] !== '' ? (int)$matches[2] : null;
        $url = trim($matches[3] ?? '');

        // Локальные фото/видео
        if (($type === 'img' || $type === 'video') && $index !== null && isset($postMedia[$index])) {
            $media = $postMedia[$index];
            if ($type === 'img' && $media['type'] === 'image') {
                return '<img src="../uploads/images/' . htmlspecialchars($media['filename']) . '" class="media-inline" alt="">';
            }
            if ($type === 'video' && $media['type'] === 'video') {
                return '<video controls class="media-inline">
                            <source src="../uploads/videos/' . htmlspecialchars($media['filename']) . '" type="' . htmlspecialchars($media['mime']) . '">
                        </video>';
            }
        }

        // Внешние фото
        if ($type === 'img' && $url) {
            return '<img src="' . htmlspecialchars($url) . '" class="media-inline" alt="">';
        }

        // Внешние mp4
        if ($type === 'video' && $url) {
            return '<video controls class="media-inline">
                        <source src="' . htmlspecialchars($url) . '" type="video/mp4">
                    </video>';
        }

        // YouTube
        if ($type === 'youtube' && $url) {
            $videoId = parse_youtube_id($url);
            if ($videoId) {
                return '<div class="iframe-wrapper">
                            <iframe src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '" frameborder="0" allowfullscreen></iframe>
                        </div>';
            }
        }

        // Vimeo
        if ($type === 'vimeo' && $url) {
            $videoId = parse_vimeo_id($url);
            if ($videoId) {
                return '<div class="iframe-wrapper">
                            <iframe src="https://player.vimeo.com/video/' . htmlspecialchars($videoId) . '" frameborder="0" allowfullscreen></iframe>
                        </div>';
            }
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
        :root {
            --bg: #f5f6fa;
            --text: #333;
            --accent: #4CAF50;
            --card: #fff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 20px;
        }
        .container { max-width: 900px; margin: 0 auto; }

        /* Header */
       header {
    position: relative;
    top: 20px;
    margin: 0 auto;
    width: 99%;
    background: linear-gradient(135deg, #45a834ff, #45a834ff);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 40px;
    color: white;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

header h1 {
    margin: 0;
}

header h1 a {
    text-decoration: none;
    color: #fff;
}

header nav {
    margin-top: 20px;
    display: flex;
    flex-wrap: wrap;              /* перенос строк */
    justify-content: space-evenly; /* равномерное распределение */
    gap: 12px;
}

header nav a {
    flex: 1 1 auto;               /* кнопки тянутся */
    min-width: 120px;             /* минимальная ширина */
    text-align: center;
    padding: 10px 18px;
    background: rgba(255,255,255,0.15);
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    transition: 0.3s;
}

header nav a:hover,
header nav a.active {
    background: rgba(255,255,255,0.35);
}

header nav a:hover,
header nav a.active {
    background: rgba(255,255,255,0.35);
}

        /* Article */
        article {
            background: var(--card);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            min-width: 700px;
        }
        article h2 { margin-bottom: 10px; text-align:center; }
        article .meta { color: #666; font-size: 14px; margin-bottom: 15px; text-align:center; }

        .post-content {
            margin-top: 20px;
            text-align: justify;
            line-height: 1.7;
            font-size: 18px;
        }

/* Локальные медиа */
.media-inline {
    float: left;
    width: 40%;
    max-width: 200px;

    margin: 0 20px 15px 0;
    overflow: hidden;
    border-radius: 8px;
    background: #fff;
}

.media-inline img,
.media-inline video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 8px;
}

/* Внешние iframe видео */
.iframe-wrapper {
    position: relative;
    width: 70%;
    margin: 20px auto;
    padding-top: 39.375%; /* 16:9 */
    overflow: hidden;
    border-radius: 10px;
    background: #000;
}

.iframe-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

/* Очистка float после блока текста */
.post-content::after {
    content: "";
    display: table;
    clear: both;
}

        .post-content p { margin-bottom: 20px; clear: none; }

        /* Comments */
        #comments {
            background: var(--card);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .comment {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .comment:last-child { border-bottom: none; }
        .comment strong { color: var(--accent); }
        #comments form input, #comments form textarea {
            width: 100%; padding: 10px; margin: 8px 0;
            border: 1px solid #ccc; border-radius: 6px;
            font-family: inherit;
        }
        #comments form button {
            background: var(--accent); color: #fff;
            border: none; padding: 10px 20px;
            border-radius: 6px; cursor: pointer;
        }
        #comments form button:hover { background: #45a049; }

        /* Responsive */
        @media (max-width: 700px) {
            body { padding: 10px; }
            header nav { flex-direction: column; }
            .media-inline { float:none; width:100%; margin:15px auto; }
        }
    </style>
</head>
<body>
<header>
    <h1><a href="index.php?section=16">Үміткер ауылы</a></h1>
    <nav>
        <?php foreach($buttons as $b): ?>
            <a href="index.php?section=<?= $b['id'] ?>" class="<?= ($post['header_button_id']==$b['id'])?'active':'' ?>">
                <?= htmlspecialchars($b['title']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>

<div class="container">
    <article>
        <h2><?= htmlspecialchars($post['title']) ?></h2>
        <div class="meta">
            Автор: <?= htmlspecialchars($post['author']) ?> | <?= date("d.m.Y H:i", strtotime($post['created_at'])) ?>
        </div>
        <div class="post-content">
            <?= renderMediaInContent($post['content'], $postMedia) ?>
        </div>
    </article>

    <section id="comments">
        <h3>Комментарии</h3>
        <?php foreach($comments as $c): ?>
            <div class="comment">
                <strong><?= htmlspecialchars($c['author']) ?>:</strong>
                <p><?= nl2br(htmlspecialchars($c['content'])) ?></p>
                <small><?= htmlspecialchars($c['created_at']) ?></small>
            </div>
        <?php endforeach; ?>

        <h4>Оставить комментарий</h4>
        <form method="POST">
            <p><input type="text" name="author" placeholder="Ваше имя" required></p>
            <p><textarea name="content" placeholder="Комментарий" required></textarea></p>
            <p><button type="submit">Отправить</button></p>
        </form>
    </section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
