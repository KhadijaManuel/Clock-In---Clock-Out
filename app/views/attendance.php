<?php
// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

// Fix the employee_id error
if (!isset($_SESSION['employee_id'])) {
    $_SESSION['employee_id'] = 1; // Set default employee ID for testing
}
$employee_id = $_SESSION['employee_id'];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';
require_once __DIR__ . '/../includes/db.php';

// Attendance Page - PHP version
// Sample notifications (same as Vue setup)
$notifications = [
    ["title" => "System Update Completed", "message" => "The attendance system has been successfully updated to version 2.3.", "time" => "2 mins ago", "read" => false],
    ["title" => "Clock Out Reminder", "message" => "You haven't clocked out yet. Please remember to clock out before leaving.", "time" => "5 mins ago", "read" => false],
    ["title" => "Holiday Notice", "message" => "The office will be closed on 16 December for a public holiday.", "time" => "10:24 am", "read" => true],
    ["title" => "Clock-In Successful", "message" => "You clocked in successfully at 08:01 AM. Have a productive day!", "time" => "Yesterday", "read" => true],
    ["title" => "Attendance Approved", "message" => "Your attendance record for 28 October has been verified by the admin.", "time" => "Yesterday", "read" => false]
];

// Weekly Activities generator
function generateWeeklyData()
{
    $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
    $today = new DateTime();
    $startOfWeek = clone $today;
    $dayNum = (int) $today->format("w");
    $startOfWeek->modify('-' . ($dayNum == 0 ? 6 : $dayNum - 1) . ' days');
    $data = [];
    for ($i = 0; $i < 7; $i++) {
        $date = clone $startOfWeek;
        $date->modify("+$i days");
        $clockInHour = rand(7, 9);
        $clockInMinute = rand(0, 59);
        $clockOutHour = rand(16, 18);
        $clockOutMinute = rand(0, 59);
        $clockIn = str_pad($clockInHour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($clockInMinute, 2, '0', STR_PAD_LEFT);
        $clockOut = str_pad($clockOutHour, 2, '0', STR_PAD_LEFT) . ':' . str_pad($clockOutMinute, 2, '0', STR_PAD_LEFT);
        $hoursWorkedCalc = $clockOutHour - $clockInHour + ($clockOutMinute - $clockInMinute) / 60;
        $data[] = [
            "date" => $date->format("m/d/Y"),
            "day" => $days[$i],
            "clockIn" => $clockIn,
            "clockOut" => $clockOut,
            "hours" => round($hoursWorkedCalc, 1) . 'h'
        ];
    }
    return $data;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --header-bg: #06C3A7;
            --button-text: #FFFFFF;
            --bg-color: #EBFFFD;
            --panel-bg: #FFFFFF;
            --card-bg: #F9F9F9;
            --text-color: #064E44;
            --subtext-color: #4B6B66;
            --accent-color: #06C3A7;
            --button-text: #FFFFFF;
            --input-bg: rgba(255, 255, 255, 0.95);
            --border-color: rgba(6, 195, 167, 0.3);
        }

        [data-theme="dark"] {
            --header-bg: #243238;
            --button-text: #EBFFFD;
            --bg-color: #1F292E;
            --bg-card: #242424e8;
            --panel-bg: #2f2f2fff;
            --text-color: #EBFFFD;
            --subtext-color: #C8D5D4;
            --accent-color: #06C3A7;
            --button-text: #EBFFFD;
            --input-bg: #2C3B41;
            --border-color: rgba(235, 255, 253, 0.2);
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Inter", sans-serif;
            color: var(--text-color);
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        button {
            transition: all 0.3s ease;
        }

        .attendance-dashboard {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            padding: 1rem;
        }

        h1 {
            margin: 1rem;
            color: var(--accent-color);
            font-size: xx-large;
            font-weight: 900;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            align-items: stretch;
            width: 100%;
        }

        .card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.47);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            width: 100%;
            box-sizing: border-box;
            overflow: hidden;
        }

        .card h2 {
            margin: 0 0 1rem 0;
            color: var(--accent-color);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .timer-container {
            width: 100%;
            margin: 0 auto 1rem;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .svg-container {
            position: relative;
            width: 100%;
            max-width: 280px;
            margin: 0 auto;
            aspect-ratio: 1 / 1;
        }

        .progress-ring {
            width: 100%;
            height: auto;
            display: block;
        }

        .progress-ring-background {
            stroke: var(--border-color);
        }

        .progress-ring-circle {
            stroke: var(--accent-color);
            transition: stroke-dashoffset 1s linear;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .timer-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            width: 80%;
            max-width: 200px;
        }

        .timer-display {
            font-size: clamp(0.9rem, 3vw, 1.1rem);
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .clock-button {
            background-color: var(--accent-color);
            color: white;
            border: none;
            border-radius: 24px;
            padding: 10px 20px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(6, 195, 167, 0.3);
            min-width: 120px;
        }

        .clock-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 195, 167, 0.4);
        }

        .clock-button:active {
            transform: translateY(0);
        }

        .clock-button.clocked-in {
            background-color: #ff5c5c;
            box-shadow: 0 2px 8px rgba(255, 92, 92, 0.3);
        }

        .clock-button.clocked-in:hover {
            box-shadow: 0 4px 12px rgba(255, 92, 92, 0.4);
        }

        .activity-card {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            background: var(--bg-card);
        }

        .table-container {
            flex-grow: 1;
            overflow: auto;
            max-height: 250px;
        }

        .activity-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
            font-size: clamp(0.7rem, 2vw, 0.8rem);
            min-height: 95px;
            background: var(--bg-card);
        }

        .activity-table th,
        .activity-table td {
            padding: clamp(0.4rem, 1.5vw, 0.6rem);
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            color: #06C3A7;
        }

        .activity-table th {
            background-color: var(--bg-card);
            font-weight: 600;
            color: #06C3A7;
        }

        .today-row {
            background-color: rgba(6, 195, 167, 0.1);
            font-weight: 600;
        }

        .notification-panel-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .notification-panel {
            width: 100%;
            max-width: calc(100%);
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-height: 400px;
            overflow-y: auto;
            padding: 3rem;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
        }

        .panel-header h4 {
            font-size: 1.8rem;
            font-weight: 500;
            margin: 0;
            padding-bottom: 8px;
            position: relative;
            color: #06C3A7;
        }

        .panel-header h4::after {
            content: '';
            display: block;
            width: 100%;
            height: 1px;
            background-color: var(--accent-color);
            border-radius: 2px;
            margin-top: 12px;
        }

        .tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .tabs button {
            background: transparent;
            border: none;
            padding: 6px 0;
            font-size: 1rem;
            color: #06C3A7;
            cursor: pointer;
            position: relative;
        }

        .tabs button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 2px;
            background-color: var(--accent-color);
        }

        .notification-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
            color: #06C3A7;
        }

        .notification-item.is-unread {
            background-color: rgba(6, 195, 167, 0.1);
            border-radius: 10px;
        }

        .icon-wrap {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent-color);
            color: var(--button-text);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .title {
            font-weight: 600;
            margin: 0;
            color: #06C3A7;
        }

        .message {
            margin: 2px 0 0 0;
            color: var(--subtext-color);
        }

        .time {
            font-size: 0.8rem;
            color: var(--subtext-color);
        }

        .unread-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ff5c5c;
        }

        .empty {
            text-align: center;
            margin-top: 10px;
            color: var(--subtext-color);
        }

        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>

<body>
    <div class="attendance-dashboard">
        <h1>Attendance</h1>
        <main class="main-content">
            <!-- Cards Grid -->
            <div class="cards-grid">
                <!-- Left Card - Timer -->
                <div class="card timer-card">
                    <h2>Hours Worked</h2>
                    <div class="timer-container">
                        <div class="svg-container">
                            <svg class="progress-ring" viewBox="0 0 280 280">
                                <circle class="progress-ring-background" stroke="#E0E0E0" stroke-width="15"
                                    fill="transparent" r="125" cx="140" cy="140" />
                                <circle class="progress-ring-circle" stroke="#06C3A7" stroke-width="15"
                                    fill="transparent" r="125" cx="140" cy="140" id="progress-circle" />
                            </svg>
                            <div class="timer-content">
                                <div class="timer-display" id="timer-display">00h 00m 00s</div>
                                <button class="clock-button" id="clock-button">Clock In</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Right Card - Weekly Activity -->
                <div class="card activity-card">
                    <h2>Weekly Activity</h2>
                    <div class="table-container">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th>Hours</th>
                                </tr>
                            </thead>
                            <tbody id="activity-table-body">
                                <?php 
                                // Get fresh data from database every time
                                try {
                                    $db = Database::getInstance();
                                    
                                    // Get current week dates
                                    $today = new DateTime();
                                    $startOfWeek = clone $today;
                                    $startOfWeek->modify('Monday this week');
                                    $endOfWeek = clone $startOfWeek;
                                    $endOfWeek->modify('+6 days');
                                    
                                    // Get fresh records for this week - no caching
                                    $sql = "SELECT date, clockin_time, clockout_time 
                                            FROM record_backups 
                                            WHERE employee_id = ? 
                                            AND date BETWEEN ? AND ?
                                            ORDER BY date DESC";
                                    
                                    $records = $db->query($sql, [
                                        $employee_id, 
                                        $startOfWeek->format('Y-m-d'), 
                                        $endOfWeek->format('Y-m-d')
                                    ]);
                                    
                                    $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                                    
                                    // Generate rows for each day of the week
                                    for ($i = 0; $i < 7; $i++) {
                                        $currentDate = clone $startOfWeek;
                                        $currentDate->modify("+$i days");
                                        $dateFormatted = $currentDate->format('m/d/Y');
                                        $dateDbFormat = $currentDate->format('Y-m-d');
                                        $dayName = $days[$i];
                                        
                                        // Check if we have a record for this date
                                        $record = null;
                                        foreach ($records as $r) {
                                            if ($r['date'] == $dateDbFormat) {
                                                $record = $r;
                                                break;
                                            }
                                        }
                                        
                                        $isToday = $dateFormatted === date('m/d/Y');
                                        $clockIn = '--:--';
                                        $clockOut = '--:--';
                                        $hours = '0h';
                                        
                                        if ($record) {
                                            if ($record['clockin_time']) {
                                                $clockIn = date('H:i', strtotime($record['clockin_time']));
                                            }
                                            if ($record['clockout_time']) {
                                                $clockOut = date('H:i', strtotime($record['clockout_time']));
                                                
                                                // Calculate hours worked
                                                if ($record['clockin_time']) {
                                                    $start = DateTime::createFromFormat('H:i:s', $record['clockin_time']);
                                                    $end = DateTime::createFromFormat('H:i:s', $record['clockout_time']);
                                                    $diff = $end->diff($start);
                                                    $totalHours = $diff->h + ($diff->i / 60);
                                                    $hours = round($totalHours, 1) . 'h';
                                                }
                                            }
                                        }
                                        
                                        echo "<tr class='" . ($isToday ? 'today-row' : '') . "'>";
                                        echo "<td>{$dateFormatted}</td>";
                                        echo "<td>{$dayName}</td>";
                                        echo "<td>{$clockIn}</td>";
                                        echo "<td>{$clockOut}</td>";
                                        echo "<td>{$hours}</td>";
                                        echo "</tr>";
                                    }
                                    
                                } catch (Exception $e) {
                                    // Fallback to sample data if database fails
                                    $sampleData = generateWeeklyData();
                                    foreach ($sampleData as $activity) {
                                        echo "<tr class='" . ($activity['date'] === date('m/d/Y') ? 'today-row' : '') . "'>";
                                        echo "<td>{$activity['date']}</td>";
                                        echo "<td>{$activity['day']}</td>";
                                        echo "<td>{$activity['clockIn']}</td>";
                                        echo "<td>{$activity['clockOut']}</td>";
                                        echo "<td>{$activity['hours']}</td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Notification Panel spanning the grid width -->
            <div class="notification-panel-wrapper">
                <div class="notification-panel">
                    <div class="panel-header">
                        <h4>Notifications</h4>
                    </div>
                    <div class="tabs">
                        <button class="active" onclick="showTab('All')">All</button>
                        <button onclick="showTab('Read')">Read</button>
                        <button onclick="showTab('Unread')">Unread</button>
                    </div>
                    <ul class="notification-list" id="notification-list">
                        <?php foreach ($notifications as $note): ?>
                            <li class="notification-item <?= $note['read'] ? '' : 'is-unread' ?>" onclick="markRead(this)">
                                <div class="icon-wrap"><i class="fas fa-bell"></i></div>
                                <div class="details">
                                    <p class="title"><?= $note['title'] ?></p>
                                    <p class="message"><?= $note['message'] ?></p>
                                </div>
                                <span class="time"><?= $note['time'] ?></span>
                                <?php if (!$note['read']): ?><span class="unread-dot"></span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Timer JS
        let secondsWorked = 0;
        let isClockedIn = false;
        const timerDisplay = document.getElementById('timer-display');
        const progressCircle = document.getElementById('progress-circle');
        const clockButton = document.getElementById('clock-button');

        // Check current clock status on page load
        function checkClockStatus() {
            fetch('../../public/api/clock_handler.php?action=check_status')
            .then(response => response.json())
            .then(data => {
                if (data.clocked_in) {
                    isClockedIn = true;
                    clockButton.textContent = 'Clock Out';
                    clockButton.classList.add('clocked-in');
                    
                    // Calculate elapsed time since clock in
                    if (data.clock_in_time) {
                        const clockInTime = new Date('<?= date('Y-m-d') ?>T' + data.clock_in_time);
                        const now = new Date();
                        secondsWorked = Math.floor((now - clockInTime) / 1000);
                    }
                }
            })
            .catch(error => {
                console.log('Could not check clock status');
            });
        }

        // Initialize clock status
        checkClockStatus();

        function updateTimer() {
            const h = Math.floor(secondsWorked / 3600);
            const m = Math.floor((secondsWorked % 3600) / 60);
            const s = secondsWorked % 60;
            timerDisplay.innerText = `${String(h).padStart(2, '0')}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;

            const circumference = 2 * Math.PI * 125;
            const totalSeconds = 8 * 3600;
            const progress = Math.min((secondsWorked / totalSeconds) * circumference, circumference);
            progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
            progressCircle.style.strokeDashoffset = circumference - progress;

            if (isClockedIn) {
                secondsWorked++;
            }
        }

        // SIMPLE Clock in/out functionality
        clockButton.addEventListener('click', function () {
            // Prevent multiple clicks
            if (clockButton.classList.contains('loading')) return;
            
            clockButton.classList.add('loading');
            clockButton.disabled = true;

            if (!isClockedIn) {
                // Clock in
                const currentTime = new Date();
                
                fetch('../../public/api/clock_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=clock_in&clock_time=${currentTime.toISOString()}`
                })
                .then(response => response.json())
                .then(data => {
                    clockButton.classList.remove('loading');
                    clockButton.disabled = false;
                    
                    if (data.success) {
                        alert('Clocked in successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    clockButton.classList.remove('loading');
                    clockButton.disabled = false;
                    alert('Error clocking in');
                });

            } else {
                // Clock out
                const clockOutTime = new Date();
                
                fetch('../../public/api/clock_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=clock_out&clock_time=${clockOutTime.toISOString()}`
                })
                .then(response => response.json())
                .then(data => {
                    clockButton.classList.remove('loading');
                    clockButton.disabled = false;
                    
                    if (data.success) {
                        alert('Clocked out successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    clockButton.classList.remove('loading');
                    clockButton.disabled = false;
                    alert('Error clocking out');
                });
            }
        });

        // Start the timer
        setInterval(updateTimer, 1000);
    </script>
</body>

</html>
