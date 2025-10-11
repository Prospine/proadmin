<?php

declare(strict_types=1);
// session_start(); // Session might not be needed if accessed directly, but good to have if you add auth later

// -------------------------
// Auth / Session Checks (currently commented out for direct access)
// -------------------------
// if (!isset($_SESSION['uid'])) {
//     header('Location: ../../login.php');
//     exit();
// }

require_once '../../common/db.php';

// Get registration_id from URL
if (!isset($_GET['registration_id']) || !is_numeric($_GET['registration_id'])) {
    die("Invalid Request: Missing or invalid registration ID.");
}
$registrationId = (int)$_GET['registration_id'];

// --- DATA FETCHING ---

// 1. Fetch registration data
$stmtReg = $pdo->prepare("SELECT * FROM registration WHERE registration_id = :id LIMIT 1");
$stmtReg->execute(['id' => $registrationId]);
$registration = $stmtReg->fetch(PDO::FETCH_ASSOC);

if (!$registration) {
    die("Record not found for the given registration ID.");
}

// 2. Fetch the full details for the branch associated with this registration
$branchId = $registration['branch_id'];
$stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
$stmtBranch->execute([':branch_id' => $branchId]);
$branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);

if (!$branchDetails) {
    die("Branch details could not be found for this record.");
}

// Define branch name for reuse
$branchName = $branchDetails['branch_name'] ?? 'Unknown Branch';

// Format today’s date
$today = date("d-m-Y");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - <?= htmlspecialchars($branchName) ?></title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/schedule.css">
</head>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f8f8f8;
        margin: 0;
        padding: 20px;
    }

    header {
        padding: 0;
        margin-bottom: 30px;
    }

    .bill-container {
        max-width: 800px;
        margin: auto;
        background-color: #fff;
        border: 1px solid #ccc;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        padding: 30px;
        border-radius: 8px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        min-height: 80px;
    }

    .logo,
    .logo2 {
        flex-basis: 200px;
        flex-shrink: 0;
    }

    .logo img,
    .logo2 img {
        max-width: 100%;
        max-height: 80px;
        object-fit: contain;
    }

    .logo-placeholder {
        width: 150px;
        height: 80px;
        border: 1px dashed #999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        color: #666;
        border-radius: 5px;
        background-color: #f2f2f2;
    }

    .clinic-info {
        text-align: center;
        margin-bottom: 30px;
    }

    .clinic-info h3 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 1.2em;
    }

    .clinic-info p {
        margin: 3px 0;
        font-size: 14px;
        color: #333;
    }

    h2.bill-title {
        margin: 0 auto;
        color: #444;
        text-align: center;
        margin-top: -30px;
        font-weight: 600;
    }

    .date {
        text-align: right;
        font-size: 14px;
        margin-bottom: 20px;
    }

    table.details,
    table.payment {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    table.details td,
    table.payment td {
        padding: 10px;
        border: 1px solid #ddd;
        font-size: 14px;
    }

    table.details tr td:first-child,
    table.payment tr td:first-child {
        font-weight: bold;
        width: 30%;
        background-color: #f2f2f2;
    }

    .footer {
        text-align: center;
        font-size: 13px;
        color: #777;
        margin-top: 30px;
        border-top: 1px dashed #ccc;
        padding-top: 10px;
    }

    .print-btn {
        text-align: center;
        margin-top: 20px;
    }

    .print-btn button {
        padding: 10px 25px;
        font-size: 14px;
        border: none;
        background-color: #2b7de9;
        color: #fff;
        cursor: pointer;
        border-radius: 5px;
        transition: background 0.3s;
    }

    .print-btn button:hover {
        background-color: #155bc0;
    }

    @media print {
        header .logo img {
            display: none;
        }

        .print-btn {
            display: none;
        }

        body {
            margin-top: 0px;
            background: #fff;
        }

        .bill-container {
            box-shadow: none;
            border: 1px solid #000;
        }

        .icon-btn {
            display: none;
        }
    }
</style>

<body>
    <header>
        <div class="logo-container">
            <div class="logo">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo">
                <?php else: ?>
                    <div class="logo-placeholder">Primary Logo N/A</div>
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
            <div class="icon-btn" title="Navigation" onclick="window.location.href = 'registration.php';"><i class="fa-solid fa-arrow-left"> </i>&nbsp; Back</div>
        </div>
    </header>

    <div class="bill-container">
        <div class="header">
            <div class="logo">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo">
                <?php else: ?>
                    <div class="logo-placeholder">Primary Logo N/A</div>
                <?php endif; ?>
            </div>
            <div class="logo2">
                <?php if (!empty($branchDetails['logo_secondary_path'])): ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_secondary_path']) ?>" alt="Secondary Clinic Logo">
                <?php else: ?>
                    <div class="logo-placeholder" style="width:200px;">Secondary Logo N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <h2 class="bill-title">Prospine Siliguri</h2>

        <div class="clinic-info">
            <p>
                <?= htmlspecialchars($branchDetails['address_line_1'] ?? '') ?>
                <?= !empty($branchDetails['address_line_2']) ? ', ' . htmlspecialchars($branchDetails['address_line_2']) : '' ?>
                <?= !empty($branchDetails['city']) ? ', ' . htmlspecialchars($branchDetails['city']) : '' ?>
            </p>
            <p>
                Phone: <?= htmlspecialchars($branchDetails['phone_primary'] ?? '') ?>
                <?= !empty($branchDetails['phone_secondary']) ? ', ' . htmlspecialchars($branchDetails['phone_secondary']) : '' ?>
            </p>
            <br>
            <h3 style="text-decoration: underline;">Consultation Bill</h3>
        </div>

        <p class="date"><strong>Date:</strong> <?= htmlspecialchars($today) ?></p>

        <table class="details">
            <tr>
                <td>Patient Name</td>
                <td><?= htmlspecialchars($registration['patient_name']) ?></td>
            </tr>
            <tr>
                <td>Address</td>
                <td><?= htmlspecialchars($registration['address'] ?? '-') ?></td>
            </tr>
            <tr>
                <td>Mobile No</td>
                <td><?= htmlspecialchars($registration['phone_number']) ?></td>
            </tr>
        </table>

        <table class="payment">
            <tr>
                <td>Consultation Amount</td>
                <td>₹ <?= number_format((float)$registration['consultation_amount'], 2) ?></td>
            </tr>
            <tr>
                <td>Payment Method</td>
                <td><?= ucfirst($registration['payment_method']) ?></td>
            </tr>
        </table>

        <div class="footer">
            <p>Thank you for visiting us!</p>
        </div>

        <div class="print-btn">
            <button onclick="window.print()"><i class="fa fa-print"></i> Print Bill</button>
        </div>
    </div>
</body>

</html>