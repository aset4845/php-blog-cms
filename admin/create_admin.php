<?php 
$config = require __DIR__ . '/../includes/config.php'; // сначала config
require __DIR__ . '/../includes/init.php';     

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        die('<p class="error">Введите логин и пароль.</p>');
    }

    // Проверяем, есть ли пользователь
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        die("<p class='error'>Пользователь '$username' уже существует.</p>");
    }

    // Создаём нового пользователя
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (:u, :p)');
    $stmt->execute([':u' => $username, ':p' => $hash]);

    echo "<p class='success'>Админ '$username' успешно создан. Удалите этот файл после использования.</p>";
    exit;
}
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Create Admin</title>
    <style>
        /* Базовые стили страницы */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #6B73FF, #000DFF);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Контейнер формы */
        .form-container {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
        }

        h1 {
            margin-bottom: 25px;
            color: #333;
            font-size: 24px;
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 10px;
            color: #555;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        input:focus {
            border-color: #6B73FF;
            box-shadow: 0 0 5px rgba(107,115,255,0.5);
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #6B73FF;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #000DFF;
        }

        .error {
            color: #FF4C4C;
            margin-bottom: 15px;
        }

        .success {
            color: #28A745;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Создать администратора</h1>
        <form method="post">
            <label>Username</label>
            <input name="username" placeholder="Введите логин">

            <label>Password</label>
            <input name="password" type="password" placeholder="Введите пароль">

            <button type="submit">Создать</button>
        </form>
    </div>
</body>
</html>
