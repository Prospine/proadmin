<?php

declare(strict_types=1);
session_start();

// Error Reporting (Dev Only)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Auth / Session Checks
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
    // --- NEW: Fetch distinct values for filter dropdowns ---
    $filterOptions = [];
    $filterQueries = [
        'doctors' => "SELECT DISTINCT assigned_doctor FROM patients WHERE branch_id = :branch_id AND assigned_doctor IS NOT NULL AND assigned_doctor != '' ORDER BY assigned_doctor",
        'treatments' => "SELECT DISTINCT treatment_type FROM patients WHERE branch_id = :branch_id AND treatment_type IS NOT NULL AND treatment_type != '' ORDER BY treatment_type",
        'statuses' => "SELECT DISTINCT status FROM patients WHERE branch_id = :branch_id AND status IS NOT NULL AND status != '' ORDER BY status",
        'services' => "SELECT DISTINCT service_type FROM patients WHERE branch_id = :branch_id AND service_type IS NOT NULL AND service_type != '' ORDER BY service_type",
    ];

    foreach ($filterQueries as $key => $query) {
        $stmt = $pdo->prepare($query);
        $stmt->execute([':branch_id' => $branchId]);
        // Use FETCH_COLUMN to get a simple array of values
        $filterOptions[$key] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // --- NEW: Fetch comprehensive list of referrers (from dashboard.php) ---
    try {
        $stmtReferrers = $pdo->prepare("
            (SELECT DISTINCT reffered_by FROM registration WHERE branch_id = :branch_id AND reffered_by IS NOT NULL AND reffered_by != '')
            UNION
            (SELECT DISTINCT reffered_by FROM test_inquiry WHERE branch_id = :branch_id AND reffered_by IS NOT NULL AND reffered_by != '')
            UNION
            (SELECT DISTINCT referred_by AS reffered_by FROM tests WHERE branch_id = :branch_id AND referred_by IS NOT NULL AND referred_by != '')
            ORDER BY reffered_by ASC
        ");
        $stmtReferrers->execute([':branch_id' => $branchId]);
        $allReferrers = $stmtReferrers->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $allReferrers = []; // Default to empty array on error
    }

    // Fetch all necessary patient data, including costs and payments
    $stmt = $pdo->prepare("
        SELECT
            p.patient_id,
            p.treatment_type,
            p.service_type,
            p.treatment_cost_per_day,
            p.package_cost,
            p.treatment_days,
            p.total_amount,
            p.advance_payment,
            p.discount_percentage,
            p.due_amount,
            p.assigned_doctor,
            p.start_date,
            pm.patient_uid,
            p.end_date,
            p.status AS patient_status,
            r.registration_id,
            r.patient_name AS patient_name,
            r.phone_number AS patient_phone,
            r.age AS patient_age,
            r.chief_complain AS patient_condition,
            r.created_at,
            r.patient_photo_path
        FROM patients p
        JOIN registration r ON p.registration_id = r.registration_id
        LEFT JOIN patient_master pm ON r.master_patient_id = pm.master_patient_id
        WHERE p.branch_id = :branch_id
        ORDER BY p.created_at DESC
    ");

    $stmt->execute([':branch_id' => $branchId]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch today's attendance for quick UI check
    $attStmt = $pdo->prepare("
        SELECT a.patient_id
        FROM attendance a
        INNER JOIN patients p2 ON a.patient_id = p2.patient_id
        WHERE a.attendance_date = CURDATE()
          AND p2.branch_id = :branch_id
    ");
    $attStmt->execute([':branch_id' => $branchId]);
    $attendanceTodayMap = array_flip($attStmt->fetchAll(PDO::FETCH_COLUMN));

    // NEW: Fetch today's tokens for quick UI check
    $tokenStmt = $pdo->prepare("
        SELECT patient_id
        FROM tokens
        WHERE token_date = CURDATE() AND branch_id = :branch_id
    ");
    $tokenStmt->execute([':branch_id' => $branchId]);
    $tokensTodayMap = array_flip($tokenStmt->fetchAll(PDO::FETCH_COLUMN));

    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];

    // For each patient, get their attendance count and effective balance
    // We must do this in a loop for each patient, as it's a dynamic calculation
    foreach ($patients as &$patient) {
        $patientId = (int)($patient['patient_id'] ?? 0);
        $treatmentType = strtolower((string)$patient['treatment_type']);

        // 1. Determine cost per day (same as add_attendance.php)
        $costPerDay = 0.0;
        if ($treatmentType === 'package') {
            if ((int)($patient['treatment_days'] ?? 0) > 0) {
                $costPerDay = (float)($patient['package_cost'] ?? 0) / (int)($patient['treatment_days']);
            }
        } elseif ($treatmentType === 'daily' || $treatmentType === 'advance') {
            $costPerDay = (float)($patient['treatment_cost_per_day'] ?? 0);
        }
        $patient['cost_per_day'] = $costPerDay;

        // 2. Fetch payments and attendance counts
        $paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS paid FROM payments WHERE patient_id = ?");
        $paidStmt->execute([$patientId]);
        $paidSum = (float)$paidStmt->fetchColumn();

        $attendanceCountStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE patient_id = ?");
        $attendanceCountStmt->execute([$patientId]);
        $attendanceCount = (int)$attendanceCountStmt->fetchColumn();
        $patient['attendance_count'] = $attendanceCount;


        // 3. Conditional logic based on existence of records
        $effectiveBalance = 0.0;
        if ($paidSum > 0 || $attendanceCount > 0) {
            // SCENARIO 2: EXISTING PATIENT
            $consumedAmount = $attendanceCount * $costPerDay;
            $effectiveBalance = $paidSum - $consumedAmount;
        } else {
            // SCENARIO 1: BRAND NEW PATIENT
            $startDate = new DateTime((string)($patient['start_date'] ?? 'now'));
            $today = new DateTime('now');
            $daysPassed = max(0, $today->diff($startDate)->days);
            $consumedAmount = $daysPassed * $costPerDay;
            $initialAdvance = (float)($patient['advance_payment'] ?? 0);
            $effectiveBalance = $initialAdvance - $consumedAmount;
        }

        // Add calculated effective balance to the patient array
        $patient['effective_balance'] = $effectiveBalance;
    }
    unset($patient); // Break the reference
} catch (PDOException $e) {
    // Log the full error for server-side records
    error_log("Database Error in patients.php: " . $e->getMessage() . " on line " . $e->getLine());

    // Display a more detailed error message for easier debugging
    // This is safe because you have error reporting enabled at the top of the file.
    echo "<div style='padding: 20px; background-color: #ffcccc; border: 1px solid #ff0000; margin: 20px;'>";
    echo "<h2>Database Query Failed</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>This error occurred while trying to fetch patient data. Please check the database query and ensure all required tables and columns exist.</p>";
    echo "</div>";
    exit; // Stop further execution
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/registration.css">
    <link rel="stylesheet" href="../css/patients.css">

    <style>
        @media (max-width: 1024px) {

            .filter-bar {
                /* margin: 0; */
                display: flex;
                width: auto;
            }

            .drawer-panel {
                width: min(750px, 100%);
            }

            .but {
                margin-right: 20px;
            }
        }

        .schedule-bar {
            padding: 6px;
            margin-top: 5px;
            margin-left: 10px;
            align-items: center;
        }

        .schedule-bar button {
            padding: 8px 20px;
        }


        /* --- NEW: Comprehensive Modal Styles --- */
        .modal-overlay {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place even when scrolling */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            /* Semi-transparent background */
            z-index: 10000;
            /* High z-index to appear on top */
            justify-content: center;
            /* Center horizontally */
            align-items: center;
            /* Center vertically */
            overflow-y: auto;
            /* Allow scrolling if content is too tall */
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
            padding: 1rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.is-visible {
            display: flex;
            opacity: 1;
        }

        .modal-overlay.is-visible .modal-content {
            transform: scale(1);
            opacity: 1;
        }

        .modal-content {
            background: var(--card-bg, #fff);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 900px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: scale(0.95);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        body.dark .modal-content {
            background-color: #111;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: var(--text-color, #111827);
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            line-height: 1;
            color: var(--text-muted, #6b7280);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background-color 0.2s, color 0.2s;
        }

        .close-modal-btn:hover {
            background-color: var(--bg-tertiary, #f3f4f6);
            color: var(--text-primary, #111827);
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }

        .form-actions {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color, #e5e7eb);
            /* Corrected from duplicate */
            text-align: right;
        }

        .modal-content.large {
            width: 90%;
            max-width: 1100px;
        }

        /* This is the style your JS will apply to show the modal */
        .modal-overlay.is-visible {
            display: flex;
        }

        /* --- END FIX --- */
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
                <a class="active" href="patients.php">Patients</a>
                <a href="billing.php">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"> <?php echo $branchName; ?> Branch </div>
            <div class="icon-btn" id="theme-toggle"> <i id="theme-icon" class="fa-solid fa-moon"></i> </div>
            <div class="icon-btn icon-btn2" title="Notifications" onclick="openNotif()">🔔</div>
            <div class="profile" onclick="openForm()">S</div>
        </div>
        <!-- Hamburger Menu Icon (for mobile) -->
        <div class="hamburger-menu" id="hamburger-menu">
            <i class="fa-solid fa-bars"></i>
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
                <li><a href="#">You have 3 new appointments.</a></li>
                <li><a href="#">Dr. Smith is available for consultation.</a></li>
                <li><a href="#">New patient registered: John Doe.</a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="dashboard-container">
            <div class="top-bar">
                <h2>Patients</h2>
                <!-- NEW: Filter and Search Bar -->
                <div class="filter-bar">
                    <div class="search-container">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, ID, condition, etc...">
                    </div>

                    <div class="filter-options">
                        <select id="serviceTypeFilter">
                            <option value="">All Services</option>
                            <?php foreach ($filterOptions['services'] as $service): ?>
                                <option value="<?= htmlspecialchars($service) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $service))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="doctorFilter">
                            <option value="">All Doctors</option>
                            <?php foreach ($filterOptions['doctors'] as $doctor): ?>
                                <option value="<?= htmlspecialchars($doctor) ?>"><?= htmlspecialchars($doctor) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="treatmentFilter">
                            <option value="">All Treatments</option>
                            <?php foreach ($filterOptions['treatments'] as $treatment): ?>
                                <option value="<?= htmlspecialchars($treatment) ?>"><?= htmlspecialchars(ucfirst($treatment)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="statusFilter">
                            <option value="">All Statuses</option>
                            <?php foreach ($filterOptions['statuses'] as $status): ?>
                                <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button id="sortDirectionBtn" class="sort-btn" title="Toggle Sort Direction">
                            <i class="fa-solid fa-sort"></i>
                        </button>
                    </div>
                </div>
                <div class="schedule-bar">
                    <button onclick="window.location.href='schedules.php'">View Schedules</button>
                </div>
            </div>
            <div class="table-container modern-table">
                <table id="patientsTable">
                    <thead>
                        <tr>
                            <th data-key="patient_uid" class="sortable">ID <span class="sort-indicator"></span></th>
                            <th>Photo</th>
                            <th data-key="patient_name" class="sortable">Name <span class="sort-indicator"></span></th>
                            <th data-key="patient_age" class="sortable">Age</th>
                            <th data-key="assigned_doctor" class="sortable">Assigned Doctor</th>
                            <!-- <th data-key="patient_condition" class="sortable">Condition</th> -->
                            <th data-key="service_type" class="sortable">Service Type</th>
                            <th data-key="treatment_type" class="sortable">Treatment Type</th>
                            <!-- <th data-key="days" class="sortable">Days</th> -->
                            <th data-key="attendance_count" class="sortable">Attendance</th>
                            <!-- <th data-key="cost" class="sortable numeric">Total Amount</th> -->
                            <th data-key="advance_payment" class="sortable numeric">Amount Paid</th>
                            <!-- <th data-key="due" class="sortable numeric">Due Amount</th> -->
                            <th data-key="start_date" class="sortable">Treatment Period</th>
                            <th data-key="patient_status">Status</th>
                            <th>Mark Attendance</th>
                            <th>Token</th>
                            <th>Action</th>
                            </>
                    </thead>
                    <tbody id="patientsTableBody">
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $row):
                                $pid = (int) ($row['patient_id'] ?? 0);
                                $hasToday = isset($attendanceTodayMap[$pid]);
                                $hasTokenToday = isset($tokensTodayMap[$pid]);
                                $initial = !empty($row['patient_name']) ? strtoupper(substr($row['patient_name'], 0, 1)) : '?';

                                // Now we pass the effective balance to the front-end
                                $data_attrs = sprintf(
                                    'data-patient-id="%d" data-treatment-type="%s" data-cost-per-day="%s" data-due-amount="%s" data-effective-balance="%s"',
                                    $pid,
                                    htmlspecialchars((string)($row['treatment_type'] ?? ''), ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars((string)($row['cost_per_day'] ?? 0), ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars((string)($row['due_amount'] ?? 0), ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars((string)($row['effective_balance'] ?? 0), ENT_QUOTES, 'UTF-8')
                                );
                                $token_data_attrs = sprintf(
                                    'data-patient-id="%d" data-treatment-type="%s" data-cost-per-day="%s" data-due-amount="%s" data-effective-balance="%s"',
                                    $pid,
                                    htmlspecialchars((string)($row['treatment_type'] ?? ''), ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars((string)($row['cost_per_day'] ?? 0), ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars((string)($row['due_amount'] ?? 0), ENT_QUOTES, 'UTF-8'),
                                    htmlspecialchars((string)($row['effective_balance'] ?? 0), ENT_QUOTES, 'UTF-8')
                                );
                            ?>
                                <tr <?= $data_attrs ?> data-id="<?= htmlspecialchars((string)$pid, ENT_QUOTES, 'UTF-8') ?>">
                                    <td data-label="ID"><?= htmlspecialchars($row['patient_uid'] ?? 'N/A') ?></td>
                                    <td data-label="Photo">
                                        <div class="photo-cell">
                                            <?php if (!empty($row['patient_photo_path'])): ?>
                                                <img src="/admin/<?= htmlspecialchars($row['patient_photo_path']) ?>?v=<?= time() ?>" alt="Photo" class="table-photo">
                                            <?php else: ?>
                                                <div class="table-initials"><?= $initial ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td data-label="Name"><?= htmlspecialchars((string)($row['patient_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Age"><?= htmlspecialchars((string)($row['patient_age'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Assigned Doctor"><?= htmlspecialchars((string)($row['assigned_doctor'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- <td data-label="Condition"><?= htmlspecialchars((string)($row['patient_condition'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td> -->
                                    <td data-label="Service Type"><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)($row['service_type'] ?? '-'))), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Treatment Type"><?= htmlspecialchars((string)($row['treatment_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- <td><?= htmlspecialchars((string)($row['treatment_days'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td> -->
                                    <td data-label="Attendance">
                                        <?= htmlspecialchars((string)($row['attendance_count'] ?? 0), ENT_QUOTES, 'UTF-8') ?>
                                        /
                                        <?= htmlspecialchars((string)($row['treatment_days'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                    </td>


                                    <!-- <td class="numeric"><?= isset($row['total_amount']) ? '₹' . number_format((float)$row['total_amount'], 2) : '-' ?></td> -->
                                    <td data-label="Amount Paid" class="numeric"><?= isset($row['advance_payment']) ? '₹' . number_format((float)$row['advance_payment'], 2) : '-' ?></td>
                                    <!-- <td class="numeric"><?= isset($row['due_amount']) ? '₹' . number_format((float)$row['due_amount'], 2) : '-' ?></td> -->
                                    <td data-label="Treatment Period">
                                        <div>
                                            <span>Start: <?= !empty($row['start_date']) ? date('d M Y', strtotime($row['start_date'])) : '-' ?></span><br>
                                            <span>End: <?= !empty($row['end_date']) ? date('d M Y', strtotime($row['end_date'])) : '-' ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match ($row['patient_status'] ?? 'inactive') {
                                            'active' => 'status-active',
                                            'completed' => 'status-completed',
                                            'inactive' => 'status-inactive',
                                            default => 'status-inactive'
                                        };
                                        ?>
                                        <span data-label="Status" class="pill <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($row['patient_status'] ?? 'Inactive'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                    <td data-label="Mark Attendance">
                                        <?php if ($hasToday): ?>
                                            <button class="attendance-present-btn" disabled>Present</button>
                                        <?php else: ?>
                                            <button class="mark-attendance-btn" data-patient-id="<?= $pid ?>">Mark Today</button>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Token">
                                        <?php if ($hasTokenToday): ?>
                                            <button class="action-btn2" disabled title="Token already generated today.">Printed</button>
                                        <?php else: ?>
                                            <button class="action-btn2 print-token-btn"
                                                data-patient-id="<?= htmlspecialchars((string)$pid) ?>"
                                                data-patient-name="<?= htmlspecialchars((string)($row['patient_name'] ?? '')) ?>"
                                                data-assigned-doctor="<?= htmlspecialchars((string)($row['assigned_doctor'] ?? 'N/A')) ?>"
                                                data-attendance-progress="<?= htmlspecialchars((string)($row['attendance_count'] ?? 0)) ?> / <?= htmlspecialchars((string)($row['treatment_days'] ?? '-')) ?>">Print</button>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Action">
                                        <button class="action-btn" data-id="<?= htmlspecialchars((string)($row['patient_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="15" class="no-data">No patients found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="drawer-overlay" id="drawer">
        <div class="drawer-panel">
            <div class="drawer-header">
                <h2>Patient Profile</h2>
                <div class="but">

                    <button id="drawerPrintBillBtn">Print Bill</button>
                    <button id="drawerViewProfileBtn">View Profile</button>
                    <button id="drawerAddTestBtn">Add Test</button>
                    <button id="closeDrawer" class="drawer-close-btn">&times;</button>
                </div>
            </div>
            <div class="drawer-body" id="drawer-body"></div>
        </div>
    </div>

    <!-- NEW: Add Test Modal -->
    <div id="add-test-modal" class="modal-overlay" style="z-index: 10000;">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Add New Test for Patient</h3>
                <button id="close-test-modal-btn" class="close-modal-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addTestForPatientForm">
                    <input type="hidden" name="patient_id" id="test_patient_id">
                    <div class="form-grid">
                        <!-- Row 1 -->
                        <div class="form-group">
                            <label>Patient Name *</label>
                            <input type="text" name="patient_name" id="test_patient_name" readonly style="background: var(--bg-tertiary);">
                        </div>
                        <div class="form-group">
                            <label>Age *</label>
                            <input type="number" name="age" id="test_age" readonly style="background: var(--bg-tertiary);">
                        </div>
                        <div class="form-group">
                            <label>Gender *</label>
                            <input type="text" name="gender" id="test_gender" readonly style="background: var(--bg-tertiary);">
                        </div>
                        <!-- Row 2 -->
                        <div class="form-group">
                            <label>Phone No *</label>
                            <input type="text" name="phone_number" id="test_phone_number" readonly style="background: var(--bg-tertiary);">
                        </div>
                        <div class="form-group">
                            <label>Alternate Phone No</label>
                            <input type="text" name="alternate_phone_no" placeholder="+91..." maxlength="10">
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob">
                        </div>
                        <!-- Row 3 -->
                        <div class="form-group">
                            <label>Parents/Guardian</label>
                            <input type="text" name="parents" placeholder="Parents/Guardian Name">
                        </div>
                        <div class="form-group">
                            <label>Relation</label>
                            <input type="text" name="relation" placeholder="e.g., Father, Mother">
                        </div>
                        <div class="form-group">
                            <label>Referred By *</label>
                            <input list="modal-referrers-list" name="referred_by" required>
                            <datalist id="modal-referrers-list"> <!-- FIX: Unique ID for the modal's datalist -->
                                <?php foreach ($allReferrers as $referrer): ?>
                                    <option value="<?= htmlspecialchars($referrer) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                        <!-- Row 4 -->
                        <div class="form-group">
                            <label>Test Name *</label>
                            <select name="test_name" required>
                                <option value="">Select Test</option>
                                <option value="eeg">EEG</option>
                                <option value="ncv">NCV</option>
                                <option value="emg">EMG</option>
                                <option value="rns">RNS</option>
                                <option value="bera">BERA</option>
                                <option value="vep">VEP</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Limb</label>
                            <select name="limb">
                                <option value="">Select Limb</option>
                                <option value="upper_limb">Upper Limb</option>
                                <option value="lower_limb">Lower Limb</option>
                                <option value="both">Both Limbs</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Test Done By *</label>
                            <select name="test_done_by" required>
                                <option value="">Select Staff</option>
                                <option value="achal">Achal</option>
                                <option value="ashish">Ashish</option>
                                <option value="pancham">Pancham</option>
                                <option value="sayan">Sayan</option>
                            </select>
                        </div>
                        <!-- Row 5 -->
                        <div class="form-group">
                            <label>Date of Visit *</label>
                            <input type="date" name="visit_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Assigned Test Date *</label>
                            <input type="date" name="assigned_test_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <!-- Row 6 -->
                        <div class="form-group">
                            <label>Total Amount *</label>
                            <input type="number" name="total_amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Discount</label>
                            <input type="number" name="discount" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Advance Amount</label>
                            <input type="number" name="advance_amount" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Due Amount</label>
                            <input type="number" name="due_amount" step="0.01" value="0" readonly style="background: var(--bg-tertiary);">
                        </div>
                        <!-- Row 7 -->
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="upi-boi">UPI-BOI</option>
                                <option value="upi-hdfc">UPI-HDFC</option>
                                <option value="card">Card</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions"><button type="submit" class="action-btn">Save Test</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Attendance Modal (only used for daily/advance) -->
    <div id="attendanceModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <h3 id="modalTitle">Mark Attendance</h3>
                <button class="close-modal-btn" aria-label="Close">&times;</button>
            </div>

            <form id="attendanceForm">
                <input type="hidden" name="patient_id" id="patient_id">
                <input type="hidden" name="treatment_type" id="treatment_type">

                <div id="payment_section" class="payment">
                    <label for="payment_today" id="payment_label">Payment Today</label>
                    <input type="number" id="payment_today" name="payment_amount" min="0" step="0.01">

                    <label for="payment_mode">Mode</label>
                    <select id="payment_mode" name="mode">
                        <option value="">Select Mode</option>
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="card">Card</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label for="remarks">Remarks for Attendance</label>
                    <textarea id="remarks" name="remarks" placeholder="Notes for today's attendance" style="height: 50px; width: 90%;"></textarea>
                </div>

                <div style="margin-top:12px; text-align:right;">
                    <button type="button" class="action-btn secondary" id="attendanceCancel">Cancel</button>
                    <button type="submit" class="btn btn-save">Save Attendance</button>
                </div>
            </form>
        </div>
    </div>

    <div id="token-modal" class="popups">
        <div class="token-modal-content" id="token-print-area">
            <div class="token-header">
                <h3>Patient Token</h3>
                <button class="close-token">&times;</button>
            </div>
            <p><strong>Token No:</strong> <span id="popup-token-uid"></span></p>
            <p><strong>Name:</strong> <span id="popup-name"></span></p>
            <p><strong>Assigned Doctor:</strong> <span id="popup-doctor"></span></p>
            <p><strong>Date & Time:</strong> <span id="popup-date"></span></p>
            <p><strong>Total Paid:</strong> ₹<span id="popup-total-paid"></span></p>
            <p><strong>Attendance Progress:</strong> <span id="popup-attendance"></span></p>
            <hr>
            <button id="popup-print-btn">🖨 Print</button>

            <p class="patient-message">Thank You for your Visit</p>
        </div>
    </div>

    <div id="toast-container"></div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/patients.js"></script>
    <script src="../js/addattendance.js"></script>
    <script src="../js/nav_toggle.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const printBillBtn = document.getElementById('drawerPrintBillBtn');
            const viewProfileBtn = document.getElementById('drawerViewProfileBtn');

            // Use event delegation on the table to handle clicks on "View" buttons
            document.getElementById('patientsTable').addEventListener('click', function(event) {
                // Check if a "View" button was clicked
                if (event.target && event.target.matches('button.action-btn')) {
                    const patientId = event.target.dataset.id;

                    // Update the onclick actions for the drawer buttons
                    if (patientId) {
                        printBillBtn.onclick = () => window.location.href = `patients_bill.php?patient_id=${patientId}`;
                        viewProfileBtn.onclick = () => window.location.href = `patients_profile.php?patient_id=${patientId}`;
                    }
                }
            });
        });
    </script>

</body>

</html>