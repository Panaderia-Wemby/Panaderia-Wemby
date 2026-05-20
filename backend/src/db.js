import mysql from 'mysql2/promise';
import { passwordHash } from './seedData.js';

let pool;
let initPromise;

export function isDatabaseEnabled() {
  return Boolean(process.env.MYSQL_HOST);
}

export function createPool() {
  if (!pool) {
    pool = mysql.createPool({
      host: process.env.MYSQL_HOST,
      port: Number(process.env.MYSQL_PORT || 3306),
      user: process.env.MYSQL_USER,
      password: process.env.MYSQL_PASSWORD,
      database: process.env.MYSQL_DATABASE,
      waitForConnections: true,
      connectionLimit: 10,
      decimalNumbers: true
    });
  }

  return pool;
}

async function executeStatements(connection, statements) {
  for (const statement of statements) {
    // eslint-disable-next-line no-await-in-loop
    await connection.query(statement);
  }
}

async function ensureSchema(connection) {
  await executeStatements(connection, [
    `CREATE TABLE IF NOT EXISTS categorias (
      id_categoria INT PRIMARY KEY,
      nombre_categoria VARCHAR(255) NOT NULL UNIQUE,
      created_at TIMESTAMP NULL,
      updated_at TIMESTAMP NULL
    )`,
    `CREATE TABLE IF NOT EXISTS users (
      id BIGINT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      email VARCHAR(255) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      rol INT NULL,
      created_at TIMESTAMP NULL,
      updated_at TIMESTAMP NULL
    )`,
    `CREATE TABLE IF NOT EXISTS proveedores (
      id_proveedor INT PRIMARY KEY,
      nombre VARCHAR(255) NOT NULL,
      contacto VARCHAR(255) NOT NULL,
      created_at TIMESTAMP NULL,
      updated_at TIMESTAMP NULL
    )`,
    `CREATE TABLE IF NOT EXISTS insumos (
      id_insumo INT PRIMARY KEY,
      nombre_insumo VARCHAR(255) NOT NULL,
      id_categoria INT NULL,
      stock INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP NULL,
      updated_at TIMESTAMP NULL,
      CONSTRAINT fk_insumos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        ON DELETE SET NULL ON UPDATE CASCADE
    )`,
    `CREATE TABLE IF NOT EXISTS productos (
      id_producto INT PRIMARY KEY,
      codigo_producto VARCHAR(255) NOT NULL UNIQUE,
      nombre VARCHAR(255) NOT NULL,
      id_categoria INT NULL,
      precio DECIMAL(10,2) NOT NULL,
      stock INT NOT NULL DEFAULT 0,
      created_at TIMESTAMP NULL,
      updated_at TIMESTAMP NULL,
      CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
        ON DELETE SET NULL ON UPDATE CASCADE
    )`,
    `CREATE TABLE IF NOT EXISTS productos_productos_insumos (
      id INT PRIMARY KEY AUTO_INCREMENT,
      producto_id INT NOT NULL,
      insumo_id INT NOT NULL,
      cantidad_usada INT NOT NULL DEFAULT 0,
      UNIQUE KEY unique_producto_insumo (producto_id, insumo_id),
      CONSTRAINT fk_ppi_producto FOREIGN KEY (producto_id) REFERENCES productos(id_producto)
        ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT fk_ppi_insumo FOREIGN KEY (insumo_id) REFERENCES insumos(id_insumo)
        ON DELETE CASCADE ON UPDATE CASCADE
    )`,
    `CREATE TABLE IF NOT EXISTS proveedores_productos (
      id INT PRIMARY KEY AUTO_INCREMENT,
      producto_id INT NOT NULL,
      proveedor_id INT NOT NULL,
      UNIQUE KEY unique_producto_proveedor (producto_id, proveedor_id),
      CONSTRAINT fk_pp_producto FOREIGN KEY (producto_id) REFERENCES productos(id_producto)
        ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT fk_pp_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id_proveedor)
        ON DELETE CASCADE ON UPDATE CASCADE
    )`,
    `CREATE TABLE IF NOT EXISTS ventas (
      id_venta INT PRIMARY KEY,
      num_factura VARCHAR(255) NOT NULL UNIQUE,
      fecha_venta DATETIME NOT NULL,
      documento_cliente VARCHAR(255) NULL,
      id_cajero BIGINT NULL,
      total_venta DECIMAL(10,2) NOT NULL,
      created_at TIMESTAMP NULL,
      updated_at TIMESTAMP NULL,
      CONSTRAINT fk_ventas_cajero FOREIGN KEY (id_cajero) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
    )`,
    `CREATE TABLE IF NOT EXISTS detalle_venta (
      id_detalle INT PRIMARY KEY,
      num_factura VARCHAR(255) NULL,
      id_producto INT NULL,
      cantidad INT NULL,
      precio_unitario DECIMAL(10,2) NULL,
      created_at TIMESTAMP NULL,
      updated_at TIMESTAMP NULL,
      CONSTRAINT fk_detalle_venta_factura FOREIGN KEY (num_factura) REFERENCES ventas(num_factura)
        ON DELETE CASCADE ON UPDATE CASCADE,
      CONSTRAINT fk_detalle_venta_producto FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
        ON DELETE SET NULL ON UPDATE CASCADE
    )`
  ]);
}

async function seedIfEmpty(connection) {
  const [[{ count: categoryCount }]] = await connection.query('SELECT COUNT(*) AS count FROM categorias');
  if (Number(categoryCount) > 0) {
    return;
  }

  await connection.beginTransaction();
  try {
    await executeStatements(connection, [
      "INSERT INTO categorias (id_categoria, nombre_categoria, created_at, updated_at) VALUES (1,'Panaderia',NOW(),NOW()),(2,'Reposteria',NOW(),NOW()),(3,'Bebidas',NOW(),NOW())",
      `INSERT INTO users (id, name, email, password, rol, created_at, updated_at) VALUES
       (4,'Maria Gomez','cajero@wemby.test','${passwordHash}',1,NOW(),NOW()),
       (5,'Carlos Perez','baker@wemby.test','${passwordHash}',2,NOW(),NOW()),
       (6,'Jefe Wemby','boss@wemby.test','${passwordHash}',3,NOW(),NOW())`,
      "INSERT INTO proveedores (id_proveedor, nombre, contacto, created_at, updated_at) VALUES (1,'Harinas del Valle','312 555 1001',NOW(),NOW()),(2,'Lacteos Express','312 555 1002',NOW(),NOW()),(3,'Embalajes Norte','312 555 1003',NOW(),NOW())",
      "INSERT INTO insumos (id_insumo, nombre_insumo, id_categoria, stock, created_at, updated_at) VALUES (1,'Harina',1,120,NOW(),NOW()),(2,'Azucar',2,80,NOW(),NOW()),(3,'Levadura',1,45,NOW(),NOW())",
      "INSERT INTO productos (id_producto, codigo_producto, nombre, id_categoria, precio, stock, created_at, updated_at) VALUES (1,'PAN-001','Pan campesino',1,2500,36,NOW(),NOW()),(2,'REP-002','Torta de chocolate',2,18500,12,NOW(),NOW()),(3,'BEB-003','Cafe latte',3,6200,24,NOW(),NOW())",
      "INSERT INTO productos_productos_insumos (id, producto_id, insumo_id, cantidad_usada) VALUES (1,1,1,2),(2,1,3,1),(3,2,2,3)",
      "INSERT INTO proveedores_productos (id, producto_id, proveedor_id) VALUES (1,1,1),(2,2,2),(3,2,3),(4,3,2)",
      "INSERT INTO ventas (id_venta, num_factura, fecha_venta, documento_cliente, id_cajero, total_venta, created_at, updated_at) VALUES (1,'FAC-1700000001','2026-05-18 08:15:00','100200300',4,11200,NOW(),NOW()),(2,'FAC-1700000002','2026-05-18 11:40:00','900800700',5,18500,NOW(),NOW())",
      "INSERT INTO detalle_venta (id_detalle, num_factura, id_producto, cantidad, precio_unitario, created_at, updated_at) VALUES (1,'FAC-1700000001',1,2,2500,NOW(),NOW()),(2,'FAC-1700000001',3,1,6200,NOW(),NOW()),(3,'FAC-1700000002',2,1,18500,NOW(),NOW())"
    ]);

    await connection.commit();
  } catch (error) {
    await connection.rollback();
    throw error;
  }
}

export async function initializeDatabase() {
  if (!isDatabaseEnabled()) {
    return null;
  }

  if (!initPromise) {
    initPromise = (async () => {
      const connection = await createPool().getConnection();
      try {
        await ensureSchema(connection);
        await seedIfEmpty(connection);
      } finally {
        connection.release();
      }
    })();
  }

  return initPromise;
}
