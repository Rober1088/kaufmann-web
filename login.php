<?php
require_once __DIR__ . '/config.php';

$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role']
        ];
        if ($user['role'] === 'worker') {
            header('Location: ' . BASE_URL . '/worker.php');
        } else {
            header('Location: ' . BASE_URL . '/client/index.php');
        }
        exit;
    }
    $error = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Kaufmann</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <div class="gear">&#9881;</div>
      <div class="logo-title">KAUFMANN</div>
      <div class="subtitle">Sistema de Reportes de Trabajo</div>

      <?php if ($error): ?>
        <div class="error-msg">Usuario o contraseña incorrectos</div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Usuario</label>
          <input type="text" name="username" required autocomplete="username" placeholder="Ingrese su usuario">
        </div>
        <div class="form-group">
          <label>Contraseña</label>
          <input type="password" name="password" required autocomplete="current-password" placeholder="Ingrese su contraseña">
        </div>
        <button type="submit" class="btn btn-primary">INICIAR SESIÓN</button>
      </form>
    </div>
  </div>
</body>
</html>
