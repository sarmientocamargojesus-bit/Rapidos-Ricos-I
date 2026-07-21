<?php
/**
 * view/cliente/registro.php — Formulario de Registro v2.0
 * Mejoras: CSRF, validación de fortaleza de contraseña en tiempo real, diseño moderno.
 */

require_once __DIR__ . '/../../helpers/Seguridad.php';
Seguridad::iniciarSesionSegura();

if (Seguridad::estaAutenticado()) { header('Location: menu.php'); exit; }

$errores = $_SESSION['errores'] ?? [];
unset($_SESSION['errores']);

$pageTitle = 'Crear cuenta – Rápido & Rico';
include '../layouts/header.php';
?>

<section class="auth-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="auth-card">

          <div class="auth-header">
            <div class="brand-logo mx-auto mb-3" style="width:56px;height:56px;font-size:1.3rem">R</div>
            <h3 class="auth-title">Crear cuenta</h3>
            <p class="auth-subtitle">Únete a Rápido &amp; Rico</p>
          </div>

          <?php if (!empty($errores)): ?>
          <div class="alert alert-danger rounded-3 small mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <ul class="mb-0 ps-3">
              <?php foreach ($errores as $e): ?>
              <li><?= Seguridad::e($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <form action="../../controller/UsuarioController.php" method="POST" novalidate id="formRegistro">
            <input type="hidden" name="accion" value="registrar">
            <?= Seguridad::campoCSRF() ?>

            <div class="mb-3">
              <label class="form-label fw-700 small">Nombre completo</label>
              <div class="input-group-icon">
                <i class="bi bi-person"></i>
                <input type="text" name="nombre" class="form-control auth-input"
                       placeholder="Juan Pérez García" minlength="3" maxlength="100" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-700 small">Correo electrónico</label>
              <div class="input-group-icon">
                <i class="bi bi-envelope"></i>
                <input type="email" name="correo" class="form-control auth-input"
                       placeholder="usuario@correo.com" autocomplete="email" required>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-700 small">Contraseña</label>
              <div class="input-group-icon">
                <i class="bi bi-lock"></i>
                <input type="password" name="contrasena" id="passNueva"
                       class="form-control auth-input"
                       placeholder="Mínimo 8 caracteres"
                       minlength="8" required oninput="evaluarPass(this.value)">
                <button type="button" class="btn-eye" onclick="togglePass('passNueva','eye1')">
                  <i class="bi bi-eye" id="eye1"></i>
                </button>
              </div>
              <!-- Indicador de fortaleza -->
              <div class="pass-strength mt-2" id="passStrength" style="display:none">
                <div class="pass-bar-wrap">
                  <div class="pass-bar" id="passBar"></div>
                </div>
                <small id="passLabel" class="fw-600"></small>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-700 small">Confirmar contraseña</label>
              <div class="input-group-icon">
                <i class="bi bi-lock-fill"></i>
                <input type="password" name="confirmar" id="passConfirm"
                       class="form-control auth-input"
                       placeholder="Repite tu contraseña" required>
                <button type="button" class="btn-eye" onclick="togglePass('passConfirm','eye2')">
                  <i class="bi bi-eye" id="eye2"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn-rr w-100 justify-content-center">
              <i class="bi bi-person-plus"></i> Crear cuenta
            </button>
          </form>

          <div class="auth-divider"><span>¿Ya tienes cuenta?</span></div>
          <a href="login.php" class="btn-rr-outline w-100 justify-content-center">
            <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
          </a>

        </div>
      </div>
    </div>
  </div>
</section>

<style>
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
.auth-input { padding-left: 2.5rem !important; border-radius: 10px !important; border: 2px solid #e0e0e0 !important; font-weight: 600 !important; height: 46px; }
.auth-input:focus { border-color: var(--rr-red) !important; box-shadow: 0 0 0 3px rgba(232,25,44,.10) !important; outline: none; }

.btn-eye { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #aaa; cursor: pointer; }
.btn-eye:hover { color: var(--rr-red); }

.auth-divider { text-align: center; position: relative; margin: 1.2rem 0; color: #ccc; font-size: .8rem; }
.auth-divider::before, .auth-divider::after { content: ''; position: absolute; top: 50%; width: 35%; height: 1px; background: #eee; }
.auth-divider::before { left: 0; } .auth-divider::after { right: 0; }
.auth-divider span { background: #fff; padding: 0 .8rem; color: #aaa; }

/* Pass strength */
.pass-bar-wrap { height: 6px; background: #eee; border-radius: 3px; margin-bottom: .3rem; overflow: hidden; }
.pass-bar { height: 100%; border-radius: 3px; transition: width .3s, background .3s; }
</style>

<script>
function togglePass(id, iconId) {
  const i = document.getElementById(id);
  const ic = document.getElementById(iconId);
  i.type = i.type === 'password' ? 'text' : 'password';
  ic.className = i.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function evaluarPass(val) {
  const el = document.getElementById('passStrength');
  const bar = document.getElementById('passBar');
  const lbl = document.getElementById('passLabel');
  if (!val) { el.style.display = 'none'; return; }
  el.style.display = 'block';

  let pts = 0;
  if (val.length >= 8)  pts++;
  if (/[A-Z]/.test(val)) pts++;
  if (/[0-9]/.test(val)) pts++;
  if (/[^A-Za-z0-9]/.test(val)) pts++;

  const cfg = [
    { pct: '25%', bg: '#dc3545', txt: 'Débil' },
    { pct: '50%', bg: '#fd7e14', txt: 'Regular' },
    { pct: '75%', bg: '#ffc107', txt: 'Buena' },
    { pct: '100%', bg: '#28a745', txt: 'Fuerte' },
  ];
  const c = cfg[pts - 1] || cfg[0];
  bar.style.width = c.pct;
  bar.style.background = c.bg;
  lbl.textContent = c.txt;
  lbl.style.color = c.bg;
}
</script>

<?php include '../layouts/footer.php'; ?>
