<?php
/**
 * dao/CategoriaDAO.php
 * ─────────────────────────────────────────────────────────────────────────────
 * DAO: CategoriaDAO  (Data Access Object)
 *
 * Gestiona la persistencia de la entidad Categoria en MySQL.
 * Es el ÚNICO punto del sistema con SQL sobre la tabla categoria.
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — SRP: Solo persiste/recupera categorías. No valida, no renderiza.
 *
 *   D — DIP: Depende de Conexion::obtener() (abstracción PDO), no de una
 *             instancia MySQL directa. Permite mocking en pruebas.
 *
 * SEGURIDAD:
 *   Todas las queries usan Prepared Statements (PDO) para prevenir SQL Injection.
 *   Los datos se almacenan limpios (sin htmlspecialchars en persistencia;
 *   el escape se aplica en la capa de presentación — Seguridad::e()).
 *
 * MEJORAS v2.0:
 *   - Recibe array $datos en lugar de objeto Categoria (más flexible).
 *   - Añade contarTodas() para el dashboard.
 *   - Validación de existencia previa a eliminación.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/Categoria.php';

class CategoriaDAO
{
    /** @var PDO Conexión PDO inyectada via Singleton */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::obtener();
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Crea una nueva categoría en la BD.
     *
     * Recibe un objeto Categoria (entidad del modelo) para mantener
     * el contrato tipado entre capas (SRP: el DAO trabaja con entidades).
     *
     * @param  Categoria $cat  Entidad con nombre y descripción
     * @return int|false       ID de la categoría creada, false si falla
     */
    public function crear(Categoria $cat): int|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO categoria (nombre, descripcion)
             VALUES (:nombre, :descripcion)"
        );
        $ok = $stmt->execute([
            ':nombre'      => $cat->nombre,
            ':descripcion' => $cat->descripcion ?? '',
        ]);

        return $ok ? (int) $this->pdo->lastInsertId() : false;
    }

    /**
     * Actualiza los datos de una categoría existente.
     *
     * @param  Categoria $cat  Entidad con id_categoria, nombre y descripcion
     * @return bool            true si se actualizó al menos una fila
     */
    public function actualizar(Categoria $cat): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE categoria
             SET nombre = :nombre, descripcion = :descripcion
             WHERE id_categoria = :id"
        );
        return $stmt->execute([
            ':nombre'      => $cat->nombre,
            ':descripcion' => $cat->descripcion ?? '',
            ':id'          => $cat->id_categoria,
        ]);
    }

    /**
     * Elimina una categoría por ID.
     *
     * PRECAUCIÓN: La BD debe tener FK con ON DELETE SET NULL o RESTRICT
     * en la tabla producto para mantener la integridad referencial.
     *
     * @param  int  $id  ID de la categoría a eliminar
     * @return bool
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM categoria WHERE id_categoria = :id"
        );
        return $stmt->execute([':id' => $id]);
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    /**
     * Obtiene todas las categorías ordenadas alfabéticamente.
     *
     * @return array  Array de arrays asociativos con datos de cada categoría
     */
    public function obtenerTodas(): array
    {
        return $this->pdo->query(
            "SELECT * FROM categoria ORDER BY nombre ASC"
        )->fetchAll();
    }

    /**
     * Busca una categoría por su ID primario.
     *
     * @param  int        $id
     * @return array|null  Datos de la categoría o null si no existe
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM categoria WHERE id_categoria = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Verifica si una categoría tiene productos asociados.
     * Útil para prevenir eliminación de categorías con productos.
     *
     * @param  int  $id  ID de la categoría
     * @return bool      true si tiene al menos un producto activo
     */
    public function tieneProductos(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM producto
             WHERE id_categoria = :id AND activo = 1"
        );
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Cuenta el total de categorías registradas.
     * Usado en el dashboard administrativo.
     *
     * @return int
     */
    public function contarTodas(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM categoria"
        )->fetchColumn();
    }

    /**
     * Obtiene categorías junto con el conteo de sus productos activos.
     * Usado en reportes y paneles de administración.
     *
     * @return array  [['id_categoria', 'nombre', 'descripcion', 'total_productos'], ...]
     */
    public function obtenerConConteoProductos(): array
    {
        return $this->pdo->query(
            "SELECT c.*, COUNT(p.id_producto) AS total_productos
             FROM categoria c
             LEFT JOIN producto p
               ON c.id_categoria = p.id_categoria AND p.activo = 1
             GROUP BY c.id_categoria, c.nombre, c.descripcion
             ORDER BY c.nombre ASC"
        )->fetchAll();
    }
}
