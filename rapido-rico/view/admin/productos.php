<?php
/**
 * view/admin/productos.php — Gestión de Productos v2.0
 * Mejoras: sidebar reutilizable, DataTables, SweetAlert2, Toastr, CSRF, preview imagen.
 */

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
AuthMiddleware::requerirAdmin();

require_once __DIR__ . '/../../dao/ProductoDAO.php';
require_once __DIR__ . '/../../dao/CategoriaDAO.php';
require_once __DIR__ . '/../../helpers/Seguridad.php';

$prodDAO    = new ProductoDAO();
$catDAO     = new CategoriaDAO();
$productos  = $prodDAO->obtenerTodos();
$categorias = $catDAO->obtenerTodas();

$editar = null;
if (isset($_GET['editar'])) {
    $editar = $prodDAO->buscarPorId(Seguridad::entero($_GET['editar']));
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
  <title>Productos – Admin Rápido &amp; Rico</title>
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
        <h5 class="mb-0 fw-800">Gestión de Productos</h5>
        <small class="text-muted"><?= count($productos) ?> productos activos</small>
      </div>
    </div>
  </div>

  <div class="admin-content">
    <div class="row g-4">

      <!-- ══ Formulario Crear / Editar ════════════════════════════════════ -->
      <div class="col-lg-4">
        <div class="admin-card">
          <h6 class="fw-800 mb-3">
            <i class="bi bi-<?= $editar ? 'pencil' : 'plus-circle' ?> text-danger me-2"></i>
            <?= $editar ? 'Editar producto' : 'Nuevo producto' ?>
          </h6>

          <form action="../../controller/ProductoController.php" method="POST"
                enctype="multipart/form-data" id="formProducto" novalidate>

            <input type="hidden" name="accion" value="<?= $editar ? 'actualizar' : 'crear' ?>">
            <?= Seguridad::campoCSRF() ?>

            <?php if ($editar): ?>
            <input type="hidden" name="id_producto"   value="<?= (int)$editar['id_producto'] ?>">
            <input type="hidden" name="imagen_actual" value="<?= Seguridad::attr($editar['imagen'] ?? 'default.jpg') ?>">
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label fw-700 small">Nombre *</label>
              <input type="text" name="nombre" class="form-control rr-input"
                     placeholder="Ej: Hamburguesa Clásica"
                     value="<?= Seguridad::e($editar['nombre'] ?? '') ?>"
                     minlength="3" maxlength="120" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-700 small">Descripción</label>
              <textarea name="descripcion" class="form-control rr-input" rows="2"
                        placeholder="Descripción breve del producto..."><?= Seguridad::e($editar['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-700 small">Precio (S/) *</label>
              <input type="number" name="precio" class="form-control rr-input"
                     step="0.10" min="0.50" max="9999.99"
                     placeholder="0.00"
                     value="<?= Seguridad::e($editar['precio'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-700 small">Stock disponible *</label>
              <input type="number" name="stock" class="form-control rr-input"
                     step="1" min="0"
                     placeholder="0"
                     value="<?= Seguridad::e($editar['stock'] ?? '0') ?>" required>
              <div class="form-text">Unidades disponibles para la venta. 0 = sin stock.</div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-700 small">Categoría *</label>
              <select name="id_categoria" class="form-select rr-input" required>
                <option value="">Selecciona una categoría...</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int)$cat['id_categoria'] ?>"
                  <?= (int)($editar['id_categoria'] ?? 0) === (int)$cat['id_categoria'] ? 'selected' : '' ?>>
                  <?= Seguridad::e($cat['nombre']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-700 small">Imagen</label>
              <!-- Preview de imagen actual -->
              <?php if ($editar && !empty($editar['imagen'])): ?>
              <div class="mb-2">
                <img src="../../img/<?= Seguridad::attr($editar['imagen']) ?>"
                     id="imgPreview"
                     class="rounded-3 shadow-sm"
                     style="width:100%;height:140px;object-fit:cover"
                     alt="Imagen actual">
              </div>
              <?php else: ?>
              <div class="mb-2" id="previewWrap" style="display:none">
                <img id="imgPreview" class="rounded-3 shadow-sm"
                     style="width:100%;height:140px;object-fit:cover" alt="">
              </div>
              <?php endif; ?>
              <input type="file" name="imagen" class="form-control rr-input"
                     accept="image/jpeg,image/png,image/webp"
                     onchange="previewImagen(this)">
              <div class="form-text">JPG, PNG o WebP. Máximo 5 MB.</div>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn-rr flex-fill justify-content-center">
                <i class="bi bi-<?= $editar ? 'check-lg' : 'plus-lg' ?>"></i>
                <?= $editar ? 'Actualizar' : 'Crear producto' ?>
              </button>
              <?php if ($editar): ?>
              <a href="productos.php" class="btn-rr-outline px-3" title="Cancelar">
                <i class="bi bi-x-lg"></i>
              </a>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

      <!-- ══ Tabla Productos ═══════════════════════════════════════════════ -->
      <div class="col-lg-8">
        <div class="admin-card">
          <h6 class="fw-800 mb-3">Catálogo de productos</h6>
          <div class="table-responsive">
            <table class="table rr-table align-middle" id="tablaProductos">
              <thead>
                <tr>
                  <th>Imagen</th>
                  <th>Nombre</th>
                  <th>Categoría</th>
                  <th>Precio</th>
                  <th>Stock</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($productos as $p): ?>
                <tr>
                  <td>
                    <img src="../../img/<?= Seguridad::attr($p['imagen'] ?? 'default.jpg') ?>"
                         onerror="this.src='https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=100&auto=format&fit=crop'"
                         class="rounded-2"
                         style="width:52px;height:52px;object-fit:cover"
                         alt="<?= Seguridad::attr($p['nombre']) ?>">
                  </td>
                  <td>
                    <div class="fw-700"><?= Seguridad::e($p['nombre']) ?></div>
                    <small class="text-muted"><?= Seguridad::e(mb_substr($p['descripcion'] ?? '', 0, 40)) ?>...</small>
                  </td>
                  <td>
                    <span class="badge bg-secondary rounded-pill">
                      <?= Seguridad::e($p['categoria_nombre'] ?? '—') ?>
                    </span>
                  </td>
                  <td class="fw-700 text-danger">S/ <?= number_format($p['precio'], 2) ?></td>
                  <td>
                    <?php
                      $stockVal = (int) ($p['stock'] ?? 0);
                      $stockBadgeClase = $stockVal <= 0 ? 'bg-danger' : ($stockVal <= 5 ? 'bg-warning text-dark' : 'bg-success');
                    ?>
                    <span class="badge <?= $stockBadgeClase ?> rounded-pill">
                      <?= $stockVal ?> und.
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="productos.php?editar=<?= (int)$p['id_producto'] ?>"
                         class="btn-icon-sm" title="Editar">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <button type="button" class="btn-icon-sm danger"
                              title="Eliminar"
                              onclick="confirmarEliminar(<?= (int)$p['id_producto'] ?>, '<?= Seguridad::attr($p['nombre']) ?>')">
                        <i class="bi bi-trash3"></i>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Formulario oculto para eliminar (CSRF incluido) -->
<form id="formEliminar" action="../../controller/ProductoController.php" method="POST" style="display:none">
  <input type="hidden" name="accion" value="eliminar">
  <?= Seguridad::campoCSRF() ?>
  <input type="hidden" name="id_producto" id="eliminarId">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFXhDdJuFiOiJiUJHPjgsMDiZDd" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ── DataTables ────────────────────────────────────────────────────────────────
$(document).ready(function () {
  $('#tablaProductos').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
    columnDefs: [{ orderable: false, targets: [0, 5] }],
    pageLength: 10
  });
});

// ── Notificaciones ────────────────────────────────────────────────────────────
<?php if ($exito): ?>
toastr.success(<?= json_encode($exito) ?>, '¡Éxito!', { positionClass: 'toast-top-right', timeOut: 4000 });
<?php endif; ?>
<?php if (!empty($errores)): ?>
toastr.error(<?= json_encode(implode('<br>', $errores)) ?>, 'Error', { positionClass: 'toast-top-right', enableHtml: true });
<?php endif; ?>

// ── Preview de imagen ─────────────────────────────────────────────────────────
function previewImagen(input) {
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    const img  = document.getElementById('imgPreview');
    const wrap = document.getElementById('previewWrap');
    img.src = e.target.result;
    if (wrap) wrap.style.display = 'block';
  };
  reader.readAsDataURL(input.files[0]);
}

// ── Eliminar con SweetAlert2 ──────────────────────────────────────────────────
function confirmarEliminar(id, nombre) {
  Swal.fire({
    title: '¿Eliminar producto?',
    html: `¿Deseas eliminar el producto <strong>${nombre}</strong>? Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#E8192C',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="bi bi-trash3 me-1"></i> Sí, eliminar',
    cancelButtonText: 'Cancelar',
    focusCancel: true,
  }).then(result => {
    if (result.isConfirmed) {
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
