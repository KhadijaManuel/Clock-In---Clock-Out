// <?php
// // Prevent caching
// header("Cache-Control: no-cache, no-store, must-revalidate");
// header("Pragma: no-cache");
// header("Expires: 0");

// session_start();
// require_once __DIR__ . '/../../includes/db.php';

// header('Content-Type: application/json');

// // Get employee ID from session
// if (!isset($_SESSION['employee_id'])) {
//     echo json_encode(['success' => false, 'message' => 'User not logged in']);
//     exit;
// }

// $employee_id = $_SESSION['employee_id'];

// // Handle GET request for checking clock status
// if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check_status') {
//     try {
//         $db = Database::getInstance();
//         $current_date = date('Y-m-d');
        
//         // Check if user is currently clocked in
//         $check_sql = "SELECT clockin_time FROM record_backups WHERE employee_id = ? AND date = ? AND clockout_time IS NULL ORDER BY clockin_time DESC LIMIT 1";
//         $check_result = $db->query($check_sql, [$employee_id, $current_date]);
        
//         if (!empty($check_result)) {
//             echo json_encode([
//                 'clocked_in' => true,
//                 'clock_in_time' => $check_result[0]['clockin_time']
//             ]);
//         } else {
//             echo json_encode(['clocked_in' => false]);
//         }
//     } catch (Exception $e) {
//         echo json_encode(['clocked_in' => false]);
//     }
//     exit;
// }

// // Handle POST requests for clock in/out
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
//     exit;
// }

// $action = $_POST['action'] ?? '';
// $clock_time = $_POST['clock_time'] ?? '';

// if (empty($action) || empty($clock_time)) {
//     echo json_encode(['success' => false, 'message' => 'Missing required data']);
//     exit;
// }

// try {
//     $db = Database::getInstance();
    
//     $current_date = date('Y-m-d');
//     $current_time = date('H:i:s', strtotime($clock_time));
    
//     if ($action === 'clock_in') {
//         // Check if already clocked in today without clocking out
//         $check_sql = "SELECT * FROM record_backups WHERE employee_id = ? AND date = ? AND clockout_time IS NULL";
//         $check_result = $db->query($check_sql, [$employee_id, $current_date]);
        
//         if (!empty($check_result)) {
//             echo json_encode(['success' => false, 'message' => 'Already clocked in today. Please clock out first.']);
//             exit;
//         }
        
//         // Get employee name
//         $name_sql = "SELECT first_name, last_name FROM employees WHERE employee_id = ?";
//         $name_result = $db->query($name_sql, [$employee_id]);
        
//         if (empty($name_result)) {
//             echo json_encode(['success' => false, 'message' => 'Employee not found']);
//             exit;
//         }
        
//         $employee = $name_result[0];
//         $full_name = $employee['first_name'] . ' ' . $employee['last_name'];
        
//         // Insert clock in record
//         $sql = "INSERT INTO record_backups (employee_id, full_name, clockin_time, date) VALUES (?, ?, ?, ?)";
//         $result = $db->execute($sql, [$employee_id, $full_name, $current_time, $current_date]);
        
//         if ($result !== false) {
//             echo json_encode(['success' => true, 'message' => 'Clocked in successfully']);
//         } else {
//             echo json_encode(['success' => false, 'message' => 'Failed to clock in']);
//         }
        
//     } elseif ($action === 'clock_out') {
//         // Find the latest clock in record for today that hasn't been clocked out
//         $find_sql = "SELECT record_id, clockin_time FROM record_backups WHERE employee_id = ? AND date = ? AND clockout_time IS NULL ORDER BY clockin_time DESC LIMIT 1";
//         $find_result = $db->query($find_sql, [$employee_id, $current_date]);
        
//         if (empty($find_result)) {
//             echo json_encode(['success' => false, 'message' => 'No active clock in record found. Please clock in first.']);
//             exit;
//         }
        
//         $record = $find_result[0];
//         $record_id = $record['record_id'];
        
//         // Update clock out time
//         $sql = "UPDATE record_backups SET clockout_time = ? WHERE record_id = ?";
//         $result = $db->execute($sql, [$current_time, $record_id]);
        
//         if ($result !== false) {
//             echo json_encode(['success' => true, 'message' => 'Clocked out successfully']);
//         } else {
//             echo json_encode(['success' => false, 'message' => 'Failed to clock out']);
//         }
//     }
    
// } catch (Exception $e) {
//     error_log("Clock handler error: " . $e->getMessage());
//     echo json_encode(['success' => false, 'message' => 'Database error occurred: ' . $e->getMessage()]);
// }
// ?>
