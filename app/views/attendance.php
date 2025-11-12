<?php

session_start();

// Check login status FIRST
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}
// Attendance Page - PHP version
// Sample notifications (same as Vue setup)
$employee_id = $_SESSION['employee_id'];
$employee_name = $_SESSION['name'];

// DEBUG: Check what employee ID we have
error_log("Logged in as employee_id: " . $employee_id . ", name: " . $employee_name);

// Get notifications from backend
$apiUrl = "http://localhost/php-notif/public/api/index.php/notifications/getNotifications?employee_id=" . $employee_id;
$response = @file_get_contents($apiUrl);
if ($response === FALSE) {
    $notifications = [];
} else {
    $data = json_decode($response, true);
    $notifications = $data['notifications'] ?? [];
}

// DEBUG: Check what notifications we received
error_log("Received " . count($notifications) . " notifications");
foreach ($notifications as $note) {
    error_log("Notification: " . $note['title'] . " - Employee ID: " . ($note['employee_id'] ?? 'NULL'));
}

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
$weeklyActivities = generateWeeklyData();

// Prepare user data for header
$user = [
    'firstName' => $employee_name,
    'lastName' => '',
    'email' => $_SESSION['email'] ?? '',
    'contactNo' => $_SESSION['contactNo'] ?? '',
    'department' => $_SESSION['department'] ?? 'Administration', 
    'position' => $_SESSION['position'] ?? 'Employee',
    'employeeId' => $employee_id
];

include  __DIR__ . '/../includes/header.php';

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

        /* :crescent_moon: Dark Mode */
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

        /* Apply globally */
        body {
            margin: 0;
            padding: 0;
            font-family: "Inter", sans-serif;
            /* background-color: var(--bg-color); */
            color: var(--text-color);
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        button {
            transition: all 0.3s ease;
        }

        /* attendance styles */
        .attendance-dashboard {
            min-height: 100vh;
            /* background-color: var(--bg-color); */
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

        /* :white_check_mark: FIXED GRID */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            align-items: stretch;
            width: 100%;
        }

        /* :white_check_mark: FIXED CARD */
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

        /* Card headings */
        .card h2 {
            margin: 0 0 1rem 0;
            color: var(--accent-color);
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Timer Section */
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

        /* Clock button styles */
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

        /* Activity table */
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
            /* :white_check_mark: optional: keeps long tables scrollable */
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

        .activity-table tbody tr:hover {
            /* background-color: var(--input-bg); */
        }

        /* Highlight today's row */
        .today-row {
            background-color: rgba(6, 195, 167, 0.1);
            font-weight: 600;
        }

        /* Notification Panel */
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
                                <?php foreach ($weeklyActivities as $activity): ?>
                                    <tr class="<?= $activity['date'] === date('m/d/Y') ? 'today-row' : '' ?>">
                                        <td><?= $activity['date'] ?></td>
                                        <td><?= $activity['day'] ?></td>
                                        <td><?= $activity['clockIn'] ?></td>
                                        <td><?= $activity['clockOut'] ?></td>
                                        <td><?= $activity['hours'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
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
        <li class="notification-item <?= isset($note['read']) && $note['read'] ? '' : 'is-unread' ?>" onclick="markRead(this)">
            <div class="icon-wrap">
                <i class="fas <?= $note['is_broadcast'] ? 'fa-bullhorn' : 'fa-bell' ?>"></i>
            </div>
            <div class="details">
                <p class="title">
                    <?= $note['title'] ?>
                    <?php if ($note['is_broadcast']): ?>
                        <span style="color: var(--accent-color); font-size: 0.8rem; margin-left: 0.5rem;">
                            <i class="fas fa-globe"></i> Broadcast
                        </span>
                    <?php endif; ?>
                </p>
                <p class="message"><?= $note['message'] ?></p>
            </div>
         <span class="time">
    <?php
    if (isset($note['date_created'])) {
        // Convert MySQL datetime to human-readable format
        $timestamp = strtotime($note['date_created']);
        $current_time = time();
        $diff = $current_time - $timestamp;
        
        if ($diff < 60) {
            echo 'Just now';
        } elseif ($diff < 3600) {
            echo floor($diff / 60) . ' mins ago';
        } elseif ($diff < 86400) {
            echo floor($diff / 3600) . ' hours ago';
        } else {
            echo date('M j, g:i A', $timestamp);
        }
    } else {
        echo 'Recently';
    }
    ?>
</span>
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
        let clockInTime = null;
        const timerDisplay = document.getElementById('timer-display');
        const progressCircle = document.getElementById('progress-circle');
        const clockButton = document.getElementById('clock-button');
        const activityTableBody = document.getElementById('activity-table-body');

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

        // Clock in/out functionality
        clockButton.addEventListener('click', function () {
            if (!isClockedIn) {
                // Clock in
                isClockedIn = true;
                clockInTime = new Date();
                clockButton.textContent = 'Clock Out';
                clockButton.classList.add('clocked-in');

                // Update today's row in the table
                updateTodayRow(clockInTime);

                // Add notification
                addNotification('Clock-In Successful', `You clocked in successfully at ${formatTime(clockInTime)}. Have a productive day!`);

            } else {
                // Clock out
                isClockedIn = false;
                clockButton.textContent = 'Clock In';
                clockButton.classList.remove('clocked-in');

                // Reset the timer
                secondsWorked = 0;
                updateTimer();

                const clockOutTime = new Date();

                // Update today's row with clock out time
                updateTodayRow(clockInTime, clockOutTime);

                // Add notification
                addNotification('Clock-Out Successful', `You clocked out successfully at ${formatTime(clockOutTime)}. See you tomorrow!`);
            }
        });

        function updateTodayRow(clockIn, clockOut = null) {
            const today = new Date();
            const todayFormatted = formatDate(today);

            // Find today's row or create a new one
            let todayRow = null;
            const rows = activityTableBody.getElementsByTagName('tr');

            for (let row of rows) {
                if (row.cells[0].textContent === todayFormatted) {
                    todayRow = row;
                    break;
                }
            }

            if (!todayRow) {
                // Create a new row for today
                todayRow = document.createElement('tr');
                todayRow.className = 'today-row';

                const dateCell = document.createElement('td');
                dateCell.textContent = todayFormatted;

                const dayCell = document.createElement('td');
                dayCell.textContent = getDayName(today);

                const clockInCell = document.createElement('td');
                clockInCell.textContent = formatTime(clockIn);

                const clockOutCell = document.createElement('td');
                clockOutCell.textContent = clockOut ? formatTime(clockOut) : '';

                const hoursCell = document.createElement('td');
                hoursCell.textContent = clockOut ? calculateHours(clockIn, clockOut) : '';

                todayRow.appendChild(dateCell);
                todayRow.appendChild(dayCell);
                todayRow.appendChild(clockInCell);
                todayRow.appendChild(clockOutCell);
                todayRow.appendChild(hoursCell);

                // Insert at the top of the table
                activityTableBody.insertBefore(todayRow, activityTableBody.firstChild);
            } else {
                // Update existing row
                if (clockOut) {
                    todayRow.cells[3].textContent = formatTime(clockOut);
                    todayRow.cells[4].textContent = calculateHours(clockIn, clockOut);
                } else {
                    todayRow.cells[2].textContent = formatTime(clockIn);
                }
            }
        }

        function formatDate(date) {
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const year = date.getFullYear();
            return `${month}/${day}/${year}`;
        }

        function formatTime(date) {
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${hours}:${minutes}`;
        }

        function getDayName(date) {
            const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
            return days[date.getDay()];
        }

        function calculateHours(clockIn, clockOut) {
            const diffMs = clockOut - clockIn;
            const diffHrs = diffMs / (1000 * 60 * 60);
            return `${diffHrs.toFixed(1)}h`;
        }

        function addNotification(title, message) {
            const notificationList = document.getElementById('notification-list');
            const now = new Date();
            const timeString = formatNotificationTime(now);

            const notificationItem = document.createElement('li');
            notificationItem.className = 'notification-item is-unread';
            notificationItem.onclick = function () { markRead(this); };

            notificationItem.innerHTML = `
                <div class="icon-wrap"><i class="fas fa-bell"></i></div>
                <div class="details">
                    <p class="title">${title}</p>
                    <p class="message">${message}</p>
                </div>
                <span class="time">${timeString}</span>
                <span class="unread-dot"></span>
            `;

            // Add to the top of the list
            notificationList.insertBefore(notificationItem, notificationList.firstChild);
        }

        function formatNotificationTime(date) {
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / (1000 * 60));

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} mins a 
                
            go`;

            const diffHours = Math.floor(diffMins / 60);
            if (diffHours < 24) return `${diffHours} hour(s) ago`;

            return 'Yesterday';
        }

        // Notification click
        function markRead(el) {
            el.classList.remove('is-unread');
            const dot = el.querySelector('.unread-dot');
            if (dot) dot.remove();
        }

        // Tabs functionality
        function showTab(tab) {
            console.log('Tab clicked:', tab);
        }

        // Start the timer
        setInterval(updateTimer, 1000);
    </script>
</body>

</html>