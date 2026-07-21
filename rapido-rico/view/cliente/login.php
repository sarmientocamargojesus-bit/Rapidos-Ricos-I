<?php
/**
 * view/cliente/login.php — Formulario de Login v2.0
 * Mejoras: CSRF token, validación HTML5, diseño moderno.
 */

require_once __DIR__ . '/../../helpers/Seguridad.php';
Seguridad::iniciarSesionSegura();

// Redirigir si ya está autenticado
if (Seguridad::estaAutenticado()) {
    $ruta = Seguridad::esAdmin() ? '../admin/dashboard.php' : 'menu.php';
    header('Location: ' . $ruta);
    exit;
}

$errores = $_SESSION['errores'] ?? [];
$exito   = $_SESSION['exito']   ?? '';
unset($_SESSION['errores'], $_SESSION['exito']);

$pageTitle = 'Iniciar sesión – Rápido & Rico';
include '../layouts/header.php';
?>

<section class="auth-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">

        <div class="auth-card">

          <!-- Header -->
          <div class="auth-header">
            <div class="brand-logo mx-auto mb-3" style="width:56px;height:56px;font-size:1.3rem">R</div>
            <h3 class="auth-title">Bienvenido</h3>
            <p class="auth-subtitle">Inicia sesión en tu cuenta</p>
          </div>

          <!-- Alertas -->
          <?php if (!empty($errores)): ?>
          <div class="alert alert-danger rounded-3 small mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php foreach ($errores as $e): ?><?= Seguridad::e($e) ?><br><?php endforeach; ?>
          </div>
          <?php endif; ?>

          <?php if ($exito): ?>
          <div class="alert alert-success rounded-3 small mb-3">
            <i class="bi bi-check-circle-fill me-2"></i><?= Seguridad::e($exito) ?>
          </div>
          <?php endif; ?>

          <!-- Formulario -->
          <form action="../../controller/UsuarioController.php" method="POST" novalidate id="formLogin">
            <input type="hidden" name="accion" value="login">
            <?= Seguridad::campoCSRF() ?>

            <div class="mb-3">
              <label class="form-label fw-700 small">Correo electrónico</label>
              <div class="input-group-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" name="correo" class="form-control auth-input"
                       placeholder="usuario@correo.com"
                       autocomplete="email" required>
              </div>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <label class="form-label fw-700 small">Contraseña</label>
              </div>
              <div class="input-group-icon">
                <i class="bi bi-lock"></i>
                <input type="password" name="contrasena" id="passInput"
                       class="form-control auth-input"
                       placeholder="••••••••"
                       autocomplete="current-password" required>
                <button type="button" class="btn-eye" onclick="togglePass('passInput','eyeIcon')">
                  <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn-rr w-100 justify-content-center mt-1">
              <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
            </button>
          </form>

          <div class="auth-divider"><span>¿No tienes cuenta?</span></div>

          <a href="registro.php" class="btn-rr-outline w-100 justify-content-center">
            <i class="bi bi-person-plus"></i> Crear cuenta gratis
          </a>

        </div>

      </div>
    </div>
  </div>
</section>

<style>
/* ── Auth styles ──────────────────────────────────────── */
.auth-section { padding: 3rem 0 5rem; background: var(--rr-gray); min-height: calc(100vh - 64px); }
.auth-card { background: #fff; border-radius: 20px; box-shadow: 0 8px 40px rgba(0,0,0,.10); padding: 2.5rem 2rem; }
.auth-header { text-align: center; margin-bottom: 1.8rem; }
.auth-title { font-family: 'Poppins',sans-serif; font-weight: 800; font-size: 1.4rem; margin-bottom: .3rem; }
.auth-subtitle { color: #888; font-size: .88rem; }

.input-group-icon { position: relative; }
.input-group-icon > i:first-child {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  color: #aaa; font-size: 1rem; pointer-events: none;
}
.auth-input {
  padding-left: 2.5rem !important;
  border-radius: 10px !important;
  border: 2px solid #e0e0e0 !important;
  font-weight: 600 !important;
  height: 46px;
}
.auth-input:focus { border-color: var(--rr-red) !important; box-shadow: 0 0 0 3px rgba(232,25,44,.10) !important; outline: none; }

.btn-eye {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: #aaa; cursor: pointer; padding: .2rem;
}
.btn-eye:hover { color: var(--rr-red); }

.auth-divider {
  text-align: center; position: relative; margin: 1.2rem 0;
  color: #ccc; font-size: .8rem;
}
.auth-divider::before, .auth-divider::after {
  content: ''; position: absolute; top: 50%;
  width: 35%; height: 1px; background: #eee;
}
.auth-divider::before { left: 0; }
.auth-divider::after  { right: 0; }
.auth-divider span { background: #fff; padding: 0 .8rem; color: #aaa; }
</style>

<script>
function togglePass(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}
</script>

<?php include '../layouts/footer.php'; ?>
