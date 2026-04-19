<?php
require_once '/var/www/reg.iamdaemon.tech/config.php';
header('Content-Type: application/json; charset=utf-8');

requireAdmin();

$username = $_GET['username'] ?? '';
if (!$username || !preg_match('/^[a-z0-9-]+$/', $username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid username']);
    exit;
}

$userDir = "/var/www/users/$username";
if (!is_dir($userDir)) {
    http_response_code(404);
    echo json_encode(['error' => 'User directory not found']);
    exit;
}

$files = [];
$scan = scandir($userDir);
foreach ($scan as $file) {
    if ($file !== '.' && $file !== '..') {
        $path = "$userDir/$file";
        $files[] = [
            'name' => $file,
            'size' => is_file($path) ? filesize($path) : 0,
            'is_dir' => is_dir($path)
        ];
    }
}

echo json_encode(['files' => $files]);
?>