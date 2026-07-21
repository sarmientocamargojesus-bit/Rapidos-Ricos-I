<?php
/**
 * model/Producto.php
 * ─────────────────────────────────────────────────────────────────────────────
 * ENTIDAD: Producto
 *
 * POPO (Plain Old PHP Object) que modela la entidad Producto del sistema.
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — SRP: Una sola responsabilidad: modelar los datos de un producto.
 *             No contiene lógica de persistencia ni de reglas de negocio.
 *
 *   O — OCP: Nuevos campos o comportamientos de presentación se agregan
 *             aquí sin modificar ProductoDAO ni ProductoController.
 *
 * PATRÓN: DTO / Rich Domain Object (contiene comportamientos de presentación
 *         pero NO de persistencia ni de negocio complejo).
 * ─────────────────────────────────────────────────────────────────────────────
 */
class Producto
{
    // ── Identificadores ───────────────────────────────────────────────────────

    /** @var int|null ID autogenerado por la BD */
    public ?int $id_producto = null;

    // ── Datos del producto ────────────────────────────────────────────────────

    /** @var string Nombre del producto */
    public string $nombre = '';

    /** @var string Descripción detallada */
    public string $descripcion = '';

    /** @var float Precio en soles peruanos */
    public float $precio = 0.0;

    /** @var string Nombre del archivo de imagen (almacenado en /img/) */
    public string $imagen = 'default.jpg';

    /** @var int ID de la categoría a la que pertenece */
    public int $id_categoria = 0;

    /** @var int Cantidad disponible en inventario */
    public int $stock = 0;

    /** @var bool Indica si el producto está disponible (soft delete) */
    public bool $activo = true;

    // ── Datos enriquecidos por JOIN (opcionales) ──────────────────────────────

    /** @var string|null Nombre de la categoría (pobado por JOIN en ProductoDAO) */
    public ?string $categoria_nombre = null;

    // ── Constructor ───────────────────────────────────────────────────────────

    /**
     * @param string $nombre
     * @param string $descripcion
     * @param float  $precio
     * @param string $imagen
     * @param int    $id_categoria
     * @param bool   $activo
     * @param int    $stock
     */
    public function __construct(
        string $nombre       = '',
        string $descripcion  = '',
        float  $precio       = 0.0,
        string $imagen       = 'default.jpg',
        int    $id_categoria = 0,
        bool   $activo       = true,
        int    $stock        = 0
    ) {
        $this->nombre       = $nombre;
        $this->descripcion  = $descripcion;
        $this->precio       = $precio;
        $this->imagen       = $imagen;
        $this->id_categoria = $id_categoria;
        $this->activo       = $activo;
        $this->stock        = $stock;
    }

    // ── Métodos de presentación ───────────────────────────────────────────────

    /**
     * Retorna el precio formateado con símbolo de moneda peruana.
     * Ej.: "S/ 12.50"
     *
     * @return string
     */
    public function precioFormateado(): string
    {
        return 'S/ ' . number_format($this->precio, 2);
    }

    /**
     * Retorna la ruta relativa completa a la imagen del producto.
     * Usa una imagen por defecto si el archivo no está definido.
     *
     * @param  string $base  Prefijo de ruta relativa (varía según la vista)
     * @return string
     */
    public function rutaImagen(string $base = '../../img/'): string
    {
        return $base . ($this->imagen ?: 'default.jpg');
    }

    /**
     * Retorna la descripción truncada para tarjetas de producto.
     *
     * @param  int    $max  Número máximo de caracteres
     * @return string
     */
    public function descripcionCorta(int $max = 80): string
    {
        if (mb_strlen($this->descripcion, 'UTF-8') <= $max) {
            return $this->descripcion;
        }
        return mb_substr($this->descripcion, 0, $max - 1, 'UTF-8') . '…';
    }

    /**
     * Indica si el precio está en el rango de "oferta" (menor a S/ 15).
     * Utilitario para lógica de presentación en vistas.
     *
     * @return bool
     */
    public function esEconomico(): bool
    {
        return $this->precio < 15.00;
    }

    /**
     * Indica si el producto tiene unidades disponibles para la venta.
     *
     * @return bool
     */
    public function hayStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Indica si el stock está por agotarse (umbral configurable).
     * Útil para mostrar avisos de "¡últimas unidades!" en las vistas.
     *
     * @param  int  $umbral  Cantidad considerada "stock bajo"
     * @return bool
     */
    public function stockBajo(int $umbral = 5): bool
    {
        return $this->stock > 0 && $this->stock <= $umbral;
    }

    /**
     * Retorna una etiqueta legible del estado del stock.
     * Ej.: "Sin stock", "¡Últimas 3 unidades!", "Disponible"
     *
     * @return string
     */
    public function etiquetaStock(): string
    {
        if ($this->stock <= 0) {
            return 'Sin stock';
        }
        if ($this->stockBajo()) {
            return '¡Últimas ' . $this->stock . ' unidades!';
        }
        return 'Disponible';
    }
}
