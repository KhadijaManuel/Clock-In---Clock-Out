<?php

/* Handles business logic
    connects model and the API
*/

require_once __DIR__ . '/../models/AttendanceModel.php';

class AttendanceController {
    public static function getWeeklyReport($employee_id) {
        return AttendanceModel::getWeeklyActivity($employee_id);
    }

    public static function clockIn($employee_id) {
        return AttendanceModel::clockIn($employee_id);
    }

    public static function clockOut($employee_id) {
        return AttendanceModel::clockOut($employee_id);
    }

    // API Endpoint handler
    public static function handleRequest($action, $method, $employee_id) {
        header('Content-Type: application/json');

        switch ($action) {
            case 'weeklyReport':
                if ($method === 'GET') {
                    echo json_encode(self::getWeeklyReport($employee_id));
                }
                break;

            case 'clockIn':
                if ($method === 'POST') {
                    echo json_encode(self::clockIn($employee_id));
                }
                break;

            case 'clockOut':
                if ($method === 'POST') {
                    echo json_encode(self::clockOut($employee_id));
                }
                break;

            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    }
}

