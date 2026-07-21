<?php
/**
 * view/cliente/carrito.php — Carrito de compras v2.0
 *
 * PRINCIPIOS SOLID:
 *   S — SRP: Solo presenta y gestiona el carrito de sesión.
 *             Cálculos de negocio (total, delivery) centralizados en un bloque único.
 *
 * MEJORAS v2.0:
 *   - CSRF en operaciones POST (actualizar, eliminar).
 *   - Sanitización de salidas con Seguridad::e().
 *   - Delivery configurable desde constante DELIVERY_FEE.
 *   - SweetAlert2 para confirmar eliminación.
 *   - Contador de items en badge del carrito dinámico.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../../helpers/Seguridad.php';
require_once __DIR__ . '/../../config/app.php';
Seguridad::iniciarSesionSegura();

// ── Procesar acciones POST (actualizar / eliminar) ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF: proteger modificaciones del carrito
    if (!Seguridad::verificarCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $_SESSION['errores'] = ['Solicitud inválida. Por favor recarga la página.'];
        header('Location: carrito.php');
        exit;
    }

    $accion = Seguridad::texto($_POST['accion'] ?? '');

    if ($accion === 'eliminar') {
        $idx = (int)($_POST['idx'] ?? -1);
        if (isset($_SESSION['carrito'][$idx])) {
            array_splice($_SESSION['carrito'], $idx, 1);
            $_SESSION['carrito'] = array_values($_SESSION['carrito']);
        }
    } elseif ($accion === 'actualizar') {
        foreach ($_POST['cantidad'] ?? [] as $i => $qty) {
            $i   = (int) $i;
            $qty = (int) $qty;
            if ($qty < 1) {
                array_splice($_SESSION['carrito'], $i, 1);
                $_SESSION['carrito'] = array_values($_SESSION['carrito']);
            } else {
                $_SESSION['carrito'][$i]['cantidad'] = min($qty, 20);
            }
        }
    } elseif ($accion === 'vaciar') {
        unset($_SESSION['carrito']);
    }

    header('Location: carrito.php');
    exit;
}

// ── Cálculos de totales ───────────────────────────────────────────────────────
$carrito  = $_SESSION['carrito'] ?? [];
$subtotal = array_reduce($carrito, fn($sum, $i) => $sum + ((float)$i['precio'] * (int)$i['cantidad']), 0.0);
$delivery = $subtotal > 0 ? DELIVERY_FEE : 0.0;
$total    = round($subtotal + $delivery, 2);

$errores = $_SESSION['errores'] ?? [];
unset($_SESSION['errores']);

$stockInsuficiente = $_SESSION['stock_insuficiente'] ?? null;
unset($_SESSION['stock_insuficiente']);

$pageTitle = 'Mi Carrito – Rápido & Rico';
include '../layouts/header.php';
?>

<section style="padding:2rem 0 5rem;background:var(--rr-gray,#F4F6FA);min-height:calc(100vh - 64px)">
  <div class="container">

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
        <li class="breadcrumb-item"><a href="menu.php">Menú</a></li>
        <li class="breadcrumb-item active">Mi Carrito</li>
      </ol>
    </nav>

    <?php if (!empty($errores)): ?>
    <div class="alert alert-danger rounded-4 mb-4">
      <ul class="mb-0 ps-3">
        <?php foreach ($errores as $e): ?>
        <li><?= Seguridad::e($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <h2 class="section-title mb-0">Mi <span>Carrito</span></h2>
      <?php if (!empty($carrito)): ?>
      <button type="button" class="btn-rr-outline text-danger"
              style="border-color:#fca5a5;color:#dc2626!important;font-size:.83rem;padding:.4rem 1rem"
              onclick="confirmarVaciar()">
        <i class="bi bi-trash3 me-1"></i>Vaciar carrito
      </button>
      <?php endif; ?>
    </div>

    <?php if (empty($carrito)): ?>
    <!-- Empty state -->
    <div class="text-center py-5">
      <div style="width:110px;height:110px;background:rgba(232,25,44,.08);border-radius:50%;
                  display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem">
        <i class="bi bi-bag" style="font-size:3.2rem;color:var(--rr-red)"></i>
      </div>
      <h4 class="fw-800" style="font-family:'Poppins',sans-serif">Tu carrito está vacío</h4>
      <p class="text-muted mb-4">¡Agrega productos desde nuestro menú y realiza tu pedido!</p>
      <a href="menu.php" class="btn-rr"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Ver el menú</a>
    </div>

    <?php else: ?>
    <div class="row g-4">

      <!-- ══ Ítems del carrito ═════════════════════════════════════════════ -->
      <div class="col-lg-8">
        <form method="POST" id="formCarrito">
          <?= Seguridad::campoCSRF() ?>
          <input type="hidden" name="accion" value="actualizar">
          <input type="hidden" name="idx"    value="-1" id="idxEliminar">

          <div class="cart-card">
            <?php foreach ($carrito as $i => $item):
              $subtItem = (float)$item['precio'] * (int)$item['cantidad'];
            ?>
            <div class="cart-item <?= $i > 0 ? 'border-top' : '' ?>">

              <!-- Imagen -->
              <div class="cart-img-wrap">
                <img src="../../img/<?= Seguridad::attr($item['imagen'] ?? 'default.jpg') ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&auto=format&fit=crop'"
                     class="cart-img"
                     alt="<?= Seguridad::attr($item['nombre']) ?>">
              </div>

              <!-- Info -->
              <div class="cart-info">
                <div class="cart-name"><?= Seguridad::e($item['nombre']) ?></div>
                <div class="cart-unit">S/ <?= number_format((float)$item['precio'], 2) ?> c/u</div>
              </div>

              <!-- Cantidad -->
              <div class="cart-qty">
                <button type="button" class="qty-btn" onclick="cambiarQty(<?= $i ?>, -1)">−</button>
                <input type="number" name="cantidad[<?= $i ?>]"
                       id="qty_<?= $i ?>"
                       value="<?= (int)$item['cantidad'] ?>"
                       min="1" max="20"
                       class="form-control qty-input text-center fw-700"
                       onchange="actualizarSubtotal(<?= $i ?>, <?= (float)$item['precio'] ?>)">
                <button type="button" class="qty-btn" onclick="cambiarQty(<?= $i ?>, 1, <?= (float)$item['precio'] ?>)">+</button>
              </div>

              <!-- Subtotal -->
              <div class="cart-subt text-end">
                <div class="text-muted" style="font-size:.72rem">Subtotal</div>
                <div class="fw-800 text-danger" id="subt_<?= $i ?>">
                  S/ <?= number_format($subtItem, 2) ?>
                </div>
              </div>

              <!-- Eliminar -->
              <button type="button" class="cart-del"
                      onclick="confirmarEliminar(<?= $i ?>, '<?= Seguridad::attr($item['nombre']) ?>')"
                      title="Eliminar">
                <i class="bi bi-x-lg"></i>
              </button>

            </div>
            <?php endforeach; ?>
          </div>

          <!-- Acciones -->
          <div class="d-flex gap-2 mt-3 flex-wrap">
            <button type="submit" class="btn-rr-outline">
              <i class="bi bi-arrow-clockwise me-1"></i>Actualizar cantidades
            </button>
            <a href="menu.php" class="btn-rr-outline">
              <i class="bi bi-arrow-left me-1"></i>Seguir comprando
            </a>
          </div>
        </form>

        <!-- Formulario oculto para eliminar un ítem -->
        <form id="formEliminar" method="POST" style="display:none">
          <?= Seguridad::campoCSRF() ?>
          <input type="hidden" name="accion" value="eliminar">
          <input type="hidden" name="idx"    id="idxEliminarHidden">
        </form>

        <!-- Formulario oculto para vaciar -->
        <form id="formVaciar" method="POST" style="display:none">
          <?= Seguridad::campoCSRF() ?>
          <input type="hidden" name="accion" value="vaciar">
        </form>
      </div>

      <!-- ══ Resumen del pedido ════════════════════════════════════════════ -->
      <div class="col-lg-4">
        <div class="cart-summary">
          <h5 class="fw-800 mb-4" style="font-family:'Poppins',sans-serif">
            <i class="bi bi-receipt-cutoff text-danger me-2"></i>Resumen
          </h5>

          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Productos (<?= count($carrito) ?>)</span>
            <span class="fw-600" id="resSubtotal">S/ <?= number_format($subtotal, 2) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-3 small">
            <span class="text-muted">
              <i class="bi bi-truck text-danger me-1"></i>Delivery
            </span>
            <span class="fw-600">S/ <?= number_format($delivery, 2) ?></span>
          </div>

          <?php $falta = max(0, 27 - $subtotal); ?>
          <?php if ($falta > 0): ?>
          <div class="delivery-promo mb-3">
            <i class="bi bi-gift-fill me-2"></i>
            Te faltan <strong>S/ <?= number_format($falta, 2) ?></strong> para envío gratis
            <div class="promo-bar mt-2">
              <div class="promo-fill" style="width:<?= min(100, ($subtotal / 27) * 100) ?>%"></div>
            </div>
          </div>
          <?php else: ?>
          <div class="alert alert-success py-2 small rounded-3 mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>¡Envío gratis aplicado!
          </div>
          <?php endif; ?>

          <hr>

          <div class="d-flex justify-content-between mb-4">
            <span class="fw-800" style="font-family:'Poppins',sans-serif">Total</span>
            <span class="fw-800 text-danger fs-5" id="resTotal">
              S/ <?= number_format($total, 2) ?>
            </span>
          </div>

          <?php if (Seguridad::estaAutenticado()): ?>
          <a href="confirmar_pedido.php" class="btn-rr w-100 justify-content-center mb-2">
            <i class="bi bi-credit-card"></i> Proceder al pago
          </a>
          <?php else: ?>
          <a href="login.php" class="btn-rr w-100 justify-content-center mb-2">
            <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión para pagar
          </a>
          <?php endif; ?>

          <a href="menu.php" class="btn-rr-outline w-100 justify-content-center"
             style="font-size:.88rem;padding:.55rem">
            <i class="bi bi-plus-circle me-1"></i>Agregar más productos
          </a>

          <!-- Métodos de pago aceptados -->
          <div class="pay-accept mt-4">
            <div class="text-muted small mb-2" style="font-size:.73rem;letter-spacing:.05em">ACEPTAMOS</div>
            <div class="d-flex gap-2 flex-wrap">
              <span class="pay-chip">💳 Tarjeta</span>
              <span class="pay-chip" style="background:rgba(107,33,168,.08);color:#6b21a8">📱 Yape</span>
            </div>
          </div>

        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<style>
/* ── Cart card ────────────────────────────────────────── */
.cart-card {
  background: #fff; border-radius: 18px;
  box-shadow: 0 2px 20px rgba(0,0,0,.07); overflow: hidden;
}
.cart-item {
  display: flex; align-items: center; gap: 1rem;
  padding: 1rem 1.2rem; border-color: #F5F5F5 !important;
  transition: background .2s;
}
.cart-item:hover { background: #FAFAFA; }

.cart-img-wrap { flex-shrink: 0; }
.cart-img { width: 72px; height: 72px; object-fit: cover; border-radius: 12px; }

.cart-info  { flex: 1; min-width: 0; }
.cart-name  { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cart-unit  { font-size: .78rem; color: #888; margin-top: .15rem; }

.cart-qty   { display: flex; align-items: center; gap: .4rem; flex-shrink: 0; }
.qty-btn    {
  width: 30px; height: 30px; border-radius: 8px; border: 2px solid #E0E0E0;
  background: #fff; font-size: 1rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: border-color .2s, background .2s;
}
.qty-btn:hover  { border-color: var(--rr-red); background: var(--rr-red); color: #fff; }
.qty-input      { width: 50px; height: 30px; padding: 0; border: 2px solid #E0E0E0; border-radius: 8px; font-size: .88rem; }
.qty-input:focus{ border-color: var(--rr-red); box-shadow: none; }

.cart-subt  { flex-shrink: 0; min-width: 70px; }

.cart-del   {
  background: none; border: none; color: #ccc; font-size: 1.05rem;
  cursor: pointer; padding: .35rem; border-radius: 8px; flex-shrink: 0;
  transition: color .2s, background .2s;
}
.cart-del:hover { color: var(--rr-red); background: rgba(232,25,44,.08); }

/* ── Summary card ─────────────────────────────────────── */
.cart-summary {
  background: #fff; border-radius: 18px;
  box-shadow: 0 2px 20px rgba(0,0,0,.07);
  padding: 1.6rem; position: sticky; top: 80px;
}

/* ── Delivery promo bar ───────────────────────────────── */
.delivery-promo {
  background: rgba(232,25,44,.05); border-radius: 10px;
  padding: .6rem .9rem; font-size: .8rem; font-weight: 600; color: #555;
}
.promo-bar  { height: 5px; background: #E0E0E0; border-radius: 3px; overflow: hidden; }
.promo-fill { height: 100%; background: var(--rr-red); border-radius: 3px; transition: width .4s; }

/* ── Pay chips ────────────────────────────────────────── */
.pay-chip {
  background: rgba(232,25,44,.07); color: var(--rr-red);
  border-radius: 8px; padding: .3rem .75rem; font-size: .78rem; font-weight: 700;
}

@media (max-width: 480px) {
  .cart-img  { width: 54px; height: 54px; }
  .cart-subt { display: none; }
  .cart-item { gap: .6rem; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const DELIVERY = <?= DELIVERY_FEE ?>;

/** Cambia la cantidad de un ítem y actualiza el subtotal visual. */
function cambiarQty(idx, delta, precio) {
  const input = document.getElementById('qty_' + idx);
  const val   = Math.min(20, Math.max(1, parseInt(input.value) + delta));
  input.value = val;
  if (precio) actualizarSubtotal(idx, precio);
}

/** Actualiza el subtotal visual del ítem y el total del resumen. */
function actualizarSubtotal(idx, precio) {
  const qty  = parseInt(document.getElementById('qty_' + idx).value);
  const subt = document.getElementById('subt_' + idx);
  if (subt) subt.textContent = 'S/ ' + (precio * qty).toFixed(2);
  // Recalcular total del resumen
  let suma = 0;
  document.querySelectorAll('[id^="subt_"]').forEach(el => {
    suma += parseFloat(el.textContent.replace('S/ ', ''));
  });
  const totalEl = document.getElementById('resTotal');
  if (totalEl) totalEl.textContent = 'S/ ' + (suma + DELIVERY).toFixed(2);
  const subtEl = document.getElementById('resSubtotal');
  if (subtEl) subtEl.textContent = 'S/ ' + suma.toFixed(2);
}

/** Confirmar eliminación de un ítem con SweetAlert2. */
function confirmarEliminar(idx, nombre) {
  Swal.fire({
    title: '¿Eliminar producto?',
    html: `<strong>${nombre}</strong> será eliminado del carrito.`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#E8192C',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  }).then(r => {
    if (r.isConfirmed) {
      document.getElementById('idxEliminarHidden').value = idx;
      document.getElementById('formEliminar').submit();
    }
  });
}

/** Confirmar vaciado total del carrito. */
function confirmarVaciar() {
  Swal.fire({
    title: '¿Vaciar el carrito?',
    text: 'Se eliminarán todos los productos.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#E8192C',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Sí, vaciar',
    cancelButtonText: 'Cancelar',
  }).then(r => {
    if (r.isConfirmed) document.getElementById('formVaciar').submit();
  });
}

// ── Aviso de stock insuficiente al confirmar pedido ────────────────────────────
<?php if ($stockInsuficiente): ?>
Swal.fire({
  title: 'Stock insuficiente',
  html: <?= json_encode(
    '<p>' . htmlspecialchars($stockInsuficiente['mensaje']) . '</p>'
    . '<p class="mb-0">Unidades disponibles actualmente: <strong>'
    . (int) $stockInsuficiente['stock'] . '</strong></p>'
  ) ?>,
  icon: 'error',
  confirmButtonColor: '#E8192C',
  confirmButtonText: 'Entendido, ajustar cantidad',
});
<?php endif; ?>
</script>

<?php include '../layouts/footer.php'; ?>
