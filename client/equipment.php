<?php
require_once __DIR__ . '/../auth.php';
requireClient();
$user = currentUser();
$base = BASE_URL;
$equipmentId = intval($_GET['id'] ?? 0);
if (!$equipmentId) {
    header('Location: ' . $base . '/client/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reportes de Trabajo - Kaufmann</title>
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
    <h2 class="page-title" id="page-title">REPORTES DE TRABAJO</h2>

    <div class="equipment-header" id="eq-header">
      <div class="info-item">
        <label>Equipo</label>
        <p id="eq-type">-</p>
      </div>
      <div class="info-item">
        <label>Marca</label>
        <p id="eq-brand">-</p>
      </div>
      <div class="info-item">
        <label>Modelo</label>
        <p id="eq-model">-</p>
      </div>
      <div class="info-item">
        <label>Serie</label>
        <p id="eq-serial">-</p>
      </div>
    </div>

    <div class="card">
      <h3>Reportes de Trabajo</h3>
      <div id="reports-list"></div>
    </div>
  </div>

  <script>
    const BASE = '<?= $base ?>';
    const equipmentId = <?= $equipmentId ?>;

    fetch(BASE + '/api/equipment.php?id=' + equipmentId).then(r => {
      if (!r.ok) { window.location = BASE + '/client/index.php'; return null; }
      return r.json();
    }).then(eq => {
      if (!eq) return;
      document.getElementById('page-title').textContent = 'REPORTES DE TRABAJO ' + eq.code;
      document.getElementById('eq-type').textContent = eq.type;
      document.getElementById('eq-brand').textContent = eq.brand;
      document.getElementById('eq-model').textContent = eq.model;
      document.getElementById('eq-serial').textContent = eq.serial;
    });

    fetch(BASE + '/api/reports.php?equipment_id=' + equipmentId).then(r => r.json()).then(reports => {
      const container = document.getElementById('reports-list');
      if (reports.length === 0) {
        container.innerHTML = '<div style="padding:20px;text-align:center;color:#888;">No hay reportes para este equipo.</div>';
        return;
      }
      container.innerHTML = reports.map(r => `
        <div class="report-item">
          <div class="report-info">
            <div class="report-code">${r.report_code} \u2013 ${r.description} ${r.report_date}</div>
          </div>
          <a href="${BASE}/api/download.php?file=${r.pdf_filename}" class="btn-download">
            &#128196; DESCARGA EN PDF
          </a>
        </div>
      `).join('');
    });
  </script>
</body>
</html>
