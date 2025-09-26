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
    $stmt = $pdo->prepare("
        SELECT
            p.patient_id,
            r.patient_name,
            r.consultation_amount,
            p.total_amount AS treatment_total_amount,
            p.status AS treatment_status,
            (
                SELECT COALESCE(SUM(amount), 0)
                FROM payments
                WHERE patient_id = p.patient_id
            ) AS total_paid_from_payments
        FROM
            patients p
        JOIN
            registration r ON p.registration_id = r.registration_id
        WHERE
            p.branch_id = :branch_id
        ORDER BY
            p.created_at DESC
    ");
    $stmt->execute([':branch_id' => $branchId]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch branch name (safer method)
    $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmtBranch->execute(['branch_id' => $branchId]);
    $branchName = $stmtBranch->fetchColumn() ?? ''; // CHANGED: Safer fetch method

} catch (PDOException $e) {
    die("Error fetching patient billing data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/billings.css">
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
                <a href="billing.php" class="active">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php">Tests</a>
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
            <div class="top-bar">
                <h2>Billing Overview</h2>
            </div>
            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th class="numeric">Total Bill</th>
                            <th class="numeric">Total Paid</th>
                            <th class="numeric">Outstanding Due</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patients)) : ?>
                            <?php foreach ($patients as $row) : ?>
                                <?php
                                $total_billable = (float)$row['consultation_amount'] + (float)$row['treatment_total_amount'];
                                $total_paid = (float)$row['consultation_amount'] + (float)$row['total_paid_from_payments'];
                                $outstanding_due = $total_billable - $total_paid;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['patient_id']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['patient_name']); ?></td>
                                    <td class="numeric">₹<?php echo number_format($total_billable, 2); ?></td>
                                    <td class="numeric">₹<?php echo number_format($total_paid, 2); ?></td>
                                    <td class="numeric"><strong>₹<?php echo number_format($outstanding_due, 2); ?></strong></td>
                                    <td>
                                        <?php if (!empty($row['treatment_status'])) :
                                            $statusClass = strtolower($row['treatment_status']);
                                        ?>
                                            <span class="pill <?php echo htmlspecialchars($statusClass); ?>">
                                                <?php echo htmlspecialchars(ucfirst($row['treatment_status'])); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="pill pending">Unknown</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="action-btn open-drawer" data-id="<?php echo (int) $row['patient_id']; ?>">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="no-data">No patient billing records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="drawer-overlay" id="drawer-overlay" style="display: none;">
            <div class="drawer-panel" id="drawer-panel">
                <div class="drawer-header">
                    <h2 id="drawer-patient-name">Patient Details</h2>
                    <button id="closeDrawer" class="drawer-close-btn">&times;</button>
                </div>
                <div class="drawer-body" id="drawer-body"></div>
            </div>
        </div>
    </main>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/billings.js"></script>

</body>

</html>