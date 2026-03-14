<?php
require_once __DIR__ . '/../auth.php';
requireLogin();

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Single equipment by ID
    if (isset($_GET['id'])) {
        if ($user['role'] === 'client') {
            $stmt = $pdo->prepare('SELECT e.*, u.name as client_name FROM equipment e JOIN users u ON e.client_id = u.id WHERE e.id = ? AND e.client_id = ?');
            $stmt->execute([$_GET['id'], $user['id']]);
        } else {
            $stmt = $pdo->prepare('SELECT e.*, u.name as client_name FROM equipment e JOIN users u ON e.client_id = u.id WHERE e.id = ?');
            $stmt->execute([$_GET['id']]);
        }
        $eq = $stmt->fetch();
        if (!$eq) jsonResponse(['error' => 'Equipo no encontrado'], 404);
        jsonResponse($eq);
    }

    // List equipment
    if ($user['role'] === 'worker') {
        if (!empty($_GET['client_id'])) {
            $stmt = $pdo->prepare('SELECT e.*, u.name as client_name FROM equipment e JOIN users u ON e.client_id = u.id WHERE e.client_id = ? ORDER BY e.code');
            $stmt->execute([$_GET['client_id']]);
        } else {
            $stmt = $pdo->query('SELECT e.*, u.name as client_name FROM equipment e JOIN users u ON e.client_id = u.id ORDER BY e.code');
        }
    } else {
        $stmt = $pdo->prepare('SELECT e.*, u.name as client_name FROM equipment e JOIN users u ON e.client_id = u.id WHERE e.client_id = ? ORDER BY e.code');
        $stmt->execute([$user['id']]);
    }
    jsonResponse($stmt->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireWorker();
    $data = json_decode(file_get_contents('php://input'), true);
    $code = trim($data['code'] ?? '');
    $type = trim($data['type'] ?? '');
    $brand = trim($data['brand'] ?? '');
    $model = trim($data['model'] ?? '');
    $serial = trim($data['serial'] ?? '');
    $client_id = $data['client_id'] ?? '';

    if (!$code || !$type || !$brand || !$model || !$serial || !$client_id) {
        jsonResponse(['error' => 'Todos los campos son requeridos'], 400);
    }

    $stmt = $pdo->prepare('SELECT id FROM equipment WHERE code = ?');
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'El código de equipo ya existe'], 400);
    }

    $stmt = $pdo->prepare('INSERT INTO equipment (code, type, brand, model, serial, client_id) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$code, $type, $brand, $model, $serial, $client_id]);
    jsonResponse(['id' => $pdo->lastInsertId(), 'code' => $code]);
}
