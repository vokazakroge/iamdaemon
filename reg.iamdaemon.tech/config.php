<?php
// === НАСТРОЙКИ СИСТЕМЫ ===
ini_set('display_errors', 0);
error_reporting(E_ALL);

// === ГЛОБАЛЬНЫЕ СЕССИИ (КУКИ) ===
ini_set('session.cookie_domain', '.iamdaemon.tech');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === БАЗА ДАННЫХ ===
define('DB_PATH', __DIR__ . '/data/users.db');

function getDb() {
    static $db = null;
    if ($db === null) {
        $db = new SQLite3(DB_PATH, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $db->busyTimeout(5000);
        
        // Создаём таблицу, если нет
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            code TEXT DEFAULT NULL,
            verified INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status TEXT DEFAULT 'active'
        )");

        // Авто-миграция (добавляет колонки, если их нет)
        $result = $db->query("PRAGMA table_info(users)");
        $colNames = [];
        while ($col = $result->fetchArray(SQLITE3_ASSOC)) {
            $colNames[] = $col['name'];
        }
        if (!in_array('code', $colNames)) $db->exec("ALTER TABLE users ADD COLUMN code TEXT DEFAULT NULL");
        if (!in_array('verified', $colNames)) $db->exec("ALTER TABLE users ADD COLUMN verified INTEGER DEFAULT 0");
        if (!in_array('status', $colNames)) $db->exec("ALTER TABLE users ADD COLUMN status TEXT DEFAULT 'active'");
    }
    return $db;
}

// === ФУНКЦИИ АВТОРИЗАЦИИ ===
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: https://reg.iamdaemon.tech/login');
        exit;
    }
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['username'] === 'vokazakroge'; // Твой логин
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: https://reg.iamdaemon.tech/login');
        exit;
    }
}

// === ПРОВЕРКА СТАТУСА ПОЛЬЗОВАТЕЛЯ (БАН) ===
function checkUserStatus() {
    if (!isLoggedIn()) return;

    $db = getDb();
    $stmt = $db->prepare('SELECT status FROM users WHERE username = :u');
    $stmt->bindValue(':u', $_SESSION['username'], SQLITE3_TEXT);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($user && $user['status'] === 'banned') {
        session_destroy();
        header('Location: https://reg.iamdaemon.tech/banned.php');
        exit;
    }
}

// === ШАБЛОН ДЛЯ НОВЫХ ПОДДОМЕНОВ ===
function getTemplateIndex() {
    return '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{username}} — DAEMON</title>
    <style>
        body{font-family:system-ui,sans-serif;background:#0a0a0f;color:#e2e8f0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:20px}
        h1{font-size:2.5rem;margin-bottom:1rem}.p{color:#94a3b8;max-width:500px}
        .badge{background:#8b5cf6;color:#fff;padding:4px 12px;border-radius:20px;font-size:0.85rem}
        .cta{margin-top:2rem}.btn{display:inline-block;padding:12px 24px;background:#8b5cf6;color:#fff;text-decoration:none;border-radius:8px;font-weight:500}
    </style>
</head>
<body>
    <span class="badge">✨ Новый сайт</span>
    <h1>{{username}}.iamdaemon.tech</h1>
    <p class="p">Этот поддомен только что создан. Загрузи файлы через <a href="https://reg.iamdaemon.tech/dashboard" style="color:#8b5cf6">личный кабинет</a>.</p>
    <div class="cta"><a href="https://reg.iamdaemon.tech/dashboard" class="btn">🔐 Открыть дашборд</a></div>
</body>
</html>';
}
?>