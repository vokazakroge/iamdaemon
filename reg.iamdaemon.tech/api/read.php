<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$filename = $_GET['file'] ?? '';
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
    echo json_encode(['error' => 'Cannot edit this file type']);
    exit;
}

echo json_encode(['content' => file_get_contents($target)]);
?>
