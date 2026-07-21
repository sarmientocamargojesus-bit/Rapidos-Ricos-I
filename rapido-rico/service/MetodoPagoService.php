<?php
/**
 * service/MetodoPagoService.php
 * ─────────────────────────────────────────────────────────────────────────────
 * SERVICIO: MetodoPagoService
 *
 * Orquestador central de todos los métodos de pago del sistema.
 * Actúa como CONTEXTO en el Patrón Strategy: selecciona y ejecuta la
 * estrategia de pago correcta en tiempo de ejecución según la elección
 * del usuario.
 *
 * También actúa como FACHADA (Facade) para el controlador: este último
 * solo necesita llamar a procesarPago() sin saber nada de tarjetas o Yape.
 *
 * PATRÓN DE DISEÑO:
 *   Strategy  — Selecciona dinámicamente la implementación de pago.
 *   Facade    — Simplifica la interfaz de la capa de servicios de pago.
 *   Registry  — Mantiene un registro de estrategias disponibles (OCP).
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — Single Responsibility Principle (SRP):
 *       Solo coordina la selección y ejecución de estrategias de pago.
 *       No persiste datos (eso es PagoDAO), no gestiona pedidos.
 *
 *   O — Open/Closed Principle (OCP):
 *       Agregar un nuevo método (ej.: Plin) requiere:
 *         1. Crear PlinPagoService implements MetodoPagoInterface.
 *         2. Registrarlo con $service->registrar(new PlinPagoService()).
 *       NINGÚN código existente se modifica.
 *
 *   D — Dependency Inversion Principle (DIP):
 *       Depende de MetodoPagoInterface (abstracción), no de clases concretas.
 *       Las dependencias concretas se inyectan desde fuera (constructor o
 *       método registrar), no se crean internamente.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/MetodoPagoInterface.php';
require_once __DIR__ . '/TarjetaPagoService.php';
require_once __DIR__ . '/YapePagoService.php';
require_once __DIR__ . '/../dao/PagoDAO.php';
require_once __DIR__ . '/../helpers/Logger.php';

class MetodoPagoService
{
    /**
     * Registro de estrategias disponibles.
     * Clave: identificador del método (ej.: 'tarjeta', 'yape').
     * Valor: instancia que implementa MetodoPagoInterface.
     *
     * @var array<string, MetodoPagoInterface>
     */
    private array $estrategias = [];

    /**
     * DAO para persistir el pago resultante.
     * Inyección de dependencia: no se instancia internamente (DIP).
     *
     * @var PagoDAO
     */
    private PagoDAO $pagoDAO;

    // ── Constructor ───────────────────────────────────────────────────────────

    /**
     * Constructor con inyección de dependencias.
     *
     * Se registran automáticamente las estrategias base del sistema.
     * Para agregar más métodos se puede llamar a registrar() desde fuera.
     *
     * @param PagoDAO|null $pagoDAO  DAO de pagos (se crea uno si no se pasa)
     */
    public function __construct(?PagoDAO $pagoDAO = null)
    {
        // DIP: se acepta una dependencia externa; si no se provee, se crea
        $this->pagoDAO = $pagoDAO ?? new PagoDAO();

        // Registrar estrategias concretas (OCP: solo aquí se conocen las clases concretas)
        $this->registrar(new TarjetaPagoService());
        $this->registrar(new YapePagoService());
    }

    // ── API pública ───────────────────────────────────────────────────────────

    /**
     * Registra una nueva estrategia de pago en el sistema.
     *
     * OCP en acción: cualquier código externo puede agregar un nuevo método
     * sin modificar este servicio.
     *
     * @param  MetodoPagoInterface $estrategia  Implementación concreta
     * @return void
     */
    public function registrar(MetodoPagoInterface $estrategia): void
    {
        $this->estrategias[$estrategia->getIdentificador()] = $estrategia;
    }

    /**
     * Punto de entrada principal: valida y procesa un pago completo.
     *
     * Flujo interno:
     *  1. Resuelve la estrategia según $metodo.
     *  2. Delega la validación a la estrategia.
     *  3. Si es válido, construye el objeto Pago y lo persiste.
     *  4. Guarda un resumen en sesión para la vista de confirmación.
     *
     * @param  string $metodo      Identificador del método ('tarjeta' | 'yape')
     * @param  array  $datos       Datos crudos del POST
     * @param  int    $id_pedido   ID del pedido recién creado
     * @param  int    $id_usuario  ID del usuario autenticado
     * @param  float  $monto       Monto total
     * @return array{
     *           exito: bool,
     *           errores: array<string>,
     *           pago: Pago|null
     *         }
     *         Resultado del procesamiento
     */
    public function procesarPago(
        string $metodo,
        array  $datos,
        int    $id_pedido,
        int    $id_usuario,
        float  $monto
    ): array {

        // ── 1. Resolver estrategia ────────────────────────────────────────────
        $estrategia = $this->resolverEstrategia($metodo);
        if ($estrategia === null) {
            return [
                'exito'   => false,
                'errores' => ["Método de pago '{$metodo}' no reconocido."],
                'pago'    => null,
            ];
        }

        // ── 2. Validar datos (delegado a la estrategia concreta) ──────────────
        $errores = $estrategia->validar($datos);
        if (!empty($errores)) {
            return [
                'exito'   => false,
                'errores' => $errores,
                'pago'    => null,
            ];
        }

        // ── 3. Construir entidad Pago y persistirla ───────────────────────────
        try {
            $pago = $estrategia->construirPago($datos, $id_pedido, $id_usuario, $monto);
            $pago->id_pago = $this->pagoDAO->crear($pago);

            // ── 4. Guardar resumen en sesión (sin datos sensibles) ─────────────
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['info_pago'] = $pago->descripcion();

            return [
                'exito'   => true,
                'errores' => [],
                'pago'    => $pago,
            ];
        } catch (Exception $e) {
            Logger::error('Error al registrar el pago en la base de datos', [
                'id_pedido' => $id_pedido,
                'metodo'    => $metodo,
                'error'     => $e->getMessage()
            ]);
            return [
                'exito'   => false,
                'errores' => ['Error al registrar el pago. Asegúrate de haber ejecutado la migración de la base de datos (' . $e->getMessage() . ').'],
                'pago'    => null,
            ];
        }
    }

    /**
     * Retorna los identificadores de todos los métodos de pago disponibles.
     * Útil para la vista: puede iterar los métodos sin conocer las clases.
     *
     * @return array<string>  Ej.: ['tarjeta', 'yape']
     */
    public function getMetodosDisponibles(): array
    {
        return array_keys($this->estrategias);
    }

    /**
     * Comprueba si un método de pago está registrado en el sistema.
     *
     * @param  string $metodo  Identificador a verificar
     * @return bool
     */
    public function esMetodoValido(string $metodo): bool
    {
        return isset($this->estrategias[$metodo]);
    }

    // ── Métodos privados ──────────────────────────────────────────────────────

    /**
     * Resuelve y retorna la estrategia correspondiente al identificador dado.
     *
     * @param  string $metodo  Identificador del método de pago
     * @return MetodoPagoInterface|null  null si no existe la estrategia
     */
    private function resolverEstrategia(string $metodo): ?MetodoPagoInterface
    {
        return $this->estrategias[$metodo] ?? null;
    }
}
