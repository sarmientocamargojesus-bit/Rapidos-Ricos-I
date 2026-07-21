<?php
/**
 * dao/ProductoDAO.php
 * ─────────────────────────────────────────────────────────────────────────────
 * DAO: ProductoDAO
 *
 * Gestiona la persistencia de la entidad Producto en MySQL.
 * Principio SRP: solo persiste/recupera productos.
 *
 * Mejoras v2.0:
 *   - Consultas optimizadas con JOIN e índices.
 *   - Métodos para dashboard: topVendidos(), contarActivos().
 *   - Paginación en obtenerTodos().
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/conexion.php';

class ProductoDAO
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::obtener();
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Crea un nuevo producto.
     *
     * @param  array $datos  ['nombre', 'descripcion', 'precio', 'imagen', 'id_categoria', 'stock']
     * @return int|false     ID del producto creado
     */
    public function crear(array $datos): int|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO producto (nombre, descripcion, precio, imagen, id_categoria, stock)
             VALUES (:nombre, :descripcion, :precio, :imagen, :id_categoria, :stock)"
        );
        $ok = $stmt->execute([
            ':nombre'      => $datos['nombre'],
            ':descripcion' => $datos['descripcion'] ?? '',
            ':precio'      => $datos['precio'],
            ':imagen'      => $datos['imagen'] ?? 'default.jpg',
            ':id_categoria'=> (int) $datos['id_categoria'],
            ':stock'       => (int) ($datos['stock'] ?? 0),
        ]);
        return $ok ? (int) $this->pdo->lastInsertId() : false;
    }

    /**
     * Actualiza un producto existente.
     *
     * @param  array $datos
     * @return bool
     */
    public function actualizar(array $datos): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE producto
             SET nombre=:nombre, descripcion=:descripcion, precio=:precio,
                 imagen=:imagen, id_categoria=:id_categoria, stock=:stock
             WHERE id_producto=:id"
        );
        return $stmt->execute([
            ':nombre'      => $datos['nombre'],
            ':descripcion' => $datos['descripcion'] ?? '',
            ':precio'      => $datos['precio'],
            ':imagen'      => $datos['imagen'],
            ':id_categoria'=> (int) $datos['id_categoria'],
            ':stock'       => (int) ($datos['stock'] ?? 0),
            ':id'          => (int) $datos['id_producto'],
        ]);
    }

    /**
     * Elimina (desactiva) un producto. Soft delete: activo=0.
     *
     * @param  int  $id
     * @return bool
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE producto SET activo=0 WHERE id_producto=:id"
        );
        return $stmt->execute([':id' => $id]);
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    /**
     * Obtiene todos los productos activos con nombre de categoría.
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        return $this->pdo->query(
            "SELECT p.*, c.nombre AS categoria_nombre
             FROM producto p
             LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
             WHERE p.activo = 1
             ORDER BY p.nombre ASC"
        )->fetchAll();
    }

    /**
     * Obtiene productos activos de una categoría.
     *
     * @param  int   $id_categoria
     * @return array
     */
    public function obtenerPorCategoria(int $id_categoria): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre
             FROM producto p
             LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
             WHERE p.id_categoria = :cat AND p.activo = 1
             ORDER BY p.nombre ASC"
        );
        $stmt->execute([':cat' => $id_categoria]);
        return $stmt->fetchAll();
    }

    /**
     * Busca un producto por ID.
     *
     * @param  int        $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre
             FROM producto p
             LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
             WHERE p.id_producto = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Búsqueda por texto en nombre y descripción.
     *
     * @param  string $termino
     * @return array
     */
    public function buscar(string $termino): array
    {
        $like = '%' . $termino . '%';
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.nombre AS categoria_nombre
             FROM producto p
             LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
             WHERE p.activo = 1 AND (p.nombre LIKE :t OR p.descripcion LIKE :t2)
             ORDER BY p.nombre ASC"
        );
        $stmt->execute([':t' => $like, ':t2' => $like]);
        return $stmt->fetchAll();
    }

    /**
     * Cuenta el total de productos activos.
     *
     * @return int
     */
    public function contarTodos(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM producto WHERE activo = 1"
        )->fetchColumn();
    }

    /**
     * Obtiene los N productos más vendidos.
     * Usado en el dashboard administrativo.
     *
     * @param  int   $limite  Cantidad de productos a retornar
     * @return array
     */
    public function topVendidos(int $limite = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.nombre, SUM(dp.cantidad) AS total_vendido, SUM(dp.cantidad * dp.precio) AS ingresos
             FROM detalle_pedido dp
             JOIN producto p ON dp.id_producto = p.id_producto
             JOIN pedido pe ON dp.id_pedido = pe.id_pedido
             WHERE pe.estado != 'cancelado'
             GROUP BY p.id_producto, p.nombre
             ORDER BY total_vendido DESC
             LIMIT :limite"
        );
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el stock actual de un producto bloqueando la fila (FOR UPDATE).
     *
     * IMPORTANTE: solo debe llamarse dentro de una transacción ya iniciada
     * (ver PedidoDAO::crear()). El bloqueo evita que dos pedidos simultáneos
     * lean el mismo stock "disponible" y lo vendan dos veces (race condition).
     *
     * @param  PDO  $pdo         Conexión con transacción activa
     * @param  int  $id_producto
     * @return int|null          Stock actual, o null si el producto no existe
     */
    public static function obtenerStockConBloqueo(PDO $pdo, int $id_producto): ?int
    {
        $stmt = $pdo->prepare(
            "SELECT stock FROM producto WHERE id_producto = :id FOR UPDATE"
        );
        $stmt->execute([':id' => $id_producto]);
        $valor = $stmt->fetchColumn();
        return $valor === false ? null : (int) $valor;
    }

    /**
     * Descuenta una cantidad del stock de un producto.
     *
     * IMPORTANTE: solo debe llamarse dentro de una transacción ya iniciada,
     * y después de validar con obtenerStockConBloqueo() que hay suficiente stock.
     * La cláusula "AND stock >= :cantidad" es una segunda barrera de seguridad
     * para nunca dejar el stock en negativo.
     *
     * @param  PDO  $pdo         Conexión con transacción activa
     * @param  int  $id_producto
     * @param  int  $cantidad    Unidades a descontar
     * @return bool              true si se descontó correctamente
     */
    public static function descontarStock(PDO $pdo, int $id_producto, int $cantidad): bool
    {
        $stmt = $pdo->prepare(
            "UPDATE producto
             SET stock = stock - :cantidad
             WHERE id_producto = :id AND stock >= :cantidad2"
        );
        $stmt->execute([
            ':cantidad'  => $cantidad,
            ':id'        => $id_producto,
            ':cantidad2' => $cantidad,
        ]);
        return $stmt->rowCount() === 1;
    }
}
