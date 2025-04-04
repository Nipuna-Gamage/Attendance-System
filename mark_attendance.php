<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $status = $_POST['status'];
    $date = date('Y-m-d');
    $time = date('H:i:s');

    try {
        // Check if attendance already exists for today
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $date]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing attendance
            $stmt = $pdo->prepare("UPDATE attendance SET status = ?, check_in_time = ? WHERE employee_id = ? AND date = ?");
            $stmt->execute([$status, $time, $employee_id, $date]);
        } else {
            // Insert new attendance
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status, check_in_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$employee_id, $date, $status, $time]);
        }
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>