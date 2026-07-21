<?php
/**
 * view/cliente/agregar_carrito.php — Agregar al carrito v2.0
 *
 * Endpoint AJAX que agrega un producto al carrito de sesión.
 * Responde JSON: { ok: bool, qty: int, msg: string }
 *
 * PRINCIPIO SRP: Solo gestiona la lógica de agregar al carrito.
 * SEGURIDAD: Sanitización de entradas, session segura.
 */

require_once __DIR__ . '/../../helpers/Seguridad.php';
require_once __DIR__ . '/../../dao/ProductoDAO.php';

Seguridad::iniciarSesionSegura();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

$id_producto = Seguridad::entero($_POST['id_producto'] ?? 0);

if ($id_producto <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Producto inválido']);
    exit;
}

// Verificar que el producto existe en BD (no confiar solo en el POST)
$dao     = new ProductoDAO();
$producto = $dao->buscarPorId($id_producto);

if (!$producto) {
    echo json_encode(['ok' => false, 'msg' => 'Producto no encontrado']);
    exit;
}

if ((int)$producto['stock'] <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'No hay stock disponible para este producto en este momento.']);
    exit;
}

// ── Agregar al carrito de sesión ──────────────────────────────────────────────
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Buscar si el producto ya está en el carrito para sumar cantidad
$encontrado = false;
foreach ($_SESSION['carrito'] as &$item) {
    if ((int)$item['id_producto'] === $id_producto) {
        $cantidadDeseada = (int)$item['cantidad'] + 1;
        if ($cantidadDeseada > (int)$producto['stock']) {
            unset($item);
            echo json_encode([
                'ok'  => false,
                'msg' => 'Solo quedan ' . (int)$producto['stock'] . ' unidades disponibles de este producto.',
            ]);
            exit;
        }
        $item['cantidad'] = min($cantidadDeseada, 20);
        $encontrado = true;
        break;
    }
}
unset($item);

if (!$encontrado) {
    $_SESSION['carrito'][] = [
        'id_producto' => $id_producto,
        'nombre'      => $producto['nombre'],
        'precio'      => (float) $producto['precio'],
        'imagen'      => $producto['imagen'] ?? 'default.jpg',
        'cantidad'    => 1,
    ];
}

$qty_total = array_sum(array_column($_SESSION['carrito'], 'cantidad'));

echo json_encode([
    'ok'  => true,
    'qty' => $qty_total,
    'msg' => 'Producto agregado al carrito',
]);
