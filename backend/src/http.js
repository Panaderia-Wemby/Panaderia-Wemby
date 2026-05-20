import express from 'express';
import cors from 'cors';
import { authMiddleware, login, roleMiddleware, verifyToken } from './auth.js';
import { listCashiers } from './store.js';

function publicUser(user) {
  return user
    ? {
        id: user.id,
        name: user.name,
        email: user.email,
        rol: user.rol
      }
    : null;
}

function callUnary(client, method, payload = {}) {
  return new Promise((resolve, reject) => {
    client[method](payload, (error, response) => {
      if (error) {
        reject(error);
        return;
      }
      resolve(response);
    });
  });
}

function callServerStream(client, method, payload = {}) {
  return new Promise((resolve, reject) => {
    const items = [];
    const stream = client[method](payload);
    stream.on('data', (item) => items.push(item));
    stream.on('error', reject);
    stream.on('end', () => resolve(items));
  });
}

export function createHttpApp(clients) {
  const app = express();

  app.use(cors({ origin: process.env.API_ORIGIN || '*' }));
  app.use(express.json());

  app.post('/api/auth/login', async (req, res) => {
    try {
      const result = await login(req.body.email, req.body.password);
      res.json(result);
    } catch (error) {
      res.status(401).json({ message: error.message });
    }
  });

  app.get('/api/auth/me', authMiddleware, (req, res) => {
    res.json({ user: publicUser(req.user) });
  });

  app.get('/api/bootstrap', authMiddleware, async (req, res, next) => {
    try {
      const [categories, products, suppliers, insumos, sales, summary] = await Promise.all([
        callServerStream(clients.catalog, 'ListCategories'),
        callUnary(clients.catalog, 'ListProducts'),
        callUnary(clients.catalog, 'ListSuppliers'),
        callUnary(clients.catalog, 'ListSupplies'),
        callUnary(clients.sales, 'ListSales'),
        callUnary(clients.reporting, 'Summary')
      ]);

      res.json({
        user: publicUser(req.user),
        categories,
        products: products.items,
        suppliers: suppliers.items,
        insumos: insumos.items,
        sales: sales.items,
        cashiers: await listCashiers(),
        summary
      });
    } catch (error) {
      next(error);
    }
  });

  app.get('/api/catalog/products', authMiddleware, async (req, res, next) => {
    try {
      const response = await callUnary(clients.catalog, 'ListProducts');
      res.json(response.items);
    } catch (error) {
      next(error);
    }
  });

  app.post('/api/catalog/products', authMiddleware, roleMiddleware(2, 3), async (req, res, next) => {
    try {
      const response = await callUnary(clients.catalog, 'CreateProduct', req.body);
      res.status(201).json(response);
    } catch (error) {
      next(error);
    }
  });

  app.patch('/api/catalog/products/:id/stock', authMiddleware, roleMiddleware(2, 3), async (req, res, next) => {
    try {
      const response = await callUnary(clients.catalog, 'UpdateProductStock', {
        id: Number(req.params.id),
        stock: Number(req.body.stock)
      });
      res.json(response);
    } catch (error) {
      next(error);
    }
  });

  app.get('/api/catalog/suppliers', authMiddleware, async (req, res, next) => {
    try {
      const response = await callUnary(clients.catalog, 'ListSuppliers');
      res.json(response.items);
    } catch (error) {
      next(error);
    }
  });

  app.get('/api/catalog/insumos', authMiddleware, async (req, res, next) => {
    try {
      const response = await callUnary(clients.catalog, 'ListSupplies');
      res.json(response.items);
    } catch (error) {
      next(error);
    }
  });

  app.get('/api/sales', authMiddleware, async (req, res, next) => {
    try {
      const response = await callUnary(clients.sales, 'ListSales');
      res.json(response.items);
    } catch (error) {
      next(error);
    }
  });

  app.post('/api/sales', authMiddleware, roleMiddleware(1, 3), async (req, res, next) => {
    try {
      const response = await callUnary(clients.sales, 'CreateSale', req.body);
      res.status(201).json(response);
    } catch (error) {
      next(error);
    }
  });

  app.get('/api/sales/:id/invoice', authMiddleware, async (req, res, next) => {
    try {
      const response = await callUnary(clients.sales, 'GetInvoice', { sale_id: Number(req.params.id) });
      res.json(response);
    } catch (error) {
      next(error);
    }
  });

  app.get('/api/reports/summary', authMiddleware, roleMiddleware(3), async (req, res, next) => {
    try {
      const response = await callUnary(clients.reporting, 'Summary');
      res.json(response);
    } catch (error) {
      next(error);
    }
  });

  app.get('/api/reports/analysis', authMiddleware, roleMiddleware(3), async (req, res, next) => {
    try {
      const lines = await callServerStream(clients.reporting, 'BuildAnalysis');
      res.json(lines);
    } catch (error) {
      next(error);
    }
  });

  app.use((error, req, res, _next) => {
    const status = error.code === 5 ? 404 : 500;
    res.status(status).json({ message: error.message || 'Error interno' });
  });

  return app;
}