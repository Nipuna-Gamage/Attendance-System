<?php
session_start();
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Check if user is manager or admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['role'] != 'manager' && $user['role'] != 'admin') {
    http_response_code(403);
    exit('Unauthorized to export reports');
}

// Get date range from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Employee ID', 'Employee Name', 'Date', 'Status', 'Check-in Time', 'Check-out Time']);

try {
    // Get attendance records for the date range
    $stmt = $pdo->prepare("
        SELECT 
            e.id as employee_id, 
            e.name as employee_name, 
            a.date, 
            a.status, 
            a.check_in_time, 
            a.check_out_time
        FROM employees e
        LEFT JOIN attendance a ON e.id = a.employee_id
        WHERE a.date BETWEEN ? AND ?
        ORDER BY e.name, a.date
    ");
    $stmt->execute([$start_date, $end_date]);
    
    // Format and write each record to CSV
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format times for display
        $checkInTime = '-';
        if ($row['check_in_time']) {
            $checkInTimeObj = new DateTime($row['check_in_time']);
            $checkInTime = $checkInTimeObj->format('h:i A');
        }
        
        $checkOutTime = '-';
        if ($row['check_out_time']) {
            $checkOutTimeObj = new DateTime($row['check_out_time']);
            $checkOutTime = $checkOutTimeObj->format('h:i A');
        }
        
        // Format date for display
        $dateObj = new DateTime($row['date']);
        $formattedDate = $dateObj->format('Y-m-d');
        
        // Write row to CSV
        fputcsv($output, [
            $row['employee_id'],
            $row['employee_name'],
            $formattedDate,
            $row['status'] ?? 'Not Marked',
            $checkInTime,
            $checkOutTime
        ]);
    }
    
    fclose($output);
} catch (PDOException $e) {
    fclose($output);
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?> 