<?php
require_once __DIR__ . '/../auth.php';
requireClient();
$user = currentUser();
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Equipos - Kaufmann</title>
  <link rel="stylesheet" href="<?= $base ?>/css/style.css">
</head>
<body>
  <div class="header">
    <div class="logo">
      <span class="gear">&#9881;</span>
      <h1>KAUFMANN</h1>
    </div>
    <nav>
      <span class="user-info"><?= htmlspecialchars($user['name']) ?></span>
      <a href="<?= $base ?>/client/index.php">MIS EQUIPOS</a>
      <a href="<?= $base ?>/logout.php">SALIR</a>
    </nav>
  </div>

  <div class="main">
    <h2 class="page-title">MIS EQUIPOS</h2>
    <div class="equipment-grid" id="equipment-list"></div>
  </div>

  <script>
    const BASE = '<?= $base ?>';
    fetch(BASE + '/api/equipment.php').then(r => r.json()).then(equipment => {
      const container = document.getElementById('equipment-list');
      if (equipment.length === 0) {
        container.innerHTML = '<div class="card" style="text-align:center;color:#888;">No tiene equipos asignados.</div>';
        return;
      }
      container.innerHTML = equipment.map(e => `
        <div class="equipment-card" onclick="window.location='${BASE}/client/equipment.php?id=${e.id}'">
          <div class="eq-code">${e.code}</div>
          <div class="eq-details">
            <span><strong>Tipo:</strong> ${e.type}</span>
            <span><strong>Marca:</strong> ${e.brand}</span>
            <span><strong>Modelo:</strong> ${e.model}</span>
            <span><strong>Serie:</strong> ${e.serial}</span>
          </div>
        </div>
      `).join('');
    });
  </script>
</body>
</html>
