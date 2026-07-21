<?php
/**
 * controller/CategoriaController.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CONTROLADOR: CategoriaController
 *
 * Gestiona las solicitudes HTTP del CRUD de categorías (solo administradores).
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — SRP: Solo orquesta el flujo HTTP del CRUD. La validación está en
 *             Validador, la persistencia en CategoriaDAO.
 *
 *   D — DIP: Depende de CategoriaDAO (abstracción), inyectable para pruebas.
 *
 * MEJORAS v2.0:
 *   - Protección CSRF en todas las operaciones.
 *   - Prevención de eliminación de categorías con productos activos.
 *   - Validación centralizada con Validador::categoria().
 *   - Logging de operaciones administrativas.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../dao/CategoriaDAO.php';
require_once __DIR__ . '/../model/Categoria.php';
require_once __DIR__ . '/../helpers/Seguridad.php';
require_once __DIR__ . '/../helpers/Validador.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class CategoriaController
{
    /** @var CategoriaDAO */
    private CategoriaDAO $categoriaDAO;

    /**
     * Constructor con inyección de dependencias (DIP).
     *
     * @param CategoriaDAO|null $categoriaDAO
     */
    public function __construct(?CategoriaDAO $categoriaDAO = null)
    {
        Seguridad::iniciarSesionSegura();
        AuthMiddleware::requerirAdmin();

        $this->categoriaDAO = $categoriaDAO ?? new CategoriaDAO();
    }

    // ── Acciones CRUD ─────────────────────────────────────────────────────────

    /**
     * Crea una nueva categoría.
     *
     * Flujo: validar → construir entidad → persistir → redirigir.
     *
     * @return void
     */
    public function crear(): void
    {
        AuthMiddleware::requerirCsrf('../view/admin/categorias.php');

        $datos = $this->extraerDatosPost();

        $errores = Validador::categoria($datos);
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $this->redirigir('../view/admin/categorias.php');
        }

        // Construir entidad del Modelo (no pasar datos crudos al DAO)
        $cat    = new Categoria($datos['nombre'], $datos['descripcion'] ?: null);
        $idNuevo = $this->categoriaDAO->crear($cat);

        if ($idNuevo) {
            Logger::info('Categoría creada', ['id' => $idNuevo, 'nombre' => $cat->nombre, 'admin' => $_SESSION['id_usuario']]);
            $_SESSION['exito'] = 'Categoría "' . htmlspecialchars($cat->nombre) . '" creada correctamente.';
        } else {
            $_SESSION['errores'] = ['Error al crear la categoría.'];
        }

        $this->redirigir('../view/admin/categorias.php');
    }

    /**
     * Actualiza una categoría existente.
     *
     * @return void
     */
    public function actualizar(): void
    {
        AuthMiddleware::requerirCsrf('../view/admin/categorias.php');

        $id    = Seguridad::entero($_POST['id_categoria'] ?? 0);
        $datos = $this->extraerDatosPost();

        if ($id <= 0) {
            $_SESSION['errores'] = ['ID de categoría inválido.'];
            $this->redirigir('../view/admin/categorias.php');
        }

        $errores = Validador::categoria($datos);
        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $this->redirigir('../view/admin/categorias.php?editar=' . $id);
        }

        // Construir entidad con ID para actualización
        $cat              = new Categoria($datos['nombre'], $datos['descripcion'] ?: null);
        $cat->id_categoria = $id;

        $ok = $this->categoriaDAO->actualizar($cat);

        if ($ok) {
            Logger::info('Categoría actualizada', ['id' => $id, 'admin' => $_SESSION['id_usuario']]);
            $_SESSION['exito'] = 'Categoría actualizada correctamente.';
        } else {
            $_SESSION['errores'] = ['Error al actualizar la categoría.'];
        }

        $this->redirigir('../view/admin/categorias.php');
    }

    /**
     * Elimina una categoría.
     *
     * Previene la eliminación si la categoría tiene productos activos asociados.
     * Esto protege la integridad referencial a nivel de aplicación (además de BD).
     *
     * @return void
     */
    public function eliminar(): void
    {
        AuthMiddleware::requerirCsrf('../view/admin/categorias.php');

        $id = Seguridad::entero($_POST['id_categoria'] ?? 0);

        if ($id <= 0) {
            $_SESSION['errores'] = ['ID de categoría inválido.'];
            $this->redirigir('../view/admin/categorias.php');
        }

        // Guard: no eliminar si tiene productos activos
        if ($this->categoriaDAO->tieneProductos($id)) {
            $_SESSION['errores'] = [
                'No se puede eliminar esta categoría porque tiene productos activos. '
                . 'Primero elimina o reasigna los productos.',
            ];
            $this->redirigir('../view/admin/categorias.php');
        }

        $ok = $this->categoriaDAO->eliminar($id);

        if ($ok) {
            Logger::info('Categoría eliminada', ['id' => $id, 'admin' => $_SESSION['id_usuario']]);
            $_SESSION['exito'] = 'Categoría eliminada correctamente.';
        } else {
            $_SESSION['errores'] = ['Error al eliminar la categoría.'];
        }

        $this->redirigir('../view/admin/categorias.php');
    }

    // ── Métodos auxiliares privados ───────────────────────────────────────────

    /**
     * Extrae y sanitiza campos del POST para categoría.
     *
     * @return array
     */
    private function extraerDatosPost(): array
    {
        return [
            'nombre'      => Seguridad::texto($_POST['nombre']      ?? ''),
            'descripcion' => Seguridad::texto($_POST['descripcion'] ?? ''),
        ];
    }

    /**
     * Centraliza redirecciones (DRY).
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
    $ctrl   = new CategoriaController();
    $accion = Seguridad::texto($_POST['accion'] ?? '');

    match ($accion) {
        'crear'      => $ctrl->crear(),
        'actualizar' => $ctrl->actualizar(),
        'eliminar'   => $ctrl->eliminar(),
        default      => header('Location: ../view/admin/categorias.php'),
    };
}
