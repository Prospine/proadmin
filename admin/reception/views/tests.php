<?php

declare(strict_types=1);
session_start();

// -------------------------
// Error Reporting (Dev Only)
// -------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// -------------------------
// Auth / Session Checks
// -------------------------
if (!isset($_SESSION['uid'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/auth.php';
require_once '../../common/db.php';

// -------------------------
// Branch Restriction
// -------------------------
$branchId = $_SESSION['branch_id'] ?? null;
if (!$branchId) {
    http_response_code(403);
    exit('Branch not assigned.');
}

try {
    // Fetch all tests for the branch
    $stmt = $pdo->prepare("
    SELECT 
        t.* 
    FROM tests t
    WHERE t.branch_id = :branch_id
    ORDER BY t.created_at DESC
");
    $stmt->execute([':branch_id' => $branchId]);
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Fetch branch name
    $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmtBranch->execute(['branch_id' => $branchId]);
    $branchName = $stmtBranch->fetchColumn() ?? '';
} catch (PDOException $e) {
    die("Error fetching test records: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tests</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/test.css">

    <style>

    </style>

</head>

<body>
    <header>
        <div class="logo-container">
            <img src="../../assets/images/image.png" alt="Pro Physio Logo" class="logo" />
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a href="registration.php">Registration</a>
                <a href="patients.php">Patients</a>
                <a href="appointments.php">Appointments</a>
                <a href="billing.php">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php" class="active">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"><?php echo htmlspecialchars($branchName, ENT_QUOTES, 'UTF-8'); ?> Branch</div>
            <div class="icon-btn" id="theme-toggle">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </div>
            <div class="icon-btn icon-btn2" title="Notifications" onclick="openNotif()">🔔</div>
            <div class="profile" onclick="openForm()">S</div>
        </div>
    </header>

    <div class="menu" id="myMenu"> <span class="closebtn" onclick="closeForm()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="#">Profile</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="notification" id="myNotif"> <span class="closebtn" onclick="closeNotif()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="changelog.html" class="active2">View Changes (1) </a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="dashboard-container">
            <h2>Tests Overview</h2>
            <!-- Table -->
            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th>Test ID</th>
                            <th>Name</th>
                            <th>Test Name</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Discount</th>
                            <th>Due Amount</th>
                            <th>Payment Status</th>
                            <th>Test Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $row): ?>
                            <tr>
                                <td><?= (int)$row['test_id'] ?></td>
                                <td><?= htmlspecialchars($row['patient_name']) ?></td>
                                <td><?= htmlspecialchars($row['test_name']) ?></td>
                                <td>₹<?= number_format((float)$row['total_amount'], 2) ?></td>
                                <td>₹<?= number_format((float)$row['advance_amount'], 2) ?></td>
                                <td>₹<?= number_format((float)$row['discount'], 2) ?></td>
                                <td>₹<?= number_format((float)$row['due_amount'], 2) ?></td>
                                <td>
                                    <span class="pill <?php echo strtolower($row['payment_status']); ?>">
                                        <?php echo ucfirst($row['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="pill <?php echo strtolower($row['test_status']); ?>">
                                        <?php echo ucfirst($row['test_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn open-drawer" data-id="<?= (int)$row['test_id'] ?>">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toast-container"></div>


    <div id="test-drawer" class="drawer">
        <div class="drawer-header">
            <h3>Test Details</h3>
            <button class="close-drawer">&times;</button>
        </div>
        <div class="drawer-content">
            <!-- Dynamic content goes here -->
        </div>
    </div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/test.js"></script>
</body>

</html>