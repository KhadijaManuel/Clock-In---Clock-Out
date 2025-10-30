import { getPool } from "../config/dbPool.js";
import { appendClockIn, appendClockOut, hasClockedInToday } from "../services/sheetService.js";

// checks employee existence in MySQL
async function findEmployeeById(employeeId) {
  try {
    const pool = await getPool();
    const [rows] = await pool.query("SELECT * FROM employees WHERE employee_id = ?", [employeeId]);
    return rows.length ? rows[0] : null;
  } catch (err) {
    console.error("DB error while verifying employee:", err.message || err);
    return null;
  }
}

// CLOCK-IN HANDLER

export const clockIn = async (req, res) => {
  const { employeeId } = req.body;

  if (!employeeId) {
    return res.status(400).json({ message: "Employee ID is required" });
  }

  try {
    // Verifies employee exists in MySQL
    const employee = await findEmployeeById(employeeId);
    if (!employee) {
      return res.status(404).json({ message: "Employee not found in database" });
    }

    // Checks if already clocked in today in Google Sheets
    const alreadyClockedIn = await hasClockedInToday(employeeId);
    if (alreadyClockedIn) {
      return res.status(400).json({ message: "Already clocked in today" });
    }

    // Appends new clock-in entry to Google Sheets
    const result = await appendClockIn({
      employeeId,
      name: `${employee.first_name} ${employee.last_name}`.trim(),
    });

    // Returns success response
    return res.json({ message: "Clock-in successful", result });
  } catch (err) {
    console.error("Clock-in error:", err.message || err);
    return res.status(500).json({ message: "Failed to clock in", error: err.message });
  }
};

// CLOCK-OUT HANDLER

export const clockOut = async (req, res) => {
  const { employeeId } = req.body;

  if (!employeeId) {
    return res.status(400).json({ message: "Employee ID is required" });
  }

  try {
    // Verifies employee exists
    const employee = await findEmployeeById(employeeId);
    if (!employee) {
      return res.status(404).json({ message: "Employee not found in database" });
    }

    // Appends or update clock-out info in Sheets
    const result = await appendClockOut({ employeeId });

    // Returns success message
    return res.json(result);
  } catch (err) {
    console.error("Clock-out error:", err.message || err);
    return res.status(500).json({ message: "Failed to clock out", error: err.message });
  }
};


// FETCHES ALL ATTENDANCE (for testing)

export const getAttendance = async (req, res) => {
  try {
    const pool = await getPool();
    const [rows] = await pool.query("SELECT * FROM employees");
    return res.json(rows);
  } catch (err) {
    console.error("Get attendance error:", err.message || err);
    return res.status(500).json({ message: "Error fetching attendance" });
  }
};

