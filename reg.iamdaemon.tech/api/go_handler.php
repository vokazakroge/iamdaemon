<?php
require_once __DIR__ . '/../config.php';

// 1. Получаем данные из Nginx или парсим вручную
$shortCode = $_SERVER['SHORT_CODE'] ?? '';
$subdomain = $_SERVER['SUBDOMAIN'] ?? '';

if (!$shortCode || !$subdomain) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = explode('.', $host)[0];
    
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/go/([a-zA-Z0-9_-]+)#', $uri, $m)) {
        $shortCode = $m[1];
    }
}

if (!$shortCode || !$subdomain) {
    http_response_code(400);
    exit("Bad Request");
}

$db = getDb();

// 2. Ищем ссылку
$stmt = $db->prepare('SELECT su.long_url, u.username FROM short_urls su JOIN users u ON su.user_id = u.id WHERE su.short_code = :code AND u.username = :user');
$stmt->bindValue(':code', $shortCode, SQLITE3_TEXT);
$stmt->bindValue(':user', $subdomain, SQLITE3_TEXT);
$row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($row) {
    // 3. Считаем клик
    $db->exec("UPDATE short_urls SET clicks = clicks + 1 WHERE short_code = '$shortCode'");
    
    // 4. Редирект
    header("Location: " . $row['long_url'], true, 302);
    exit;
}

// 5. Если не найдено
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>404</title>
<style>body{background:#0a0a0f;color:#e2e8f0;font-family:system-ui;display:flex;justify-content:center;align-items:center;height:100vh;text-align:center}h1{color:#8b5cf6;font-size:3rem;margin:0}p{color:#94a3b8}a{color:#8b5cf6}</style>
</head>
<body><div><h1>404</h1><p>Ссылка не найдена</p><a href="https://iamdaemon.tech">На главную</a></div></body></html>