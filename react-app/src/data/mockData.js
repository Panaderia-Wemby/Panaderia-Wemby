export const roles = {
  1: 'seller',
  2: 'baker',
  3: 'boss'
};

export const categories = [
  { id_categoria: 1, nombre_categoria: 'Panaderia' },
  { id_categoria: 2, nombre_categoria: 'Reposteria' },
  { id_categoria: 3, nombre_categoria: 'Bebidas' }
];

export const suppliers = [
  { id_proveedor: 1, nombre: 'Harinas del Valle', contacto: '312 555 1001' },
  { id_proveedor: 2, nombre: 'Lacteos Express', contacto: '312 555 1002' },
  { id_proveedor: 3, nombre: 'Embalajes Norte', contacto: '312 555 1003' }
];

export const insumos = [
  { id_insumo: 1, nombre_insumo: 'Harina', id_categoria: 1, stock: 120 },
  { id_insumo: 2, nombre_insumo: 'Azucar', id_categoria: 2, stock: 80 },
  { id_insumo: 3, nombre_insumo: 'Levadura', id_categoria: 1, stock: 45 }
];

export const products = [
  {
    id_producto: 1,
    codigo_producto: 'PAN-001',
    nombre: 'Pan campesino',
    id_categoria: 1,
    precio: 2500,
    stock: 36,
    proveedores: [1],
    insumos: [{ id_insumo: 1, cantidad_usada: 2 }, { id_insumo: 3, cantidad_usada: 1 }]
  },
  {
    id_producto: 2,
    codigo_producto: 'REP-002',
    nombre: 'Torta de chocolate',
    id_categoria: 2,
    precio: 18500,
    stock: 12,
    proveedores: [2, 3],
    insumos: [{ id_insumo: 2, cantidad_usada: 3 }]
  },
  {
    id_producto: 3,
    codigo_producto: 'BEB-003',
    nombre: 'Cafe latte',
    id_categoria: 3,
    precio: 6200,
    stock: 24,
    proveedores: [2],
    insumos: []
  }
];

export const cashiers = [
  { id: 4, name: 'Maria Gomez', rol: 1 },
  { id: 5, name: 'Carlos Perez', rol: 1 }
];

export const sales = [
  {
    id_venta: 1,
    num_factura: 'FAC-1700000001',
    fecha_venta: '2026-05-18T08:15:00',
    documento_cliente: '100200300',
    id_cajero: 4,
    total_venta: 11200,
    detalleVenta: [
      { id_detalle: 1, num_factura: 'FAC-1700000001', id_producto: 1, cantidad: 2, precio_unitario: 2500 },
      { id_detalle: 2, num_factura: 'FAC-1700000001', id_producto: 3, cantidad: 1, precio_unitario: 6200 }
    ]
  },
  {
    id_venta: 2,
    num_factura: 'FAC-1700000002',
    fecha_venta: '2026-05-18T11:40:00',
    documento_cliente: '900800700',
    id_cajero: 5,
    total_venta: 18500,
    detalleVenta: [
      { id_detalle: 3, num_factura: 'FAC-1700000002', id_producto: 2, cantidad: 1, precio_unitario: 18500 }
    ]
  }
];

export function formatCurrency(amount) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0
  }).format(amount);
}

export function getCategoryName(categoryId) {
  return categories.find((category) => category.id_categoria === categoryId)?.nombre_categoria ?? 'Sin categoria';
}

export function getSupplierNames(supplierIds = []) {
  return suppliers.filter((supplier) => supplierIds.includes(supplier.id_proveedor));
}

export function filterProducts(productList, { search = '', categoria = '' } = {}) {
  const normalizedSearch = search.trim().toLowerCase();
  return productList.filter((product) => {
    const matchesSearch =
      normalizedSearch.length === 0 ||
      product.nombre.toLowerCase().includes(normalizedSearch) ||
      product.codigo_producto.toLowerCase().includes(normalizedSearch);
    const matchesCategory = categoria === '' || String(product.id_categoria) === String(categoria);
    return matchesSearch && matchesCategory;
  });
}

export function calculateSaleTotal(lines, catalog = products) {
  return lines.reduce((total, line) => {
    const product = catalog.find((item) => item.id_producto === Number(line.productId));
    const quantity = Number(line.quantity || 0);
    const price = Number(product?.precio || line.price || 0);
    return total + quantity * price;
  }, 0);
}

export function getRoleNavigation(role) {
  const shared = [{ label: 'Inicio', path: '/' }];
  const inventory = [
    { label: 'Inventario de productos', path: '/productos' },
    { label: 'Inventario de insumos', path: '/insumos' },
    { label: 'Proveedores', path: '/proveedores' }
  ];
  const salesMenu = [
    { label: 'Ventas', path: '/ventas' },
    { label: 'Crear venta', path: '/ventas/crear-venta' }
  ];
  const reports = [
    { label: 'Reportes', path: '/reports' },
    { label: 'Analisis', path: '/analisis' },
    { label: 'Factura', path: '/factura/1' }
  ];

  if (role === 'seller') {
    return [...shared, ...salesMenu];
  }

  if (role === 'baker') {
    return [...shared, ...inventory];
  }

  return [...shared, ...inventory, ...reports];
}

export function buildInvoice(saleId, saleList = sales, productList = products) {
  const sale = saleList.find((entry) => entry.id_venta === Number(saleId));
  if (!sale) {
    return null;
  }

  return {
    sale,
    lines: sale.detalleVenta.map((line) => ({
      ...line,
      producto: productList.find((product) => product.id_producto === line.id_producto)
    })),
    total: sale.total_venta
  };
}