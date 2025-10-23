require("dotenv").config(); // ✅ Load environment variables
const { google } = require("googleapis");

const auth = new google.auth.GoogleAuth({
  keyFile: process.env.GOOGLE_SERVICE_ACCOUNT_KEY, // ✅ From .env
  scopes: ["https://www.googleapis.com/auth/spreadsheets"],
});

// Write data to the Google Sheet
async function writeToSheet(values) {
  const sheets = google.sheets({ version: "v4", auth });
  const spreadsheetId = process.env.GOOGLE_SHEET_ID;
  const range = "Sheet1!A1"; // Start cell (append below if needed)
  const valueInputOption = "USER_ENTERED";

  const resource = { values };

  try {
    const res = await sheets.spreadsheets.values.update({
      spreadsheetId,
      range,
      valueInputOption,
      resource,
    });
    console.log("✅ Data written to sheet successfully!");
    return res.data;
  } catch (error) {
    console.error("❌ Error writing to sheet:", error);
  }
}

// Read data from the Google Sheet
async function readSheet() {
  const sheets = google.sheets({ version: "v4", auth });
  const spreadsheetId = process.env.GOOGLE_SHEET_ID;
  const range = process.env.GOOGLE_SHEET_RANGE;

  try {
    const response = await sheets.spreadsheets.values.get({
      spreadsheetId,
      range,
    });
    const rows = response.data.values;
    console.log("📖 Data read from sheet:");
    return rows;
  } catch (error) {
    console.error("❌ Error reading sheet:", error);
  }
}

// Demo run (IIFE)
(async () => {
  await writeToSheet([
    ["Timestamp", "Employee ID", "Location"],
    ["james", 33, "Miami"],
    ["liso", 21, "Singapore"],
    ["Juan", 32, "Mexico"],
  ]);

  const data = await readSheet();
  console.table(data);
})();
