import bcrypt from 'bcryptjs';

export const passwordHash = bcrypt.hashSync('password', 10);

export const seedData = {
  users: [
    { id: 4, name: 'Maria Gomez', email: 'cajero@wemby.test', password: passwordHash, rol: 1 },
    { id: 5, name: 'Carlos Perez', email: 'baker@wemby.test', password: passwordHash, rol: 2 },
    { id: 6, name: 'Jefe Wemby', email: 'boss@wemby.test', password: passwordHash, rol: 3 }
  ],
  categories: [
    { id_categoria: 1, nombre_categoria: 'Panaderia' },
    { id_categoria: 2, nombre_categoria: 'Reposteria' },
    { id_categoria: 3, nombre_categoria: 'Bebidas' }
  ],
  suppliers: [
    { id_proveedor: 1, nombre: 'Harinas del Valle', contacto: '312 555 1001' },
    { id_proveedor: 2, nombre: 'Lacteos Express', contacto: '312 555 1002' },
    { id_proveedor: 3, nombre: 'Embalajes Norte', contacto: '312 555 1003' }
  ],
  insumos: [
    { id_insumo: 1, nombre_insumo: 'Harina', id_categoria: 1, stock: 120 },
    { id_insumo: 2, nombre_insumo: 'Azucar', id_categoria: 2, stock: 80 },
    { id_insumo: 3, nombre_insumo: 'Levadura', id_categoria: 1, stock: 45 }
  ],
  products: [
    {
      id_producto: 1,
      codigo_producto: 'PAN-001',
      nombre: 'Pan campesino',
      id_categoria: 1,
      precio: 2500,
      stock: 36,
      proveedor_ids: [1],
      insumos: [{ id_insumo: 1, cantidad_usada: 2 }, { id_insumo: 3, cantidad_usada: 1 }]
    },
    {
      id_producto: 2,
      codigo_producto: 'REP-002',
      nombre: 'Torta de chocolate',
      id_categoria: 2,
      precio: 18500,
      stock: 12,
      proveedor_ids: [2, 3],
      insumos: [{ id_insumo: 2, cantidad_usada: 3 }]
    },
    {
      id_producto: 3,
      codigo_producto: 'BEB-003',
      nombre: 'Cafe latte',
      id_categoria: 3,
      precio: 6200,
      stock: 24,
      proveedor_ids: [2],
      insumos: []
    }
  ],
  sales: [
    {
      id_venta: 1,
      num_factura: 'FAC-1700000001',
      fecha_venta: '2026-05-18T08:15:00',
      documento_cliente: '100200300',
      id_cajero: 4,
      total_venta: 11200,
      detalleVenta: [
        { id_producto: 1, cantidad: 2, precio_unitario: 2500 },
        { id_producto: 3, cantidad: 1, precio_unitario: 6200 }
      ]
    },
    {
      id_venta: 2,
      num_factura: 'FAC-1700000002',
      fecha_venta: '2026-05-18T11:40:00',
      documento_cliente: '900800700',
      id_cajero: 5,
      total_venta: 18500,
      detalleVenta: [{ id_producto: 2, cantidad: 1, precio_unitario: 18500 }]
    }
  ]
};