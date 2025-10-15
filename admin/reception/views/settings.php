<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Role check:
if (!isset($_SESSION['uid'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/db.php';

$branchId = $_SESSION['branch_id'] ?? null;
$branchName = 'Reception'; // Default name

if ($branchId) {
    try {
        $stmtBranch = $pdo->prepare("SELECT branch_name, logo_primary_path FROM branches WHERE branch_id = :branch_id LIMIT 1");
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
    <title>User Settings</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
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

        .welcome-message h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .welcome-message p {
            font-size: 1.1rem;
            color: var(--text-secondary);
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <div class="logo">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo">
                <?php else: ?>
                    <img src="/admin/assets/images/image.png" alt="ProSpine Logo">
                <?php endif; ?>
            </div>
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a href="registration.php">Registration</a>
                <a href="appointments.php">Appointments</a>
                <a href="patients.php">Patients</a>
                <a href="billing.php">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
            <a href="logout.php" class="icon-btn" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
        </div>
    </header>

    <main class="main">
        <div class="welcome-message">
            <h1>User Settings</h1>
            <p><i class="fa-solid fa-circle-info"></i> This page is under construction.</p>
        </div>
    </main>

    <script src="../js/theme.js"></script>
</body>

</html>