<?php
/**
 * view/admin/admin_layout.php
 * Layout reutilizable para todas las páginas del admin.
 * Incluir al inicio de cada vista admin:
 *   $pageTitle = 'Mi Página';
 *   $activeMenu = 'dashboard'; // dashboard|pedidos|productos|categorias|usuarios
 *   include 'admin_layout.php';
 * Y al final: include 'admin_layout_end.php';
 */
require_once __DIR__ . '/../../helpers/Seguridad.php';
require_once __DIR__ . '/../../helpers/CabecerasSeguridad.php';
Seguridad::iniciarSesionSegura();
CabecerasSeguridad::aplicar();
if (!Seguridad::esAdmin()) {
    header('Location: ../cliente/login.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> – Rápido & Rico</title>
  <!-- SRI: mitiga "Falta atributo de integridad de recursos secundarios" (Medio) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
        integrity="sha384-tViUnnbplMdV8hCP1rkIBnNSPTXv4YFRiYd3SuEerKjMIfFahanIe9dYmFWOOE/n"
        crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Nunito:wght@400;600;700&display=swap"
        rel="stylesheet" crossorigin="anonymous"/>
  <link href="../../css/style.css" rel="stylesheet"/>
  <link href="../../css/admin.css" rel="stylesheet"/>
</head>
<body class="admin-body">

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<div class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <div class="brand-logo">R</div>
    <span>Rápido &amp; Rico</span>
  </div>
  <nav class="sidebar-nav">
    <?php
    $menu = [
      ['href'=>'dashboard.php',  'icon'=>'bi-speedometer2', 'label'=>'Dashboard',   'key'=>'dashboard'],
      ['href'=>'pedidos.php',    'icon'=>'bi-bag-check',    'label'=>'Pedidos',     'key'=>'pedidos'],
      ['href'=>'productos.php',  'icon'=>'bi-grid',         'label'=>'Productos',   'key'=>'productos'],
      ['href'=>'categorias.php', 'icon'=>'bi-tags',         'label'=>'Categorías',  'key'=>'categorias'],
      ['href'=>'usuarios.php',   'icon'=>'bi-people',       'label'=>'Usuarios',    'key'=>'usuarios'],
    ];
    $active = $activeMenu ?? '';
    foreach ($menu as $m):
    ?>
    <a href="<?= $m['href'] ?>" class="sidebar-link <?= $active===$m['key']?'active':'' ?>">
      <i class="bi <?= $m['icon'] ?>"></i> <?= $m['label'] ?>
    </a>
    <?php endforeach; ?>
    <hr class="sidebar-divider">
    <a href="../../index.php" class="sidebar-link"><i class="bi bi-house"></i> Ver tienda</a>
    <a href="../../controller/UsuarioController.php?accion=logout" class="sidebar-link text-danger">
      <i class="bi bi-box-arrow-right"></i> Cerrar sesión
    </a>
  </nav>
</div>

<!-- MAIN -->
<div class="admin-main">
  <div class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
      <h5 class="mb-0 fw-800"><?= htmlspecialchars($pageTitle ?? 'Panel Admin') ?></h5>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="text-muted small d-none d-sm-inline">
        <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['nombre'] ?? '') ?>
      </span>
    </div>
  </div>
  <div class="admin-content">

  <?php if (!empty($_SESSION['exito'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['exito']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['exito']); endif; ?>

  <?php if (!empty($_SESSION['errores'])): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <?php foreach($_SESSION['errores'] as $e) echo htmlspecialchars($e).'<br>'; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['errores']); endif; ?>
