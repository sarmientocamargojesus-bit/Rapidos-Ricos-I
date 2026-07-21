<?php
/**
 * view/layouts/header.php — Header reutilizable v2.0
 *
 * PRINCIPIO SRP: Solo renderiza la navegación principal y alertas globales.
 * DRY: incluido en TODAS las vistas cliente, eliminando duplicación.
 *
 * MEJORAS v2.0:
 *   - Seguridad::e() en todas las salidas de sesión (XSS).
 *   - Badge del carrito siempre visible si hay items.
 *   - Link a mis_pedidos para usuarios autenticados.
 *   - Menú dropdown para usuario autenticado.
 */

require_once __DIR__ . '/../../helpers/Seguridad.php';
require_once __DIR__ . '/../../helpers/CabecerasSeguridad.php';
Seguridad::iniciarSesionSegura();
CabecerasSeguridad::aplicar();

$carrito_qty = array_sum(array_column($_SESSION['carrito'] ?? [], 'cantidad'));
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Rápido & Rico – Comida rápida y deliciosa en Ica"/>
  <title><?= Seguridad::e($pageTitle ?? 'Rápido & Rico') ?></title>

  <!-- Bootstrap 5 — SRI mitiga "Falta atributo de integridad de recursos secundarios" (Medio) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"/>
  <!-- Bootstrap Icons — SRI -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
        integrity="sha384-tViUnnbplMdV8hCP1rkIBnNSPTXv4YFRiYd3SuEerKjMIfFahanIe9dYmFWOOE/n"
        crossorigin="anonymous"/>
  <!-- Google Fonts — crossorigin para política de privacidad -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Nunito:wght@400;500;600;700&display=swap"
        rel="stylesheet"
        crossorigin="anonymous"/>
  <!-- Estilos globales -->
  <link href="../../css/style.css" rel="stylesheet"/>
</head>
<body>

<!-- ══ NAVBAR ════════════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg">
  <div class="container">

    <!-- Brand -->
    <a class="navbar-brand" href="../../index.php">
      <div class="brand-logo">R</div>
      Rápido <span>&amp; Rico</span>
    </a>

    <!-- Toggle mobile -->
    <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMenu"
            aria-controls="navMenu" aria-expanded="false" aria-label="Menú">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Links + iconos -->
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav mx-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= $paginaActual === 'index.php' ? 'active' : '' ?>"
             href="../../index.php">Inicio</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $paginaActual === 'menu.php' ? 'active' : '' ?>"
             href="menu.php">Menú</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../../index.php#combos">Combos</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../../index.php#nosotros">Nosotros</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="../../index.php#contacto">Contacto</a>
        </li>
      </ul>

      <!-- Iconos de navegación -->
      <div class="navbar-icons d-flex align-items-center gap-1">

        <!-- Búsqueda -->
        <a href="menu.php" class="btn-icon" title="Buscar en el menú">
          <i class="bi bi-search"></i>
        </a>

        <?php if (Seguridad::estaAutenticado()): ?>

          <!-- Mis pedidos -->
          <a href="mis_pedidos.php" class="btn-icon" title="Mis pedidos">
            <i class="bi bi-clock-history"></i>
          </a>

          <!-- Dropdown usuario -->
          <div class="dropdown">
            <button class="btn-icon dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false"
                    style="font-size:.82rem;gap:.2rem;width:auto;padding:0 .6rem;border-radius:20px"
                    title="Mi cuenta">
              <span class="user-nav-avatar">
                <?= strtoupper(mb_substr($_SESSION['nombre'] ?? 'U', 0, 1, 'UTF-8')) ?>
              </span>
              <span class="d-none d-lg-inline fw-700 small">
                <?= Seguridad::e(explode(' ', $_SESSION['nombre'] ?? '')[0]) ?>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 border-0 py-1" style="min-width:180px">
              <li>
                <div class="dropdown-item-text py-2 px-3 border-bottom mb-1">
                  <div class="fw-700 small"><?= Seguridad::e($_SESSION['nombre'] ?? '') ?></div>
                  <div class="text-muted" style="font-size:.75rem"><?= Seguridad::e($_SESSION['correo'] ?? '') ?></div>
                </div>
              </li>
              <?php if (Seguridad::esAdmin()): ?>
              <li>
                <a class="dropdown-item py-2" href="../../view/admin/dashboard.php">
                  <i class="bi bi-speedometer2 me-2 text-danger"></i>Panel admin
                </a>
              </li>
              <?php endif; ?>
              <li>
                <a class="dropdown-item py-2" href="mis_pedidos.php">
                  <i class="bi bi-bag me-2 text-muted"></i>Mis pedidos
                </a>
              </li>
              <li><hr class="dropdown-divider my-1"></li>
              <li>
                <a class="dropdown-item py-2 text-danger" href="../../controller/UsuarioController.php?accion=logout"
                   onclick="return confirm('¿Cerrar sesión?')">
                  <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                </a>
              </li>
            </ul>
          </div>

        <?php else: ?>

          <!-- Botón login -->
          <a href="login.php" class="btn-icon" title="Iniciar sesión">
            <i class="bi bi-person"></i>
          </a>

        <?php endif; ?>

        <!-- Carrito -->
        <a href="carrito.php" class="btn-icon" title="Mi carrito"
           style="position:relative">
          <i class="bi bi-bag"></i>
          <?php if ($carrito_qty > 0): ?>
          <span class="cart-badge"><?= $carrito_qty ?></span>
          <?php endif; ?>
        </a>

      </div>
    </div>
  </div>
</nav>

<!-- ══ ALERTAS GLOBALES (mensajes de sesión) ══════════════════════════════════ -->
<?php if (!empty($_SESSION['exito'])): ?>
<div class="alert alert-success alert-dismissible fade show m-0 rounded-0 py-2" role="alert">
  <div class="container">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?= Seguridad::e($_SESSION['exito']) ?>
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php unset($_SESSION['exito']); endif; ?>

<?php if (!empty($_SESSION['errores'])): ?>
<div class="alert alert-danger alert-dismissible fade show m-0 rounded-0 py-2" role="alert">
  <div class="container">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?php foreach ((array)$_SESSION['errores'] as $e): ?>
    <?= Seguridad::e($e) ?><br>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php unset($_SESSION['errores']); endif; ?>

<style>
/* ── Avatar del navbar ─────────────────────── */
.user-nav-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--rr-red, #E8192C); color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Poppins', sans-serif; font-weight: 800; font-size: .78rem;
  flex-shrink: 0;
}
.dropdown-toggle::after { display: none; }
</style>
