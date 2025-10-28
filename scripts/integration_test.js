import dotenv from 'dotenv';
dotenv.config();
import fetch from 'node-fetch';
import { strict as assert } from 'assert';

const base = 'http://localhost:1306';

async function postClockin(qr) {
  const res = await fetch(`${base}/api/attendance/clockin`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ qrCode: qr }),
  });
  const body = await res.json();
  return { status: res.status, body };
}

async function getLogs() {
  const res = await fetch(`${base}/api/attendance/logs`);
  const body = await res.json();
  return { status: res.status, body };
}

async function main(){
  console.log('Running integration test: POST clockin and GET logs');
  const testQr = process.env.TEST_QR || 'QR_EMP002';
  const { status, body } = await postClockin(testQr);
  console.log('POST status', status, 'body', body);
  assert.equal(status, 200, 'Expected 200 from clockin');

  const logs = await getLogs();
  console.log('GET logs status', logs.status);
  assert.equal(logs.status, 200, 'Expected 200 from logs');
  assert.ok(Array.isArray(logs.body), 'Logs should be an array');
  console.log('Last log:', logs.body[logs.body.length - 1]);

  // If MySQL is enabled, optionally check DB rows using query_logs script
  console.log('Integration test completed successfully.');
}

main().catch(err => {
  console.error('Integration test failed:', err?.message || err);
  process.exit(1);
});
