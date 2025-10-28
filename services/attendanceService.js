// Attendance service adapter
// This module provides a small adapter layer between controllers and the
// data source. Contract:
// - Inputs: { qrCode?, id? } for lookups
// - Outputs: employee object or null
// - Error handling: on transient DB errors we fall back to the JSON model
// The implementation prefers MySQL when `USE_MYSQL=true` (see config/dbPool.js)
// but keeps a JSON fallback so development doesn't require a running DB.
// The MySQL client is dynamically imported by the pool helper so `mysql2`
// is optional in local dev.

import { findEmployee, getAllEmployees } from "../models/employeeData.js";
import { getPool } from "../config/dbPool.js";

// Find employee by qrCode or id. Returns the employee object or null.
export async function getEmployeeByQrOrId({ qrCode, id }) {
  // getPool() returns a mysql2/promise pool when USE_MYSQL=true and the
  // connection test succeeded, otherwise it returns null and we use the JSON
  // fallback. This keeps the controller code simple.
  const p = await getPool();
  if (!p) {
    // JSON fallback (reads data/employees.json)
    return findEmployee({ qrCode, id });
  }

  // Use MySQL table `employees` with at least columns: id, name, department, qrCode
  try {
    // Note: the SQL schema in some DB dumps uses `employee_id` as the
    // numeric primary key and `id` as a national/id string. To support
    // both styles we handle two cases:
    // - If caller passed a qrCode, attempt to match a column named qrCode
    //   (legacy JSON mode) or fall back to the `id` column if present.
    // - If caller passed an id that looks numeric, query `employee_id`.
    //   Otherwise query the `id` column.
    if (qrCode) {
      // try qrCode column first
      let [rows] = await p.query("SELECT * FROM employees WHERE qrCode = ? LIMIT 1", [qrCode]);
      if (rows && rows.length) return normalizeRow(rows[0]);

      // fall back to `id` column match (some schemas store a token/national id)
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
    // This is a pragmatic choice: lookups remain available even if DB is
    // temporarily unavailable. Log the error for observability.
    console.error("attendanceService: MySQL query failed, falling back to JSON model:", err?.message || err);
    return findEmployee({ qrCode, id });
  }
}

// Return list of employees (async). Similar fallback semantics apply: prefer
// MySQL when available, otherwise return the JSON-backed list.
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

// Future: export a `closePool()` helper to gracefully shut down the pool if needed.
