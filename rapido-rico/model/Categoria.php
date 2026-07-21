<?php
/**
 * model/Categoria.php
 * ─────────────────────────────────────────────────────────────────────────────
 * ENTIDAD: Categoria
 *
 * POPO (Plain Old PHP Object) que modela la entidad Categoría del sistema.
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — SRP: Una sola responsabilidad: transportar datos de una categoría.
 *             No valida, no persiste, no interactúa con sesiones.
 *
 *   O — OCP: Si se necesitan nuevos campos (imagen, slug, activo), se agregan
 *             aquí sin modificar CategoriaDAO ni CategoriaController.
 *
 * PATRÓN: Data Transfer Object (DTO) / Value Object.
 *         Transporta datos entre la capa DAO y la capa de presentación.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class Categoria
{
    // ── Identificadores ───────────────────────────────────────────────────────

    /** @var int|null ID autogenerado por la BD (null antes de persistir) */
    public ?int $id_categoria = null;

    // ── Datos de la categoría ─────────────────────────────────────────────────

    /** @var string Nombre de la categoría (ej.: "Hamburguesas", "Bebidas") */
    public string $nombre = '';

    /** @var string|null Descripción opcional de la categoría */
    public ?string $descripcion = null;

    // ── Constructor ───────────────────────────────────────────────────────────

    /**
     * @param string      $nombre       Nombre de la categoría
     * @param string|null $descripcion  Descripción opcional
     */
    public function __construct(
        string  $nombre      = '',
        ?string $descripcion = null
    ) {
        $this->nombre      = $nombre;
        $this->descripcion = $descripcion;
    }

    // ── Métodos de presentación ───────────────────────────────────────────────

    /**
     * Retorna el nombre truncado para espacios reducidos (ej.: badges).
     *
     * @param  int    $max  Longitud máxima antes del truncado
     * @return string
     */
    public function nombreCorto(int $max = 20): string
    {
        if (mb_strlen($this->nombre, 'UTF-8') <= $max) {
            return $this->nombre;
        }
        return mb_substr($this->nombre, 0, $max - 1, 'UTF-8') . '…';
    }

    /**
     * Retorna la descripción o un placeholder si está vacía.
     *
     * @return string
     */
    public function descripcionODefault(): string
    {
        return (! empty($this->descripcion)) ? $this->descripcion : 'Sin descripción';
    }
}
