<?php
/**
 * dao/UsuarioDAO.php
 * ─────────────────────────────────────────────────────────────────────────────
 * DAO: UsuarioDAO
 *
 * Gestiona la persistencia de la entidad Usuario en MySQL.
 * Es el ÚNICO punto del sistema con SQL sobre la tabla usuario.
 *
 * Principios SOLID:
 *   S — SRP: solo persiste/recupera usuarios.
 *   D — DIP: depende de Conexion::obtener() (abstracción PDO).
 *
 * Mejoras v2.0:
 *   - Typed properties y return types.
 *   - Nuevos métodos: existeCorreo(), contarTodos().
 *   - Queries con índices eficientes.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../model/Usuario.php';

class UsuarioDAO
{
    /** @var PDO */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Conexion::obtener();
    }

    // ── Escritura ─────────────────────────────────────────────────────────────

    /**
     * Crea un nuevo usuario en la BD.
     *
     * @param  array $datos  ['nombre', 'correo', 'contrasena']
     * @return int|false     ID del usuario creado o false si falla
     */
    public function crear(array $datos): int|false
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuario (nombre, correo, contrasena)
             VALUES (:nombre, :correo, :contrasena)"
        );

        $resultado = $stmt->execute([
            ':nombre'    => $datos['nombre'],
            ':correo'    => $datos['correo'],
            ':contrasena'=> $datos['contrasena'],
        ]);

        return $resultado ? (int) $this->pdo->lastInsertId() : false;
    }

    /**
     * Actualiza los datos de un usuario.
     *
     * @param  int    $id
     * @param  array  $datos  Campos a actualizar
     * @return bool
     */
    public function actualizar(int $id, array $datos): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE usuario SET nombre=:nombre, correo=:correo, telefono=:telefono, direccion=:direccion
             WHERE id_usuario=:id"
        );
        return $stmt->execute([
            ':nombre'   => $datos['nombre'],
            ':correo'   => $datos['correo'],
            ':telefono' => $datos['telefono'] ?? null,
            ':direccion'=> $datos['direccion'] ?? null,
            ':id'       => $id,
        ]);
    }

    /**
     * Elimina un usuario (soft: solo clientes).
     *
     * @param  int  $id
     * @return bool
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM usuario WHERE id_usuario=:id AND rol='cliente'"
        );
        return $stmt->execute([':id' => $id]);
    }

    // ── Lectura ───────────────────────────────────────────────────────────────

    /**
     * Busca un usuario por correo electrónico.
     * Usado en el proceso de login.
     *
     * @param  string     $correo
     * @return array|null
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM usuario WHERE correo = :correo LIMIT 1"
        );
        $stmt->execute([':correo' => $correo]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Busca un usuario por su ID primario.
     *
     * @param  int        $id
     * @return array|null
     */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_usuario, nombre, correo, telefono, direccion, rol, created_at
             FROM usuario WHERE id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Verifica si un correo ya está registrado en el sistema.
     *
     * @param  string $correo
     * @return bool
     */
    public function existeCorreo(string $correo): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM usuario WHERE correo = :correo"
        );
        $stmt->execute([':correo' => $correo]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene todos los usuarios (uso administrativo).
     *
     * @return array
     */
    public function obtenerTodos(): array
    {
        return $this->pdo->query(
            "SELECT id_usuario, nombre, correo, telefono, direccion, rol, created_at
             FROM usuario ORDER BY created_at DESC"
        )->fetchAll();
    }

    /**
     * Cuenta el total de usuarios registrados.
     *
     * @return int
     */
    public function contarTodos(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
    }

    /**
     * Cuenta solo usuarios con rol cliente.
     *
     * @return int
     */
    public function contarClientes(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM usuario WHERE rol='cliente'"
        )->fetchColumn();
    }
}
