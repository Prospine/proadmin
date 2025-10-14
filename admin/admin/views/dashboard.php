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
    <link rel="stylesheet" href="../../reception/css/dashboard.css">
    <link rel="stylesheet" href="../../reception/css/dark.css">
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
            margin-bottom: 1.5rem;
        }

        .action-card .action-btn {
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <div class="logo"><img src="/admin/assets/images/image.png" alt="ProSpine Logo"></div>
        </div>
        <div class="nav-actions">
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
            <a href="../../reception/views/logout.php" class="icon-btn" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
        </div>
    </header>

    <main class="main">
        <div class="welcome-message">
            <h1>Welcome, Admin!</h1>
            <p>This is your central hub for managing system-wide settings and users. Select an option below to get started.</p>

            <p class="msg">
            <h4><i class="fa-solid fa-circle-info"></i> This is a Temporary Page</p>
        </div>

        <div class="action-card">
            <h3>User Management</h3>
            <p>Add, edit, and manage user accounts, roles, and permissions across all branches.</p>
            <a href="manage_users.php" class="action-btn">Manage Users</a>
        </div>
    </main>

    <script src="../../reception/js/theme.js"></script>
</body>

</html>