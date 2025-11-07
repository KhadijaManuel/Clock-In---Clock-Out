<?php

/* Handles business logic
    connects model and the API
*/

require_once __DIR__ . '/../models/AttendanceModel.php';

class AttendanceController {
    public static function getWeeklyReport($employee_id) {
        return AttendanceModel::getWeeklyActivity($employee_id);
    }

    // For API endpoint (JSON response)
    public static function handleRequest($action, $method, $employee_id) {
        if ($action === 'weeklyReport' && $method === 'GET') {
            $data = self::getWeeklyReport($employee_id);
            header('Content-Type: application/json');
            echo json_encode($data);
        }
    }
}
