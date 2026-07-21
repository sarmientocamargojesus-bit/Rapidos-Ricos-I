<?php
/**
 * model/Pedido.php
 * ─────────────────────────────────────────────────────────────────────────────
 * ENTIDAD: Pedido
 *
 * POPO (Plain Old PHP Object) que modela un pedido del sistema.
 * Principio SRP: solo transporta y presenta datos de un pedido.
 * No contiene lógica de negocio ni de persistencia.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class Pedido
{
    // ── Identificadores ───────────────────────────────────────────────────────

    /** @var int|null ID autogenerado por la BD */
    public ?int $id_pedido = null;

    /** @var int ID del usuario que realizó el pedido */
    public int $id_usuario;

    // ── Datos del pedido ──────────────────────────────────────────────────────

    /** @var float Monto total (incluye delivery) */
    public float $total;

    /** @var string Dirección de entrega */
    public string $direccion;

    /** @var string|null Referencia del domicilio */
    public ?string $referencia;

    /** @var string Teléfono de contacto */
    public string $telefono;

    // ── Estado y auditoría ────────────────────────────────────────────────────

    /**
     * @var string Estado del pedido.
     * Valores: pendiente | en_preparacion | listo | entregado | cancelado
     */
    public string $estado = 'pendiente';

    /** @var string|null Timestamp del pedido (asignado por BD) */
    public ?string $fecha = null;

    // ── Constructor ───────────────────────────────────────────────────────────

    public function __construct(
        int     $id_usuario  = 0,
        float   $total       = 0.0,
        string  $direccion   = '',
        ?string $referencia  = null,
        string  $telefono    = ''
    ) {
        $this->id_usuario = $id_usuario;
        $this->total      = $total;
        $this->direccion  = $direccion;
        $this->referencia = $referencia;
        $this->telefono   = $telefono;
    }

    // ── Métodos de presentación ───────────────────────────────────────────────

    /**
     * Retorna el estado en español para mostrar en vistas.
     *
     * @return string
     */
    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            'pendiente'       => 'Pendiente',
            'en_preparacion'  => 'En preparación',
            'listo'           => 'Listo',
            'entregado'       => 'Entregado',
            'cancelado'       => 'Cancelado',
            default           => ucfirst($this->estado),
        };
    }

    /**
     * Retorna la clase Bootstrap para el badge de estado.
     *
     * @return string  Clase CSS de Bootstrap (danger, warning, info, success, secondary)
     */
    public function badgeClase(): string
    {
        return match ($this->estado) {
            'pendiente'       => 'warning',
            'en_preparacion'  => 'info',
            'listo'           => 'primary',
            'entregado'       => 'success',
            'cancelado'       => 'danger',
            default           => 'secondary',
        };
    }

    /**
     * Retorna el ícono Bootstrap Icons correspondiente al estado.
     *
     * @return string  Clase del ícono
     */
    public function iconoEstado(): string
    {
        return match ($this->estado) {
            'pendiente'       => 'bi-hourglass-split',
            'en_preparacion'  => 'bi-fire',
            'listo'           => 'bi-bag-check',
            'entregado'       => 'bi-check-circle-fill',
            'cancelado'       => 'bi-x-circle-fill',
            default           => 'bi-circle',
        };
    }

    /**
     * Retorna el número de pedido formateado con prefijo.
     * Ej.: RR-000042
     *
     * @return string
     */
    public function numeroPedido(): string
    {
        return 'RR-' . str_pad((string)($this->id_pedido ?? 0), 6, '0', STR_PAD_LEFT);
    }
}
