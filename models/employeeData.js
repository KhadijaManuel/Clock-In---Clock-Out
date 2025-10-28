// Employee data accessors — reads from a simple JSON "database" in /data
import { readFile } from "fs/promises";
import path from "path";

const employeesFile = path.resolve(new URL(import.meta.url).pathname, "..", "data", "employees.json");

async function readEmployees() {
  try {
    const raw = await readFile(employeesFile, "utf8");
    return JSON.parse(raw);
  } catch (err) {
    // If file missing or corrupt, return empty array (caller should handle not-found)
    return [];
  }
}

// Find an employee by qrCode or id
export async function findEmployee({ qrCode, id }) {
  const list = await readEmployees();
  if (qrCode) {
    const found = list.find((e) => e.qrCode === qrCode);
    if (found) return found;
  }
  if (id) {
    return list.find((e) => e.id === id) || null;
  }
  return null;
}

// Return all employees (async for consistency)
export async function getAllEmployees() {
  return readEmployees();
}
