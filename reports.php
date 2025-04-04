<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee Name', 'Date', 'Status', 'Check-in Time']);
    
    $stmt = $pdo->query("
        SELECT e.name, a.date, a.status, a.check_in_time 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        ORDER BY a.date DESC, e.name
    ");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

// Get attendance statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT CASE WHEN status = 'Present' THEN employee_id END) as present_count,
        COUNT(DISTINCT CASE WHEN status = 'Absent' THEN employee_id END) as absent_count,
        COUNT(DISTINCT employee_id) as total_employees
    FROM attendance 
    WHERE date = CURDATE()
");
$stats = $stmt->fetch();

// Get recent attendance
$stmt = $pdo->query("
    SELECT e.name, a.date, a.status, a.check_in_time 
    FROM attendance a 
    JOIN employees e ON a.employee_id = e.id 
    ORDER BY a.date DESC, a.check_in_time DESC 
    LIMIT 10
");
$recent_attendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-calendar-check"></i> Attendance System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employees.php">Employees</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">Reports</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Employees</h5>
                        <h2 class="mb-0"><?php echo $stats['total_employees']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Present Today</h5>
                        <h2 class="mb-0"><?php echo $stats['present_count']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Absent Today</h5>
                        <h2 class="mb-0"><?php echo $stats['absent_count']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Attendance Reports</h5>
                        <a href="?export=1" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export to CSV
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Check-in Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_attendance as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td><?php echo $record['date']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $record['status'] == 'Present' ? 'success' : 'danger'; ?>">
                                                <?php echo $record['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $record['check_in_time']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>