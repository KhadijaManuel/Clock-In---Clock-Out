<?php

/*
    Model directly queries record_backups table 
    for weekly activity data using the MySQL Database.
*/

require_once __DIR__ . '/../includes/db.php';

class AttendanceModel {
    public static function getWeeklyActivity($employee_id) {
        $db = db()->getConnection();

        // Get current week's Monday and Sunday
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));

        $stmt = $db->prepare("
            SELECT 
                date,
                DAYNAME(date) AS day,
                clockin_time AS clockIn,
                clockout_time AS clockOut,
                ROUND(TIMESTAMPDIFF(MINUTE, clockin_time, clockout_time)/60, 1) AS hours
            FROM record_backups
            WHERE employee_id = ? 
            AND date BETWEEN ? AND ?
            ORDER BY date ASC
        ");
        $stmt->bind_param('iss', $employee_id, $monday, $sunday);
        $stmt->execute();

        $result = $stmt->get_result();
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }

        $stmt->close();
        return $records;
    }
}
