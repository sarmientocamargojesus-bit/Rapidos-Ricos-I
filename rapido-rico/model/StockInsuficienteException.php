<?php
/**
 * model/StockInsuficienteException.php
 * ─────────────────────────────────────────────────────────────────────────────
 * EXCEPCIÓN: StockInsuficienteException
 *
 * Se lanza cuando, al confirmar un pedido, uno o más productos del carrito
 * no tienen stock suficiente (incluyendo el caso de stock = 0).
 *
 * PRINCIPIO SRP: una excepción especializada permite que el controlador
 * distingua este caso ("no hay stock") de un error genérico de base de datos,
 * sin tener que inspeccionar mensajes de texto.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class StockInsuficienteException extends Exception
{
    /** @var string Nombre del producto sin stock suficiente */
    private string $nombreProducto;

    /** @var int Stock disponible al momento del intento */
    private int $stockDisponible;

    public function __construct(string $nombreProducto, int $stockDisponible)
    {
        $this->nombreProducto = $nombreProducto;
        $this->stockDisponible = $stockDisponible;

        $mensaje = $stockDisponible <= 0
            ? "No hay stock en estos momentos para \"{$nombreProducto}\"."
            : "Solo quedan {$stockDisponible} unidad(es) de \"{$nombreProducto}\".";

        parent::__construct($mensaje);
    }

    public function getNombreProducto(): string
    {
        return $this->nombreProducto;
    }

    public function getStockDisponible(): int
    {
        return $this->stockDisponible;
    }
}
