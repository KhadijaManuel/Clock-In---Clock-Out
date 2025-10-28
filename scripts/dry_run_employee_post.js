(async ()=>{
  try {
    const emp = process.env.TEST_EMP_ID || '9202080806014';
    // add a client-side timeout so the script doesn't hang indefinitely
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 5000);
    const res = await fetch('http://localhost:1306/api/attendance/clockin', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ employeeId: emp }),
      signal: controller.signal
    });
    clearTimeout(timeout);
    const text = await res.text();
    console.log('HTTP', res.status);
    try { console.log(JSON.parse(text)); } catch(e) { console.log(text); }
  } catch (e) {
    console.error('Request failed:', e?.message || e);
    process.exit(1);
  }
})();
