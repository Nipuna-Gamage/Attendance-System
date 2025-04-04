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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get attendance patterns
$stmt = $pdo->prepare("
    SELECT 
        e.id as employee_id,
        e.name as employee_name,
        a.date,
        a.check_in_time,
        a.check_out_time,
        a.status,
        TIME_TO_SEC(TIMEDIFF(a.check_in_time, '09:00:00'))/60 as minutes_late
    FROM employees e
    LEFT JOIN attendance a ON e.id = a.employee_id
    WHERE a.date BETWEEN ? AND ?
    ORDER BY a.date, e.name
");
$stmt->execute([$start_date, $end_date]);
$attendance_records = $stmt->fetchAll();

// Calculate statistics
$late_arrivals = [];
$attendance_patterns = [];
$employee_stats = [];
$total_days = 0;
$total_late = 0;
$total_absent = 0;
$total_present = 0;
$total_minutes_late = 0;

foreach ($attendance_records as $record) {
    if ($record['date']) {
        $total_days++;
        
        // Count attendance status
        if ($record['status'] == 'Present') {
            $total_present++;
        } elseif ($record['status'] == 'Absent') {
            $total_absent++;
        }
        
        // Track late arrivals
        if ($record['check_in_time'] && $record['minutes_late'] > 0) {
            $total_late++;
            $total_minutes_late += $record['minutes_late'];
            $late_arrivals[] = [
                'employee' => $record['employee_name'],
                'date' => $record['date'],
                'minutes_late' => round($record['minutes_late'])
            ];
        }
        
        // Track attendance patterns
        $date = $record['date'];
        if (!isset($attendance_patterns[$date])) {
            $attendance_patterns[$date] = [
                'present' => 0,
                'absent' => 0,
                'late' => 0
            ];
        }
        
        if ($record['status'] == 'Present') {
            $attendance_patterns[$date]['present']++;
        } elseif ($record['status'] == 'Absent') {
            $attendance_patterns[$date]['absent']++;
        }
        if ($record['minutes_late'] > 0) {
            $attendance_patterns[$date]['late']++;
        }
        
        // Track employee-specific statistics
        $employee_id = $record['employee_id'];
        if (!isset($employee_stats[$employee_id])) {
            $employee_stats[$employee_id] = [
                'name' => $record['employee_name'],
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'total_minutes_late' => 0,
                'total_days' => 0
            ];
        }
        
        $employee_stats[$employee_id]['total_days']++;
        if ($record['status'] == 'Present') {
            $employee_stats[$employee_id]['present']++;
        } elseif ($record['status'] == 'Absent') {
            $employee_stats[$employee_id]['absent']++;
        }
        if ($record['minutes_late'] > 0) {
            $employee_stats[$employee_id]['late']++;
            $employee_stats[$employee_id]['total_minutes_late'] += $record['minutes_late'];
        }
    }
}

// Calculate average minutes late
$average_minutes_late = $total_late > 0 ? round($total_minutes_late / $total_late, 1) : 0;

// Sort late arrivals by minutes late (descending)
usort($late_arrivals, function($a, $b) {
    return $b['minutes_late'] - $a['minutes_late'];
});

// Get top 5 late arrivals
$top_late_arrivals = array_slice($late_arrivals, 0, 5);

// Sort employees by attendance percentage
foreach ($employee_stats as &$stat) {
    $stat['attendance_percentage'] = $stat['total_days'] > 0 ? 
        round(($stat['present'] / $stat['total_days']) * 100, 1) : 0;
    $stat['average_minutes_late'] = $stat['late'] > 0 ? 
        round($stat['total_minutes_late'] / $stat['late'], 1) : 0;
}
unset($stat);

// Sort employees by attendance percentage (descending)
uasort($employee_stats, function($a, $b) {
    return $b['attendance_percentage'] - $a['attendance_percentage'];
});

// Get top 5 employees by attendance
$top_employees = array_slice($employee_stats, 0, 5, true);

// Get bottom 5 employees by attendance
$bottom_employees = array_slice($employee_stats, -5, 5, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Analysis - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
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
                        <a class="nav-link" href="reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="attendance_analysis.php">Analysis</a>
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
                        <h5 class="mb-0">Attendance Analysis</h5>
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
                                            <button type="submit" class="btn btn-primary">Analyze</button>
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
                                        <h5 class="card-title">Late Arrivals</h5>
                                        <h2 class="mb-0"><?php echo $total_late; ?></h2>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Attendance Rate</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="progress mb-3" style="height: 25px;">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                style="width: <?php echo $total_days > 0 ? round(($total_present / $total_days) * 100) : 0; ?>%;" 
                                                aria-valuenow="<?php echo $total_days > 0 ? round(($total_present / $total_days) * 100) : 0; ?>" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $total_days > 0 ? round(($total_present / $total_days) * 100) : 0; ?>%
                                            </div>
                                        </div>
                                        <p class="text-muted">Overall attendance rate for the selected period</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Average Minutes Late</h5>
                                    </div>
                                    <div class="card-body text-center">
                                        <h2 class="display-4"><?php echo $average_minutes_late; ?></h2>
                                        <p class="text-muted">Average delay for late arrivals</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Absence Rate</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="progress mb-3" style="height: 25px;">
                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                style="width: <?php echo $total_days > 0 ? round(($total_absent / $total_days) * 100) : 0; ?>%;" 
                                                aria-valuenow="<?php echo $total_days > 0 ? round(($total_absent / $total_days) * 100) : 0; ?>" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                <?php echo $total_days > 0 ? round(($total_absent / $total_days) * 100) : 0; ?>%
                                            </div>
                                        </div>
                                        <p class="text-muted">Percentage of absences during the selected period</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Attendance Pattern</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="chart-container">
                                            <canvas id="attendancePatternChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Top 5 Late Arrivals</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Employee</th>
                                                        <th>Date</th>
                                                        <th>Minutes Late</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($top_late_arrivals as $late): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($late['employee']); ?></td>
                                                        <td><?php echo date('Y-m-d', strtotime($late['date'])); ?></td>
                                                        <td><?php echo $late['minutes_late']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Top 5 Employees by Attendance</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Employee</th>
                                                        <th>Attendance %</th>
                                                        <th>Present</th>
                                                        <th>Absent</th>
                                                        <th>Late</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($top_employees as $employee_id => $employee): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar bg-success" role="progressbar" 
                                                                    style="width: <?php echo $employee['attendance_percentage']; ?>%;" 
                                                                    aria-valuenow="<?php echo $employee['attendance_percentage']; ?>" 
                                                                    aria-valuemin="0" aria-valuemax="100">
                                                                    <?php echo $employee['attendance_percentage']; ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $employee['present']; ?></td>
                                                        <td><?php echo $employee['absent']; ?></td>
                                                        <td><?php echo $employee['late']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Bottom 5 Employees by Attendance</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Employee</th>
                                                        <th>Attendance %</th>
                                                        <th>Present</th>
                                                        <th>Absent</th>
                                                        <th>Late</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($bottom_employees as $employee_id => $employee): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar bg-danger" role="progressbar" 
                                                                    style="width: <?php echo $employee['attendance_percentage']; ?>%;" 
                                                                    aria-valuenow="<?php echo $employee['attendance_percentage']; ?>" 
                                                                    aria-valuemin="0" aria-valuemax="100">
                                                                    <?php echo $employee['attendance_percentage']; ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $employee['present']; ?></td>
                                                        <td><?php echo $employee['absent']; ?></td>
                                                        <td><?php echo $employee['late']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Employee Attendance Details</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Employee</th>
                                                        <th>Attendance %</th>
                                                        <th>Present</th>
                                                        <th>Absent</th>
                                                        <th>Late</th>
                                                        <th>Avg. Minutes Late</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($employee_stats as $employee_id => $employee): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                                        <td>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar <?php echo $employee['attendance_percentage'] >= 80 ? 'bg-success' : ($employee['attendance_percentage'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                                                    role="progressbar" 
                                                                    style="width: <?php echo $employee['attendance_percentage']; ?>%;" 
                                                                    aria-valuenow="<?php echo $employee['attendance_percentage']; ?>" 
                                                                    aria-valuemin="0" aria-valuemax="100">
                                                                    <?php echo $employee['attendance_percentage']; ?>%
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $employee['present']; ?></td>
                                                        <td><?php echo $employee['absent']; ?></td>
                                                        <td><?php echo $employee['late']; ?></td>
                                                        <td><?php echo $employee['average_minutes_late']; ?></td>
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prepare data for attendance pattern chart
        const dates = <?php echo json_encode(array_keys($attendance_patterns)); ?>;
        const presentData = <?php echo json_encode(array_column($attendance_patterns, 'present')); ?>;
        const absentData = <?php echo json_encode(array_column($attendance_patterns, 'absent')); ?>;
        const lateData = <?php echo json_encode(array_column($attendance_patterns, 'late')); ?>;

        // Create attendance pattern chart
        const ctx = document.getElementById('attendancePatternChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'Present',
                        data: presentData,
                        borderColor: 'rgb(40, 167, 69)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true
                    },
                    {
                        label: 'Absent',
                        data: absentData,
                        borderColor: 'rgb(220, 53, 69)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        fill: true
                    },
                    {
                        label: 'Late',
                        data: lateData,
                        borderColor: 'rgb(255, 193, 7)',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 