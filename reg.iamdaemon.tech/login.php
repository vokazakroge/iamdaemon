<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header('Location: https://reg.iamdaemon.tech/dashboard');
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
        $stmt = $db->prepare('SELECT id, password_hash, status FROM users WHERE username = :u');
        $stmt->bindValue(':u', $username, SQLITE3_TEXT);
        $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'banned') {
                $error = 'Ваш аккаунт заблокирован';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                header('Location: https://reg.iamdaemon.tech/dashboard');
                exit;
            }
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --card: #12121a;
            --primary: #8b5cf6;
            --primary-hover: #7c3aed;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --border: #2a2a3a;
            --error: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 400px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 24px;
            color: var(--text);
        }

        .subtitle {
            color: var(--muted);
            margin-bottom: 32px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: var(--muted);
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(139, 92, 246, 0.05);
            border: 2px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(139, 92, 246, 0.1);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1);
        }

        input::placeholder {
            color: var(--muted);
        }

        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }

        button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
        }

        button:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .link:hover {
            color: #a78bfa;
            text-decoration: underline;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 24px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--text);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">DAEMON</div>
        <h1>Вход в систему</h1>
        <p class="subtitle">Введите свои данные для входа</p>

        <?php if ($error): ?>
            <div class="error">❌ <?=htmlspecialchars($error)?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Логин</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="your_username" 
                    required 
                    autocomplete="off"
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <button type="submit">Войти</button>
        </form>

        <a href="https://reg.iamdaemon.tech" class="link">
            Нет аккаунта? Зарегистрироваться
        </a>

        <a href="https://iamdaemon.tech" class="back-link">
            ← На главную
        </a>
    </div>
</body>
</html>