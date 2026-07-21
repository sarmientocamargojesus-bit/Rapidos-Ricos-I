<?php
/**
 * view/admin/pedidos.php — Gestión de Pedidos v2.0
 * Mejoras: DataTables, SweetAlert2, Toastr, sidebar reutilizable.
 */

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
AuthMiddleware::requerirAdmin();

require_once __DIR__ . '/../../dao/PedidoDAO.php';
require_once __DIR__ . '/../../dao/PagoDAO.php';
require_once __DIR__ . '/../../helpers/Seguridad.php';

$dao     = new PedidoDAO();
$pagoDAO = new PagoDAO();
$pedidos = $dao->obtenerTodos();

// Ver detalle de pedido
$detalle     = null;
$ped_detalle = null;
$infoPago    = null;

if (isset($_GET['ver'])) {
    $idVer       = (int) $_GET['ver'];
    $ped_detalle = $dao->buscarPorId($idVer);
    if ($ped_detalle) {
        $detalle  = $dao->obtenerDetalle($idVer);
        $infoPago = $pagoDAO->obtenerPorPedido($idVer);
    }
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
  <title>Pedidos – Admin Rápido &amp; Rico</title>
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
        <h5 class="mb-0 fw-800">Gestión de Pedidos</h5>
        <small class="text-muted"><?= count($pedidos) ?> pedidos registrados</small>
      </div>
    </div>
  </div>

  <div class="admin-content">

    <!-- ══ Detalle de pedido ══════════════════════════════════════════════════ -->
    <?php if ($detalle !== null && $ped_detalle): ?>
    <?php
      $mPed = new Pedido();
      $mPed->estado = $ped_detalle['estado'];
    ?>
    <div class="admin-card mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h6 class="fw-800 mb-0">
            Pedido <?= Seguridad::e('RR-' . str_pad($ped_detalle['id_pedido'], 6, '0', STR_PAD_LEFT)) ?>
          </h6>
          <small class="text-muted">
            <?= date('d/m/Y H:i', strtotime($ped_detalle['fecha'])) ?> ·
            <span class="badge bg-<?= Seguridad::e($mPed->badgeClase()) ?>">
              <?= Seguridad::e($mPed->etiquetaEstado()) ?>
            </span>
          </small>
        </div>
        <a href="pedidos.php" class="btn-rr-outline" style="padding:.4rem 1rem;font-size:.83rem">
          <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
      </div>

      <div class="row g-3 mb-3">
        <!-- Info cliente -->
        <div class="col-md-6">
          <div style="background:var(--admin-bg);border-radius:12px;padding:1rem">
            <div class="fw-700 mb-2 small text-uppercase text-muted" style="letter-spacing:.05em">Cliente</div>
            <div class="fw-700"><?= Seguridad::e($ped_detalle['cliente_nombre']) ?></div>
            <div class="small text-muted"><?= Seguridad::e($ped_detalle['cliente_correo']) ?></div>
            <div class="small mt-1"><i class="bi bi-geo-alt text-danger me-1"></i><?= Seguridad::e($ped_detalle['direccion'] ?? '') ?></div>
            <div class="small"><i class="bi bi-telephone text-danger me-1"></i><?= Seguridad::e($ped_detalle['telefono'] ?? '') ?></div>
          </div>
        </div>
        <!-- Info pago -->
        <?php if ($infoPago): ?>
        <div class="col-md-6">
          <div style="background:var(--admin-bg);border-radius:12px;padding:1rem">
            <div class="fw-700 mb-2 small text-uppercase text-muted" style="letter-spacing:.05em">Pago</div>
            <div class="fw-700 text-capitalize"><?= Seguridad::e($infoPago['metodo']) ?></div>
            <?php if ($infoPago['metodo'] === 'tarjeta'): ?>
            <div class="small text-muted">
              <?= Seguridad::e($infoPago['banco'] ?? '') ?> · •••• <?= Seguridad::e($infoPago['ultimos_cuatro'] ?? '') ?>
            </div>
            <?php elseif ($infoPago['metodo'] === 'yape'): ?>
            <div class="small text-muted">+51<?= Seguridad::e($infoPago['yape_telefono'] ?? '') ?></div>
            <?php endif; ?>
            <div class="small"><strong>Estado pago:</strong>
              <span class="badge bg-<?= $infoPago['estado'] === 'aprobado' ? 'success' : ($infoPago['estado'] === 'rechazado' ? 'danger' : 'warning') ?>">
                <?= ucfirst(Seguridad::e($infoPago['estado'])) ?>
              </span>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Tabla de ítems -->
      <div class="table-responsive mb-3">
        <table class="table rr-table">
          <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unit.</th><th>Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($detalle as $d): ?>
            <tr>
              <td><?= Seguridad::e($d['producto_nombre']) ?></td>
              <td><?= (int)$d['cantidad'] ?></td>
              <td>S/ <?= number_format($d['precio'], 2) ?></td>
              <td class="fw-700 text-danger">S/ <?= number_format($d['precio'] * $d['cantidad'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="table-light">
              <td colspan="3" class="text-end fw-800">TOTAL</td>
              <td class="fw-800 text-danger fs-6">S/ <?= number_format($ped_detalle['total'], 2) ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Cambiar estado con SweetAlert2 -->
      <form action="../../controller/PedidoController.php" method="POST"
            class="d-flex gap-2 align-items-center flex-wrap"
            id="formEstado">
        <?= \Seguridad::campoCSRF() ?>
        <input type="hidden" name="accion"    value="cambiarEstado">
        <input type="hidden" name="id_pedido" value="<?= (int)$ped_detalle['id_pedido'] ?>">
        <select name="estado" class="form-select" style="max-width:220px;border-radius:10px;border:2px solid #e0e0e0">
          <?php foreach (['pendiente','en_preparacion','listo','entregado','cancelado'] as $est): ?>
          <option value="<?= $est ?>" <?= $ped_detalle['estado'] === $est ? 'selected' : '' ?>>
            <?= ucfirst(str_replace('_', ' ', $est)) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn-rr" onclick="confirmarCambioEstado()">
          <i class="bi bi-arrow-repeat"></i> Actualizar estado
        </button>
      </form>
    </div>
    <?php endif; ?>

    <!-- ══ Tabla de todos los pedidos ════════════════════════════════════════ -->
    <div class="admin-card">
      <h6 class="fw-800 mb-3">Todos los pedidos</h6>
      <div class="table-responsive">
        <table class="table rr-table align-middle" id="tablaPedidos">
          <thead>
            <tr>
              <th>#Pedido</th>
              <th>Cliente</th>
              <th>Fecha</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pedidos as $p):
              $mP = new Pedido(); $mP->estado = $p['estado'];
            ?>
            <tr>
              <td class="fw-700 text-danger">
                <?= Seguridad::e('RR-' . str_pad($p['id_pedido'], 6, '0', STR_PAD_LEFT)) ?>
              </td>
              <td>
                <div class="fw-600"><?= Seguridad::e($p['cliente_nombre']) ?></div>
                <small class="text-muted"><?= Seguridad::e($p['cliente_correo']) ?></small>
              </td>
              <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($p['fecha'])) ?></td>
              <td class="fw-700">S/ <?= number_format($p['total'], 2) ?></td>
              <td>
                <span class="badge bg-<?= Seguridad::e($mP->badgeClase()) ?> rounded-pill">
                  <?= Seguridad::e($mP->etiquetaEstado()) ?>
                </span>
              </td>
              <td>
                <a href="pedidos.php?ver=<?= (int)$p['id_pedido'] ?>" class="btn-icon-sm" title="Ver detalle">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFXhDdJuFiOiJiUJHPjgsMDiZDd" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
  $('#tablaPedidos').DataTable({
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
    order: [[2, 'desc']],
    columnDefs: [{ orderable: false, targets: 5 }]
  });
});

<?php if ($exito): ?>
toastr.success(<?= json_encode($exito) ?>, '¡Éxito!', { positionClass: 'toast-top-right', timeOut: 4000 });
<?php endif; ?>
<?php if (!empty($errores)): ?>
toastr.error(<?= json_encode(implode('<br>', $errores)) ?>, 'Error', { positionClass: 'toast-top-right' });
<?php endif; ?>

function confirmarCambioEstado() {
  const estado = document.querySelector('select[name="estado"]').value;
  Swal.fire({
    title: '¿Cambiar estado?',
    html: `El pedido pasará a <strong>${estado.replace('_',' ')}</strong>.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#E8192C',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, cambiar',
    cancelButtonText: 'Cancelar'
  }).then(result => { if (result.isConfirmed) document.getElementById('formEstado').submit(); });
}

function toggleSidebar() {
  document.getElementById('adminSidebar').classList.toggle('open');
}
</script>
</body>
</html>
