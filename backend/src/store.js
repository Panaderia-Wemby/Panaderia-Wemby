import { createPool, initializeDatabase, isDatabaseEnabled } from './db.js';
import { seedData } from './seedData.js';

const memoryState = structuredClone(seedData);

function memoryCategoryName(idCategoria) {
  return memoryState.categories.find((category) => category.id_categoria === Number(idCategoria))?.nombre_categoria ?? 'Sin categoria';
}

function memorySupplierNames(ids = []) {
  return memoryState.suppliers.filter((supplier) => ids.includes(supplier.id_proveedor));
}

function memoryProductById(id) {
  return memoryState.products.find((product) => product.id_producto === Number(id)) || null;
}

function memorySaleById(id) {
  return memoryState.sales.find((sale) => sale.id_venta === Number(id)) || null;
}

function memoryListProducts() {
  return memoryState.products.map((product) => ({
    ...product,
    categoria: memoryCategoryName(product.id_categoria),
    proveedores: memorySupplierNames(product.proveedor_ids),
    insumos: product.insumos.map((item) => ({
      ...item,
      nombre_insumo: memoryState.insumos.find((insumo) => insumo.id_insumo === item.id_insumo)?.nombre_insumo ?? 'Insumo'
    }))
  }));
}

function memoryListSales() {
  return memoryState.sales.map((sale) => ({ ...sale }));
}

function memoryCashiers() {
  return memoryState.users.filter((user) => [1, 2, 3].includes(user.rol)).map(({ password, ...user }) => user);
}

function memoryBuildSummary() {
  const totalRevenue = memoryState.sales.reduce((sum, sale) => sum + sale.total_venta, 0);
  return {
    total_sales: memoryState.sales.length,
    total_revenue: totalRevenue,
    products_active: memoryState.products.length,
    low_stock_products: memoryState.products.filter((product) => product.stock <= 12).length,
    top_category: memoryState.categories[0]?.nombre_categoria ?? 'Sin categoria',
    top_product: memoryState.products[0]?.nombre ?? 'Sin producto'
  };
}

function memoryAnalysisLines() {
  return [
    'Categoria dominante: Panaderia lidera el volumen de inventario.',
    'Producto mas vendido: Pan campesino mantiene la mayor rotacion.',
    'Riesgo de quiebre: Levadura requiere reposicion antes del siguiente turno.'
  ];
}

function totalFromItems(items = []) {
  return items.reduce((sum, item) => sum + Number(item.cantidad) * Number(item.precio_unitario), 0);
}

function nextMemoryId(collection, key) {
  return collection.length ? Math.max(...collection.map((item) => item[key])) + 1 : 1;
}

function applyMemoryStock(productId, delta) {
  const product = memoryProductById(productId);
  if (!product) {
    throw new Error(`Producto ${productId} no existe`);
  }
  if (product.stock + delta < 0) {
    throw new Error(`Stock insuficiente para ${product.nombre}`);
  }
  product.stock += delta;
  return product;
}

async function queryDb(sql, params = []) {
  const pool = createPool();
  const [rows] = await pool.query(sql, params);
  return rows;
}

async function queryDbOne(sql, params = []) {
  const rows = await queryDb(sql, params);
  return rows[0] || null;
}

async function loadProductRelationsDb(products) {
  if (!products.length) {
    return products;
  }

  const ids = products.map((product) => product.id_producto);
  const supplierRows = await queryDb(
    `SELECT pp.producto_id, pr.id_proveedor, pr.nombre, pr.contacto
     FROM proveedores_productos pp
     INNER JOIN proveedores pr ON pr.id_proveedor = pp.proveedor_id
     WHERE pp.producto_id IN (?)
     ORDER BY pr.id_proveedor`,
    [ids]
  );
  const supplyRows = await queryDb(
    `SELECT pi.producto_id, pi.insumo_id, pi.cantidad_usada, i.nombre_insumo
     FROM productos_productos_insumos pi
     INNER JOIN insumos i ON i.id_insumo = pi.insumo_id
     WHERE pi.producto_id IN (?)
     ORDER BY i.id_insumo`,
    [ids]
  );

  const supplierMap = new Map();
  for (const row of supplierRows) {
    if (!supplierMap.has(row.producto_id)) {
      supplierMap.set(row.producto_id, []);
    }
    supplierMap.get(row.producto_id).push({ id_proveedor: row.id_proveedor, nombre: row.nombre, contacto: row.contacto });
  }

  const supplyMap = new Map();
  for (const row of supplyRows) {
    if (!supplyMap.has(row.producto_id)) {
      supplyMap.set(row.producto_id, []);
    }
    supplyMap.get(row.producto_id).push({
      id_insumo: row.insumo_id,
      cantidad_usada: row.cantidad_usada,
      nombre_insumo: row.nombre_insumo
    });
  }

  return products.map((product) => ({
    ...product,
    proveedores: supplierMap.get(product.id_producto) || [],
    insumos: supplyMap.get(product.id_producto) || []
  }));
}

async function listSalesDb() {
  const salesRows = await queryDb(
    `SELECT id_venta, num_factura, fecha_venta, documento_cliente, id_cajero, total_venta
     FROM ventas ORDER BY id_venta DESC`
  );
  const detailRows = await queryDb(
    `SELECT id_detalle, num_factura, id_producto, cantidad, precio_unitario
     FROM detalle_venta ORDER BY id_detalle ASC`
  );

  const detailMap = new Map();
  for (const row of detailRows) {
    if (!detailMap.has(row.num_factura)) {
      detailMap.set(row.num_factura, []);
    }
    detailMap.get(row.num_factura).push({
      id_producto: row.id_producto,
      cantidad: row.cantidad,
      precio_unitario: Number(row.precio_unitario)
    });
  }

  return salesRows.map((sale) => ({ ...sale, detalleVenta: detailMap.get(sale.num_factura) || [] }));
}

async function adjustDbStock(connection, productId, delta) {
  const [rows] = await connection.query('SELECT stock, nombre FROM productos WHERE id_producto = ? FOR UPDATE', [productId]);
  const product = rows[0];
  if (!product) {
    throw new Error(`Producto ${productId} no existe`);
  }
  const nextStock = Number(product.stock) + Number(delta);
  if (nextStock < 0) {
    throw new Error(`Stock insuficiente para ${product.nombre}`);
  }
  await connection.query('UPDATE productos SET stock = ?, updated_at = NOW() WHERE id_producto = ?', [nextStock, productId]);
}

export async function initializeStore() {
  if (isDatabaseEnabled()) {
    await initializeDatabase();
  }
}

export async function findUserByEmail(email) {
  if (!isDatabaseEnabled()) {
    return memoryState.users.find((user) => user.email === email) || null;
  }

  return queryDbOne('SELECT id, name, email, password, rol FROM users WHERE email = ? LIMIT 1', [email]);
}

export async function findUserById(id) {
  if (!isDatabaseEnabled()) {
    return memoryState.users.find((user) => user.id === Number(id)) || null;
  }

  return queryDbOne('SELECT id, name, email, password, rol FROM users WHERE id = ? LIMIT 1', [id]);
}

export async function listCashiers() {
  if (!isDatabaseEnabled()) {
    return memoryCashiers();
  }

  return queryDb('SELECT id, name, email, rol FROM users WHERE rol IN (1, 2, 3) ORDER BY id');
}

export async function listCategories() {
  if (!isDatabaseEnabled()) {
    return memoryState.categories.map((category) => ({ ...category }));
  }

  return queryDb('SELECT id_categoria, nombre_categoria FROM categorias ORDER BY id_categoria');
}

export async function listSuppliers() {
  if (!isDatabaseEnabled()) {
    return memoryState.suppliers.map((supplier) => ({ ...supplier }));
  }

  return queryDb('SELECT id_proveedor, nombre, contacto FROM proveedores ORDER BY id_proveedor');
}

export async function listSupplies() {
  if (!isDatabaseEnabled()) {
    return memoryState.insumos.map((insumo) => ({ ...insumo }));
  }

  return queryDb('SELECT id_insumo, nombre_insumo, id_categoria, stock FROM insumos ORDER BY id_insumo');
}

export async function createProduct({ codigo_producto, nombre, id_categoria, precio, stock }) {
  if (!isDatabaseEnabled()) {
    const nextId = nextMemoryId(memoryState.products, 'id_producto');
    const product = {
      id_producto: nextId,
      codigo_producto,
      nombre,
      id_categoria: Number(id_categoria),
      precio: Number(precio),
      stock: Number(stock),
      proveedor_ids: [],
      insumos: []
    };
    memoryState.products.push(product);
    return product;
  }

  const pool = createPool();
  const [rows] = await pool.query('SELECT COALESCE(MAX(id_producto), 0) + 1 AS nextId FROM productos');
  const nextId = rows[0].nextId;
  await pool.query(
    'INSERT INTO productos (id_producto, codigo_producto, nombre, id_categoria, precio, stock, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
    [nextId, codigo_producto, nombre, id_categoria, precio, stock]
  );
  return getProductById(nextId);
}

export async function listProducts() {
  if (!isDatabaseEnabled()) {
    return memoryListProducts();
  }

  const products = await queryDb(
    `SELECT p.id_producto, p.codigo_producto, p.nombre, p.id_categoria, p.precio, p.stock, c.nombre_categoria AS categoria
     FROM productos p
     LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
     ORDER BY p.id_producto`
  );
  return loadProductRelationsDb(products);
}

export async function getProductById(id) {
  if (!isDatabaseEnabled()) {
    return memoryProductById(id);
  }

  const product = await queryDbOne(
    `SELECT p.id_producto, p.codigo_producto, p.nombre, p.id_categoria, p.precio, p.stock, c.nombre_categoria AS categoria
     FROM productos p
     LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
     WHERE p.id_producto = ? LIMIT 1`,
    [id]
  );

  if (!product) {
    return null;
  }

  const supplierRows = await queryDb(
    `SELECT pr.id_proveedor, pr.nombre, pr.contacto
     FROM proveedores_productos pp
     INNER JOIN proveedores pr ON pr.id_proveedor = pp.proveedor_id
     WHERE pp.producto_id = ? ORDER BY pr.id_proveedor`,
    [id]
  );
  const supplyRows = await queryDb(
    `SELECT pi.insumo_id AS id_insumo, pi.cantidad_usada, i.nombre_insumo
     FROM productos_productos_insumos pi
     INNER JOIN insumos i ON i.id_insumo = pi.insumo_id
     WHERE pi.producto_id = ? ORDER BY i.id_insumo`,
    [id]
  );

  return { ...product, proveedores: supplierRows, insumos: supplyRows };
}

export async function updateProductStock(id, stock) {
  const nextStock = Number(stock);

  if (!isDatabaseEnabled()) {
    const product = memoryProductById(id);
    if (!product) {
      return null;
    }
    product.stock = nextStock;
    return product;
  }

  const pool = createPool();
  await pool.query('UPDATE productos SET stock = ?, updated_at = NOW() WHERE id_producto = ?', [nextStock, id]);
  return getProductById(id);
}

export async function listSales() {
  if (!isDatabaseEnabled()) {
    return memoryListSales();
  }

  return listSalesDb();
}

export async function getSaleById(id) {
  if (!isDatabaseEnabled()) {
    return memorySaleById(id);
  }

  const sale = await queryDbOne(
    'SELECT id_venta, num_factura, fecha_venta, documento_cliente, id_cajero, total_venta FROM ventas WHERE id_venta = ? LIMIT 1',
    [id]
  );
  if (!sale) {
    return null;
  }

  const detailRows = await queryDb(
    `SELECT id_detalle, num_factura, id_producto, cantidad, precio_unitario
     FROM detalle_venta WHERE num_factura = ? ORDER BY id_detalle ASC`,
    [sale.num_factura]
  );

  return {
    ...sale,
    detalleVenta: detailRows.map((row) => ({
      id_producto: row.id_producto,
      cantidad: row.cantidad,
      precio_unitario: Number(row.precio_unitario)
    }))
  };
}

export async function createSale({ documento_cliente, id_cajero, items = [], generate_invoice = false }) {
  if (!isDatabaseEnabled()) {
    const detail = items.map((item) => ({
      id_producto: Number(item.id_producto),
      cantidad: Number(item.cantidad),
      precio_unitario: Number(item.precio_unitario)
    }));
    const total = totalFromItems(detail);
    detail.forEach((line) => applyMemoryStock(line.id_producto, -line.cantidad));
    const sale = {
      id_venta: nextMemoryId(memoryState.sales, 'id_venta'),
      num_factura: `FAC-${Date.now()}`,
      fecha_venta: new Date().toISOString(),
      documento_cliente,
      id_cajero: Number(id_cajero),
      total_venta: total,
      detalleVenta: detail,
      generate_invoice: Boolean(generate_invoice)
    };
    memoryState.sales.unshift(sale);
    return sale;
  }

  const connection = await createPool().getConnection();
  try {
    await connection.beginTransaction();
    const total = totalFromItems(items);
    const [saleRow] = await connection.query('SELECT COALESCE(MAX(id_venta), 0) + 1 AS nextId FROM ventas');
    const nextSaleId = saleRow[0].nextId;
    const numFactura = `FAC-${Date.now()}`;

    for (const item of items) {
      // eslint-disable-next-line no-await-in-loop
      await adjustDbStock(connection, item.id_producto, -Number(item.cantidad));
    }

    await connection.query(
      'INSERT INTO ventas (id_venta, num_factura, fecha_venta, documento_cliente, id_cajero, total_venta, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, NOW(), NOW())',
      [nextSaleId, numFactura, documento_cliente, id_cajero, total]
    );

    const [detailRow] = await connection.query('SELECT COALESCE(MAX(id_detalle), 0) + 1 AS nextId FROM detalle_venta');
    let nextDetailId = detailRow[0].nextId;
    for (const item of items) {
      // eslint-disable-next-line no-await-in-loop
      await connection.query(
        'INSERT INTO detalle_venta (id_detalle, num_factura, id_producto, cantidad, precio_unitario, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
        [nextDetailId, numFactura, item.id_producto, item.cantidad, item.precio_unitario]
      );
      nextDetailId += 1;
    }

    await connection.commit();
    return {
      id_venta: nextSaleId,
      num_factura: numFactura,
      fecha_venta: new Date().toISOString(),
      documento_cliente,
      id_cajero: Number(id_cajero),
      total_venta: total,
      detalleVenta: items.map((item) => ({
        id_producto: Number(item.id_producto),
        cantidad: Number(item.cantidad),
        precio_unitario: Number(item.precio_unitario)
      })),
      generate_invoice: Boolean(generate_invoice)
    };
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

export async function updateSale(idVenta, { documento_cliente, items = [] }) {
  if (!isDatabaseEnabled()) {
    const sale = memorySaleById(idVenta);
    if (!sale) {
      throw new Error('Venta no encontrada');
    }

    sale.detalleVenta.forEach((line) => applyMemoryStock(line.id_producto, line.cantidad));
    sale.documento_cliente = documento_cliente;
    sale.detalleVenta = items.map((item) => ({
      id_producto: Number(item.id_producto),
      cantidad: Number(item.cantidad),
      precio_unitario: Number(item.precio_unitario)
    }));
    sale.total_venta = totalFromItems(sale.detalleVenta);
    sale.detalleVenta.forEach((line) => applyMemoryStock(line.id_producto, -line.cantidad));
    return sale;
  }

  const connection = await createPool().getConnection();
  try {
    await connection.beginTransaction();
    const [saleRows] = await connection.query('SELECT * FROM ventas WHERE id_venta = ? LIMIT 1 FOR UPDATE', [idVenta]);
    const sale = saleRows[0];
    if (!sale) {
      throw new Error('Venta no encontrada');
    }

    const [currentDetails] = await connection.query('SELECT * FROM detalle_venta WHERE num_factura = ? FOR UPDATE', [sale.num_factura]);
    for (const line of currentDetails) {
      // eslint-disable-next-line no-await-in-loop
      await adjustDbStock(connection, line.id_producto, Number(line.cantidad));
    }

    await connection.query('DELETE FROM detalle_venta WHERE num_factura = ?', [sale.num_factura]);

    const total = totalFromItems(items);
    await connection.query('UPDATE ventas SET documento_cliente = ?, total_venta = ?, updated_at = NOW() WHERE id_venta = ?', [documento_cliente, total, idVenta]);

    const [detailRow] = await connection.query('SELECT COALESCE(MAX(id_detalle), 0) + 1 AS nextId FROM detalle_venta');
    let nextDetailId = detailRow[0].nextId;
    for (const item of items) {
      // eslint-disable-next-line no-await-in-loop
      await adjustDbStock(connection, item.id_producto, -Number(item.cantidad));
      // eslint-disable-next-line no-await-in-loop
      await connection.query(
        'INSERT INTO detalle_venta (id_detalle, num_factura, id_producto, cantidad, precio_unitario, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
        [nextDetailId, sale.num_factura, item.id_producto, item.cantidad, item.precio_unitario]
      );
      nextDetailId += 1;
    }

    await connection.commit();
    return await getSaleById(idVenta);
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

export async function getInvoice(idVenta) {
  const sale = await getSaleById(idVenta);
  if (!sale) {
    throw new Error('Factura no encontrada');
  }

  if (!isDatabaseEnabled()) {
    return {
      sale,
      lines: sale.detalleVenta.map((line) => ({
        ...line,
        producto: memoryProductById(line.id_producto)
      })),
      total: sale.total_venta
    };
  }

  const products = await listProducts();
  return {
    sale,
    lines: sale.detalleVenta.map((line) => ({
      ...line,
      producto: products.find((product) => product.id_producto === Number(line.id_producto)) || null
    })),
    total: sale.total_venta
  };
}

export async function buildSummary() {
  if (!isDatabaseEnabled()) {
    return memoryBuildSummary();
  }

  const sales = await queryDbOne('SELECT COUNT(*) AS total_sales, COALESCE(SUM(total_venta), 0) AS total_revenue FROM ventas');
  const products = await queryDbOne('SELECT COUNT(*) AS products_active, SUM(CASE WHEN stock <= 12 THEN 1 ELSE 0 END) AS low_stock_products FROM productos');
  const topCategory = await queryDbOne(
    `SELECT c.nombre_categoria AS top_category
     FROM detalle_venta d
     INNER JOIN productos p ON p.id_producto = d.id_producto
     INNER JOIN categorias c ON c.id_categoria = p.id_categoria
     GROUP BY c.nombre_categoria
     ORDER BY SUM(d.cantidad) DESC
     LIMIT 1`
  );
  const topProduct = await queryDbOne(
    `SELECT p.nombre AS top_product
     FROM detalle_venta d
     INNER JOIN productos p ON p.id_producto = d.id_producto
     GROUP BY p.nombre
     ORDER BY SUM(d.cantidad) DESC
     LIMIT 1`
  );

  return {
    total_sales: Number(sales?.total_sales || 0),
    total_revenue: Number(sales?.total_revenue || 0),
    products_active: Number(products?.products_active || 0),
    low_stock_products: Number(products?.low_stock_products || 0),
    top_category: topCategory?.top_category || 'Sin categoria',
    top_product: topProduct?.top_product || 'Sin producto'
  };
}

export async function buildAnalysisLines() {
  if (!isDatabaseEnabled()) {
    return memoryAnalysisLines();
  }

  const summary = await buildSummary();
  return [
    `Categoria dominante: ${summary.top_category}.`,
    `Producto mas vendido: ${summary.top_product}.`,
    `Stock bajo: ${summary.low_stock_products} productos requieren reposicion.`
  ];
}
