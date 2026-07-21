<?php
/**
 * view/cliente/detalle_pedido.php — Detalle de un pedido v2.0
 */

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
AuthMiddleware::requerirLogin();

require_once __DIR__ . '/../../dao/PedidoDAO.php';
require_once __DIR__ . '/../../dao/PagoDAO.php';
require_once __DIR__ . '/../../model/Pedido.php';
require_once __DIR__ . '/../../helpers/Seguridad.php';

$dao       = new PedidoDAO();
$pagoDAO   = new PagoDAO();
$id_pedido = Seguridad::entero($_GET['id'] ?? 0);

// Buscar pedido y verificar que pertenece al usuario actual
$pedido = $dao->buscarPorId($id_pedido);
if (!$pedido || (int)$pedido['id_usuario'] !== (int)$_SESSION['id_usuario']) {
    header('Location: mis_pedidos.php');
    exit;
}

$detalle = $dao->obtenerDetalle($id_pedido);
$pago    = $pagoDAO->obtenerPorPedido($id_pedido);

// Instanciar modelo para acceder a métodos de presentación (MVC — Rich Model)
$model           = new Pedido();
$model->id_pedido = $id_pedido;
$model->estado    = $pedido['estado'];

$pasos      = ['pendiente', 'en_preparacion', 'listo', 'entregado'];
$labPasos   = ['Pedido recibido', 'En preparación', 'Listo para entrega', 'Entregado'];
$iconsPasos = ['bi-bag-check', 'bi-fire', 'bi-clock-history', 'bi-truck'];
$pasoActual = (int) array_search($pedido['estado'], $pasos);

$pageTitle = 'Detalle Pedido – Rápido & Rico';
include '../layouts/header.php';
?>

<section style="padding:2rem 0 5rem;background:var(--rr-gray,#F4F6FA);min-height:calc(100vh - 64px)">
  <div class="container">

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
        <li class="breadcrumb-item"><a href="mis_pedidos.php">Mis pedidos</a></li>
        <li class="breadcrumb-item active"><?= Seguridad::e($model->numeroPedido()) ?></li>
      </ol>
    </nav>

    <!-- Encabezado del pedido -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
      <div>
        <h2 class="section-title mb-1">
          Pedido <span><?= Seguridad::e($model->numeroPedido()) ?></span>
        </h2>
        <span class="text-muted small">
          <i class="bi bi-calendar3 me-1"></i>
          <?= date('d/m/Y – H:i', strtotime($pedido['fecha'])) ?>
        </span>
      </div>
      <span class="badge bg-<?= Seguridad::e($model->badgeClase()) ?> rounded-pill px-3 py-2 fs-6">
        <i class="bi <?= Seguridad::e($model->iconoEstado()) ?> me-1"></i>
        <?= Seguridad::e($model->etiquetaEstado()) ?>
      </span>
    </div>

    <div class="row g-4">

      <!-- ══ Columna izquierda ══════════════════════════════════════════════ -->
      <div class="col-lg-8">

        <!-- Tracker de estado -->
        <?php if ($pedido['estado'] !== 'cancelado'): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
          <h6 class="fw-800 mb-4">
            <i class="bi bi-geo-alt-fill text-danger me-2"></i>Seguimiento del pedido
          </h6>
          <div class="pedido-tracker">
            <div class="tracker-track">
              <div class="tracker-fill"
                   style="width:<?= $pasoActual >= 0 ? min(100, ($pasoActual / (count($pasos)-1)) * 100) : 0 ?>%">
              </div>
            </div>
            <?php foreach ($pasos as $pi => $paso): ?>
            <div class="tracker-step <?= $pi <= $pasoActual ? 'done' : '' ?> <?= $pi === $pasoActual ? 'current' : '' ?>">
              <div class="tracker-dot">
                <i class="bi <?= $iconsPasos[$pi] ?>"></i>
              </div>
              <div class="tracker-label"><?= $labPasos[$pi] ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php else: ?>
        <div class="alert alert-danger rounded-4 mb-4">
          <i class="bi bi-x-circle-fill me-2"></i>
          Este pedido fue <strong>cancelado</strong>.
        </div>
        <?php endif; ?>

        <!-- Productos del pedido -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
          <h6 class="fw-800 mb-3">
            <i class="bi bi-bag-fill text-danger me-2"></i>Productos
          </h6>
          <?php foreach ($detalle as $d): ?>
          <div class="d-flex align-items-center gap-3 py-3 border-bottom">
            <img src="../../img/<?= Seguridad::attr($d['imagen'] ?? 'default.jpg') ?>"
                 onerror="this.src='https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=120&auto=format&fit=crop'"
                 class="rounded-3 flex-shrink-0"
                 style="width:68px;height:68px;object-fit:cover"
                 alt="<?= Seguridad::attr($d['producto_nombre']) ?>">
            <div class="flex-grow-1">
              <div class="fw-700" style="font-family:'Poppins',sans-serif">
                <?= Seguridad::e($d['producto_nombre']) ?>
              </div>
              <div class="text-muted small">S/ <?= number_format($d['precio'], 2) ?> c/u</div>
            </div>
            <div class="text-center px-2">
              <div class="text-muted" style="font-size:.72rem">Cant.</div>
              <div class="fw-800 fs-5"><?= (int)$d['cantidad'] ?></div>
            </div>
            <div class="text-end">
              <div class="text-muted" style="font-size:.72rem">Subtotal</div>
              <div class="fw-800 text-danger">S/ <?= number_format($d['precio'] * $d['cantidad'], 2) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>

      <!-- ══ Columna derecha ════════════════════════════════════════════════ -->
      <div class="col-lg-4">

        <!-- Resumen de pago -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
          <h6 class="fw-800 mb-3">
            <i class="bi bi-receipt text-danger me-2"></i>Resumen
          </h6>
          <?php
            $subtotal = $pedido['total'] - DELIVERY_FEE;
          ?>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Subtotal</span>
            <span>S/ <?= number_format(max(0, $subtotal), 2) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Delivery</span>
            <span>S/ <?= number_format(DELIVERY_FEE, 2) ?></span>
          </div>
          <hr>
          <div class="d-flex justify-content-between">
            <span class="fw-800" style="font-family:'Poppins',sans-serif">Total</span>
            <span class="fw-800 text-danger fs-5">S/ <?= number_format($pedido['total'], 2) ?></span>
          </div>
        </div>

        <!-- Información de pago -->
        <?php if ($pago): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
          <h6 class="fw-800 mb-3">
            <i class="bi bi-credit-card text-danger me-2"></i>Pago
          </h6>
          <div class="small">
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Método</span>
              <span class="fw-700 text-capitalize"><?= Seguridad::e($pago['metodo']) ?></span>
            </div>
            <?php if ($pago['metodo'] === 'tarjeta' && $pago['banco']): ?>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Banco</span>
              <span class="fw-600"><?= Seguridad::e($pago['banco']) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Tarjeta</span>
              <span class="fw-600">•••• <?= Seguridad::e($pago['ultimos_cuatro'] ?? '????') ?></span>
            </div>
            <?php elseif ($pago['metodo'] === 'yape' && $pago['yape_telefono']): ?>
            <div class="d-flex justify-content-between mb-2">
              <span class="text-muted">Yape</span>
              <span class="fw-600">+51<?= Seguridad::e($pago['yape_telefono']) ?></span>
            </div>
            <?php endif; ?>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Estado</span>
              <span class="badge bg-<?= $pago['estado'] === 'aprobado' ? 'success' : ($pago['estado'] === 'rechazado' ? 'danger' : 'warning') ?>">
                <?= ucfirst(Seguridad::e($pago['estado'])) ?>
              </span>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Datos de entrega -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-3">
          <h6 class="fw-800 mb-3">
            <i class="bi bi-geo-alt text-danger me-2"></i>Entrega
          </h6>
          <div class="small">
            <div class="text-muted mb-1">Dirección</div>
            <div class="fw-600 mb-2"><?= Seguridad::e($pedido['direccion'] ?? '—') ?></div>
            <?php if (!empty($pedido['referencia'])): ?>
            <div class="text-muted mb-1">Referencia</div>
            <div class="fw-600 mb-2"><?= Seguridad::e($pedido['referencia']) ?></div>
            <?php endif; ?>
            <div class="text-muted mb-1">Teléfono</div>
            <div class="fw-600"><?= Seguridad::e($pedido['telefono'] ?? '—') ?></div>
          </div>
        </div>

        <a href="mis_pedidos.php" class="btn-rr-outline w-100 justify-content-center">
          <i class="bi bi-arrow-left me-1"></i> Volver a mis pedidos
        </a>

      </div>
    </div>
  </div>
</section>

<style>
.pedido-tracker {
  display:flex; justify-content:space-between; align-items:flex-start;
  position:relative; padding:.2rem 1rem .5rem;
}
.tracker-track {
  position:absolute; top:1.45rem; left:1.5rem; right:1.5rem;
  height:4px; background:#eee; border-radius:2px; z-index:0;
}
.tracker-fill {
  height:100%; background:var(--rr-red); border-radius:2px; transition:width .5s;
}
.tracker-step { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; z-index:1; }
.tracker-dot {
  width:42px; height:42px; border-radius:50%; background:#eee; color:#aaa;
  font-size:1.05rem; display:flex; align-items:center; justify-content:center;
  transition:background .3s, color .3s, box-shadow .3s;
}
.tracker-step.done .tracker-dot    { background:var(--rr-red); color:#fff; }
.tracker-step.current .tracker-dot { box-shadow:0 0 0 5px rgba(232,25,44,.2); }
.tracker-label {
  font-size:.68rem; font-weight:700; margin-top:.4rem;
  text-align:center; color:#aaa; line-height:1.2;
}
.tracker-step.done .tracker-label { color:var(--rr-red); }
</style>

<?php include '../layouts/footer.php'; ?>
