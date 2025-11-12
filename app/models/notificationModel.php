<?php
require_once __DIR__ . '/../includes/db.php';

class NotificationModel {

    public static function getNotificationsForEmployee($employeeId) {
        $conn = db();

        $query = "SELECT notification_id, title, message, date_created, is_broadcast
                  FROM notification_records
                  WHERE employee_id = ? OR is_broadcast = 1
                  ORDER BY date_created DESC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        $stmt->close();
        return $notifications;
    }
}
?>