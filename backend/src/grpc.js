import path from 'node:path';
import { fileURLToPath } from 'node:url';
import grpc from '@grpc/grpc-js';
import protoLoader from '@grpc/proto-loader';
import {
  buildAnalysisLines,
  buildSummary,
  createSale,
  createProduct,
  getInvoice,
  getProductById,
  listProducts,
  listSales,
  listCategories,
  listSuppliers,
  listSupplies,
  updateProductStock,
  updateSale
} from './store.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const protoPath = path.resolve(__dirname, '../grpc/proto/panaderia.proto');

const packageDefinition = protoLoader.loadSync(protoPath, {
  keepCase: true,
  longs: String,
  enums: String,
  defaults: true,
  oneofs: true
});

export const proto = grpc.loadPackageDefinition(packageDefinition).panaderia.v1;

function mapProduct(product) {
  if (!product) {
    return null;
  }

  return {
    id_producto: product.id_producto,
    codigo_producto: product.codigo_producto,
    nombre: product.nombre,
    id_categoria: product.id_categoria,
    precio: product.precio,
    stock: product.stock,
    proveedor_ids: product.proveedor_ids,
    insumos: product.insumos.map((item) => ({
      id_insumo: item.id_insumo,
      nombre_insumo: item.nombre_insumo,
      cantidad_usada: item.cantidad_usada
    }))
  };
}

function mapSale(sale) {
  return {
    id_venta: sale.id_venta,
    num_factura: sale.num_factura,
    fecha_venta: sale.fecha_venta,
    documento_cliente: sale.documento_cliente,
    id_cajero: sale.id_cajero,
    total_venta: sale.total_venta,
    detalle: sale.detalleVenta.map((line) => ({
      id_producto: line.id_producto,
      cantidad: line.cantidad,
      precio_unitario: line.precio_unitario
    }))
  };
}

export function createGrpcServer() {
  const server = new grpc.Server();

  server.addService(proto.CatalogService.service, {
    ListCategories: async (call) => {
      const categories = await listCategories();
      categories.forEach((category) => {
        call.write({ id_categoria: category.id_categoria, nombre_categoria: category.nombre_categoria });
      });
      call.end();
    },
    ListProducts: async (_, callback) => {
      const items = await listProducts();
      callback(null, { items: items.map(mapProduct) });
    },
    CreateProduct: async (call, callback) => {
      try {
        const product = await createProduct(call.request);
        callback(null, mapProduct(product));
      } catch (error) {
        callback({ code: grpc.status.INVALID_ARGUMENT, message: error.message });
      }
    },
    UpdateProductStock: async (call, callback) => {
      const product = await updateProductStock(call.request.id || call.request.id_producto, call.request.stock);
      if (!product) {
        return callback({ code: grpc.status.NOT_FOUND, message: 'Producto no encontrado' });
      }
      callback(null, mapProduct(product));
    },
    ListSupplies: async (_, callback) => {
      const items = await listSupplies();
      callback(null, {
        items: items.map((insumo) => ({
          id_insumo: insumo.id_insumo,
          nombre_insumo: insumo.nombre_insumo,
          id_categoria: insumo.id_categoria,
          stock: insumo.stock
        }))
      });
    },
    ListSuppliers: async (_, callback) => {
      const items = await listSuppliers();
      callback(null, {
        items: items.map((supplier) => ({
          id_proveedor: supplier.id_proveedor,
          nombre: supplier.nombre,
          contacto: supplier.contacto
        }))
      });
    }
  });

  server.addService(proto.SalesService.service, {
    ListSales: async (_, callback) => {
      const items = await listSales();
      callback(null, { items: items.map(mapSale) });
    },
    GetInvoice: async (call, callback) => {
      try {
        const invoice = await getInvoice(call.request.sale_id);
        callback(null, {
          sale: mapSale(invoice.sale),
          lines: invoice.lines.map((line) => ({
            id_producto: line.id_producto,
            cantidad: line.cantidad,
            precio_unitario: line.precio_unitario
          })),
          total: invoice.total
        });
      } catch (error) {
        callback({ code: grpc.status.NOT_FOUND, message: error.message });
      }
    },
    CreateSale: async (call, callback) => {
      try {
        const sale = await createSale(call.request);
        callback(null, {
          sale: mapSale(sale),
          invoice_generated: Boolean(call.request.generate_invoice)
        });
      } catch (error) {
        callback({ code: grpc.status.INVALID_ARGUMENT, message: error.message });
      }
    },
    UpdateSale: async (call, callback) => {
      try {
        const sale = await updateSale(call.request.id_venta, call.request);
        callback(null, mapSale(sale));
      } catch (error) {
        callback({ code: grpc.status.INVALID_ARGUMENT, message: error.message });
      }
    }
  });

  server.addService(proto.ReportingService.service, {
    ListSales: async (_, callback) => {
      const items = await listSales();
      callback(null, { items: items.map(mapSale) });
    },
    BuildAnalysis: async (call) => {
      const lines = await buildAnalysisLines();
      lines.forEach((text) => {
        call.write({ text });
      });
      call.end();
    },
    Summary: async (_, callback) => {
      callback(null, await buildSummary());
    }
  });

  return server;
}

export function startGrpcServer(port = 50051) {
  const server = createGrpcServer();

  return new Promise((resolve, reject) => {
    server.bindAsync(`0.0.0.0:${port}`, grpc.ServerCredentials.createInsecure(), (error, boundPort) => {
      if (error) {
        if (error.code === 'EADDRINUSE') {
          reject(new Error(`El puerto gRPC ${port} ya esta en uso. Cierra el proceso anterior o cambia GRPC_PORT.`));
          return;
        }
        reject(error);
        return;
      }
      server.start();
      resolve({ server, port: boundPort });
    });
  });
}

export function createGrpcClients(endpoint = 'localhost:50051') {
  return {
    catalog: new proto.CatalogService(endpoint, grpc.credentials.createInsecure()),
    sales: new proto.SalesService(endpoint, grpc.credentials.createInsecure()),
    reporting: new proto.ReportingService(endpoint, grpc.credentials.createInsecure())
  };
}