<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

$filename = basename($_GET['file'] ?? '');
if (!$filename) {
    http_response_code(400);
    die('Archivo no especificado');
}

$filepath = UPLOAD_DIR . $filename;
if (!file_exists($filepath)) {
    http_response_code(404);
    die('Archivo no encontrado');
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
