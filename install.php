<?php
// ============================================
// INSTALADOR - Ejecutar una sola vez
// Acceder a: kaufmann.mx/reportes/install.php
// ============================================
require_once __DIR__ . '/config.php';

$messages = [];

try {
    // Crear tablas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(200) NOT NULL,
            role ENUM('worker', 'client') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $messages[] = "Tabla 'users' creada.";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS equipment (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) UNIQUE NOT NULL,
            type VARCHAR(100) NOT NULL,
            brand VARCHAR(100) NOT NULL,
            model VARCHAR(100) NOT NULL,
            serial VARCHAR(100) NOT NULL,
            client_id INT NOT NULL,
            status ENUM('Activo', 'En Mantenimiento', 'Inactivo') NOT NULL DEFAULT 'Activo',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $messages[] = "Tabla 'equipment' creada.";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            report_code VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            report_date DATE NOT NULL,
            pdf_filename VARCHAR(255) NOT NULL,
            equipment_id INT NOT NULL,
            uploaded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (equipment_id) REFERENCES equipment(id),
            FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    $messages[] = "Tabla 'reports' creada.";

    // Add status column if missing (migration for existing installs)
    try {
        $pdo->exec("ALTER TABLE equipment ADD COLUMN status ENUM('Activo', 'En Mantenimiento', 'Inactivo') NOT NULL DEFAULT 'Activo' AFTER client_id");
        $messages[] = "Columna 'status' agregada a equipment.";
    } catch (Exception $e) {
        // Column already exists, ignore
    }

    // Crear usuario admin por defecto
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute(['admin']);
    if (!$stmt->fetch()) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)');
        $stmt->execute(['admin', $hash, 'Administrador', 'worker']);
        $messages[] = "Usuario admin creado (usuario: admin, contraseña: admin123).";
    } else {
        $messages[] = "Usuario admin ya existe.";
    }

    // Crear directorio de uploads
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
        $messages[] = "Directorio 'uploads/' creado.";
    }

    // Crear .htaccess para proteger uploads del listado
    $htaccess = UPLOAD_DIR . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Options -Indexes\n");
        $messages[] = "Protección de directorio uploads/ configurada.";
    }

} catch (Exception $e) {
    $messages[] = "ERROR: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instalación - Kaufmann Reportes</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .msg { padding: 10px; margin: 5px 0; background: #d4edda; border-radius: 5px; }
        .msg.error { background: #f8d7da; }
        h1 { color: #d4a017; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px; }
        a { color: #c41e3a; font-weight: bold; }
    </style>
</head>
<body>
    <h1>&#9881; KAUFMANN - Instalación</h1>
    <?php foreach ($messages as $msg): ?>
        <div class="msg <?= strpos($msg, 'ERROR') !== false ? 'error' : '' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>

    <div class="warning">
        <strong>IMPORTANTE:</strong> Elimina este archivo (install.php) después de la instalación por seguridad.<br><br>
        <a href="<?= BASE_URL ?>/login.php">Ir al Login</a>
    </div>
</body>
</html>
