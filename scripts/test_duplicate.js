import dotenv from 'dotenv'; dotenv.config();
import fetch from 'node-fetch';

const base = 'http://localhost:1306';
const qr = process.env.TEST_QR || 'QR_EMP002';

async function postClockin() {
  const res = await fetch(`${base}/api/attendance/clockin`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ qrCode: qr }),
  });
  const body = await res.json().catch(() => null);
  return { status: res.status, body };
}

async function main() {
  console.log('Posting first clock-in...');
  const first = await postClockin();
  console.log('First:', first.status, first.body);

  console.log('Posting second clock-in (should be duplicate)...');
  const second = await postClockin();
  console.log('Second:', second.status, second.body);
}

main().catch(e => console.error(e));
