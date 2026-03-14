<?php
// Helper functions for authentication
require_once __DIR__ . '/config.php';

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireWorker() {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'worker') {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireClient() {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'client') {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
