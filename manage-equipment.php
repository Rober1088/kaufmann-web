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
  <title>Gestión de Equipos - Kaufmann</title>
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
      <a href="<?= $base ?>/worker.php">PANEL</a>
      <a href="<?= $base ?>/manage-equipment.php" style="color:#d4a017;">EQUIPOS</a>
      <a href="<?= $base ?>/logout.php">SALIR</a>
    </nav>
  </div>

  <div class="panel-layout">
    <!-- Sidebar -->
    <aside class="panel-sidebar">
      <div class="sidebar-title">CLIENTES</div>
      <input type="text" class="sidebar-search" id="search-client" placeholder="Buscar cliente...">
      <ul class="sidebar-list" id="client-list">
        <li class="sidebar-item active" data-id="">
          <span class="sidebar-icon">&#128193;</span> Todos
        </li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="panel-main">
      <div class="panel-toolbar">
        <input type="text" class="toolbar-search" id="search-equipment" placeholder="Buscar No. Económico, Tipo, Marca o Modelo...">
        <button class="btn btn-danger" id="btn-new-equipment" onclick="openModal('modal-new-equipment')">+ Crear equipo</button>
        <div class="toolbar-sort">
          <span>Ordenar</span>
          <select id="sort-field" onchange="loadEquipment()">
            <option value="created_at">Fecha</option>
            <option value="code">No. Económico</option>
            <option value="type">Tipo</option>
            <option value="brand">Marca</option>
            <option value="status">Estado</option>
          </select>
          <button class="sort-dir-btn" id="sort-dir-btn" onclick="toggleSort()">&#9660;</button>
        </div>
      </div>

      <div id="alert-equipment"></div>

      <div class="panel-table-container">
        <table class="panel-table">
          <thead>
            <tr>
              <th class="sortable" onclick="sortBy('code')">No. Económico <span class="sort-arrows">&#8693;</span></th>
              <th class="sortable" onclick="sortBy('type')">Tipo <span class="sort-arrows">&#8693;</span></th>
              <th class="sortable" onclick="sortBy('brand')">Marca <span class="sort-arrows">&#8693;</span></th>
              <th class="sortable" onclick="sortBy('model')">Modelo <span class="sort-arrows">&#8693;</span></th>
              <th>Serie</th>
              <th class="sortable" onclick="sortBy('status')">Estado <span class="sort-arrows">&#8693;</span></th>
              <th class="sortable" onclick="sortBy('created_at')">Fecha <span class="sort-arrows">&#8693;</span></th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="equipment-table"></tbody>
        </table>
        <div class="empty-state" id="empty-state" style="display:none;">
          No se encontraron equipos.
        </div>
      </div>
    </main>
  </div>

  <!-- Modal: New Equipment -->
  <div class="modal-overlay" id="modal-new-equipment">
    <div class="modal">
      <h3>Crear Nuevo Equipo</h3>
      <form id="form-new-equipment">
        <div class="form-group">
          <label>Cliente</label>
          <select id="ne-client" required>
            <option value="">Seleccione un cliente</option>
          </select>
        </div>
        <div class="form-group">
          <label>Número Económico</label>
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

  <!-- Modal: Confirm Delete -->
  <div class="modal-overlay" id="modal-delete">
    <div class="modal" style="width:400px;">
      <h3>Confirmar Eliminación</h3>
      <p id="delete-msg">¿Está seguro de eliminar este equipo?</p>
      <div class="modal-actions">
        <button class="btn btn-sm" onclick="closeModal('modal-delete')" style="background:#ccc;">Cancelar</button>
        <button class="btn btn-danger btn-sm" id="btn-confirm-delete">Eliminar</button>
      </div>
    </div>
  </div>

  <script>
    const BASE = '<?= $base ?>';
    let currentClientId = '';
    let currentSort = 'created_at';
    let currentOrder = 'desc';
    let searchTimeout = null;

    // Sidebar - load clients
    function loadClients() {
      fetch(BASE + '/api/clients.php').then(r => r.json()).then(clients => {
        const list = document.getElementById('client-list');
        const items = '<li class="sidebar-item' + (currentClientId === '' ? ' active' : '') + '" data-id="" onclick="selectClient(this, \'\')"><span class="sidebar-icon">&#128193;</span> Todos</li>' +
          clients.map(c =>
            '<li class="sidebar-item' + (currentClientId == c.id ? ' active' : '') + '" data-id="' + c.id + '" onclick="selectClient(this, ' + c.id + ')">' +
            '<span class="sidebar-icon">&#128100;</span> ' + escapeHtml(c.name) + '</li>'
          ).join('');
        list.innerHTML = items;

        // Populate modal select
        const sel = document.getElementById('ne-client');
        sel.innerHTML = '<option value="">Seleccione un cliente</option>' +
          clients.map(c => '<option value="' + c.id + '">' + escapeHtml(c.name) + '</option>').join('');
      });
    }

    function selectClient(el, clientId) {
      currentClientId = clientId;
      document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
      el.classList.add('active');
      loadEquipment();
    }

    // Filter sidebar
    document.getElementById('search-client').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.sidebar-item').forEach(item => {
        if (item.dataset.id === '') {
          item.style.display = '';
          return;
        }
        item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });

    // Equipment table
    function loadEquipment() {
      const search = document.getElementById('search-equipment').value;
      let url = BASE + '/api/equipment.php?sort=' + currentSort + '&order=' + currentOrder;
      if (currentClientId) url += '&client_id=' + currentClientId;
      if (search) url += '&search=' + encodeURIComponent(search);

      fetch(url).then(r => r.json()).then(equipment => {
        const tbody = document.getElementById('equipment-table');
        const empty = document.getElementById('empty-state');

        if (equipment.length === 0) {
          tbody.innerHTML = '';
          empty.style.display = 'block';
          return;
        }
        empty.style.display = 'none';

        tbody.innerHTML = equipment.map(e => {
          const statusClass = e.status === 'Activo' ? 'badge-success' :
                              e.status === 'Inactivo' ? 'badge-danger' : 'badge-warning';
          const date = new Date(e.created_at);
          const dateStr = date.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' }) + ' ' +
                          date.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });
          return '<tr>' +
            '<td><strong>' + escapeHtml(e.code) + '</strong></td>' +
            '<td>' + escapeHtml(e.type) + '</td>' +
            '<td>' + escapeHtml(e.brand) + '</td>' +
            '<td>' + escapeHtml(e.model) + '</td>' +
            '<td>' + escapeHtml(e.serial) + '</td>' +
            '<td><select class="status-select ' + statusClass + '" onchange="updateStatus(' + e.id + ', this.value, this)">' +
              '<option value="Activo"' + (e.status === 'Activo' ? ' selected' : '') + '>Activo</option>' +
              '<option value="En Mantenimiento"' + (e.status === 'En Mantenimiento' ? ' selected' : '') + '>En Mantenimiento</option>' +
              '<option value="Inactivo"' + (e.status === 'Inactivo' ? ' selected' : '') + '>Inactivo</option>' +
            '</select></td>' +
            '<td>' + dateStr + '</td>' +
            '<td class="actions-cell">' +
              '<a href="#" class="action-link" onclick="confirmDelete(' + e.id + ', \'' + escapeHtml(e.code) + '\');return false;">Eliminar</a>' +
            '</td>' +
          '</tr>';
        }).join('');
      });
    }

    // Search with debounce
    document.getElementById('search-equipment').addEventListener('input', function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(loadEquipment, 300);
    });

    // Sort
    function sortBy(field) {
      if (currentSort === field) {
        currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
      } else {
        currentSort = field;
        currentOrder = 'asc';
      }
      document.getElementById('sort-field').value = field;
      updateSortBtn();
      loadEquipment();
    }

    function toggleSort() {
      currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
      updateSortBtn();
      loadEquipment();
    }

    function updateSortBtn() {
      document.getElementById('sort-dir-btn').innerHTML = currentOrder === 'asc' ? '&#9650;' : '&#9660;';
    }

    document.getElementById('sort-field').addEventListener('change', function() {
      currentSort = this.value;
      loadEquipment();
    });

    // Status update
    function updateStatus(id, status, selectEl) {
      fetch(BASE + '/api/equipment.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, status: status })
      }).then(r => r.json()).then(data => {
        if (data.error) {
          showAlert('alert-equipment', data.error, 'error');
          return;
        }
        // Update badge color
        selectEl.className = 'status-select ' + (status === 'Activo' ? 'badge-success' : status === 'Inactivo' ? 'badge-danger' : 'badge-warning');
      });
    }

    // Delete
    let deleteId = null;
    function confirmDelete(id, code) {
      deleteId = id;
      document.getElementById('delete-msg').textContent = '¿Está seguro de eliminar el equipo ' + code + '?';
      openModal('modal-delete');
    }

    document.getElementById('btn-confirm-delete').addEventListener('click', function() {
      if (!deleteId) return;
      fetch(BASE + '/api/equipment.php?id=' + deleteId, { method: 'DELETE' })
        .then(r => r.json()).then(data => {
          closeModal('modal-delete');
          if (data.error) {
            showAlert('alert-equipment', data.error, 'error');
            return;
          }
          showAlert('alert-equipment', 'Equipo eliminado correctamente', 'success');
          loadEquipment();
        });
    });

    // Create equipment
    document.getElementById('form-new-equipment').addEventListener('submit', function(e) {
      e.preventDefault();
      fetch(BASE + '/api/equipment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          code: document.getElementById('ne-code').value,
          type: document.getElementById('ne-type').value,
          brand: document.getElementById('ne-brand').value,
          model: document.getElementById('ne-model').value,
          serial: document.getElementById('ne-serial').value,
          client_id: document.getElementById('ne-client').value
        })
      }).then(r => r.json()).then(data => {
        if (data.error) return showAlert('alert-equipment', data.error, 'error');
        closeModal('modal-new-equipment');
        this.reset();
        loadEquipment();
        showAlert('alert-equipment', 'Equipo creado exitosamente', 'success');
      });
    });

    // Helpers
    function openModal(id) { document.getElementById(id).classList.add('active'); }
    function closeModal(id) { document.getElementById(id).classList.remove('active'); }

    function showAlert(containerId, msg, type) {
      const el = document.getElementById(containerId);
      el.innerHTML = '<div class="alert alert-' + type + '">' + escapeHtml(msg) + '</div>';
      setTimeout(() => el.innerHTML = '', 4000);
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Init
    loadClients();
    loadEquipment();
  </script>
</body>
</html>
