<?php
require_once __DIR__ . '/auth.php';
requireWorker();
$user = currentUser();
$base = BASE_URL;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Trabajador - Kaufmann</title>
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
      <a href="<?= $base ?>/manage-equipment.php">GESTIONAR EQUIPOS</a>
      <a href="<?= $base ?>/logout.php">SALIR</a>
    </nav>
  </div>

  <div class="panel-layout">
    <!-- SIDEBAR: Clientes -->
    <div class="panel-sidebar">
      <div class="sidebar-title">CLIENTES</div>
      <input type="text" class="sidebar-search" id="client-search" placeholder="Buscar cliente...">
      <ul class="sidebar-list" id="client-list"></ul>
      <div style="padding:10px 15px;">
        <button class="btn btn-success btn-sm" style="width:100%;" onclick="openModal('modal-new-client')">+ Nuevo Cliente</button>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="panel-main">
      <div id="alert-global"></div>

      <!-- Estado inicial: ningún cliente seleccionado -->
      <div id="view-welcome" class="empty-state" style="border-radius:8px;">
        Selecciona un cliente del panel izquierdo para ver sus equipos.
      </div>

      <!-- Vista de equipos del cliente seleccionado -->
      <div id="view-equipment" style="display:none;">
        <div class="panel-toolbar">
          <h2 id="equipment-title" style="font-size:20px;font-weight:700;color:#333;margin:0;flex:1;"></h2>
          <button class="btn btn-success btn-sm" onclick="openModal('modal-new-equipment')">+ Nuevo Equipo</button>
        </div>
        <div id="equipment-grid" class="equipment-grid"></div>
        <div id="equipment-empty" class="empty-state" style="display:none;border-radius:8px;">
          Este cliente no tiene equipos registrados.
        </div>
      </div>

      <!-- Vista de reportes de un equipo seleccionado -->
      <div id="view-reports" style="display:none;">
        <div style="margin-bottom:15px;">
          <button class="btn btn-sm" style="background:#e0e0e0;" onclick="backToEquipment()">&#8592; Volver a Equipos</button>
        </div>
        <div class="card" style="margin-bottom:20px;padding:20px;">
          <div class="equipment-header" id="report-equipment-header" style="margin-bottom:0;box-shadow:none;padding:0;"></div>
        </div>
        <div class="panel-toolbar">
          <h3 style="font-size:18px;font-weight:700;color:#333;margin:0;flex:1;">Reportes</h3>
          <button class="btn btn-success btn-sm" onclick="openModal('modal-upload-report')">+ Subir Reporte</button>
        </div>
        <div class="panel-table-container">
          <table class="panel-table" id="reports-table">
            <thead>
              <tr>
                <th>Codigo</th>
                <th>Descripcion</th>
                <th>Fecha</th>
                <th>Subido por</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="reports-tbody"></tbody>
          </table>
        </div>
        <div id="reports-empty" class="empty-state" style="display:none;border-radius:0 0 8px 8px;">
          No hay reportes para este equipo.
        </div>
      </div>
    </div>
  </div>

  <!-- Modal: Nuevo Cliente -->
  <div class="modal-overlay" id="modal-new-client">
    <div class="modal">
      <h3>Crear Nuevo Cliente</h3>
      <form id="form-new-client">
        <div class="form-group">
          <label>Nombre / Empresa</label>
          <input type="text" id="nc-name" required placeholder="Nombre del cliente o empresa">
        </div>
        <div class="form-group">
          <label>Usuario</label>
          <input type="text" id="nc-username" required placeholder="Usuario para iniciar sesion">
        </div>
        <div class="form-group">
          <label>Contrasena</label>
          <input type="password" id="nc-password" required placeholder="Contrasena">
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-sm" onclick="closeModal('modal-new-client')" style="background:#ccc;">Cancelar</button>
          <button type="submit" class="btn btn-success btn-sm">Crear Cliente</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Nuevo Equipo -->
  <div class="modal-overlay" id="modal-new-equipment">
    <div class="modal">
      <h3>Crear Nuevo Equipo</h3>
      <form id="form-new-equipment">
        <div class="form-group">
          <label>Numero Economico</label>
          <input type="text" id="ne-code" required placeholder="Ej: MONT-26">
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Tipo de Equipo</label>
            <input type="text" id="ne-type" required placeholder="Ej: Montacargas">
          </div>
          <div class="form-group">
            <label>Marca</label>
            <input type="text" id="ne-brand" required placeholder="Ej: TOYOTA">
          </div>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label>Modelo</label>
            <input type="text" id="ne-model" required placeholder="Ej: 7FBEU20">
          </div>
          <div class="form-group">
            <label>Serie</label>
            <input type="text" id="ne-serial" required placeholder="Ej: 11922">
          </div>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-sm" onclick="closeModal('modal-new-equipment')" style="background:#ccc;">Cancelar</button>
          <button type="submit" class="btn btn-success btn-sm">Crear Equipo</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Subir Reporte -->
  <div class="modal-overlay" id="modal-upload-report">
    <div class="modal">
      <h3>Subir Reporte de Trabajo</h3>
      <form id="form-upload-report" enctype="multipart/form-data">
        <div class="form-group">
          <label>Codigo de Reporte</label>
          <input type="text" name="report_code" required placeholder="Ej: KAUF3509">
        </div>
        <div class="form-group">
          <label>Fecha del Reporte</label>
          <input type="date" name="report_date" required>
        </div>
        <div class="form-group">
          <label>Descripcion del Trabajo</label>
          <textarea name="description" rows="3" placeholder="Descripcion del trabajo realizado" required></textarea>
        </div>
        <div class="form-group">
          <label>Archivo PDF</label>
          <input type="file" name="pdf" accept=".pdf" required>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-sm" onclick="closeModal('modal-upload-report')" style="background:#ccc;">Cancelar</button>
          <button type="submit" class="btn btn-success btn-sm">Subir Reporte</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const BASE = '<?= $base ?>';
    let allClients = [];
    let selectedClientId = null;
    let selectedClientName = '';
    let selectedEquipmentId = null;

    // ============ HELPERS ============
    function esc(str) {
      const d = document.createElement('div');
      d.textContent = str;
      return d.innerHTML;
    }

    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    function showAlert(msg, type) {
      const el = document.getElementById('alert-global');
      el.innerHTML = '<div class="alert alert-' + type + '">' + esc(msg) + '</div>';
      setTimeout(() => el.innerHTML = '', 4000);
    }

    // ============ CLIENTS SIDEBAR ============
    function loadClients() {
      fetch(BASE + '/api/clients.php').then(r => r.json()).then(clients => {
        allClients = clients;
        renderClientList(clients);
      });
    }

    function renderClientList(clients) {
      const list = document.getElementById('client-list');
      list.innerHTML = clients.map(c =>
        '<li class="sidebar-item' + (selectedClientId == c.id ? ' active' : '') + '" onclick="selectClient(' + c.id + ', \'' + esc(c.name).replace(/'/g, "\\'") + '\')">' +
        '<span class="sidebar-icon">&#128188;</span>' + esc(c.name) +
        '</li>'
      ).join('');
    }

    document.getElementById('client-search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      const filtered = allClients.filter(c => c.name.toLowerCase().includes(q));
      renderClientList(filtered);
    });

    function selectClient(id, name) {
      selectedClientId = id;
      selectedClientName = name;
      selectedEquipmentId = null;

      // Update sidebar active state
      document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
      const items = document.querySelectorAll('.sidebar-item');
      items.forEach(item => {
        if (item.onclick && item.onclick.toString().includes(id)) {
          item.classList.add('active');
        }
      });
      renderClientList(allClients.filter(c => {
        const q = document.getElementById('client-search').value.toLowerCase();
        return !q || c.name.toLowerCase().includes(q);
      }));

      // Show equipment view
      document.getElementById('view-welcome').style.display = 'none';
      document.getElementById('view-reports').style.display = 'none';
      document.getElementById('view-equipment').style.display = 'block';
      document.getElementById('equipment-title').textContent = 'Equipos de ' + name;
      loadEquipment(id);
    }

    // ============ EQUIPMENT ============
    function loadEquipment(clientId) {
      fetch(BASE + '/api/equipment.php?client_id=' + clientId).then(r => r.json()).then(equipment => {
        const grid = document.getElementById('equipment-grid');
        const empty = document.getElementById('equipment-empty');
        if (equipment.length === 0) {
          grid.innerHTML = '';
          grid.style.display = 'none';
          empty.style.display = 'block';
          return;
        }
        empty.style.display = 'none';
        grid.style.display = 'grid';
        grid.innerHTML = equipment.map(e => {
          const badgeClass = e.status === 'Activo' ? 'badge-success' : e.status === 'Inactivo' ? 'badge-danger' : 'badge-warning';
          return '<div class="equipment-card" onclick="selectEquipment(' + e.id + ')">' +
            '<div class="eq-code">' + esc(e.code) + '</div>' +
            '<div class="eq-details">' +
            '<span>Tipo: <strong>' + esc(e.type) + '</strong></span>' +
            '<span>Marca: <strong>' + esc(e.brand) + '</strong></span>' +
            '<span>Modelo: <strong>' + esc(e.model) + '</strong></span>' +
            '<span>Serie: <strong>' + esc(e.serial) + '</strong></span>' +
            '</div>' +
            '<div style="margin-top:10px;"><span class="status-badge ' + badgeClass + '">' + esc(e.status) + '</span></div>' +
            '</div>';
        }).join('');
      });
    }

    // ============ REPORTS ============
    function selectEquipment(equipmentId) {
      selectedEquipmentId = equipmentId;

      // Get equipment details
      fetch(BASE + '/api/equipment.php?id=' + equipmentId).then(r => r.json()).then(eq => {
        document.getElementById('view-equipment').style.display = 'none';
        document.getElementById('view-reports').style.display = 'block';

        document.getElementById('report-equipment-header').innerHTML =
          '<div class="info-item"><label>No. Economico</label><p>' + esc(eq.code) + '</p></div>' +
          '<div class="info-item"><label>Tipo</label><p>' + esc(eq.type) + '</p></div>' +
          '<div class="info-item"><label>Marca</label><p>' + esc(eq.brand) + '</p></div>' +
          '<div class="info-item"><label>Modelo</label><p>' + esc(eq.model) + '</p></div>';

        loadReports(equipmentId);
      });
    }

    function loadReports(equipmentId) {
      fetch(BASE + '/api/reports.php?equipment_id=' + equipmentId).then(r => r.json()).then(reports => {
        const tbody = document.getElementById('reports-tbody');
        const empty = document.getElementById('reports-empty');
        const table = document.getElementById('reports-table');

        if (reports.length === 0) {
          tbody.innerHTML = '';
          table.style.display = 'none';
          empty.style.display = 'block';
          return;
        }
        empty.style.display = 'none';
        table.style.display = 'table';
        tbody.innerHTML = reports.map(r =>
          '<tr>' +
          '<td><strong>' + esc(r.report_code) + '</strong></td>' +
          '<td>' + esc(r.description) + '</td>' +
          '<td>' + esc(r.report_date) + '</td>' +
          '<td>' + esc(r.uploaded_by_name) + '</td>' +
          '<td><a href="' + BASE + '/api/download.php?file=' + encodeURIComponent(r.pdf_filename) + '" class="btn-download" style="display:inline-flex;padding:6px 12px;font-size:12px;">&#128196; PDF</a></td>' +
          '</tr>'
        ).join('');
      });
    }

    function backToEquipment() {
      document.getElementById('view-reports').style.display = 'none';
      document.getElementById('view-equipment').style.display = 'block';
      selectedEquipmentId = null;
    }

    // ============ FORM: Nuevo Cliente ============
    document.getElementById('form-new-client').addEventListener('submit', function(e) {
      e.preventDefault();
      fetch(BASE + '/api/clients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: document.getElementById('nc-name').value,
          username: document.getElementById('nc-username').value,
          password: document.getElementById('nc-password').value
        })
      }).then(r => r.json()).then(data => {
        if (data.error) return showAlert(data.error, 'error');
        closeModal('modal-new-client');
        this.reset();
        loadClients();
        showAlert('Cliente creado exitosamente', 'success');
      });
    });

    // ============ FORM: Nuevo Equipo ============
    document.getElementById('form-new-equipment').addEventListener('submit', function(e) {
      e.preventDefault();
      if (!selectedClientId) return showAlert('Selecciona un cliente primero', 'error');
      fetch(BASE + '/api/equipment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          code: document.getElementById('ne-code').value,
          type: document.getElementById('ne-type').value,
          brand: document.getElementById('ne-brand').value,
          model: document.getElementById('ne-model').value,
          serial: document.getElementById('ne-serial').value,
          client_id: selectedClientId
        })
      }).then(r => r.json()).then(data => {
        if (data.error) return showAlert(data.error, 'error');
        closeModal('modal-new-equipment');
        this.reset();
        loadEquipment(selectedClientId);
        showAlert('Equipo creado exitosamente', 'success');
      });
    });

    // ============ FORM: Subir Reporte ============
    document.getElementById('form-upload-report').addEventListener('submit', function(e) {
      e.preventDefault();
      if (!selectedEquipmentId) return showAlert('Selecciona un equipo primero', 'error');
      const formData = new FormData(this);
      formData.append('equipment_id', selectedEquipmentId);
      fetch(BASE + '/api/reports.php', {
        method: 'POST',
        body: formData
      }).then(r => r.json()).then(data => {
        if (data.error) return showAlert(data.error, 'error');
        closeModal('modal-upload-report');
        this.reset();
        loadReports(selectedEquipmentId);
        showAlert('Reporte subido exitosamente: ' + data.report_code, 'success');
      });
    });

    // ============ INIT ============
    loadClients();
  </script>
</body>
</html>
