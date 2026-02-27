-- Script limpio para crear la base de datos DISTRICARNES
-- Compatible con pgAdmin Query Tool

-- ============================================
-- 1. CREACIÓN DE SECUENCIAS
-- ============================================

CREATE SEQUENCE IF NOT EXISTS caja_caja_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS categoria_categoria_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS categorias_id_categoria_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS cliente_id_cliente_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS contrato_proveedor_id_contrato_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS detalle_venta_id_detalle_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS empresa_empresa_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS entrega_id_entrega_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS lotes_id_lote_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS notificacion_id_notificacion_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS ofertas_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS order_items_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS orders_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS password_resets_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS preferencia_cliente_id_preferencia_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS producto_id_producto_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS proveedor_id_proveedor_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS usuario_id_usuario_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS venta_detalle_venta_detalle_id_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

CREATE SEQUENCE IF NOT EXISTS venta_id_venta_seq
    AS integer START WITH 1 INCREMENT BY 1 NO MINVALUE NO MAXVALUE CACHE 1;

-- ============================================
-- 2. CREACIÓN DE TABLAS
-- ============================================

CREATE TABLE IF NOT EXISTS caja (
    caja_id integer NOT NULL DEFAULT nextval('caja_caja_id_seq'),
    caja_numero integer NOT NULL,
    caja_nombre character varying(100) NOT NULL,
    caja_efectivo numeric(30,2) NOT NULL,
    CONSTRAINT caja_pkey PRIMARY KEY (caja_id)
);

CREATE TABLE IF NOT EXISTS categoria (
    categoria_id integer NOT NULL DEFAULT nextval('categoria_categoria_id_seq'),
    categoria_nombre character varying(50) NOT NULL,
    categoria_ubicacion character varying(150) NOT NULL,
    CONSTRAINT categoria_pkey PRIMARY KEY (categoria_id)
);

CREATE TABLE IF NOT EXISTS categorias (
    id_categoria integer NOT NULL DEFAULT nextval('categorias_id_categoria_seq'),
    nombre_categoria character varying(50) NOT NULL,
    CONSTRAINT categorias_pkey PRIMARY KEY (id_categoria)
);

CREATE TABLE IF NOT EXISTS cliente (
    id_cliente integer NOT NULL DEFAULT nextval('cliente_id_cliente_seq'),
    nombre character varying(45) NOT NULL,
    apellido character varying(45) NOT NULL,
    correo character varying(100),
    telefono character varying(20),
    direccion character varying(255),
    cliente_id integer GENERATED ALWAYS AS (id_cliente) STORED,
    cliente_tipo_documento character varying(20) DEFAULT 'Cedula',
    cliente_numero_documento character varying(35),
    cliente_nombre character varying(50),
    cliente_apellido character varying(50),
    cliente_provincia character varying(30) DEFAULT 'N/A',
    cliente_ciudad character varying(30) DEFAULT 'N/A',
    cliente_direccion character varying(70),
    cliente_telefono character varying(20),
    cliente_email character varying(50),
    CONSTRAINT cliente_pkey PRIMARY KEY (id_cliente),
    CONSTRAINT uq_cliente_correo UNIQUE (correo)
);

CREATE TABLE IF NOT EXISTS proveedor (
    id_proveedor integer NOT NULL DEFAULT nextval('proveedor_id_proveedor_seq'),
    nombre character varying(100) NOT NULL,
    contacto character varying(100),
    telefono character varying(20),
    correo character varying(100),
    direccion character varying(255),
    CONSTRAINT proveedor_pkey PRIMARY KEY (id_proveedor)
);

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario integer NOT NULL DEFAULT nextval('usuario_id_usuario_seq'),
    nombres_completos character varying(255) NOT NULL,
    cedula character varying(50) NOT NULL,
    direccion character varying(255),
    celular character varying(20),
    correo_electronico character varying(100) NOT NULL,
    contrasena character varying(255) NOT NULL,
    rol character varying(10) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    usuario_usuario character varying(50),
    usuario_foto character varying(200),
    caja_id integer,
    usuario_nombre character varying(100),
    usuario_apellido character varying(100),
    CONSTRAINT usuario_pkey PRIMARY KEY (id_usuario),
    CONSTRAINT uq_usuario_cedula UNIQUE (cedula),
    CONSTRAINT uq_usuario_correo UNIQUE (correo_electronico),
    CONSTRAINT usuario_rol_check CHECK ((rol)::text = ANY (ARRAY['admin'::text, 'trabajo'::text, 'empleado'::text]))
);

CREATE TABLE IF NOT EXISTS producto (
    id_producto integer NOT NULL DEFAULT nextval('producto_id_producto_seq'),
    nombre character varying(100) NOT NULL,
    precio_venta numeric(10,2) NOT NULL,
    precio_abastecimiento numeric(10,2) NOT NULL,
    precio_compra_lote numeric(10,2),
    descripcion text,
    fecha_caducidad date,
    numero_lote character varying(32),
    stock integer DEFAULT 0 NOT NULL,
    id_proveedor integer NOT NULL,
    id_categoria integer,
    imagen_producto character varying(255),
    estado boolean DEFAULT true NOT NULL,
    imagen character varying(500),
    codigo character varying(77),
    tipo_unidad character varying(20) DEFAULT 'Unidad',
    marca character varying(35),
    modelo character varying(35),
    producto_id integer GENERATED ALWAYS AS (id_producto) STORED,
    producto_codigo character varying(77),
    producto_nombre character varying(100),
    producto_stock_total integer,
    producto_tipo_unidad character varying(20),
    producto_precio_compra numeric(30,2),
    producto_precio_venta numeric(30,2),
    producto_marca character varying(35),
    producto_modelo character varying(35),
    producto_estado character varying(20),
    producto_foto character varying(500),
    categoria_id integer,
    CONSTRAINT producto_pkey PRIMARY KEY (id_producto),
    CONSTRAINT producto_precio_abastecimiento_check CHECK ((precio_abastecimiento >= (0)::numeric)),
    CONSTRAINT producto_precio_venta_check CHECK ((precio_venta >= (0)::numeric)),
    CONSTRAINT producto_stock_check CHECK ((stock >= 0))
);

CREATE TABLE IF NOT EXISTS lotes (
    id_lote integer NOT NULL DEFAULT nextval('lotes_id_lote_seq'),
    id_producto integer NOT NULL,
    numero_lote character varying(50),
    fecha_caducidad date NOT NULL,
    precio_compra_lote numeric(10,2) NOT NULL,
    descripcion text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT lotes_pkey PRIMARY KEY (id_lote),
    CONSTRAINT lotes_precio_compra_lote_check CHECK ((precio_compra_lote >= (0)::numeric)),
    CONSTRAINT uk_producto_lote UNIQUE (id_producto, numero_lote)
);

CREATE TABLE IF NOT EXISTS contrato_proveedor (
    id_contrato integer NOT NULL DEFAULT nextval('contrato_proveedor_id_contrato_seq'),
    id_proveedor integer NOT NULL,
    fecha_inicio date NOT NULL,
    fecha_fin date,
    terminos text NOT NULL,
    estado character varying(10) DEFAULT 'activo',
    CONSTRAINT contrato_proveedor_pkey PRIMARY KEY (id_contrato),
    CONSTRAINT contrato_proveedor_estado_check CHECK (((estado)::text = ANY (ARRAY['activo'::text, 'vencido'::text, 'cancelado'::text])))
);

CREATE TABLE IF NOT EXISTS venta (
    id_venta integer NOT NULL DEFAULT nextval('venta_id_venta_seq'),
    fecha timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    total numeric(10,2) NOT NULL,
    id_cliente integer NOT NULL,
    id_usuario integer NOT NULL,
    metodo_pago character varying(50) NOT NULL,
    venta_id integer GENERATED ALWAYS AS (id_venta) STORED,
    venta_codigo character varying(200),
    venta_fecha date,
    venta_hora character varying(17),
    venta_total numeric(30,2),
    venta_pagado numeric(30,2),
    venta_cambio numeric(30,2),
    usuario_id integer,
    cliente_id integer,
    caja_id integer,
    CONSTRAINT venta_pkey PRIMARY KEY (id_venta),
    CONSTRAINT venta_total_check CHECK ((total >= (0)::numeric))
);

CREATE TABLE IF NOT EXISTS detalle_venta (
    id_detalle integer NOT NULL DEFAULT nextval('detalle_venta_id_detalle_seq'),
    id_venta integer NOT NULL,
    id_producto integer NOT NULL,
    cantidad integer NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    CONSTRAINT detalle_venta_pkey PRIMARY KEY (id_detalle),
    CONSTRAINT detalle_venta_cantidad_check CHECK ((cantidad > 0)),
    CONSTRAINT detalle_venta_precio_unitario_check CHECK ((precio_unitario >= (0)::numeric))
);

CREATE TABLE IF NOT EXISTS empresa (
    empresa_id integer NOT NULL DEFAULT nextval('empresa_empresa_id_seq'),
    empresa_nombre character varying(90) NOT NULL,
    empresa_telefono character varying(20) NOT NULL,
    empresa_email character varying(50) NOT NULL,
    empresa_direccion character varying(100) NOT NULL,
    CONSTRAINT empresa_pkey PRIMARY KEY (empresa_id)
);

CREATE TABLE IF NOT EXISTS entrega (
    id_entrega integer NOT NULL DEFAULT nextval('entrega_id_entrega_seq'),
    id_venta integer NOT NULL,
    direccion character varying(255) NOT NULL,
    estado character varying(12) DEFAULT 'pendiente',
    fecha_entrega timestamp without time zone,
    CONSTRAINT entrega_pkey PRIMARY KEY (id_entrega),
    CONSTRAINT entrega_estado_check CHECK (((estado)::text = ANY (ARRAY['pendiente'::text, 'en camino'::text, 'entregado'::text, 'fallido'::text])))
);

CREATE TABLE IF NOT EXISTS notificacion (
    id_notificacion integer NOT NULL DEFAULT nextval('notificacion_id_notificacion_seq'),
    id_usuario integer NOT NULL,
    mensaje text NOT NULL,
    fecha timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    leida boolean DEFAULT false,
    tipo character varying(50),
    CONSTRAINT notificacion_pkey PRIMARY KEY (id_notificacion)
);

CREATE TABLE IF NOT EXISTS ofertas (
    id integer NOT NULL DEFAULT nextval('ofertas_id_seq'),
    nombre character varying(255) NOT NULL,
    descripcion text,
    tipo character varying(10) DEFAULT 'percentage' NOT NULL,
    valor_descuento numeric(12,2) DEFAULT 0.00 NOT NULL,
    fecha_inicio timestamp without time zone,
    fecha_fin timestamp without time zone,
    limite_usos integer,
    estado character varying(16) DEFAULT 'inactive' NOT NULL,
    productos_json text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    imagen character varying(255),
    CONSTRAINT ofertas_pkey PRIMARY KEY (id),
    CONSTRAINT ofertas_tipo_check CHECK (((tipo)::text = ANY (ARRAY['percentage'::text, 'fixed'::text, 'bogo'::text])))
);

CREATE TABLE IF NOT EXISTS orders (
    id integer NOT NULL DEFAULT nextval('orders_id_seq'),
    paypal_id character varying(64),
    user_email character varying(255),
    user_name character varying(255),
    status character varying(32) NOT NULL,
    total numeric(12,2) DEFAULT 0.00 NOT NULL,
    delivery_method character varying(32) NOT NULL,
    address_json text,
    schedule_json text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    factus_invoice_id character varying(128),
    factus_number character varying(128),
    factus_status character varying(32),
    factus_pdf_url text,
    CONSTRAINT orders_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS order_items (
    id integer NOT NULL DEFAULT nextval('order_items_id_seq'),
    order_id integer NOT NULL,
    title character varying(255),
    price numeric(12,2) DEFAULT 0.00 NOT NULL,
    qty integer DEFAULT 1 NOT NULL,
    image text,
    CONSTRAINT order_items_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS password_resets (
    id integer NOT NULL DEFAULT nextval('password_resets_id_seq'),
    user_id integer NOT NULL,
    token_hash character(64) NOT NULL,
    expires_at timestamp without time zone NOT NULL,
    used boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT password_resets_pkey PRIMARY KEY (id),
    CONSTRAINT uq_token_hash UNIQUE (token_hash)
);

CREATE TABLE IF NOT EXISTS preferencia_cliente (
    id_preferencia integer NOT NULL DEFAULT nextval('preferencia_cliente_id_preferencia_seq'),
    id_cliente integer NOT NULL,
    categoria character varying(50) NOT NULL,
    valor character varying(100) NOT NULL,
    CONSTRAINT preferencia_cliente_pkey PRIMARY KEY (id_preferencia)
);

CREATE TABLE IF NOT EXISTS venta_detalle (
    venta_detalle_id integer NOT NULL DEFAULT nextval('venta_detalle_venta_detalle_id_seq'),
    venta_detalle_cantidad integer NOT NULL,
    venta_detalle_precio_compra numeric(30,2) NOT NULL,
    venta_detalle_precio_venta numeric(30,2) NOT NULL,
    venta_detalle_total numeric(30,2) NOT NULL,
    venta_detalle_descripcion character varying(200) NOT NULL,
    venta_codigo character varying(200) NOT NULL,
    producto_id integer NOT NULL,
    CONSTRAINT venta_detalle_pkey PRIMARY KEY (venta_detalle_id)
);

-- ============================================
-- 3. CREACIÓN DE ÍNDICES
-- ============================================

CREATE INDEX IF NOT EXISTS idx_lotes_caducidad ON lotes USING btree (fecha_caducidad);
CREATE INDEX IF NOT EXISTS idx_lotes_producto ON lotes USING btree (id_producto);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items USING btree (order_id);

-- ============================================
-- 4. INSERCIÓN DE DATOS
-- ============================================

-- Datos: caja
INSERT INTO caja (caja_id, caja_numero, caja_nombre, caja_efectivo) VALUES (1, 1, 'Caja Principal', 0.00) ON CONFLICT DO NOTHING;

-- Datos: categoria
INSERT INTO categoria (categoria_id, categoria_nombre, categoria_ubicacion) VALUES
(1, 'Cortes Premium Res', 'General'),
(2, 'Cortes Corrientes Res', 'General'),
(3, 'Costillas Res', 'General'),
(4, 'Carne Molida Res', 'General'),
(5, 'Vísceras Res', 'General'),
(6, 'Huesos Res', 'General'),
(7, 'Cortes Premium Cerdo', 'General'),
(8, 'Tocinos Cerdo', 'General'),
(9, 'Costillas Cerdo', 'General'),
(10, 'Pollo Entero', 'General'),
(11, 'Partes de Pollo', 'General'),
(12, 'Otros', 'General') ON CONFLICT DO NOTHING;

-- Datos: categorias
INSERT INTO categorias (id_categoria, nombre_categoria) VALUES
(1, 'Cortes Premium Res'),
(2, 'Cortes Corrientes Res'),
(3, 'Costillas Res'),
(4, 'Carne Molida Res'),
(5, 'Vísceras Res'),
(6, 'Huesos Res'),
(7, 'Cortes Premium Cerdo'),
(8, 'Tocinos Cerdo'),
(9, 'Costillas Cerdo'),
(10, 'Pollo Entero'),
(11, 'Partes de Pollo'),
(12, 'Otros') ON CONFLICT DO NOTHING;

-- Datos: proveedor
INSERT INTO proveedor (id_proveedor, nombre, contacto, telefono, correo, direccion) VALUES
(1, 'Proveedor genérico', NULL, NULL, NULL, NULL) ON CONFLICT DO NOTHING;

-- Datos: usuario (ANTES de password_resets)
INSERT INTO usuario (id_usuario, nombres_completos, cedula, direccion, celular, correo_electronico, contrasena, rol, created_at, updated_at, usuario_usuario, usuario_foto, caja_id, usuario_nombre, usuario_apellido) VALUES
(7, 'comprador', '12234', 'cr 11b 17-95', '23232123131', 'comprador@gmail.com', '$2y$10$CkjAtl.mVsvK4i2A5pV8rubPTcABmETvyIpn68N2rua61F1Qg392C', 'trabajo', '2025-10-04 12:20:51', '2025-10-04 14:18:00', 'comprador', NULL, 1, NULL, NULL),
(8, 'JUAN HUMBERTO VEGA SANCHEZ', '1052732445', 'CR 11B 17-95 VILLANUEVA BOLIVAR', '3108392866', 'juanhumbertovega600@gmail.com', '$2y$10$edAtp.uQEd5hOOK/c/WSXu05Lwyp0Idttx13XaTHbv5nDnvZ8lrKS', 'admin', '2025-10-04 12:24:59', '2025-10-04 12:25:13', 'juanhumbertovega600', NULL, 1, NULL, NULL),
(9, 'juan pablo', '23234234', 'CR 11B 17-95', '32323442342', 'juanpiayola@gmail.com', '$2y$10$6MdcyqCHJAv0Wzme2yPrZ.GXcOXnEoVMNkZa1ApNG5CV0ov0y2TY6', 'trabajo', '2025-10-04 14:09:35', '2026-02-18 21:56:52', 'juanpiayola', NULL, 1, NULL, NULL),
(10, 'unity', '65432151', 'cartegena', '3105246873', 'unityaccess56@gmail.com', '$2y$10$iA47rfsE3jOW8VcJJDJFeOhZfOgRrUFIPVPMaSKwpqEphSaDc6GxC', 'trabajo', '2025-11-07 13:30:53', '2025-11-07 15:45:14', 'unityaccess56', NULL, 1, NULL, NULL),
(13, 'wendy vega sanchez', '1052733976', 'CR 11B 17-95', '3043040067', 'wendyvega@gmail.com', '$2y$10$6cgfQUACcRaXrdRWzzE8J.3fkfcQeqc14m0TzBz//n0WqEfYkJ3Ai', 'trabajo', '2026-02-18 21:53:25', '2026-02-18 16:53:25.147034', 'wendyvega', NULL, 1, NULL, NULL),
(19, 'Maide Sanchez', '1131429178', 'CR 11B 17-95', '32235014660', 'maidesanchez@gmail.com', '$2y$10$sdWBCFEncPhRxDOkvWmWp.aS5SW5QAoWcn0SZBtZ7dd4/8iLkSqoW', 'empleado', '2026-02-20 00:42:18', '2026-02-20 02:50:23', 'maidesanchez', NULL, 1, 'Maide', 'Sanchez') ON CONFLICT DO NOTHING;

-- Datos: producto
INSERT INTO producto (id_producto, nombre, precio_venta, precio_abastecimiento, precio_compra_lote, descripcion, fecha_caducidad, numero_lote, stock, id_proveedor, id_categoria, imagen_producto, estado, tipo_unidad, producto_nombre, producto_stock_total, producto_tipo_unidad, producto_precio_venta, categoria_id) VALUES
(1, 'Lomo fino', 25000.00, 18000.00, 300000.00, 'dededed', '2025-10-07', 'L20251012-CC3543', 1, 1, 1, '/static/images/products/prod_68ebea69caa298.78139733.jpeg', true, 'Unidad', 'Lomo fino', 1, 'Unidad', 25000.00, 1),
(2, 'Lomo ancho', 24000.00, 17500.00, 500000.00, 'Segundo producto que se atualiza', '2025-10-05', 'L20251012-56CAF3', 10, 1, 1, '/static/images/products/prod_68ebea8dc8ae31.97147879.jpeg', true, 'Unidad', 'Lomo ancho', 10, 'Unidad', 24000.00, 1),
(3, 'Punta gorda', 22000.00, 16000.00, 50000.00, '', '2025-10-05', 'L20251012-05F912', 50, 1, 1, '/static/images/products/prod_68ebeaf6726954.44629935.jpeg', true, 'Unidad', 'Punta gorda', 50, 'Unidad', 22000.00, 1),
(4, 'Palomilla', 23000.00, 16500.00, 400000.00, '', '2025-10-05', 'L20251012-B75F68', 50, 1, 1, '/static/images/products/prod_68ebf31d3354e0.79006117.jpeg', true, 'Unidad', 'Palomilla', 50, 'Unidad', 23000.00, 1),
(5, 'Sobre barriga', 21000.00, 15000.00, 300000.00, '', '2025-10-05', 'L20251012-B8A126', 50, 1, 1, '/static/images/products/prod_68ebf35317a883.94345415.jpg', true, 'Unidad', 'Sobre barriga', 50, 'Unidad', 21000.00, 1),
(6, 'Espaldilla', 20000.00, 14500.00, 2.00, '', '2025-10-05', 'L20251012-CE77CF', 50, 1, 1, '/static/images/products/prod_68ebf364ced524.41922891.webp', true, 'Unidad', 'Espaldilla', 50, 'Unidad', 20000.00, 1),
(7, 'Barcino', 19000.00, 14000.00, 2.00, '', '2025-10-05', 'L20251012-1765D5', 50, 1, 1, '/static/images/products/prod_68ebf37ec581a7.64419766.jpg', true, 'Unidad', 'Barcino', 50, 'Unidad', 19000.00, 1),
(8, 'Posta (res)', 22500.00, 16000.00, 3.00, '', '2025-10-05', 'L20251012-C444FC', 50, 1, 1, '/static/images/products/prod_68ebf39b35b5b5.35756584.png', true, 'Unidad', 'Posta (res)', 50, 'Unidad', 22500.00, 1),
(9, 'Bollito (res)', 21500.00, 15500.00, 2.00, '', '2025-10-05', 'L20251012-A9D261', 50, 1, 1, '/static/images/products/prod_68ebf4c192d6c9.50344778.webp', true, 'Unidad', 'Bollito (res)', 50, 'Unidad', 21500.00, 1),
(10, 'Masa frente', 15000.00, 10000.00, 2.00, '', '2025-10-05', 'L20251012-55D311', 50, 1, 2, '/static/images/products/prod_68ebf4df8f9e44.65315709.jpeg', true, 'Unidad', 'Masa frente', 50, 'Unidad', 15000.00, 2),
(11, 'Masa chocozuela', 14500.00, 9500.00, 1.00, '', '2025-10-05', 'L20251012-AE985E', 50, 1, 2, '/static/images/products/prod_68ebf57edbb168.75066028.jpeg', true, 'Unidad', 'Masa chocozuela', 50, 'Unidad', 14500.00, 2),
(12, 'Morro', 13000.00, 9000.00, 1.00, '', '2025-10-05', 'L20251012-0D52BC', 50, 1, 2, '/static/images/products/prod_68ebf596e82cd2.65883959.jpg', true, 'Unidad', 'Morro', 50, 'Unidad', 13000.00, 2),
(13, 'Cohete', 14000.00, 9500.00, 3.00, '', '2025-10-05', 'L20251012-F875AB', 50, 1, 2, '/static/images/products/prod_68ebf5ab48f442.24850910.jpeg', true, 'Unidad', 'Cohete', 50, 'Unidad', 14000.00, 2),
(14, 'Corriente', 13500.00, 9000.00, 3.00, '', '2025-10-05', 'L20251012-9E8B8B', 50, 1, 2, '/static/images/products/prod_68ebf5bd857667.96149222.webp', true, 'Unidad', 'Corriente', 50, 'Unidad', 13500.00, 2),
(15, 'Costilla corriente', 18000.00, 12000.00, 3.00, '', '2025-10-05', 'L20251012-6A684B', 50, 1, 3, '/static/images/products/prod_68ebf5cdc66315.94191505.webp', true, 'Unidad', 'Costilla corriente', 50, 'Unidad', 18000.00, 3),
(16, 'Costilla especial', 20000.00, 13500.00, 3.00, '', '2025-10-05', 'L20251012-E2DD4B', 50, 1, 3, '/static/images/products/prod_68ebf5e4dafd05.90954993.jpeg', true, 'Unidad', 'Costilla especial', 50, 'Unidad', 20000.00, 3),
(17, 'Costilla super', 22000.00, 15000.00, 5.00, '', '2025-10-05', 'L20251012-71E74D', 50, 1, 3, '/static/images/products/prod_68ebf5f5e94d85.96218916.webp', true, 'Unidad', 'Costilla super', 50, 'Unidad', 22000.00, 3),
(18, 'Molida corriente', 16000.00, 11000.00, 1.00, '', '2025-10-05', 'L20251012-36CCC8', 50, 1, 4, '/static/images/products/prod_68ebf567853f14.99177535.jpg', true, 'Unidad', 'Molida corriente', 50, 'Unidad', 16000.00, 4),
(19, 'Molida especial', 18000.00, 12500.00, 5.00, '', '2025-10-05', 'L20251012-813B04', 50, 1, 4, '/static/images/products/prod_68ebf609d26710.61829850.jpeg', true, 'Unidad', 'Molida especial', 50, 'Unidad', 18000.00, 4),
(20, 'Molida super', 20000.00, 14000.00, 20000.00, '', '2025-10-05', 'L20251012-A72061', 50, 1, 4, '/static/images/products/prod_68ebf61a7cc520.77777105.jpg', true, 'Unidad', 'Molida super', 50, 'Unidad', 20000.00, 4),
(21, 'Hígado', 12000.00, 8000.00, 3000000.00, '', '2025-10-05', 'L20251012-0B7DB8', 50, 1, 5, '/static/images/products/prod_68ebf6370ee2a9.09330560.jpeg', true, 'Unidad', 'Hígado', 50, 'Unidad', 12000.00, 5),
(22, 'Bofe', 11000.00, 7500.00, 3.00, '', '2025-10-05', 'L20251012-ADA48D', 50, 1, 5, '/static/images/products/prod_68ebf64f2430f4.02541214.jpg', true, 'Unidad', 'Bofe', 50, 'Unidad', 11000.00, 5),
(23, 'Corazón', 13000.00, 8500.00, 32.00, '', '2025-10-05', 'L20251012-07DEE5', 50, 1, 5, '/static/images/products/prod_68ebf6676c3a31.82693452.webp', true, 'Unidad', 'Corazón', 50, 'Unidad', 13000.00, 5),
(24, 'Lengua', 25000.00, 17000.00, 2000000.00, '', '2025-10-05', 'L20251012-9075AE', 50, 1, 5, '/static/images/products/prod_68ebf67dc34327.50751803.png', true, 'Unidad', 'Lengua', 50, 'Unidad', 25000.00, 5),
(25, 'Riñón', 14000.00, 9000.00, 1.00, '', '2025-10-05', 'L20251012-0F2FAB', 50, 1, 5, '/static/images/products/prod_68ebf68eecd784.84489479.jpg', true, 'Unidad', 'Riñón', 50, 'Unidad', 14000.00, 5),
(26, 'Rabo de res', 23000.00, 16000.00, 2.00, '', '2025-10-05', 'L20251012-31166F', 50, 1, 5, '/static/images/products/prod_68ebf69d90ca12.57469540.jpeg', true, 'Unidad', 'Rabo de res', 50, 'Unidad', 23000.00, 5),
(27, 'Carne desmechar', 16500.00, 11000.00, 1.00, '', '2025-10-05', 'L20251012-CE8B6B', 50, 1, 12, '/static/images/products/prod_68ebf6ad64e1c5.06929708.jpg', true, 'Unidad', 'Carne desmechar', 50, 'Unidad', 16500.00, 12),
(28, 'Bistec', 24000.00, 17000.00, 1.00, '', '2025-10-05', 'L20251012-3D74AE', 50, 1, 1, '/static/images/products/prod_68ebf6bf6866b0.80022685.png', true, 'Unidad', 'Bistec', 50, 'Unidad', 24000.00, 1),
(29, 'Huesos', 8000.00, 5000.00, 0.97, '', '2025-10-05', 'L20251012-98F9FC', 50, 1, 6, '/static/images/products/prod_68ebf6d1c7ef39.46405632.webp', true, 'Unidad', 'Huesos', 50, 'Unidad', 8000.00, 6),
(30, 'Lomo de cerdo', 20000.00, 14000.00, 1.00, '', '2025-10-05', 'L20251012-A0AE13', 50, 1, 7, '/static/images/products/prod_68ebf6e13735c0.77130189.jpg', true, 'Unidad', 'Lomo de cerdo', 50, 'Unidad', 20000.00, 7),
(31, 'Solomito de cerdo', 22000.00, 15000.00, 1000000.00, '', '2025-10-05', 'L20251012-4693C8', 50, 1, 7, '/static/images/products/prod_68ebf7022365c7.34699182.jpg', true, 'Unidad', 'Solomito de cerdo', 50, 'Unidad', 22000.00, 7),
(32, 'Chuleta especial', 21000.00, 14500.00, 1.00, '', '2025-10-05', 'L20251012-2481D0', 50, 1, 7, '/static/images/products/prod_68ebf711d5cf91.15905289.jpg', true, 'Unidad', 'Chuleta especial', 50, 'Unidad', 21000.00, 7),
(33, 'Masa de cerdo', 15000.00, 10000.00, 0.96, '', '2025-10-05', 'L20251012-18C25A', 50, 1, 12, '/static/images/products/prod_68ebf7241b6910.98394274.jpeg', true, 'Unidad', 'Masa de cerdo', 50, 'Unidad', 15000.00, 12),
(34, 'Chuleta corriente', 16000.00, 11000.00, NULL, NULL, '2025-10-05', NULL, 50, 1, 12, '/static/images/products/prod_chuleta-corriente_68ebe9c0e63c9.jpg', true, 'Unidad', 'Chuleta corriente', 50, 'Unidad', 16000.00, 12),
(35, 'Contra codillo', 17000.00, 11500.00, 1.00, '', '2025-10-05', 'L20251012-09AAC9', 50, 1, 12, '/static/images/products/prod_68ebf73d9d8269.18658443.jpg', true, 'Unidad', 'Contra codillo', 50, 'Unidad', 17000.00, 12),
(36, 'Patica', 14000.00, 9500.00, 1.97, '', '2025-10-05', 'L20251012-15C265', 50, 1, 12, '/static/images/products/prod_68ebf750d23302.21533921.jpeg', true, 'Unidad', 'Patica', 50, 'Unidad', 14000.00, 12),
(37, 'Tocino carnudo', 18000.00, 12000.00, 1.00, '', '2025-10-05', 'L20251012-55F1D8', 50, 1, 8, '/static/images/products/prod_68ebf763af4369.37205273.jpg', true, 'Unidad', 'Tocino carnudo', 50, 'Unidad', 18000.00, 8),
(38, 'Tocino corriente', 16000.00, 10500.00, 1.00, '', '2025-10-05', 'L20251012-985DEB', 50, 1, 8, '/static/images/products/prod_68ebf7749be327.68650889.jpeg', true, 'Unidad', 'Tocino corriente', 50, 'Unidad', 16000.00, 8),
(39, 'Papada', 15000.00, 10000.00, 1.14, '', '2025-10-05', 'L20251012-40D022', 30, 1, 8, '/static/images/products/prod_68ebf788530097.91516183.webp', true, 'Unidad', 'Papada', 30, 'Unidad', 15000.00, 8),
(40, 'Costilla de cerdo', 19000.00, 13000.00, NULL, NULL, '2025-10-05', NULL, 50, 1, 9, '/static/images/products/prod_costilla-de-cerdo_68ebe9c0ebcd8.jpeg', true, 'Unidad', 'Costilla de cerdo', 50, 'Unidad', 19000.00, 9),
(41, 'Biceras', 17000.00, 11000.00, 2.00, '', '2025-10-05', 'L20251012-0F77F9', 50, 1, 12, '/static/images/products/prod_68ebf79a9c4a95.02257713.jpeg', true, 'Unidad', 'Biceras', 50, 'Unidad', 17000.00, 12),
(42, 'Pollo entero', 18000.00, 12000.00, 1.00, '', '2025-10-05', 'L20251012-AE9398', 50, 1, 10, '/static/images/products/prod_68ebf7af637650.11955032.jpg', true, 'Unidad', 'Pollo entero', 50, 'Unidad', 18000.00, 10),
(43, 'Muslo y contramuslo', 16000.00, 10500.00, 1.00, '', '2025-10-05', 'L20251012-CEAF3A', 50, 1, 11, '/static/images/products/prod_68ebf7c3b4e027.67202182.jpeg', true, 'Unidad', 'Muslo y contramuslo', 50, 'Unidad', 16000.00, 11),
(44, 'Alas con costillar', 17000.00, 11000.00, 1.00, '', '2025-10-05', 'L20251012-B62F7D', 50, 1, 11, '/static/images/products/prod_68ebf7d3524a86.07522593.webp', true, 'Unidad', 'Alas con costillar', 50, 'Unidad', 17000.00, 11),
(45, 'Alas', 15000.00, 9500.00, 1.94, '', '2025-10-05', 'L20251012-9D507B', 50, 1, 11, '/static/images/products/prod_68ebf7ee45ce63.22704605.webp', true, 'Unidad', 'Alas', 50, 'Unidad', 15000.00, 11),
(46, 'Paticas de pollo', 14000.00, 9000.00, 1.00, '', '2025-10-05', 'L20251012-C7CF14', 50, 1, 11, '/static/images/products/prod_68ebf800090820.22430874.jpg', true, 'Unidad', 'Paticas de pollo', 50, 'Unidad', 14000.00, 11) ON CONFLICT DO NOTHING;

-- Datos: lotes
INSERT INTO lotes (id_lote, id_producto, numero_lote, fecha_caducidad, precio_compra_lote, descripcion, created_at, updated_at) VALUES
(1, 1, 'L20251012-CC3543', '2025-10-07', 300000.00, 'dededed', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(2, 2, 'L20251012-56CAF3', '2025-10-05', 500000.00, 'Segundo producto que se atualiza', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(3, 3, 'L20251012-05F912', '2025-10-05', 50000.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(4, 4, 'L20251012-B75F68', '2025-10-05', 400000.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(5, 5, 'L20251012-B8A126', '2025-10-05', 300000.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(6, 6, 'L20251012-CE77CF', '2025-10-05', 2.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(7, 7, 'L20251012-1765D5', '2025-10-05', 2.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(8, 8, 'L20251012-C444FC', '2025-10-05', 3.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(9, 9, 'L20251012-A9D261', '2025-10-05', 2.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(10, 10, 'L20251012-55D311', '2025-10-05', 2.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(11, 11, 'L20251012-AE985E', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(12, 12, 'L20251012-0D52BC', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(13, 13, 'L20251012-F875AB', '2025-10-05', 3.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(14, 14, 'L20251012-9E8B8B', '2025-10-05', 3.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(15, 15, 'L20251012-6A684B', '2025-10-05', 3.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(16, 16, 'L20251012-E2DD4B', '2025-10-05', 3.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(17, 17, 'L20251012-71E74D', '2025-10-05', 5.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(18, 18, 'L20251012-36CCC8', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(19, 19, 'L20251012-813B04', '2025-10-05', 5.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(20, 20, 'L20251012-A72061', '2025-10-05', 20000.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(21, 21, 'L20251012-0B7DB8', '2025-10-05', 3000000.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(22, 22, 'L20251012-ADA48D', '2025-10-05', 3.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(23, 23, 'L20251012-07DEE5', '2025-10-05', 32.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(24, 24, 'L20251012-9075AE', '2025-10-05', 2000000.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(25, 25, 'L20251012-0F2FAB', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(26, 26, 'L20251012-31166F', '2025-10-05', 2.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(27, 27, 'L20251012-CE8B6B', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(28, 28, 'L20251012-3D74AE', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(29, 29, 'L20251012-98F9FC', '2025-10-05', 0.97, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(30, 30, 'L20251012-A0AE13', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(31, 31, 'L20251012-4693C8', '2025-10-05', 1000000.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(32, 32, 'L20251012-2481D0', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(33, 33, 'L20251012-18C25A', '2025-10-05', 0.96, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(35, 35, 'L20251012-09AAC9', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(36, 36, 'L20251012-15C265', '2025-10-05', 1.97, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(37, 37, 'L20251012-55F1D8', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(38, 38, 'L20251012-985DEB', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(39, 39, 'L20251012-40D022', '2025-10-05', 1.14, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(41, 41, 'L20251012-0F77F9', '2025-10-05', 2.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(42, 42, 'L20251012-AE9398', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(43, 43, 'L20251012-CEAF3A', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(44, 44, 'L20251012-B62F7D', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(45, 45, 'L20251012-9D507B', '2025-10-05', 1.94, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936'),
(46, 46, 'L20251012-C7CF14', '2025-10-05', 1.00, '', '2026-02-10 11:01:58.606936', '2026-02-10 11:01:58.606936') ON CONFLICT DO NOTHING;

-- Datos: ofertas
INSERT INTO ofertas (id, nombre, descripcion, tipo, valor_descuento, fecha_inicio, fecha_fin, limite_usos, estado, productos_json, created_at, imagen) VALUES
(5, 'Lomo fino', 'dededed', 'fixed', 25000.00, '2025-10-08 00:00:00', NULL, NULL, 'active', NULL, '2025-10-13 21:15:57', '/static/images/offers/offer_68ed6fe3b36b87.33091067.jpeg'),
(6, 'Lomo ancho', 'Lomo ancho', 'bogo', 50.00, '2025-10-08 00:00:00', NULL, NULL, 'active', NULL, '2025-10-13 22:24:17', '/static/images/offers/offer_68ed7c11b9ded9.28994685.jpeg'),
(7, 'Punta gorda', 'Punta gorda', 'fixed', 20000.00, '2025-10-08 00:00:00', NULL, NULL, 'active', NULL, '2025-10-13 22:27:31', '/static/images/offers/offer_68ed7cd33af750.44248820.jpeg'),
(8, 'dededed', 'ededede', 'fixed', 4000000.00, '2026-02-18 00:00:00', '2026-02-19 00:00:00', NULL, 'active', '["4"]', '2026-02-18 19:23:20.75994', '/static/images/offers/offer_699657f8b00610.81538557.jpg') ON CONFLICT DO NOTHING;

-- Datos: orders
INSERT INTO orders (id, paypal_id, user_email, user_name, status, total, delivery_method, address_json, schedule_json, created_at) VALUES
(1, '92T721159F630974S', 'comprador@gmail.com', 'comprador', 'COMPLETED', 14000.00, '0', '{"street":"Avenida Calle cartagena SN 23","city":"Cartagena De Indias","dept":"Bolívar","zip":"","notes":""}', '{"envio1":"sabado","envio2":"lunes"}', '2025-10-13 11:27:21'),
(2, '6DS76703S2114415R', 'comprador@gmail.com', 'comprador', 'COMPLETED', 45000.00, '0', '{"street":"Avenida Calle cartagena SN 23","city":"Cartagena De Indias","dept":"Bolívar","zip":"","notes":""}', '{"envio1":"sabado","envio2":"lunes"}', '2025-10-13 14:41:57'),
(3, '00V78098FH534732J', 'juanpiayola@gmail.com', 'juan pablo', 'PROCESSING', 46000.00, '0', '{"street":"Avenida Calle cartagena SN 23","city":"Cartagena De Indias","dept":"Bolívar","zip":"","notes":""}', '{"envio1":"sabado","envio2":"lunes"}', '2025-10-13 14:52:00'),
(4, '97598458EK347352A', 'juanpiayola@gmail.com', 'juan pablo', 'PENDING', 14000.00, '0', '{"street":"Avenida Calle cartagena SN 23","city":"Cartagena De Indias","dept":"Bolívar","zip":"","notes":""}', '{"envio1":"sabado","envio2":"lunes"}', '2025-10-13 16:26:25'),
(5, '06U33077V6744505L', 'comprador@gmail.com', 'comprador', '0', 14000.00, '0', '{"street":"Avenida Calle cartagena SN 23","city":"Cartagena De Indias","dept":"Bolívar","zip":"","notes":""}', '{"envio1":"sabado","envio2":"lunes"}', '2025-11-07 15:49:14') ON CONFLICT DO NOTHING;

-- Datos: order_items
INSERT INTO order_items (id, order_id, title, price, qty, image) VALUES
(1, 1, 'Paticas de pollo', 14000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf800090820.22430874.jpg'),
(2, 2, 'Paticas de pollo', 14000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf800090820.22430874.jpg'),
(3, 2, 'Alas', 15000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf7ee45ce63.22704605.webp'),
(4, 2, 'Muslo y contramuslo', 16000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf7c3b4e027.67202182.jpeg'),
(5, 3, 'Paticas de pollo', 14000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf800090820.22430874.jpg'),
(6, 3, 'Alas', 15000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf7ee45ce63.22704605.webp'),
(7, 3, 'Alas con costillar', 17000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf7d3524a86.07522593.webp'),
(8, 4, 'Paticas de pollo', 14000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf800090820.22430874.jpg'),
(9, 5, 'Paticas de pollo', 14000.00, 1, 'http://localhost/DISTRICARNES/static/images/products/prod_68ebf800090820.22430874.jpg') ON CONFLICT DO NOTHING;

-- Datos: password_resets (DESPUES de usuario) - TODAS las filas con 6 columnas
INSERT INTO password_resets (id, user_id, token_hash, expires_at, used, created_at) VALUES
(1, 8, '602239ae462bfb8b7dd9d797f877b506ec360809fa2b9ba4b4774d2bbd9b7acf', '2025-10-04 15:03:38', false, '2025-10-04 12:33:38'),
(2, 8, '2d1ab7f3d4d26b989014a13c27d2bd7e199b641b807f11643a8672e32d789951', '2025-10-04 15:24:22', false, '2025-10-04 12:54:22'),
(3, 8, '131e7271bed5ef66d4de1563cb8d2907fa3df670c57cc8e7280e5618ba162ec2', '2025-10-04 15:32:01', false, '2025-10-04 13:02:01'),
(4, 8, '17417dbcbc86c4f2b9f837c3ec234e355ebee7b68ad99afd5cd739e551b20845', '2025-10-04 15:32:02', false, '2025-10-04 13:02:02'),
(5, 8, 'f72f41984c78b6b08f1f84af2b0c2c50273b116ff22442ed098244d83efc91a2', '2025-10-04 15:33:45', false, '2025-10-04 13:03:45'),
(6, 8, '7b1b4c01ec1de30d6c39ec24aaf3fc9f13262f6a361fea1efa9d47c1d3c5feeb', '2025-10-04 15:34:08', false, '2025-10-04 13:04:08'),
(7, 8, '72af508b50cab58582374acfd493680983210093d016ce340dd9c189accb035d', '2025-10-04 15:42:30', false, '2025-10-04 13:12:30'),
(8, 8, '7dba16bea35564ab5f6f3c627f556d3a9dfa59b89f59a8bd959667b45395f5a8', '2025-10-04 15:46:09', false, '2025-10-04 13:16:09'),
(9, 8, 'f5ca2976fbccb5554ac428f39c31cd5471da20fd90a52d04a1c3ea0c2190e10e', '2025-10-04 15:50:16', false, '2025-10-04 13:20:16'),
(10, 8, '516a6e84dda58199920d81204675740ad10bf17f209ed60093e646134c664194', '2025-10-04 15:53:55', false, '2025-10-04 13:23:55'),
(11, 8, 'aefe3a4c5f55171a86e44c2eee903e447e1056086ddf0c7359822eb4d93dd00f', '2025-10-04 15:59:35', false, '2025-10-04 13:29:35'),
(12, 8, '98c5f96a8e4be0cb6664826e2c8be2f741d61357a7045485397738b72303b040', '2025-10-04 16:07:37', false, '2025-10-04 13:37:37'),
(13, 8, '243bb91adfea113d3769f6461c16e9c5b5ecc805526a97f336feb775c54115a1', '2025-10-04 16:10:21', false, '2025-10-04 13:40:21'),
(14, 8, 'bfa367c7923586c79a663d528037641ba04067506f2a5833ce630496942d6e6a', '2025-10-04 16:11:24', false, '2025-10-04 13:41:24'),
(15, 8, '3f4accf86cbc925314824ee047d187b6b26b224b0a6e717f58622212bf66277e', '2025-10-04 16:13:43', false, '2025-10-04 13:43:43'),
(16, 8, 'a2f9bffd89c66ebfdf332de41c71cdb0bbb41493d1a2a807192179d4560569da', '2025-10-04 16:22:26', false, '2025-10-04 13:52:26'),
(17, 9, '0ebfe015114867d6366aa1b5f97f14ca0b431a2fe8a46f1bd5d6325e23ede89e', '2025-10-04 16:40:19', true, '2025-10-04 14:10:19'),
(18, 8, '5601d428b0543442d918659911587d9d7f2f033ab8fd523686de459ba488fa9e', '2025-10-05 01:36:41', false, '2025-10-04 23:06:41'),
(19, 8, '2dad845f01d9a67b10b78945b852157863f4f668287e87c970860e1278e1f2d7', '2025-10-08 14:48:39', false, '2025-10-08 12:18:39'),
(20, 8, '86388a81b431223abacc25884385b5eee62e5f7ccbaa1d58888341d3e2d7e41d', '2025-10-08 15:30:41', false, '2025-10-08 13:00:41'),
(21, 9, '6b8b01319b8737ce4dd97816ad9306317c3c44544970d7e506e82100f8e53d73', '2025-10-13 17:14:24', true, '2025-10-13 14:44:24'),
(22, 10, 'a7fd42fa4dd9f770dfa173f7061c111345582bc361bcdd218e2fce9d9b1f100e', '2025-11-07 15:01:57', false, '2025-11-07 13:31:57'),
(23, 10, '8ef513b05e49e640a277b598f6a858cab76b230ef5acef18e19a672ca118daa2', '2025-11-07 15:09:12', false, '2025-11-07 13:39:12'),
(24, 10, 'eee46b9d95c3c7d22d87cc41b6116a500b4f038d55cf14f3c28faca03d612c2b', '2025-11-07 15:16:05', false, '2025-11-07 13:46:05'),
(25, 10, '9a05f6a4915c158605ec414d3f59266483b40ccbff026e12546015a864cbd902', '2025-11-07 15:24:53', false, '2025-11-07 13:54:53'),
(26, 10, 'd9050a4c8f2ba4c84dd879c8f9354b73508796e38e854f8d29836bd23ec635a3', '2025-11-07 15:26:00', false, '2025-11-07 13:56:00'),
(27, 10, 'c6fad19de1352b9fe1cc8362152bc33636144ecdb6112b435d9d4ff277a21e83', '2025-11-07 15:27:50', false, '2025-11-07 13:57:50'),
(28, 10, '9cdc400c04112b837da00546ca5bf51ba14d41bfdbf3dc47e8a63165231163d1', '2025-11-07 15:27:56', false, '2025-11-07 13:57:56') ON CONFLICT DO NOTHING;

-- ============================================
-- 5. LLAVES FORÁNEAS
-- ============================================

ALTER TABLE contrato_proveedor DROP CONSTRAINT IF EXISTS fk_contrato_proveedor;
ALTER TABLE contrato_proveedor ADD CONSTRAINT fk_contrato_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor);

ALTER TABLE detalle_venta DROP CONSTRAINT IF EXISTS fk_detalle_venta_producto;
ALTER TABLE detalle_venta ADD CONSTRAINT fk_detalle_venta_producto FOREIGN KEY (id_producto) REFERENCES producto(id_producto);

ALTER TABLE detalle_venta DROP CONSTRAINT IF EXISTS fk_detalle_venta_venta;
ALTER TABLE detalle_venta ADD CONSTRAINT fk_detalle_venta_venta FOREIGN KEY (id_venta) REFERENCES venta(id_venta);

ALTER TABLE entrega DROP CONSTRAINT IF EXISTS fk_entrega_venta;
ALTER TABLE entrega ADD CONSTRAINT fk_entrega_venta FOREIGN KEY (id_venta) REFERENCES venta(id_venta);

ALTER TABLE lotes DROP CONSTRAINT IF EXISTS fk_lotes_producto;
ALTER TABLE lotes ADD CONSTRAINT fk_lotes_producto FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE notificacion DROP CONSTRAINT IF EXISTS fk_notificacion_usuario;
ALTER TABLE notificacion ADD CONSTRAINT fk_notificacion_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario);

ALTER TABLE order_items DROP CONSTRAINT IF EXISTS fk_order_items_order;
ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE password_resets DROP CONSTRAINT IF EXISTS fk_password_resets_usuario;
ALTER TABLE password_resets ADD CONSTRAINT fk_password_resets_usuario FOREIGN KEY (user_id) REFERENCES usuario(id_usuario);

ALTER TABLE preferencia_cliente DROP CONSTRAINT IF EXISTS fk_preferencia_cliente_cliente;
ALTER TABLE preferencia_cliente ADD CONSTRAINT fk_preferencia_cliente_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente);

ALTER TABLE producto DROP CONSTRAINT IF EXISTS fk_producto_categoria;
ALTER TABLE producto ADD CONSTRAINT fk_producto_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria);

ALTER TABLE producto DROP CONSTRAINT IF EXISTS fk_producto_proveedor;
ALTER TABLE producto ADD CONSTRAINT fk_producto_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor);

ALTER TABLE venta DROP CONSTRAINT IF EXISTS fk_venta_cliente;
ALTER TABLE venta ADD CONSTRAINT fk_venta_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente);

ALTER TABLE venta DROP CONSTRAINT IF EXISTS fk_venta_usuario;
ALTER TABLE venta ADD CONSTRAINT fk_venta_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario);

-- ============================================
-- 6. CONFIGURACIÓN DE SECUENCIAS
-- ============================================

SELECT setval('caja_caja_id_seq', 1, false);
SELECT setval('categoria_categoria_id_seq', 12, true);
SELECT setval('categorias_id_categoria_seq', 12, true);
SELECT setval('cliente_id_cliente_seq', 1, false);
SELECT setval('contrato_proveedor_id_contrato_seq', 1, false);
SELECT setval('detalle_venta_id_detalle_seq', 1, false);
SELECT setval('empresa_empresa_id_seq', 1, false);
SELECT setval('entrega_id_entrega_seq', 1, false);
SELECT setval('lotes_id_lote_seq', 46, true);
SELECT setval('notificacion_id_notificacion_seq', 1, false);
SELECT setval('ofertas_id_seq', 8, true);
SELECT setval('order_items_id_seq', 9, true);
SELECT setval('orders_id_seq', 5, true);
SELECT setval('password_resets_id_seq', 28, true);
SELECT setval('preferencia_cliente_id_preferencia_seq', 1, false);
SELECT setval('producto_id_producto_seq', 51, true);
SELECT setval('proveedor_id_proveedor_seq', 1, true);
SELECT setval('usuario_id_usuario_seq', 19, true);
SELECT setval('venta_detalle_venta_detalle_id_seq', 1, false);
SELECT setval('venta_id_venta_seq', 1, false);

-- ============================================
-- ¡SCRIPT COMPLETADO EXITOSAMENTE!
-- ============================================