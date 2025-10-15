<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Role check: Allow 'admin' or 'superadmin' for access
if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/db.php';

try {
    // Fetch all employees and their linked user's username
    $stmt = $pdo->query("
        SELECT 
            e.*, 
            u.username 
        FROM employees e
        LEFT JOIN users u ON e.user_id = u.id
        ORDER BY e.employee_id
    ");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching employee data: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="/proadmin/assets/images/favicon.png" type="image/x-icon" />
    <!-- Reusing styles from manage_users.php for consistency -->
    <style>
        html {
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        }

        *,
        *::before,
        *::after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background-color: #f8f9fa;
            color: #212529;
            line-height: 1.5;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .logo-container .logo img {
            height: 80px;
            width: auto;
        }

        nav {
            display: flex;
            align-items: center;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-links a {
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
            transition: color 0.2s ease, border-color 0.2s ease;
        }

        .nav-links a:hover {
            color: #0d6efd;
        }

        .nav-links a.active {
            color: #0d6efd;
            border-bottom-color: #0d6efd;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-btn {
            background-color: transparent;
            border: none;
            color: #495057;
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            line-height: 1;
            transition: background-color 0.2s ease, color 0.2s ease;
            text-decoration: none;
        }

        .icon-btn:hover {
            background-color: #f1f3f5;
            color: #0d6efd;
        }

        .main {
            padding: 2rem;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .top-bar h2 {
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
        }

        .table-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.07);
            overflow: hidden;
        }

        .modern-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table th {
            padding: 1rem 1.25rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            border-bottom: 2px solid #dee2e6;
        }

        .modern-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e9ecef;
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        .pill {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 50rem;
        }

        .pill.active {
            color: #0f5132;
            background-color: #d1e7dd;
        }

        .pill.inactive {
            color: #664d03;
            background-color: #fff3cd;
        }

        .action-btn {
            background-color: #0d6efd;
            color: #ffffff;
            border: 1px solid #0d6efd;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
        }

        /* Reusing modal styles from manage_users.php */
        .modal-overlay { display: none; position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.6); z-index: 10000; justify-content: center; align-items: center; padding: 1rem; opacity: 0; transition: opacity 0.3s ease; }
        .modal-overlay.is-visible { display: flex; opacity: 1; }
        .modal-content { background: #ffffff; padding: 0; border-radius: 12px; width: 90%; max-width: 650px; transform: scale(0.95); opacity: 0; transition: transform 0.3s ease, opacity 0.3s ease; overflow: hidden; }
        .modal-overlay.is-visible .modal-content { transform: scale(1); opacity: 1; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; border-bottom: 1px solid #dee2e6; }
        .modal-header h3 { margin: 0; font-size: 1.25rem; }
        .close-modal-btn { background: none; border: none; font-size: 1.75rem; cursor: pointer; }
        .modal-body { padding: 1.5rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { font-size: 0.875rem; margin-bottom: 0.5rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ced4da; border-radius: 8px; font-size: 1rem; }
        .form-actions { padding: 1rem 1.5rem; text-align: right; border-top: 1px solid #dee2e6; background-color: #f8f9fa; }
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 10001; display: flex; flex-direction: column; gap: 10px; }
        .toast { padding: 1rem 1.5rem; border-radius: 8px; opacity: 0; transition: opacity 0.3s, transform 0.3s; transform: translateX(100%); color: #ffffff; }
        .toast.success { background-color: #198754; }
        .toast.error { background-color: #dc3545; }
        .toast.show { opacity: 1; transform: translateX(0); }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <div class="logo"><img src="/admin/assets/images/image.png" alt="ProSpine Logo" /></div>
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php">Manage Users</a>
                <a href="manage_employees.php" class="active">Manage Employees</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
            <a href="../../reception/views/logout.php" class="icon-btn" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
        </div>
    </header>

    <main class="main">
        <div class="top-bar">
            <h2>Manage Employees</h2>
        </div>

        <div class="table-container modern-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Job Title</th>
                        <th>Phone</th>
                        <th>Linked User</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee) : ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$employee['employee_id']) ?></td>
                            <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                            <td><?= htmlspecialchars($employee['job_title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($employee['phone_number'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($employee['username'] ?? 'Not Linked') ?></td>
                            <td>
                                <span class="pill <?= $employee['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $employee['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn edit-employee-btn" data-employee='<?= htmlspecialchars(json_encode($employee), ENT_QUOTES, 'UTF-8') ?>'>Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Edit Employee Modal -->
    <div id="edit-employee-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Employee</h3>
                <button class="close-modal-btn">&times;</button>
            </div>
            <form id="editEmployeeForm">
                <div class="modal-body">
                    <input type="hidden" name="employee_id" id="edit_employee_id">
                    <div class="form-grid">
                        <div class="form-group"><label for="edit_first_name">First Name</label><input type="text" id="edit_first_name" name="first_name" required></div>
                        <div class="form-group"><label for="edit_last_name">Last Name</label><input type="text" id="edit_last_name" name="last_name" required></div>
                        <div class="form-group"><label for="edit_job_title">Job Title</label><input type="text" id="edit_job_title" name="job_title"></div>
                        <div class="form-group"><label for="edit_phone_number">Phone Number</label><input type="tel" id="edit_phone_number" name="phone_number"></div>
                        <div class="form-group"><label for="edit_date_of_birth">Date of Birth</label><input type="date" id="edit_date_of_birth" name="date_of_birth"></div>
                        <div class="form-group"><label for="edit_date_of_joining">Date of Joining</label><input type="date" id="edit_date_of_joining" name="date_of_joining" required></div>
                        <div class="form-group full-width"><label for="edit_address">Address</label><textarea id="edit_address" name="address" rows="2"></textarea></div>
                        <div class="form-group"><label for="edit_is_active">Status</label><select id="edit_is_active" name="is_active" required><option value="1">Active</option><option value="0">Inactive</option></select></div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>

    <script src="../../reception/js/theme.js"></script>
    <script src="../js/manage_employees.js"></script>
</body>

</html>