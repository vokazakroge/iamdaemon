<?php
// Этот файл будет перенаправлять с username.iamdaemon.tech/go/CODE
require_once __DIR__ . '/../config.php';

$path = $_GET['path'] ?? '';
$parts = explode('/', trim($path, '/'));

if (count($parts) < 2 || $parts[0] !== 'go') {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

$shortCode = $parts[1];
$username = $_SERVER['HTTP_HOST'];
$username = str_replace('.iamdaemon.tech', '', $username);

$db = getDb();

// Ищем ссылку
$stmt = $db->prepare('
    SELECT su.long_url, u.username 
    FROM short_urls su 
    JOIN users u ON su.user_id = u.id 
    WHERE su.short_code = :code AND u.username = :user
');
$stmt->bindValue(':code', $shortCode, SQLITE3_TEXT);
$stmt->bindValue(':user', $username, SQLITE3_TEXT);
$url = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if ($url) {
    // Увеличиваем счётчик
    $stmt = $db->prepare('UPDATE short_urls SET clicks = clicks + 1 WHERE short_code = :code');
    $stmt->bindValue(':code', $shortCode, SQLITE3_TEXT);
    $stmt->execute();

    // Редирект
    header('Location: ' . $url['long_url'], true, 302);
    exit;
} else {
    http_response_code(404);
    echo "<h1>404 - Ссылка не найдена</h1>";
    echo "<p>Эта короткая ссылка не существует или была удалена.</p>";
    exit;
}
?>