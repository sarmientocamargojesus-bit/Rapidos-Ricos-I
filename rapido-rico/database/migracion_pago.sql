-- =====================================================
-- MIGRACIÓN: crear tabla 'pago'
-- Ejecutar sobre una base de datos rapido_rico existente
--
-- Cómo ejecutarlo (phpMyAdmin):
--   1. Selecciona la base de datos rapido_rico.
--   2. Pestaña "SQL" -> pega este contenido -> click "Continuar".
--
-- Cómo ejecutarlo (consola MySQL):
--   mysql -u root -p rapido_rico < database/migracion_pago.sql
-- =====================================================

USE rapido_rico;

CREATE TABLE IF NOT EXISTS pago (
    id_pago        INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido      INT NOT NULL,
    id_usuario     INT NOT NULL,
    metodo         VARCHAR(20) NOT NULL,
    banco          VARCHAR(50) DEFAULT NULL,
    ultimos_cuatro VARCHAR(4)  DEFAULT NULL,
    titular        VARCHAR(100) DEFAULT NULL,
    dni            VARCHAR(15) DEFAULT NULL,
    yape_telefono  VARCHAR(20) DEFAULT NULL,
    estado         ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    monto          DECIMAL(10,2) NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pedido)  REFERENCES pedido(id_pedido)   ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
