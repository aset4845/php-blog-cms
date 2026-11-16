<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

// Если сессия истекла
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $error = '⏳ Сессия истекла, пожалуйста, войдите заново';
}

// Если уже вошли → сразу на дашборд
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

// обработка POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            login_user($user['id']); // единый вход
            $_SESSION['username'] = $user['username']; // сохраняем имя
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    } else {
        $error = 'Введите логин и пароль';
    }
}
?>

<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Admin Login</title>
<style>
/* Стили те же, что и раньше */
* { box-sizing: border-box; margin: 0; padding: 0; font-family: "Segoe UI", Tahoma, sans-serif; }
body.admin-login { height: 100vh; display: flex; justify-content: center; align-items: center; background: linear-gradient(135deg,#6a11cb,#2575fc); color:#333; }
.login-box { background:#fff; padding:40px 30px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.2); width:100%; max-width:400px; text-align:center; }
.login-box h2 { margin-bottom:25px; color:#333; font-size:1.8em; }
.login-box .error { background:#f44336; color:#fff; padding:10px 15px; margin-bottom:20px; border-radius:6px; font-size:0.95em; animation:fadein 0.3s ease-in; }
@keyframes fadein { from{opacity:0;transform:translateY(-10px);} to{opacity:1;transform:translateY(0);} }
.login-box form { display:flex; flex-direction:column; gap:15px; }
.login-box label { text-align:left; font-size:0.95em; color:#555; }
.login-box input { width:100%; padding:10px 12px; border:1px solid #ccc; border-radius:6px; font-size:1em; transition:0.2s; }
.login-box input:focus { border-color:#2575fc; box-shadow:0 0 5px rgba(37,117,252,0.5); outline:none; }
.login-box button { padding:12px; background:#2575fc; color:#fff; border:none; border-radius:6px; font-size:1em; cursor:pointer; transition:0.3s; }
.login-box button:hover { background:#1a5fcc; }
.login-box button:active { transform:scale(0.98); }
@media(max-width:500px){.login-box{padding:30px 20px;}}
</style>
</head>
<body class="admin-login">
<div class="login-box">
    <h2>Вход в админку</h2>
    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Логин
            <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <button type="submit">Войти</button>
    </form>
</div>
</body>
</html>
