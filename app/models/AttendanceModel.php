<?php

/*
    Model directly queries record_backups table 
    for weekly activity data using the MySQL Database.
*/

require_once __DIR__ . '/../../includes/db.php';

class AttendanceModel {
    public static function getWeeklyActivity($employee_id) {
        $db = db()->getConnection();

        $today = new DateTime();
        $dayOfWeek = (int)$today->format('w');
        $startOfWeek = clone $today;
        $startOfWeek->modify('-' . ($dayOfWeek == 0 ? 6 : $dayOfWeek - 1) . ' days');
        $start = $startOfWeek->format('Y-m-d');
        $end = (clone $startOfWeek)->modify('+6 days')->format('Y-m-d');

        $query = "
            SELECT date, clockin_time, clockout_time
             FROM record_backups
             WHERE employee_id = ?
               AND date BETWEEN ? AND ?
             ORDER BY date ASC, clockin_time ASC";
        $rows = db()->query($query, [$employee_id, $start, $end]);

        $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
        $weeklyData = [];

        for ($i = 0; $i < 7; $i++) {
            $date = (clone $startOfWeek)->modify("+$i days")->format('Y-m-d');
            $records = array_filter($rows, fn($r) => $r['date'] === $date);
            $hoursWorked = 0;
            $firstIn = '-';
            $lastOut = '-';

            foreach ($records as $rec) {
                if ($rec['clockin_time'] && $rec['clockout_time']) {
                    $in = new DateTime($rec['clockin_time']);
                    $out = new DateTime($rec['clockout_time']);
                    $hoursWorked += ($out->getTimestamp() - $in->getTimestamp()) / 3600;
                }
                if ($firstIn === '-') $firstIn = $rec['clockin_time'];
                $lastOut = $rec['clockout_time'];
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
    
    public static function clockIn($employee_id) {
        $db = db();
        $today = date('Y-m-d H:i:s');
        $dateOnly = date('Y-m-d');

        // Check if already clocked in today - STRICTER CHECK
        $existing = $db->query(
            "SELECT record_id FROM record_backups WHERE employee_id = ? AND date = ?",
            [$employee_id, $dateOnly]
        );

        if (!empty($existing)) {
            return ['status' => 'error', 'message' => 'You have already clocked in today'];
        }

        // Get employee name for the record
        $employee = $db->query(
            "SELECT first_name, last_name FROM employees WHERE employee_id = ?",
            [$employee_id]
        );
        
        $full_name = '';
        if (!empty($employee)) {
            $full_name = $employee[0]['first_name'] . ' ' . $employee[0]['last_name'];
        }

        $db->execute(
            "INSERT INTO record_backups (employee_id, full_name, date, clockin_time) VALUES (?, ?, ?, ?)",
            [$employee_id, $full_name, $dateOnly, $today]
        );

        return ['status' => 'success', 'message' => 'Clocked in successfully at ' . date('H:i:s')];
    }

    public static function clockOut($employee_id) {
        $db = db();
        $today = date('Y-m-d H:i:s');
        $dateOnly = date('Y-m-d');

        // Check if user has clocked in today but not out yet
        $activeRecord = $db->query(
            "SELECT record_id FROM record_backups WHERE employee_id = ? AND date = ? AND clockout_time IS NULL",
            [$employee_id, $dateOnly]
        );

        if (empty($activeRecord)) {
            // Check if user has already clocked out today
            $completedRecord = $db->query(
                "SELECT record_id FROM record_backups WHERE employee_id = ? AND date = ? AND clockout_time IS NOT NULL",
                [$employee_id, $dateOnly]
            );
            
            if (!empty($completedRecord)) {
                return ['status' => 'error', 'message' => 'You have already clocked out today'];
            } else {
                return ['status' => 'error', 'message' => 'You need to clock in first before clocking out'];
            }
        }

        $result = $db->execute(
            "UPDATE record_backups 
             SET clockout_time = ?
             WHERE employee_id = ? AND date = ? AND clockout_time IS NULL
             LIMIT 1",
            [$today, $employee_id, $dateOnly]
        );

        if ($result === true) {
            return ['status' => 'success', 'message' => 'Clocked out successfully at ' . date('H:i:s')];
        } else {
            return ['status' => 'error', 'message' => 'Error clocking out'];
        }
    }
    
}
