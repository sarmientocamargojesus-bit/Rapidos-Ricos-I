-- =====================================================
-- BASE DE DATOS: rapido_rico
-- Sistema Web de Pedidos "Rápido & Rico"
-- Compatible con MySQL 5.7+ / XAMPP
-- =====================================================

CREATE DATABASE IF NOT EXISTS rapido_rico
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE rapido_rico;

-- ─── TABLA: usuario ───────────────────────────────────
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario  INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    correo      VARCHAR(150) NOT NULL UNIQUE,
    contrasena  VARCHAR(255) NOT NULL,          -- password_hash()
    telefono    VARCHAR(20)  DEFAULT NULL,
    direccion   VARCHAR(255) DEFAULT NULL,
    rol         ENUM('cliente','admin') NOT NULL DEFAULT 'cliente',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLA: categoria ─────────────────────────────────
CREATE TABLE IF NOT EXISTS categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(80)  NOT NULL,
    descripcion  TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLA: producto ──────────────────────────────────
CREATE TABLE IF NOT EXISTS producto (
    id_producto  INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(120)   NOT NULL,
    descripcion  TEXT           DEFAULT NULL,
    precio       DECIMAL(10,2)  NOT NULL,
    imagen       VARCHAR(255)   DEFAULT 'default.jpg',
    id_categoria INT            NOT NULL,
    stock        INT            NOT NULL DEFAULT 0,
    activo       TINYINT(1)     DEFAULT 1,
    created_at   TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLA: pedido ────────────────────────────────────
CREATE TABLE IF NOT EXISTS pedido (
    id_pedido  INT AUTO_INCREMENT PRIMARY KEY,
    fecha      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    estado     ENUM('pendiente','en_preparacion','listo','entregado','cancelado')
               NOT NULL DEFAULT 'pendiente',
    total      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    direccion  VARCHAR(255)  DEFAULT NULL,
    referencia VARCHAR(255)  DEFAULT NULL,
    telefono   VARCHAR(20)   DEFAULT NULL,
    id_usuario INT           NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLA: detalle_pedido ────────────────────────────
CREATE TABLE IF NOT EXISTS detalle_pedido (
    id_detalle  INT AUTO_INCREMENT PRIMARY KEY,
    cantidad    INT           NOT NULL DEFAULT 1,
    precio      DECIMAL(10,2) NOT NULL,
    id_pedido   INT           NOT NULL,
    id_producto INT           NOT NULL,
    FOREIGN KEY (id_pedido)   REFERENCES pedido(id_pedido)   ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLA: carrito ───────────────────────────────────
CREATE TABLE IF NOT EXISTS carrito (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    fecha      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT       NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLA: detalle_carrito ───────────────────────────
CREATE TABLE IF NOT EXISTS detalle_carrito (
    id_detalle_carrito INT AUTO_INCREMENT PRIMARY KEY,
    cantidad           INT NOT NULL DEFAULT 1,
    id_carrito         INT NOT NULL,
    id_producto        INT NOT NULL,
    FOREIGN KEY (id_carrito)   REFERENCES carrito(id_carrito)    ON DELETE CASCADE,
    FOREIGN KEY (id_producto)  REFERENCES producto(id_producto)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── TABLA: pago ──────────────────────────────────────
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

-- =====================================================
-- DATOS DE PRUEBA
-- =====================================================

-- Usuarios (contraseña: Admin1234 y Cliente1234)
INSERT INTO usuario (nombre, correo, contrasena, telefono, direccion, rol) VALUES
('Administrador', 'admin@rapidoyrico.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '987654321', 'Av. Los Maestros 121 - Ica', 'admin'),
('Juan Pérez', 'juan@correo.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '987654321', 'Av. Los Maestros 121 - Ica', 'cliente');

-- Categorías
INSERT INTO categoria (nombre, descripcion) VALUES
('Hamburguesas', 'Deliciosas hamburguesas artesanales'),
('Combos',       'Combos completos al mejor precio'),
('Bebidas',      'Bebidas frías y calientes'),
('Snacks',       'Papas fritas, nuggets y más'),
('Postres',      'Postres dulces y helados');

-- Productos
INSERT INTO producto (nombre, descripcion, precio, imagen, id_categoria) VALUES
('Hamburguesa Clásica',  'Carne, lechuga, tomate, queso',           18.00, 'hamburguesa_clasica.jpg',  1),
('Hamburguesa Doble',    'Doble carne, queso, tocino',              26.00, 'hamburguesa_doble.jpg',    1),
('Hamburguesa BBQ',      'Carne BBQ, cebolla caramelizada, queso',  22.00, 'hamburguesa_bbq.jpg',      1),
('Combo Clásico',        'Hamburguesa + Papas + Bebida',            22.00, 'combo_clasico.jpg',        2),
('Combo Familiar',       '2 Hamburguesas + 2 Papas + 2 Bebidas',    42.00, 'combo_familiar.jpg',       2),
('Gaseosa 500ml',        'Coca-Cola, Pepsi, Inca Kola',              4.00, 'gaseosa.jpg',              3),
('Jugo Natural',         'Maracuyá, naranja o fresa',                5.00, 'jugo.jpg',                 3),
('Papas Fritas',         'Porción personal crujiente',               6.00, 'papas.jpg',                4),
('Nuggets (6 und.)',     'Nuggets de pollo con salsa',               8.00, 'nuggets.jpg',              4),
('Helado de Vainilla',   'Helado artesanal 2 bolas',                 5.00, 'helado.jpg',               5);
