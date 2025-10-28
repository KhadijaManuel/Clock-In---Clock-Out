// Simple dry-run POST using global fetch (Node 18+)
(async ()=>{
  try {
    const res = await fetch('http://localhost:1306/api/attendance/clockin', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ qrCode: process.env.TEST_QR || 'QR_EMP002' })
    });
    const text = await res.text();
    console.log('HTTP', res.status);
    try { console.log(JSON.parse(text)); } catch(e) { console.log(text); }
  } catch (e) {
    console.error('Request failed:', e?.message || e);
    process.exit(1);
  }
})();
