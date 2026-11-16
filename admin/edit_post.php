<?php 
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя

$id = $_GET['id'] ?? null;
if (!$id) die("Не указан ID поста.");

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) die("Пост не найден.");

$sections = $pdo->query("SELECT id, title FROM header_buttons ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM media WHERE post_id = ?");
$stmt->execute([$id]);
$mediaFiles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Редактировать пост</title>
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
body { font-family:"Segoe UI", Tahoma, sans-serif; background:var(--bg); color:var(--text); padding:20px; line-height:1.6; }
h1 { text-align:center; margin-bottom:30px; color:var(--accent); }
form { background:var(--card); padding:30px; border-radius:15px; box-shadow:0 6px 20px var(--shadow); max-width:900px; margin:0 auto 40px; display:flex; flex-direction:column; gap:20px; }
label { font-weight:500; margin-bottom:5px; display:block; }
input[type=text], select, textarea, input[type=file] { width:100%; padding:14px; border:1px solid var(--border); border-radius:10px; font-family:inherit; font-size:16px; transition: all 0.3s; }
input[type=text]:focus, select:focus, textarea:focus { border-color:var(--accent); box-shadow:0 0 8px rgba(76,175,80,0.3); outline:none; }
textarea { height:200px; resize: vertical; }
button { background:var(--accent); color:#fff; border:none; padding:14px 30px; border-radius:10px; cursor:pointer; font-size:16px; transition: background 0.3s, transform 0.2s; align-self:flex-start; }
button:hover { background:var(--hover); transform:translateY(-2px); }

.media-preview { display:flex; flex-wrap:wrap; gap:12px; margin-top:10px; padding:12px; background:#f0f0f0; border-radius:10px; border:1px dashed var(--border); }
.media-preview img, .media-preview video { cursor:pointer; width:150px; height:100px; object-fit:cover; border:1px solid var(--border); border-radius:8px; transition:transform 0.2s, box-shadow 0.2s; }
.media-preview img:hover, .media-preview video:hover { transform:scale(1.08); box-shadow:0 4px 12px var(--shadow); }

h3 { text-align:center; margin-bottom:15px; color:var(--accent); font-size:1.5em; }
</style>
</head>
<body>
<h1>Редактировать пост</h1>

<form method="POST" action="save_post.php" enctype="multipart/form-data">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="id" value="<?= $post['id'] ?>">

    <label>Slug:<input type="text" name="slug" value="<?= htmlspecialchars($post['slug']) ?>"></label>
    <label>Заголовок:<input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>"></label>
    <label>Автор:<input type="text" name="author" value="<?= htmlspecialchars($post['author']) ?>"></label>
    <label>Раздел:
        <select name="header_button_id">
            <?php foreach($sections as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $post['header_button_id']==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Контент:<textarea id="content" name="content"><?= htmlspecialchars($post['content']) ?></textarea></label>

    <h3>Текущие медиа (клик для вставки шорткода)</h3>
    <div class="media-preview" id="mediaPreview">
        <?php 
        $imgCount = 0;
        $videoCount = 0;
        foreach($mediaFiles as $media): 
            if($media['type']=='image'): ?>
                <img src="../uploads/images/<?= htmlspecialchars($media['filename']) ?>" title="[img<?= $imgCount ?>]">
            <?php $imgCount++; 
            else: ?>
                <video controls src="../uploads/videos/<?= htmlspecialchars($media['filename']) ?>" title="[video<?= $videoCount ?>]"></video>
            <?php $videoCount++; 
            endif; 
        endforeach; 
        ?>
    </div>

    <label>Загрузить новые изображения:<input type="file" name="images[]" multiple accept="image/*"></label>
    <label>Загрузить новые видео:<input type="file" name="videos[]" multiple accept="video/*"></label>

    <label>Внешние медиа (ссылки через запятую):
        <input type="text" id="external_media_input" placeholder="https://youtube.com/... , https://site.com/image.jpg" style="width:100%">
        <button type="button" onclick="insertExternal()">Вставить в текст</button>
    </label>

    <p><button type="submit">Сохранить изменения</button></p>
</form>
<script>
function insertShortcode(shortcode){
    let textarea = document.getElementById('content');
    let start = textarea.selectionStart;
    let end = textarea.selectionEnd;
    textarea.value = textarea.value.slice(0,start)+shortcode+"\n"+textarea.value.slice(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + shortcode.length + 1;
}

function insertExternal(){
    let input = document.getElementById('external_media_input');
    let urls = input.value.split(',');
    urls.forEach(url=>{
        url = url.trim();
        if(!url) return;
        let shortcode = '';
        if(url.includes('youtube.com') || url.includes('youtu.be')) shortcode = '[youtube='+url+']';
        else if(url.includes('vimeo.com')) shortcode = '[vimeo='+url+']';
        else if(url.match(/\.mp4$/i)) shortcode = '[video='+url+']';
        else if(url.match(/\.(jpg|jpeg|png|gif|webp)$/i)) shortcode = '[img='+url+']';
        else { alert('Не удалось определить тип: '+url); return; }
        insertShortcode(shortcode);
    });
    input.value='';
}

// --- Кликабельность существующих медиа ---
document.querySelectorAll('#mediaPreview img, #mediaPreview video').forEach(el=>{
    el.addEventListener('click',()=> insertShortcode(el.title));
});

// --- Превью новых файлов с нумерацией ---
const mediaPreview = document.getElementById('mediaPreview');
let newImgCount = <?= $imgCount ?>;
let newVideoCount = <?= $videoCount ?>;

function addNewMediaPreview(file){
    const reader = new FileReader();
    reader.onload = e => {
        let el, shortcode;
        if(file.type.startsWith('image')){
            el = document.createElement('img');
            el.src = e.target.result;
            shortcode = `[img${newImgCount}]`;
            newImgCount++;
        } else if(file.type.startsWith('video')){
            el = document.createElement('video');
            el.src = e.target.result;
            el.controls = true;
            shortcode = `[video${newVideoCount}]`;
            newVideoCount++;
        } else return;
        el.title = shortcode;
        el.addEventListener('click', ()=> insertShortcode(shortcode));
        mediaPreview.appendChild(el);
    };
    reader.readAsDataURL(file);
}

document.querySelector('input[name="images[]"]').addEventListener('change', e=>{
    Array.from(e.target.files).forEach(addNewMediaPreview);
});
document.querySelector('input[name="videos[]"]').addEventListener('change', e=>{
    Array.from(e.target.files).forEach(addNewMediaPreview);
});
</script>

</body>
</html>
