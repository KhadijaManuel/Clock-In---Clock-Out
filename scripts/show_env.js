import dotenv from 'dotenv';
dotenv.config();

console.log('DB_HOST=' + (process.env.DB_HOST || '<unset>'));
console.log('DB_PORT=' + (process.env.DB_PORT || '<unset>'));
console.log('DB_PASS=' + (process.env.DB_PASS ?? process.env.DB_PASSWORD ?? '<unset>'));
console.log('USE_MYSQL=' + (process.env.USE_MYSQL || '<unset>'));
