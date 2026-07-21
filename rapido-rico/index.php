<?php
/**
 * index.php — Página de inicio Rápido & Rico v2.0
 * Mejoras: Seguridad::e() en salidas, AuthMiddleware implícito via includes.
 */

require_once 'config/app.php';
require_once 'helpers/Seguridad.php';
require_once 'helpers/CabecerasSeguridad.php';
require_once 'dao/ProductoDAO.php';
require_once 'dao/CategoriaDAO.php';

Seguridad::iniciarSesionSegura();
CabecerasSeguridad::aplicar();

$prodDAO     = new ProductoDAO();
$catDAO      = new CategoriaDAO();
$categorias  = $catDAO->obtenerTodas();
$populares   = array_slice($prodDAO->obtenerTodos(), 0, 4);
$carrito_qty = array_sum(array_column($_SESSION['carrito'] ?? [], 'cantidad'));

$catImgs = [
    'Hamburguesas' => 'img/cat_hamburguesas.jpg',
    'Combos'       => 'img/cat_combos.jpg',
    'Bebidas'      => 'img/cat_bebidas.jpg',
    'Snacks'       => 'img/cat_snacks.jpg',
    'Postres'      => 'img/cat_postres.jpg',
];
$catFallback = [
    'Hamburguesas' => 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=400&auto=format&fit=crop',
    'Combos'       => 'https://images.unsplash.com/photo-1592415486689-125cbbfcbee2?w=400&auto=format&fit=crop',
    'Bebidas'      => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=400&auto=format&fit=crop',
    'Snacks'       => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=400&auto=format&fit=crop',
    'Postres'      => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?w=400&auto=format&fit=crop',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Rápido & Rico – Comida rápida, deliciosa y a domicilio en Ica"/>
  <title>Rápido &amp; Rico – Comida rápida y deliciosa</title>
  <!-- SRI: mitiga "Falta atributo de integridad de recursos secundarios" (Medio) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
        integrity="sha384-tViUnnbplMdV8hCP1rkIBnNSPTXv4YFRiYd3SuEerKjMIfFahanIe9dYmFWOOE/n"
        crossorigin="anonymous"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800;900&family=Nunito:wght@400;500;600;700&display=swap"
        rel="stylesheet" crossorigin="anonymous"/>
  <link href="css/style.css" rel="stylesheet"/>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <div class="brand-logo">R</div>Rápido <span>&amp; Rico</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav mx-auto gap-1">
        <li class="nav-item"><a class="nav-link active" href="index.php">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="view/cliente/menu.php">Menú</a></li>
        <li class="nav-item"><a class="nav-link" href="#combos">Combos</a></li>
        <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
        <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
      </ul>
      <div class="navbar-icons d-flex align-items-center gap-1">
        <a href="view/cliente/menu.php" class="btn-icon" title="Buscar"><i class="bi bi-search"></i></a>
        <?php if (Seguridad::estaAutenticado()): ?>
          <?php if (Seguridad::esAdmin()): ?>
          <a href="view/admin/dashboard.php" class="btn-icon" title="Panel Admin"><i class="bi bi-speedometer2"></i></a>
          <?php else: ?>
          <a href="view/cliente/mis_pedidos.php" class="btn-icon" title="Mis pedidos"><i class="bi bi-clock-history"></i></a>
          <?php endif; ?>
          <a href="controller/UsuarioController.php?accion=logout" class="btn-icon" title="Cerrar sesión"
             onclick="return confirm('¿Cerrar sesión?')"><i class="bi bi-box-arrow-right"></i></a>
        <?php else: ?>
          <a href="view/cliente/login.php" class="btn-icon" title="Iniciar sesión"><i class="bi bi-person"></i></a>
        <?php endif; ?>
        <a href="view/cliente/carrito.php" class="btn-icon" title="Carrito" style="position:relative">
          <i class="bi bi-bag"></i>
          <?php if ($carrito_qty > 0): ?><span class="cart-badge"><?= $carrito_qty ?></span><?php endif; ?>
        </a>
      </div>
    </div>
  </div>
</nav>

<?php if (!empty($_SESSION['exito'])): ?>
<div class="alert alert-success alert-dismissible fade show m-0 rounded-0">
  <div class="container"><i class="bi bi-check-circle me-2"></i><?= Seguridad::e($_SESSION['exito']) ?></div>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php unset($_SESSION['exito']); endif; ?>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <span class="hero-badge"><i class="bi bi-lightning-fill me-1"></i>Pide tus platos favoritos</span>
        <h1>Deliciosa comida,<br>rápida y a <span>tu alcance</span></h1>
        <p class="mt-3">Haz tu pedido en línea de forma fácil y rápida.<br>Disfruta nuestros mejores platos en cualquier lugar.</p>
        <div class="d-flex gap-3 mt-4 flex-wrap">
          <a href="view/cliente/menu.php" class="btn-rr"><i class="bi bi-grid-3x3-gap-fill"></i> Ver Menú</a>
          <a href="#combos" class="btn-rr-outline"><i class="bi bi-tags-fill"></i> Ver Combos</a>
        </div>
        <div class="hero-stats">
          <div class="hero-stat"><strong>500+</strong><span>Platos disponibles</span></div>
          <div class="hero-stat"><strong>4.9★</strong><span>Valoración promedio</span></div>
          <div class="hero-stat"><strong>30min</strong><span>Entrega rápida</span></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-img-wrap">
          <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
            <div class="carousel-inner">
              <div class="carousel-item active">
                <img src="img/hero_hamburguesa.jpg" onerror="this.src='https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&auto=format&fit=crop'" alt="Hamburguesa">
              </div>
              <div class="carousel-item">
                <img src="img/hero_combo.jpg" onerror="this.src='https://images.unsplash.com/photo-1603064752734-4c48eff1d05e?w=800&auto=format&fit=crop'" alt="Combo">
              </div>
              <div class="carousel-item">
                <img src="img/hero_ensalada.jpg" onerror="this.src='https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?w=800&auto=format&fit=crop'" alt="Comida">
              </div>
            </div>
            <div class="carousel-indicators" style="bottom:-1.8rem">
              <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
              <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
              <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORÍAS -->
<section style="padding:3.5rem 0 1.5rem">
  <div class="container">
    <h2 class="section-title mb-4">Nuestras <span>Categorías</span></h2>
    <div class="row g-3">
      <?php foreach ($categorias as $i => $cat):
        $local    = $catImgs[$cat['nombre']]     ?? 'img/cat_hamburguesas.jpg';
        $fallback = $catFallback[$cat['nombre']] ?? 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&auto=format&fit=crop';
      ?>
      <div class="col-6 col-sm-4 col-md-3 col-lg">
        <a href="view/cliente/menu.php?cat=<?= (int)$cat['id_categoria'] ?>" style="text-decoration:none">
          <div class="cat-card <?= $i === 0 ? 'active' : '' ?>">
            <div class="cat-icon">
              <img src="<?= Seguridad::attr($local) ?>"
                   onerror="this.src='<?= Seguridad::attr($fallback) ?>'"
                   alt="<?= Seguridad::attr($cat['nombre']) ?>">
            </div>
            <h6><?= Seguridad::e($cat['nombre']) ?></h6>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- PRODUCTOS POPULARES -->
<section style="padding:1rem 0 3rem">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title">Más <span>populares</span></h2>
      <a href="view/cliente/menu.php" class="btn-rr" style="padding:.5rem 1.2rem;font-size:.85rem">
        Ver todos <i class="bi bi-arrow-right"></i>
      </a>
    </div>
    <div class="row g-4">
      <?php foreach ($populares as $p): ?>
      <div class="col-sm-6 col-lg-3">
        <div class="product-card">
          <img class="product-img"
               src="img/<?= Seguridad::attr($p['imagen'] ?? 'default.jpg') ?>"
               onerror="this.src='https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop'"
               alt="<?= Seguridad::attr($p['nombre']) ?>">
          <div class="product-body">
            <div class="product-name"><?= Seguridad::e($p['nombre']) ?></div>
            <div class="product-desc"><?= Seguridad::e(mb_substr($p['descripcion'] ?? '', 0, 70)) ?>…</div>
            <div class="product-footer">
              <span class="product-price">S/ <?= number_format((float)$p['precio'], 2) ?></span>
              <button class="btn-add" onclick="addHome(<?= (int)$p['id_producto'] ?>,'<?= Seguridad::attr($p['nombre']) ?>',<?= (float)$p['precio'] ?>)">
                <i class="bi bi-plus-lg"></i> Agregar
              </button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- COMBO BANNER -->
<section id="combos" style="padding:0 0 4rem">
  <div class="container">
    <div class="combo-banner">
      <div class="row align-items-center g-3">
        <div class="col-md-7 content">
          <span class="combo-badge"><i class="bi bi-lightning-fill me-1"></i>Oferta especial</span>
          <h3>¡Combos especiales con increíbles precios!</h3>
          <p>Ahorra más pidiendo nuestros combos exclusivos</p>
          <a href="view/cliente/menu.php?cat=2" class="btn-rr">Ver Combos <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="col-md-5 d-none d-md-flex justify-content-end">
          <img src="img/banner_combo.jpg"
               onerror="this.src='https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&auto=format&fit=crop'"
               style="width:100%;max-width:340px;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.4);object-fit:cover;max-height:260px"
               alt="Combo especial">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- NOSOTROS -->
<section id="nosotros" style="padding:3.5rem 0 4rem;background:#F5F5F5">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-md-6">
        <img src="img/nosotros.jpg"
             onerror="this.src='https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&auto=format&fit=crop'"
             class="w-100 rounded-4 shadow" style="max-height:380px;object-fit:cover" alt="Nosotros">
      </div>
      <div class="col-md-6">
        <span class="hero-badge"><i class="bi bi-heart-fill me-1"></i>Nuestra historia</span>
        <h2 class="section-title mt-2 mb-3">¿Quiénes <span>somos?</span></h2>
        <p style="color:#555;line-height:1.8">Somos un restaurante de comida rápida comprometido con la calidad y el sabor. Ingredientes frescos, seleccionados para ofrecerte la mejor experiencia en Ica.</p>
        <div class="row g-3 mt-2">
          <?php foreach ([
            ['bi-award-fill','Calidad','Ingredientes frescos'],
            ['bi-lightning-fill','Rapidez','Entrega en 30 min'],
            ['bi-heart-fill','Pasión','Cocinamos con amor'],
          ] as $v): ?>
          <div class="col-sm-4 text-center">
            <div style="width:52px;height:52px;background:rgba(232,25,44,.1);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto .7rem">
              <i class="bi <?= $v[0] ?>" style="font-size:1.4rem;color:#E8192C"></i>
            </div>
            <div style="font-family:'Poppins',sans-serif;font-weight:700;font-size:.9rem"><?= $v[1] ?></div>
            <div style="font-size:.78rem;color:#777"><?= $v[2] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer id="contacto">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4 brand-col">
        <div class="logo-wrap">
          <div class="brand-logo">R</div><h5>Rápido &amp; Rico</h5>
        </div>
        <p>Comida rápida y deliciosa con ingredientes frescos y de calidad.</p>
        <div class="social-row">
          <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-btn"><i class="bi bi-instagram"></i></a>
          <a href="#" class="social-btn"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2"><h6>Navegación</h6>
        <ul>
          <li><a href="index.php">Inicio</a></li>
          <li><a href="view/cliente/menu.php">Menú</a></li>
          <li><a href="#combos">Combos</a></li>
          <li><a href="#nosotros">Nosotros</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2"><h6>Ayuda</h6>
        <ul>
          <li><a href="#">Preguntas frecuentes</a></li>
          <li><a href="#">Política de privacidad</a></li>
          <li><a href="#">Métodos de pago</a></li>
          <li><a href="#">Envíos y delivery</a></li>
        </ul>
      </div>
      <div class="col-lg-4"><h6>Contacto</h6>
        <ul class="footer-contact" style="list-style:none;padding:0;display:flex;flex-direction:column;gap:.7rem">
          <li><i class="bi bi-geo-alt-fill"></i><span>Av. Los Maestros 121 - Ica</span></li>
          <li><i class="bi bi-telephone-fill"></i><span>+51 987 654 321</span></li>
          <li><i class="bi bi-envelope-fill"></i><span>contacto@rapidoyrico.com</span></li>
          <li><i class="bi bi-clock-fill"></i><span>Lun–Dom: 10:00 AM – 10:00 PM</span></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> <strong>Rápido &amp; Rico</strong>. Todos los derechos reservados.</p>
      <button onclick="window.scrollTo({top:0,behavior:'smooth'})"
              style="background:#E8192C;border:none;width:36px;height:36px;border-radius:50%;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center">
        <i class="bi bi-arrow-up"></i>
      </button>
    </div>
  </div>
</footer>

<!-- Carrito flotante -->
<a href="view/cliente/carrito.php" class="float-cart" title="Ver carrito">
  <i class="bi bi-bag-fill"></i>
  <?php if ($carrito_qty > 0): ?><span class="cart-badge"><?= $carrito_qty ?></span><?php endif; ?>
</a>

<!-- Toast -->
<div class="toast-container">
  <div id="cartToast" class="toast align-items-center" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-bag-check-fill text-danger me-2"></i>
        <span id="toastMsg">Producto agregado</span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFXhDdJuFiOiJiUJHPjgsMDiZDd"
        crossorigin="anonymous"></script>
<script src="js/main.js"></script>
<script>
function addHome(id, nombre, precio) {
  fetch('view/cliente/agregar_carrito.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id_producto=${id}&nombre=${encodeURIComponent(nombre)}&precio=${precio}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      document.querySelectorAll('.cart-badge').forEach(b => {
        b.textContent = data.qty;
        b.style.display = 'flex';
        b.style.transform = 'scale(1.5)';
        setTimeout(() => b.style.transform = 'scale(1)', 250);
      });
      document.getElementById('toastMsg').textContent = `"${nombre}" agregado al carrito`;
      new bootstrap.Toast(document.getElementById('cartToast'), { delay: 2500 }).show();
    }
  })
  .catch(() => alert('Error de conexión.'));
}
</script>
</body>
</html>
