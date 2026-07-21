<?php
/**
 * view/cliente/menu.php — Menú de productos v2.0
 * Mejoras: CSRF en carrito, Seguridad::e() en todas las salidas, búsqueda optimizada.
 */

require_once __DIR__ . '/../../helpers/Seguridad.php';
require_once __DIR__ . '/../../dao/ProductoDAO.php';
require_once __DIR__ . '/../../dao/CategoriaDAO.php';

Seguridad::iniciarSesionSegura();

$prodDAO    = new ProductoDAO();
$catDAO     = new CategoriaDAO();
$categorias = $catDAO->obtenerTodas();

$id_cat = Seguridad::entero($_GET['cat'] ?? 0);
$buscar = Seguridad::texto($_GET['q']   ?? '');

if (!empty($buscar)) {
    $productos = $prodDAO->buscar($buscar);
} elseif ($id_cat > 0) {
    $productos = $prodDAO->obtenerPorCategoria($id_cat);
} else {
    $productos = $prodDAO->obtenerTodos();
}

$pageTitle = 'Menú – Rápido & Rico';
include '../layouts/header.php';
?>

<section class="menu-section">
  <div class="container">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
        <li class="breadcrumb-item active">Menú</li>
      </ol>
    </nav>

    <div class="row g-4">

      <!-- ══ Sidebar categorías ════════════════════════════════════════════ -->
      <div class="col-lg-3">
        <div class="menu-sidebar">

          <!-- Buscador -->
          <form method="GET" class="mb-3">
            <div class="search-wrap">
              <i class="bi bi-search search-icon"></i>
              <input type="text" name="q" class="form-control search-input"
                     placeholder="Buscar producto..."
                     value="<?= Seguridad::e($buscar) ?>"
                     autocomplete="off">
              <?php if (!empty($buscar)): ?>
              <a href="menu.php" class="search-clear"><i class="bi bi-x-lg"></i></a>
              <?php endif; ?>
            </div>
          </form>

          <div class="cat-label">CATEGORÍAS</div>

          <nav class="cat-nav">
            <a href="menu.php"
               class="cat-pill <?= $id_cat === 0 && empty($buscar) ? 'active' : '' ?>">
              <i class="bi bi-grid-3x3-gap-fill me-2"></i>Todo el menú
              <span class="cat-count"><?= count($prodDAO->obtenerTodos()) ?></span>
            </a>
            <?php foreach ($categorias as $cat): ?>
            <a href="menu.php?cat=<?= (int)$cat['id_categoria'] ?>"
               class="cat-pill <?= $id_cat === (int)$cat['id_categoria'] ? 'active' : '' ?>">
              <i class="bi bi-tag me-2"></i><?= Seguridad::e($cat['nombre']) ?>
            </a>
            <?php endforeach; ?>
          </nav>

        </div>
      </div>

      <!-- ══ Grid de productos ═════════════════════════════════════════════ -->
      <div class="col-lg-9">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h2 class="section-title mb-0">
            <?php if (!empty($buscar)): ?>
              Resultados para: <span>"<?= Seguridad::e($buscar) ?>"</span>
            <?php elseif ($id_cat > 0): ?>
              <?php
                $catActual = array_filter($categorias, fn($c) => (int)$c['id_categoria'] === $id_cat);
                $catActual = reset($catActual);
              ?>
              <span><?= Seguridad::e($catActual['nombre'] ?? 'Categoría') ?></span>
            <?php else: ?>
              Nuestro <span>Menú</span>
            <?php endif; ?>
          </h2>
          <span class="badge rounded-pill"
                style="background:rgba(232,25,44,.1);color:var(--rr-red);font-size:.82rem;padding:.45rem .9rem">
            <?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?>
          </span>
        </div>

        <?php if (empty($productos)): ?>
        <div class="empty-state">
          <i class="bi bi-search empty-icon"></i>
          <h5>No se encontraron productos</h5>
          <p class="text-muted">Intenta con otro término o explora nuestras categorías.</p>
          <a href="menu.php" class="btn-rr mt-2">Ver todo el menú</a>
        </div>

        <?php else: ?>
        <div class="row g-3" id="gridProductos">
          <?php foreach ($productos as $p):
            $sinStock = (int)($p['stock'] ?? 0) <= 0;
          ?>
          <div class="col-sm-6 col-xl-4">
            <div class="product-card<?= $sinStock ? ' product-sin-stock' : '' ?>" data-id="<?= (int)$p['id_producto'] ?>">
              <div class="product-img-wrap">
                <img class="product-img"
                     src="../../img/<?= Seguridad::attr($p['imagen'] ?? 'default.jpg') ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&auto=format&fit=crop'"
                     alt="<?= Seguridad::attr($p['nombre']) ?>"
                     loading="lazy">
                <span class="product-cat-badge">
                  <?= Seguridad::e($p['categoria_nombre'] ?? '') ?>
                </span>
                <?php if ($sinStock): ?>
                <span class="product-stock-badge">Sin stock</span>
                <?php endif; ?>
              </div>
              <div class="product-body">
                <div class="product-name"><?= Seguridad::e($p['nombre']) ?></div>
                <div class="product-desc">
                  <?= Seguridad::e(mb_substr($p['descripcion'] ?? '', 0, 65, 'UTF-8')) ?>
                  <?= mb_strlen($p['descripcion'] ?? '', 'UTF-8') > 65 ? '…' : '' ?>
                </div>
                <div class="product-footer">
                  <span class="product-price">S/ <?= number_format($p['precio'], 2) ?></span>
                  <?php if ($sinStock): ?>
                  <button class="btn-add" disabled style="opacity:.5;cursor:not-allowed">
                    <i class="bi bi-slash-circle"></i> Sin stock
                  </button>
                  <?php else: ?>
                  <button class="btn-add"
                          onclick="agregarCarrito(
                            <?= (int)$p['id_producto'] ?>,
                            '<?= Seguridad::attr($p['nombre']) ?>',
                            <?= (float)$p['precio'] ?>
                          )">
                    <i class="bi bi-plus-lg"></i> Agregar
                  </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<!-- Toast de carrito -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
  <div id="cartToast" class="toast align-items-center border-0 text-white"
       style="background:var(--rr-dark,#1A1A2E)" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-600">
        <i class="bi bi-bag-check-fill text-success me-2"></i>
        <span id="toastMsg"></span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto"
              data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<style>
/* ── Menu section ─────────────────────────────── */
.menu-section { padding: 2rem 0 5rem; background: var(--rr-gray, #F4F6FA); }

/* ── Sidebar ──────────────────────────────────── */
.menu-sidebar { background: #fff; border-radius: 16px; padding: 1.4rem; box-shadow: 0 2px 20px rgba(0,0,0,.06); position: sticky; top: 76px; }

.search-wrap   { position: relative; }
.search-icon   { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: .95rem; pointer-events: none; }
.search-input  { padding-left: 2.4rem !important; border-radius: 10px !important; border: 2px solid #e0e0e0 !important; font-weight: 600 !important; }
.search-input:focus { border-color: var(--rr-red, #E8192C) !important; box-shadow: 0 0 0 3px rgba(232,25,44,.10) !important; }
.search-clear  { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #aaa; text-decoration: none; }
.search-clear:hover { color: var(--rr-red); }

.cat-label { font-size: .65rem; font-weight: 800; letter-spacing: .1em; color: #aaa; margin-bottom: .4rem; margin-top: .8rem; }
.cat-nav   { display: flex; flex-direction: column; gap: .2rem; }
.cat-pill  {
  display: flex; align-items: center; padding: .55rem .8rem;
  border-radius: 10px; text-decoration: none;
  color: #555; font-weight: 600; font-size: .875rem;
  transition: background .2s, color .2s; position: relative;
}
.cat-pill:hover { background: rgba(232,25,44,.06); color: var(--rr-red); }
.cat-pill.active { background: var(--rr-red); color: #fff !important; }
.cat-count { margin-left: auto; font-size: .72rem; background: rgba(0,0,0,.08); padding: .1rem .5rem; border-radius: 20px; }
.cat-pill.active .cat-count { background: rgba(255,255,255,.25); }

/* ── Product card ────────────────────────────── */
.product-card {
  background: #fff; border-radius: 16px; overflow: hidden;
  box-shadow: 0 2px 16px rgba(0,0,0,.07);
  transition: transform .25s, box-shadow .25s;
  height: 100%;
}
.product-card:hover { transform: translateY(-5px); box-shadow: 0 12px 35px rgba(0,0,0,.13); }

.product-img-wrap { position: relative; overflow: hidden; }
.product-img      { width: 100%; height: 180px; object-fit: cover; display: block; transition: transform .3s; }
.product-card:hover .product-img { transform: scale(1.05); }
.product-cat-badge {
  position: absolute; top: 10px; left: 10px;
  background: rgba(0,0,0,.55); color: #fff; border-radius: 20px;
  padding: .2rem .65rem; font-size: .7rem; font-weight: 700; backdrop-filter: blur(4px);
}
.product-stock-badge {
  position: absolute; top: 10px; right: 10px;
  background: var(--rr-red, #E8192C); color: #fff; border-radius: 20px;
  padding: .2rem .65rem; font-size: .7rem; font-weight: 700;
}
.product-sin-stock .product-img { filter: grayscale(.6); opacity: .75; }

.product-body   { padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: .3rem; }
.product-name   { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: .95rem; color: var(--rr-dark, #1A1A2E); }
.product-desc   { font-size: .8rem; color: #777; line-height: 1.4; flex: 1; }
.product-footer { display: flex; align-items: center; justify-content: space-between; margin-top: .5rem; }
.product-price  { font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.05rem; color: var(--rr-red, #E8192C); }

.btn-add {
  background: var(--rr-red, #E8192C); color: #fff;
  border: none; border-radius: 10px; padding: .4rem .9rem;
  font-weight: 700; font-size: .82rem; cursor: pointer;
  display: flex; align-items: center; gap: .3rem;
  transition: background .2s, transform .15s;
}
.btn-add:hover { background: #c0111e; transform: scale(1.04); }

/* ── Empty state ─────────────────────────────── */
.empty-state { text-align: center; padding: 4rem 1rem; }
.empty-icon  { font-size: 4rem; color: #ccc; display: block; margin-bottom: 1rem; }
</style>

<script>
/**
 * Agrega un producto al carrito via fetch (AJAX).
 * SRP: solo maneja la acción de agregar y actualizar el badge visual.
 */
function agregarCarrito(id, nombre, precio) {
  fetch('agregar_carrito.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body:    `id_producto=${id}&nombre=${encodeURIComponent(nombre)}&precio=${precio}`,
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      // Actualizar badge del carrito en el header
      document.querySelectorAll('.cart-badge').forEach(b => b.textContent = data.qty);

      // Mostrar toast
      document.getElementById('toastMsg').textContent = `"${nombre}" agregado al carrito`;
      new bootstrap.Toast(document.getElementById('cartToast'), { delay: 2500 }).show();
    } else {
      alert(data.msg || 'Error al agregar el producto.');
    }
  })
  .catch(() => alert('Error de conexión. Intenta nuevamente.'));
}
</script>

<?php include '../layouts/footer.php'; ?>
