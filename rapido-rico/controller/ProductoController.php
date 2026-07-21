<?php
/**
 * controller/ProductoController.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CONTROLADOR: ProductoController
 *
 * Gestiona las solicitudes HTTP del CRUD de productos (solo administradores).
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — SRP: Solo orquesta el flujo HTTP del CRUD de productos.
 *             La validación está en Validador, la persistencia en ProductoDAO,
 *             la seguridad en Seguridad y AuthMiddleware.
 *
 *   D — DIP: Depende de ProductoDAO y CategoriaDAO (abstracciones),
 *             no de SQL directo. Las dependencias son inyectables.
 *
 * MEJORAS v2.0:
 *   - Protección CSRF en todas las operaciones POST.
 *   - Validación centralizada con Validador::producto().
 *   - Subida de imagen segura con validación MIME, tamaño y nombre aleatorio.
 *   - Logging de operaciones administrativas.
 *   - AuthMiddleware reemplaza el guard manual inline.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../dao/ProductoDAO.php';
require_once __DIR__ . '/../dao/CategoriaDAO.php';
require_once __DIR__ . '/../model/Producto.php';
require_once __DIR__ . '/../helpers/Seguridad.php';
require_once __DIR__ . '/../helpers/Validador.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ProductoController
{
    /** @var ProductoDAO */
    private ProductoDAO $productoDAO;

    /** @var CategoriaDAO */
    private CategoriaDAO $categoriaDAO;

    /**
     * Constructor con inyección de dependencias (DIP).
     *
     * @param ProductoDAO|null  $productoDAO
     * @param CategoriaDAO|null $categoriaDAO
     */
    public function __construct(
        ?ProductoDAO  $productoDAO  = null,
        ?CategoriaDAO $categoriaDAO = null
    ) {
        Seguridad::iniciarSesionSegura();
        AuthMiddleware::requerirAdmin();

        $this->productoDAO  = $productoDAO  ?? new ProductoDAO();
        $this->categoriaDAO = $categoriaDAO ?? new CategoriaDAO();
    }

    // ── Acciones CRUD ─────────────────────────────────────────────────────────

    /**
     * Crea un nuevo producto.
     * Valida datos, sube imagen, persiste y redirige.
     *
     * @return void
     */
    public function crear(): void
    {
        AuthMiddleware::requerirCsrf('../view/admin/productos.php');

        $datos = $this->extraerDatosPost();

        // Validar con reglas centralizadas (SRP: Validador es responsable de validar)
        $errores = Validador::producto($datos);
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $this->redirigir('../view/admin/productos.php');
        }

        // Subida segura de imagen
        $datos['imagen'] = $this->subirImagen() ?? 'default.jpg';

        // Construir entidad (Model) y persistir (DAO)
        $producto = $this->construirEntidad($datos);
        $idNuevo  = $this->productoDAO->crear([
            'nombre'       => $producto->nombre,
            'descripcion'  => $producto->descripcion,
            'precio'       => $producto->precio,
            'imagen'       => $producto->imagen,
            'id_categoria' => $producto->id_categoria,
            'stock'        => $producto->stock,
        ]);

        if ($idNuevo) {
            Logger::info('Producto creado', ['id' => $idNuevo, 'nombre' => $producto->nombre, 'admin' => $_SESSION['id_usuario']]);
            $_SESSION['exito'] = 'Producto "' . htmlspecialchars($producto->nombre) . '" creado correctamente.';
        } else {
            $_SESSION['errores'] = ['Error al crear el producto. Intenta nuevamente.'];
        }

        $this->redirigir('../view/admin/productos.php');
    }

    /**
     * Actualiza un producto existente.
     *
     * @return void
     */
    public function actualizar(): void
    {
        AuthMiddleware::requerirCsrf('../view/admin/productos.php');

        $id    = Seguridad::entero($_POST['id_producto'] ?? 0);
        $datos = $this->extraerDatosPost();

        $errores = Validador::producto($datos);
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $this->redirigir('../view/admin/productos.php?editar=' . $id);
        }

        // Mantener imagen actual si no se sube una nueva
        $imagenActual = Seguridad::texto($_POST['imagen_actual'] ?? 'default.jpg');
        $datos['imagen'] = $this->subirImagen() ?? $imagenActual;
        $datos['id_producto'] = $id;

        $ok = $this->productoDAO->actualizar($datos);

        if ($ok) {
            Logger::info('Producto actualizado', ['id' => $id, 'admin' => $_SESSION['id_usuario']]);
            $_SESSION['exito'] = 'Producto actualizado correctamente.';
        } else {
            $_SESSION['errores'] = ['Error al actualizar el producto.'];
        }

        $this->redirigir('../view/admin/productos.php');
    }

    /**
     * Elimina (desactiva) un producto — soft delete.
     *
     * @return void
     */
    public function eliminar(): void
    {
        AuthMiddleware::requerirCsrf('../view/admin/productos.php');

        $id = Seguridad::entero($_POST['id_producto'] ?? 0);

        if ($id <= 0) {
            $_SESSION['errores'] = ['ID de producto inválido.'];
            $this->redirigir('../view/admin/productos.php');
        }

        $ok = $this->productoDAO->eliminar($id);

        if ($ok) {
            Logger::info('Producto eliminado (soft)', ['id' => $id, 'admin' => $_SESSION['id_usuario']]);
            $_SESSION['exito'] = 'Producto eliminado correctamente.';
        } else {
            $_SESSION['errores'] = ['No se pudo eliminar el producto.'];
        }

        $this->redirigir('../view/admin/productos.php');
    }

    // ── Métodos auxiliares privados ───────────────────────────────────────────

    /**
     * Extrae y sanitiza los campos del POST relacionados con producto.
     *
     * SRP: la extracción y sanitización de datos de entrada es responsabilidad
     * del controlador, no del DAO ni del modelo.
     *
     * @return array  Array con campos saneados
     */
    private function extraerDatosPost(): array
    {
        return [
            'nombre'       => Seguridad::texto($_POST['nombre']       ?? ''),
            'descripcion'  => Seguridad::texto($_POST['descripcion']  ?? ''),
            'precio'       => Seguridad::decimal($_POST['precio']     ?? 0),
            'id_categoria' => Seguridad::entero($_POST['id_categoria'] ?? 0),
            'stock'        => Seguridad::entero($_POST['stock']       ?? 0),
        ];
    }

    /**
     * Construye una entidad Producto (Model) desde el array de datos.
     *
     * OCP: si el modelo Producto cambia, solo se adapta aquí.
     *
     * @param  array    $datos  Datos ya validados y saneados
     * @return Producto
     */
    private function construirEntidad(array $datos): Producto
    {
        return new Producto(
            $datos['nombre'],
            $datos['descripcion'],
            (float) $datos['precio'],
            $datos['imagen'] ?? 'default.jpg',
            (int)   $datos['id_categoria'],
            true,
            (int)   ($datos['stock'] ?? 0)
        );
    }

    /**
     * Gestiona la subida segura de la imagen del producto.
     *
     * Validaciones de seguridad aplicadas:
     *  - Tipo MIME real (finfo), no solo extensión.
     *  - Tamaño máximo configurable (UPLOAD_MAX_SIZE).
     *  - Nombre aleatorio para prevenir directory traversal.
     *  - Solo extensiones permitidas (jpg, jpeg, png, webp).
     *
     * @return string|null  Nombre del archivo subido, o null si no se subió nada
     */
    private function subirImagen(): ?string
    {
        if (empty($_FILES['imagen']['name']) || $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $archivo = $_FILES['imagen'];

        // Validar error de subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['errores'][] = 'Error al subir la imagen (código ' . $archivo['error'] . ').';
            return null;
        }

        // Validar tamaño
        if ($archivo['size'] > UPLOAD_MAX_SIZE) {
            $_SESSION['errores'][] = 'La imagen no debe superar ' . (UPLOAD_MAX_SIZE / 1024 / 1024) . ' MB.';
            return null;
        }

        // Validar tipo MIME real (no confiar en $_FILES['type'])
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeReal, UPLOAD_TYPES, true)) {
            $_SESSION['errores'][] = 'Formato de imagen no permitido. Usa JPG, PNG o WebP.';
            return null;
        }

        // Generar nombre único y seguro
        $ext      = match ($mimeReal) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $nombre   = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destino  = UPLOAD_DIR . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            $_SESSION['errores'][] = 'No se pudo guardar la imagen. Verifica permisos del directorio.';
            return null;
        }

        return $nombre;
    }

    /**
     * Centraliza redirecciones (DRY, testeable).
     *
     * @param  string $url
     * @return never
     */
    private function redirigir(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}

// ── Enrutador ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl   = new ProductoController();
    $accion = Seguridad::texto($_POST['accion'] ?? '');

    match ($accion) {
        'crear'      => $ctrl->crear(),
        'actualizar' => $ctrl->actualizar(),
        'eliminar'   => $ctrl->eliminar(),
        default      => header('Location: ../view/admin/productos.php'),
    };
}
