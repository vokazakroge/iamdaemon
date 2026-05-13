<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$filename = $input['file'] ?? '';
$content = $input['content'] ?? '';

if (!isSafeFilename($filename)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filename']);
    exit;
}

$userDir = getUserDir($_SESSION['username']);
$target = realpath("$userDir/$filename");

if ($target === false || strpos($target, $userDir) !== 0 || !is_file($target)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

$ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
if (!in_array($ext, getEditableExtensions(), true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Cannot save this file type']);
    exit;
}

if (file_put_contents($target, $content) !== false) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
}
?>
