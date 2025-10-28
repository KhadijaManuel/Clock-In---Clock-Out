// express logic
console.log("Testing if Digital Attendance Backend is running...");

import express from "express";
import cors from "cors"; //
import dotenv from "dotenv";
import attendanceRoutes from "./routes/attendanceRoutes.js";
import adminRoutes from "./routes/adminRoutes.js";
import { closePool } from "./config/dbPool.js";

dotenv.config();

const app = express();

// Enable CORS for all origins
app.use(cors());

// Parse JSON request bodies
app.use(express.json());

// Attendance Routes
app.use("/api/attendance", attendanceRoutes);

// Admin debug routes (development only)
app.use('/admin', adminRoutes);

// Root route (just for testing)
app.get("/", (req, res) => {
  res.send("Digital Attendance Tracking System Backend is running...");
});

const PORT = process.env.PORT || 1306;
app.listen(PORT, () =>
  console.log(`The server is currently running on port ${PORT}`)
);

// Graceful shutdown
async function shutdown(code = 0) {
  console.log('Shutting down...');
  try {
    await closePool();
    console.log('DB pool closed.');
  } catch (e) {
    console.error('Error closing DB pool:', e?.message || e);
  }
  process.exit(code);
}

process.on('SIGINT', () => shutdown(0));
process.on('SIGTERM', () => shutdown(0));
process.on('uncaughtException', (err) => {
  console.error('Uncaught exception:', err);
  shutdown(1);
});

