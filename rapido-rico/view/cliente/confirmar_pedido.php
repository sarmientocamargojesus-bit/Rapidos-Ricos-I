<?php
/**
 * view/cliente/confirmar_pedido.php — Confirmación de pedido v2.0
 *
 * PRINCIPIO SRP: Solo presenta el formulario de confirmación.
 *                La validación de pago es responsabilidad de los servicios.
 *
 * MEJORAS v2.0:
 *   - CSRF token en el formulario principal.
 *   - Total recalculado desde constante DELIVERY_FEE (no hardcoded).
 *   - Seguridad::e() en todas las salidas de sesión.
 *   - Seguridad::campoCSRF() para el form.
 */

require_once __DIR__ . '/../../helpers/Seguridad.php';
require_once __DIR__ . '/../../config/app.php';

Seguridad::iniciarSesionSegura();

if (!Seguridad::estaAutenticado()) {
    header('Location: login.php');
    exit;
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header('Location: menu.php');
    exit;
}

// ── Calcular totales en servidor (no confiar en POST anterior) ────────────────
$subtotal = array_reduce(
    $carrito,
    fn($sum, $i) => $sum + ((float)$i['precio'] * (int)$i['cantidad']),
    0.0
);
$delivery = DELIVERY_FEE;
$total    = round($subtotal + $delivery, 2);

$errores = $_SESSION['errores'] ?? [];
unset($_SESSION['errores']);

$pageTitle = 'Confirmar Pedido – Rápido & Rico';
include '../layouts/header.php';
?>

<style>
/* ── Método de pago ─────────────────────────────────── */
.pay-method-wrap { display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1.25rem; }
.pay-btn {
  flex:1; min-width:120px; cursor:pointer;
  border:2px solid #e0e0e0; border-radius:12px; background:#fff;
  padding:.8rem 1rem; display:flex; flex-direction:column;
  align-items:center; gap:.35rem;
  transition:border-color .2s, box-shadow .2s, transform .15s;
  position:relative;
}
.pay-btn:hover { border-color:var(--rr-red); transform:translateY(-2px); }
.pay-btn.active { border-color:var(--rr-red); box-shadow:0 0 0 3px rgba(232,25,44,.15); }
.pay-btn .pay-icon  { font-size:1.7rem; }
.pay-btn .pay-label { font-size:.78rem; font-weight:700; font-family:'Poppins',sans-serif; }
.pay-btn .pay-check {
  position:absolute; top:6px; right:8px; width:18px; height:18px; border-radius:50%;
  background:var(--rr-red); color:#fff; font-size:.65rem;
  display:none; align-items:center; justify-content:center;
}
.pay-btn.active .pay-check { display:flex; }
.pay-btn.yape-btn.active { border-color:#6b21a8; box-shadow:0 0 0 3px rgba(107,33,168,.15); }
.pay-btn.yape-btn .pay-check { background:#6b21a8; }

.pay-panel { display:none; animation:fadeIn .25s ease; }
.pay-panel.show { display:block; }
@keyframes fadeIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:translateY(0)} }

.bank-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:.6rem; margin-bottom:1rem; }
.bank-opt {
  cursor:pointer; border:2px solid #e0e0e0; border-radius:10px;
  padding:.55rem .7rem; display:flex; align-items:center; gap:.5rem;
  transition:border-color .2s, background .2s; font-size:.82rem; font-weight:700; font-family:'Poppins',sans-serif;
}
.bank-opt:hover { border-color:var(--rr-red); }
.bank-opt.selected { border-color:var(--rr-red); background:rgba(232,25,44,.05); }
.bank-dot { width:26px; height:26px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:900; color:#fff; flex-shrink:0; }
.bank-bcp  { background:#003f8a; } .bank-ibn { background:#00853e; }
.bank-bbva { background:#004a97; } .bank-scot { background:#ec111a; }
.bank-otro { background:#555; }

.rr-input { border-radius:9px!important; border:2px solid #e0e0e0!important; font-weight:600!important; }
.rr-input:focus { border-color:var(--rr-red)!important; box-shadow:0 0 0 3px rgba(232,25,44,.12)!important; }

.card-preview {
  width:100%; max-width:300px; height:160px; border-radius:16px;
  background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);
  padding:1.2rem 1.4rem; color:#fff; position:relative;
  box-shadow:0 8px 30px rgba(0,0,0,.3); font-family:'Courier New',monospace; margin-bottom:1rem;
}
.card-preview::before { content:''; position:absolute; top:20px; right:20px; width:36px; height:36px; border-radius:50%; background:rgba(255,200,0,.35); }
.card-preview::after  { content:''; position:absolute; top:20px; right:40px; width:36px; height:36px; border-radius:50%; background:rgba(255,100,0,.45); }
.card-chip  { width:34px; height:26px; background:linear-gradient(135deg,#d4af37,#f5d66e); border-radius:5px; margin-bottom:.8rem; }
.card-num   { font-size:.95rem; letter-spacing:.12em; font-weight:600; margin-bottom:.6rem; }
.card-bot   { display:flex; justify-content:space-between; font-size:.72rem; opacity:.8; }
.yape-header { background:linear-gradient(135deg,#6b21a8,#9333ea); border-radius:12px; padding:1rem 1.2rem; display:flex; align-items:center; gap:.8rem; margin-bottom:1rem; color:#fff; }
.yape-circle { width:44px; height:44px; background:rgba(255,255,255,.18); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
.ssl-badge { display:flex; align-items:center; gap:.4rem; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:.45rem .8rem; font-size:.75rem; font-weight:700; color:#15803d; margin-top:.8rem; }
</style>

<section style="padding:2rem 0 5rem;background:var(--rr-gray,#F4F6FA)">
  <div class="container">

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
        <li class="breadcrumb-item"><a href="carrito.php">Carrito</a></li>
        <li class="breadcrumb-item active">Confirmar pedido</li>
      </ol>
    </nav>

    <h2 class="section-title mb-4">Confirmar <span>pedido</span></h2>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger rounded-4 mb-4">
      <ul class="mb-0 ps-3">
        <?php foreach ($errores as $e): ?>
        <li><?= Seguridad::e($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form action="../../controller/PedidoController.php" method="POST" id="formPedido" novalidate>
      <?= Seguridad::campoCSRF() ?>
      <input type="hidden" name="accion"      value="confirmar">
      <input type="hidden" name="total"       value="<?= $total ?>">
      <input type="hidden" name="metodo_pago" id="metodo_pago_hidden" value="">

      <div class="row g-4">

        <!-- ══ Izquierda: entrega + pago ════════════════════════════════════ -->
        <div class="col-lg-7">

          <!-- Datos de entrega -->
          <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h5 class="fw-800 mb-3" style="font-family:'Poppins',sans-serif">
              <i class="bi bi-truck me-2 text-danger"></i>Datos de entrega
            </h5>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-700 small">Cliente</label>
                <input type="text" class="form-control rr-input"
                       value="<?= Seguridad::e($_SESSION['nombre'] ?? '') ?>" readonly>
              </div>
              <div class="col-12">
                <label class="form-label fw-700 small">Dirección de entrega *</label>
                <input type="text" name="direccion" class="form-control rr-input"
                       placeholder="Av. Los Maestros 121 – Ica"
                       minlength="10" maxlength="255" required>
              </div>
              <div class="col-12">
                <label class="form-label fw-700 small">
                  Referencia <span class="text-muted">(opcional)</span>
                </label>
                <input type="text" name="referencia" class="form-control rr-input"
                       placeholder="Frente al parque, puerta azul...">
              </div>
              <div class="col-12">
                <label class="form-label fw-700 small">Teléfono de contacto *</label>
                <div class="input-group">
                  <span class="input-group-text" style="background:#f5f5f5;border:2px solid #e0e0e0;border-right:0;font-weight:700">+51</span>
                  <input type="tel" name="telefono" class="form-control rr-input"
                         style="border-left:0!important"
                         placeholder="987 654 321" maxlength="9" required
                         oninput="this.value=this.value.replace(/\D/g,'')">
                </div>
              </div>
            </div>
          </div>

          <!-- Método de pago -->
          <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-800 mb-3" style="font-family:'Poppins',sans-serif">
              <i class="bi bi-credit-card me-2 text-danger"></i>Método de pago
            </h5>

            <div class="pay-method-wrap">
              <button type="button" class="pay-btn" id="btn-tarjeta" onclick="selectMetodo('tarjeta')">
                <span class="pay-icon">💳</span>
                <span class="pay-label">Tarjeta</span>
                <span class="pay-check"><i class="bi bi-check"></i></span>
              </button>
              <button type="button" class="pay-btn yape-btn" id="btn-yape" onclick="selectMetodo('yape')">
                <span class="pay-icon">📱</span>
                <span class="pay-label">Yape</span>
                <span class="pay-check"><i class="bi bi-check"></i></span>
              </button>
            </div>

            <!-- ── Panel Tarjeta ─────────────────────────────────────────── -->
            <div class="pay-panel" id="panel-tarjeta">
              <div class="card-preview">
                <div class="card-chip"></div>
                <div class="card-num" id="previewNum">•••• •••• •••• ••••</div>
                <div class="card-bot">
                  <div>
                    <div style="font-size:.6rem;opacity:.6">TITULAR</div>
                    <div id="previewNombre" style="font-size:.78rem;font-weight:600;text-transform:uppercase">NOMBRE APELLIDO</div>
                  </div>
                  <div style="text-align:right">
                    <div style="font-size:.6rem;opacity:.6">CVV</div>
                    <div id="previewCvv" style="font-size:.78rem;font-weight:600">•••</div>
                  </div>
                </div>
              </div>

              <label class="form-label fw-700 small mb-2">Banco *</label>
              <div class="bank-grid">
                <div class="bank-opt" onclick="selectBanco('BCP',this)">
                  <div class="bank-dot bank-bcp">BCP</div>BCP
                </div>
                <div class="bank-opt" onclick="selectBanco('Interbank',this)">
                  <div class="bank-dot bank-ibn">IB</div>Interbank
                </div>
                <div class="bank-opt" onclick="selectBanco('BBVA',this)">
                  <div class="bank-dot bank-bbva">BB</div>BBVA
                </div>
                <div class="bank-opt" onclick="selectBanco('Scotiabank',this)">
                  <div class="bank-dot bank-scot">SB</div>Scotiabank
                </div>
                <div class="bank-opt" onclick="selectBanco('Otro banco',this)" style="grid-column:1/-1">
                  <div class="bank-dot bank-otro"><i class="bi bi-bank"></i></div>Otro banco
                </div>
              </div>
              <input type="hidden" name="banco" id="banco_hidden">

              <div class="row g-3 mt-1">
                <div class="col-12">
                  <label class="form-label fw-700 small">Número de tarjeta *</label>
                  <input type="text" name="num_tarjeta" id="numTarjeta"
                         class="form-control rr-input" maxlength="19"
                         placeholder="0000 0000 0000 0000"
                         oninput="formatCardNum(this)" autocomplete="cc-number">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-700 small">CVV *</label>
                  <input type="password" name="cvv" id="cvvInput"
                         class="form-control rr-input" maxlength="4"
                         placeholder="•••" oninput="updatePreview()" autocomplete="cc-csc">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-700 small">DNI *</label>
                  <input type="text" name="dni_tarjeta" class="form-control rr-input"
                         maxlength="8" placeholder="12345678"
                         oninput="this.value=this.value.replace(/\D/g,'')">
                </div>
                <div class="col-12">
                  <label class="form-label fw-700 small">Titular *</label>
                  <input type="text" name="titular_tarjeta" id="titularInput"
                         class="form-control rr-input" placeholder="Juan Pérez García"
                         oninput="updatePreview()">
                </div>
              </div>
              <div class="ssl-badge">
                <i class="bi bi-shield-lock-fill"></i>Pago 100 % seguro — cifrado SSL
              </div>
            </div>

            <!-- ── Panel Yape ────────────────────────────────────────────── -->
            <div class="pay-panel" id="panel-yape">
              <div class="yape-header">
                <div class="yape-circle">📲</div>
                <div>
                  <div style="font-weight:800;font-family:'Poppins',sans-serif;font-size:.95rem">Paga con Yape</div>
                  <div style="font-size:.78rem;opacity:.85">Rápido, seguro y sin comisiones</div>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-12">
                  <label class="form-label fw-700 small">Número Yape *</label>
                  <div class="input-group">
                    <span class="input-group-text" style="background:#f5f5f5;border:2px solid #e0e0e0;border-right:0;font-weight:700">+51</span>
                    <input type="tel" name="yape_telefono" class="form-control rr-input"
                           style="border-left:0!important" maxlength="9" placeholder="987 654 321"
                           oninput="this.value=this.value.replace(/\D/g,'')">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-700 small">DNI *</label>
                  <input type="text" name="yape_dni" class="form-control rr-input"
                         maxlength="8" placeholder="12345678"
                         oninput="this.value=this.value.replace(/\D/g,'')">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-700 small">Nombre completo *</label>
                  <input type="text" name="yape_nombres" class="form-control rr-input" placeholder="Juan Pérez">
                </div>
                <div class="col-12">
                  <label class="form-label fw-700 small">Código Yape (6 dígitos) *</label>
                  <input type="text" name="yape_codigo"
                         class="form-control rr-input text-center fw-800"
                         maxlength="6" placeholder="— — — — — —"
                         style="letter-spacing:.4em;font-size:1.1rem"
                         oninput="this.value=this.value.replace(/\D/g,'')">
                  <div class="form-text">Yape → <strong>Pagar con código</strong> → código de 6 dígitos</div>
                </div>
              </div>
              <div class="ssl-badge">
                <i class="bi bi-shield-lock-fill"></i>Yape usa cifrado de extremo a extremo
              </div>
            </div>
          </div>
        </div>

        <!-- ══ Derecha: resumen ════════════════════════════════════════════ -->
        <div class="col-lg-5">
          <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:80px">
            <h5 class="fw-800 mb-3" style="font-family:'Poppins',sans-serif">Resumen del pedido</h5>

            <?php foreach ($carrito as $item): ?>
            <div class="d-flex justify-content-between mb-2 small">
              <span><?= Seguridad::e($item['nombre']) ?> ×<?= (int)$item['cantidad'] ?></span>
              <span class="fw-600">S/ <?= number_format((float)$item['precio'] * (int)$item['cantidad'], 2) ?></span>
            </div>
            <?php endforeach; ?>

            <hr>
            <div class="d-flex justify-content-between mb-1 small">
              <span class="text-muted">Subtotal</span>
              <span>S/ <?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3 small">
              <span class="text-muted">Delivery</span>
              <span>S/ <?= number_format($delivery, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-4">
              <span class="fw-800" style="font-family:'Poppins',sans-serif">Total</span>
              <span class="fw-800 text-danger fs-5">S/ <?= number_format($total, 2) ?></span>
            </div>

            <div id="metodoBadge" class="text-center mb-3" style="display:none">
              <span class="badge rounded-pill px-3 py-2" id="badgeText" style="font-size:.8rem"></span>
            </div>

            <button type="submit" class="btn-rr w-100 justify-content-center">
              <i class="bi bi-check-circle-fill"></i>Confirmar pedido
            </button>
            <a href="carrito.php" class="btn-rr-outline w-100 justify-content-center mt-2">
              <i class="bi bi-arrow-left"></i>Volver al carrito
            </a>
          </div>
        </div>

      </div>
    </form>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let metodoActivo = '', bancoActivo = '';

function selectMetodo(m) {
  metodoActivo = m;
  document.getElementById('metodo_pago_hidden').value = m;
  document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.pay-panel').forEach(p => p.classList.remove('show'));
  
  const btn = document.getElementById('btn-' + m);
  const panel = document.getElementById('panel-' + m);
  if (btn) btn.classList.add('active');
  if (panel) panel.classList.add('show');

  const badge = document.getElementById('metodoBadge');
  const bt    = document.getElementById('badgeText');
  if (badge && bt) {
    badge.style.display = 'block';
    if (m === 'tarjeta') {
      bt.style.cssText = 'background:#1a1a2e;color:#fff';
      bt.textContent   = '💳 Pago con tarjeta';
    } else {
      bt.style.cssText = 'background:#6b21a8;color:#fff';
      bt.textContent   = '📲 Pago con Yape';
    }
  }
}

function selectBanco(nombre, el) {
  bancoActivo = nombre;
  document.getElementById('banco_hidden').value = nombre;
  document.querySelectorAll('.bank-opt').forEach(b => b.classList.remove('selected'));
  if (el) el.classList.add('selected');
}

function formatCardNum(input) {
  let v = input.value.replace(/\D/g, '').substring(0, 16);
  input.value = v.replace(/(.{4})/g, '$1 ').trim();
  const d = v.padEnd(16, '•');
  const preview = document.getElementById('previewNum');
  if (preview) {
    preview.textContent = d.substring(0,4) + ' •••• •••• ' + d.substring(12,16);
  }
}

function updatePreview() {
  const cvv    = document.getElementById('cvvInput').value;
  const nombre = document.getElementById('titularInput').value || 'NOMBRE APELLIDO';
  const previewCvv = document.getElementById('previewCvv');
  const previewNombre = document.getElementById('previewNombre');
  if (previewCvv) previewCvv.textContent = cvv ? '•'.repeat(cvv.length) : '•••';
  if (previewNombre) previewNombre.textContent = nombre.toUpperCase().substring(0, 22);
}

// Inicializar método 'tarjeta' y banco 'BCP' al cargar la página
document.addEventListener('DOMContentLoaded', function() {
  selectMetodo('tarjeta');
  const firstBank = document.querySelector('.bank-opt');
  if (firstBank) {
    selectBanco('BCP', firstBank);
  }
});

document.getElementById('formPedido').addEventListener('submit', function(e) {
  const dir = document.querySelector('input[name="direccion"]').value.trim();
  const tel = document.querySelector('input[name="telefono"]').value.trim();
  if (!dir) {
    e.preventDefault();
    alert('Por favor ingresa la dirección de entrega.');
    document.querySelector('input[name="direccion"]').focus();
    return;
  }
  if (!tel || tel.length < 9) {
    e.preventDefault();
    alert('Por favor ingresa un teléfono de contacto válido (9 dígitos).');
    document.querySelector('input[name="telefono"]').focus();
    return;
  }

  if (!metodoActivo) {
    e.preventDefault();
    alert('Por favor selecciona un método de pago (Tarjeta o Yape).');
    return;
  }

  if (metodoActivo === 'tarjeta') {
    if (!bancoActivo) {
      e.preventDefault();
      alert('Por favor selecciona tu banco.');
      return;
    }
    const num = document.getElementById('numTarjeta').value.replace(/\s+/g, '');
    if (num.length < 15) {
      e.preventDefault();
      alert('Ingresa un número de tarjeta válido (15 o 16 dígitos).');
      document.getElementById('numTarjeta').focus();
      return;
    }
    const cvv = document.getElementById('cvvInput').value.trim();
    if (cvv.length < 3) {
      e.preventDefault();
      alert('Ingresa un CVV válido (3 o 4 dígitos).');
      document.getElementById('cvvInput').focus();
      return;
    }
    const dni = document.querySelector('input[name="dni_tarjeta"]').value.trim();
    if (dni.length !== 8) {
      e.preventDefault();
      alert('El DNI del titular debe tener exactamente 8 dígitos.');
      document.querySelector('input[name="dni_tarjeta"]').focus();
      return;
    }
    const titular = document.getElementById('titularInput').value.trim();
    if (!titular) {
      e.preventDefault();
      alert('Ingresa el nombre del titular de la tarjeta.');
      document.getElementById('titularInput').focus();
      return;
    }
  } else if (metodoActivo === 'yape') {
    const yapeTel = document.querySelector('input[name="yape_telefono"]').value.trim();
    if (yapeTel.length !== 9) {
      e.preventDefault();
      alert('El número de celular Yape debe tener 9 dígitos.');
      document.querySelector('input[name="yape_telefono"]').focus();
      return;
    }
    const yapeDni = document.querySelector('input[name="yape_dni"]').value.trim();
    if (yapeDni.length !== 8) {
      e.preventDefault();
      alert('El DNI debe tener exactamente 8 dígitos.');
      document.querySelector('input[name="yape_dni"]').focus();
      return;
    }
    const yapeNom = document.querySelector('input[name="yape_nombres"]').value.trim();
    if (!yapeNom) {
      e.preventDefault();
      alert('Ingresa el nombre completo del titular de Yape.');
      document.querySelector('input[name="yape_nombres"]').focus();
      return;
    }
    const yapeCod = document.querySelector('input[name="yape_codigo"]').value.trim();
    if (yapeCod.length !== 6) {
      e.preventDefault();
      alert('El código Yape debe tener 6 dígitos.');
      document.querySelector('input[name="yape_codigo"]').focus();
      return;
    }
  }
});
</script>

<?php include '../layouts/footer.php'; ?>
