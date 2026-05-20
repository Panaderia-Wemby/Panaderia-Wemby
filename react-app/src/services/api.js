const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8080/api';

function getToken() {
  return localStorage.getItem('wemby_token');
}

export function setToken(token) {
  if (token) {
    localStorage.setItem('wemby_token', token);
    return;
  }
  localStorage.removeItem('wemby_token');
}

async function request(path, options = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers || {}),
      ...(getToken() ? { Authorization: `Bearer ${getToken()}` } : {})
    }
  });

  const payload = await response.json();
  if (!response.ok) {
    throw new Error(payload.message || 'Error de red');
  }
  return payload;
}

export const api = {
  login: (email, password) => request('/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) }),
  me: () => request('/auth/me'),
  bootstrap: () => request('/bootstrap'),
  products: () => request('/catalog/products'),
  suppliers: () => request('/catalog/suppliers'),
  insumos: () => request('/catalog/insumos'),
  sales: () => request('/sales'),
  createSale: (payload) => request('/sales', { method: 'POST', body: JSON.stringify(payload) }),
  invoice: (id) => request(`/sales/${id}/invoice`),
  reportSummary: () => request('/reports/summary'),
  reportAnalysis: () => request('/reports/analysis')
};