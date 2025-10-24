<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Role check: Allow 'admin' or 'superadmin'
if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/db.php';

$branchId = $_SESSION['branch_id'] ?? null;
$branchName = 'Admin'; // Default name

if ($branchId) {
    try {
        $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id LIMIT 1");
        $stmtBranch->execute([':branch_id' => $branchId]);
        $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
        if ($branchDetails) {
            $branchName = $branchDetails['branch_name'];
        }
    } catch (PDOException $e) {
        // Log error but continue with default branch name
        error_log("Could not fetch branch name: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- FIX: Corrected CSS paths to point to the reception/css directory -->
    <link rel="stylesheet" href="../../reception/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <style>
        .main {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 100px);
            text-align: center;
        }

        .welcome-message {
            margin-bottom: 2rem;
        }

        .welcome-message h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .welcome-message p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
        }

        .action-card {
            background: var(--bg-primary);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border-color-primary);
        }

        .action-card h3 {
            margin-top: 0;
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        .action-card p {
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
        }

        .action-card .action-btn {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            text-decoration: none;
            background-color: #6fe8f3;
            color: #000;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            /* FIX: Added transform and box-shadow to the transition for a smooth effect */
            transition: background-color 0.3s ease, transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-card .action-btn:hover {
            background-color: #4dd0e1;
            /* IMPROVEMENT: Move button up for a "lifting" effect */
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .wrapper {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 2rem;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <!-- FIX: Corrected image path to be relative to the web root -->
            <div class="logo"><img src="/proadmin/admin/assets/images/image.png" alt="ProSpine Logo"></div>
        </div>
        <div class="nav-actions">
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
            <!-- FIX: Corrected logout path -->
            <a href="../../../reception/views/logout.php" class="icon-btn" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
        </div>
    </header>

    <main class="main">
        <div class="welcome-message">
            <h1>Welcome, Admin!</h1>
            <p>This is your central hub for managing system-wide settings and users. Select an option below to get started.</p>
            
            <p class="msg">
            <h4><i class="fa-solid fa-circle-info"></i> This is a Temporary Page</p>
        </div>


            <div class="wrapper">
                <div class="action-card">
                    <h3>User Management</h3>
                    <p>Add, edit, and manage user accounts, roles, and permissions across all branches.</p>
                    <a href="manage_users.php" class="action-btn">Manage Users</a>
                </div>

                <div class="action-card">
                    <h3>Employee Managemnt</h3>
                    <p>Add, edit, and manage employee accounts, roles, and permissions across all branches.</p>
                    <a href="manage_employees.php" class="action-btn">Manage Employees</a>
                </div>

                <div class="action-card">
                    <h3>Expense Management</h3>
                    <p>Manage and track all expenses across different branches. </p>
                    <a href="manage_expenses.php" class="action-btn">Manage Expenses</a>
                </div>

                <div class="action-card">
                    <h3>Branch Management</h3>
                    <p>Add, edit, and manage branch details and settings.</p>
                    <a href="manage_branches.php" class="action-btn">Manage Branches</a>
                </div>
            </div>
    </main>

    <script src="../../../reception/js/theme.js"></script>
</body>

</html>