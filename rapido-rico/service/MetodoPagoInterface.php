<?php
/**
 * service/MetodoPagoInterface.php
 * ─────────────────────────────────────────────────────────────────────────────
 * INTERFAZ: MetodoPagoInterface
 *
 * Define el contrato que debe cumplir CUALQUIER método de pago del sistema.
 * Es el núcleo del PATRÓN STRATEGY aplicado a los pagos.
 *
 * PATRÓN DE DISEÑO:
 *   Strategy — Permite que el algoritmo de validación/procesamiento de pago
 *   varíe independientemente de los clientes que lo usan (PedidoController
 *   y MetodoPagoService).  Se puede agregar un nuevo método de pago
 *   (ej.: PlinPagoService) implementando esta interfaz, sin tocar ningún
 *   código existente.
 *
 * PRINCIPIOS SOLID APLICADOS:
 *   O — Open/Closed Principle (OCP):
 *       El sistema está abierto para EXTENSIÓN (nuevos métodos de pago) y
 *       cerrado para MODIFICACIÓN.  Agregar Plin solo requiere crear
 *       PlinPagoService que implemente esta interfaz.
 *
 *   L — Liskov Substitution Principle (LSP):
 *       Cualquier implementación de esta interfaz puede sustituir a otra
 *       en MetodoPagoService sin romper el comportamiento esperado.
 *
 *   I — Interface Segregation Principle (ISP):
 *       La interfaz es pequeña y cohesiva: solo los métodos que cualquier
 *       método de pago necesita.  No se obliga a implementar métodos
 *       irrelevantes.
 *
 *   D — Dependency Inversion Principle (DIP):
 *       MetodoPagoService depende de esta ABSTRACCIÓN, no de clases
 *       concretas como TarjetaPagoService o YapePagoService.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../model/Pago.php';

interface MetodoPagoInterface
{
    /**
     * Valida los datos del formulario POST para este método de pago.
     *
     * Cada implementación aplica sus propias reglas de negocio:
     * - TarjetaPagoService valida número de tarjeta, CVV, banco, etc.
     * - YapePagoService valida teléfono, código Yape, etc.
     *
     * @param  array $datos  Array asociativo con los campos $_POST relevantes
     * @return array         Array de mensajes de error; vacío si todo es válido
     */
    public function validar(array $datos): array;

    /**
     * Construye y retorna un objeto Pago con los datos ya validados.
     *
     * Este método transforma los datos crudos del POST en una entidad Pago
     * lista para ser persistida por PagoDAO.
     *
     * @param  array $datos      Datos del formulario POST (ya saneados)
     * @param  int   $id_pedido  ID del pedido recién creado
     * @param  int   $id_usuario ID del usuario autenticado
     * @param  float $monto      Monto total del pedido
     * @return Pago              Objeto Pago listo para persistir
     */
    public function construirPago(array $datos, int $id_pedido, int $id_usuario, float $monto): Pago;

    /**
     * Retorna el identificador único de este método de pago.
     *
     * Se usa como clave en el registro de MetodoPagoService para seleccionar
     * la estrategia correcta en tiempo de ejecución.
     *
     * @return string  Ej.: 'tarjeta' | 'yape'
     */
    public function getIdentificador(): string;
}
