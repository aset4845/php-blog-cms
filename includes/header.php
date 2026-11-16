<?php 
// подключаем конфиг и init только если они ещё не подключены
if (!isset($pdo)) {
    $config = require __DIR__ . '/../includes/config.php';
    require __DIR__ . '/../includes/init.php';
}
?>

<header>
    <h1><a href="index.php?section=16">Үміткер ауылы</a></h1>
    <nav>
        <!-- <a href="index.php" class="<?= empty($_GET['section']) ? 'active' : '' ?>">Все посты</a> -->

        
        <?php foreach ($headerButtons as $b): ?>
            <a href="index.php?section=<?= $b['id'] ?>"
               
            class="<?= (isset($header_button_id) && $header_button_id == $b['id']) ? 'active' : '' ?>">
                <?= htmlspecialchars($b['title']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>
<style>
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

</style>