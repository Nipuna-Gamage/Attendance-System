<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is manager or admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user['role'] != 'manager' && $user['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Get attendance records for the date range
$stmt = $pdo->prepare("
    SELECT DISTINCT
        e.id as employee_id, 
        e.name as employee_name, 
        a.date, 
        a.status, 
        a.check_in_time, 
        a.check_out_time
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id AND a.date BETWEEN ? AND ?
    WHERE (a.date IS NULL OR a.date BETWEEN ? AND ?)
    ORDER BY e.name, a.date
");
$stmt->execute([$start_date, $end_date, $start_date, $end_date]);
$attendance_records = $stmt->fetchAll();

// Calculate statistics
$total_days = 0;
$total_present = 0;
$total_absent = 0;
$total_late = 0;

foreach ($attendance_records as $record) {
    if ($record['date']) {
        $total_days++;
        if ($record['status'] == 'Present') {
            $total_present++;
        } elseif ($record['status'] == 'Absent') {
            $total_absent++;
        }
        
        // Check if late (check-in after 9:00 AM)
        if ($record['check_in_time']) {
            $checkInTime = new DateTime($record['check_in_time']);
            $nineAM = new DateTime('09:00:00');
            if ($checkInTime > $nineAM) {
                $total_late++;
            }
        }
    }
}

// Group records by employee
$employee_records = [];
foreach ($attendance_records as $record) {
    if (!isset($employee_records[$record['employee_id']])) {
        $employee_records[$record['employee_id']] = [
            'name' => $record['employee_name'],
            'records' => []
        ];
    }
    if ($record['date']) {
        $employee_records[$record['employee_id']]['records'][] = $record;
    }
}
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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Attendance Reports</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">View Report</button>
                                            <a href="export_report.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success">
                                                <i class="fas fa-file-export"></i> Export to CSV
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Days</h5>
                                        <h2 class="mb-0"><?php echo $total_days; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Present</h5>
                                        <h2 class="mb-0"><?php echo $total_present; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-danger text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Absent</h5>
                                        <h2 class="mb-0"><?php echo $total_absent; ?></h2>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card bg-warning text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Late</h5>
                                        <h2 class="mb-0"><?php echo $total_late; ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Check-in Time</th>
                                        <th>Check-out Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_records as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($record['date'])); ?></td>
                                        <td><?php echo $record['status'] ?? 'Not Marked'; ?></td>
                                        <td>
                                            <?php 
                                            if ($record['check_in_time']) {
                                                $checkInTimeObj = new DateTime($record['check_in_time']);
                                                echo $checkInTimeObj->format('h:i A');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($record['check_out_time']) {
                                                $checkOutTimeObj = new DateTime($record['check_out_time']);
                                                echo $checkOutTimeObj->format('h:i A');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
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
</body>
</html> 