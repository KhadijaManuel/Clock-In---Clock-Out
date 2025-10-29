// sheetService: a thin wrapper to append attendance logs to Google Sheets.
// Responsibilities:
// - Provides an appendLog(log) function that appends a log to the configured
//   Google Sheets spreadsheet and range.
// - Provides a hasClockInToday(employeeId) function that checks whether the
//   given employee has a "Clock In" entry for today in the sheet.

import { readFile } from "fs/promises";

const USE_SHEETS = process.env.USE_SHEETS === "true";
const SPREADSHEET_ID = process.env.GOOGLE_SHEETS_SPREADSHEET_ID || process.env.SHEET_ID;
const SHEET_RANGE = process.env.GOOGLE_SHEETS_RANGE || "Sheet1!A:D"; // default range

// Build an authorized JWT client for the Google Sheets API. Returns { jwt, google }
// to allow lazy-importing the googleapis module only when needed.
async function getAuthClient() {
  // attempts to obtain service account JSON from env or file
  let keyJson = null;
  if (process.env.GOOGLE_SERVICE_ACCOUNT_JSON) {
    try {
      keyJson = JSON.parse(process.env.GOOGLE_SERVICE_ACCOUNT_JSON);
    } catch (e) {
      console.error("sheetService: failed to parse GOOGLE_SERVICE_ACCOUNT_JSON", e?.message || e);
      keyJson = null;
    }
  }
  if (!keyJson && process.env.GOOGLE_APPLICATION_CREDENTIALS) {
    try {
      const raw = await readFile(process.env.GOOGLE_APPLICATION_CREDENTIALS, "utf8");
      keyJson = JSON.parse(raw);
    } catch (e) {
      console.error("sheetService: failed to read GOOGLE_APPLICATION_CREDENTIALS", e?.message || e);
      keyJson = null;
    }
  }

  if (!keyJson) return null;

  // lazy import so consumers who don't enable sheets won't need googleapis installed
  const { google } = await import("googleapis");
  const scopes = ["https://www.googleapis.com/auth/spreadsheets"];
  const jwt = new google.auth.JWT(keyJson.client_email, null, keyJson.private_key, scopes);
  await jwt.authorize();
  return { jwt, google };
}

export async function appendLog(log) {
  // No-op when sheets integration is disabled to keep developer ergonomics simple
  if (!USE_SHEETS) return;
  if (!SPREADSHEET_ID) {
    console.error("sheetService: GOOGLE_SHEETS_SPREADSHEET_ID not set");
    return;
  }

  try {
    const auth = await getAuthClient();
    if (!auth) {
      console.error("sheetService: no Google auth available");
      return;
    }

    const sheets = auth.google.sheets({ version: "v4", auth: auth.jwt });

    // Append rows in the order: employeeId, name, action, timestamp. Keep
    // the shape compact so the spreadsheet is easy to filter/sort later.
    const values = [[log.employeeId, log.name, log.action, log.timestamp]];
    await sheets.spreadsheets.values.append({
      spreadsheetId: SPREADSHEET_ID,
      range: SHEET_RANGE,
      valueInputOption: "RAW",
      requestBody: { values },
    });
  } catch (err) {
    // Let callers decide how to handle failures; we log here for visibility.
    console.error("sheetService: failed to append log:", err?.message || err);
    throw err;
  }
}

// hasClockInToday: reads the configured sheet range and checks whether a row
// exists for the given employeeId and action on today's date. Returns true
// when a matching row is found.
export async function hasClockInToday(employeeId, action = 'Clock In') {
  if (!USE_SHEETS) throw new Error('Sheets integration is not enabled (USE_SHEETS=false)');
  if (!SPREADSHEET_ID) throw new Error('GOOGLE_SHEETS_SPREADSHEET_ID is not configured');

  const auth = await getAuthClient();
  if (!auth) throw new Error('Google auth not available');

  const sheets = auth.google.sheets({ version: 'v4', auth: auth.jwt });
  const resp = await sheets.spreadsheets.values.get({ spreadsheetId: SPREADSHEET_ID, range: SHEET_RANGE });
  const rows = resp?.data?.values || [];

  const today = new Date();
  const sameDate = (ts) => {
    const d = new Date(ts);
    return d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth() && d.getDate() === today.getDate();
  };

  for (const row of rows) {
    // Expected row format: [employeeId, name, action, timestamp]
    const rEmployee = row[0];
    const rAction = row[2];
    const rTs = row[3];
    if (!rEmployee || !rAction || !rTs) continue;
    if (String(rEmployee) === String(employeeId) && String(rAction) === String(action)) {
      try {
        if (sameDate(rTs)) return true;
      } catch (e) {
        // ignore parse errors and continue
      }
    }
  }
  return false;
}