import express from "express";
import { sheetsTest } from "../controllers/adminController.js";

const router = express.Router();

// POST /admin/sheets-test
// Body (optional): { employeeId, name, action }
router.post('/sheets-test', sheetsTest);

export default router;
