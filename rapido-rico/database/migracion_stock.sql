-- =====================================================
-- MIGRACIÓN: agregar control de stock a productos
-- Ejecutar UNA sola vez sobre una base de datos rapido_rico
-- que ya existía antes de esta funcionalidad.
--
-- Cómo ejecutarlo (phpMyAdmin):
--   1. Selecciona la base de datos rapido_rico.
--   2. Pestaña "SQL" → pega este contenido → "Continuar".
--
-- Cómo ejecutarlo (consola MySQL):
--   mysql -u root -p rapido_rico < database/migracion_stock.sql
-- =====================================================

USE rapido_rico;

-- Agrega la columna stock solo si todavía no existe (evita error si se corre 2 veces)
SET @col_existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'producto'
      AND COLUMN_NAME  = 'stock'
);

SET @sql := IF(
    @col_existe = 0,
    'ALTER TABLE producto ADD COLUMN stock INT NOT NULL DEFAULT 0 AFTER id_categoria',
    'SELECT "La columna stock ya existe, no se hizo nada."'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Opcional: si quieres que tus productos actuales empiecen con stock
-- disponible en vez de 0 (0 = "sin stock" y bloquearía pedidos), descomenta:
-- UPDATE producto SET stock = 50 WHERE stock = 0;
