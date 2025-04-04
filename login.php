<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4">Attendance System</h3>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
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
        COUNT(DISTINCT CASE WHEN check_in_time IS NOT NULL THEN employee_id END) as checked_in_count,
        COUNT(DISTINCT CASE WHEN check_out_time IS NOT NULL THEN employee_id END) as checked_out_count,
        COUNT(DISTINCT employee_id) as total_employees
    FROM attendance 
    WHERE date = CURDATE()
");
$stats = $stmt->fetch();

// Get recent attendance
$stmt = $pdo->query("
    SELECT e.name, a.check_in_time, a.check_out_time 
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
                        <h5 class="card-title">Checked In Today</h5>
                        <h2 class="mb-0"><?php echo $stats['checked_in_count']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Checked Out Today</h5>
                        <h2 class="mb-0"><?php echo $stats['checked_out_count']; ?></h2>
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
                                        <th>Check-in Time</th>
                                        <th>Check-out Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="attendanceList">
                                    <?php foreach ($today_attendance as $record): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td><?php echo $record['check_in_time'] ?? '-'; ?></td>
                                        <td><?php echo $record['check_out_time'] ?? '-'; ?></td>
                                        <td>
                                            <?php if (!$record['check_in_time']): ?>
                                            <button class="btn btn-sm btn-success me-2" onclick="markAttendance(<?php echo $record['id']; ?>, 'check_in')">Check In</button>
                                            <?php endif; ?>
                                            <?php if ($record['check_in_time'] && !$record['check_out_time']): ?>
                                            <button class="btn btn-sm btn-warning" onclick="markAttendance(<?php echo $record['id']; ?>, 'check_out')">Check Out</button>
                                            <?php endif; ?>
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

        function markAttendance(employeeId, action) {
            $.ajax({
                url: 'mark_attendance.php',
                method: 'POST',
                data: {
                    employee_id: employeeId,
                    action: action
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
</html>         <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>