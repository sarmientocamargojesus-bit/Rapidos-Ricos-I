<?php
/**
 * middleware/AuthMiddleware.php
 * ─────────────────────────────────────────────────────────────────────────────
 * MIDDLEWARE: AuthMiddleware
 *
 * Gestiona la autenticación y autorización de rutas.
 * Principio SRP: solo maneja control de acceso.
 *
 * USO EN VISTAS:
 *   AuthMiddleware::requerirLogin();       // Solo usuarios autenticados
 *   AuthMiddleware::requerirAdmin();       // Solo administradores
 *   AuthMiddleware::requerirCsrf();        // Verificar token CSRF en POST
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../helpers/Seguridad.php';

class AuthMiddleware
{
    /**
     * Verifica que el usuario esté autenticado.
     * Si no lo está, redirige al login.
     *
     * @param  string $redirect  URL de redirección si no está autenticado
     * @return void
     */
    public static function requerirLogin(string $redirect = ''): void
    {
        Seguridad::iniciarSesionSegura();

        if (!Seguridad::estaAutenticado()) {
            $url = $redirect ?: self::detectarBaseUrl() . '/view/cliente/login.php';
            header('Location: ' . $url);
            exit;
        }
    }

    /**
     * Verifica que el usuario sea administrador.
     * Si no lo es, redirige al login de cliente.
     *
     * @return void
     */
    public static function requerirAdmin(): void
    {
        Seguridad::iniciarSesionSegura();

        if (!Seguridad::esAdmin()) {
            header('Location: ' . self::detectarBaseUrl() . '/view/cliente/login.php');
            exit;
        }
    }

    /**
     * Verifica el token CSRF en peticiones POST.
     * Si falla, registra el intento y devuelve 403.
     *
     * @param  string $redirectOnFail  URL a redirigir en caso de fallo
     * @return void
     */
    public static function requerirCsrf(string $redirectOnFail = ''): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $_POST[CSRF_TOKEN_NAME] ?? '';

        if (!Seguridad::verificarCsrfToken($token)) {
            // Loguear el intento
            error_log('[CSRF] Token inválido desde IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

            if ($redirectOnFail) {
                $_SESSION['errores'] = ['Solicitud inválida. Por favor recarga la página e intenta nuevamente.'];
                header('Location: ' . $redirectOnFail);
                exit;
            }

            http_response_code(403);
            exit('Solicitud inválida.');
        }

        // Regenerar token después de cada uso exitoso (Double Submit)
        unset($_SESSION[CSRF_TOKEN_NAME]);
    }

    /**
     * Detecta la URL base de la aplicación.
     *
     * @return string
     */
    private static function detectarBaseUrl(): string
    {
        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/rapido-rico';
    }
}
