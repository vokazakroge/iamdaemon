<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '/var/www/reg.iamdaemon.tech/config.php';
    
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

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
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>