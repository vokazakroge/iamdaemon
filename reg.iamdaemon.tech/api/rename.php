<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username'])) {
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

// Валидация: только буквы, цифры, точка, дефис, подчёркивание
if (!preg_match('/^[a-z0-9.\-_]+$/i', $oldName) || !preg_match('/^[a-z0-9.\-_]+$/i', $newName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filename format']);
    exit;
}

$userDir = '/var/www/users/' . $_SESSION['username'];
$oldPath = realpath("$userDir/$oldName");
$newPath = "$userDir/$newName";

// Проверка пути и существования
if ($oldPath === false || strpos($oldPath, $userDir) !== 0 || !is_file($oldPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Проверка на дубликат
if (file_exists($newPath)) {
    http_response_code(409);
    echo json_encode(['error' => 'File with this name already exists']);
    exit;
}

// Переименование
if (rename($oldPath, $newPath)) {
    chmod($newPath, 0644);
    echo json_encode(['success' => true, 'new_name' => $newName]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to rename file']);
}
?>