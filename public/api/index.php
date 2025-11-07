<?php

/* 
    Attendance API entry point
 */

require_once '../../app/controllers/AttendanceController.php';

session_start();
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$employee_id = $_SESSION['employee_id'];

$uri = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$action = $uri[count($uri) - 1]; // e.g., weeklyReport
$method = $_SERVER['REQUEST_METHOD'];

// Example: /attendance_dashboard/public/api/attendance/weeklyReport
if (isset($uri[3]) && $uri[3] === 'attendance') {
    AttendanceController::handleRequest($action, $method, $employee_id);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Invalid API endpoint"]);
}
