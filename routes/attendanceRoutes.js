// routes/attendanceRoutes.js
import express from "express";
import { clockIn, clockOut, getAttendance } from "../controllers/attendanceController.js";

const router = express.Router();

/**
 * @route   POST /api/attendance/clockin
 * @desc    Employee clock-in endpoint
 * @body    { employeeId: number }
 */
router.post("/clockin", clockIn);

/**
 * @route   POST /api/attendance/clockout
 * @desc    Employee clock-out endpoint
 * @body    { employeeId: number }
 */
router.post("/clockout", clockOut);

/**
 * @route   GET /api/attendance/logs
 * @desc    Fetch all attendance data (for admin/testing)
 */
router.get("/logs", getAttendance);

/**
 * @route   POST /api/attendance
 * @desc    Backwards-compatible endpoint (auto-detects action)
 * @body    { employeeId: number, action: "Clock In" | "Clock Out" }
 */
router.post("/", (req, res) => {
  const { action } = req.body;
  if (action === "Clock In") return clockIn(req, res);
  if (action === "Clock Out") return clockOut(req, res);
  return res.status(400).json({ message: "Invalid action. Use 'Clock In' or 'Clock Out'." });
});

export default router;





