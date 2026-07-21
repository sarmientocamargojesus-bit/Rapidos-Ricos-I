<?php
/**
 * config/app.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CONFIGURACIÓN CENTRAL DE LA APLICACIÓN
 *
 * Centraliza TODAS las constantes y configuraciones del sistema.
 * Principio SRP: un único lugar para configurar la aplicación.
 * Principio OCP: agregar configuración no modifica clases existentes.
 *
 * NUNCA exponer este archivo públicamente (protegido por .htaccess).
 * ─────────────────────────────────────────────────────────────────────────────
 */

// ── Entorno ────────────────────────────────────────────────────────────────────
define('APP_ENV',      'development');   // 'development' | 'production'
define('APP_NAME',     'Rápido & Rico');
define('APP_VERSION',  '2.0.0');
define('APP_URL',      'http://localhost/rapido-rico');
define('APP_TIMEZONE', 'America/Lima');

// ── Base de Datos ──────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'rapido_rico');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_PORT',    '3306');
define('DB_CHARSET', 'utf8mb4');

// ── Seguridad ──────────────────────────────────────────────────────────────────
define('SESSION_LIFETIME',   3600);        // 1 hora en segundos
define('SESSION_NAME',       'rr_sess');
define('CSRF_TOKEN_NAME',    '_csrf_token');
define('PASSWORD_COST',      12);          // Costo bcrypt (mayor = más seguro)

// ── Upload de imágenes ─────────────────────────────────────────────────────────
define('UPLOAD_DIR',         __DIR__ . '/../img/');
define('UPLOAD_MAX_SIZE',    5 * 1024 * 1024);  // 5 MB
define('UPLOAD_TYPES',       ['image/jpeg', 'image/png', 'image/webp']);

// ── Negocio ────────────────────────────────────────────────────────────────────
define('DELIVERY_FEE',       5.00);        // Costo de delivery en soles
define('PEDIDO_PREFIJO',     'RR');        // Prefijo para número de pedido

// ── Logs ───────────────────────────────────────────────────────────────────────
define('LOG_DIR',            __DIR__ . '/../logs/');
define('LOG_LEVEL',          APP_ENV === 'production' ? 'error' : 'debug');

// ── Zona horaria ───────────────────────────────────────────────────────────────
date_default_timezone_set(APP_TIMEZONE);

// ── Control de errores por entorno ────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
