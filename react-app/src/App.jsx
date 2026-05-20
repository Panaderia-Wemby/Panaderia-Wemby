import React, { useEffect, useMemo, useState } from 'react';
import { Link, Navigate, Route, Routes, useNavigate, useParams } from 'react-router-dom';
import { api, setToken } from './services/api';
import {
  buildInvoice,
  calculateSaleTotal,
  categories as fallbackCategories,
  cashiers as fallbackCashiers,
  filterProducts,
  formatCurrency,
  getCategoryName,
  getRoleNavigation,
  getSupplierNames,
  insumos as fallbackInsumos,
  products as fallbackProducts,
  sales as fallbackSales,
  suppliers as fallbackSuppliers
} from './data/mockData';

const emptyData = {
  categories: fallbackCategories,
  products: fallbackProducts,
  suppliers: fallbackSuppliers,
  insumos: fallbackInsumos,
  sales: fallbackSales,
  cashiers: fallbackCashiers,
  summary: {
    total_sales: fallbackSales.length,
    total_revenue: fallbackSales.reduce((sum, sale) => sum + sale.total_venta, 0),
    products_active: fallbackProducts.length,
    low_stock_products: 0,
    top_category: 'Panaderia',
    top_product: 'Pan campesino'
  }
};

function roleNameFromRol(rol) {
  if (rol === 1) return 'seller';
  if (rol === 2) return 'baker';
  return 'boss';
}

function normalizeBootstrap(payload) {
  return {
    categories: payload.categories || emptyData.categories,
    products: payload.products || emptyData.products,
    suppliers: payload.suppliers || emptyData.suppliers,
    insumos: payload.insumos || emptyData.insumos,
    sales: (payload.sales || emptyData.sales).map((sale) => ({
      ...sale,
      detalleVenta: sale.detalle || sale.detalleVenta || []
    })),
    cashiers: payload.cashiers || emptyData.cashiers,
    summary: payload.summary || emptyData.summary
  };
}

function App() {
  const navigate = useNavigate();
  const [auth, setAuth] = useState({ status: 'loading', user: null });
  const [data, setData] = useState(emptyData);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [loginForm, setLoginForm] = useState({ email: 'boss@wemby.test', password: 'password' });
  const [saleDraft, setSaleDraft] = useState(null);

  const role = useMemo(() => roleNameFromRol(auth.user?.rol), [auth.user]);
  const navigation = useMemo(() => getRoleNavigation(role), [role]);

  useEffect(() => {
    let mounted = true;

    async function hydrate() {
      const token = localStorage.getItem('wemby_token');
      if (!token) {
        if (mounted) {
          setAuth({ status: 'guest', user: null });
          setData(emptyData);
        }
        return;
      }

      try {
        const me = await api.me();
        const bootstrap = await api.bootstrap();
        if (!mounted) return;
        setAuth({ status: 'authenticated', user: me.user });
        setData(normalizeBootstrap(bootstrap));
      } catch {
        setToken(null);
        if (mounted) {
          setAuth({ status: 'guest', user: null });
          setData(emptyData);
        }
      }
    }

    hydrate();

    return () => {
      mounted = false;
    };
  }, []);

  async function handleLogin(event) {
    event.preventDefault();
    setLoading(true);
    setError('');
    try {
      const result = await api.login(loginForm.email, loginForm.password);
      setToken(result.token);
      const bootstrap = await api.bootstrap();
      setAuth({ status: 'authenticated', user: result.user });
      setData(normalizeBootstrap(bootstrap));
      navigate('/');
    } catch (loginError) {
      setError(loginError.message || 'No fue posible iniciar sesion');
    } finally {
      setLoading(false);
    }
  }

  function handleLogout() {
    setToken(null);
    setAuth({ status: 'guest', user: null });
    setData(emptyData);
    navigate('/login');
  }

  if (auth.status === 'loading') {
    return <div className="shell"><section className="panel">Cargando...</section></div>;
  }

  return (
    <div className="shell">
      <header className="topbar">
        <div>
          <p className="eyebrow">Panaderia Wemby</p>
          <h1>React + gRPC + roles equivalentes al Laravel original</h1>
        </div>
        <div className="role-switcher">
          <label>Sesion</label>
          {auth.user ? (
            <button className="button ghost" type="button" onClick={handleLogout}>
              Cerrar sesion de {auth.user.name}
            </button>
          ) : (
            <Link className="button" to="/login">
              Iniciar sesion
            </Link>
          )}
        </div>
      </header>

      <div className="workspace">
        <aside className="sidebar">
          <div className="brand">
            <span>W</span>
            <div>
              <strong>Wemby React</strong>
              <p>{auth.user ? `${auth.user.name} · rol ${auth.user.rol}` : 'Acceso requerido'}</p>
            </div>
          </div>
          <nav>
            {auth.user &&
              navigation.map((item) => (
                <Link key={item.path} to={item.path} className="nav-link">
                  {item.label}
                </Link>
              ))}
            {!auth.user && (
              <Link className="nav-link" to="/login">
                Login
              </Link>
            )}
          </nav>
        </aside>

        <main className="content">
          <Routes>
            <Route
              path="/login"
              element={<LoginPage form={loginForm} setForm={setLoginForm} onLogin={handleLogin} loading={loading} error={error} />}
            />
            <Route
              path="/"
              element={
                <RequireAuth auth={auth}>
                  <HomePage user={auth.user} summary={data.summary} />
                </RequireAuth>
              }
            />
            <Route
              path="/productos"
              element={
                <RequireAuth auth={auth} roles={['baker', 'boss']}>
                  <ProductsPage products={data.products} categories={data.categories} />
                </RequireAuth>
              }
            />
            <Route
              path="/insumos"
              element={
                <RequireAuth auth={auth} roles={['baker', 'boss']}>
                  <SuppliesPage insumos={data.insumos} categories={data.categories} />
                </RequireAuth>
              }
            />
            <Route
              path="/proveedores"
              element={
                <RequireAuth auth={auth} roles={['baker', 'boss']}>
                  <SuppliersPage suppliers={data.suppliers} />
                </RequireAuth>
              }
            />
            <Route
              path="/ventas"
              element={
                <RequireAuth auth={auth} roles={['seller']}>
                  <SalesPage sales={data.sales} />
                </RequireAuth>
              }
            />
            <Route
              path="/ventas/crear-venta"
              element={
                <RequireAuth auth={auth} roles={['seller']}>
                  <CreateSalePage
                    user={auth.user}
                    data={data}
                    onCreated={async () => {
                      const bootstrap = await api.bootstrap();
                      setData(normalizeBootstrap(bootstrap));
                    }}
                  />
                </RequireAuth>
              }
            />
            <Route
              path="/ventas/:id"
              element={
                <RequireAuth auth={auth} roles={['seller']}>
                  <SaleDetailsPage sales={data.sales} products={data.products} />
                </RequireAuth>
              }
            />
            <Route
              path="/ventas/:id/editar"
              element={
                <RequireAuth auth={auth} roles={['seller']}>
                  <EditSalePage />
                </RequireAuth>
              }
            />
            <Route
              path="/reports"
              element={
                <RequireAuth auth={auth} role="boss">
                  <ReportsPage summary={data.summary} sales={data.sales} />
                </RequireAuth>
              }
            />
            <Route
              path="/analisis"
              element={
                <RequireAuth auth={auth} role="boss">
                  <AnalysisPage />
                </RequireAuth>
              }
            />
            <Route
              path="/factura/:id"
              element={
                <RequireAuth auth={auth} roles={['seller']}>
                  <InvoicePage sales={data.sales} products={data.products} />
                </RequireAuth>
              }
            />
            <Route path="*" element={<Navigate to={auth.user ? '/' : '/login'} replace />} />
          </Routes>
        </main>
      </div>
    </div>
  );
}

function RequireAuth({ auth, roles, children }) {
  if (!auth.user) {
    return <Navigate to="/login" replace />;
  }

  if (roles && !roles.includes(roleNameFromRol(auth.user.rol))) {
    return <Navigate to="/" replace />;
  }

  return children;
}

function LoginPage({ form, setForm, onLogin, loading, error }) {
  return (
    <section className="hero-card" style={{ alignItems: 'center' }}>
      <div className="hero-copy">
        <p className="eyebrow">Acceso</p>
        <h2>Inicia sesion para ver la misma experiencia por roles que en Laravel</h2>
        <p>
          Usuarios demo: <strong>cajero@wemby.test</strong>, <strong>baker@wemby.test</strong>, <strong>boss@wemby.test</strong>
          . Clave: <strong>password</strong>.
        </p>
      </div>
      <form className="panel" onSubmit={onLogin}>
        <div className="form-grid">
          <label>
            Email
            <input value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} />
          </label>
          <label>
            Password
            <input
              type="password"
              value={form.password}
              onChange={(event) => setForm({ ...form, password: event.target.value })}
            />
          </label>
        </div>
        {error ? <p className="summary-line">{error}</p> : null}
        <button className="button" type="submit" disabled={loading}>
          {loading ? 'Ingresando...' : 'Entrar'}
        </button>
      </form>
    </section>
  );
}

function HomePage({ user, summary }) {
  const cards = [
    { title: 'Ventas totales', value: formatCurrency(summary.total_revenue) },
    { title: 'Productos activos', value: summary.products_active },
    { title: 'Stock bajo', value: summary.low_stock_products }
  ];

  return (
    <section className="hero-card">
      <div className="hero-copy">
        <p className="eyebrow">Bienvenido</p>
        <h2>{user?.name || 'Usuario'} tiene acceso al panel distribuido</h2>
        <p>
          Esta vista usa datos reales del backend HTTP/gRPC y respeta el control por rol igual que el Laravel original.
        </p>
      </div>
      <div className="hero-grid">
        {cards.map((card) => (
          <article key={card.title} className="mini-card">
            <h3>{card.title}</h3>
            <p>{card.value}</p>
          </article>
        ))}
      </div>
    </section>
  );
}

function ProductsPage({ products, categories }) {
  const [search, setSearch] = useState('');
  const [categoria, setCategoria] = useState('');
  const filtered = filterProducts(products, { search, categoria });

  return (
    <section className="panel">
      <div className="panel-head">
        <div>
          <p className="eyebrow">Inventario</p>
          <h2>Listado de productos</h2>
        </div>
        <Link className="button" to="/ventas/crear-venta">
          Ir a ventas
        </Link>
      </div>
      <div className="filters">
        <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Buscar por nombre o codigo" />
        <select value={categoria} onChange={(event) => setCategoria(event.target.value)}>
          <option value="">Sin filtrar</option>
          {categories.map((category) => (
            <option key={category.id_categoria} value={category.id_categoria}>
              {category.nombre_categoria}
            </option>
          ))}
        </select>
      </div>
      <table>
        <thead>
          <tr>
            <th>Codigo</th>
            <th>Nombre</th>
            <th>Categoria</th>
            <th>Precio</th>
            <th>Stock</th>
            <th>Proveedores</th>
            <th>Insumos</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          {filtered.map((product) => (
            <tr key={product.id_producto}>
              <td>{product.codigo_producto}</td>
              <td>{product.nombre}</td>
              <td>{getCategoryName(product.id_categoria)}</td>
              <td>{formatCurrency(product.precio)}</td>
              <td>{product.stock}</td>
              <td>
                <div className="pill-group">
                  {getSupplierNames(product.proveedor_ids || product.proveedores || []).map((supplier) => (
                    <span key={supplier.id_proveedor} className="pill">
                      {supplier.nombre}
                    </span>
                  ))}
                </div>
              </td>
              <td>
                <div className="pill-group">
                  {(product.insumos || []).map((insumo) => (
                    <span key={insumo.id_insumo} className="pill soft">
                      {insumo.nombre_insumo}: {insumo.cantidad_usada}
                    </span>
                  ))}
                </div>
              </td>
              <td>
                <span className="link-button">Editar stock</span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}

function SuppliesPage({ insumos, categories }) {
  return (
    <section className="panel">
      <div className="panel-head">
        <div>
          <p className="eyebrow">Inventario</p>
          <h2>Listado de insumos</h2>
        </div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Categoria</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          {insumos.map((item) => (
            <tr key={item.id_insumo}>
              <td>{item.nombre_insumo}</td>
              <td>{getCategoryName(item.id_categoria || categories[0]?.id_categoria)}</td>
              <td>{item.stock}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}

function SuppliersPage({ suppliers }) {
  return (
    <section className="panel">
      <div className="panel-head">
        <div>
          <p className="eyebrow">Compras</p>
          <h2>Proveedores</h2>
        </div>
      </div>
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Contacto</th>
          </tr>
        </thead>
        <tbody>
          {suppliers.map((supplier) => (
            <tr key={supplier.id_proveedor}>
              <td>{supplier.nombre}</td>
              <td>{supplier.contacto}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}

function SalesPage({ sales }) {
  return (
    <section className="panel">
      <div className="panel-head">
        <div>
          <p className="eyebrow">Ventas</p>
          <h2>Listado de ventas</h2>
        </div>
        <Link className="button" to="/ventas/crear-venta">
          Crear venta
        </Link>
      </div>
      <table>
        <thead>
          <tr>
            <th>Factura</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Total</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          {sales.map((sale) => (
            <tr key={sale.id_venta}>
              <td>{sale.num_factura}</td>
              <td>{new Date(sale.fecha_venta).toLocaleString('es-CO')}</td>
              <td>{sale.documento_cliente}</td>
              <td>{formatCurrency(sale.total_venta)}</td>
              <td className="actions">
                <Link className="link-button" to={`/ventas/${sale.id_venta}`}>
                  Ver
                </Link>
                <Link className="link-button" to={`/ventas/${sale.id_venta}/editar`}>
                  Editar
                </Link>
                <Link className="link-button" to={`/factura/${sale.id_venta}`}>
                  Factura
                </Link>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}

function CreateSalePage({ user, data, onCreated }) {
  const navigate = useNavigate();
  const [cajeroId, setCajeroId] = useState(data.cashiers[0]?.id || user?.id);
  const [cliente, setCliente] = useState('');
  const [lines, setLines] = useState([{ productId: data.products[0]?.id_producto, quantity: 1 }]);
  const [generateInvoice, setGenerateInvoice] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  const total = calculateSaleTotal(lines, data.products);

  const updateLine = (index, field, value) => {
    setLines((current) => current.map((line, currentIndex) => (currentIndex === index ? { ...line, [field]: Number(value) } : line)));
  };

  async function handleSubmit() {
    setSubmitting(true);
    try {
      await api.createSale({
        documento_cliente: cliente,
        id_cajero: cajeroId,
        items: lines.map((line) => {
          const product = data.products.find((item) => item.id_producto === Number(line.productId));
          return {
            id_producto: Number(line.productId),
            cantidad: Number(line.quantity),
            precio_unitario: Number(product?.precio || 0)
          };
        }),
        generate_invoice: generateInvoice
      });
      await onCreated();
      navigate('/ventas');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <section className="panel">
      <div className="panel-head">
        <div>
          <p className="eyebrow">Ventas</p>
          <h2>Crear venta</h2>
        </div>
      </div>
      <div className="form-grid">
        <label>
          Cajero
          <select value={cajeroId} onChange={(event) => setCajeroId(Number(event.target.value))}>
            {data.cashiers.map((cashier) => (
              <option key={cashier.id} value={cashier.id}>
                {cashier.name}
              </option>
            ))}
          </select>
        </label>
        <label>
          Documento del cliente
          <input value={cliente} onChange={(event) => setCliente(event.target.value)} />
        </label>
      </div>
      <div className="stack">
        {lines.map((line, index) => (
          <div key={`${line.productId}-${index}`} className="sale-line">
            <label>
              Producto
              <select value={line.productId} onChange={(event) => updateLine(index, 'productId', event.target.value)}>
                {data.products.map((product) => (
                  <option key={product.id_producto} value={product.id_producto}>
                    {product.nombre} - {formatCurrency(product.precio)} - Stock: {product.stock}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Cantidad
              <input
                type="number"
                min="1"
                value={line.quantity}
                onChange={(event) => updateLine(index, 'quantity', event.target.value)}
              />
            </label>
          </div>
        ))}
      </div>
      <div className="actions-row">
        <button className="button ghost" type="button" onClick={() => setLines((current) => [...current, { ...current[0] }])}>
          Anadir producto
        </button>
        <button className="button ghost" type="button" onClick={() => setGenerateInvoice((value) => !value)}>
          {generateInvoice ? 'Generar factura: si' : 'Generar factura: no'}
        </button>
      </div>
      <div className="summary">
        <strong>Total de la venta: {formatCurrency(total)}</strong>
        <span>Cajero: {data.cashiers.find((cashier) => cashier.id === cajeroId)?.name}</span>
      </div>
      <div className="actions-row">
        <button className="button" type="button" disabled={submitting || !cliente} onClick={handleSubmit}>
          {submitting ? 'Guardando...' : 'Confirmar venta'}
        </button>
        <span className="hint">Factura: {generateInvoice ? 'si' : 'no'}</span>
      </div>
    </section>
  );
}

function SaleDetailsPage({ sales, products }) {
  const { id } = useParams();
  const sale = sales.find((entry) => entry.id_venta === Number(id));

  if (!sale) return <Navigate to="/ventas" replace />;

  return (
    <section className="panel">
      <p className="eyebrow">Venta</p>
      <h2>{sale.num_factura}</h2>
      <p>Cliente: {sale.documento_cliente}</p>
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Precio unitario</th>
          </tr>
        </thead>
        <tbody>
          {sale.detalleVenta.map((line, index) => (
            <tr key={`${line.id_producto}-${index}`}>
              <td>{products.find((product) => product.id_producto === line.id_producto)?.nombre}</td>
              <td>{line.cantidad}</td>
              <td>{formatCurrency(line.precio_unitario)}</td>
            </tr>
          ))}
        </tbody>
      </table>
      <p className="summary-line">Total: {formatCurrency(sale.total_venta)}</p>
    </section>
  );
}

function EditSalePage() {
  return (
    <section className="panel">
      <p className="eyebrow">Ventas</p>
      <h2>Editar venta</h2>
      <p>Esta pantalla queda como punto de extension para edicion por gRPC/HTTP.</p>
    </section>
  );
}

function ReportsPage({ summary, sales }) {
  return (
    <section className="panel">
      <div className="panel-head">
        <div>
          <p className="eyebrow">Reportes</p>
          <h2>Graficos y reportes</h2>
        </div>
      </div>
      <div className="metric-grid">
        <article className="metric">
          <span>Ventas hoy</span>
          <strong>{formatCurrency(summary.total_revenue)}</strong>
        </article>
        <article className="metric">
          <span>Ticket promedio</span>
          <strong>{formatCurrency(summary.total_sales ? summary.total_revenue / summary.total_sales : 0)}</strong>
        </article>
        <article className="metric">
          <span>Productos activos</span>
          <strong>{summary.products_active}</strong>
        </article>
      </div>
      <table>
        <thead>
          <tr>
            <th>Factura</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          {sales.map((sale) => (
            <tr key={sale.id_venta}>
              <td>{sale.num_factura}</td>
              <td>{formatCurrency(sale.total_venta)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}

function AnalysisPage() {
  return (
    <section className="panel">
      <div className="panel-head">
        <div>
          <p className="eyebrow">Analisis</p>
          <h2>Resultados del analisis</h2>
        </div>
      </div>
      <div className="hero-grid">
        <article className="mini-card">
          <h3>Categoria dominante</h3>
          <p>Panaderia lidera el volumen de inventario y ventas del dia.</p>
        </article>
        <article className="mini-card">
          <h3>Producto mas vendido</h3>
          <p>Pan campesino mantiene la mayor rotacion por unidad.</p>
        </article>
        <article className="mini-card">
          <h3>Riesgo de quiebre</h3>
          <p>Levadura requiere reposicion antes del siguiente turno.</p>
        </article>
      </div>
    </section>
  );
}

function InvoicePage({ sales, products }) {
  const { id } = useParams();
  const sale = sales.find((entry) => entry.id_venta === Number(id));

  if (!sale) return <Navigate to="/ventas" replace />;

  const invoice = buildInvoice(sale.id_venta, sales, products);

  return (
    <section className="panel invoice">
      <div className="panel-head">
        <div>
          <p className="eyebrow">Factura</p>
          <h2>{invoice.sale.num_factura}</h2>
        </div>
      </div>
      <div className="invoice-grid">
        <article>
          <span>Cliente</span>
          <strong>{invoice.sale.documento_cliente}</strong>
        </article>
        <article>
          <span>Fecha</span>
          <strong>{new Date(invoice.sale.fecha_venta).toLocaleString('es-CO')}</strong>
        </article>
        <article>
          <span>Total</span>
          <strong>{formatCurrency(invoice.total)}</strong>
        </article>
      </div>
      <table>
        <thead>
          <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          {invoice.lines.map((line, index) => (
            <tr key={`${line.id_producto}-${index}`}>
              <td>{line.producto?.nombre}</td>
              <td>{line.cantidad}</td>
              <td>{formatCurrency(line.cantidad * line.precio_unitario)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </section>
  );
}

export default App;