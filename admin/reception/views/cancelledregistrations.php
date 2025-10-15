<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!isset($_SESSION['uid'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/auth.php';
require_once '../../common/db.php';

$branchId = $_SESSION['branch_id'] ?? null;
if (!$branchId) {
    http_response_code(403);
    exit('Branch not assigned.');
}

try {
    // Fetch all cancelled registrations for the branch
    $stmt = $pdo->prepare("
        SELECT registration_id, patient_name, phone_number, consultation_amount, status, refund_status
        FROM registration
        WHERE branch_id = :branch_id AND status = 'closed'
        ORDER BY created_at DESC
    ");
    $stmt->execute([':branch_id' => $branchId]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
} catch (PDOException $e) {
    die("Error fetching cancelled registration records: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cancelled Registrations</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/test.css">
    <style>
        /* --- NEW: Modern Modal Styles --- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 100000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.is-visible {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: var(--bg-primary, #fff);
            border-radius: 12px;
            padding: 1.5rem 2rem;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .modal-overlay.is-visible .modal-content {
            transform: scale(1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            line-height: 1;
            color: var(--text-secondary);
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s, transform 0.2s;
        }

        .close-modal-btn:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .form-grid-condensed {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }

        /* Target the form group containing the textarea to make it span both columns */
        .form-grid-condensed .form-group:has(textarea) {
            grid-column: 1 / -1;
        }

        .form-grid-condensed .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
            color: var(--text-secondary);
        }

        .form-grid-condensed input,
        .form-grid-condensed textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background-color: var(--bg-tertiary);
            color: var(--text-color);
        }

        .form-grid-condensed textarea {
            height: 50px;
        }

        .form-actions {
            margin-top: 1.5rem;
            text-align: right;
        }

        /* --- Original Styles (can be removed if the above replaces them completely) --- */
        /*
        .button-box {
            margin-top: 10px;
            margin-right: 20px;
            height: 70px;
            align-items: center;
            text-align: center;
        }

        */
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <div class="logo">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo">
                <?php endif; ?>
            </div>
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a href="registration.php" class="active">Registration</a>
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
            <div class="icon-btn" title="Settings"><?= htmlspecialchars($branchName) ?> Branch</div>
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
            <div class="profile" onclick="openForm()">R</div>
        </div>
        <div class="hamburger-menu" id="hamburger-menu"><i class="fa-solid fa-bars"></i></div>
    </header>

    <div class="menu" id="myMenu">
        <div class="popup">
            <span class="closebtn" onclick="closeForm()">&times;</span>
            <ul>
                <li><a href="profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                <li class="logout"><a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="dashboard-container">
            <div class="top-bar">
                <h2>Cancelled Registrations</h2>
                <div class="button-box">
                    <button onclick="window.location.href='registration.php'">Active Registrations</button>
                </div>
            </div>

            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Phone</th>
                            <th class="numeric">Consultation Fee</th>
                            <th>Registration Status</th>
                            <th>Refunded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td data-label="Patient Name"><?= htmlspecialchars($reg['patient_name']) ?></td>
                                <td data-label="Phone"><?= htmlspecialchars($reg['phone_number']) ?></td>
                                <td data-label="Consultation Fee" class="numeric">₹<?= number_format((float)$reg['consultation_amount'], 2) ?></td>
                                <td data-label="Registration Status">
                                    <?php if ($reg['refund_status'] !== 'no'): ?>
                                        <span class="pill cancelled" title="Cannot change status after refund is initiated."><?= htmlspecialchars(ucfirst($reg['status'])) ?></span>
                                    <?php else: ?>
                                        <select class="status-select" data-id="<?= (int)$reg['registration_id'] ?>">
                                            <option value="closed" selected>Closed</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Refund Status">
                                    <?php if ($reg['refund_status'] === 'initiated'): ?>
                                        <span class="pill cancelled">Refunded</span>
                                    <?php else: ?>
                                        <span>No</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions">
                                    <?php if ($reg['refund_status'] === 'no' && (float)$reg['consultation_amount'] > 0): ?>
                                        <button class="action-btn refund-btn" data-id="<?= (int)$reg['registration_id'] ?>" data-paid="<?= (float)$reg['consultation_amount'] ?>">Refund</button>
                                    <?php else: ?>
                                        <button class="action-btn" disabled title="Refund already processed or no payment was made."><?= ucfirst($reg['refund_status']) ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="toast-container"></div>

    <!-- Refund Modal -->
    <div id="refund-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Initiate Refund</h3>
                <button class="close-modal-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="refundForm">
                    <input type="hidden" name="registration_id" id="refund_id">
                    <div class="form-grid-condensed">
                        <div class="form-group">
                            <label>Amount Paid</label>
                            <input type="text" id="refund_paid_amount" readonly style="background: var(--bg-tertiary);">
                        </div>
                        <div class="form-group">
                            <label>Refund Amount *</label>
                            <input type="number" name="refund_amount" id="refund_amount_input" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Reason for Refund</label>
                            <textarea name="refund_reason" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-actions"><button type="submit" class="action-btn">Initiate Refund</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/theme.js"></script>
    <script src="../js/nav_toggle.js"></script>
    <script>
        // Simplified popup JS for this page
        function openForm() {
            document.getElementById("myMenu").style.display = "block";
        }

        function closeForm() {
            document.getElementById("myMenu").style.display = "none";
        }
    </script>
    <script src="../js/registration_refund.js"></script>

</body>

</html>