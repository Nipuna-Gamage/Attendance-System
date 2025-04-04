<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $action = $_POST['action']; // 'check_in' or 'check_out'
    $date = date('Y-m-d');
    $time = date('H:i:s');

    try {
        // Check if attendance already exists for today
        $stmt = $pdo->prepare("SELECT id, check_in_time, check_out_time FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $date]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($action == 'check_in' && !$existing['check_in_time']) {
                // Update check-in time
                $stmt = $pdo->prepare("UPDATE attendance SET check_in_time = ? WHERE id = ?");
                $stmt->execute([$time, $existing['id']]);
            } elseif ($action == 'check_out' && !$existing['check_out_time']) {
                // Update check-out time
                $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = ? WHERE id = ?");
                $stmt->execute([$time, $existing['id']]);
            }
        } else {
            // Insert new attendance record
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status, check_in_time) VALUES (?, ?, 'Present', ?)");
            $stmt->execute([$employee_id, $date, $time]);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>