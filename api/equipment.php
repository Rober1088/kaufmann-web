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

    // Build query for listing
    $where = [];
    $params = [];

    if ($user['role'] === 'client') {
        $where[] = 'e.client_id = ?';
        $params[] = $user['id'];
    } elseif (!empty($_GET['client_id'])) {
        $where[] = 'e.client_id = ?';
        $params[] = $_GET['client_id'];
    }

    // Search filter
    if (!empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $where[] = '(e.code LIKE ? OR e.type LIKE ? OR e.brand LIKE ? OR e.model LIKE ? OR e.serial LIKE ?)';
        $params = array_merge($params, [$search, $search, $search, $search, $search]);
    }

    // Status filter
    if (!empty($_GET['status'])) {
        $where[] = 'e.status = ?';
        $params[] = $_GET['status'];
    }

    $sql = 'SELECT e.*, u.name as client_name FROM equipment e JOIN users u ON e.client_id = u.id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    // Sorting
    $allowedSort = ['code', 'type', 'brand', 'model', 'serial', 'client_name', 'status', 'created_at'];
    $sort = in_array($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'e.created_at';
    $order = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    if ($sort === 'client_name') {
        $sql .= " ORDER BY u.name $order";
    } else {
        $sql .= " ORDER BY e.$sort $order";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    requireWorker();
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    $status = $data['status'] ?? '';

    $allowedStatuses = ['Activo', 'En Mantenimiento', 'Inactivo'];
    if (!$id || !in_array($status, $allowedStatuses)) {
        jsonResponse(['error' => 'Datos inválidos'], 400);
    }

    $stmt = $pdo->prepare('UPDATE equipment SET status = ? WHERE id = ?');
    $stmt->execute([$status, $id]);
    jsonResponse(['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    requireWorker();
    $id = $_GET['id'] ?? '';
    if (!$id) {
        jsonResponse(['error' => 'ID requerido'], 400);
    }

    // Check if equipment has reports
    $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM reports WHERE equipment_id = ?');
    $stmt->execute([$id]);
    $count = $stmt->fetch()['cnt'];
    if ($count > 0) {
        jsonResponse(['error' => 'No se puede eliminar: el equipo tiene ' . $count . ' reporte(s) asociado(s)'], 400);
    }

    $stmt = $pdo->prepare('DELETE FROM equipment WHERE id = ?');
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}
