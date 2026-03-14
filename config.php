<?php
// ============================================
// CONFIGURACIÓN - Editar estos valores en cPanel
// ============================================

// Base de datos MySQL (crear en cPanel > MySQL Databases)
define('DB_HOST', 'localhost');
define('DB_NAME', 'i5746513_reportes');  // Prefijo de cPanel + nombre
define('DB_USER', 'i5746513_reportes');  // Prefijo de cPanel + usuario
define('DB_PASS', 'TU_PASSWORD_AQUI');   // La que asignes en cPanel

// Ruta base del sistema (no cambiar si usas /reportes/)
define('BASE_URL', '/reportes');

// Directorio de uploads
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Iniciar sesión
session_start();

// Conexión a la base de datos
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Error de conexión a la base de datos. Verifica config.php');
}
