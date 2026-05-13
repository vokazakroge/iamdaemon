<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authorized']);
    exit;
}

$username = $_SESSION['username'];
$uploadDir = getUserDir($username) . '/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$allowedExt = getAllowedUploadExtensions();
$maxSize = 20 * 1024 * 1024; // 20MB

$uploads = [];
if (isset($_FILES['file'])) {
    $uploads[] = $_FILES['file'];
} elseif (isset($_FILES['files'])) {
    foreach ($_FILES['files']['name'] as $i => $name) {
        $uploads[] = [
            'name' => $name,
            'type' => $_FILES['files']['type'][$i],
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'error' => $_FILES['files']['error'][$i],
            'size' => $_FILES['files']['size'][$i],
        ];
    }
}

if (!$uploads) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

foreach ($uploads as $file) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = basename($file['name']);

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(500);
        echo json_encode(['error' => 'Upload error code: ' . $file['error']]);
        exit;
    }

    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => "$filename is too large (max 20MB)"]);
        exit;
    }

    if (!in_array($ext, $allowedExt, true)) {
        http_response_code(400);
        echo json_encode(['error' => "$filename extension is not allowed"]);
        exit;
    }

    if (!isSafeFilename($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid filename']);
        exit;
    }

    $target = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        http_response_code(500);
        echo json_encode(['error' => "Failed to save $filename"]);
        exit;
    }
    chmod($target, 0644);
}

echo json_encode(['success' => true, 'count' => count($uploads)]);
?>
