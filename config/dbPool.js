// ESM-compatible DB pool helper. Dynamically imports mysql2/promise when needed.
// This module centralizes pool creation and closing so the rest of the app
// can be agnostic to whether MySQL is enabled. getPool() returns:
// - a mysql2/promise pool when USE_MYSQL=true and the pool was created
// - null when MySQL integration is disabled or creation fails
// The helper trims and normalizes env vars and supports both DB_PASS and
// DB_PASSWORD names for flexibility.

let pool = null;

export async function getPool() {
  if (pool) return pool;

  const useMysql = (process.env.USE_MYSQL || "").toString().trim().toLowerCase() === "true";
  if (!useMysql) return null;

  try {
    // lazy import so mysql2 is optional when USE_MYSQL is false
    const mysql = await import('mysql2/promise');
    const DB_HOST = (process.env.DB_HOST || 'localhost').toString().trim();
    const DB_PORT = process.env.DB_PORT ? Number(process.env.DB_PORT.toString().trim()) : 3306;
    const DB_USER = (process.env.DB_USER || 'root').toString().trim();
    // support DB_PASS or DB_PASSWORD env var names
    const DB_PASS = (process.env.DB_PASS ?? process.env.DB_PASSWORD ?? '').toString();
  const DB_NAME = (process.env.DB_NAME || 'tracker_db').toString().trim();

    pool = mysql.createPool({
      host: DB_HOST,
      port: DB_PORT,
      user: DB_USER,
      password: DB_PASS,
      database: DB_NAME,
      waitForConnections: true,
      connectionLimit: 10,
    });


    return pool;
  } catch (err) {
    console.error('config/dbPool: failed to create pool; is mysql2 installed?', err?.message || err);
    return null;
  }
}

export async function closePool() {
  if (!pool) return;
  try {
    await pool.end();
  } catch (e) {
    console.error('config/dbPool: error closing pool', e?.message || e);
  } finally {
    pool = null;
  }
}
