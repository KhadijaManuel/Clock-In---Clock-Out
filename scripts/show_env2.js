import dotenv from 'dotenv';
import { readFile } from 'fs/promises';
import path from 'path';

console.log('cwd=' + process.cwd());
// load dotenv
const r = dotenv.config();
console.log('dotenv result parsed keys:', Object.keys(r.parsed || {}));
try{
  const raw = await readFile(path.resolve(process.cwd(), '.env'), 'utf8');
  console.log('\n.env file contents (raw):\n' + raw);
}catch(e){
  console.log('failed to read .env:', e?.message || e);
}

console.log('\nEffective env values:');
console.log('DB_HOST=' + (process.env.DB_HOST || '<unset>'));
console.log('DB_PORT=' + (process.env.DB_PORT || '<unset>'));
console.log('DB_PASS=' + (process.env.DB_PASS ?? process.env.DB_PASSWORD ?? '<unset>'));
console.log('USE_MYSQL=' + (process.env.USE_MYSQL || '<unset>'));
