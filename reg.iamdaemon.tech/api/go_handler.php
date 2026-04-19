<?php
require_once __DIR__ . '/../config.php';

// Получаем данные от Nginx
$shortCode = $_SERVER['SHORT_CODE'] ?? '';
$subdomain = $_SERVER['SUBDOMAIN'] ?? '';

// Если переменные не переданы, пробуем из URL
if (!$shortCode) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/go/([a-zA-Z0-9]+)#', $requestUri, $matches)) {
        $shortCode = $matches[1];
    }
}

if (!$subdomain) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = str_replace('.iamdaemon.tech', '', $host);
}

if (!$shortCode || !$subdomain) {
    http_response_code(400);
    echo "<h1>400 Bad Request</h1><p>Invalid URL</p>";
    exit;
}

$db = getDb();

// Ищем ссылку
$stmt = $db->prepare('
    SELECT su.long_url, u.username 
    FROM short_urls su 
    JOIN users u ON su.user_id = u.id 
    WHERE su.short_code = :code AND u.username = :user
');
$stmt->bindValue(':code', $shortCode, SQLITE3_TEXT);
$stmt->bindValue(':user', $subdomain, SQLITE3_TEXT);
$url = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($url) {
    // Увеличиваем счетчик
    $stmt = $db->prepare('UPDATE short_urls SET clicks = clicks + 1 WHERE short_code = :code');
    $stmt->bindValue(':code', $shortCode, SQLITE3_TEXT);
    $stmt->execute();

    // Редирект
    header('Location: ' . $url['long_url'], true, 302);
    exit;
} else {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Ссылка не найдена | DAEMON</title>
        <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
        <style>
            :root { --bg: #0a0a0f; --text: #e2e8f0; --muted: #94a3b8; --primary: #8b5cf6; }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', sans-serif;
                background: var(--bg);
                color: var(--text);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 20px;
            }
            h1 { font-family: 'Orbitron', sans-serif; font-size: 4rem; color: var(--primary); margin-bottom: 20px; }
            p { font-size: 1.2rem; color: var(--muted); margin-bottom: 30px; }
            a { color: var(--primary); text-decoration: none; font-weight: 600; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h1>404</h1>
        <p>Ссылка не найдена или была удалена</p>
        <a href="https://iamdaemon.tech">← На главную DAEMON</a>
    </body>
    </html>
    <?php
    exit;
}
?>