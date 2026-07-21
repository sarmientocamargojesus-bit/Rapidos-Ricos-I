<?php
/**
 * helpers/Seguridad.php
 * ─────────────────────────────────────────────────────────────────────────────
 * HELPER: Seguridad
 *
 * Centraliza todas las funciones de seguridad del sistema:
 *  - Protección CSRF (tokens por formulario).
 *  - Escape de salidas HTML (XSS).
 *  - Sanitización de entradas.
 *  - Gestión segura de sesiones.
 *
 * Principio SRP: una clase, una responsabilidad (seguridad web).
 * Principio OCP: nuevas utilidades se agregan sin modificar las existentes.
 *
 * USO:
 *   Seguridad::iniciarSesionSegura();
 *   Seguridad::generarCsrfToken();
 *   Seguridad::verificarCsrfToken($_POST['_csrf_token']);
 *   echo Seguridad::e($variable_usuario);
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/app.php';

class Seguridad
{
    // ── Gestión de Sesión ──────────────────────────────────────────────────────

    /**
     * Inicia una sesión con configuración segura.
     * Debe llamarse antes de cualquier output.
     */
    public static function iniciarSesionSegura(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return; // Ya iniciada
        }

        // Configuración segura de cookies de sesión
        ini_set('session.cookie_httponly', '1');    // Protege contra XSS
        ini_set('session.cookie_samesite', 'Lax');  // Protege contra CSRF
        ini_set('session.use_strict_mode', '1');     // Rechaza IDs externos
        ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

        session_name(SESSION_NAME);
        session_start();

        // Regenerar ID periódicamente para prevenir session fixation
        if (!isset($_SESSION['_iniciada'])) {
            session_regenerate_id(true);
            $_SESSION['_iniciada'] = time();
        } elseif (time() - $_SESSION['_iniciada'] > 1800) {
            // Cada 30 minutos, regenerar ID
            session_regenerate_id(true);
            $_SESSION['_iniciada'] = time();
        }
    }

    /**
     * Destruye la sesión de forma segura.
     */
    public static function destruirSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_unset();

            // Eliminar cookie de sesión
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }

            session_destroy();
        }
    }

    // ── CSRF Protection ────────────────────────────────────────────────────────

    /**
     * Genera y almacena un token CSRF en sesión.
     * Usar en formularios: <input type="hidden" name="_csrf_token" value="<?= Seguridad::generarCsrfToken() ?>">
     *
     * @return string Token CSRF de 64 caracteres hexadecimales
     */
    public static function generarCsrfToken(): string
    {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Verifica que el token CSRF del formulario coincide con el de sesión.
     *
     * @param  string $tokenEnviado  Token recibido del formulario
     * @return bool                  true si el token es válido
     */
    public static function verificarCsrfToken(string $tokenEnviado): bool
    {
        $tokenSesion = $_SESSION[CSRF_TOKEN_NAME] ?? '';
        if (empty($tokenSesion) || empty($tokenEnviado)) {
            return false;
        }
        // hash_equals previene timing attacks
        return hash_equals($tokenSesion, $tokenEnviado);
    }

    /**
     * Genera el campo HTML oculto para CSRF.
     * Uso: echo Seguridad::campoCSRF();
     *
     * @return string HTML del input hidden
     */
    public static function campoCSRF(): string
    {
        $token = self::generarCsrfToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
    }

    // ── Escape / XSS Protection ────────────────────────────────────────────────

    /**
     * Escapa una cadena para salida HTML segura.
     * Alias corto: Seguridad::e($var)
     *
     * @param  mixed  $valor  Valor a escapar
     * @return string         HTML escapado
     */
    public static function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escapa para atributos HTML (más estricto que e()).
     *
     * @param  string $valor
     * @return string
     */
    public static function attr(string $valor): string
    {
        return htmlspecialchars($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // ── Sanitización de entradas ───────────────────────────────────────────────

    /**
     * Sanitiza una cadena de texto: trim + strip_tags.
     * Para datos de texto plano (nombres, descripciones).
     *
     * @param  mixed  $valor
     * @return string
     */
    public static function texto(mixed $valor): string
    {
        return trim(strip_tags((string) ($valor ?? '')));
    }

    /**
     * Sanitiza y valida un entero.
     *
     * @param  mixed $valor
     * @return int
     */
    public static function entero(mixed $valor): int
    {
        return (int) filter_var($valor, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitiza un decimal/float.
     *
     * @param  mixed $valor
     * @return float
     */
    public static function decimal(mixed $valor): float
    {
        return (float) filter_var($valor, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitiza un email.
     *
     * @param  mixed $valor
     * @return string
     */
    public static function email(mixed $valor): string
    {
        return strtolower(trim(filter_var((string) ($valor ?? ''), FILTER_SANITIZE_EMAIL)));
    }

    /**
     * Sanitiza solo dígitos (para DNI, teléfono, tarjeta).
     *
     * @param  mixed $valor
     * @return string
     */
    public static function soloDigitos(mixed $valor): string
    {
        return preg_replace('/\D/', '', (string) ($valor ?? ''));
    }

    // ── Autenticación ──────────────────────────────────────────────────────────

    /**
     * Hashea una contraseña usando bcrypt.
     * Costo configurable desde APP_PASSWORD_COST.
     *
     * @param  string $contrasena  Contraseña en texto plano
     * @return string              Hash bcrypt
     */
    public static function hashContrasena(string $contrasena): string
    {
        return password_hash($contrasena, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    }

    /**
     * Verifica una contraseña contra su hash.
     *
     * @param  string $contrasena  Contraseña en texto plano
     * @param  string $hash        Hash almacenado en BD
     * @return bool
     */
    public static function verificarContrasena(string $contrasena, string $hash): bool
    {
        return password_verify($contrasena, $hash);
    }

    /**
     * Comprueba si la sesión pertenece a un usuario autenticado.
     *
     * @return bool
     */
    public static function estaAutenticado(): bool
    {
        return !empty($_SESSION['id_usuario']);
    }

    /**
     * Comprueba si el usuario autenticado tiene rol admin.
     *
     * @return bool
     */
    public static function esAdmin(): bool
    {
        return self::estaAutenticado() && ($_SESSION['rol'] ?? '') === 'admin';
    }
}
