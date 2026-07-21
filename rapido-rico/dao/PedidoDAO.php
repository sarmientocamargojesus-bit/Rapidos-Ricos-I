<?php
/**
 * dao/PedidoDAO.php
 * ─────────────────────────────────────────────────────────────────────────────
 * DAO: PedidoDAO
 *
 * Gestiona la persistencia de pedidos y sus detalles.
 * Principio SRP: solo persiste/recupera pedidos.
 *
 * Mejoras v2.0:
 *   - Métodos para dashboard: estadisticasEstados(), ventasPorDia().
 *   - Validación de estado con Validador.
 *   - Queries más eficientes con índices.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/Pedido.php';
require_once __DIR__ . '/../model/StockInsuficienteException.php';
require_once __DIR__ . '/../dao/ProductoDAO.php';

class PedidoDAO
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::obtener();
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Crea un pedido y sus detalles en una transacción.
     *
     * Antes de insertar cada detalle, verifica con bloqueo de fila (FOR UPDATE)
     * que el producto tenga stock suficiente y lo descuenta. Si algún producto
     * no tiene stock suficiente (incluyendo stock = 0), se revierte toda la
     * transacción (no se crea el pedido ni se descuenta nada) y se lanza
     * StockInsuficienteException para que el controlador informe al usuario.
     *
     * @param  Pedido  $pedido  Entidad pedido
     * @param  array   $items   [['cantidad', 'precio', 'id_producto'], ...]
     * @return int              ID del pedido creado
     * @throws StockInsuficienteException  Si algún producto no tiene stock suficiente
     * @throws Exception                   Si la transacción falla por otro motivo
     */
    public function crear(Pedido $pedido, array $items): int
    {
        $this->pdo->beginTransaction();

        try {
            // ── Verificar y descontar stock de cada producto (con bloqueo) ────
            // Se hace ANTES de insertar nada para poder abortar limpio si falta stock.
            foreach ($items as $item) {
                $idProducto = (int) $item['id_producto'];
                $cantidad   = (int) $item['cantidad'];

                $stockActual = ProductoDAO::obtenerStockConBloqueo($this->pdo, $idProducto);

                if ($stockActual === null) {
                    throw new StockInsuficienteException($item['nombre'] ?? 'producto', 0);
                }

                if ($stockActual < $cantidad) {
                    throw new StockInsuficienteException($item['nombre'] ?? 'producto', $stockActual);
                }

                if (!ProductoDAO::descontarStock($this->pdo, $idProducto, $cantidad)) {
                    // Otro proceso descontó el stock entre la lectura y este punto
                    throw new StockInsuficienteException($item['nombre'] ?? 'producto', 0);
                }
            }

            // Insertar cabecera del pedido
            $stmt = $this->pdo->prepare(
                "INSERT INTO pedido (total, direccion, referencia, telefono, id_usuario)
                 VALUES (:total, :direccion, :referencia, :telefono, :id_usuario)"
            );
            $stmt->execute([
                ':total'      => round($pedido->total, 2),
                ':direccion'  => $pedido->direccion,
                ':referencia' => $pedido->referencia,
                ':telefono'   => $pedido->telefono,
                ':id_usuario' => $pedido->id_usuario,
            ]);
            $id_pedido = (int) $this->pdo->lastInsertId();

            // Insertar detalles
            $stmtD = $this->pdo->prepare(
                "INSERT INTO detalle_pedido (cantidad, precio, id_pedido, id_producto)
                 VALUES (:cantidad, :precio, :id_pedido, :id_producto)"
            );
            foreach ($items as $item) {
                $stmtD->execute([
                    ':cantidad'    => (int)   $item['cantidad'],
                    ':precio'      => (float) $item['precio'],
                    ':id_pedido'   => $id_pedido,
                    ':id_producto' => (int)   $item['id_producto'],
                ]);
            }

            $this->pdo->commit();
            return $id_pedido;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cambia el estado de un pedido.
     *
     * @param  int    $id_pedido
     * @param  string $estado
     * @return bool
     */
    public function cambiarEstado(int $id_pedido, string $estado): bool
    {
        $estadosPermitidos = ['pendiente', 'en_preparacion', 'listo', 'entregado', 'cancelado'];
        if (!in_array($estado, $estadosPermitidos, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "UPDATE pedido SET estado = :estado WHERE id_pedido = :id"
        );
        return $stmt->execute([':estado' => $estado, ':id' => $id_pedido]);
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    /**
     * Obtiene pedidos de un usuario, ordenados por fecha descendente.
     *
     * @param  int   $id_usuario
     * @return array
     */
    public function obtenerPorUsuario(int $id_usuario): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pedido WHERE id_usuario = :id ORDER BY fecha DESC"
        );
        $stmt->execute([':id' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene todos los pedidos con datos del cliente (admin).
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        return $this->pdo->query(
            "SELECT p.*, u.nombre AS cliente_nombre, u.correo AS cliente_correo
             FROM pedido p
             JOIN usuario u ON p.id_usuario = u.id_usuario
             ORDER BY p.fecha DESC"
        )->fetchAll();
    }

    /**
     * Obtiene el detalle (ítems) de un pedido.
     *
     * @param  int   $id_pedido
     * @return array
     */
    public function obtenerDetalle(int $id_pedido): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT dp.*, pr.nombre AS producto_nombre, pr.imagen
             FROM detalle_pedido dp
             JOIN producto pr ON dp.id_producto = pr.id_producto
             WHERE dp.id_pedido = :id"
        );
        $stmt->execute([':id' => $id_pedido]);
        return $stmt->fetchAll();
    }

    /**
     * Busca un pedido por ID con datos del cliente.
     *
     * @param  int        $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, u.nombre AS cliente_nombre, u.correo AS cliente_correo, u.telefono AS cliente_telefono
             FROM pedido p
             JOIN usuario u ON p.id_usuario = u.id_usuario
             WHERE p.id_pedido = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Cuenta el total de pedidos.
     *
     * @return int
     */
    public function contarTodos(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM pedido")->fetchColumn();
    }

    /**
     * Suma las ventas del día actual (excluye cancelados).
     *
     * @return float
     */
    public function ventasHoy(): float
    {
        return (float) $this->pdo->query(
            "SELECT COALESCE(SUM(total), 0)
             FROM pedido
             WHERE DATE(fecha) = CURDATE() AND estado != 'cancelado'"
        )->fetchColumn();
    }

    /**
     * Cuenta pedidos pendientes (para alertas del dashboard).
     *
     * @return int
     */
    public function contarPendientes(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM pedido WHERE estado = 'pendiente'"
        )->fetchColumn();
    }

    /**
     * Estadísticas de pedidos agrupados por estado.
     * Usado en gráficos del dashboard.
     *
     * @return array  [['estado' => ..., 'total' => ...], ...]
     */
    public function estadisticasPorEstado(): array
    {
        return $this->pdo->query(
            "SELECT estado, COUNT(*) AS total
             FROM pedido
             GROUP BY estado"
        )->fetchAll();
    }

    /**
     * Ventas de los últimos N días (para gráfico de línea).
     *
     * @param  int   $dias  Número de días hacia atrás
     * @return array        [['dia' => 'YYYY-MM-DD', 'ventas' => float, 'pedidos' => int], ...]
     */
    public function ventasUltimosDias(int $dias = 7): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(fecha) AS dia,
                    COALESCE(SUM(total), 0) AS ventas,
                    COUNT(*) AS pedidos
             FROM pedido
             WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
               AND estado != 'cancelado'
             GROUP BY DATE(fecha)
             ORDER BY dia ASC"
        );
        $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Total de ventas históricas (excluye cancelados).
     *
     * @return float
     */
    public function totalVentas(): float
    {
        return (float) $this->pdo->query(
            "SELECT COALESCE(SUM(total), 0) FROM pedido WHERE estado != 'cancelado'"
        )->fetchColumn();
    }
}
