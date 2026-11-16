<?php 
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя

function cleanFileName($name) {
    return preg_replace('/[^A-Za-z0-9\-_\.]/', '_', $name);
}

// --- Пути к папкам медиа ---
$baseUploadDir = __DIR__ . '/../uploads';
$imageDir = $baseUploadDir . '/images';
$videoDir = $baseUploadDir . '/videos';

// --- Создаём папки, если их нет ---
foreach ([$imageDir, $videoDir] as $dir) {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            die("Не удалось создать папку: $dir");
        }
    }
}

// --- Функция для уникального слага ---
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
    $slug = generateUniqueSlug($pdo, $title);
    $headerButtonId = !empty($_POST['header_button_id']) ? (int)$_POST['header_button_id'] : null;

    // --- Создаём пост ---
    $stmt = $pdo->prepare(
        "INSERT INTO posts (slug, title, content, author, header_button_id, created_at) 
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$slug, $title, $content, $author, $headerButtonId]);
    $postId = $pdo->lastInsertId();

    // --- Сохраняем изображения ---
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $i => $name) {
            if (empty($_FILES['images']['tmp_name'][$i])) continue;
            $filename = uniqid('img_') . '_' . cleanFileName(basename($name));
            $dest = $imageDir . '/' . $filename;
            if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $dest)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO media (post_id, filename, original_name, mime, type, created_at) 
                     VALUES (?, ?, ?, ?, 'image', NOW())"
                );
                $stmt->execute([$postId, $filename, $name, $_FILES['images']['type'][$i]]);
                $uploadedImages[] = $filename;
            }
        }
    }

    // --- Сохраняем видео ---
    if (!empty($_FILES['videos']['name'][0])) {
        foreach ($_FILES['videos']['name'] as $i => $name) {
            if (empty($_FILES['videos']['tmp_name'][$i])) continue;
            $filename = uniqid('vid_') . '_' . cleanFileName(basename($name));
            $dest = $videoDir . '/' . $filename;
            if (move_uploaded_file($_FILES['videos']['tmp_name'][$i], $dest)) {
                $stmt = $pdo->prepare(
                    "INSERT INTO media (post_id, filename, original_name, mime, type, created_at) 
                     VALUES (?, ?, ?, ?, 'video', NOW())"
                );
                $stmt->execute([$postId, $filename, $name, $_FILES['videos']['type'][$i]]);
                $uploadedVideos[] = $filename;
            }
        }
    }

    header('Location: new_post.php?success=1');
    exit;
}

// --- Получаем разделы ---
$sections = $pdo->query("SELECT id, title FROM header_buttons ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Создать новый пост</title>
<style>
:root {
    --bg: #f5f6fa;
    --card: #fff;
    --text: #333;
    --accent: #4CAF50;
    --hover: #45a049;
    --border: #ddd;
    --shadow: rgba(0,0,0,0.12);
}

* { box-sizing: border-box; margin:0; padding:0; }
body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: var(--bg);
    color: var(--text);
    padding: 20px;
    line-height: 1.6;
}

h1 { text-align:center; margin-bottom:30px; font-size:2em; color: var(--accent); }

form {
    background: var(--card);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 6px 20px var(--shadow);
    max-width: 900px;
    margin: 0 auto 40px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

label { font-weight: 500; margin-bottom: 5px; display:block; }
input[type=text], select, textarea {
    width: 100%;
    padding: 14px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-family: inherit;
    font-size: 16px;
    transition: all 0.3s;
}
input[type=text]:focus, select:focus, textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 8px rgba(76,175,80,0.3);
    outline: none;
}
textarea { height: 200px; resize: vertical; }

button {
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 14px 30px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s, transform 0.2s;
    align-self: flex-start;
}
button:hover {
    background: var(--hover);
    transform: translateY(-2px);
}

.media-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 10px;
    padding: 12px;
    background: #f0f0f0;
    border-radius: 10px;
    border: 1px dashed var(--border);
}

.media-preview img,
.media-preview video {
    cursor: pointer;
    width: 150px;
    height: 100px;
    object-fit: cover;
    border: 1px solid var(--border);
    border-radius: 8px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.media-preview img:hover,
.media-preview video:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 12px var(--shadow);
}

h3 {
    text-align:center;
    margin-bottom:15px;
    color: var(--accent);
    font-size:1.5em;
}

@media (max-width: 700px){
    form { padding: 20px; }
    .media-preview img, .media-preview video { width: 120px; height: 80px; }
}
a.back-link {
    
           background: var(--accent);
    color: #fff;
    border: none;
    padding: 14px 30px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 16px;
    transition: background 0.3s, transform 0.2s;
    align-self: flex-start;
        }
        a.back-link:hover {  background: var(--hover);
    transform: translateY(-2px); }
</style>

</head>
<body>
<h1>Создать новый пост</h1>
<a href="dashboard.php" class="back-link">Назад в админку</a>

<form method="POST" enctype="multipart/form-data">
    <p><label>Заголовок:<br><input type="text" name="title" required></label></p>
    <p><label>Автор:<br><input type="text" name="author" value="Неизвестный автор"></label></p>
    <p><label>Раздел:<br>
        <select name="header_button_id">
            <?php foreach($sections as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>

    <p><label>Контент:<br>
        <textarea name="content" id="content" rows="8"></textarea>
    </label></p>

    <p><label>Локальные изображения:<br>
        <input type="file" name="images[]" multiple accept="image/*">
    </label></p>
    <p><label>Локальные видео:<br>
        <input type="file" name="videos[]" multiple accept="video/*">
    </label></p>

    <p><label>Внешние медиа (ссылки через запятую):<br>
        <input type="text" id="external_media_input" placeholder="https://youtube.com/... , https://site.com/image.jpg" style="width:100%">
        <button type="button" onclick="insertExternal()">Вставить в текст</button>
    </label></p>

    <p><button type="submit">Создать пост</button></p>
</form>

<h3>Клик по медиа для вставки в текст</h3>
<div class="media-preview" id="mediaPreview"></div>

<script>
function insertShortcode(shortcode){
    const textarea = document.getElementById('content');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    textarea.value = textarea.value.slice(0, start) + shortcode + "\n" + textarea.value.slice(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + shortcode.length + 1;
}

function insertExternal(){
    const input = document.getElementById('external_media_input');
    const urls = input.value.split(',');
    urls.forEach(url=>{
        url = url.trim();
        if(!url) return;
        let shortcode = '';
        if(url.includes('youtube.com') || url.includes('youtu.be')) shortcode = '[youtube='+url+']';
        else if(url.includes('vimeo.com')) shortcode = '[vimeo='+url+']';
        else if(url.match(/\.mp4$/i)) shortcode = '[video='+url+']';
        else if(url.match(/\.(jpg|jpeg|png|gif|webp)$/i)) shortcode = '[img='+url+']';
        else { alert('Не удалось определить тип: ' + url); return; }
        insertShortcode(shortcode);
    });
    input.value='';
}

document.addEventListener('DOMContentLoaded', () => {
    const imagesInput = document.querySelector('input[name="images[]"]');
    const videosInput = document.querySelector('input[name="videos[]"]');
    const preview = document.getElementById('mediaPreview');

    // Счётчики медиа: продолжают нумерацию при новых загрузках
    let imgCounter = 0;
    let vidCounter = 0;

    function createMediaPreview(file, type) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const url = e.target.result;
            let el, shortcode;

            if(type === 'image'){
                shortcode = `[img${imgCounter}]`;
                el = document.createElement('img');
                el.src = url;
                imgCounter++;
            } else {
                shortcode = `[video${vidCounter}]`;
                el = document.createElement('video');
                el.src = url;
                el.controls = true;
                el.width = 150;
                vidCounter++;
            }

            el.style.margin = '5px';
            el.style.cursor = 'pointer';
            el.title = 'Клик для вставки в текст';
            el.addEventListener('click', () => insertShortcode(shortcode));
            preview.appendChild(el);
        }
        reader.readAsDataURL(file);
    }

    function handleFiles(input, type){
        Array.from(input.files).forEach(file => createMediaPreview(file, type));
        // После добавления новых файлов очищаем input, чтобы можно было выбрать те же файлы ещё раз
        input.value = '';
    }

    imagesInput.addEventListener('change', ()=> handleFiles(imagesInput, 'image'));
    videosInput.addEventListener('change', ()=> handleFiles(videosInput, 'video'));
});
</script>
</body>
</html>