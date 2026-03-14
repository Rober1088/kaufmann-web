const Database = require('better-sqlite3');
const bcrypt = require('bcryptjs');
const path = require('path');

const db = new Database(path.join(__dirname, 'kaufmann.db'));

db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    name TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('worker', 'client')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  );

  CREATE TABLE IF NOT EXISTS equipment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    type TEXT NOT NULL,
    brand TEXT NOT NULL,
    model TEXT NOT NULL,
    serial TEXT NOT NULL,
    client_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_code TEXT NOT NULL,
    description TEXT NOT NULL,
    report_date TEXT NOT NULL,
    pdf_filename TEXT NOT NULL,
    equipment_id INTEGER NOT NULL,
    uploaded_by INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
  );
`);

// Create default worker account if none exists
const workerExists = db.prepare('SELECT id FROM users WHERE role = ?').get('worker');
if (!workerExists) {
  const hash = bcrypt.hashSync('admin123', 10);
  db.prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)').run('admin', hash, 'Administrador', 'worker');
  console.log('Default worker account created: admin / admin123');
}

module.exports = db;
