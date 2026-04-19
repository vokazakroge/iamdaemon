<?php
require_once __DIR__ . '/config.php';
// Проверка статуса
$stmt = $db->prepare('SELECT status FROM users WHERE username = :u');
$stmt->bindValue(':u', $username, SQLITE3_TEXT);
$userCheck = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($userCheck && $userCheck['status'] === 'banned') {
    $error = 'Ваш аккаунт заблокирован';
} else {
    // Успешный вход
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $username;
    header('Location: https://' . $username . '.iamdaemon.tech/dashboard');
    exit;
}
// Если уже залогинен -> перекидываем на его поддомен
if (isLoggedIn()) {
    header('Location: https://' . $_SESSION['username'] . '.iamdaemon.tech');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Введите логин и пароль';
    } else {
        $db = getDb();
        $stmt = $db->prepare('SELECT id, password_hash FROM users WHERE username = :u');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;

            // Успех -> редирект на личный сайт
            header('Location: https://' . $username . '.iamdaemon.tech');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN — DAEMON</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --card: #12121a;
            --primary: #8b5cf6;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #2a2a3a;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 380px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 30px;
            text-align: center;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 12px;
            background: #0c0c12;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
        }

        button {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            opacity: 0.9;
        }

        .error {
            color: #ef4444;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .link {
            margin-top: 15px;
            display: block;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>LOGIN</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="логин" required autocomplete="off">
            <input type="password" name="password" placeholder="пароль" required>
            <button type="submit">войти</button>
        </form>
        <a href="https://reg.iamdaemon.tech" class="link">нет аккаунта? регистрация</a>
    </div>
</body>

</html>