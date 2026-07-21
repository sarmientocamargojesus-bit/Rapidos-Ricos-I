<?php
/**
 * view/layouts/footer.php — Footer reutilizable v2.0
 * SRP: Solo renderiza el pie de página y cierra el documento HTML.
 * DRY: Incluido en todas las vistas cliente.
 */
?>

<!-- ══ FOOTER ════════════════════════════════════════════════════════════════ -->
<footer id="contacto">
  <div class="container">
    <div class="row g-4">

      <!-- Columna brand -->
      <div class="col-lg-4 brand-col">
        <div class="logo-wrap">
          <div class="brand-logo">R</div>
          <h5>Rápido &amp; Rico</h5>
        </div>
        <p>Comida rápida y deliciosa con ingredientes frescos y de calidad. Disfruta nuestros sabores en Ica.</p>
        <div class="social-row">
          <a href="#" class="social-btn" aria-label="Facebook">
            <i class="bi bi-facebook"></i>
          </a>
          <a href="#" class="social-btn" aria-label="Instagram">
            <i class="bi bi-instagram"></i>
          </a>
          <a href="#" class="social-btn" aria-label="WhatsApp">
            <i class="bi bi-whatsapp"></i>
          </a>
          <a href="#" class="social-btn" aria-label="TikTok">
            <i class="bi bi-tiktok"></i>
          </a>
        </div>
      </div>

      <!-- Columna enlaces -->
      <div class="col-6 col-lg-2">
        <h6>Navegación</h6>
        <ul>
          <li><a href="../../index.php"><i class="bi bi-house me-1"></i>Inicio</a></li>
          <li><a href="menu.php"><i class="bi bi-grid me-1"></i>Menú</a></li>
          <li><a href="../../index.php#combos"><i class="bi bi-tags me-1"></i>Combos</a></li>
          <li><a href="../../index.php#nosotros"><i class="bi bi-people me-1"></i>Nosotros</a></li>
          <li><a href="mis_pedidos.php"><i class="bi bi-bag me-1"></i>Mis pedidos</a></li>
        </ul>
      </div>

      <!-- Columna ayuda -->
      <div class="col-6 col-lg-2">
        <h6>Ayuda</h6>
        <ul>
          <li><a href="#">Preguntas frecuentes</a></li>
          <li><a href="#">Política de privacidad</a></li>
          <li><a href="#">Términos y condiciones</a></li>
          <li><a href="#">Métodos de pago</a></li>
          <li><a href="#">Envíos y delivery</a></li>
        </ul>
      </div>

      <!-- Columna contacto -->
      <div class="col-lg-4">
        <h6>Contáctanos</h6>
        <ul class="footer-contact">
          <li>
            <i class="bi bi-geo-alt-fill"></i>
            <span>Av. Los Maestros 121 – Ica, Perú</span>
          </li>
          <li>
            <i class="bi bi-telephone-fill"></i>
            <span>+51 987 654 321</span>
          </li>
          <li>
            <i class="bi bi-envelope-fill"></i>
            <span>contacto@rapidoyrico.com</span>
          </li>
          <li>
            <i class="bi bi-clock-fill"></i>
            <span>Lun–Dom: 10:00 AM – 10:00 PM</span>
          </li>
        </ul>

        <!-- Métodos de pago -->
        <div class="mt-3">
          <div style="font-size:.72rem;color:rgba(255,255,255,.4);letter-spacing:.07em;margin-bottom:.5rem">ACEPTAMOS</div>
          <div class="d-flex gap-2 flex-wrap">
            <span style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);border-radius:6px;padding:.25rem .65rem;font-size:.75rem;font-weight:700">
              💳 Tarjeta
            </span>
            <span style="background:rgba(107,33,168,.25);color:#c084fc;border-radius:6px;padding:.25rem .65rem;font-size:.75rem;font-weight:700">
              📱 Yape
            </span>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer bottom -->
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> <strong>Rápido &amp; Rico</strong>. Todos los derechos reservados.</p>
      <button onclick="window.scrollTo({top:0,behavior:'smooth'})"
              class="back-top"
              aria-label="Volver arriba">
        <i class="bi bi-arrow-up"></i>
      </button>
    </div>

  </div>
</footer>

<style>
/* ── Back to top ─────────────────────────────── */
.back-top {
  background: var(--rr-red, #E8192C); border: none; width: 36px; height: 36px;
  border-radius: 50%; color: #fff; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: transform .2s, background .2s; flex-shrink: 0;
}
.back-top:hover { transform: translateY(-3px); background: #c0111e; }
.footer-contact { list-style: none; padding: 0; display: flex; flex-direction: column; gap: .65rem; }
.footer-contact li { display: flex; align-items: flex-start; gap: .6rem; color: rgba(255,255,255,.65); font-size: .87rem; }
.footer-contact li i { color: var(--rr-red, #E8192C); margin-top: .18rem; flex-shrink: 0; }
</style>

<!-- Bootstrap JS — SRI mitiga "Falta atributo de integridad de recursos secundarios" (Medio) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmFXhDdJuFiOiJiUJHPjgsMDiZDd"
        crossorigin="anonymous"></script>
<script src="../../js/main.js"></script>
</body>
</html>
