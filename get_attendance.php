<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$date = date('Y-m-d');

try {
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, a.status, a.check_in_time 
        FROM employees e 
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
        ORDER BY e.name
    ");
    $stmt->execute([$date]);
    $employees = $stmt->fetchAll();

    foreach ($employees as $employee) {
        $status = $employee['status'] ?? 'Not Marked';
        $checkInTime = $employee['check_in_time'] ?? '-';
        $statusClass = $status == 'Present' ? 'status-present' : ($status == 'Absent' ? 'status-absent' : '');
        
        echo "<tr>";
        echo "<td>{$employee['name']}</td>";
        echo "<td class='{$statusClass}'>{$status}</td>";
        echo "<td>{$checkInTime}</td>";
        echo "<td>";
        echo "<button class='btn btn-sm btn-success me-2' onclick='markAttendance({$employee['id']}, \"Present\")'>Present</button>";
        echo "<button class='btn btn-sm btn-danger' onclick='markAttendance({$employee['id']}, \"Absent\")'>Absent</button>";
        echo "</td>";
        echo "</tr>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>