<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get today's statistics
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
    SELECT e.name, a.status, a.check_in_time 
    FROM attendance a 
    JOIN employees e ON a.employee_id = e.id 
    WHERE a.date = CURDATE()
    ORDER BY a.check_in_time DESC
");
$today_attendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .status-present {
            color: #28a745;
        }
        .status-absent {
            color: #dc3545;
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
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employees.php">Employees</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Reports</a>
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
                    <div class="card-header">
                        <h5 class="mb-0">Today's Attendance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Status</th>
                                        <th>Check-in Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="attendanceList">
                                    <?php foreach ($today_attendance as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td class="status-<?php echo strtolower($record['status']); ?>">
                                            <?php echo $record['status']; ?>
                                        </td>
                                        <td><?php echo $record['check_in_time']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success me-2" onclick="markAttendance(<?php echo $record['id']; ?>, 'Present')">Present</button>
                                            <button class="btn btn-sm btn-danger" onclick="markAttendance(<?php echo $record['id']; ?>, 'Absent')">Absent</button>
                                        </td>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function loadAttendance() {
            $.ajax({
                url: 'get_attendance.php',
                method: 'GET',
                success: function(response) {
                    $('#attendanceList').html(response);
                }
            });
        }

        function markAttendance(employeeId, status) {
            $.ajax({
                url: 'mark_attendance.php',
                method: 'POST',
                data: {
                    employee_id: employeeId,
                    status: status
                },
                success: function(response) {
                    loadAttendance();
                }
            });
        }

        $(document).ready(function() {
            loadAttendance();
            setInterval(loadAttendance, 30000); // Refresh every 30 seconds
        });
    </script>
</body>
</html>