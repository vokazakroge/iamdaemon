<?php
require_once __DIR__ . '/config.php';

$username = strtolower(trim($_GET['u'] ?? ''));
if (!isSafeUsername($username)) {
    http_response_code(400);
    exit;
}

$db = getDb();
$stmt = $db->prepare('SELECT avatar FROM users WHERE username = :u');
$stmt->bindValue(':u', $username, SQLITE3_TEXT);
$user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$user || !$user['avatar'] || !isSafeFilename($user['avatar'])) {
    http_response_code(404);
    exit;
}

$path = realpath(__DIR__ . '/avatars/' . $user['avatar']);
$avatarDir = realpath(__DIR__ . '/avatars');

if (!$path || !$avatarDir || strpos($path, $avatarDir) !== 0 || !is_file($path)) {
    http_response_code(404);
    exit;
}

$mime = mime_content_type($path) ?: 'application/octet-stream';
if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=86400');
readfile($path);
?>
