<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $equipment_id = $_GET['equipment_id'] ?? '';
    if (!$equipment_id) jsonResponse(['error' => 'ID de equipo requerido'], 400);

    // If client, verify ownership
    if ($user['role'] === 'client') {
        $stmt = $pdo->prepare('SELECT id FROM equipment WHERE id = ? AND client_id = ?');
        $stmt->execute([$equipment_id, $user['id']]);
        if (!$stmt->fetch()) jsonResponse(['error' => 'Acceso denegado'], 403);
    }

    $stmt = $pdo->prepare('SELECT r.*, u.name as uploaded_by_name FROM reports r JOIN users u ON r.uploaded_by = u.id WHERE r.equipment_id = ? ORDER BY r.report_date DESC');
    $stmt->execute([$equipment_id]);
    jsonResponse($stmt->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireWorker();

    $report_code = trim($_POST['report_code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $report_date = $_POST['report_date'] ?? '';
    $equipment_id = $_POST['equipment_id'] ?? '';

    if (!$report_code || !$description || !$report_date || !$equipment_id) {
        jsonResponse(['error' => 'Todos los campos son requeridos'], 400);
    }

    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Archivo PDF requerido'], 400);
    }

    $file = $_FILES['pdf'];
    if ($file['type'] !== 'application/pdf' && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        jsonResponse(['error' => 'Solo se permiten archivos PDF'], 400);
    }

    if ($file['size'] > 20 * 1024 * 1024) {
        jsonResponse(['error' => 'El archivo no puede superar 20MB'], 400);
    }

    $filename = time() . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        jsonResponse(['error' => 'Error al subir el archivo'], 500);
    }

    $stmt = $pdo->prepare('INSERT INTO reports (report_code, description, report_date, pdf_filename, equipment_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$report_code, $description, $report_date, $filename, $equipment_id, $user['id']]);
    jsonResponse(['id' => $pdo->lastInsertId(), 'report_code' => $report_code]);
}
