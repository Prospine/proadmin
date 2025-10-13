<?php

declare(strict_types=1);

require_once '../../common/auth.php';
require_once '../../common/db.php';

// Validate patient_id
if (!isset($_GET['patient_id']) || !is_numeric($_GET['patient_id'])) {
    die("Invalid Request.");
}
$patientId = (int)$_GET['patient_id'];

// Patient
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = :id LIMIT 1");
$stmt->execute(['id' => $patientId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$patient) {
    die("Record not found.");
}

// Registration
$registration = null;
if (!empty($patient['registration_id'])) {
    $stmtReg = $pdo->prepare("SELECT * FROM registration WHERE registration_id = :id LIMIT 1");
    $stmtReg->execute(['id' => $patient['registration_id']]);
    $registration = $stmtReg->fetch(PDO::FETCH_ASSOC);
}

// Attendance
$attStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE patient_id = :id");
$attStmt->execute(['id' => $patientId]);
$attendanceCount = (int)$attStmt->fetchColumn();
$treatmentDays   = (int)($patient['treatment_days'] ?? 0);

// Payments (raw rows if you need them later)
$paymentsRawStmt = $pdo->prepare("
    SELECT remarks, mode, amount, payment_date
    FROM payments
    WHERE patient_id = :id
    ORDER BY payment_date ASC
");
$paymentsRawStmt->execute(['id' => $patientId]);
$paymentsRaw = $paymentsRawStmt->fetchAll(PDO::FETCH_ASSOC);

// Payments overview
$overviewStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS entries,
        COALESCE(SUM(amount),0) AS total_paid,
        MAX(payment_date) AS last_date
    FROM payments
    WHERE patient_id = :id
");
$overviewStmt->execute(['id' => $patientId]);
$payOverview = $overviewStmt->fetch(PDO::FETCH_ASSOC) ?: ['entries' => 0, 'total_paid' => 0, 'last_date' => null];

// Build: Group by remark, with nested mode breakdown + period
$byRemark = []; // remark => [entries,total_amount,first_date,last_date,modes=>[ [mode,entries,total_amount] ]]
foreach ($paymentsRaw as $row) {
    $remark = $row['remarks'] ?? 'Unspecified';
    $mode   = $row['mode'] ?? 'other';
    $amt    = (float)$row['amount'];
    $date   = $row['payment_date'];

    if (!isset($byRemark[$remark])) {
        $byRemark[$remark] = [
            'entries'      => 0,
            'total_amount' => 0.0,
            'first_date'   => $date,
            'last_date'    => $date,
            'modes'        => [] // mode => ['entries'=>X,'total_amount'=>Y]
        ];
    }

    $byRemark[$remark]['entries'] += 1;
    $byRemark[$remark]['total_amount'] += $amt;

    // Period
    if ($date < $byRemark[$remark]['first_date']) $byRemark[$remark]['first_date'] = $date;
    if ($date > $byRemark[$remark]['last_date'])  $byRemark[$remark]['last_date']  = $date;

    // Mode breakdown
    if (!isset($byRemark[$remark]['modes'][$mode])) {
        $byRemark[$remark]['modes'][$mode] = ['entries' => 0, 'total_amount' => 0.0];
    }
    $byRemark[$remark]['modes'][$mode]['entries'] += 1;
    $byRemark[$remark]['modes'][$mode]['total_amount'] += $amt;
}

// Optional: also build a flat "by mode" summary (mode => entries,total)
$byMode = [];
foreach ($paymentsRaw as $row) {
    $mode = $row['mode'] ?? 'other';
    $amt  = (float)$row['amount'];
    if (!isset($byMode[$mode])) {
        $byMode[$mode] = ['entries' => 0, 'total_amount' => 0.0];
    }
    $byMode[$mode]['entries'] += 1;
    $byMode[$mode]['total_amount'] += $amt;
}
// Branch name
$stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
$stmtBranch->execute([':branch_id' => $branchId]);
$branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
$branchName = $branchDetails['branch_name'];

if (!$branchDetails) {
    die("Branch details could not be found for this record.");
}

$today = date("d-m-Y");
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Patient Bill</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/schedule.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        .nav {
            background-color: #000;
            width: 60px;
            padding: 10px 20px;
            border-radius: 10px;
        }

        .nav a {
            color: #fff;
            text-decoration: none;
        }

        .bill-container {
            max-width: 900px;
            margin: auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        /* Header */
        .bill-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .bill-header .logo img {
            height: 70px;
            object-fit: contain;
        }

        .bill-header .clinic-details {
            text-align: right;
            font-size: 14px;
            color: #555;
        }

        .bill-header .clinic-details h2 {
            margin: 0 0 5px 0;
            font-size: 20px;
            color: #222;
        }

        /* Date */
        .bill-date {
            text-align: right;
            font-size: 14px;
            margin-bottom: 20px;
            color: #333;
        }

        /* Cards */
        .section-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .section-card h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #2b7de9;
            border-bottom: 2px solid #2b7de9;
            display: inline-block;
            padding-bottom: 3px;
        }

        .section-content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .section-item {
            flex: 1 1 250px;
            min-width: 200px;
        }

        .section-item strong {
            display: inline-block;
            width: 140px;
            color: #333;
        }

        /* Payments */
        .payments-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .payment-card {
            flex: 1 1 200px;
            min-width: 180px;
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .payment-card p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        /* New Table for Print View */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .payment-table th,
        .payment-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        .payment-table th {
            background-color: #f2f2f2;
        }


        .footer {
            text-align: center;
            margin-top: 30px;
            color: #777;
            font-size: 13px;
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
            border-radius: 5px;
            background-color: #2b7de9;
            color: #fff;
            cursor: pointer;
            transition: background 0.3s;
        }

        .print-btn button:hover {
            background-color: #155bc0;
        }

        /* --- PRINT STYLES --- */
        @media print {

            .print-btn,
            .nav {
                display: none;
            }

            body {
                background: #fff;
                font-size: 10pt;
                /* Smaller font for print */
                padding: 0;
                margin: 0;
                color: #000;
            }

            .bill-container {
                box-shadow: none;
                border: none;
                /* padding: 10mm; */
                /* A bit of margin for the page */
                /* width: 100%; */
            }

            .bill-header {
                padding-bottom: 5px;
                margin-bottom: 10px;
                border-bottom: 2px dashed #e0e0e0;
            }

            .bill-header .logo img {
                height: 50px;
            }

            .bill-header .clinic-details {
                font-size: 9pt;
            }

            .bill-date {
                font-size: 9pt;
                margin-bottom: 10px;
            }

            .section-card {
                padding: 10px;
                margin-bottom: 10px;
                box-shadow: none;
                border: 1px solid #e0e0e0;
            }

            .section-card h3 {
                font-size: 12pt;
                margin-bottom: 5px;
                border-bottom: 1px solid #2b7de9;
            }

            .section-content,
            .payments-grid {
                display: block;
                gap: 0;
            }

            .section-item {
                flex: none;
                font-size: 10pt;
                line-height: 1.5;
            }

            .payment-card,
            .payments-grid {
                display: none;
                /* Hide the card view for print */
            }

            .payment-table {
                display: table;
                /* Show the table for print */
                font-size: 8pt;
            }

            .footer {
                margin-top: 15px;
                padding-top: 5px;
                font-size: 9pt;
            }

            header .logo img {
                display: none;
            }

            .icon-btn {
                display: none;
            }
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
                    <div class="logo-placeholder">Primary Logo N/A</div>
                <?php endif; ?>
            </div>
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a href="registration.php">Registration</a>
                <a href="appointments.php">Appointments</a>
                <a href="patients.php" class="active">Patients</a>
                <a href="billing.php">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Navigation" onclick="window.location.href = 'patients.php';"><i class="fa-solid fa-arrow-left"> </i>&nbsp; Back</div>
        </div>
    </header>

    <div class="bill-container">
        <!-- Header -->
        <div class="bill-header">
            <div class="logo-container">
                <div class="logo">
                    <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                        <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo">
                    <?php else: ?>
                        <div class="logo-placeholder">Primary Logo N/A</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="clinic-details">
                <p>Swami Vivika Nand Road, Adampur Chowk, Bhagalpur</p>
                <p>Phone: +91-8002910021, 9304414144</p>
            </div>
        </div>

        <!-- Date -->
        <p class="bill-date"><strong>Date:</strong> <?= htmlspecialchars($today) ?></p>

        <!-- Patient Details -->
        <div class="section-card">
            <h3>Patient Details</h3>
            <div class="section-content">
                <div class="section-item"><strong>Name:</strong> <?= htmlspecialchars($registration['patient_name'] ?? '-') ?></div>
                <div class="section-item"><strong>Mobile:</strong> <?= htmlspecialchars($registration['phone_number'] ?? '-') ?></div>
                <div class="section-item"><strong>Address:</strong> <?= htmlspecialchars($registration['address'] ?? '-') ?></div>
                <div class="section-item"><strong>Assigned Doctor:</strong> <?= htmlspecialchars($patient['assigned_doctor'] ?? '-') ?></div>
            </div>
        </div>

        <!-- Treatment Details -->
        <div class="section-card">
            <h3>Treatment Details</h3>
            <div class="section-content">
                <div class="section-item"><strong>Type:</strong> <?= htmlspecialchars(ucfirst($patient['treatment_type'] ?? '-')) ?></div>

                <?php if (!empty($patient['treatment_type']) && $patient['treatment_type'] === 'daily'): ?>
                    <div class="section-item"><strong>Cost/Day:</strong> ₹ <?= number_format((float)($patient['treatment_cost_per_day'] ?? 0), 2) ?></div>
                <?php elseif (!empty($patient['package_cost'])): ?>
                    <div class="section-item"><strong>Package Cost:</strong> ₹ <?= number_format((float)$patient['package_cost'], 2) ?></div>
                <?php endif; ?>

                <div class="section-item"><strong>Treatment Days:</strong> <?= $treatmentDays ?: '-' ?></div>
                <div class="section-item"><strong>Attendance:</strong> <?= (int)$attendanceCount ?> / <?= $treatmentDays ?: '-' ?></div>

                <div class="section-item"><strong>Total:</strong> ₹ <?= number_format((float)$patient['total_amount'], 2) ?></div>
                <div class="section-item"><strong>Paid:</strong> ₹ <?= number_format((float)$patient['advance_payment'], 2) ?></div>
                <div class="section-item"><strong>Discount:</strong> <?= number_format((float)$patient['discount_percentage'], 2) ?>%</div>
                <div class="section-item"><strong>Due:</strong> ₹ <?= number_format((float)$patient['due_amount'], 2) ?></div>
                <div class="section-item"><strong>Method:</strong> <?= htmlspecialchars(ucfirst($patient['payment_method'] ?? '-')) ?></div>
                <div class="section-item"><strong>Start Date:</strong> <?= htmlspecialchars($patient['start_date'] ?? '-') ?></div>
                <div class="section-item"><strong>End Date:</strong> <?= htmlspecialchars($patient['end_date'] ?? '-') ?></div>
            </div>
        </div>

        <!-- Payments Overview -->
        <?php if (!empty($paymentsRaw)): ?>
            <div class="section-card">
                <h3>Payments Overview</h3>
                <div class="section-content">
                    <div class="section-item"><strong>Total Payments:</strong> <?= (int)($payOverview['entries'] ?? 0) ?></div>
                    <div class="section-item"><strong>Total Paid:</strong> ₹ <?= number_format((float)($payOverview['total_paid'] ?? 0), 2) ?></div>
                    <div class="section-item"><strong>Last Payment:</strong> <?= htmlspecialchars($payOverview['last_date'] ?? '-') ?></div>
                    <div class="section-item"><strong>Dues:</strong> ₹ <?= number_format((float)($patient['due_amount'] ?? 0), 2) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payments by Remark (grouped, with mode breakdown) -->
        <?php if (!empty($byRemark)): ?>
            <div class="section-card payment-card">
                <h3>Payments Summary (Grouped by Remark \ Not Printable)</h3>
                <div class="payments-grid">
                    <?php foreach ($byRemark as $remark => $data): ?>
                        <div class="payment-card">
                            <p><strong>Remark:</strong> <?= htmlspecialchars($remark) ?></p>
                            <p><strong>Total for Remark:</strong> ₹ <?= number_format((float)$data['total_amount'], 2) ?></p>
                            <p><strong>Entries:</strong> <?= (int)$data['entries'] ?></p>
                            <p><strong>Period:</strong> <?= htmlspecialchars($data['first_date']) ?> — <?= htmlspecialchars($data['last_date']) ?></p>
                            <?php if (!empty($data['modes'])): ?>
                                <div class="modes">
                                    <p><strong>Modes:</strong></p>
                                    <ul>
                                        <?php foreach ($data['modes'] as $mode => $m): ?>
                                            <li>
                                                <?= htmlspecialchars(ucfirst($mode)) ?> —
                                                <?= (int)$m['entries'] ?> entr<?= $m['entries'] == 1 ? 'y' : 'ies' ?>,
                                                ₹ <?= number_format((float)$m['total_amount'], 2) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- New: Payments Table for Print -->
        <?php if (!empty($paymentsRaw)): ?>
            <div class="section-card payment-card">
                <h3>Payment History Table (Not Printable)</h3>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Remark</th>
                            <th>Mode</th>
                            <th style="text-align: right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentsRaw as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['payment_date']) ?></td>
                                <td><?= htmlspecialchars($row['remarks']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($row['mode'])) ?></td>
                                <td style="text-align: right;">₹ <?= number_format((float)$row['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            Thank you for choosing our clinic!
        </div>

        <!-- Print -->
        <div class="print-btn">
            <button onclick="window.print()">Print Bill</button>
        </div>
    </div>
</body>

</html>