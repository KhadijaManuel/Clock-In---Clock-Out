<?php
require_once __DIR__ . '/../models/notificationModel.php';

class NotificationController {

    public static function handleRequest($action, $method) {
        if ($action === 'getNotifications' && $method === 'GET') {
            self::getNotifications();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Invalid route']);
        }
    }

    public static function getNotifications() {
        header('Content-Type: application/json');
        $employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

        if ($employeeId <= 0) {
            echo json_encode(['error' => 'Invalid employee_id']);
            return;
        }

        $notifications = NotificationModel::getNotificationsForEmployee($employeeId);
        echo json_encode(['notifications' => $notifications]);
    }
}
?>
