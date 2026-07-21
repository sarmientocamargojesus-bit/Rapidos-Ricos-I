<?php
/**
 * model/Usuario.php
 * ─────────────────────────────────────────────────────────────────────────────
 * ENTIDAD: Usuario
 *
 * POPO (Plain Old PHP Object) que modela la entidad Usuario del sistema.
 *
 * PRINCIPIO SOLID APLICADO:
 *   S — Single Responsibility Principle (SRP):
 *       Una sola responsabilidad: modelar los datos de un usuario.
 *       No valida, no persiste, no procesa sesiones.
 *       Solo transporta estado y expone comportamiento de presentación.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
class Usuario
{
    // ── Identificadores ───────────────────────────────────────────────────────

    /** @var int|null ID autogenerado por la BD */
    public ?int $id_usuario = null;

    // ── Datos personales ──────────────────────────────────────────────────────

    /** @var string Nombre completo del usuario */
    public string $nombre = '';

    /** @var string Correo electrónico único */
    public string $correo = '';

    /** @var string Hash bcrypt de la contraseña (NUNCA texto plano) */
    public string $contrasena = '';

    /** @var string|null Teléfono de contacto */
    public ?string $telefono = null;

    /** @var string|null Dirección habitual de entrega */
    public ?string $direccion = null;

    // ── Control de acceso ─────────────────────────────────────────────────────

    /**
     * @var string Rol del usuario en el sistema.
     *             Valores: 'cliente' | 'admin'
     */
    public string $rol = 'cliente';

    // ── Auditoría ─────────────────────────────────────────────────────────────

    /** @var string|null Timestamp de registro (asignado por BD) */
    public ?string $created_at = null;

    // ── Constructor ───────────────────────────────────────────────────────────

    /**
     * @param string      $nombre
     * @param string      $correo
     * @param string      $contrasena  Hash ya procesado por Seguridad::hashContrasena()
     * @param string|null $telefono
     * @param string|null $direccion
     * @param string      $rol
     */
    public function __construct(
        string  $nombre     = '',
        string  $correo     = '',
        string  $contrasena = '',
        ?string $telefono   = null,
        ?string $direccion  = null,
        string  $rol        = 'cliente'
    ) {
        $this->nombre     = $nombre;
        $this->correo     = $correo;
        $this->contrasena = $contrasena;
        $this->telefono   = $telefono;
        $this->direccion  = $direccion;
        $this->rol        = $rol;
    }

    // ── Métodos de consulta ───────────────────────────────────────────────────

    /**
     * Indica si el usuario tiene rol de administrador.
     *
     * @return bool
     */
    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    /**
     * Retorna la inicial del nombre en mayúscula para avatares.
     *
     * @return string  Ej.: 'J' para 'Juan Pérez'
     */
    public function inicial(): string
    {
        return strtoupper(mb_substr($this->nombre, 0, 1, 'UTF-8'));
    }

    /**
     * Retorna la etiqueta del rol en español para presentación en vistas.
     *
     * @return string  'Administrador' | 'Cliente'
     */
    public function etiquetaRol(): string
    {
        return match ($this->rol) {
            'admin'  => 'Administrador',
            default  => 'Cliente',
        };
    }

    /**
     * Retorna la clase Bootstrap para el badge de rol.
     *
     * @return string  'danger' | 'secondary'
     */
    public function badgeRol(): string
    {
        return $this->rol === 'admin' ? 'danger' : 'secondary';
    }
}
