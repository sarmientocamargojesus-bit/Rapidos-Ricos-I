<?php
/**
 * helpers/CabecerasSeguridad.php
 * ─────────────────────────────────────────────────────────────────────────────
 * HELPER: CabecerasSeguridad
 *
 * Centraliza y envía todas las cabeceras HTTP de seguridad necesarias
 * para mitigar las vulnerabilidades detectadas por OWASP ZAP:
 *
 *  [ALTO]   Inyección SQL           → Ya mitigada con PDO + Prepared Statements
 *  [MEDIO]  Ausencia Tokens CSRF    → Ya mitigada con Seguridad::campoCSRF()
 *  [MEDIO]  CSP no configurado      → MITIGADA AQUÍ con Content-Security-Policy
 *  [MEDIO]  CSP script-src unsafe   → MITIGADA AQUÍ (sin unsafe-eval/unsafe-inline)
 *  [MEDIO]  Falta Anti-Clickjacking → MITIGADA AQUÍ con X-Frame-Options
 *  [MEDIO]  Librería JS Vulnerable  → MITIGADA AQUÍ con SRI en header.php
 *  [MEDIO]  Config. Cross-Domain    → MITIGADA AQUÍ con CORS restrictivo
 *  [BAJO]   X-Content-Type-Options  → MITIGADA AQUÍ con nosniff
 *  [BAJO]   X-Powered-By expuesto   → MITIGADA AQUÍ (header eliminado)
 *  [BAJO]   Server expuesto         → MITIGADA en .htaccess
 *  [BAJO]   HSTS no establecido     → MITIGADA AQUÍ (en producción)
 *
 * USO: llamar al inicio de index.php, header.php y admin_layout.php:
 *   CabecerasSeguridad::aplicar();
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/app.php';

class CabecerasSeguridad
{
    /**
     * Aplica todas las cabeceras de seguridad HTTP.
     * Debe llamarse ANTES de cualquier output HTML.
     */
    public static function aplicar(): void
    {
        // ── 1. Eliminar cabeceras que exponen información del servidor ──────────
        // Mitiga: "X-Powered-By expuesto" y "Server expuesto" (Bajo)
        header_remove('X-Powered-By');
        @ini_set('expose_php', '0');

        // ── 2. X-Content-Type-Options ─────────────────────────────────────────
        // Mitiga: "Falta encabezado X-Content-Type-Options" (Bajo)
        // Impide que el navegador adivine el tipo MIME de una respuesta.
        // Sin esto, un archivo .jpg con código JS podría ejecutarse como script.
        header('X-Content-Type-Options: nosniff');

        // ── 3. X-Frame-Options ────────────────────────────────────────────────
        // Mitiga: "Falta de cabecera Anti-Clickjacking" (Medio)
        // Impide que la página sea cargada dentro de un iframe en otro dominio.
        // Un atacante no puede incrustar tu sitio en el suyo para engañar usuarios.
        header('X-Frame-Options: SAMEORIGIN');

        // ── 4. Content-Security-Policy (CSP) ─────────────────────────────────
        // Mitiga: "Cabecera CSP no configurada", "script-src unsafe-eval",
        //         "script-src unsafe-inline", "style-src unsafe-inline" (Medio)
        //
        // Política explicada:
        //   default-src 'self'         → Por defecto, solo recursos propios
        //   script-src 'self' cdn...   → Scripts solo de nuestro dominio y CDNs de confianza
        //   style-src 'self' cdn...    → Estilos solo de nuestro dominio y CDNs de confianza
        //   img-src 'self' data: ...   → Imágenes propias + data URIs + Unsplash (placeholders)
        //   font-src 'self' cdn...     → Fuentes de Google Fonts
        //   connect-src 'self'         → Fetch/AJAX solo al mismo dominio
        //   frame-ancestors 'none'     → Refuerza X-Frame-Options
        //   base-uri 'self'            → Evita ataques de inyección de base URL
        //   form-action 'self'         → Formularios solo envían datos al mismo dominio
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com",
            "style-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "img-src 'self' data: https://images.unsplash.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
        header("Content-Security-Policy: {$csp}");

        // ── 5. Referrer-Policy ────────────────────────────────────────────────
        // Mitiga: fuga de información por el encabezado Referer (Bajo)
        // El navegador no enviará la URL completa al navegar a otros dominios.
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // ── 6. Permissions-Policy ─────────────────────────────────────────────
        // Desactiva funciones del navegador que la app no usa.
        // Reduce la superficie de ataque ante scripts maliciosos.
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');

        // ── 7. Cross-Origin-Resource-Policy ──────────────────────────────────
        // Mitiga: "Configuración Incorrecta Cross-Domain" (Medio)
        // Impide que otros dominios carguen tus recursos directamente.
        header('Cross-Origin-Resource-Policy: same-origin');

        // ── 8. Cross-Origin-Opener-Policy ────────────────────────────────────
        header('Cross-Origin-Opener-Policy: same-origin');

        // ── 9. HSTS — Solo en producción con HTTPS ────────────────────────────
        // Mitiga: "Strict-Transport-Security Header No Establecido" (Bajo)
        // Fuerza HTTPS para todas las visitas futuras durante 1 año.
        // NO activar en localhost (rompería XAMPP).
        if (defined('APP_ENV') && APP_ENV === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        // ── 10. Cache-Control para páginas con datos sensibles ────────────────
        // Impide que el navegador cachee páginas del panel admin o del carrito.
        $rutaActual = $_SERVER['PHP_SELF'] ?? '';
        if (str_contains($rutaActual, '/admin/') || str_contains($rutaActual, 'carrito')
            || str_contains($rutaActual, 'confirmar') || str_contains($rutaActual, 'mis_pedidos')) {
            header('Cache-Control: no-store, no-cache, must-revalidate, private');
            header('Pragma: no-cache');
        }
    }
}
