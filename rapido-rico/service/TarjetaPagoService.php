<?php
/**
 * service/TarjetaPagoService.php
 * ─────────────────────────────────────────────────────────────────────────────
 * SERVICIO: TarjetaPagoService
 *
 * Implementación concreta de MetodoPagoInterface para pagos con tarjeta
 * bancaria (BCP, Interbank, BBVA, Scotiabank u otro banco).
 *
 * Responsabilidades ÚNICAS de esta clase:
 *  1. Validar los datos de tarjeta enviados desde el formulario.
 *  2. Construir el objeto Pago con los datos saneados.
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — Single Responsibility Principle (SRP):
 *       Solo gestiona la lógica de validación y construcción de pagos con
 *       tarjeta.  No conoce Yape, no conoce pedidos, no persiste datos.
 *
 *   O — Open/Closed Principle (OCP):
 *       Si se necesita soportar tarjetas AMEX (4 dígitos CVV), se puede
 *       extender esta clase o crear AmexPagoService sin modificar el resto.
 *
 *   L — Liskov Substitution Principle (LSP):
 *       Puede sustituir a cualquier MetodoPagoInterface sin alterar el
 *       comportamiento de MetodoPagoService.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/MetodoPagoInterface.php';

class TarjetaPagoService implements MetodoPagoInterface
{
    /**
     * Bancos aceptados por el sistema.
     * Principio OCP: añadir un banco solo requiere agregar un elemento aquí.
     */
    private const BANCOS_VALIDOS = [
        'BCP',
        'Interbank',
        'BBVA',
        'Scotiabank',
        'Otro banco',
    ];

    // ── MetodoPagoInterface ───────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Reglas de validación para tarjeta:
     *  - Banco: debe pertenecer a BANCOS_VALIDOS
     *  - Número: 15–16 dígitos numéricos (Visa/MC/Amex)
     *  - CVV: 3–4 dígitos
     *  - DNI: exactamente 8 dígitos numéricos
     *  - Titular: nombre no vacío, máximo 100 caracteres
     */
    public function validar(array $datos): array
    {
        $errores = [];

        // ── Validar banco ─────────────────────────────────────────────────────
        $banco = trim($datos['banco'] ?? '');
        if (!in_array($banco, self::BANCOS_VALIDOS, true)) {
            $errores[] = 'Debes seleccionar un banco válido.';
        }

        // ── Validar número de tarjeta (solo dígitos, 15-16 caracteres) ────────
        $numTarjeta = preg_replace('/\s+/', '', $datos['num_tarjeta'] ?? '');
        if (!preg_match('/^\d{15,16}$/', $numTarjeta)) {
            $errores[] = 'El número de tarjeta debe tener 15 o 16 dígitos.';
        }

        // ── Validar CVV (3-4 dígitos) ─────────────────────────────────────────
        $cvv = trim($datos['cvv'] ?? '');
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $errores[] = 'El CVV debe tener 3 o 4 dígitos.';
        }

        // ── Validar DNI (8 dígitos exactos) ──────────────────────────────────
        $dni = preg_replace('/\D/', '', $datos['dni_tarjeta'] ?? '');
        if (strlen($dni) !== 8) {
            $errores[] = 'El DNI debe tener exactamente 8 dígitos.';
        }

        // ── Validar titular ───────────────────────────────────────────────────
        $titular = trim($datos['titular_tarjeta'] ?? '');
        if (empty($titular) || strlen($titular) > 100) {
            $errores[] = 'El nombre del titular es obligatorio (máximo 100 caracteres).';
        }

        return $errores;
    }

    /**
     * {@inheritdoc}
     *
     * Construye el objeto Pago con datos de tarjeta.
     * SEGURIDAD: nunca almacena el número completo ni el CVV.
     * Solo persiste los últimos 4 dígitos para referencia del usuario.
     */
    public function construirPago(array $datos, int $id_pedido, int $id_usuario, float $monto): Pago
    {
        $numTarjeta = preg_replace('/\s+/', '', $datos['num_tarjeta'] ?? '');

        $pago                 = new Pago($id_pedido, $id_usuario, $this->getIdentificador(), $monto);
        $pago->banco          = htmlspecialchars(trim($datos['banco']            ?? ''), ENT_QUOTES);
        $pago->ultimos_cuatro = substr($numTarjeta, -4);   // Solo últimos 4 dígitos
        $pago->titular        = htmlspecialchars(trim($datos['titular_tarjeta'] ?? ''), ENT_QUOTES);
        $pago->dni            = preg_replace('/\D/', '', $datos['dni_tarjeta']   ?? '');
        $pago->estado         = 'aprobado';  // En producción: respuesta del gateway

        return $pago;
    }

    /**
     * {@inheritdoc}
     *
     * @return string 'tarjeta' — clave usada como índice en MetodoPagoService
     */
    public function getIdentificador(): string
    {
        return 'tarjeta';
    }

    // ── Métodos auxiliares públicos ───────────────────────────────────────────

    /**
     * Retorna el listado de bancos aceptados.
     * Útil para generar el selector de banco en la vista sin duplicar datos.
     *
     * @return array<string>
     */
    public function getBancosValidos(): array
    {
        return self::BANCOS_VALIDOS;
    }
}
