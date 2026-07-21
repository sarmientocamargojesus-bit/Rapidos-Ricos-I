<?php
/**
 * config/conexion.php
 * ─────────────────────────────────────────────────────────────────────────────
 * SINGLETON: Conexion (PDO)
 *
 * Gestiona la conexión única a la base de datos MySQL.
 * Principio SRP: única responsabilidad = proveer conexión PDO.
 * Patrón Singleton: una sola instancia de PDO por request.
 *
 * Mejoras v2.0:
 *  - Carga configuración desde config/app.php (DRY).
 *  - Logging de errores de conexión a archivo (no pantalla).
 *  - Compatible con PHP 8.0+.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/app.php';

class Conexion
{
    /** @var PDO|null Instancia única de PDO */
    private static ?PDO $instancia = null;

    /**
     * Retorna la instancia PDO única (Singleton).
     * Thread-safe para entornos PHP-FPM.
     *
     * @return PDO
     * @throws RuntimeException Si la conexión falla
     */
    public static function obtener(): PDO
    {
        if (self::$instancia === null) {
            self::$instancia = self::crearConexion();
        }
        return self::$instancia;
    }

    /**
     * Crea y configura la instancia PDO.
     *
     * @return PDO
     * @throws RuntimeException
     */
    private static function crearConexion(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            return new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // Loguear sin exponer detalles al usuario
            error_log('[' . date('Y-m-d H:i:s') . '] DB Connection Error: ' . $e->getMessage(), 3, LOG_DIR . 'db_errors.log');

            if (APP_ENV === 'development') {
                throw new RuntimeException('Error de conexión BD: ' . $e->getMessage());
            }
            throw new RuntimeException('Error de conexión a la base de datos. Por favor intenta más tarde.');
        }
    }

    // Bloquear instanciación y clonación (patrón Singleton estricto)
    private function __construct() {}
    private function __clone()    {}
}
