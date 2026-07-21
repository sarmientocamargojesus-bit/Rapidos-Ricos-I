/**
 * js/main.js — JavaScript principal Rápido & Rico
 */
'use strict';

/* ─── Agregar al carrito vía AJAX ──────────────────── */
function agregarCarrito(id_producto, nombre, precio) {
  fetch('agregar_carrito.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id_producto=${id_producto}&nombre=${encodeURIComponent(nombre)}&precio=${precio}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      document.querySelectorAll('.cart-badge').forEach(b => {
        b.textContent = data.qty;
        b.style.transform = 'scale(1.5)';
        setTimeout(() => b.style.transform = 'scale(1)', 250);
      });
      mostrarToast(`"${nombre}" agregado al carrito`);
    }
  })
  .catch(() => mostrarToast('Error al agregar. Intenta de nuevo.', true));
}

/* ─── Toast ─────────────────────────────────────────── */
function mostrarToast(mensaje, esError = false) {
  const toastEl = document.getElementById('cartToast');
  if (!toastEl) return;
  const msgEl = document.getElementById('toastMsg');
  if (msgEl) msgEl.textContent = mensaje;
  toastEl.style.background = esError ? '#dc3545' : '#1A1A1A';
  new bootstrap.Toast(toastEl, { delay: 2500 }).show();
}

/* ─── Filtro categorías ─────────────────────────────── */
function filterCat(el, categoria) {
  document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
}

/* ─── Cambiar cantidad carrito ──────────────────────── */
function cambiarCantidad(idx, delta) {
  const input = document.getElementById('qty_' + idx);
  if (!input) return;
  const val = parseInt(input.value) + delta;
  if (val >= 1 && val <= 20) input.value = val;
}

/* ─── DOM Ready ─────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  // Combo dots
  document.querySelectorAll('.combo-dot').forEach(dot => {
    dot.addEventListener('click', () => {
      document.querySelectorAll('.combo-dot').forEach(d => d.classList.remove('active'));
      dot.classList.add('active');
    });
  });

  // Sidebar móvil admin
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar   = document.querySelector('.admin-sidebar');
  const overlay   = document.getElementById('sidebarOverlay');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay?.classList.toggle('show');
    });
    overlay?.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay?.classList.remove('show');
    });
  }

  // Preview imagen
  document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
    input.addEventListener('change', () => {
      const file = input.files[0];
      if (!file) return;
      const preview = input.closest('form')?.querySelector('.img-preview');
      if (preview) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(file);
      }
    });
  });

  // Confirmar eliminación
  document.querySelectorAll('[data-confirm]').forEach(btn => {
    btn.addEventListener('click', e => {
      if (!confirm(btn.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
});
