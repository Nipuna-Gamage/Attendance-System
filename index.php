<?php
require_once 'config/session_config.php'; // This already starts the session
require_once 'config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user role
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get selected date or default to today
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

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

// Get attendance based on user role and selected date
if ($user['role'] == 'manager' || $user['role'] == 'admin') {
    // For managers and admins, show all employees
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, a.check_in_time, a.check_out_time, a.status 
        FROM employees e 
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
        ORDER BY e.name
    ");
    $stmt->execute([$selected_date]);
} else {
    // For regular employees, show only their attendance
    $stmt = $pdo->prepare("
        SELECT e.id, e.name, a.check_in_time, a.check_out_time, a.status 
        FROM employees e 
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = ?
        WHERE e.id = ?
        ORDER BY e.name
    ");
    $stmt->execute([$selected_date, $_SESSION['user_id']]);
}
$today_attendance = $stmt->fetchAll();

// Set page title
$page_title = "Dashboard";

// Include header
include 'includes/header.php';
?>

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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Attendance for <?php echo date('F d, Y', strtotime($selected_date)); ?></h5>
                <div>
                    <form class="d-flex" method="GET">
                        <input type="date" class="form-control me-2" name="date" value="<?php echo $selected_date; ?>">
                        <button type="submit" class="btn btn-primary">Select Date</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Status</th>
                                <th>Check-in Time</th>
                                <th>Check-out Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="attendanceList">
                            <?php foreach ($today_attendance as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['name']); ?></td>
                                <td><?php 
                                    if (isset($record['status'])) {
                                        echo $record['status'];
                                    } elseif ($record['check_in_time']) {
                                        echo 'Present';
                                    } else {
                                        echo 'Not Marked';
                                    }
                                ?></td>
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
                                <td>
                                    <?php if (!$record['check_in_time']): ?>
                                    <?php if ($record['status'] != 'Absent'): ?>
                                    <button class="btn btn-sm btn-success me-2" onclick="showTimeInput(<?php echo $record['id']; ?>, 'check_in', '<?php echo $selected_date; ?>')">Check In</button>
                                    <?php endif; ?>
                                    <?php if ($user['role'] == 'manager' || $user['role'] == 'admin'): ?>
                                    <?php if ($record['status'] != 'Absent'): ?>
                                    <button class="btn btn-sm btn-danger" onclick="markAbsent(<?php echo $record['id']; ?>, '<?php echo $selected_date; ?>')">Mark Absent</button>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>Marked Absent</button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($record['check_in_time'] && !$record['check_out_time']): ?>
                                    <button class="btn btn-sm btn-warning" onclick="showTimeInput(<?php echo $record['id']; ?>, 'check_out', '<?php echo $selected_date; ?>')">Check Out</button>
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

<!-- Time Input Modal -->
<div class="modal fade" id="timeInputModal" tabindex="-1" aria-labelledby="timeInputModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timeInputModalLabel">Enter Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="timeInputForm">
                    <input type="hidden" id="employeeId" name="employee_id">
                    <input type="hidden" id="actionType" name="action">
                    <input type="hidden" id="attendanceDate" name="date">
                    <div class="mb-3">
                        <label for="timeInput" class="form-label">Time</label>
                        <input type="time" class="form-control" id="timeInput" name="time" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitTimeInput()">Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let timeInputModal;
    
    function loadAttendance() {
        const urlParams = new URLSearchParams(window.location.search);
        const date = urlParams.get('date') || '<?php echo date('Y-m-d'); ?>';
        
        $.ajax({
            url: 'get_attendance.php',
            method: 'GET',
            data: { date: date },
            success: function(response) {
                $('#attendanceList').html(response);
            },
            error: function(xhr, status, error) {
                console.error("Error loading attendance:", error);
            }
        });
    }

    function showTimeInput(employeeId, action, date) {
        document.getElementById('employeeId').value = employeeId;
        document.getElementById('actionType').value = action;
        document.getElementById('attendanceDate').value = date;
        
        // Set default time to current time
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        document.getElementById('timeInput').value = `${hours}:${minutes}`;
        
        // Show the modal
        timeInputModal = new bootstrap.Modal(document.getElementById('timeInputModal'));
        timeInputModal.show();
    }
    
    function submitTimeInput() {
        const employeeId = document.getElementById('employeeId').value;
        const action = document.getElementById('actionType').value;
        const date = document.getElementById('attendanceDate').value;
        const time = document.getElementById('timeInput').value;
        
        $.ajax({
            url: 'mark_attendance.php',
            method: 'POST',
            data: {
                employee_id: employeeId,
                action: action,
                date: date,
                time: time
            },
            success: function(response) {
                console.log("Attendance marked successfully:", response);
                timeInputModal.hide();
                loadAttendance();
            },
            error: function(xhr, status, error) {
                console.error("Error marking attendance:", error);
                alert("Failed to mark attendance. Please try again.");
            }
        });
    }
    
    function markAbsent(employeeId, date) {
        if (confirm('Are you sure you want to mark this employee as absent?')) {
            $.ajax({
                url: 'mark_attendance.php',
                method: 'POST',
                data: {
                    employee_id: employeeId,
                    action: 'mark_absent',
                    date: date
                },
                success: function(response) {
                    console.log("Absence marked successfully:", response);
                    loadAttendance();
                },
                error: function(xhr, status, error) {
                    console.error("Error marking absence:", error);
                    alert("Failed to mark absence. Please try again.");
                }
            });
        }
    }

    $(document).ready(function() {
        loadAttendance();
        setInterval(loadAttendance, 30000); // Refresh every 30 seconds
    });
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>