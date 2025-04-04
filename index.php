<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .attendance-card {
            transition: transform 0.2s;
        }
        .attendance-card:hover {
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
            <a class="navbar-brand" href="#"><i class="fas fa-calendar-check"></i> Attendance System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Today's Attendance</h5>
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
                                    <!-- Attendance data will be loaded here -->
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