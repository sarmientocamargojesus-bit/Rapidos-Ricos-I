<?php
/**
 * service/YapePagoService.php
 * ─────────────────────────────────────────────────────────────────────────────
 * SERVICIO: YapePagoService
 *
 * Implementación concreta de MetodoPagoInterface para pagos con Yape
 * (billetera digital peruana).
 *
 * Responsabilidades ÚNICAS de esta clase:
 *  1. Validar los datos de Yape enviados desde el formulario.
 *  2. Construir el objeto Pago con los datos saneados.
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — Single Responsibility Principle (SRP):
 *       Solo gestiona la lógica propia de Yape.  No conoce tarjetas, no
 *       persiste datos, no interactúa con pedidos.
 *
 *   O — Open/Closed Principle (OCP):
 *       Si Yape cambia su protocolo (ej.: código de 8 dígitos), se modifica
 *       SOLO esta clase, sin afectar al controlador ni a MetodoPagoService.
 *
 *   L — Liskov Substitution Principle (LSP):
 *       Intercambiable con TarjetaPagoService en cualquier parte del sistema
 *       que use MetodoPagoInterface.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/MetodoPagoInterface.php';

class YapePagoService implements MetodoPagoInterface
{
    /**
     * Longitud del código Yape (actualmente 6 dígitos).
     * Constante para facilitar futuros cambios (OCP).
     */
    private const LONGITUD_CODIGO_YAPE = 6;

    /**
     * Longitud del número de celular peruano sin prefijo.
     */
    private const LONGITUD_CELULAR = 9;

    // ── MetodoPagoInterface ───────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Reglas de validación para Yape:
     *  - Teléfono: exactamente 9 dígitos numéricos (celular peruano)
     *  - DNI: exactamente 8 dígitos
     *  - Nombres: no vacío, máximo 100 caracteres
     *  - Código Yape: exactamente 6 dígitos numéricos
     */
    public function validar(array $datos): array
    {
        $errores = [];

        // ── Validar número de celular ─────────────────────────────────────────
        $telefono = preg_replace('/\D/', '', $datos['yape_telefono'] ?? '');
        if (strlen($telefono) !== self::LONGITUD_CELULAR) {
            $errores[] = 'El número de celular Yape debe tener ' . self::LONGITUD_CELULAR . ' dígitos.';
        }

        // ── Validar DNI ───────────────────────────────────────────────────────
        $dni = preg_replace('/\D/', '', $datos['yape_dni'] ?? '');
        if (strlen($dni) !== 8) {
            $errores[] = 'El DNI debe tener exactamente 8 dígitos.';
        }

        // ── Validar nombres ───────────────────────────────────────────────────
        $nombres = trim($datos['yape_nombres'] ?? '');
        if (empty($nombres) || strlen($nombres) > 100) {
            $errores[] = 'El nombre es obligatorio (máximo 100 caracteres).';
        }

        // ── Validar código Yape ───────────────────────────────────────────────
        $codigo = preg_replace('/\D/', '', $datos['yape_codigo'] ?? '');
        if (strlen($codigo) !== self::LONGITUD_CODIGO_YAPE) {
            $errores[] = 'El código Yape debe tener ' . self::LONGITUD_CODIGO_YAPE . ' dígitos.';
        }

        return $errores;
    }

    /**
     * {@inheritdoc}
     *
     * Construye el objeto Pago con datos de Yape.
     * SEGURIDAD: el código Yape no se persiste (es de uso único y efímero).
     * Solo se guardan teléfono, nombres y DNI como referencia.
     */
    public function construirPago(array $datos, int $id_pedido, int $id_usuario, float $monto): Pago
    {
        $pago                 = new Pago($id_pedido, $id_usuario, $this->getIdentificador(), $monto);
        $pago->yape_telefono  = preg_replace('/\D/', '', $datos['yape_telefono'] ?? '');
        $pago->titular        = htmlspecialchars(trim($datos['yape_nombres'] ?? ''), ENT_QUOTES);
        $pago->dni            = preg_replace('/\D/', '', $datos['yape_dni']    ?? '');
        $pago->estado         = 'aprobado';  // En producción: verificar con API Yape

        return $pago;
    }

    /**
     * {@inheritdoc}
     *
     * @return string 'yape' — clave usada como índice en MetodoPagoService
     */
    public function getIdentificador(): string
    {
        return 'yape';
    }
}
