<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя




// Получаем все посты
$posts = getAllPosts();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админка - Все посты</title>
    <style>
:root {
    --bg: #f5f6fa;
    --card: #fff;
    --text: #333;
    --accent: #4CAF50;
    --hover: #45a049;
    --border: #ddd;
    --shadow: rgba(0,0,0,0.1);
}

body {
    font-family: "Segoe UI", Tahoma, sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
    padding: 20px;
}

h1 {
    text-align: center;
    color: var(--accent);
    margin-bottom: 20px;
}

a.button {
    display: inline-block;
    background: var(--accent);
    color: #fff;
    padding: 8px 16px;
    margin: 5px;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.3s, transform 0.2s;
}

a.button:hover {
    background: var(--hover);
    transform: translateY(-2px);
}

table {
    width: 100%;
    border-collapse: collapse;
    background: var(--card);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px var(--shadow);
}

thead {
    background: var(--accent);
    color: #fff;
}

thead th {
    padding: 12px;
    text-align: left;
}

tbody tr {
    transition: background 0.3s;
}

tbody tr:nth-child(even) {
    background: #f9f9f9;
}

tbody tr:hover {
    background: #e6f7ff;
}

tbody td {
    padding: 10px 12px;
}

.actions a {
    color: var(--accent);
    text-decoration: none;
    margin-right: 10px;
}

.actions a:hover {
    text-decoration: underline;
}
</style>

</head>
<body>
    <div style="text-align:center; margin-bottom:20px;">
 
    <a href="logout.php" class="button">Выйти</a>
    <a href="header_buttons.php" class="button">Header</a>
    <a href="new_post.php" class="button">Создать новый пост</a>
</div>

    <h1>Все посты</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Заголовок</th>
                <th>Автор</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
                <tr>
                    <td><?= $post['id'] ?></td>
                    <td><?= htmlspecialchars($post['title']) ?></td>
                    <td><?= htmlspecialchars($post['author'] ?? 'Неизвестный автор') ?></td>
                    <td><?= $post['created_at'] ?></td>
                    <td class="actions">
                        <a href="edit_post.php?id=<?= $post['id'] ?>">Редактировать</a>
                        <a href="delete_post.php?id=<?= $post['id'] ?>" onclick="return confirm('Удалить пост?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
