<?php
/**
 * view/cliente/mis_pedidos.php — Historial de pedidos del cliente v2.0
 */

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
AuthMiddleware::requerirLogin();

require_once __DIR__ . '/../../dao/PedidoDAO.php';
require_once __DIR__ . '/../../model/Pedido.php';
require_once __DIR__ . '/../../helpers/Seguridad.php';

$dao     = new PedidoDAO();
$pedidos = $dao->obtenerPorUsuario((int) $_SESSION['id_usuario']);

$exito   = $_SESSION['exito']   ?? '';
$errores = $_SESSION['errores'] ?? [];
unset($_SESSION['exito'], $_SESSION['errores']);

$pageTitle = 'Mis Pedidos – Rápido & Rico';
include '../layouts/header.php';
?>

<section style="padding:2rem 0 5rem;background:var(--rr-gray,#F4F6FA);min-height:calc(100vh - 64px)">
  <div class="container">

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
        <li class="breadcrumb-item active">Mis pedidos</li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <h2 class="section-title mb-0">Mis <span>Pedidos</span></h2>
      <a href="menu.php" class="btn-rr">
        <i class="bi bi-plus-lg"></i> Nuevo pedido
      </a>
    </div>

    <!-- Notificaciones -->
    <?php if ($exito): ?>
    <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <?= Seguridad::e($exito) ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger rounded-4 mb-4">
      <?php foreach ($errores as $e): ?>
      <div><i class="bi bi-exclamation-triangle-fill me-2"></i><?= Seguridad::e($e) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($pedidos)): ?>
    <!-- Empty state -->
    <div class="text-center py-5">
      <div style="width:100px;height:100px;background:rgba(232,25,44,.08);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
        <i class="bi bi-bag-x" style="font-size:2.8rem;color:var(--rr-red)"></i>
      </div>
      <h4 class="fw-800" style="font-family:'Poppins',sans-serif">Aún no tienes pedidos</h4>
      <p class="text-muted mb-4">Explora nuestro menú y realiza tu primer pedido.</p>
      <a href="menu.php" class="btn-rr">
        <i class="bi bi-shop me-2"></i>Ver el menú
      </a>
    </div>

    <?php else: ?>

    <?php
      // Definición de pasos y sus íconos (SRP: solo presentación)
      $pasos      = ['pendiente', 'en_preparacion', 'listo', 'entregado'];
      $labPasos   = ['Recibido', 'Preparando', 'Listo', 'Entregado'];
      $iconsPasos = ['bi-bag-check', 'bi-fire', 'bi-clock-history', 'bi-truck'];
    ?>

    <?php foreach ($pedidos as $p):
      // Instanciar el modelo Pedido para usar sus métodos de presentación (MVC)
      $model           = new Pedido();
      $model->id_pedido = (int) $p['id_pedido'];
      $model->estado    = $p['estado'];
      $pasoActual       = (int) array_search($p['estado'], $pasos);
      $esCancelado      = $p['estado'] === 'cancelado';
    ?>
    <div class="pedido-card mb-4">

      <!-- Header del pedido -->
      <div class="pedido-header">
        <div>
          <span class="pedido-num"><?= Seguridad::e($model->numeroPedido()) ?></span>
          <span class="text-muted small ms-2">
            <i class="bi bi-calendar3 me-1"></i>
            <?= date('d/m/Y – H:i', strtotime($p['fecha'])) ?>
          </span>
        </div>
        <span class="badge bg-<?= Seguridad::e($model->badgeClase()) ?> rounded-pill px-3 py-2">
          <i class="bi <?= Seguridad::e($model->iconoEstado()) ?> me-1"></i>
          <?= Seguridad::e($model->etiquetaEstado()) ?>
        </span>
      </div>

      <!-- Tracker de progreso (solo si no está cancelado) -->
      <?php if (!$esCancelado): ?>
      <div class="pedido-tracker">
        <!-- Línea de progreso -->
        <div class="tracker-track">
          <div class="tracker-fill"
               style="width:<?= $pasoActual >= 0 ? min(100, ($pasoActual / (count($pasos) - 1)) * 100) : 0 ?>%"></div>
        </div>
        <!-- Pasos -->
        <?php foreach ($pasos as $pi => $paso): ?>
        <div class="tracker-step <?= $pi <= $pasoActual ? 'done' : '' ?> <?= $pi === $pasoActual ? 'current' : '' ?>">
          <div class="tracker-dot">
            <i class="bi <?= $iconsPasos[$pi] ?>"></i>
          </div>
          <div class="tracker-label"><?= $labPasos[$pi] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="px-4 py-2">
        <div class="alert alert-danger rounded-3 mb-0 py-2 small">
          <i class="bi bi-x-circle-fill me-2"></i>Este pedido fue <strong>cancelado</strong>.
        </div>
      </div>
      <?php endif; ?>

      <!-- Footer del pedido -->
      <div class="pedido-footer">
        <div class="pedido-total">
          Total: <span>S/ <?= number_format($p['total'], 2) ?></span>
        </div>
        <a href="detalle_pedido.php?id=<?= (int)$p['id_pedido'] ?>" class="btn-rr-outline"
           style="padding:.4rem 1rem;font-size:.83rem">
          Ver detalle <i class="bi bi-chevron-right ms-1"></i>
        </a>
      </div>

    </div>
    <?php endforeach; ?>

    <?php endif; ?>
  </div>
</section>

<style>
/* ── Pedido card ──────────────────────────────── */
.pedido-card {
  background: #fff; border-radius: 18px; overflow: hidden;
  box-shadow: 0 2px 20px rgba(0,0,0,.07);
}
.pedido-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 1.4rem; border-bottom: 1px solid #F0F0F0; flex-wrap: wrap; gap: .5rem;
}
.pedido-num {
  font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1rem; color: var(--rr-red);
}
.pedido-footer {
  display: flex; justify-content: space-between; align-items: center;
  padding: .9rem 1.4rem; background: var(--rr-gray, #F4F6FA); flex-wrap: wrap; gap: .5rem;
}
.pedido-total { font-size: .88rem; color: #555; }
.pedido-total span { font-family: 'Poppins', sans-serif; font-weight: 800; color: var(--rr-red); font-size: 1rem; margin-left: .2rem; }

/* ── Tracker ─────────────────────────────────── */
.pedido-tracker {
  display: flex; justify-content: space-between; align-items: flex-start;
  position: relative; padding: 1.2rem 2rem 1rem;
}
.tracker-track {
  position: absolute; top: 1.95rem; left: 2.5rem; right: 2.5rem;
  height: 4px; background: #eee; border-radius: 2px; z-index: 0;
}
.tracker-fill {
  height: 100%; background: var(--rr-red); border-radius: 2px; transition: width .5s ease;
}
.tracker-step {
  display: flex; flex-direction: column; align-items: center;
  flex: 1; position: relative; z-index: 1;
}
.tracker-dot {
  width: 40px; height: 40px; border-radius: 50%;
  background: #eee; color: #aaa; font-size: 1rem;
  display: flex; align-items: center; justify-content: center;
  transition: background .3s, color .3s, box-shadow .3s;
  flex-shrink: 0;
}
.tracker-step.done .tracker-dot    { background: var(--rr-red); color: #fff; }
.tracker-step.current .tracker-dot { box-shadow: 0 0 0 5px rgba(232,25,44,.20); }
.tracker-label {
  font-size: .68rem; font-weight: 700; margin-top: .4rem;
  text-align: center; color: #aaa; transition: color .3s;
}
.tracker-step.done .tracker-label { color: var(--rr-red); }

@media (max-width: 480px) {
  .tracker-label { display: none; }
  .tracker-dot   { width: 32px; height: 32px; font-size: .85rem; }
}
</style>

<?php include '../layouts/footer.php'; ?>
