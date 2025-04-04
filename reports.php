<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $time = $_POST['time'] ?? date('H:i:s');

    if (!$employee_id || !$action) {
        http_response_code(400);
        exit('Missing required parameters');
    }

    try {
        // Check if user is manager or admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Only allow managers and admins to mark attendance for others
        if (($user['role'] != 'manager' && $user['role'] != 'admin') && $employee_id != $_SESSION['user_id']) {
            http_response_code(403);
            exit('Unauthorized to mark attendance for others');
        }

        // Check if attendance record exists for the date
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $date]);
        $attendance = $stmt->fetch();

        if ($action === 'check_in') {
            // Check if employee is marked as absent
            if ($attendance && $attendance['status'] == 'Absent') {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot check in after being marked as absent']);
                exit();
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_in_time = ?, status = 'Present' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, status) VALUES (?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time]);
            }
        } elseif ($action === 'check_out') {
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = ? WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record with both check-in and check-out times
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, check_out_time, status) VALUES (?, ?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time, $time]);
            }
        } elseif ($action === 'mark_absent') {
            // Only managers and admins can mark absence
            if ($user['role'] != 'manager' && $user['role'] != 'admin') {
                http_response_code(403);
                exit('Unauthorized to mark absence');
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET status = 'Absent' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$employee_id, $date]);
            } else {
                // Create new record with Absent status
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'Absent')");
                $stmt->execute([$employee_id, $date]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    exit('Method not allowed');
}
?> <?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $time = $_POST['time'] ?? date('H:i:s');

    if (!$employee_id || !$action) {
        http_response_code(400);
        exit('Missing required parameters');
    }

    try {
        // Check if user is manager or admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Only allow managers and admins to mark attendance for others
        if (($user['role'] != 'manager' && $user['role'] != 'admin') && $employee_id != $_SESSION['user_id']) {
            http_response_code(403);
            exit('Unauthorized to mark attendance for others');
        }

        // Check if attendance record exists for the date
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $date]);
        $attendance = $stmt->fetch();

        if ($action === 'check_in') {
            // Check if employee is marked as absent
            if ($attendance && $attendance['status'] == 'Absent') {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot check in after being marked as absent']);
                exit();
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_in_time = ?, status = 'Present' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, status) VALUES (?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time]);
            }
        } elseif ($action === 'check_out') {
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = ? WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record with both check-in and check-out times
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, check_out_time, status) VALUES (?, ?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time, $time]);
            }
        } elseif ($action === 'mark_absent') {
            // Only managers and admins can mark absence
            if ($user['role'] != 'manager' && $user['role'] != 'admin') {
                http_response_code(403);
                exit('Unauthorized to mark absence');
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET status = 'Absent' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$employee_id, $date]);
            } else {
                // Create new record with Absent status
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'Absent')");
                $stmt->execute([$employee_id, $date]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    exit('Method not allowed');
}
?> <?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $time = $_POST['time'] ?? date('H:i:s');

    if (!$employee_id || !$action) {
        http_response_code(400);
        exit('Missing required parameters');
    }

    try {
        // Check if user is manager or admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Only allow managers and admins to mark attendance for others
        if (($user['role'] != 'manager' && $user['role'] != 'admin') && $employee_id != $_SESSION['user_id']) {
            http_response_code(403);
            exit('Unauthorized to mark attendance for others');
        }

        // Check if attendance record exists for the date
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $date]);
        $attendance = $stmt->fetch();

        if ($action === 'check_in') {
            // Check if employee is marked as absent
            if ($attendance && $attendance['status'] == 'Absent') {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot check in after being marked as absent']);
                exit();
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_in_time = ?, status = 'Present' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, status) VALUES (?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time]);
            }
        } elseif ($action === 'check_out') {
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = ? WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record with both check-in and check-out times
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, check_out_time, status) VALUES (?, ?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time, $time]);
            }
        } elseif ($action === 'mark_absent') {
            // Only managers and admins can mark absence
            if ($user['role'] != 'manager' && $user['role'] != 'admin') {
                http_response_code(403);
                exit('Unauthorized to mark absence');
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET status = 'Absent' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$employee_id, $date]);
            } else {
                // Create new record with Absent status
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'Absent')");
                $stmt->execute([$employee_id, $date]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    exit('Method not allowed');
}
?> <?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $time = $_POST['time'] ?? date('H:i:s');

    if (!$employee_id || !$action) {
        http_response_code(400);
        exit('Missing required parameters');
    }

    try {
        // Check if user is manager or admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Only allow managers and admins to mark attendance for others
        if (($user['role'] != 'manager' && $user['role'] != 'admin') && $employee_id != $_SESSION['user_id']) {
            http_response_code(403);
            exit('Unauthorized to mark attendance for others');
        }

        // Check if attendance record exists for the date
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $date]);
        $attendance = $stmt->fetch();

        if ($action === 'check_in') {
            // Check if employee is marked as absent
            if ($attendance && $attendance['status'] == 'Absent') {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot check in after being marked as absent']);
                exit();
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_in_time = ?, status = 'Present' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, status) VALUES (?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time]);
            }
        } elseif ($action === 'check_out') {
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = ? WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record with both check-in and check-out times
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, check_out_time, status) VALUES (?, ?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time, $time]);
            }
        } elseif ($action === 'mark_absent') {
            // Only managers and admins can mark absence
            if ($user['role'] != 'manager' && $user['role'] != 'admin') {
                http_response_code(403);
                exit('Unauthorized to mark absence');
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET status = 'Absent' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$employee_id, $date]);
            } else {
                // Create new record with Absent status
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'Absent')");
                $stmt->execute([$employee_id, $date]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    exit('Method not allowed');
}
?> <?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $date = $_POST['date'] ?? date('Y-m-d');
    $time = $_POST['time'] ?? date('H:i:s');

    if (!$employee_id || !$action) {
        http_response_code(400);
        exit('Missing required parameters');
    }

    try {
        // Check if user is manager or admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Only allow managers and admins to mark attendance for others
        if (($user['role'] != 'manager' && $user['role'] != 'admin') && $employee_id != $_SESSION['user_id']) {
            http_response_code(403);
            exit('Unauthorized to mark attendance for others');
        }

        // Check if attendance record exists for the date
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $date]);
        $attendance = $stmt->fetch();

        if ($action === 'check_in') {
            // Check if employee is marked as absent
            if ($attendance && $attendance['status'] == 'Absent') {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot check in after being marked as absent']);
                exit();
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_in_time = ?, status = 'Present' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, status) VALUES (?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time]);
            }
        } elseif ($action === 'check_out') {
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET check_out_time = ? WHERE employee_id = ? AND date = ?");
                $stmt->execute([$time, $employee_id, $date]);
            } else {
                // Create new record with both check-in and check-out times
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, check_in_time, check_out_time, status) VALUES (?, ?, ?, ?, 'Present')");
                $stmt->execute([$employee_id, $date, $time, $time]);
            }
        } elseif ($action === 'mark_absent') {
            // Only managers and admins can mark absence
            if ($user['role'] != 'manager' && $user['role'] != 'admin') {
                http_response_code(403);
                exit('Unauthorized to mark absence');
            }
            
            if ($attendance) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET status = 'Absent' WHERE employee_id = ? AND date = ?");
                $stmt->execute([$employee_id, $date]);
            } else {
                // Create new record with Absent status
                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'Absent')");
                $stmt->execute([$employee_id, $date]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    exit('Method not allowed');
}
?> 