<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Get selected date or default to today
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

try {
    // Check if user is manager or admin
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user['role'] == 'manager' || $user['role'] == 'admin') {
        // Show all employees for managers and admins
        $stmt = $pdo->prepare("
            SELECT e.id, e.name, a.status, a.check_in_time, a.check_out_time 
            FROM employees e 
            LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
            ORDER BY e.name
        ");
        $stmt->execute([$date]);
    } else {
        // Show only the logged-in employee
        $stmt = $pdo->prepare("
            SELECT e.id, e.name, a.status, a.check_in_time, a.check_out_time 
            FROM employees e 
            LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
            WHERE e.id = ?
            ORDER BY e.name
        ");
        $stmt->execute([$date, $_SESSION['user_id']]);
    }
    
    $employees = $stmt->fetchAll();

    foreach ($employees as $employee) {
        $status = $employee['status'] ?? 'Not Marked';
        
        // Format time for display
        $checkInTime = '-';
        if ($employee['check_in_time']) {
            $checkInTimeObj = new DateTime($employee['check_in_time']);
            $checkInTime = $checkInTimeObj->format('h:i A');
        }
        
        $checkOutTime = '-';
        if ($employee['check_out_time']) {
            $checkOutTimeObj = new DateTime($employee['check_out_time']);
            $checkOutTime = $checkOutTimeObj->format('h:i A');
        }
        
        $statusClass = $status == 'Present' ? 'status-present' : ($status == 'Absent' ? 'status-absent' : '');
        
        echo "<tr>";
        echo "<td>{$employee['name']}</td>";
        echo "<td class='{$statusClass}'>{$status}</td>";
        echo "<td>{$checkInTime}</td>";
        echo "<td>{$checkOutTime}</td>";
        echo "<td>";
        if (!$employee['check_in_time']) {
            if ($status != 'Absent') {
                echo "<button class='btn btn-sm btn-success me-2' onclick='showTimeInput({$employee['id']}, \"check_in\", \"{$date}\")'>Check In</button>";
            }
            if ($user['role'] == 'manager' || $user['role'] == 'admin') {
                if ($status != 'Absent') {
                    echo "<button class='btn btn-sm btn-danger' onclick='markAbsent({$employee['id']}, \"{$date}\")'>Mark Absent</button>";
                } else {
                    echo "<button class='btn btn-sm btn-secondary' disabled>Marked Absent</button>";
                }
            }
        }
        if ($employee['check_in_time'] && !$employee['check_out_time']) {
            echo "<button class='btn btn-sm btn-warning' onclick='showTimeInput({$employee['id']}, \"check_out\", \"{$date}\")'>Check Out</button>";
        }
        echo "</td>";
        echo "</tr>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 