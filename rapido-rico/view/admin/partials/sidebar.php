<?php
/**
 * view/admin/partials/sidebar.php
 * ─────────────────────────────────────────────────────────────────────────────
 * PARTIAL: Sidebar administrativo reutilizable
 *
 * Principio SRP: solo renderiza la navegación lateral del admin.
 * DRY: incluido en todas las vistas admin en lugar de repetirse.
 * ─────────────────────────────────────────────────────────────────────────────
 */

// Detectar página activa para resaltar el enlace
$paginaActual = basename($_SERVER['PHP_SELF']);

$enlaces = [
    ['href' => 'dashboard.php',  'icon' => 'bi-speedometer2',  'label' => 'Dashboard',   'file' => 'dashboard.php'],
    ['href' => 'pedidos.php',    'icon' => 'bi-bag-check',     'label' => 'Pedidos',     'file' => 'pedidos.php'],
    ['href' => 'productos.php',  'icon' => 'bi-grid',          'label' => 'Productos',   'file' => 'productos.php'],
    ['href' => 'categorias.php', 'icon' => 'bi-tags',          'label' => 'Categorías',  'file' => 'categorias.php'],
    ['href' => 'usuarios.php',   'icon' => 'bi-people',        'label' => 'Usuarios',    'file' => 'usuarios.php'],
];
?>
<div class="admin-sidebar" id="adminSidebar">

  <!-- Brand -->
  <div class="sidebar-brand">
    <div class="brand-logo">R</div>
    <span>Rápido &amp; Rico</span>
  </div>

  <!-- Sección: Menú -->
  <div class="sidebar-section-label">MENÚ PRINCIPAL</div>
  <nav class="sidebar-nav">
    <?php foreach ($enlaces as $enlace): ?>
    <a href="<?= $enlace['href'] ?>"
       class="sidebar-link <?= $paginaActual === $enlace['file'] ? 'active' : '' ?>">
      <i class="bi <?= $enlace['icon'] ?>"></i>
      <span><?= $enlace['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-divider"></div>

  <!-- Sección: Accesos rápidos -->
  <div class="sidebar-section-label">ACCESOS RÁPIDOS</div>
  <nav class="sidebar-nav">
    <a href="../../index.php" class="sidebar-link" target="_blank">
      <i class="bi bi-shop"></i>
      <span>Ver tienda</span>
    </a>
    <a href="../../controller/UsuarioController.php?accion=logout"
       class="sidebar-link text-danger"
       onclick="return confirmarLogout(event)">
      <i class="bi bi-box-arrow-right"></i>
      <span>Cerrar sesión</span>
    </a>
  </nav>

  <!-- Footer del sidebar -->
  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar">
        <?= strtoupper(substr($_SESSION['nombre'] ?? 'A', 0, 1)) ?>
      </div>
      <div>
        <div class="fw-700 small"><?= htmlspecialchars($_SESSION['nombre'] ?? '') ?></div>
        <div style="font-size:.7rem;opacity:.6">Administrador</div>
      </div>
    </div>
  </div>

</div>

<script>
function confirmarLogout(e) {
  e.preventDefault();
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      title: '¿Cerrar sesión?',
      text: 'Se cerrará tu sesión actual.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#E8192C',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, cerrar sesión',
      cancelButtonText: 'Cancelar'
    }).then(result => {
      if (result.isConfirmed) {
        window.location.href = '../../controller/UsuarioController.php?accion=logout';
      }
    });
  } else {
    if (confirm('¿Cerrar sesión?')) {
      window.location.href = '../../controller/UsuarioController.php?accion=logout';
    }
  }
}
</script>
