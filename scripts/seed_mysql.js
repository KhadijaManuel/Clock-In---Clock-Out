import { promises as fs } from 'fs';
import dotenv from 'dotenv';
dotenv.config();
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function loadEmployees() {
  const file = path.resolve(__dirname, '..', 'data', 'employees.json');
  const raw = await fs.readFile(file, 'utf8');
  return JSON.parse(raw);
}

async function main() {
  if (!process.env.USE_MYSQL || process.env.USE_MYSQL === 'false') {
    console.error('USE_MYSQL is not enabled in your environment. Set USE_MYSQL=true in .env');
    process.exit(1);
  }

  let mysql;
  try {
    mysql = await import('mysql2/promise');
  } catch (e) {
    console.error('Please install mysql2: npm install mysql2');
    process.exit(1);
  }

  const DB_HOST = process.env.DB_HOST || 'localhost';
  const DB_PORT = process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306;
  const DB_USER = process.env.DB_USER || 'root';
  const DB_PASS = process.env.DB_PASS ?? process.env.DB_PASSWORD ?? '';
  const DB_NAME = process.env.DB_NAME || 'attendance';

  const pool = mysql.createPool({
    host: DB_HOST,
    port: DB_PORT,
    user: DB_USER,
    password: DB_PASS,
    database: DB_NAME,
    waitForConnections: true,
    connectionLimit: 5,
  });

  try {
    // create tables if not exists
    await pool.query(`
      CREATE TABLE IF NOT EXISTS employees (
        id VARCHAR(64) PRIMARY KEY,
        name VARCHAR(255),
        department VARCHAR(255),
        qrCode VARCHAR(255)
      )
    `);

    await pool.query(`
      CREATE TABLE IF NOT EXISTS attendance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employeeId VARCHAR(64),
        name VARCHAR(255),
        action VARCHAR(50),
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Add a generated date-only column and a unique index to prevent multiple
    // entries for the same employee/action on the same date. Some MySQL
    // versions may not allow adding a generated column if it already exists,
    // so we guard with a CREATE OR REPLACE style approach.
    try {
      await pool.query(`ALTER TABLE attendance_logs ADD COLUMN date_only DATE GENERATED ALWAYS AS (DATE(timestamp)) STORED`);
    } catch (e) {
      // ignore if the column already exists
    }
    try {
      await pool.query(`CREATE UNIQUE INDEX IF NOT EXISTS uniq_employee_date_action ON attendance_logs (employeeId, date_only, action)`);
    } catch (e) {
      // MySQL older versions don't support IF NOT EXISTS for CREATE INDEX; attempt to create and ignore duplicate index error
    }

    const employees = await loadEmployees();

    for (const e of employees) {
      await pool.query(
        'INSERT INTO employees (id, name, department, qrCode) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), department=VALUES(department), qrCode=VALUES(qrCode)',
        [e.id, e.name, e.department, e.qrCode]
      );
    }

    console.log('Seed complete.');
  } catch (err) {
    console.error('Seed failed:', err?.message || err);
  } finally {
    await pool.end();
  }
}

main();
