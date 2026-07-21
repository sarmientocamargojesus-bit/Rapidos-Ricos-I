<?php
/**
 * dao/PagoDAO.php
 * ─────────────────────────────────────────────────────────────────────────────
 * DAO: PagoDAO  (Data Access Object)
 *
 * Gestiona la persistencia de la entidad Pago en la base de datos MySQL.
 * Es el ÚNICO punto del sistema que ejecuta SQL relacionado con la tabla pago.
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — Single Responsibility Principle (SRP):
 *       Solo se encarga de persistir y recuperar pagos.  No valida datos,
 *       no aplica reglas de negocio, no conoce sesiones.
 *
 *   D — Dependency Inversion Principle (DIP):
 *       Depende de la abstracción Conexion::obtener() (PDO), no de una
 *       instancia concreta creada internamente.  La conexión puede ser
 *       mockeada en pruebas unitarias.
 *
 * NOTA TÉCNICA:
 *   Los campos cvv y num_tarjeta NO se almacenan en la BD por seguridad
 *   (PCI-DSS compliance básico).  Solo se guarda ultimos_cuatro para
 *   referencia visual del usuario.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/Pago.php';

class PagoDAO
{
    /** @var PDO Instancia de conexión PDO */
    private PDO $pdo;

    /**
     * Constructor: obtiene la conexión PDO mediante el Singleton Conexion.
     */
    public function __construct()
    {
        $this->pdo = Conexion::obtener();
    }

    // ── Operaciones de escritura ──────────────────────────────────────────────

    /**
     * Persiste un nuevo pago en la base de datos.
     *
     * Usa una prepared statement para prevenir SQL Injection.
     * Los campos sensibles (CVV, número completo de tarjeta) NUNCA
     * llegan aquí: son filtrados en construirPago() de cada servicio.
     *
     * @param  Pago $pago  Entidad con todos los campos poblados
     * @return int         ID del pago recién insertado (lastInsertId)
     * @throws Exception   Si el INSERT falla
     */
    public function crear(Pago $pago): int
    {
        $sql = "INSERT INTO pago
                    (id_pedido, id_usuario, metodo, banco, ultimos_cuatro,
                     titular, dni, yape_telefono, estado, monto)
                VALUES
                    (:id_pedido, :id_usuario, :metodo, :banco, :ultimos_cuatro,
                     :titular, :dni, :yape_telefono, :estado, :monto)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_pedido'      => $pago->id_pedido,
            ':id_usuario'     => $pago->id_usuario,
            ':metodo'         => $pago->metodo,
            ':banco'          => $pago->banco,
            ':ultimos_cuatro' => $pago->ultimos_cuatro,
            ':titular'        => $pago->titular,
            ':dni'            => $pago->dni,
            ':yape_telefono'  => $pago->yape_telefono,
            ':estado'         => $pago->estado,
            ':monto'          => $pago->monto,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Actualiza el estado de un pago existente.
     * Útil para simular respuestas de un gateway de pago.
     *
     * @param  int    $id_pago  ID del pago a actualizar
     * @param  string $estado   Nuevo estado: 'pendiente'|'aprobado'|'rechazado'
     * @return bool             true si se actualizó al menos 1 fila
     */
    public function actualizarEstado(int $id_pago, string $estado): bool
    {
        $estados = ['pendiente', 'aprobado', 'rechazado'];
        if (!in_array($estado, $estados, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "UPDATE pago SET estado = :estado WHERE id_pago = :id"
        );
        return $stmt->execute([':estado' => $estado, ':id' => $id_pago]);
    }

    // ── Operaciones de lectura ────────────────────────────────────────────────

    /**
     * Obtiene el pago asociado a un pedido específico.
     *
     * @param  int        $id_pedido  ID del pedido
     * @return array|null             Fila como array asociativo, o null si no existe
     */
    public function obtenerPorPedido(int $id_pedido): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM pago WHERE id_pedido = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id_pedido]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtiene todos los pagos de un usuario (historial de pagos).
     *
     * @param  int   $id_usuario  ID del usuario
     * @return array              Array de filas asociativas ordenadas por fecha DESC
     */
    public function obtenerPorUsuario(int $id_usuario): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, pe.fecha AS fecha_pedido
             FROM pago p
             JOIN pedido pe ON p.id_pedido = pe.id_pedido
             WHERE p.id_usuario = :id
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([':id' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene todos los pagos del sistema (uso administrativo).
     *
     * @return array  Pagos con información del usuario y pedido join
     */
    public function obtenerTodos(): array
    {
        $sql = "SELECT pg.*, u.nombre AS cliente_nombre, pe.fecha AS fecha_pedido
                FROM pago pg
                JOIN usuario u  ON pg.id_usuario = u.id_usuario
                JOIN pedido  pe ON pg.id_pedido  = pe.id_pedido
                ORDER BY pg.created_at DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Busca un pago por su ID primario.
     *
     * @param  int        $id_pago  ID del pago
     * @return array|null           Fila como array, o null si no existe
     */
    public function buscarPorId(int $id_pago): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pago WHERE id_pago = :id");
        $stmt->execute([':id' => $id_pago]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Cuenta el total de pagos registrados en el sistema.
     *
     * @return int  Total de registros
     */
    public function contarTodos(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM pago")->fetchColumn();
    }

    /**
     * Suma los ingresos del día actual (pagos aprobados).
     *
     * @return float  Monto total de pagos aprobados hoy
     */
    public function ingresosHoy(): float
    {
        return (float) $this->pdo->query(
            "SELECT COALESCE(SUM(monto), 0)
             FROM pago
             WHERE DATE(created_at) = CURDATE()
               AND estado = 'aprobado'"
        )->fetchColumn();
    }
}
