<?php
/**
 * helpers/Logger.php
 * ─────────────────────────────────────────────────────────────────────────────
 * HELPER: Logger
 *
 * Sistema simple de logging para la aplicación.
 * Principio SRP: solo registra eventos del sistema.
 *
 * Niveles: debug, info, warning, error, critical
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/app.php';

class Logger
{
    private const NIVELES = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4];

    /**
     * Registra un mensaje en el log correspondiente.
     *
     * @param  string $nivel    Nivel: debug|info|warning|error|critical
     * @param  string $mensaje  Mensaje a registrar
     * @param  array  $contexto Datos adicionales de contexto
     */
    public static function log(string $nivel, string $mensaje, array $contexto = []): void
    {
        $nivelMinimo = self::NIVELES[LOG_LEVEL] ?? 0;
        $nivelActual = self::NIVELES[$nivel]    ?? 0;

        if ($nivelActual < $nivelMinimo) {
            return; // No registrar niveles por debajo del configurado
        }

        // Asegurar que el directorio de logs existe
        if (!is_dir(LOG_DIR)) {
            @mkdir(LOG_DIR, 0755, true);
        }

        $fecha    = date('Y-m-d H:i:s');
        $archivo  = LOG_DIR . date('Y-m') . '_' . $nivel . '.log';
        $contextoStr = empty($contexto) ? '' : ' | ' . json_encode($contexto, JSON_UNESCAPED_UNICODE);
        $linea    = "[{$fecha}] [{$nivel}] {$mensaje}{$contextoStr}" . PHP_EOL;

        error_log($linea, 3, $archivo);
    }

    public static function debug(string $msg, array $ctx = []): void    { self::log('debug',    $msg, $ctx); }
    public static function info(string $msg, array $ctx = []): void     { self::log('info',     $msg, $ctx); }
    public static function warning(string $msg, array $ctx = []): void  { self::log('warning',  $msg, $ctx); }
    public static function error(string $msg, array $ctx = []): void    { self::log('error',    $msg, $ctx); }
    public static function critical(string $msg, array $ctx = []): void { self::log('critical', $msg, $ctx); }
}
