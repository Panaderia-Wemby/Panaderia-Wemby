import { describe, expect, it } from 'vitest';
import {
  buildInvoice,
  calculateSaleTotal,
  filterProducts,
  getRoleNavigation,
  products,
  sales
} from './mockData';

describe('mockData helpers', () => {
  it('filters products by search and category', () => {
    const filtered = filterProducts(products, { search: 'pan', categoria: 1 });
    expect(filtered).toHaveLength(1);
    expect(filtered[0].codigo_producto).toBe('PAN-001');
  });

  it('calculates sale totals from catalog prices', () => {
    const total = calculateSaleTotal([
      { productId: 1, quantity: 2 },
      { productId: 3, quantity: 1 }
    ]);
    expect(total).toBe(11200);
  });

  it('builds an invoice with sale details and products', () => {
    const invoice = buildInvoice(1, sales, products);
    expect(invoice?.sale.num_factura).toBe('FAC-1700000001');
    expect(invoice?.lines).toHaveLength(2);
    expect(invoice?.lines[0].producto?.nombre).toBe('Pan campesino');
  });

  it('exposes role based navigation similar to Laravel access rules', () => {
    expect(getRoleNavigation('seller')).toEqual([
      { label: 'Inicio', path: '/' },
      { label: 'Ventas', path: '/ventas' },
      { label: 'Crear venta', path: '/ventas/crear-venta' }
    ]);
    expect(getRoleNavigation('boss').some((item) => item.path === '/analisis')).toBe(true);
  });
});