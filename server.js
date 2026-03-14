const express = require('express');
const session = require('express-session');
const multer = require('multer');
const path = require('path');
const db = require('./database');
const bcrypt = require('bcryptjs');

const app = express();
const PORT = process.env.PORT || 3000;

// Multer config for PDF uploads
const storage = multer.diskStorage({
  destination: path.join(__dirname, 'uploads'),
  filename: (req, file, cb) => {
    const uniqueName = Date.now() + '-' + file.originalname;
    cb(null, uniqueName);
  }
});
const upload = multer({
  storage,
  fileFilter: (req, file, cb) => {
    if (file.mimetype === 'application/pdf') {
      cb(null, true);
    } else {
      cb(new Error('Solo se permiten archivos PDF'));
    }
  },
  limits: { fileSize: 20 * 1024 * 1024 } // 20MB
});

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));
app.use(session({
  secret: 'kaufmann-secret-key-2026',
  resave: false,
  saveUninitialized: false,
  cookie: { maxAge: 8 * 60 * 60 * 1000 } // 8 hours
}));

// Auth middleware
function requireAuth(req, res, next) {
  if (!req.session.user) return res.redirect('/login');
  next();
}
function requireWorker(req, res, next) {
  if (!req.session.user || req.session.user.role !== 'worker') return res.redirect('/login');
  next();
}
function requireClient(req, res, next) {
  if (!req.session.user || req.session.user.role !== 'client') return res.redirect('/login');
  next();
}

// --- Auth Routes ---
app.get('/', (req, res) => res.redirect('/login'));

app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'views', 'login.html'));
});

app.post('/login', (req, res) => {
  const { username, password } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE username = ?').get(username);
  if (!user || !bcrypt.compareSync(password, user.password)) {
    return res.redirect('/login?error=1');
  }
  req.session.user = { id: user.id, username: user.username, name: user.name, role: user.role };
  if (user.role === 'worker') return res.redirect('/worker');
  return res.redirect('/client');
});

app.get('/logout', (req, res) => {
  req.session.destroy();
  res.redirect('/login');
});

// --- Worker Routes ---
app.get('/worker', requireWorker, (req, res) => {
  res.sendFile(path.join(__dirname, 'views', 'worker.html'));
});

// API: Get all clients
app.get('/api/clients', requireWorker, (req, res) => {
  const clients = db.prepare('SELECT id, username, name FROM users WHERE role = ?').all('client');
  res.json(clients);
});

// API: Create client
app.post('/api/clients', requireWorker, (req, res) => {
  const { username, password, name } = req.body;
  if (!username || !password || !name) return res.status(400).json({ error: 'Todos los campos son requeridos' });
  const exists = db.prepare('SELECT id FROM users WHERE username = ?').get(username);
  if (exists) return res.status(400).json({ error: 'El usuario ya existe' });
  const hash = bcrypt.hashSync(password, 10);
  const result = db.prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)').run(username, hash, name, 'client');
  res.json({ id: result.lastInsertRowid, username, name });
});

// API: Get equipment (optionally filtered by client)
app.get('/api/equipment', requireAuth, (req, res) => {
  const user = req.session.user;
  let equipment;
  if (user.role === 'worker') {
    if (req.query.client_id) {
      equipment = db.prepare(`
        SELECT e.*, u.name as client_name FROM equipment e
        JOIN users u ON e.client_id = u.id
        WHERE e.client_id = ? ORDER BY e.code
      `).all(req.query.client_id);
    } else {
      equipment = db.prepare(`
        SELECT e.*, u.name as client_name FROM equipment e
        JOIN users u ON e.client_id = u.id ORDER BY e.code
      `).all();
    }
  } else {
    equipment = db.prepare(`
      SELECT e.*, u.name as client_name FROM equipment e
      JOIN users u ON e.client_id = u.id
      WHERE e.client_id = ? ORDER BY e.code
    `).all(user.id);
  }
  res.json(equipment);
});

// API: Create equipment
app.post('/api/equipment', requireWorker, (req, res) => {
  const { code, type, brand, model, serial, client_id } = req.body;
  if (!code || !type || !brand || !model || !serial || !client_id) {
    return res.status(400).json({ error: 'Todos los campos son requeridos' });
  }
  const exists = db.prepare('SELECT id FROM equipment WHERE code = ?').get(code);
  if (exists) return res.status(400).json({ error: 'El código de equipo ya existe' });
  const result = db.prepare('INSERT INTO equipment (code, type, brand, model, serial, client_id) VALUES (?, ?, ?, ?, ?, ?)').run(code, type, brand, model, serial, client_id);
  res.json({ id: result.lastInsertRowid, code, type, brand, model, serial, client_id });
});

// API: Get reports for an equipment
app.get('/api/reports/:equipment_id', requireAuth, (req, res) => {
  const user = req.session.user;
  // If client, verify ownership
  if (user.role === 'client') {
    const eq = db.prepare('SELECT * FROM equipment WHERE id = ? AND client_id = ?').get(req.params.equipment_id, user.id);
    if (!eq) return res.status(403).json({ error: 'Acceso denegado' });
  }
  const reports = db.prepare(`
    SELECT r.*, u.name as uploaded_by_name FROM reports r
    JOIN users u ON r.uploaded_by = u.id
    WHERE r.equipment_id = ? ORDER BY r.report_date DESC
  `).all(req.params.equipment_id);
  res.json(reports);
});

// API: Upload report
app.post('/api/reports', requireWorker, upload.single('pdf'), (req, res) => {
  if (!req.file) return res.status(400).json({ error: 'Archivo PDF requerido' });
  const { report_code, description, report_date, equipment_id } = req.body;
  if (!report_code || !description || !report_date || !equipment_id) {
    return res.status(400).json({ error: 'Todos los campos son requeridos' });
  }
  const result = db.prepare('INSERT INTO reports (report_code, description, report_date, pdf_filename, equipment_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)').run(report_code, description, report_date, req.file.filename, equipment_id, req.session.user.id);
  res.json({ id: result.lastInsertRowid, report_code, description, report_date, pdf_filename: req.file.filename });
});

// Download PDF
app.get('/api/reports/download/:filename', requireAuth, (req, res) => {
  const filename = path.basename(req.params.filename);
  const filePath = path.join(__dirname, 'uploads', filename);
  res.download(filePath);
});

// API: Current user
app.get('/api/me', requireAuth, (req, res) => {
  res.json(req.session.user);
});

// --- Client Routes ---
app.get('/client', requireClient, (req, res) => {
  res.sendFile(path.join(__dirname, 'views', 'client.html'));
});

app.get('/client/equipment/:id', requireClient, (req, res) => {
  res.sendFile(path.join(__dirname, 'views', 'client-equipment.html'));
});

app.get('/api/equipment/:id', requireAuth, (req, res) => {
  const user = req.session.user;
  let eq;
  if (user.role === 'client') {
    eq = db.prepare('SELECT e.*, u.name as client_name FROM equipment e JOIN users u ON e.client_id = u.id WHERE e.id = ? AND e.client_id = ?').get(req.params.id, user.id);
  } else {
    eq = db.prepare('SELECT e.*, u.name as client_name FROM equipment e JOIN users u ON e.client_id = u.id WHERE e.id = ?').get(req.params.id);
  }
  if (!eq) return res.status(404).json({ error: 'Equipo no encontrado' });
  res.json(eq);
});

app.listen(PORT, () => {
  console.log(`Kaufmann server running on http://localhost:${PORT}`);
});
