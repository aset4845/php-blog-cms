<?php
// Запускаем сессию только если она ещё не активна
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * --- Аутентификация ---
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function login_user($user_id): void {
    $_SESSION['user_id'] = $user_id;
}

function logout_user(): void {
    // Очищаем все данные сессии
    $_SESSION = [];

    // Удаляем cookie сессии (если есть)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Уничтожаем саму сессию
    session_destroy();
}
function get_base_url(): string {
    // Автоматически определяем адрес проекта
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    return $protocol . "://" . $host . $path;
}

function require_login(): void {
    if (!is_logged_in()) {
        $baseUrl = get_base_url();
        header("Location: {$baseUrl}/login.php");
        exit;
    }
}



/**
 * --- Посты ---
 */
function getAllPosts() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function getPostBySlug($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function slugifyUnique($title) {
    global $pdo;

    // --- 1. Транслитерация казахских символов ---
    $replace = [
        'Ә'=>'A','ә'=>'a','Ө'=>'O','ө'=>'o','Ү'=>'U','ү'=>'u',
        'Қ'=>'Q','қ'=>'q','Ң'=>'N','ң'=>'n','Ғ'=>'G','ғ'=>'g',
        'Ш'=>'Sh','ш'=>'sh','Ч'=>'Ch','ч'=>'ch','Ы'=>'Y','ы'=>'y',
        'І'=>'I','і'=>'i','Ж'=>'Zh','ж'=>'zh','Һ'=>'H','һ'=>'h'
    ];
    $text = strtr($title, $replace);

    // --- 2. Преобразование в "slug" ---
    $text = preg_replace('~[^\pL0-9]+~u', '-', $text); // заменяем пробелы и спецсимволы на "-"
    $text = preg_replace('~[^-\w]+~', '', $text);       // оставляем только буквы, цифры и "-"
    $text = trim($text, '-');                            // убираем лишние "-"
    $text = strtolower($text);                           // в нижний регистр
    $slug = empty($text) ? 'n-a' : $text;

    // --- 3. Проверка на уникальность в базе ---
    $originalSlug = $slug;
    $i = 1;
    while (slugExists($slug)) {
        $slug = $originalSlug . '-' . $i;
        $i++;
    }

    return $slug;
}

// --- Вспомогательная функция ---
function slugExists($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetchColumn() > 0;
}

/**
 * --- Комментарии ---
 */
function addComment($postId, $author, $content) {
    global $pdo;
    $stmt = $pdo->prepare(
        "INSERT INTO comments (post_id, author, content, created_at) VALUES (?, ?, ?, NOW())"
    );
    $stmt->execute([$postId, $author, $content]);
}

function getComments($postId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at DESC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * --- Кнопки в хедере ---
 */
function getHeaderButtons() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM header_buttons ORDER BY id ASC");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

function addHeaderButton($title, $url) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO header_buttons (title, url) VALUES (?, ?)");
    $stmt->execute([$title, $url]);
}

function deleteHeaderButton($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM header_buttons WHERE id = ?");
    $stmt->execute([$id]);
}

/**
 * --- Загрузка фото/видео ---
 */
function save_uploaded_file($file, $type, $config) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error code: ' . $file['error']);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_images = ['image/jpeg','image/png','image/gif'];
    $allowed_videos = ['video/mp4','video/webm','video/ogg'];

    if ($type === 'image') {
        if (!in_array($mime, $allowed_images)) throw new Exception('Unsupported image type.');
        if ($file['size'] > $config['upload']['max_image_size']) throw new Exception('Image too large.');
        $dir = $config['upload']['images_dir'];
    } else {
        if (!in_array($mime, $allowed_videos)) throw new Exception('Unsupported video type.');
        if ($file['size'] > $config['upload']['max_video_size']) throw new Exception('Video too large.');
        $dir = $config['upload']['videos_dir'];
    }

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to move uploaded file.');
    }

    if ($type === 'image') {
        create_thumbnail($dest, $dir . '/thumb_' . $name, 400);
    }

    return ['filename' => $name, 'mime' => $mime, 'original' => $file['name']];
}

function create_thumbnail($src, $dst, $max_width = 400) {
    if (!extension_loaded('gd')) return;
    list($w, $h, $type) = getimagesize($src);
    if ($w <= $max_width) {
        copy($src, $dst);
        return;
    }
    $ratio = $h / $w;
    $new_w = $max_width;
    $new_h = round($new_w * $ratio);

    $dst_img = imagecreatetruecolor($new_w, $new_h);
    switch ($type) {
        case IMAGETYPE_JPEG: $src_img = imagecreatefromjpeg($src); break;
        case IMAGETYPE_PNG: $src_img = imagecreatefrompng($src); break;
        case IMAGETYPE_GIF: $src_img = imagecreatefromgif($src); break;
        default: return;
    }
    imagecopyresampled($dst_img, $src_img, 0,0,0,0, $new_w, $new_h, $w, $h);
    imagejpeg($dst_img, $dst, 85);
    imagedestroy($dst_img);
    imagedestroy($src_img);
}
function renderContent($content, $media) {
    $result = $content;

    // --- локальные картинки ---
    $i = 0;
    foreach ($media as $m) {
        if ($m['type'] === 'image') {
            // проверяем есть ли "right"
            $class = (strpos($result, "[img{$i} right]") !== false) ? 'media-inline right' : 'media-inline left';
            $replace = '<figure class="' . $class . '"><img src="/php-blog/uploads/images/' .
                       htmlspecialchars($m['filename']) . '" alt=""></figure>';
            $result = str_replace(["[img{$i}]", "[img{$i} right]"], $replace, $result);
            $i++;
        }
    }

    // --- локальные видео ---
    $i = 0;
    foreach ($media as $m) {
        if ($m['type'] === 'video') {
            $class = (strpos($result, "[video{$i} right]") !== false) ? 'media-inline right' : 'media-inline left';
            $replace = '<figure class="' . $class . '"><video controls><source src="/php-blog/uploads/videos/' .
                       htmlspecialchars($m['filename']) . '" type="' . htmlspecialchars($m['mime']) . '"></video></figure>';
            $result = str_replace(["[video{$i}]", "[video{$i} right]"], $replace, $result);
            $i++;
        }
    }

    // --- внешние медиа ---
    // YouTube
    $result = preg_replace_callback('/\[youtube=(.*?)( right)?\]/i', function($m) {
        $url = trim($m[1]);
        $align = isset($m[2]) && trim($m[2]) === 'right' ? 'right' : 'left';
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]+)~', $url, $id)) {
            return '<figure class="media-inline ' . $align . '"><iframe width="400" height="225" src="https://www.youtube.com/embed/' .
                   htmlspecialchars($id[1]) . '" frameborder="0" allowfullscreen></iframe></figure>';
        }
        return '';
    }, $result);

    // Vimeo
    $result = preg_replace_callback('/\[vimeo=(.*?)( right)?\]/i', function($m) {
        $url = trim($m[1]);
        $align = isset($m[2]) && trim($m[2]) === 'right' ? 'right' : 'left';
        if (preg_match('~vimeo\.com/(\d+)~', $url, $id)) {
            return '<figure class="media-inline ' . $align . '"><iframe src="https://player.vimeo.com/video/' .
                   htmlspecialchars($id[1]) . '" width="400" height="225" frameborder="0" allowfullscreen></iframe></figure>';
        }
        return '';
    }, $result);

    // Внешние изображения
    $result = preg_replace_callback('/\[img=(.*?)( right)?\]/i', function($m) {
        $url = trim($m[1]);
        $align = isset($m[2]) && trim($m[2]) === 'right' ? 'right' : 'left';
        return '<figure class="media-inline ' . $align . '"><img src="' . htmlspecialchars($url) . '" alt=""></figure>';
    }, $result);

    // Внешние видео (mp4)
    $result = preg_replace_callback('/\[video=(.*?)( right)?\]/i', function($m) {
        $url = trim($m[1]);
        $align = isset($m[2]) && trim($m[2]) === 'right' ? 'right' : 'left';
        return '<figure class="media-inline ' . $align . '"><video controls><source src="' .
               htmlspecialchars($url) . '" type="video/mp4"></video></figure>';
    }, $result);

    // Переводим переносы строк в <p>, но не оборачиваем медиа
    $parts = preg_split('/\n+/', $result);
    $html = '';
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        if (strpos($p, 'media-inline') !== false) {
            $html .= $p;
        } else {
            $html .= '<p>' . $p . '</p>';
        }
    }

    return $html;
}
