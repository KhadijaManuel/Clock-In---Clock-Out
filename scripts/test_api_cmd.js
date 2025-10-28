// Quick API test used from cmd: POST a clock-in then GET logs
const base = 'http://localhost:1306';

(async () => {
  try {
    const postRes = await fetch(`${base}/api/attendance/clockin`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ qrCode: 'QR_EMP001' }),
    });
    let postBody;
    try { postBody = await postRes.json(); } catch(e) { postBody = await postRes.text(); }
    console.log('POST /api/attendance/clockin ->', postRes.status);
    console.log(postBody);
  } catch (err) {
    console.error('POST error', err);
  }

  try {
    const getRes = await fetch(`${base}/api/attendance/logs`);
    let getBody;
    try { getBody = await getRes.json(); } catch(e) { getBody = await getRes.text(); }
    console.log('\nGET /api/attendance/logs ->', getRes.status);
    console.log(JSON.stringify(getBody, null, 2));
  } catch (err) {
    console.error('GET error', err);
  }
})();
