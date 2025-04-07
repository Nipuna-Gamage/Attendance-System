<?php
require_once 'config/session_config.php';
require_once 'config/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user role
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Check if user has permission to view reports
if ($user['role'] != 'admin' && $user['role'] != 'manager') {
    header('Location: index.php');
    exit();
}

// Get date range from request parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Set page title
$page_title = "Attendance Reports";

// Include header
include 'includes/header.php';

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

<div class="row mb-4">
    <div class="col">
        <h2>Attendance Reports</h2>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <a href="export_report.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                       class="btn btn-success">
                        <i class="fas fa-file-export"></i> Export CSV
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

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
                        <td><?php echo $record['date'] ? date('Y-m-d', strtotime($record['date'])) : 'Not Marked'; ?></td>
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

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>