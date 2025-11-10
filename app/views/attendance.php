<?php
session_start();

// For testing: pretend you’re logged in as employee 1
//$_SESSION['employee_id'] = 1;

//if (!isset($_SESSION['employee_id'])) {
  //  header("Location: login.php");
    //exit();
//}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';

// TODO: Replace with actual logged-in user ID once session is implemented
$employee_id = $_SESSION['employee_id'];

// Fetch REAL weekly activity data from DB
$weeklyActivities = AttendanceController::getWeeklyReport($employee_id);

// Example notifications (keep your sample ones)
$notifications = [
    ["title" => "System Update Completed", "message" => "The attendance system has been successfully updated to version 2.3.", "time" => "2 mins ago", "read" => false],
    ["title" => "Clock Out Reminder", "message" => "You haven't clocked out yet. Please remember to clock out before leaving.", "time" => "5 mins ago", "read" => false],
    ["title" => "Holiday Notice", "message" => "The office will be closed on 16 December for a public holiday.", "time" => "10:24 am", "read" => true],
    ["title" => "Clock-In Successful", "message" => "You clocked in successfully at 08:01 AM. Have a productive day!", "time" => "Yesterday", "read" => true],
    ["title" => "Attendance Approved", "message" => "Your attendance record for 28 October has been verified by the admin.", "time" => "Yesterday", "read" => false]
];
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

        /*.activity-table tbody tr:hover {
            /* background-color: var(--input-bg);}*/

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
                            <tbody>
                                <!--replace fake data with DB data-->
                                <?php if (!empty($weeklyActivities)): ?>
                                <?php foreach ($weeklyActivities as $activity): ?>
                                    <tr>
                                        <td><?= $activity['date'] ?></td>
                                        <td><?= $activity['day'] ?></td>
                                        <td><?= $activity['clockIn'] ?></td>
                                        <td><?= $activity['clockOut'] ?></td>
                                        <td><?= $activity['hours'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; color:gray;">No records found this week.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notification Panel -->
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
        const timerDisplay = document.getElementById('timer-display');
        const progressCircle = document.getElementById('progress-circle');
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
            secondsWorked++;
        }
        setInterval(updateTimer, 1000);
        // Notification click
        function markRead(el) {
            el.classList.remove('is-unread');
            const dot = el.querySelector('.unread-dot');
            if (dot) dot.remove();
        }
        // Tabs functionality
        function showTab(tab) {
            console.log('Tab clicked:', tab); // optional
        }
    </script>
</body>

</html>
