// Attendance service adapter
// This module provides a small adapter layer between controllers and the
// data source. How it works:
//  Data sources: MySQL (preferred) and JSON file (fallback)
//  Main functions:
//   - getEmployeeByQrOrId: look up employee by qrCode or numeric ID
//   - listEmployees: return list of all employees
// - Behavior: 
//   - Prefers MySQL when available (USE_MYSQL=true and connection OK)
//   - Falls back to JSON model on errors or when MySQL is disabled 
// - Input validation: ensures employeeId is numeric when querying by ID
// The implementation prefers MySQL when `USE_MYSQL=true` (see config/dbPool.js)
// but keeps a JSON fallback so development doesn't require a running DB.
// The MySQL client is dynamically imported by the pool helper so `mysql2`
// is optional in local dev.

import { findEmployee, getAllEmployees } from "../models/employeeData.js";
import { getPool } from "../config/dbPool.js";

// Gets employee by qrCode or numeric ID
export async function getEmployeeByQrOrId({ qrCode, id }) {
  // Prefer MySQL when available
  const p = await getPool();
  if (!p) {
    // JSON fallback (reads data/employees.json)
    return findEmployee({ qrCode, id });
  }

  try {
   
    // - If caller passed a qrCode, attempt to match a column named qrCode
    //   (legacy JSON mode) or fall back to the `id` column if present.
    // - If caller passed an id that looks numeric, query `employee_id`.
    //   Otherwise query the `id` column.
    if (qrCode) {
      // try qrCode column first
      let [rows] = await p.query("SELECT * FROM employees WHERE qrCode = ? LIMIT 1", [qrCode]);
      if (rows && rows.length) return normalizeRow(rows[0]);

      // fall back to `id` column match
      [rows] = await p.query("SELECT * FROM employees WHERE id = ? LIMIT 1", [qrCode]);
      if (rows && rows.length) return normalizeRow(rows[0]);
    }

    if (id) {
      // if id looks numeric, treat it as the primary key `employee_id`
      const isNumericId = (typeof id === 'number') || (/^\d+$/.test(String(id)));
      if (isNumericId) {
        const [rows] = await p.query("SELECT * FROM employees WHERE employee_id = ? LIMIT 1", [id]);
        if (rows && rows.length) return normalizeRow(rows[0]);
      } else {
        const [rows] = await p.query("SELECT * FROM employees WHERE id = ? LIMIT 1", [id]);
        if (rows && rows.length) return normalizeRow(rows[0]);
      }
    }

    return null;
  } catch (err) {
    // On error, fall back to JSON model rather than crashing the app.
    // This makes lookups remain available even if DB is temporarily unavailable. Log the error for observability.
    console.error("attendanceService: MySQL query failed, falling back to JSON model:", err?.message || err);
    return findEmployee({ qrCode, id });
  }
}

// Return list of employees 
// MySQL when available, otherwise returns the JSON-backed list.
export async function listEmployees() {
  const p = await getPool();
  if (!p) return getAllEmployees();

  try {
    const [rows] = await p.query("SELECT * FROM employees");
    // Normalize rows to the same shape as the JSON fallback
    return rows.map(normalizeRow);
  } catch (err) {
    console.error("attendanceService: MySQL query failed, falling back to JSON model:", err?.message || err);
    return getAllEmployees();
  }
}

// Normalize a DB row into the shape expected by controllers and services
// (fields: id (numeric primary key), name, qrCode)
function normalizeRow(row) {
  if (!row) return null;
  const id = row.employee_id ?? row.id ?? row.employeeId ?? null;
  const first = row.first_name || row.firstname || row.first || '';
  const last = row.last_name || row.lastname || row.last || '';
  const name = (first || last) ? `${first} ${last}`.trim() : row.name || null;
  // prefer an explicit qrCode column if present; otherwise expose the `id`
  const qrCode = row.qrCode ?? row.qr_code ?? row.id ?? null;
  return { ...row, id, employee_id: row.employee_id, name, qrCode };
}

// Future plans: export a `closePool()` helper to gracefully shut down the pool if needed.
