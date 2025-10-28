// persistenceService: durable-ish recording of attendance logs.
// Responsibilities:
// - always push to the in-memory `attendanceLogs` for fast access
// - optionally insert a row into MySQL (if USE_MYSQL=true and pool available)
// - optionally append to Google Sheets; if Sheets append fails we enqueue
//   the write and retry asynchronously with exponential backoff. Exhausted
//   failures are persisted to `data/sheet_failures.json` for manual inspection.

import { attendanceLogs } from "../models/attendanceData.js";
import { appendLog as appendToSheet } from "./sheetService.js";
import { getPool } from "../config/dbPool.js";
import { writeFile } from "fs/promises";
import path from "path";

// Path where exhausted sheet failures are persisted for admins.
const FAILURE_STORE = path.resolve(new URL(import.meta.url).pathname, "..", "data", "sheet_failures.json");

// In-memory retry queue for failed sheet writes. Each item shape:
// { log, retries: number, backoff: ms }
const sheetQueue = [];
let sheetWorkerStarted = false;

// Persist failures to disk so that manual investigation is possible. This is
// intentionally best-effort: failing to persist will not break the app.
async function persistFailures() {
  try {
    // Only persist items that have exhausted retries (retries <= 0)
    const failures = sheetQueue.filter(i => i.retries <= 0).map(i => i.log || i);
    await writeFile(FAILURE_STORE, JSON.stringify(failures, null, 2), 'utf8');
  } catch (e) {
    console.error('persistenceService: failed to persist sheet failures', e?.message || e);
  }
}

// Background worker that processes the sheetQueue with a simple exponential
// backoff strategy. This worker is intentionally simple (in-memory). For
// production reliability replace with a durable queue (Redis/Bull, RabbitMQ, etc.).
function startSheetWorker() {
  if (sheetWorkerStarted) return;
  sheetWorkerStarted = true;

  // worker runs on an interval; it pulls one item at a time so heavy-load
  // won't overwhelm the Google Sheets API. Backoff/rescheduling is done via
  // setTimeout so long-running intervals don't block other operations.
  setInterval(async () => {
    if (sheetQueue.length === 0) return;
    const item = sheetQueue.shift();
    try {
      // attempt the append; appendToSheet itself will no-op if sheets are disabled
      await appendToSheet(item.log);
      // success: nothing more to do for this item
    } catch (err) {
      // decrement retry counter and increase backoff
      item.retries = (item.retries || 3) - 1;
      item.backoff = (item.backoff || 1000) * 2;
      if (item.retries > 0) {
        // schedule the next attempt after backoff
        setTimeout(() => sheetQueue.push(item), item.backoff);
      } else {
        // exhausted retries -> persist for manual inspection
        sheetQueue.push(item); // keep for persistence filter
        await persistFailures();
      }
    }
  }, 5000);
}

// recordLog: main exported function used by controllers. The function keeps
// the operation resilient: DB and Sheets failures are logged and handled
// asynchronously so HTTP responses aren't blocked by external systems.
export async function recordLog(log) {
  // Try to write to MySQL when available. We prefer the DB as the source
  // of truth for deduplication. If the pool exists, attempt an INSERT IGNORE
  // so we can detect duplicates (affectedRows === 0).
  const p = await getPool();
  if (p) {
    try {
      const [result] = await p.query(
        "INSERT IGNORE INTO attendance_logs (employeeId, name, action, timestamp) VALUES (?, ?, ?, ?)",
        [log.employeeId, log.name, log.action, log.timestamp]
      );

      // If affectedRows is 0 the row was ignored due to unique constraint
      if (result && typeof result.affectedRows === 'number' && result.affectedRows === 0) {
        // Duplicate detected at the DB level.
        return { inserted: false, reason: 'duplicate' };
      }

      // Successful DB insert. Now push to in-memory store and append to sheets.
      attendanceLogs.push(log);
      try {
        await appendToSheet(log);
      } catch (err) {
        console.error("persistenceService: sheet append failed, enqueuing for retry:", err?.message || err);
        sheetQueue.push({ log, retries: 3, backoff: 1000 });
        startSheetWorker();
      }

      return { inserted: true };
    } catch (err) {
      // DB attempt failed unexpectedly. Fall back to prior behavior:
      // push to in-memory and try Sheets so the app remains usable.
      console.error("persistenceService: MySQL insert failed, falling back to in-memory:", err?.message || err);
      attendanceLogs.push(log);
      try {
        await appendToSheet(log);
      } catch (err2) {
        console.error("persistenceService: sheet append failed, enqueuing for retry:", err2?.message || err2);
        sheetQueue.push({ log, retries: 3, backoff: 1000 });
        startSheetWorker();
      }
      return { inserted: true, fallback: true };
    }
  }

  // No DB configured: behave as before (in-memory + Sheets)
  attendanceLogs.push(log);
  try {
    await appendToSheet(log);
  } catch (err) {
    console.error("persistenceService: sheet append failed, enqueuing for retry:", err?.message || err);
    sheetQueue.push({ log, retries: 3, backoff: 1000 });
    startSheetWorker();
  }
  return { inserted: true, fallback: 'no-db' };
}

export default { recordLog };
