<?php
/**
 * helpers/Validador.php
 * ─────────────────────────────────────────────────────────────────────────────
 * HELPER: Validador
 *
 * Centraliza todas las reglas de validación del sistema.
 * Principio SRP: solo valida, no sanitiza ni persiste.
 * Principio OCP: se agregan nuevas reglas sin modificar las existentes.
 *
 * USO:
 *   $errores = Validador::usuario($datos);
 *   $errores = Validador::producto($datos);
 * ─────────────────────────────────────────────────────────────────────────────
 */

class Validador
{
    // ── Reglas de validación de Usuario ───────────────────────────────────────

    /**
     * Valida datos de registro de usuario.
     *
     * @param  array $datos  Array con 'nombre', 'correo', 'contrasena', 'confirmar'
     * @return array         Array de mensajes de error; vacío si todo es válido
     */
    public static function registroUsuario(array $datos): array
    {
        $errores = [];

        $nombre   = trim($datos['nombre']    ?? '');
        $correo   = trim($datos['correo']    ?? '');
        $pass     = $datos['contrasena']     ?? '';
        $confirmar= $datos['confirmar']      ?? '';

        if (empty($nombre) || strlen($nombre) < 3 || strlen($nombre) > 100) {
            $errores[] = 'El nombre debe tener entre 3 y 100 caracteres.';
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 150) {
            $errores[] = 'El correo electrónico no es válido.';
        }

        if (strlen($pass) < 8 || strlen($pass) > 72) {
            $errores[] = 'La contraseña debe tener entre 8 y 72 caracteres.';
        }

        if (!preg_match('/[A-Z]/', $pass)) {
            $errores[] = 'La contraseña debe contener al menos una mayúscula.';
        }

        if (!preg_match('/[0-9]/', $pass)) {
            $errores[] = 'La contraseña debe contener al menos un número.';
        }

        if ($pass !== $confirmar) {
            $errores[] = 'Las contraseñas no coinciden.';
        }

        return $errores;
    }

    /**
     * Valida datos de login.
     *
     * @param  array $datos
     * @return array
     */
    public static function loginUsuario(array $datos): array
    {
        $errores = [];

        $correo = trim($datos['correo'] ?? '');
        $pass   = $datos['contrasena'] ?? '';

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo no tiene un formato válido.';
        }

        if (empty($pass)) {
            $errores[] = 'La contraseña es obligatoria.';
        }

        return $errores;
    }

    // ── Reglas de validación de Producto ──────────────────────────────────────

    /**
     * Valida datos de creación/edición de producto.
     *
     * @param  array $datos
     * @return array
     */
    public static function producto(array $datos): array
    {
        $errores = [];

        $nombre = trim($datos['nombre'] ?? '');
        $precio = $datos['precio']      ?? '';
        $cat    = $datos['id_categoria']?? '';
        $stock  = $datos['stock']       ?? '';

        if (empty($nombre) || strlen($nombre) < 3 || strlen($nombre) > 120) {
            $errores[] = 'El nombre del producto debe tener entre 3 y 120 caracteres.';
        }

        if (!is_numeric($precio) || (float)$precio <= 0 || (float)$precio > 9999.99) {
            $errores[] = 'El precio debe ser un número positivo menor a 10,000.';
        }

        if (!is_numeric($cat) || (int)$cat <= 0) {
            $errores[] = 'Debes seleccionar una categoría válida.';
        }

        if ($stock === '' || !is_numeric($stock) || (int)$stock < 0 || (int)$stock != $stock) {
            $errores[] = 'El stock debe ser un número entero igual o mayor a 0.';
        }

        return $errores;
    }

    // ── Reglas de validación de Categoría ─────────────────────────────────────

    /**
     * Valida datos de creación/edición de categoría.
     *
     * @param  array $datos
     * @return array
     */
    public static function categoria(array $datos): array
    {
        $errores = [];

        $nombre = trim($datos['nombre'] ?? '');
        if (empty($nombre) || strlen($nombre) < 2 || strlen($nombre) > 80) {
            $errores[] = 'El nombre de la categoría debe tener entre 2 y 80 caracteres.';
        }

        return $errores;
    }

    // ── Reglas de validación de Pedido ────────────────────────────────────────

    /**
     * Valida los datos de entrega de un pedido.
     *
     * @param  array $datos
     * @return array
     */
    public static function datosPedido(array $datos): array
    {
        $errores = [];

        $direccion = trim($datos['direccion'] ?? '');
        $telefono  = preg_replace('/\D/', '', $datos['telefono'] ?? '');

        if (empty($direccion) || strlen($direccion) < 10 || strlen($direccion) > 255) {
            $errores[] = 'La dirección debe tener entre 10 y 255 caracteres.';
        }

        if (strlen($telefono) !== 9) {
            $errores[] = 'El teléfono debe tener 9 dígitos (celular peruano).';
        }

        return $errores;
    }

    /**
     * Valida que un estado de pedido sea permitido.
     *
     * @param  string $estado
     * @return bool
     */
    public static function estadoPedido(string $estado): bool
    {
        return in_array($estado, ['pendiente', 'en_preparacion', 'listo', 'entregado', 'cancelado'], true);
    }
}
