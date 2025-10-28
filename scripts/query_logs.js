import dotenv from 'dotenv';
dotenv.config();

const mysql = await import('mysql2/promise');

const DB_HOST = process.env.DB_HOST || 'localhost';
const DB_PORT = process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306;
const DB_USER = process.env.DB_USER || 'root';
const DB_PASS = process.env.DB_PASS ?? process.env.DB_PASSWORD ?? '';
const DB_NAME = process.env.DB_NAME || 'attendance';

const pool = mysql.createPool({ host: DB_HOST, port: DB_PORT, user: DB_USER, password: DB_PASS, database: DB_NAME });

async function main(){
  try{
    const [rows] = await pool.query('SELECT * FROM attendance_logs ORDER BY id DESC LIMIT 20');
    console.log('Last attendance_logs rows:');
    console.table(rows);
  }catch(e){
    console.error('Query failed:', e?.message || e);
  }finally{
    await pool.end();
  }
}

main();
