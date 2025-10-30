// sheetService.js
// Handles attendance logging to Google Sheets for clock-in/out

import { readFile } from "fs/promises";
import { google } from "googleapis";

const USE_SHEETS = process.env.USE_SHEETS === "true";
const SPREADSHEET_ID = process.env.SHEET_ID || process.env.GOOGLE_SHEETS_SPREADSHEET_ID;
const SHEET_RANGE = process.env.GOOGLE_SHEETS_RANGE || "Attendance!A:G";

// --- Google Auth Setup ---
async function getAuthClient() {
  let keyJson = null;
  if (process.env.GOOGLE_SERVICE_ACCOUNT_JSON) {
    keyJson = JSON.parse(process.env.GOOGLE_SERVICE_ACCOUNT_JSON);
  } else if (process.env.GOOGLE_APPLICATION_CREDENTIALS) {
    const raw = await readFile(process.env.GOOGLE_APPLICATION_CREDENTIALS, "utf8");
    keyJson = JSON.parse(raw);
  }

  if (!keyJson) throw new Error("Missing Google service account credentials.");

  const scopes = ["https://www.googleapis.com/auth/spreadsheets"];
  const jwt = new google.auth.JWT(keyJson.client_email, null, keyJson.private_key, scopes);
  await jwt.authorize();
  return google.sheets({ version: "v4", auth: jwt });
}

// --- Helper: Read all rows from sheet ---
async function readSheet() {
  const sheets = await getAuthClient();
  const res = await sheets.spreadsheets.values.get({
    spreadsheetId: SPREADSHEET_ID,
    range: SHEET_RANGE,
  });
  return res.data.values || [];
}

// --- Helper: Convert JS Date → Sheet Date format (dd/mm/yyyy) ---
function formatDate(date) {
  return date.toLocaleDateString("en-GB");
}

// --- Check if employee already clocked in today ---
export async function hasClockedInToday(employeeId) {
  const rows = await readSheet();
  const today = formatDate(new Date());

  return rows.some(
    (row) =>
      String(row[0]) === String(employeeId) &&
      row[2] !== "—" &&
      row[2] !== "" &&
      row[6] === today // Date column (7th)
  );
}

// --- Append or update a Clock-In ---
export async function appendClockIn({ employeeId, name }) {
  if (!USE_SHEETS) throw new Error("Google Sheets integration disabled.");
  const sheets = await getAuthClient();
  const rows = await readSheet();

  const today = formatDate(new Date());
  const currentTime = new Date().toTimeString().split(" ")[0];
  const status = currentTime <= "09:00:00" ? "OnTime" : "Late";

  // Check if employee already has a record today
  const existingIndex = rows.findIndex(
    (r) => String(r[0]) === String(employeeId) && r[6] === today
  );

  if (existingIndex !== -1 && rows[existingIndex][2] !== "—") {
    return { message: "Already clocked in today" };
  }

  if (existingIndex === -1) {
    // Append new record
    const values = [
      [employeeId, name, currentTime, "—", status, "Work", today],
    ];
    await sheets.spreadsheets.values.append({
      spreadsheetId: SPREADSHEET_ID,
      range: SHEET_RANGE,
      valueInputOption: "USER_ENTERED",
      resource: { values },
    });
  } else {
    // Update CheckIn for existing row
    const range = `Attendance!C${existingIndex + 1}`; // Column C = CheckIn
    await sheets.spreadsheets.values.update({
      spreadsheetId: SPREADSHEET_ID,
      range,
      valueInputOption: "USER_ENTERED",
      resource: { values: [[currentTime]] },
    });
  }

  return { message: "Clock-in successful", employeeId, name, time: currentTime };
}

// --- Append or update a Clock-Out ---
export async function appendClockOut({ employeeId }) {
  const sheets = await getAuthClient();
  const rows = await readSheet();
  const today = formatDate(new Date());
  const currentTime = new Date().toTimeString().split(" ")[0];

  const index = rows.findIndex(
    (r) => String(r[0]) === String(employeeId) && r[6] === today
  );

  if (index === -1) {
    return { message: "Cannot clock out before clocking in" };
  }

  const range = `Attendance!D${index + 1}`; // Column D = CheckOut
  await sheets.spreadsheets.values.update({
    spreadsheetId: SPREADSHEET_ID,
    range,
    valueInputOption: "USER_ENTERED",
    resource: { values: [[currentTime]] },
  });

  return { message: "Clock-out successful", employeeId, time: currentTime };
}