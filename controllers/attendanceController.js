// Employee clocks in
// export const clockIn = (req, res) => {
//   res.status(200).json({
//     message: "Clock-in feature under development",
//   });
// };

// // Employee clocks out
// export const clockOut = (req, res) => {
//   res.status(200).json({
//     message: "Clock-out feature under development",
//   });
// };





import { getEmployeeByQrOrId } from "../services/attendanceService.js";
import { recordLog } from "../services/persistenceService.js";
import { hasClockInToday, appendLog as appendToSheet } from "../services/sheetService.js";
import { attendanceLogs } from "../models/attendanceData.js";

// simulateScan: local helper that returns a qrCode from the request body
// or a sensible default. Keep this isolated so replacing with a real QR
// scanner in the future is simple.
const simulateScan = (req) => req.body.qrCode || "QR_EMP001";

// clockIn: controller endpoint for employee clock-in.
// Steps:
// 1. obtain qrCode or employeeId from the request
// 2. look up the employee via the attendanceService adapter
// 3. prevent duplicate clock-ins on the same day using the in-memory store
// 4. create a log object and delegate persistence to persistenceService.recordLog
// Note: recordLog is resilient and will not cause the HTTP request to fail
// if downstream systems (DB/Sheets) are temporarily unavailable.
export const clockIn = async (req, res) => {
  const qrCode = simulateScan(req);
  const { employeeId } = req.body;

  if (!qrCode && !employeeId) {
    return res.status(400).json({ message: "QR Code or Employee ID is required" });
  }

  const employee = await getEmployeeByQrOrId({ qrCode, id: employeeId });
  if (!employee) return res.status(404).json({ message: "Employee not found" });

  // If the deployment is configured to only use Sheets (no DB writes),
  // follow the SHEETS_ONLY flow: verify employee exists in DB, check the
  // sheet for an existing clock-in today, and append only to Sheets when
  // allowed by the operator (SHEETS_DO_APPEND=true). This enables safe
  // review before enabling live writes to the shared spreadsheet.
  const SHEETS_ONLY = String(process.env.SHEETS_ONLY || "false").toLowerCase() === "true";
  const SHEETS_DO_APPEND = String(process.env.SHEETS_DO_APPEND || "false").toLowerCase() === "true";

  const log = {
    employeeId: employee.id,
    name: employee.name,
    action: "Clock In",
    timestamp: new Date().toISOString(),
  };

  if (SHEETS_ONLY) {
    // Check spreadsheet for existing clock-in
    try {
      const exists = await hasClockInToday(employee.id, 'Clock In');
      if (exists) return res.status(400).json({ message: 'Already clocked in today' });
    } catch (err) {
      // Propagate meaningful errors to the operator for configuration issues
      return res.status(500).json({ message: 'Sheets check failed', error: String(err?.message || err) });
    }

    if (!SHEETS_DO_APPEND) {
      // Dry-run mode: show what would happen but do not modify the spreadsheet.
      return res.json({ message: 'Dry-run: Sheets append skipped (SHEETS_DO_APPEND not enabled)', log });
    }

    // Append to Sheets (live)
    try {
      await appendToSheet(log);
      // Optionally reflect the log in-memory for local visibility
      attendanceLogs.push(log);
      return res.json({ message: 'Clock-in appended to Sheets', log });
    } catch (err) {
      return res.status(500).json({ message: 'Failed to append to Sheets', error: String(err?.message || err) });
    }
  }

  // Default behavior: DB-backed + Sheets append (existing flow)
  // Use the in-memory attendanceLogs to check for duplicates within the same day.
  const alreadyClockedIn = attendanceLogs.some(
    (logItem) =>
      String(logItem.employeeId) === String(employee.id) &&
      logItem.action === "Clock In" &&
      new Date(logItem.timestamp).toDateString() === new Date().toDateString()
  );

  if (alreadyClockedIn) return res.status(400).json({ message: "Already clocked in today" });

  const result = await recordLog(log);
  if (result && result.inserted === false && result.reason === 'duplicate') {
    return res.status(400).json({ message: 'Already clocked in today' });
  }

  res.json({ message: "Clock-in successful", log });
};

// clockOut: controller endpoint for employee clock-out. Validates that the
// employee clocked in earlier the same day and then records a Clock Out log.
export const clockOut = async (req, res) => {
  const qrCode = simulateScan(req);
  const { employeeId } = req.body;

  if (!qrCode && !employeeId) return res.status(400).json({ message: "QR Code or Employee ID is required" });

  const employee = await getEmployeeByQrOrId({ qrCode, id: employeeId });
  if (!employee) return res.status(404).json({ message: "Employee not found" });

  const hasClockedInToday = attendanceLogs.some(
    (log) =>
      String(log.employeeId) === String(employee.id) &&
      log.action === "Clock In" &&
      new Date(log.timestamp).toDateString() === new Date().toDateString()
  );

  if (!hasClockedInToday) return res.status(400).json({ message: "Cannot clock out before clocking in" });

  const log = {
    employeeId: employee.id,
    name: employee.name,
    action: "Clock Out",
    timestamp: new Date().toISOString(),
  };

  const result = await recordLog(log);
  if (result && result.inserted === false && result.reason === 'duplicate') {
    return res.status(400).json({ message: 'Already clocked out today' });
  }

  res.json({ message: "Clock-out successful", log });
};

// getAttendance: returns the in-memory attendance logs (useful for quick testing)
export const getAttendance = (req, res) => {
  res.json(attendanceLogs);
};
