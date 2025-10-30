import express from "express";
import { sheetsTest } from "../controllers/adminController.js";

const router = express.Router();

// POST /admin/sheets-test

router.post('/sheets-test', sheetsTest);

export default router;

