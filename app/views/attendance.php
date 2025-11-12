<?php
require_once __DIR__ . '/../../includes/config.php';

// Set employee_id for testing
if (!isset($_SESSION['employee_id'])) {
    $_SESSION['employee_id'] = 1;
}

$employee_id = $_SESSION['employee_id'];

// Handle form submission for clock in/out - MUST BE BEFORE ANY HTML OUTPUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/../controllers/AttendanceController.php';
    
    $action = $_POST['action'];
    
    if ($action === 'clockIn') {
        $result = AttendanceController::clockIn($employee_id);
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['status'];
    } else if ($action === 'clockOut') {
        $result = AttendanceController::clockOut($employee_id);
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['status'];
    }
    
    // Redirect to refresh the page and show updated data
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Now include the header and other files AFTER handling the POST request
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';

// Check if user is currently clocked in (for button display)
$isClockedIn = false;
try {
    $db = Database::getInstance()->getConnection();
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT record_id FROM record_backups WHERE employee_id = ? AND date = ? AND clockout_time IS NULL");
    $stmt->bind_param("is", $employee_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $isClockedIn = $result->num_rows > 0;
    $stmt->close();
} catch (Exception $e) {
    error_log("Error checking clock status: " . $e->getMessage());
}

// Get weekly activities
$weeklyActivities = AttendanceController::getWeeklyReport($employee_id);

// Sample notifications
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
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com;">
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
            color: var(--text-color);
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        button {
            transition: all 0.3s ease;
        }

        /* Message styles */
        .message {
            padding: 12px 20px 12px 15px;
            margin: 10px 0;
            border-radius: 8px;
            font-weight: 500;
            position: relative;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

    .close-btn {
    position: absolute;
    top: 8px;
    right: 12px;
    font-size: 18px;
    font-weight: bold;
    color: #555;
    cursor: pointer;
    transition: color 0.3s ease;
}

.close-btn:hover {
    color: #000;
}

        /* attendance styles */
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
            background-color: rgba(6, 195, 167, 0.05);
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
        
        <!-- Display messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'error'; ?>">
                <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
                <?php 
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
&nbsp;
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
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="<?php echo $isClockedIn ? 'clockOut' : 'clockIn'; ?>">
                                    <button type="submit" class="clock-button <?php echo $isClockedIn ? 'clocked-in' : ''; ?>" id="clock-button">
                                        <?php echo $isClockedIn ? 'Clock Out' : 'Clock In'; ?>
                                    </button>
                                </form>
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
                                        <td><?= htmlspecialchars($activity['date']) ?></td>
                                        <td><?= htmlspecialchars($activity['day']) ?></td>
                                        <td><?= htmlspecialchars($activity['clockIn']) ?></td>
                                        <td><?= htmlspecialchars($activity['clockOut']) ?></td>
                                        <td><?= htmlspecialchars($activity['hours']) ?></td>
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
                        <button class="active" onclick="showTab('All')" aria-label="Show all notifications">All</button>
                        <button onclick="showTab('Read')" aria-label="Show read notifications">Read</button>
                        <button onclick="showTab('Unread')" aria-label="Show unread notifications">Unread</button>
                    </div>
                    <ul class="notification-list" id="notification-list">
                        <?php foreach ($notifications as $note): ?>
                            <li class="notification-item <?= $note['read'] ? '' : 'is-unread' ?>" onclick="markRead(this)">
                                <div class="icon-wrap"><i class="fas fa-bell"></i></div>
                                <div class="details">
                                    <p class="title"><?= htmlspecialchars($note['title']) ?></p>
                                    <p class="message"><?= htmlspecialchars($note['message']) ?></p>
                                </div>
                                <span class="time"><?= htmlspecialchars($note['time']) ?></span>
                                <?php if (!$note['read']): ?><span class="unread-dot"></span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
    /* ---------------- Timer Functionality Only ---------------- */
    let secondsWorked = 0;
    let isClockedIn = <?php echo $isClockedIn ? 'true' : 'false'; ?>;
    let clockInTime = null;

    const timerDisplay = document.getElementById('timer-display');
    const progressCircle = document.getElementById('progress-circle');

    /* Update timer display */
    function updateTimer() {
        const h = Math.floor(secondsWorked / 3600);
        const m = Math.floor((secondsWorked % 3600) / 60);
        const s = secondsWorked % 60;
        timerDisplay.innerText = `${String(h).padStart(2,'0')}h ${String(m).padStart(2,'0')}m ${String(s).padStart(2,'0')}s`;

        const circumference = 2 * Math.PI * 125;
        const totalSeconds = 8 * 3600;
        const progress = Math.min((secondsWorked / totalSeconds) * circumference, circumference);
        progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
        progressCircle.style.strokeDashoffset = circumference - progress;

        if (isClockedIn) secondsWorked++;
    }

    /* Utility functions for notifications */
    function formatTime(date) {
        const h = String(date.getHours()).padStart(2,'0');
        const m = String(date.getMinutes()).padStart(2,'0');
        return `${h}:${m}`;
    }

    function calculateHours(clockIn, clockOut) {
        const diff = (clockOut - clockIn) / 3600000;
        return `${diff.toFixed(1)}h`;
    }

    /* Notifications */
    function addNotification(title, message) {
        const list = document.getElementById('notification-list');
        const item = document.createElement('li');
        item.className = 'notification-item is-unread';
        item.onclick = function() { markRead(this); };
        item.innerHTML = `
            <div class="icon-wrap"><i class="fas fa-bell"></i></div>
            <div class="details">
                <p class="title">${title}</p>
                <p class="message">${message}</p>
            </div>
            <span class="time">Just now</span>
            <span class="unread-dot"></span>`;
        list.prepend(item);
    }

    function markRead(el) {
        el.classList.remove('is-unread');
        const dot = el.querySelector('.unread-dot');
        if (dot) dot.remove();
    }

    function showTab(tab) { 
        console.log('Tab clicked:', tab); 
    }

    /* Start timer */
    setInterval(updateTimer, 1000);
    
    </script>
</body>
</html>
