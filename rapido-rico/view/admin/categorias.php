<?php
/**
 * view/admin/categorias.php — Gestión de Categorías v2.0
 */

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
AuthMiddleware::requerirAdmin();

require_once __DIR__ . '/../../dao/CategoriaDAO.php';
require_once __DIR__ . '/../../helpers/Seguridad.php';

$dao        = new CategoriaDAO();
$categorias = $dao->obtenerConConteoProductos();

$editar = null;
if (isset($_GET['editar'])) {
    $editar = $dao->buscarPorId(Seguridad::entero($_GET['editar']));
}

$exito   = $_SESSION['exito']   ?? '';
$errores = $_SESSION['errores'] ?? [];
unset($_SESSION['exito'], $_SESSION['errores']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Categorías – Admin Rápido &amp; Rico</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
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
        <h5 class="mb-0 fw-800">Gestión de Categorías</h5>
        <small class="text-muted"><?= count($categorias) ?> categorías registradas</small>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="row g-4">

      <!-- ══ Formulario ════════════════════════════════════════════════════ -->
      <div class="col-lg-4">
        <div class="admin-card">
          <h6 class="fw-800 mb-3">
            <i class="bi bi-<?= $editar ? 'pencil' : 'plus-circle' ?> text-danger me-2"></i>
            <?= $editar ? 'Editar categoría' : 'Nueva categoría' ?>
          </h6>

          <form action="../../controller/CategoriaController.php" method="POST" novalidate>
            <input type="hidden" name="accion" value="<?= $editar ? 'actualizar' : 'crear' ?>">
            <?= Seguridad::campoCSRF() ?>

            <?php if ($editar): ?>
            <input type="hidden" name="id_categoria" value="<?= (int)$editar['id_categoria'] ?>">
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label fw-700 small">Nombre *</label>
              <input type="text" name="nombre" class="form-control rr-input"
                     placeholder="Ej: Hamburguesas, Bebidas..."
                     value="<?= Seguridad::e($editar['nombre'] ?? '') ?>"
                     minlength="2" maxlength="80" required>
            </div>

            <div class="mb-4">
              <label class="form-label fw-700 small">Descripción</label>
              <textarea name="descripcion" class="form-control rr-input" rows="3"
                        placeholder="Descripción breve..."><?= Seguridad::e($editar['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn-rr flex-fill justify-content-center">
                <i class="bi bi-<?= $editar ? 'check-lg' : 'plus-lg' ?>"></i>
                <?= $editar ? 'Actualizar' : 'Crear categoría' ?>
              </button>
              <?php if ($editar): ?>
              <a href="categorias.php" class="btn-rr-outline px-3" title="Cancelar">
                <i class="bi bi-x-lg"></i>
              </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- ══ Tabla Categorías ══════════════════════════════════════════════ -->
      <div class="col-lg-8">
        <div class="admin-card">
          <h6 class="fw-800 mb-3">Categorías registradas</h6>
          <div class="table-responsive">
            <table class="table rr-table align-middle" id="tablaCategorias">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Nombre</th>
                  <th>Descripción</th>
                  <th>Productos</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($categorias)): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No hay categorías registradas.
                  </td>
                </tr>
                <?php else: ?>
                <?php foreach ($categorias as $cat): ?>
                <tr>
                  <td class="text-muted small"><?= (int)$cat['id_categoria'] ?></td>
                  <td>
                    <span class="badge rounded-pill px-3 py-2"
                          style="background:rgba(232,25,44,.08);color:var(--rr-red);font-size:.82rem;font-weight:700">
                      <i class="bi bi-tag-fill me-1"></i>
                      <?= Seguridad::e($cat['nombre']) ?>
                    </span>
                  </td>
                  <td class="text-muted small">
                    <?= Seguridad::e($cat['descripcion'] ?? '—') ?>
                  </td>
                  <td>
                    <span class="badge bg-<?= (int)$cat['total_productos'] > 0 ? 'success' : 'secondary' ?> rounded-pill">
                      <?= (int)$cat['total_productos'] ?> producto<?= (int)$cat['total_productos'] !== 1 ? 's' : '' ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="categorias.php?editar=<?= (int)$cat['id_categoria'] ?>"
                         class="btn-icon-sm" title="Editar">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <?php if ((int)$cat['total_productos'] === 0): ?>
                      <button type="button" class="btn-icon-sm danger" title="Eliminar"
                              onclick="confirmarEliminar(<?= (int)$cat['id_categoria'] ?>, '<?= Seguridad::attr($cat['nombre']) ?>')">
                        <i class="bi bi-trash3"></i>
                      </button>
                      <?php else: ?>
                      <button type="button" class="btn-icon-sm" title="Tiene productos activos — no eliminable" disabled
                              style="opacity:.35;cursor:not-allowed">
                        <i class="bi bi-trash3"></i>
                      </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Formulario oculto para eliminar -->
<form id="formEliminar" action="../../controller/CategoriaController.php" method="POST" style="display:none">
  <input type="hidden" name="accion" value="eliminar">
  <?= Seguridad::campoCSRF() ?>
  <input type="hidden" name="id_categoria" id="eliminarId">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFXhDdJuFiOiJiUJHPjgsMDiZDd" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
  $('#tablaCategorias').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
    columnDefs: [{ orderable: false, targets: 4 }],
    pageLength: 10,
    order: [[0, 'asc']]
  });
});

<?php if ($exito): ?>
toastr.success(<?= json_encode($exito) ?>, '¡Éxito!', { positionClass: 'toast-top-right', timeOut: 4000 });
<?php endif; ?>
<?php if (!empty($errores)): ?>
toastr.error(<?= json_encode(implode('<br>', $errores)) ?>, 'Error', { positionClass: 'toast-top-right', enableHtml: true, timeOut: 6000 });
<?php endif; ?>

function confirmarEliminar(id, nombre) {
  Swal.fire({
    title: '¿Eliminar categoría?',
    html: `¿Deseas eliminar <strong>${nombre}</strong>?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#E8192C',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="bi bi-trash3 me-1"></i> Eliminar',
    cancelButtonText: 'Cancelar',
    focusCancel: true,
  }).then(r => {
    if (r.isConfirmed) {
      document.getElementById('eliminarId').value = id;
      document.getElementById('formEliminar').submit();
    }
  });
}

function toggleSidebar() {
  document.getElementById('adminSidebar').classList.toggle('open');
}
</script>
</body>
</html>
