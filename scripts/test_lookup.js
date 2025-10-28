import dotenv from 'dotenv'; dotenv.config();
import { getEmployeeByQrOrId } from '../services/attendanceService.js';
import mysql from 'mysql2/promise';

(async ()=>{
  const qr = process.env.TEST_QR || 'QR_EMP003';
  try{
    const emp = await getEmployeeByQrOrId({ qrCode: qr });
    console.log('Lookup for', qr, '=>', emp);
    // Direct DB query for comparison
    try{
      const conn = await mysql.createConnection({
        host: process.env.DB_HOST || 'localhost',
        port: process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306,
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASS ?? process.env.DB_PASSWORD ?? '',
        database: process.env.DB_NAME || 'attendance'
      });
      const [rows] = await conn.query('SELECT * FROM employees WHERE qrCode = ? LIMIT 1', [qr]);
      console.log('Direct DB query result:', rows.length ? rows[0] : null);
      await conn.end();
    }catch(e){
      console.error('Direct DB query failed:', e?.message||e);
    }
  }catch(e){
    console.error('Lookup error', e?.message||e);
  }
})();
