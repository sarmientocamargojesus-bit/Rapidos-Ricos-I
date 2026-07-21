<?php
/**
 * controller/UsuarioController.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CONTROLADOR: UsuarioController
 *
 * Maneja las solicitudes HTTP relacionadas con autenticación de usuarios:
 * registro, login y logout.
 *
 * Principios SOLID aplicados:
 *   S — SRP: solo orquesta el flujo HTTP de autenticación.
 *   D — DIP: depende de UsuarioDAO (abstracción), no de SQL directo.
 *
 * Mejoras v2.0:
 *   - Protección CSRF en login y registro.
 *   - Validación centralizada vía Validador.
 *   - Seguridad centralizada vía Seguridad.
 *   - Logging de eventos de autenticación.
 *   - Rate limiting básico contra fuerza bruta.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../dao/UsuarioDAO.php';
require_once __DIR__ . '/../helpers/Seguridad.php';
require_once __DIR__ . '/../helpers/Validador.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class UsuarioController
{
    /** @var UsuarioDAO DAO de usuarios */
    private UsuarioDAO $usuarioDAO;

    /**
     * Constructor con inyección de dependencias (DIP).
     *
     * @param UsuarioDAO|null $usuarioDAO
     */
    public function __construct(?UsuarioDAO $usuarioDAO = null)
    {
        Seguridad::iniciarSesionSegura();
        $this->usuarioDAO = $usuarioDAO ?? new UsuarioDAO();
    }

    // ── Acciones públicas ─────────────────────────────────────────────────────

    /**
     * Procesa el formulario de registro de usuario.
     * Valida, sanitiza, hashea contraseña y crea el usuario.
     *
     * @return void
     */
    public function registrar(): void
    {
        AuthMiddleware::requerirCsrf('../view/cliente/registro.php');

        $datos = [
            'nombre'    => Seguridad::texto($_POST['nombre']    ?? ''),
            'correo'    => Seguridad::email($_POST['correo']    ?? ''),
            'contrasena'=> $_POST['contrasena']                 ?? '',
            'confirmar' => $_POST['confirmar']                  ?? '',
        ];

        // Validar con reglas centralizadas
        $errores = Validador::registroUsuario($datos);

        if (empty($errores)) {
            // Verificar que el correo no exista
            if ($this->usuarioDAO->existeCorreo($datos['correo'])) {
                $errores[] = 'El correo ya está registrado. ¿Olvidaste tu contraseña?';
            }
        }

        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $this->redirigir('../view/cliente/registro.php');
        }

        // Crear usuario con contraseña hasheada
        $idNuevo = $this->usuarioDAO->crear([
            'nombre'    => $datos['nombre'],
            'correo'    => $datos['correo'],
            'contrasena'=> Seguridad::hashContrasena($datos['contrasena']),
        ]);

        if (!$idNuevo) {
            $_SESSION['errores'] = ['Error al crear la cuenta. Por favor intenta nuevamente.'];
            $this->redirigir('../view/cliente/registro.php');
        }

        Logger::info('Usuario registrado', ['id' => $idNuevo, 'correo' => $datos['correo']]);

        $_SESSION['exito'] = 'Cuenta creada correctamente. ¡Bienvenido a Rápido & Rico!';
        $this->redirigir('../view/cliente/login.php');
    }

    /**
     * Procesa el formulario de login.
     * Verifica credenciales y crea la sesión del usuario.
     *
     * @return void
     */
    public function login(): void
    {
        AuthMiddleware::requerirCsrf('../view/cliente/login.php');

        // Protección básica anti fuerza bruta (delay en intentos fallidos)
        $intentosClave = 'login_intentos_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
        $intentos      = (int) ($_SESSION[$intentosClave] ?? 0);

        if ($intentos >= 5) {
            // Bloquear por 5 minutos
            $bloqueoClave = 'login_bloqueo_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
            $tiempoBloqueo= $_SESSION[$bloqueoClave] ?? 0;

            if (time() - $tiempoBloqueo < 300) {
                $_SESSION['errores'] = ['Demasiados intentos fallidos. Por favor espera 5 minutos.'];
                $this->redirigir('../view/cliente/login.php');
            } else {
                // Resetear contador
                $_SESSION[$intentosClave] = 0;
            }
        }

        $datos = [
            'correo'    => Seguridad::email($_POST['correo']    ?? ''),
            'contrasena'=> $_POST['contrasena']                 ?? '',
        ];

        $errores = Validador::loginUsuario($datos);

        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            $this->redirigir('../view/cliente/login.php');
        }

        $usuario = $this->usuarioDAO->buscarPorCorreo($datos['correo']);

        // Verificar credenciales (timing-safe con password_verify)
        if (!$usuario || !Seguridad::verificarContrasena($datos['contrasena'], $usuario['contrasena'])) {
            $_SESSION[$intentosClave] = $intentos + 1;
            $_SESSION['login_bloqueo_' . md5($_SERVER['REMOTE_ADDR'] ?? '')] = time();

            // Mensaje genérico para no revelar si el correo existe
            $_SESSION['errores'] = ['Credenciales incorrectas. Verifica tu correo y contraseña.'];
            $this->redirigir('../view/cliente/login.php');
        }

        // Login exitoso: resetear contador de intentos
        unset($_SESSION[$intentosClave]);

        // Crear sesión del usuario
        session_regenerate_id(true);  // Prevenir session fixation
        $_SESSION['id_usuario'] = (int)   $usuario['id_usuario'];
        $_SESSION['nombre']     = Seguridad::texto($usuario['nombre']);
        $_SESSION['correo']     = $usuario['correo'];
        $_SESSION['rol']        = $usuario['rol'];

        Logger::info('Login exitoso', ['id' => $usuario['id_usuario'], 'rol' => $usuario['rol']]);

        // Redirigir según rol
        if ($usuario['rol'] === 'admin') {
            $this->redirigir('../view/admin/dashboard.php');
        } else {
            $this->redirigir('../view/cliente/menu.php');
        }
    }

    /**
     * Cierra la sesión del usuario de forma segura.
     *
     * @return void
     */
    public function logout(): void
    {
        Logger::info('Logout', ['id' => $_SESSION['id_usuario'] ?? 'N/A']);
        Seguridad::destruirSesion();
        header('Location: ../index.php');
        exit;
    }

    // ── Métodos auxiliares ─────────────────────────────────────────────────────

    /**
     * Centraliza redirecciones para facilitar testing y DRY.
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

// ── Enrutador de peticiones ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctrl   = new UsuarioController();
    $accion = $_POST['accion'] ?? '';

    match ($accion) {
        'registrar' => $ctrl->registrar(),
        'login'     => $ctrl->login(),
        default     => header('Location: ../index.php'),
    };
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = $_GET['accion'] ?? '';
    if ($accion === 'logout') {
        Seguridad::iniciarSesionSegura();
        (new UsuarioController())->logout();
    }
}
