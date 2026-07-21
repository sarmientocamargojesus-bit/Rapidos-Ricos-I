<?php
/**
 * view/admin/usuarios.php — Gestión de Usuarios v2.0
 */

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
AuthMiddleware::requerirAdmin();

require_once __DIR__ . '/../../dao/UsuarioDAO.php';
require_once __DIR__ . '/../../helpers/Seguridad.php';

$dao      = new UsuarioDAO();
$usuarios = $dao->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Usuarios – Admin Rápido &amp; Rico</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="../../css/style.css" rel="stylesheet"/>
  <link href="../../css/admin.css" rel="stylesheet"/>
</head>
<body class="admin-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn-sidebar-toggle d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list fs-4"></i></button>
      <div>
        <h5 class="mb-0 fw-800">Gestión de Usuarios</h5>
        <small class="text-muted"><?= count($usuarios) ?> usuarios registrados</small>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="admin-card">
      <h6 class="fw-800 mb-3"><i class="bi bi-people text-danger me-2"></i>Todos los usuarios</h6>
      <div class="table-responsive">
        <table class="table rr-table align-middle" id="tablaUsuarios">
          <thead>
            <tr>
              <th>#</th>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Teléfono</th>
              <th>Dirección</th>
              <th>Rol</th>
              <th>Registro</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u):
              $model = new \stdClass();
              $model->rol    = $u['rol'];
              $model->nombre = $u['nombre'];
            ?>
            <tr>
              <td class="text-muted small"><?= (int)$u['id_usuario'] ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="user-avatar-sm">
                    <?= strtoupper(mb_substr($u['nombre'], 0, 1, 'UTF-8')) ?>
                  </div>
                  <span class="fw-700"><?= Seguridad::e($u['nombre']) ?></span>
                </div>
              </td>
              <td class="text-muted"><?= Seguridad::e($u['correo']) ?></td>
              <td><?= Seguridad::e($u['telefono'] ?? '—') ?></td>
              <td class="small text-muted" style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= Seguridad::e($u['direccion'] ?? '—') ?>
              </td>
              <td>
                <span class="badge rounded-pill bg-<?= $u['rol'] === 'admin' ? 'danger' : 'secondary' ?>">
                  <?= $u['rol'] === 'admin' ? 'Admin' : 'Cliente' ?>
                </span>
              </td>
              <td class="text-muted small">
                <?= isset($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '—' ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
.user-avatar-sm {
  width:34px; height:34px; border-radius:50%;
  background: var(--rr-red, #E8192C); color:#fff;
  display:flex; align-items:center; justify-content:center;
  font-family:'Poppins',sans-serif; font-weight:700; font-size:.85rem;
  flex-shrink:0;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFXhDdJuFiOiJiUJHPjgsMDiZDd" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
  $('#tablaUsuarios').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
    order: [[0, 'desc']],
    pageLength: 15,
  });
});
function toggleSidebar() {
  document.getElementById('adminSidebar').classList.toggle('open');
}
</script>
</body>
</html>
