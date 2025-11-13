<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Redirect if not logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

$employee_id = $_SESSION['employee_id'];

// Handle clock in/out POST before any HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/../controllers/AttendanceController.php';
    $action = $_POST['action'];

    if ($action === 'clockIn') {
        $result = AttendanceController::clockIn($employee_id);
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['status'];
    } elseif ($action === 'clockOut') {
        $result = AttendanceController::clockOut($employee_id);
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['status'];
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Include header after logic
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';
require_once __DIR__ . '/../controllers/notificationController.php';

// Check if employee is currently clocked in
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

// Fetch notifications from backend API
$apiUrl = "http://localhost/php-notif/public/api/index.php?employee_id=" . $employee_id;
$response = @file_get_contents($apiUrl);
if ($response === FALSE) {
    $notifications = [];
} else {
    $data = json_decode($response, true);
    $notifications = $data['notifications'] ?? [];
}

// Weekly activities
$weeklyActivities = AttendanceController::getWeeklyReport($employee_id);

// Check dark mode from cookie (same as header.php)
$isDarkMode = isset($_COOKIE['dark_mode']) ? $_COOKIE['dark_mode'] === 'true' : false;
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
            --bg-card: #FFFFFF;
        }
        /* Dark Mode - Matching header.php */
        body.dark-mode {
            --header-bg: #243238;
            --button-text: #EBFFFD;
            --bg-color: #1F292E;
            --bg-card: #2A2A2A;
            --panel-bg: #243238;
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
            background-color: var(--bg-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        button {
            transition: all 0.3s ease;
        }
        /* attendance styles */
        .attendance-dashboard {
            min-height: 100vh;
            background-color: var(--bg-color);
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
        /* FIXED GRID */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            align-items: stretch;
            width: 100%;
        }
        /* FIXED CARD */
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
            transition: all 0.3s ease;
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
            position: relative;
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
            background-color: #FF5C5C;
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
            color: var(--accent-color);
        }
        .activity-table th {
            background-color: var(--bg-card);
            font-weight: 600;
            color: var(--accent-color);
        }
        .activity-table tbody tr:hover {
            background-color: var(--input-bg);
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
            transition: all 0.3s ease;
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
            color: var(--accent-color);
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
            color: var(--accent-color);
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
            color: var(--accent-color);
            transition: all 0.3s ease;
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
            color: var(--accent-color);
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
            background: #FF5C5C;
        }
        .empty {
            text-align: center;
            margin-top: 10px;
            color: var(--subtext-color);
        }
        
        /* Message styles */
        .message {
        padding: 1px;
        border-radius: 6px;
        margin-bottom: 10px;
        margin-left: 0; /* Changed from 1rem to 0 */
        margin-right: 1rem;
        margin-top: 0.5; /* Added some top margin for better spacing */
    }
        .message.success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
            margin-top: 5px;
            margin-bottom: 5px;
            text-align: center;
        }
        .message.error { 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
            margin-top: 5px;
            margin-bottom: 5px;
            text-align: center;
        }
        
        /* Dark mode specific message styles */
        body.dark-mode .message.success { 
            background: #1e3a2a; 
            color: #4ade80; 
            border: 1px solid #166534;
        }
        body.dark-mode .message.error { 
            background: #3a1e1e; 
            color: #f87171; 
            border: 1px solid #7f1d1d;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
            .card {
                padding: 1rem;
            }
            .notification-panel {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="<?php echo $isDarkMode ? 'dark-mode' : ''; ?>">
    <div class="attendance-dashboard">
        <h1>Attendance</h1>

        <!-- Flash messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?= $_SESSION['message_type'] === 'success' ? 'success' : 'error'; ?>">
                <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message'], $_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <main class="main-content">
            <div class="cards-grid">
                <!-- Timer Card -->
                <div class="card">
                    <h2>Hours Worked</h2>
                    <div class="timer-container">
                        <div class="svg-container">
                            <svg class="progress-ring" viewBox="0 0 100 100">
                                <circle class="progress-ring-background" cx="50" cy="50" r="45" stroke-width="8" fill="none"/>
                                <circle class="progress-ring-circle" cx="50" cy="50" r="45" stroke-width="8" fill="none" 
                                        stroke-dasharray="283" stroke-dashoffset="283"/>
                            </svg>
                            <div class="timer-content">
                                <div id="timer-display" class="timer-display">
                                    00h 00m 00s
                                    <div class="floating-dot"></div>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="action" value="<?= $isClockedIn ? 'clockOut' : 'clockIn'; ?>">
                                    <button class="clock-button <?= $isClockedIn ? 'clocked-in' : ''; ?>">
                                        <?= $isClockedIn ? 'Clock Out' : 'Clock In'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Activity -->
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
                                <?php foreach ($weeklyActivities as $a): ?>
                                    <tr class="<?= $a['date'] === date('m/d/Y') ? 'today-row' : '' ?>">
                                        <td><?= $a['date'] ?></td>
                                        <td><?= $a['day'] ?></td>
                                        <td><?= $a['clockIn'] ?></td>
                                        <td><?= $a['clockOut'] ?></td>
                                        <td><?= $a['hours'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="notification-panel-wrapper">
                <div class="notification-panel">
                    <div class="panel-header">
                        <h4>Notifications</h4>
                    </div>
                    <ul class="notification-list" id="notification-list">
                        <?php if (empty($notifications)): ?>
                            <p class="empty">No notifications found.</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $note): ?>
                                <li class="notification-item <?= empty($note['read']) ? 'is-unread' : ''; ?>" onclick="markRead(this)">
                                    <div class="icon-wrap">
                                        <i class="fas <?= !empty($note['is_broadcast']) ? 'fa-bullhorn' : 'fa-bell'; ?>"></i>
                                    </div>
                                    <div class="details">
                                        <p class="title"><?= htmlspecialchars($note['title']); ?></p>
                                        <p class="message"><?= htmlspecialchars($note['message']); ?></p>
                                        <span class="time">
                                            <?php
                                                if (!empty($note['date_created'])) {
                                                    $ts = strtotime($note['date_created']);
                                                    $diff = time() - $ts;
                                                    if ($diff < 60) echo 'Just now';
                                                    elseif ($diff < 3600) echo floor($diff/60).' mins ago';
                                                    elseif ($diff < 86400) echo floor($diff/3600).' hours ago';
                                                    else echo date('M j, g:i A', $ts);
                                                } else echo 'Recently';
                                            ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Timer functionality with progress ring
        let secondsWorked = 0;
        const timerDisplay = document.getElementById('timer-display');
        const progressRing = document.querySelector('.progress-ring-circle');
        const radius = 45;
        const circumference = 2 * Math.PI * radius;

        // Initialize progress ring
        progressRing.style.strokeDasharray = circumference;
        progressRing.style.strokeDashoffset = circumference;

        function updateTimer() {
            const h = Math.floor(secondsWorked / 3600);
            const m = Math.floor((secondsWorked % 3600) / 60);
            const s = secondsWorked % 60;
            timerDisplay.textContent = `${String(h).padStart(2,'0')}h ${String(m).padStart(2,'0')}m ${String(s).padStart(2,'0')}s`;
            
            // Update progress ring (8-hour work day)
            const progress = secondsWorked / (8 * 3600); // 8 hours max
            const offset = circumference - (progress * circumference);
            progressRing.style.strokeDashoffset = Math.min(offset, circumference);
            
            secondsWorked++;
        }

        // Start timer if clocked in
        <?php if ($isClockedIn): ?>
        // You would need to calculate actual seconds worked from database
        // For now, starting from 0
        setInterval(updateTimer, 1000);
        <?php endif; ?>

        // Notifications mark read
        function markRead(el) {
            el.classList.remove('is-unread');
            // Here you would typically make an API call to mark as read
        }

        // Listen for dark mode changes from header
        document.addEventListener('DOMContentLoaded', function() {
            // Observe body class changes for dark mode
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        // Dark mode was toggled in header, our CSS variables will automatically update
                        console.log('Dark mode toggled via header');
                    }
                });
            });
            
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>
</body>
</html>
