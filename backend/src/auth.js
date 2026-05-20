import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { findUserByEmail, findUserById } from './store.js';

function getSecret() {
  return process.env.JWT_SECRET || 'wemby-secret-change-me';
}

export async function login(email, password) {
  const user = await findUserByEmail(email);
  if (!user) {
    throw new Error('Credenciales invalidas');
  }

  const matches = bcrypt.compareSync(password, user.password);
  if (!matches) {
    throw new Error('Credenciales invalidas');
  }

  const token = jwt.sign({ sub: user.id, rol: user.rol, email: user.email }, getSecret(), { expiresIn: '12h' });
  return {
    token,
    user: {
      id: user.id,
      name: user.name,
      email: user.email,
      rol: user.rol
    }
  };
}

export function verifyToken(token) {
  return jwt.verify(token, getSecret());
}

export async function authMiddleware(req, res, next) {
  const header = req.headers.authorization || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;

  if (!token) {
    return res.status(401).json({ message: 'No autenticado' });
  }

  try {
    const payload = verifyToken(token);
    req.user = await findUserById(Number(payload.sub));
    if (!req.user) {
      return res.status(401).json({ message: 'Sesion invalida' });
    }
    return next();
  } catch (error) {
    return res.status(401).json({ message: 'Token invalido' });
  }
}

export function roleMiddleware(...allowedRoles) {
  return (req, res, next) => {
    if (!req.user || !allowedRoles.includes(req.user.rol)) {
      return res.status(403).json({ message: 'Acceso denegado' });
    }
    return next();
  };
}