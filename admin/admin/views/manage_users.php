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
    // Fetch all users EXCEPT superadmins, and their branch names
    // LEFT JOIN with employees table to get personal details
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, u.role, u.is_active, u.branch_id, b.branch_name,
               e.employee_id, e.first_name, e.last_name
        FROM users u
        LEFT JOIN employees e ON u.id = e.user_id
        LEFT JOIN branches b ON u.branch_id = b.branch_id
        WHERE u.role != 'superadmin'
        ORDER BY u.id
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all branches for the dropdown in the edit modal
    $branches = $pdo->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all unassigned employees for the dropdown
    $unassignedEmployees = $pdo->query("SELECT employee_id, first_name, last_name FROM employees WHERE user_id IS NULL ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching user data: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="/proadmin/assets/images/favicon.png" type="image/x-icon" />
    <style>
        /* --- Global & Body --- */
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

        /* --- Header --- */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .logo-container {
            display: flex;
            align-items: center;
        }

        .logo img {
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

        .icon-btn i {
            display: block;
        }

        /* --- Main Content --- */
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
            color: #212529;
            margin: 0;
        }

        /* --- Table --- */
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

        .modern-table thead {
            background-color: #f8f9fa;
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
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            color: #495057;
        }

        .modern-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        .modern-table tbody tr:hover {
            background-color: #f1f3f5;
        }

        .pill {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.8rem;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 50rem;
        }

        .pill.consulted { /* Active */
            color: #0f5132;
            background-color: #d1e7dd;
        }

        .pill.cancelled { /* Inactive */
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
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .action-btn:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .action-btn i {
            margin-right: 0.5rem;
        }

        .action-btn.secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            color: #ffffff;
        }
        .action-btn.secondary:hover {
            background-color: #5c636a;
            border-color: #565e64;
        }


        /* --- Modal --- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.is-visible {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: #ffffff;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 650px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            transform: scale(0.95);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
            overflow: hidden;
        }

        .modal-overlay.is-visible .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: #212529;
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            line-height: 1;
            color: #6c757d;
            cursor: pointer;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .close-modal-btn:hover {
            transform: rotate(90deg);
            color: #212529;
        }

        #editUserForm {
            display: contents; /* Allows form to be a direct child of modal-content for layout */
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 8px;
            background-color: #f8f9fa;
            color: #212529;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #86b7fe;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
        }

        /* --- Password Toggle Icon --- */
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-container .toggle-password-btn {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .password-input-container .toggle-password-btn:hover {
            color: #212529;
        }

        .form-actions {
            padding: 1rem 1.5rem;
            text-align: right;
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .form-actions .action-btn {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }

        /* --- Toast Notifications --- */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateX(100%);
            color: #ffffff;
            font-weight: 500;
        }

        .toast.success {
            background-color: #198754;
        }

        .toast.error {
            background-color: #dc3545;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        /* --- Message Box Styling --- */
        .msg {
            background-color: #fff3cd; /* Light yellow background */
            color: #664d03; /* Dark yellow text */
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            line-height: 1.6;
        }
        .msg h4 {
            margin-top: 0;
            color: #533f03;
            font-size: 1.1rem;
        }
        .msg ul {
            padding-left: 20px;
            margin-top: 0.5rem;
            margin-bottom: 0;
        }

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
                <a href="manage_users.php" class="active">Manage Users</a>
                <a href="manage_employees.php">Manage Employees</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
            <a href="../../reception/views/logout.php" class="icon-btn" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
        </div>
    </header>

    <main class="main">
        <div class="msg">
            <h4><i class="fa-solid fa-circle-info"></i> Important Instructions & Notes</h4>
            <p>This is a temporary page for adding users and changing passwords. Please follow the instructions below carefully:</p>
            <ul>
                <li><strong>Do NOT edit the first user (superadmin).</strong></li>
                <li><strong>For the second user (Reception):</strong> Please use the "Edit" and "Password" buttons to perform the following actions:
                    <ul>
                        <li>Change the username to "Reception".</li>
                        <li>Update the email address to a valid one.</li>
                        <li>Set a new, strong password. The current one is weak. Please notify the reception staff of the new password.</li>
                    </ul>
                </li>
                <li><strong>For Yourself (Admin):</strong> Please use the "Create New User" button to add an account for your own use with the 'Admin' role.</li>
                <li><strong>Role-Based Login:</strong> Please note that users with the 'Admin' role can only log into the Admin panel. To access the Reception dashboard, you must log in with the 'Reception' user's credentials.</li>
            </ul>
        </div>
    <main class="main">
        <div class="top-bar">
            <h2>User Management</h2>
            <div>
                <button id="create-employee-btn" class="action-btn secondary" style="margin-right: 10px;">
                    <i class="fa-solid fa-user-tie"></i> Create New Employee
                </button>
                <button id="create-user-btn" class="action-btn">
                    <i class="fa-solid fa-user-plus"></i> Create New User
                </button>
            </div>
        </div>

        <div class="table-container modern-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee Name</th>
                        <th>Login/Username</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$user['id']) ?></td>
                            <td>
                                <?php if ($user['first_name']): ?>
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                <?php else: ?>
                                    <span style="color: #9ca3af;">Not Linked</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                            <td><?= htmlspecialchars($user['branch_name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="pill <?= $user['is_active'] ? 'consulted' : 'cancelled' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <button class="action-btn edit-user-btn" data-user='<?= htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') ?>' style="margin-right: 5px;">Edit</button>
                                <button class="action-btn secondary change-password-btn" data-userid="<?= $user['id'] ?>">Password</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Edit User Modal -->
    <div id="edit-user-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <button class="close-modal-btn">&times;</button>
            </div>
            <form id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="reception">Reception</option>
                                <option value="doctor">Doctor</option>
                                <option value="jrdoctor">Jr. Doctor</option>
                                <option value="admin">Admin</option>
                                
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="branch_id">Branch</label>
                            <select id="branch_id" name="branch_id">
                                <option value="">None</option>
                                <?php foreach ($branches as $branch) : ?>
                                    <option value="<?= $branch['branch_id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="is_active">Status</label>
                            <select id="is_active" name="is_active" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="employee_id">Link to Employee Profile</label>
                            <select id="employee_id" name="employee_id">
                                <option value="">-- Do not link --</option>
                                <!-- Current employee will be added here by JS -->
                                <optgroup label="Unassigned Employees">
                                    <?php foreach ($unassignedEmployees as $employee) : ?>
                                        <option value="<?= $employee['employee_id'] ?>">
                                            <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="change-password-modal" class="modal-overlay">
        <div class="modal-content" style="max-width: 450px;">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button class="close-modal-btn">&times;</button>
            </div>
            <form id="changePasswordForm">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="password_user_id">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-input-container">
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                            <i class="fa-solid fa-eye toggle-password-btn"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-input-container">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            <i class="fa-solid fa-eye toggle-password-btn"></i>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn">Save Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="create-user-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New User</h3>
                <button class="close-modal-btn">&times;</button>
            </div>
            <form id="createUserForm">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="create_username">Username</label>
                            <input type="text" id="create_username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="create_password">Password</label>
                            <div class="password-input-container">
                                <input type="password" id="create_password" name="password" required minlength="8">
                                <i class="fa-solid fa-eye toggle-password-btn"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="create_email">Email</label>
                            <input type="email" id="create_email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="create_role">Role</label>
                            <select id="create_role" name="role" required>
                                <option value="reception">Reception</option>
                                <option value="doctor">Doctor</option>
                                <option value="jrdoctor">Jr. Doctor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="create_branch_id">Branch</label>
                            <select id="create_branch_id" name="branch_id">
                                <option value="">None</option>
                                <?php foreach ($branches as $branch) : ?>
                                    <option value="<?= $branch['branch_id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="create_is_active">Status</label>
                            <select id="create_is_active" name="is_active" required>
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Employee Modal -->
    <div id="create-employee-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Employee</h3>
                <button class="close-modal-btn">&times;</button>
            </div>
            <form id="createEmployeeForm">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="emp_first_name">First Name</label>
                            <input type="text" id="emp_first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="emp_last_name">Last Name</label>
                            <input type="text" id="emp_last_name" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label for="emp_phone_number">Phone Number</label>
                            <input type="tel" id="emp_phone_number" name="phone_number">
                        </div>
                        <div class="form-group">
                            <label for="emp_date_of_joining">Date of Joining</label>
                            <input type="date" id="emp_date_of_joining" name="date_of_joining" required>
                        </div>
                        <div class="form-group full-width">
                            <label for="emp_address">Address</label>
                            <input type="text" id="emp_address" name="address">
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn">Create Employee</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>

    <script src="../../reception/js/theme.js"></script>
    <script src="../js/manage_users.js"></script>
</body>

</html>