import { appendLog } from "../services/sheetService.js";

// Admin controller: lightweight endpoints to help verify integrations.
// NOTE: These endpoints are intended for development and debugging only.
// Do NOT expose them in production without authentication.

export async function sheetsTest(req, res) {
  // If ADMIN_TEST_KEY is configured, require the client to present it via
  // the 'x-admin-key' header. This avoids leaving an unprotected admin
  // endpoint exposed (safe default for development).
  const adminKey = process.env.ADMIN_TEST_KEY;
  if (adminKey) {
    const provided = req.headers['x-admin-key'] || req.body?.adminKey || req.query?.adminKey;
    if (!provided || String(provided) !== String(adminKey)) {
      return res.status(403).json({ ok: false, message: 'Missing or invalid admin key' });
    }
  }

  // write a single test row to the configured spreadsheet so the operator
  // can verify credentials and permissions. The test payload is minimal
  // and safe; the caller can inspect the spreadsheet and remove the row.
  try {
    const testLog = {
      employeeId: req.body?.employeeId || 'TEST_EMP',
      name: req.body?.name || 'Test User',
      action: req.body?.action || 'Test Append',
      timestamp: new Date().toISOString(),
    };

    await appendLog(testLog);
    return res.json({ ok: true, message: 'Sheets append succeeded', testLog });
  } catch (err) {
    return res.status(500).json({ ok: false, message: 'Sheets append failed', error: String(err?.message || err) });
  }
}

export default { sheetsTest };
