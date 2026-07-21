<?php
/**
 * view/admin/dashboard.php
 * ─────────────────────────────────────────────────────────────────────────────
 * VISTA: Dashboard Administrativo
 *
 * Panel de control con KPIs, gráficos Chart.js y pedidos recientes con DataTables.
 * Principio SRP: solo presenta datos; los obtiene de los DAOs sin lógica de negocio.
 *
 * Mejoras v2.0:
 *   - Gráficos Chart.js (ventas, estados, productos).
 *   - DataTables en pedidos recientes.
 *   - KPIs con tendencias visuales.
 *   - SweetAlert2 para confirmaciones.
 *   - Toastr para notificaciones rápidas.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
AuthMiddleware::requerirAdmin();

require_once __DIR__ . '/../../dao/PedidoDAO.php';
require_once __DIR__ . '/../../dao/ProductoDAO.php';
require_once __DIR__ . '/../../dao/UsuarioDAO.php';
require_once __DIR__ . '/../../helpers/Seguridad.php';

// ── Recopilar datos para el dashboard ─────────────────────────────────────────
$pedidoDAO   = new PedidoDAO();
$productoDAO = new ProductoDAO();
$usuarioDAO  = new UsuarioDAO();

$kpiPedidos     = $pedidoDAO->contarTodos();
$kpiClientes    = $usuarioDAO->contarClientes();
$kpiProductos   = $productoDAO->contarTodos();
$kpiVentasHoy   = $pedidoDAO->ventasHoy();
$kpiTotal       = $pedidoDAO->totalVentas();
$kpiPendientes  = $pedidoDAO->contarPendientes();

$pedidosRecientes   = array_slice($pedidoDAO->obtenerTodos(), 0, 10);
$estadisticasEstado = $pedidoDAO->estadisticasPorEstado();
$ventasDias         = $pedidoDAO->ventasUltimosDias(7);
$topProductos       = $productoDAO->topVendidos(5);

// ── Preparar datos para Chart.js (JSON) ───────────────────────────────────────
$chartEstadosLabels = [];
$chartEstadosDatos  = [];
$estadoEtiquetas = ['pendiente'=>'Pendiente','en_preparacion'=>'En preparación','listo'=>'Listo','entregado'=>'Entregado','cancelado'=>'Cancelado'];
foreach ($estadisticasEstado as $e) {
    $chartEstadosLabels[] = $estadoEtiquetas[$e['estado']] ?? $e['estado'];
    $chartEstadosDatos[]  = (int) $e['total'];
}

$chartVentasFechas = [];
$chartVentasMonto  = [];
foreach ($ventasDias as $v) {
    $chartVentasFechas[] = date('d/m', strtotime($v['dia']));
    $chartVentasMonto[]  = (float) $v['ventas'];
}

$chartTopNombres  = [];
$chartTopVendidos = [];
foreach ($topProductos as $t) {
    $chartTopNombres[]  = $t['nombre'];
    $chartTopVendidos[]  = (int) $t['total_vendido'];
}

$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito']   ?? '';
unset($_SESSION['errores'], $_SESSION['exito']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard – Admin Rápido &amp; Rico</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"/>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet"/>
  <!-- DataTables -->
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet"/>
  <!-- Toastr -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>

  <link href="../../css/style.css" rel="stylesheet"/>
  <link href="../../css/admin.css" rel="stylesheet"/>
</head>
<body class="admin-body">

<!-- ══ Sidebar ════════════════════════════════════════════════════════════════ -->
<?php include __DIR__ . '/partials/sidebar.php'; ?>

<!-- ══ Main ═══════════════════════════════════════════════════════════════════ -->
<div class="admin-main">

  <!-- Topbar -->
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn-sidebar-toggle d-lg-none" onclick="toggleSidebar()">
        <i class="bi bi-list fs-4"></i>
      </button>
      <div>
        <h5 class="mb-0 fw-800">Dashboard</h5>
        <small class="text-muted">Resumen general del sistema</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if ($kpiPendientes > 0): ?>
      <span class="badge bg-danger rounded-pill px-3 py-2">
        <i class="bi bi-exclamation-circle me-1"></i><?= $kpiPendientes ?> pendientes
      </span>
      <?php endif; ?>
      <span class="text-muted small">
        <i class="bi bi-person-circle me-1"></i>
        <?= Seguridad::e($_SESSION['nombre']) ?>
      </span>
    </div>
  </div>

  <div class="admin-content">

    <!-- ══ KPI Cards ══════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">

      <div class="col-6 col-xl-3">
        <div class="kpi-card kpi-red">
          <div class="kpi-icon"><i class="bi bi-bag-check-fill"></i></div>
          <div class="kpi-body">
            <div class="kpi-value"><?= number_format($kpiPedidos) ?></div>
            <div class="kpi-label">Total Pedidos</div>
          </div>
          <div class="kpi-trend"><i class="bi bi-arrow-up-right"></i></div>
        </div>
      </div>

      <div class="col-6 col-xl-3">
        <div class="kpi-card kpi-dark">
          <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
          <div class="kpi-body">
            <div class="kpi-value"><?= number_format($kpiClientes) ?></div>
            <div class="kpi-label">Clientes</div>
          </div>
          <div class="kpi-trend"><i class="bi bi-arrow-up-right"></i></div>
        </div>
      </div>

      <div class="col-6 col-xl-3">
        <div class="kpi-card kpi-red">
          <div class="kpi-icon"><i class="bi bi-grid-fill"></i></div>
          <div class="kpi-body">
            <div class="kpi-value"><?= number_format($kpiProductos) ?></div>
            <div class="kpi-label">Productos</div>
          </div>
          <div class="kpi-trend"><i class="bi bi-arrow-up-right"></i></div>
        </div>
      </div>

      <div class="col-6 col-xl-3">
        <div class="kpi-card kpi-success">
          <div class="kpi-icon"><i class="bi bi-cash-stack"></i></div>
          <div class="kpi-body">
            <div class="kpi-value">S/ <?= number_format($kpiVentasHoy, 0) ?></div>
            <div class="kpi-label">Ventas Hoy</div>
          </div>
          <div class="kpi-trend"><i class="bi bi-arrow-up-right"></i></div>
        </div>
      </div>

    </div>

    <!-- ══ Gráficos ═══════════════════════════════════════════════════════════ -->
    <div class="row g-3 mb-4">

      <!-- Ventas últimos 7 días -->
      <div class="col-lg-8">
        <div class="admin-card h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-800 mb-0"><i class="bi bi-graph-up text-danger me-2"></i>Ventas últimos 7 días</h6>
            <span class="badge rounded-pill" style="background:rgba(232,25,44,.1);color:var(--rr-red);font-size:.75rem">
              Total: S/ <?= number_format($kpiTotal, 2) ?>
            </span>
          </div>
          <div style="height:220px;position:relative">
            <canvas id="chartVentas"></canvas>
          </div>
        </div>
      </div>

      <!-- Pedidos por estado -->
      <div class="col-lg-4">
        <div class="admin-card h-100">
          <h6 class="fw-800 mb-3"><i class="bi bi-pie-chart text-danger me-2"></i>Pedidos por estado</h6>
          <div style="height:200px;position:relative">
            <canvas id="chartEstados"></canvas>
          </div>
        </div>
      </div>

    </div>

    <!-- Top Productos -->
    <div class="row g-3 mb-4">
      <div class="col-lg-6">
        <div class="admin-card h-100">
          <h6 class="fw-800 mb-3"><i class="bi bi-bar-chart text-danger me-2"></i>Productos más vendidos</h6>
          <div style="height:200px;position:relative">
            <canvas id="chartProductos"></canvas>
          </div>
        </div>
      </div>

      <!-- Resumen numérico -->
      <div class="col-lg-6">
        <div class="admin-card h-100">
          <h6 class="fw-800 mb-3"><i class="bi bi-lightning text-danger me-2"></i>Resumen rápido</h6>
          <div class="d-flex flex-column gap-3">
            <div class="resumen-item">
              <div class="resumen-label"><i class="bi bi-hourglass-split text-warning me-2"></i>Pedidos pendientes</div>
              <div class="resumen-value text-warning fw-800"><?= $kpiPendientes ?></div>
            </div>
            <div class="resumen-item">
              <div class="resumen-label"><i class="bi bi-cash-coin text-success me-2"></i>Ingresos totales</div>
              <div class="resumen-value text-success fw-800">S/ <?= number_format($kpiTotal, 2) ?></div>
            </div>
            <div class="resumen-item">
              <div class="resumen-label"><i class="bi bi-bag text-danger me-2"></i>Promedio por pedido</div>
              <div class="resumen-value text-danger fw-800">
                S/ <?= $kpiPedidos > 0 ? number_format($kpiTotal / $kpiPedidos, 2) : '0.00' ?>
              </div>
            </div>
            <div class="resumen-item">
              <div class="resumen-label"><i class="bi bi-calendar-check me-2" style="color:#6b21a8"></i>Ventas hoy</div>
              <div class="resumen-value fw-800" style="color:#6b21a8">S/ <?= number_format($kpiVentasHoy, 2) ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ Pedidos Recientes ════════════════════════════════════════════════ -->
    <div class="admin-card">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-800 mb-0"><i class="bi bi-clock-history text-danger me-2"></i>Pedidos recientes</h6>
        <a href="pedidos.php" class="btn-rr" style="padding:.4rem 1rem;font-size:.83rem">
          Ver todos <i class="bi bi-arrow-right ms-1"></i>
        </a>
      </div>
      <div class="table-responsive">
        <table class="table rr-table align-middle" id="tablaPedidos">
          <thead>
            <tr>
              <th>#Pedido</th>
              <th>Cliente</th>
              <th>Fecha</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pedidosRecientes as $p):
              $modelPedido = new Pedido();
              $modelPedido->estado = $p['estado'];
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
                <span class="badge bg-<?= Seguridad::e($modelPedido->badgeClase()) ?> rounded-pill">
                  <?= Seguridad::e($modelPedido->etiquetaEstado()) ?>
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

  </div><!-- /admin-content -->
</div><!-- /admin-main -->

<!-- ══ Scripts ════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFXhDdJuFiOiJiUJHPjgsMDiZDd" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ── Notificaciones al cargar ──────────────────────────────────────────────────
<?php if ($exito): ?>
toastr.success(<?= json_encode($exito) ?>, '¡Éxito!', { positionClass: 'toast-top-right', timeOut: 4000 });
<?php endif; ?>
<?php if (!empty($errores)): ?>
toastr.error(<?= json_encode(implode('<br>', $errores)) ?>, 'Error', { positionClass: 'toast-top-right' });
<?php endif; ?>

// ── DataTables ────────────────────────────────────────────────────────────────
$(document).ready(function () {
  $('#tablaPedidos').DataTable({
    language: {
      url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
    },
    pageLength: 10,
    order: [[2, 'desc']],
    columnDefs: [{ orderable: false, targets: 5 }]
  });
});

// ── Chart.js: Ventas últimos 7 días ─────────────────────────────────────────
const ctxVentas = document.getElementById('chartVentas').getContext('2d');
new Chart(ctxVentas, {
  type: 'line',
  data: {
    labels: <?= json_encode($chartVentasFechas) ?>,
    datasets: [{
      label: 'Ventas (S/)',
      data: <?= json_encode($chartVentasMonto) ?>,
      borderColor: '#E8192C',
      backgroundColor: 'rgba(232,25,44,0.08)',
      borderWidth: 2.5,
      tension: 0.4,
      fill: true,
      pointBackgroundColor: '#E8192C',
      pointRadius: 5,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' },
           ticks: { callback: v => 'S/ ' + v } },
      x: { grid: { display: false } }
    }
  }
});

// ── Chart.js: Pedidos por estado (Doughnut) ───────────────────────────────────
const ctxEstados = document.getElementById('chartEstados').getContext('2d');
new Chart(ctxEstados, {
  type: 'doughnut',
  data: {
    labels: <?= json_encode($chartEstadosLabels) ?>,
    datasets: [{
      data: <?= json_encode($chartEstadosDatos) ?>,
      backgroundColor: ['#FFC107','#17A2B8','#007BFF','#28A745','#DC3545'],
      borderWidth: 0,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
    cutout: '65%',
  }
});

// ── Chart.js: Top productos (Bar) ─────────────────────────────────────────────
const ctxProd = document.getElementById('chartProductos').getContext('2d');
new Chart(ctxProd, {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartTopNombres) ?>,
    datasets: [{
      label: 'Unidades vendidas',
      data: <?= json_encode($chartTopVendidos) ?>,
      backgroundColor: [
        'rgba(232,25,44,0.85)',
        'rgba(232,25,44,0.65)',
        'rgba(232,25,44,0.50)',
        'rgba(232,25,44,0.35)',
        'rgba(232,25,44,0.20)',
      ],
      borderRadius: 8,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
      x: { grid: { display: false },
           ticks: { maxRotation: 0, font: { size: 10 } } }
    }
  }
});

function toggleSidebar() {
  document.querySelector('.admin-sidebar').classList.toggle('open');
}
</script>
</body>
</html>
