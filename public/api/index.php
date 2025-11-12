<?php
/**
 * Attendance API entry point
 * Handles routes like:
 *   /public/api/attendance/weeklyReport
 *   /public/api/attendance/clockIn
 *   /public/api/attendance/clockOut
 */

header('Content-Type: application/json');

// Enable CORS for development
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AttendanceController.php';

// ---- AUTH CHECK ----
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$employee_id = (int)$_SESSION['employee_id'];

// ---- ROUTE PARSING ----
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', trim($path, '/'));

// Find 'api' position
$apiIndex = array_search('api', $uri);
$action = null;
$resource = null;

if ($apiIndex !== false && isset($uri[$apiIndex + 1])) {
    $resource = $uri[$apiIndex + 1];
    $action = $uri[$apiIndex + 2] ?? null;
}

$method = $_SERVER['REQUEST_METHOD'];

// ---- ROUTE DISPATCH ----
if ($resource === 'attendance' && $action) {
    AttendanceController::handleRequest($action, $method, $employee_id);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Invalid API endpoint"]);
}
