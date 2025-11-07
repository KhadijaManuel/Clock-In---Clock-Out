<?php
/*
    Model directly queries record_backups table 
    for weekly activity data using the MySQLi Database helper.
*/

require_once __DIR__ . '/../includes/db.php';

class AttendanceModel {
    public static function getWeeklyActivity($employee_id) {
        $db = db(); // Get shared Database instance

        $today = new DateTime();
        $dayOfWeek = (int)$today->format('w'); // 0=Sun, 1=Mon, etc.
        $startOfWeek = clone $today;
        $startOfWeek->modify('-' . ($dayOfWeek == 0 ? 6 : $dayOfWeek - 1) . ' days');
        $start = $startOfWeek->format('Y-m-d');
        $end = (clone $startOfWeek)->modify('+6 days')->format('Y-m-d');

        // Use db()->query() instead of PDO prepare/execute
        $rows = $db->query("
            SELECT date, clockin_time, clockout_time
            FROM record_backups
            WHERE employee_id = ?
              AND date BETWEEN ? AND ?
            ORDER BY date ASC, clockin_time ASC
        ", [$employee_id, $start, $end]);

        // Process per day
        $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
        $weeklyData = [];

        for ($i = 0; $i < 7; $i++) {
            $date = (clone $startOfWeek)->modify("+$i days")->format('Y-m-d');
            $records = array_filter($rows, fn($r) => $r['date'] === $date);
            $hoursWorked = 0;
            $firstIn = '-';
            $lastOut = '-';

            foreach ($records as $rec) {
                if (!empty($rec['clockin_time']) && !empty($rec['clockout_time'])) {
                    $in = new DateTime($rec['clockin_time']);
                    $out = new DateTime($rec['clockout_time']);
                    $hoursWorked += ($out->getTimestamp() - $in->getTimestamp()) / 3600;
                }
                if ($firstIn === '-' && !empty($rec['clockin_time'])) {
                    $firstIn = $rec['clockin_time'];
                }
                if (!empty($rec['clockout_time'])) {
                    $lastOut = $rec['clockout_time'];
                }
            }

            $weeklyData[] = [
                'date' => date("m/d/Y", strtotime($date)),
                'day' => $days[$i],
                'clockIn' => $firstIn ?: '-',
                'clockOut' => $lastOut ?: '-',
                'hours' => round($hoursWorked, 2) . 'h'
            ];
        }

        return $weeklyData;
    }
}
