import express from "express";
import { clockIn, clockOut, getAttendance } from "../controllers/attendanceController.js";

const router = express.Router();

router.post("/clockin", clockIn);
router.post("/clockout", clockOut);
router.get("/logs", getAttendance);

export default router;

