import express from "express";
import { clockIn, clockOut, getAttendance } from "../controllers/attendanceController.js";

const router = express.Router();

// Explicit clock-in and clock-out endpoints
router.post("/clockin", clockIn);
router.post("/clockout", clockOut);

// Backwards-compatible single POST endpoint which inspects `action` in body
router.post("/", (req, res) => {
  const action = req.body.action;
  if (action === "Clock In") return clockIn(req, res);
  if (action === "Clock Out") return clockOut(req, res);
  res.status(400).json({ message: "Invalid action. Use 'Clock In' or 'Clock Out'" });
});

// Simple echo endpoint for debugging route reachability
router.post('/echo', (req, res) => {
  res.json({ message: 'echo', body: req.body });
});

// Gets employee attendance records
router.get("/:employeeId", async (req, res) => {
  const numericId = parseInt(req.params.employeeId, 10);
  if (isNaN(numericId)) {
    return res.status(400).json({ message: "Employee ID must be a number" });
  }
  req.query.employeeId = numericId;
  return getAttendance(req, res);
});

// Get all attendance logs
router.get("/logs", getAttendance);

export default router;


