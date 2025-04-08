<?php
require_once 'config/session_config.php'; // This already starts the session
require_once 'config/config.php';

// Remove session_start() from here since it's handled in session_config.php

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user role
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Check if user has permission to manage employees
if ($user['role'] != 'admin' && $user['role'] != 'manager') {
    header('Location: index.php');
    exit();
}

// Set page title
$page_title = "Manage Employees";

// Include header
include 'includes/header.php';

// Handle employee operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO employees (name, email) VALUES (?, ?)");
                $stmt->execute([$_POST['name'], $_POST['email']]);
                break;
            case 'edit':
                $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$_POST['name'], $_POST['email'], $_POST['id']]);
                break;
            case 'delete':
                // First delete attendance records for this employee
                $stmt = $pdo->prepare("DELETE FROM attendance WHERE employee_id = ?");
                $stmt->execute([$_POST['id']]);
                
                // Then delete the employee
                $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                break;
        }
        header('Location: employees.php');
        exit();
    }
}

// Get all employees
$stmt = $pdo->query("SELECT * FROM employees ORDER BY name");
$employees = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col">
        <h2>Manage Employees</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fas fa-plus"></i> Add New Employee
        </button>
    </div>
</div>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo $employee['id']; ?></td>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editEmployee(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['name']); ?>', '<?php echo htmlspecialchars($employee['email']); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteEmployee(<?php echo $employee['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Employee Modal -->
<div class="modal fade" id="deleteEmployeeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete this employee?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editEmployee(id, name, email) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
    }

    function deleteEmployee(id) {
        document.getElementById('delete_id').value = id;
        new bootstrap.Modal(document.getElementById('deleteEmployeeModal')).show();
    }
</script>

<?php include 'includes/footer.php'; ?>