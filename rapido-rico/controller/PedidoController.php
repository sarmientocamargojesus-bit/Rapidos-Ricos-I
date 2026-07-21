<?php
/**
 * controller/PedidoController.php
 * ─────────────────────────────────────────────────────────────────────────────
 * CONTROLADOR: PedidoController
 *
 * Orquesta el flujo HTTP de creación y gestión de pedidos.
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   S — SRP: Solo coordina el flujo HTTP. Delega:
 *             • Validación de datos de entrega → Validador.
 *             • Validación y procesamiento de pago → MetodoPagoService.
 *             • Persistencia de pedido → PedidoDAO.
 *             • Seguridad → Seguridad + AuthMiddleware.
 *
 *   O — OCP: Agregar un método de pago (Plin, PayPal) no modifica este
 *             controlador; solo se registra en MetodoPagoService.
 *
 *   D — DIP: Depende de PedidoDAO y MetodoPagoService (abstracciones),
 *             ambos inyectables para facilitar pruebas unitarias.
 *
 * FLUJO confirmar():
 *   1. Verificar CSRF token.
 *   2. Guard: usuario autenticado + carrito no vacío.
 *   3. Validar datos de entrega (Validador::datosPedido).
 *   4. Crear pedido en BD (transacción: cabecera + detalles).
 *   5. Procesar pago (MetodoPagoService — Strategy Pattern).
 *   6. Si pago falla → marcar pedido cancelado + volver con errores.
 *   7. Si éxito → limpiar carrito + redirigir a mis_pedidos.
 *
 * MEJORAS v2.0:
 *   - Protección CSRF.
 *   - Validación de datos de entrega con Validador.
 *   - Rollback en pago fallido (marcar cancelado).
 *   - Logging de creación de pedidos.
 *   - Sanitización de datos de entrada.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../dao/PedidoDAO.php';
require_once __DIR__ . '/../model/Pedido.php';
require_once __DIR__ . '/../model/StockInsuficienteException.php';
require_once __DIR__ . '/../service/MetodoPagoService.php';
require_once __DIR__ . '/../helpers/Seguridad.php';
require_once __DIR__ . '/../helpers/Validador.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class PedidoController
{
    /** @var PedidoDAO DAO de pedidos */
    private PedidoDAO $pedidoDAO;

    /** @var MetodoPagoService Servicio de pagos (patrón Strategy) */
    private MetodoPagoService $metodoPagoService;

    /**
     * Constructor con inyección de dependencias (DIP).
     *
     * @param PedidoDAO|null         $pedidoDAO
     * @param MetodoPagoService|null $metodoPagoService
     */
    public function __construct(
        ?PedidoDAO         $pedidoDAO         = null,
        ?MetodoPagoService $metodoPagoService = null
    ) {
        Seguridad::iniciarSesionSegura();
        $this->pedidoDAO         = $pedidoDAO         ?? new PedidoDAO();
        $this->metodoPagoService = $metodoPagoService ?? new MetodoPagoService();
    }

    // ── Acción: Confirmar pedido (cliente) ───────────────────────────────────

    /**
     * Confirma un nuevo pedido completo: valida entrega, crea pedido, procesa pago.
     *
     * @return void  (redirige siempre al finalizar)
     */
    public function confirmar(): void
    {
        // ── 1. CSRF ───────────────────────────────────────────────────────────
        AuthMiddleware::requerirCsrf('../view/cliente/confirmar_pedido.php');

        // ── 2. Guards ─────────────────────────────────────────────────────────
        AuthMiddleware::requerirLogin('../view/cliente/login.php');

        $carrito = $_SESSION['carrito'] ?? [];
        if (empty($carrito)) {
            $this->redirigir('../view/cliente/menu.php');
        }

        // ── 3. Sanitizar y validar datos de entrega ───────────────────────────
        $datosEntrega = [
            'direccion'  => Seguridad::texto($_POST['direccion']  ?? ''),
            'referencia' => Seguridad::texto($_POST['referencia'] ?? ''),
            'telefono'   => Seguridad::soloDigitos($_POST['telefono'] ?? ''),
        ];

        $erroresEntrega = Validador::datosPedido($datosEntrega);
        if (!empty($erroresEntrega)) {
            $_SESSION['errores'] = $erroresEntrega;
            $this->redirigir('../view/cliente/confirmar_pedido.php');
        }

        // ── 4. Validar método de pago ─────────────────────────────────────────
        $metodo = Seguridad::texto($_POST['metodo_pago'] ?? '');
        if (!$this->metodoPagoService->esMetodoValido($metodo)) {
            $_SESSION['errores'] = ['Debes seleccionar un método de pago válido.'];
            $this->redirigir('../view/cliente/confirmar_pedido.php');
        }

        // ── 5. Calcular total (servidor, no confiar en POST) ──────────────────
        $subtotal = array_reduce(
            $carrito,
            fn($sum, $item) => $sum + ((float)$item['precio'] * (int)$item['cantidad']),
            0.0
        );
        $total = round($subtotal + DELIVERY_FEE, 2);

        // ── 6. Construir entidad Pedido (Model) ───────────────────────────────
        $pedido = new Pedido(
            id_usuario : (int) $_SESSION['id_usuario'],
            total      : $total,
            direccion  : $datosEntrega['direccion'],
            referencia : $datosEntrega['referencia'] ?: null,
            telefono   : $datosEntrega['telefono']
        );

        // ── 7. Preparar ítems del carrito (sanitizados) ────────────────────────
        $items = array_map(fn($item) => [
            'cantidad'    => (int)   $item['cantidad'],
            'precio'      => (float) $item['precio'],
            'id_producto' => (int)   $item['id_producto'],
            'nombre'      => (string)($item['nombre'] ?? 'producto'),
        ], $carrito);

        // ── 8. Crear pedido en BD (transacción — PedidoDAO, valida stock) ─────
        try {
            $idPedido = $this->pedidoDAO->crear($pedido, $items);
        } catch (StockInsuficienteException $e) {
            Logger::warning('Pedido rechazado por falta de stock', [
                'usuario'  => $_SESSION['id_usuario'],
                'producto' => $e->getNombreProducto(),
                'stock'    => $e->getStockDisponible(),
            ]);
            $_SESSION['stock_insuficiente'] = [
                'producto' => $e->getNombreProducto(),
                'stock'    => $e->getStockDisponible(),
                'mensaje'  => $e->getMessage(),
            ];
            $this->redirigir('../view/cliente/carrito.php');
        } catch (Exception $e) {
            Logger::error('Error al crear pedido', ['usuario' => $_SESSION['id_usuario'], 'error' => $e->getMessage()]);
            $_SESSION['errores'] = ['Error al registrar el pedido. Por favor intenta de nuevo.'];
            $this->redirigir('../view/cliente/confirmar_pedido.php');
        }

        // ── 9. Procesar pago (Strategy Pattern via MetodoPagoService) ─────────
        $resultadoPago = $this->metodoPagoService->procesarPago(
            metodo:     $metodo,
            datos:      $_POST,
            id_pedido:  $idPedido,
            id_usuario: (int) $_SESSION['id_usuario'],
            monto:      $total
        );

        if (!$resultadoPago['exito']) {
            // Pago fallido: cancelar pedido para mantener consistencia
            $this->pedidoDAO->cambiarEstado($idPedido, 'cancelado');
            Logger::warning('Pago fallido, pedido cancelado', ['id_pedido' => $idPedido, 'metodo' => $metodo]);
            $_SESSION['errores'] = $resultadoPago['errores'];
            $this->redirigir('../view/cliente/confirmar_pedido.php');
        }

        // ── 10. Éxito: limpiar carrito y notificar ────────────────────────────
        Logger::info('Pedido confirmado', [
            'id_pedido'  => $idPedido,
            'total'      => $total,
            'metodo'     => $metodo,
            'id_usuario' => $_SESSION['id_usuario'],
        ]);

        unset($_SESSION['carrito']);
        $_SESSION['exito'] = '¡Pedido ' . PEDIDO_PREFIJO . '-' . str_pad($idPedido, 6, '0', STR_PAD_LEFT) . ' confirmado! Estamos preparando tu pedido.';
        $this->redirigir('../view/cliente/mis_pedidos.php');
    }

    // ── Acción: Cambiar estado de pedido (admin) ─────────────────────────────

    /**
     * Actualiza el estado de un pedido (solo administradores).
     *
     * @return void
     */
    public function cambiarEstado(): void
    {
        Seguridad::iniciarSesionSegura();
        AuthMiddleware::requerirAdmin();
        AuthMiddleware::requerirCsrf('../view/admin/pedidos.php');

        $id_pedido = Seguridad::entero($_POST['id_pedido'] ?? 0);
        $estado    = Seguridad::texto($_POST['estado']     ?? '');

        if ($id_pedido <= 0 || !Validador::estadoPedido($estado)) {
            $_SESSION['errores'] = ['Datos de actualización inválidos.'];
            $this->redirigir('../view/admin/pedidos.php');
        }

        $ok = $this->pedidoDAO->cambiarEstado($id_pedido, $estado);

        if ($ok) {
            Logger::info('Estado de pedido actualizado', ['id_pedido' => $id_pedido, 'estado' => $estado, 'admin' => $_SESSION['id_usuario']]);
            $_SESSION['exito'] = 'Estado del pedido actualizado a "' . htmlspecialchars($estado) . '"';
        } else {
            $_SESSION['errores'] = ['No se pudo actualizar el estado del pedido.'];
        }

        $this->redirigir('../view/admin/pedidos.php?ver=' . $id_pedido);
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

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
    $ctrl   = new PedidoController();
    $accion = Seguridad::texto($_POST['accion'] ?? '');

    match ($accion) {
        'confirmar'     => $ctrl->confirmar(),
        'cambiarEstado' => $ctrl->cambiarEstado(),
        default         => header('Location: ../index.php'),
    };
}
