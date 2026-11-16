<?php 
$config = require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/init.php';

// --- Определяем выбранный раздел ---
$header_button_id = isset($_GET['section']) ? (int)$_GET['section'] : 16;


// --- Получаем посты ---
if ($header_button_id) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE header_button_id = ? ORDER BY created_at ASC");
    $stmt->execute([$header_button_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at ASC");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// --- Загружаем кнопки для хедера ---
$buttonsStmt = $pdo->query("SELECT * FROM header_buttons ORDER BY id ASC");
$headerButtons = $buttonsStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Получаем медиа для всех постов ---
$mediaAll = [];
$mediaStmt = $pdo->query("SELECT * FROM media ORDER BY id ASC");
while ($m = $mediaStmt->fetch(PDO::FETCH_ASSOC)) {
    $mediaAll[$m['post_id']][] = $m;
}

// --- Функция для рендеринга только первого медиа ---
// --- Функция для рендеринга только первого медиа ---
// --- Рендерим только первое медиа без шорткодов ---
function renderFirstMedia($content, $postMedia) {
    // Если есть локальные медиа, берем первый
    if (!empty($postMedia)) {
        $media = $postMedia[0];
        if ($media['type'] === 'image') {
            return '<img src="../uploads/images/' . htmlspecialchars($media['filename']) . '" alt="">';
        } elseif ($media['type'] === 'video') {
            return '<video controls>
                        <source src="../uploads/videos/' . htmlspecialchars($media['filename']) . '" type="' . htmlspecialchars($media['mime']) . '">
                    </video>';
        }
    }

    // Если медиа нет, ищем внешние ссылки в контенте
    if (preg_match('/\[(img|video|youtube|vimeo)(\d*)=?([^\]]+)\]/i', $content, $matches)) {
        $type = strtolower($matches[1]);
        $url = trim($matches[3] ?? '');

        if (!$url) return '';

        if ($type === 'img') {
            return '<img src="' . htmlspecialchars($url) . '" alt="">';
        }
        if ($type === 'video') {
            return '<video controls>
                        <source src="' . htmlspecialchars($url) . '" type="video/mp4">
                    </video>';
        }
        if ($type === 'youtube') {
            return '<iframe width="100%" height="200" src="https://www.youtube.com/embed/' . htmlspecialchars(parse_youtube_id($url)) . '" frameborder="0" allowfullscreen></iframe>';
        }
        if ($type === 'vimeo') {
            return '<iframe width="100%" height="200" src="https://player.vimeo.com/video/' . htmlspecialchars(parse_vimeo_id($url)) . '" frameborder="0" allowfullscreen></iframe>';
        }
    }

    return '';
}



// --- Вспомогательные функции для YouTube/Vimeo ---
function parse_youtube_id($url) {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]+)/', $url, $m)) return $m[1];
    return '';
}
function parse_vimeo_id($url) {
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) return $m[1];
    return '';
}
?>
<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <title>Үміткер ауылы</title>
    
    <style>
        
   .container { 
    max-width: 90%; /* сузил ширину, чтобы текст удобнее читался */
    margin: auto;
    padding: 0 15px;
}

/* Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: #f5f6fa;
    color: #333;
    line-height: 1.6;
}

/* --- Контейнер --- */
.container {
    max-width: 40%;
    margin: 0 auto;
    padding: 20px;
}

/* --- Header --- */
header {
    position: relative;
    top: 20px;
    margin: 0 auto;
    width: 99%;
    background: linear-gradient(135deg, #45a834ff, #215e16ff);
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

/* --- Посты --- */
.posts {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

/* --- Карточка поста --- */
.post {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
    padding: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.post:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}

.post h2 {
    margin-bottom: 12px;
    font-size: 20px;
}

.post h2 a {
    text-decoration: none;
    color: #333;
    transition: color 0.2s;
    text-align: center;
}

.post h2 a:hover {
    color: #007BFF;
}

/* --- Медиа внутри поста --- */
.post img,
.post video{
    display: block;
    width: 20%;
     
    height: auto;
    object-fit: cover;
    border-radius: 10px;
    margin: 15px auto;
}
.post iframe {
    display: block;
    width: 50%;
    aspect-ratio: 16 / 9; 
    height: auto;
    object-fit: cover;
    border-radius: 10px;
    margin: 15px auto;
}

/* --- Превью текста --- */
.post p {
    color: #555;
    margin-top: 8px;
}

/* --- Кликабельная ссылка на весь пост --- */
.post a.post-full-link {
    position: absolute;
    inset: 0;
    text-indent: -9999px;
    z-index: 1;
}

/* --- Карта --- */
iframe.map {
    width: 100%;
    border: 0;
    border-radius: 10px;
    margin-top: 40px;
    height: 400px;
}

/* --- Responsive --- */
@media (max-width: 600px) {
    header h1 a {
        font-size: 20px;
    }
    .post h2 {
        font-size: 18px;
    }
    .post img,
    .post video,
    .post iframe {
        max-height: 250px;
    }
}



    </style>
</head>
<body>



<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="container">
    
    <div class="posts">
        <?php if ($posts): ?>
          <?php
// --- Функция для удаления шорткодов из текста ---
if (!function_exists('stripShortcodes')) {
    function stripShortcodes($text) {
        return preg_replace('/\[(img|video|youtube|vimeo)(\d*)=?([^\]]*)\]/i', '', $text);
    }
}

// --- Вывод постов ---
foreach ($posts as $post):
    $postMedia = $mediaAll[$post['id']] ?? [];
    ?>
    <div class="post">
        <a href="post.php?slug=<?= htmlspecialchars($post['slug']) ?>" class="post-full-link">Читать</a>
        <h2>
            <a href="post.php?slug=<?= htmlspecialchars($post['slug']) ?>">
                <?= htmlspecialchars($post['title']) ?>
            </a>
        </h2>

        <?php
        // Отображаем первое медиа
        echo renderFirstMedia($post['content'], $postMedia);
        ?>

        <p>
            <?= mb_strimwidth(stripShortcodes(strip_tags($post['content'])), 0, 150, "...") ?>
        </p>
    </div>
<?php endforeach; ?>


        <?php else: ?>
            <p>Нет постов в этом разделе.</p>
        <?php endif; ?>
    </div>
     <?php
    // Показываем карту только для раздела 16
    if ($header_button_id === 16) {
        echo '<iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d20359.865015252086!2d74.2643594!3d50.3668691!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x425b85d10695d463%3A0x4023232bcce0c4f6!2zMTAwMDAwINCj0LzRg9GC0LrQtdGA!5e0!3m2!1sru!2skz!4v1759151057341!5m2!1sru!2skz" 
        width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
    }
    ?>
   <!-- <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d20359.865015252086!2d74.2643594!3d50.3668691!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x425b85d10695d463%3A0x4023232bcce0c4f6!2zMTAwMDAwINCj0LzRg9GC0LrQtdGA!5e0!3m2!1sru!2skz!4v1759151057341!5m2!1sru!2skz" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>-->
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
