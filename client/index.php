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
      <a href="<?= $base ?>/client/index.php" style="color:#d4a017;">MIS EQUIPOS</a>
      <a href="<?= $base ?>/logout.php">SALIR</a>
    </nav>
  </div>

  <div class="panel-layout">
    <!-- Sidebar -->
    <aside class="panel-sidebar">
      <div class="sidebar-title">MI CUENTA</div>
      <ul class="sidebar-list">
        <li class="sidebar-item active">
          <span class="sidebar-icon">&#9881;</span> Mis Equipos
        </li>
        <li class="sidebar-item" onclick="window.location='<?= $base ?>/client/equipment.php?id=0'">
          <span class="sidebar-icon">&#128196;</span> Mis Reportes
        </li>
        <li class="sidebar-item">
          <span class="sidebar-icon">&#128100;</span> Perfil
        </li>
        <li class="sidebar-item">
          <span class="sidebar-icon">&#9993;</span> Contactar
        </li>
      </ul>
    </aside>

    <!-- Main Content -->
    <main class="panel-main">
      <div class="panel-toolbar">
        <input type="text" class="toolbar-search" id="search-equipment" placeholder="Buscar No. Económico, Tipo, Marca o Modelo...">
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
              <th>Reportes</th>
            </tr>
          </thead>
          <tbody id="equipment-table"></tbody>
        </table>
        <div class="empty-state" id="empty-state" style="display:none;">
          No tiene equipos asignados.
        </div>
      </div>
    </main>
  </div>

  <script>
    const BASE = '<?= $base ?>';
    let currentSort = 'created_at';
    let currentOrder = 'desc';
    let searchTimeout = null;

    function loadEquipment() {
      const search = document.getElementById('search-equipment').value;
      let url = BASE + '/api/equipment.php?sort=' + currentSort + '&order=' + currentOrder;
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
          return '<tr onclick="window.location=\'' + BASE + '/client/equipment.php?id=' + e.id + '\'" style="cursor:pointer;">' +
            '<td><strong>' + escapeHtml(e.code) + '</strong></td>' +
            '<td>' + escapeHtml(e.type) + '</td>' +
            '<td>' + escapeHtml(e.brand) + '</td>' +
            '<td>' + escapeHtml(e.model) + '</td>' +
            '<td>' + escapeHtml(e.serial) + '</td>' +
            '<td><span class="status-badge ' + statusClass + '">' + escapeHtml(e.status) + '</span></td>' +
            '<td>' + dateStr + '</td>' +
            '<td><a href="' + BASE + '/client/equipment.php?id=' + e.id + '" class="action-link" onclick="event.stopPropagation();">Ver Reportes</a></td>' +
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

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    // Init
    loadEquipment();
  </script>
</body>
</html>
