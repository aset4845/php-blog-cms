<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // 🔒 без входа сюда нельзя

// Добавление кнопки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    if ($title !== '') {
        $stmt = $pdo->prepare("INSERT INTO header_buttons (title) VALUES (?)");
        $stmt->execute([$title]);
    }
    header('Location: header_buttons.php');
    exit;
}

// Удаление кнопки
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM header_buttons WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header('Location: header_buttons.php');
    exit;
}

// Получаем список кнопок
$buttons = $pdo->query("SELECT * FROM header_buttons ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление кнопками хедера</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: #f5f6fa;
            color: #333;
            padding: 30px;
        }

        h1, h2 { margin-bottom: 20px; }

        form {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        form input { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; }
        form button {
            background: #4CAF50;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.2s;
        }
        form button:hover { background: #45a049; }

        .button-list {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .button-item {
            background: linear-gradient(135deg,#4CAF50,#66BB6A);
            color: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-width: 250px;
            cursor: grab;
            transition: all 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        }

        .button-item.dragging {
            opacity: 0.5;
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .button-item .btn-actions {
            display: flex;
            gap: 8px;
        }

        .btn-actions a {
            text-decoration: none;
            font-size: 0.85em;
            padding: 6px 10px;
            border-radius: 6px;
            color: #fff;
            transition: 0.2s;
        }

        .btn-actions a.edit {
            background: #2196F3;
        }
        .btn-actions a.delete {
            background: #f44336;
        }

        .btn-actions a.edit:hover { background: #1976D2; }
        .btn-actions a.delete:hover { background: #d32f2f; }

        .button-list li.placeholder {
            background: #a5d6a7;
            border: 2px dashed #fff;
            min-width: 200px;
            height: 40px;
            border-radius: 8px;
        }

        a.back-link {
            display: inline-block;
            margin-top: 30px;
            color: #4CAF50;
            text-decoration: none;
            transition: 0.2s;
        }
        a.back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h1>Кнопки хедера</h1>

<form method="POST">
    <input type="text" name="title" placeholder="Название кнопки" required>
    <button type="submit">Добавить кнопку</button>
</form>

<h2>Существующие кнопки</h2>
<ul class="button-list" id="sortableButtons">
    <?php foreach ($buttons as $btn): ?>
        <li class="button-item" data-id="<?= $btn['id'] ?>" draggable="true">
            <span class="btn-title"><?= htmlspecialchars($btn['title']) ?></span>
            <span class="btn-actions">
                <a href="<?= $config['base_url'] ?>public/section.php?id=<?= $btn['id'] ?>" target="_blank" class="edit">Перейти</a>
                <a href="?delete=<?= $btn['id'] ?>" onclick="return confirm('Удалить?')" class="delete">Удалить</a>
            </span>
        </li>
    <?php endforeach; ?>
</ul>

<a href="dashboard.php" class="back-link">Назад в админку</a>

<script>
const list = document.getElementById('sortableButtons');
let dragged;

list.addEventListener('dragstart', (e) => {
    dragged = e.target;
    e.target.classList.add('dragging');
});

list.addEventListener('dragend', (e) => {
    e.target.classList.remove('dragging');
});

list.addEventListener('dragover', (e) => {
    e.preventDefault();
    const afterElement = getDragAfterElement(list, e.clientY);
    const placeholder = document.querySelector('.placeholder');
    if (!placeholder) {
        const ph = document.createElement('li');
        ph.classList.add('placeholder');
        list.insertBefore(ph, afterElement);
    } else {
        list.insertBefore(placeholder, afterElement);
    }
});

list.addEventListener('drop', () => {
    const placeholder = document.querySelector('.placeholder');
    if (placeholder) {
        list.insertBefore(dragged, placeholder);
        placeholder.remove();
        saveOrder();
    }
});

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.button-item:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// Сохраняем порядок на сервер
function saveOrder() {
    const order = [...list.querySelectorAll('.button-item')].map(li => li.dataset.id);
    fetch('save_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order })
    });
}
</script>

</body>
</html>
