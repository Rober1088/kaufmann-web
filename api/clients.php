<?php
require_once __DIR__ . '/../auth.php';
requireWorker();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare('SELECT id, username, name FROM users WHERE role = ?');
    $stmt->execute(['client']);
    jsonResponse($stmt->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $name = trim($data['name'] ?? '');

    if (!$username || !$password || !$name) {
        jsonResponse(['error' => 'Todos los campos son requeridos'], 400);
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'El usuario ya existe'], 400);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $hash, $name, 'client']);
    jsonResponse(['id' => $pdo->lastInsertId(), 'username' => $username, 'name' => $name]);
}
