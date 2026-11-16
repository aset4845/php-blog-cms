<?php 
// подключаем конфиг и init только если они ещё не подключены
if (!isset($pdo)) {
    $config = require __DIR__ . '/../includes/config.php';
    require __DIR__ . '/../includes/init.php';
}

// Загружаем кнопки меню
$buttons = $pdo->query("SELECT * FROM header_buttons ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<footer>
    <div class="footer-container">
        <nav class="footer-nav">
            <?php foreach ($buttons as $b): ?>
                <a href="<?= $config['base_url'] ?>public/section.php?id=<?= $b['id'] ?>">
                    <?= htmlspecialchars($b['title']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <hr>
        <p>&copy; <?= date('Y') ?> Үміткер. <!--Все права защищены.--></p>
    </div>
</footer>

<style>
/* --- Липкий футер --- */
html, body {
    height: 100%;
    margin: 0;
}
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
footer {
    background: #45a834ff;
    color: #eee;
    width: 98%;
    margin: 50px auto 0; /* верхний отступ можно оставить, нижний убран */
    border-radius: 15px;
    padding: 30px 20px;
    border-top: 2px solid #fff;
    margin-top: auto; /* вот ключ — футер прижимается к низу */
}
.footer-container {
    max-width: 1000px;
    margin: 0 auto;
    text-align: center;
}

.footer-nav {
    display: grid;
    grid-template-columns: repeat(2, auto);
    gap: 12px 200px;
    justify-content: center;
    margin-bottom: 20px;
}

.footer-nav a {
    text-decoration: none;
    color: #eee;
    background: rgba(255,255,255,0.08);
    padding: 10px 15px;
    border-radius: 6px;
    transition: all 0.3s ease;
    font-size: 15px;
}

.footer-nav a:hover {
    background: rgba(255,255,255,0.25);
    color: #fff;
}

footer hr {
    border: 0;
    border-top: 1px solid rgba(255, 255, 255, 0.42);
    margin: 20px auto;
    width: 80%;
}

footer p {
    font-size: 14px;
    color: #fff;
}
</style>