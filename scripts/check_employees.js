import dotenv from 'dotenv';
dotenv.config();
import { getPool } from '../config/dbPool.js';

(async ()=>{
  const p = await getPool();
  if (!p) return console.error('No DB pool available (check USE_MYSQL, credentials)');
  try {
    const [rows] = await p.query('SELECT id, name, qrCode FROM employees LIMIT 100');
    console.log('employees rows:', rows);
  } catch (e) {
    console.error('Query failed:', e?.message || e);
  } finally {
    // close pool
    try { await p.end(); } catch {};
  }
})();
