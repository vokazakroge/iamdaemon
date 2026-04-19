<?php
// === НАСТРОЙКИ СИСТЕМЫ ===
ini_set('display_errors', 0);
error_reporting(E_ALL);

// === ГЛОБАЛЬНЫЕ СЕССИИ ===
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
        
        // Обновленная структура таблицы (добавлены code и verified)
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
    }
    return $db;
}

// === ФУНКЦИИ ===
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
    return isLoggedIn() && $_SESSION['username'] === 'vokazakroge'; // Замени на свой
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: https://reg.iamdaemon.tech/login');
        exit;
    }
}
?>