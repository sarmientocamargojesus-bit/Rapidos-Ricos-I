<?php
/**
 * model/Pago.php
 * ─────────────────────────────────────────────────────────────────────────────
 * ENTIDAD: Pago
 *
 * Representa la información de un pago realizado por un cliente al confirmar
 * un pedido.  Esta clase es un Plain-Old PHP Object (POPO): solo contiene
 * estado y sin lógica de negocio ni de persistencia.
 *
 * PRINCIPIO SOLID APLICADO:
 *   S — Single Responsibility Principle (SRP):
 *       Esta clase tiene UNA sola responsabilidad: modelar los datos de un
 *       pago.  No valida, no persiste, no procesa; simplemente transporta
 *       información entre capas (DTO / Value Object).
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
class Pago
{
    // ── Identificadores ──────────────────────────────────────────────────────

    /** @var int|null ID autogenerado por la base de datos (null antes de persistir) */
    public ?int $id_pago = null;

    /** @var int ID del pedido al que pertenece este pago */
    public int $id_pedido;

    /** @var int ID del usuario que realiza el pago */
    public int $id_usuario;

    // ── Datos del método de pago ──────────────────────────────────────────────

    /**
     * @var string Método de pago elegido.
     *             Valores válidos: 'tarjeta' | 'yape'
     */
    public string $metodo;

    /**
     * @var string|null Banco emisor de la tarjeta.
     *                  Solo aplica cuando $metodo === 'tarjeta'.
     *                  Ej.: 'BCP', 'Interbank', 'BBVA', 'Scotiabank', 'Otro banco'
     */
    public ?string $banco = null;

    /**
     * @var string|null Últimos 4 dígitos del número de tarjeta (nunca el número completo).
     *                  Se almacena enmascarado por seguridad.
     */
    public ?string $ultimos_cuatro = null;

    /**
     * @var string|null Nombre del titular de la tarjeta o titular de la cuenta Yape.
     */
    public ?string $titular = null;

    /**
     * @var string|null DNI del titular del método de pago.
     */
    public ?string $dni = null;

    /**
     * @var string|null Número de teléfono asociado a la cuenta Yape.
     *                  Solo aplica cuando $metodo === 'yape'.
     */
    public ?string $yape_telefono = null;

    // ── Estado y auditoría ───────────────────────────────────────────────────

    /**
     * @var string Estado del pago.
     *             Valores: 'pendiente' | 'aprobado' | 'rechazado'
     */
    public string $estado = 'pendiente';

    /** @var float Monto total del pago */
    public float $monto;

    /** @var string|null Timestamp de creación (asignado por BD) */
    public ?string $created_at = null;

    // ── Constructor ──────────────────────────────────────────────────────────

    /**
     * Constructor con valores mínimos obligatorios.
     *
     * @param int    $id_pedido   ID del pedido asociado
     * @param int    $id_usuario  ID del usuario que paga
     * @param string $metodo      Método de pago ('tarjeta' | 'yape')
     * @param float  $monto       Monto a pagar
     */
    public function __construct(
        int    $id_pedido  = 0,
        int    $id_usuario = 0,
        string $metodo     = '',
        float  $monto      = 0.0
    ) {
        $this->id_pedido  = $id_pedido;
        $this->id_usuario = $id_usuario;
        $this->metodo     = $metodo;
        $this->monto      = $monto;
    }

    // ── Métodos de presentación ───────────────────────────────────────────────

    /**
     * Retorna una descripción legible del método de pago para mostrar al usuario
     * o guardar como referencia en sesión.
     *
     * @return string Ej.: "Tarjeta BCP · •••• 4321 · Juan Pérez"
     */
    public function descripcion(): string
    {
        if ($this->metodo === 'tarjeta') {
            return "Tarjeta {$this->banco} · •••• {$this->ultimos_cuatro} · {$this->titular}";
        }
        if ($this->metodo === 'yape') {
            return "Yape · +51{$this->yape_telefono} · {$this->titular}";
        }
        return 'Pago desconocido';
    }

    /**
     * Retorna la etiqueta de estado en español para presentación en vistas.
     *
     * @return string Ej.: "Aprobado"
     */
    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'aprobado'  => 'Aprobado',
            'rechazado' => 'Rechazado',
            default     => $this->estado,
        };
    }
}
