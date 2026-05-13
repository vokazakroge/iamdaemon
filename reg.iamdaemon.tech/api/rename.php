<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$oldName = $input['old_name'] ?? '';
$newName = $input['new_name'] ?? '';

if (!$oldName || !$newName) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing filename']);
    exit;
}

if (!isSafeFilename($oldName) || !isSafeFilename($newName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filename']);
    exit;
}

$userDir = getUserDir($_SESSION['username']);
$oldPath = realpath("$userDir/$oldName");
$newPath = "$userDir/$newName";

if ($oldPath === false || strpos($oldPath, $userDir) !== 0 || !is_file($oldPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

if (file_exists($newPath)) {
    http_response_code(409);
    echo json_encode(['error' => 'File already exists']);
    exit;
}

if (rename($oldPath, $newPath)) {
    chmod($newPath, 0644);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to rename']);
}
?>
