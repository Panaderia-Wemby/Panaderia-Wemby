import test from 'node:test';
import assert from 'node:assert/strict';
import { login } from '../src/auth.js';
import { buildAnalysisLines, buildSummary, createSale, getInvoice, getProductById } from '../src/store.js';

test('login returns a token for seeded users', async () => {
  const result = await login('boss@wemby.test', 'password');
  assert.ok(result.token.length > 20);
  assert.equal(result.user.rol, 3);
});

test('sale creation updates stock and can generate invoice data', async () => {
  const before = (await getProductById(1)).stock;
  const sale = await createSale({
    documento_cliente: '123',
    id_cajero: 4,
    items: [{ id_producto: 1, cantidad: 1, precio_unitario: 2500 }],
    generate_invoice: true
  });

  assert.equal(sale.total_venta, 2500);
  assert.equal((await getProductById(1)).stock, before - 1);

  const invoice = await getInvoice(sale.id_venta);
  assert.equal(invoice.sale.num_factura, sale.num_factura);
  assert.equal(invoice.lines[0].producto.id_producto, 1);
});

test('summary and analysis expose report data', async () => {
  const summary = await buildSummary();
  const lines = await buildAnalysisLines();
  assert.ok(summary.total_sales >= 1);
  assert.ok(lines.length >= 3);
});