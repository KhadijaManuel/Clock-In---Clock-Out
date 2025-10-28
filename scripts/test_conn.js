import dotenv from 'dotenv';
dotenv.config();

async function main(){
  try{
    const mysql = await import('mysql2/promise');
    const conn = await mysql.createConnection({
      host: process.env.DB_HOST || 'localhost',
      port: process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306,
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASS ?? process.env.DB_PASSWORD ?? '',
      database: process.env.DB_NAME || undefined,
      connectTimeout: 5000,
    });
    console.log('Connection established');
    await conn.end();
  }catch(err){
    console.error('Connection error:');
    console.error(err);
    process.exit(1);
  }
}

main();
