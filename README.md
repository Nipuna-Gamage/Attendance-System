# Attendance Management System

A web-based system for managing employee attendance, tracking check-ins/check-outs, and generating attendance reports.

## Features

- Employee attendance tracking
- Check-in/Check-out management
- Attendance reports and analytics
- Role-based access control (Admin, Manager, Employee)
- Export attendance data to CSV
- Real-time attendance monitoring

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone or download the repository
2. Create a MySQL database named `attendance_system`
3. Import the database schema from `attendance_system.sql`
4. Update database configuration in `config/config.php`
5. Place the files in your web server directory

## Default Login Credentials

- Admin:
  - Username: admin
  - Password: admin123

- Manager:
  - Username: manager
  - Password: manager123

## Directory Structure

```
attendance_system/
├── assets/
│   ├── css/
│   └── js/
├── config/
│   ├── config.php
│   └── session_config.php
├── includes/
│   ├── header.php
│   └── footer.php
└── [PHP files]
```

## Features by User Role

### Admin
- Manage employees
- View all attendance records
- Generate reports
- Export attendance data
- View analytics

### Manager
- View team attendance
- Mark attendance for team
- Generate reports
- View analytics

### Employee
- View own attendance
- Mark attendance (check-in/out)

## License

This project is licensed under the MIT License.

## Support

For support or queries, please contact the system administrator.

## Security

- Password hashing using PHP's password_hash()
- Session-based authentication
- SQL injection prevention using PDO
- XSS prevention using htmlspecialchars()
