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

require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection


// Make sure the user is logged in
if (!isset($_SESSION['branch_id'])) {
    header("Location: ../../login.php");
    exit();
}

$branchId = $_SESSION['branch_id'];
date_default_timezone_set('Asia/Kolkata');
$today = date('Y-m-d');
$todayDisplay = date('M d');

$groupedSchedules = [];
$daysRange = [];
$schedules = [];
// -------------------------
// Dashboard Metrics Fetch
// -------------------------

try {
    date_default_timezone_set('Asia/Kolkata');
    $pdo->exec("SET time_zone = '+05:30'");
    $today = date('Y-m-d');

    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];

    // --- Appointments from Registration Status ---
    $stmtApptStatus = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status IN ('consulted', 'closed') THEN 1 ELSE 0 END) as conducted,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as in_queue
        FROM registration
        WHERE branch_id = :branch_id AND DATE(appointment_date) = :today
    ");
    $stmtApptStatus->execute(['branch_id' => $branchId, 'today' => $today]);
    $appointmentStatusCounts = $stmtApptStatus->fetch(PDO::FETCH_ASSOC);

    $todayAppointmentsConducted = (int)($appointmentStatusCounts['conducted'] ?? 0);
    $todayAppointmentsInQueue = (int)($appointmentStatusCounts['in_queue'] ?? 0);

    //--- patients ----
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM patients 
                           WHERE branch_id = :branch_id 
                             AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayPatients = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM patients 
                           WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $totalPatients = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM patients 
                           WHERE branch_id = :branch_id AND status = :status");
    $stmt->execute(['branch_id' => $branchId, 'status' => 'active']);
    $ongoingPatients = (int) $stmt->fetch()['count'];

    $stmt->execute(['branch_id' => $branchId, 'status' => 'completed']);
    $dischargedPatients = (int) $stmt->fetch()['count'];

    // --- Inquiries ---
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM registration 
                           WHERE branch_id = :branch_id 
                             AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayInquiries = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM registration 
                           WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $totalInquiries = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM quick_inquiry WHERE branch_id = :branch_id AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayquickInquiry = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM quick_inquiry WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $totalquickInquiry = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM test_inquiry WHERE branch_id = :branch_id AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todaytestInquiry = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM test_inquiry WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $totaltestInquiry = (int) $stmt->fetch()['count'];

    // --- Tests ---
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM tests 
                           WHERE branch_id = :branch_id 
                             AND test_status = 'pending' 
                             AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayPendingTests = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM tests 
                           WHERE branch_id = :branch_id 
                             AND test_status = 'pending'");
    $stmt->execute(['branch_id' => $branchId]);
    $totalPendingTests = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM tests 
                           WHERE branch_id = :branch_id 
                             AND payment_status = 'paid' 
                             AND test_status = 'completed' 
                             AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayCompletedTests = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM tests 
                           WHERE branch_id = :branch_id 
                             AND payment_status = 'paid' 
                             AND test_status = 'completed'");
    $stmt->execute(['branch_id' => $branchId]);
    $totalCompletedTests = (int) $stmt->fetch()['count'];

    // --- Payments ---
    // New, more specific queries for today's collections breakdown
    $stmtRegPayments = $pdo->prepare("
        SELECT SUM(consultation_amount) FROM registration 
        WHERE branch_id = :branch_id AND DATE(created_at) = :today
    ");
    $stmtRegPayments->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayRegistrationPaid = (float)$stmtRegPayments->fetchColumn();

    $stmtTestPayments = $pdo->prepare("
        SELECT SUM(advance_amount) FROM tests 
        WHERE branch_id = :branch_id AND DATE(visit_date) = :today
    ");
    $stmtTestPayments->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayTestPaid = (float)$stmtTestPayments->fetchColumn();

    $stmtPatientPayments = $pdo->prepare("
        SELECT SUM(p.amount) 
        FROM payments p
        JOIN patients pt ON p.patient_id = pt.patient_id
        WHERE pt.branch_id = :branch_id AND p.payment_date = :today
    ");
    $stmtPatientPayments->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayPatientTreatmentPaid = (float)$stmtPatientPayments->fetchColumn();


    $appointments = $pdo->prepare("SELECT payment_amount, payment_date 
                                   FROM appointments 
                                   WHERE branch_id = :branch_id 
                                     AND payment_status = 'paid'");
    $appointments->execute(['branch_id' => $branchId]);
    $appointments = $appointments->fetchAll(PDO::FETCH_ASSOC);

    $tests = $pdo->prepare("SELECT total_amount, updated_at 
                            FROM tests 
                            WHERE branch_id = :branch_id 
                              AND payment_status IN ('paid', 'partial')");
    $tests->execute(['branch_id' => $branchId]);
    $tests = $tests->fetchAll(PDO::FETCH_ASSOC);

    $inquiries = $pdo->prepare("SELECT consultation_amount, created_at 
                                FROM registration 
                                WHERE branch_id = :branch_id");
    $inquiries->execute(['branch_id' => $branchId]);
    $inquiries = $inquiries->fetchAll(PDO::FETCH_ASSOC);

    $patients = $pdo->prepare("SELECT advance_payment, created_at 
                               FROM patients 
                               WHERE branch_id = :branch_id 
                                 AND status = 'active'");
    $patients->execute(['branch_id' => $branchId]);
    $patients = $patients->fetchAll(PDO::FETCH_ASSOC);

    $attPayments = $pdo->prepare("
    SELECT pmt.amount
    FROM payments pmt
    INNER JOIN patients pat ON pmt.patient_id = pat.patient_id
    WHERE pat.branch_id = :branch_id
      AND pat.status = 'active'
      AND DATE(pmt.payment_date) = :today
    ORDER BY pmt.payment_date DESC
");
    $attPayments->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayPayments = $stmt->fetchAll(PDO::FETCH_COLUMN); // returns array of amounts

    $todayPaid = 0.0;
    $totalPaid = 0.0;

    foreach ($appointments as $a) {
        $amount = (float) $a['payment_amount'];
        $totalPaid += $amount;
        if (substr($a['payment_date'], 0, 10) === $today) {
            $todayPaid += $amount;
        }
    }
    foreach ($tests as $t) {
        $amount = (float) $t['total_amount'];
        $totalPaid += $amount;
        if (substr($t['updated_at'], 0, 10) === $today) {
            $todayPaid += $amount;
        }
    }
    foreach ($inquiries as $inq) {
        $amount = (float) $inq['consultation_amount'];
        $totalPaid += $amount;
        if (substr($inq['created_at'], 0, 10) === $today) {
            $todayPaid += $amount;
        }
    }
    foreach ($patients as $pat) {
        $amount = (float) $pat['advance_payment'];
        $totalPaid += $amount;
        if (substr($pat['created_at'], 0, 10) === $today) {
            $todayPaid += $amount;
        }
    }
    foreach ($attPayments as $pay) {
        $amount = (float) $pay['amount'];
        $totalPaid += $amount;
        $todayPaid += $amount; // ✅ since it's already filtered by today's date
    }


    // --- Pending Dues (today) ---
    $stmt = $pdo->prepare("SELECT SUM(due_amount) AS total_due
                           FROM tests
                           WHERE branch_id = :branch_id 
                             AND payment_status IN ('pending','partial') 
                             AND DATE(assigned_test_date) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayTestDues = (float) ($stmt->fetch()['total_due'] ?? 0.0);

    $stmt = $pdo->prepare("SELECT SUM(due_amount) AS total_due
                           FROM patients
                           WHERE branch_id = :branch_id 
                             AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayPatientDues = (float) ($stmt->fetch()['total_due'] ?? 0.0);

    $todayDues = $todayTestDues + $todayPatientDues;

    // --- Pending Dues (all) ---
    $stmt = $pdo->prepare("SELECT SUM(due_amount) AS total_due
                           FROM tests
                           WHERE branch_id = :branch_id 
                             AND payment_status IN ('pending','partial')");
    $stmt->execute(['branch_id' => $branchId]);
    $totalTestDues = (float) ($stmt->fetch()['total_due'] ?? 0.0);

    $stmt = $pdo->prepare("SELECT SUM(due_amount) AS total_due
                           FROM patients
                           WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $totalPatientDues = (float) ($stmt->fetch()['total_due'] ?? 0.0);

    $totalDues = $totalTestDues + $totalPatientDues;

    $stmt = $pdo->prepare("
        SELECT patient_name, appointment_date, appointment_time, status
        FROM registration 
        WHERE branch_id = :branch_id 
          AND appointment_date >= :today
        ORDER BY appointment_date, appointment_time DESC
        LIMIT 20
    ");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $groupedSchedules = [];
    foreach ($schedules as $row) {
        $date = $row['appointment_date'];
        $groupedSchedules[$date][] = $row;
    }
} catch (Throwable $e) {
    error_log($e->getMessage());

    // Fallback defaults
    $todayAppointmentsConducted = $todayAppointmentsInQueue = 0;
    $todayPatients = $totalPatients = 0;
    $ongoingPatients = $dischargedPatients = 0;
    $todayInquiries = $totalInquiries = 0;
    $todayquickInquiry = $totalquickInquiry = 0;
    $todaytestInquiry = $totaltestInquiry = 0;
    $todayPendingTests = $totalPendingTests = 0;
    $todayCompletedTests = $totalCompletedTests = 0;
    $todayPaid = $totalPaid = 0.0;
    $todayDues = $totalDues = 0.0;
}

// --- Fetch distinct referrers for datalist ---
try {
    $stmtReferrers = $pdo->query("
        (SELECT DISTINCT reffered_by FROM registration WHERE branch_id = {$branchId} AND reffered_by IS NOT NULL AND reffered_by != '')
        UNION
        (SELECT DISTINCT reffered_by FROM test_inquiry WHERE branch_id = {$branchId} AND reffered_by IS NOT NULL AND reffered_by != '')
        UNION
        (SELECT DISTINCT referred_by FROM tests WHERE branch_id = {$branchId} AND referred_by IS NOT NULL AND referred_by != '')
        ORDER BY reffered_by ASC
    ");
    $referrers = $stmtReferrers->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $referrers = []; // Default to empty array on error
    error_log("Could not fetch referrers: " . $e->getMessage());
}

// --- CSRF token ---
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

$errors = [];
$success = false;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />

    <style>
        .chat-sidebar-header {
            display: flex;
            align-items: center;
            gap: 8px;
            /* Add some space between items */
        }

        #chat-user-search {
            flex-grow: 1;
            /* Allow search to take up available space */
        }

        .chat-header-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s, color 0.2s;
        }

        .chat-header-btn:hover:not(:disabled) {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Cooldown / Disabled State */
        .chat-header-btn:disabled {
            cursor: not-allowed;
            opacity: 0.5;
        }

        /* Spinning animation for the icon */
        .chat-header-btn.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .logo img {
            max-width: 100%;
            max-height: 80px;
            object-fit: contain;
            cursor: auto;
        }

        /* NEW: System Update Overlay */
        .system-update-overlay {
            display: none;
            /* Hidden by default, controlled by JS */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }

        .system-update-popup {
            background-color: #fff;
            padding: 2rem 3rem;
            border-radius: 12px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
        }

        .system-update-popup .fa-gears {
            font-size: 3rem;
            color: var(--primary-color, #007bff);
            margin-bottom: 1rem;
            --fa-animation-duration: 3s;
            /* Slower spin */
        }

        .system-update-popup h2 {
            margin-top: 0;
            color: var(--text-color);
        }

        .system-update-popup p {
            color: var(--text-secondary);
            font-size: 1rem;
            line-height: 1.6;
        }

        /* NEW: System Update Banner */
        .system-update-banner {
            margin: 0 auto;
            width: 90%;
            display: none;
            /* Hidden by default */
            background-color: #fffbe6;
            /* A warning yellow */
            color: #8a6d3b;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #ffe58f;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
        }

        body.dark .system-update-banner {
            background-color: #4d3c1a;
            color: #ffeca7;
            border-color: #8a6d3b;
        }

        .system-update-banner i {
            margin-right: 0.5rem;
        }
    </style>
</head>

<body>
    <!-- Mobile Blocker Overlay -->
    <div class="mobile-blocker">
        <div class="mobile-blocker-popup">
            <i class="fa-solid fa-mobile-screen-button popup-icon"></i>
            <h2>Mobile View Not Supported</h2>
            <p>The admin panel is designed for desktop use. For the best experience on your mobile device, please download our dedicated application.</p>
            <a href="/proadmin/download-app/index.html" class="mobile-download-btn">
                <i class="fa-solid fa-download"></i> Download App
            </a>
        </div>
    </div>

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
                <a href="dashboard.php" class="active"><i class="fa-solid fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="inquiry.php"><i class="fa-solid fa-magnifying-glass"></i><span>Inquiry</span></a>
                <a href="registration.php"><i class="fa-solid fa-user-plus"></i><span>Registration</span></a>
                <a href="appointments.php"><i class="fa-solid fa-calendar-check"></i><span>Appointments</span></a>
                <a href="patients.php"><i class="fa-solid fa-users"></i><span>Patients</span></a>
                <a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>Billing</span></a>
                <a href="attendance.php"><i class="fa-solid fa-user-check"></i><span>Attendance</span></a>
                <a href="tests.php"><i class="fa-solid fa-vial"></i><span>Tests</span></a>
                <a href="reports.php"><i class="fa-solid fa-chart-line"></i><span>Reports</span></a>
                <a href="expenses.php"><i class="fa-solid fa-money-bill-wave"></i><span>Expenses</span></a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" id="theme-toggle"> <i id="theme-icon" class="fa-solid fa-moon"></i> </div>
            <div class="inbox icon-btn icon-btn2" title="Inbox" onclick="openInbox()"><i class="fa-solid fa-inbox"></i></div>
            <div class="icon-btn icon-btn2" title="Notifications" onclick="openNotif()">🔔</div>
            <div class="profile" onclick="openForm()">S</div>
        </div>
        <!-- Hamburger Menu Icon (for mobile) -->
        <div class="hamburger-menu" id="hamburger-menu">
            <i class="fa-solid fa-bars"></i>
        </div>
    </header>
    <div class="menu" id="myMenu">
        <div class="popup">
            <span class="closebtn" onclick="closeForm()">&times;</span>
            <ul>
                <li><a href="#"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                <li><a href="#"><i class="fa-solid fa-cog"></i> Settings</a></li>
                <li class="logout"><a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="notification" id="myNotif">
        <span class="closebtn" onclick="closeNotif()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="changelog.html" class="active2">View Changes (1) </a></li>
                <li><a href="#">You have 3 new appointments.</a></li>
                <li><a href="#">Dr. Smith is available for consultation.</a></li>
                <li><a href="#">New patient registered: John Doe.</a></li>
            </ul>
        </div>
    </div>

    <div class="chat-inbox" id="myInbox">
        <div class="chat-container">
            <!-- Left Panel: User List -->
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <input type="text" id="chat-user-search" placeholder="Search users...">
                    <button id="chat-refresh-btn" class="chat-header-btn" title="Refresh Chat" disabled>
                        <i class="fa-solid fa-sync"></i>
                    </button>
                    <span class="closebtn" onclick="closeInbox()">&times;</span>
                </div>
                <div class="chat-user-list" id="chat-user-list">
                    <!-- User list will be populated by JavaScript -->
                    <div class="chat-loader">Loading users...</div>
                </div>
            </div>

            <!-- Right Panel: Chat Interface -->
            <div class="chat-main">
                <div class="chat-header" id="chat-header"></div>
                <div class="chat-messages" id="chat-messages">
                    <div class="chat-welcome-message">Select a user to start chatting</div>
                    <!-- Chat partner's name will appear here -->
                    <!-- <div class="chat-welcome-message">Select a user to start chatting</div> -->
                    <div class="encryption-status">
                        <i class="fa-solid fa-lock"></i> Messages are end-to-end encrypted
                    </div>
                </div>
                <div class="chat-input-area">
                    <input type="text" id="chat-message-input" placeholder="Type your message..." disabled>
                    <button id="chat-send-btn" disabled><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>

    <main>
        <!-- NEW: System Update Banner -->
        <div id="system-update-banner" class="system-update-banner">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span id="update-banner-message">
                <strong>System Update Scheduled:</strong> The system will be undergoing maintenance today from 6:10 PM to 06:20 PM. Access may be intermittent.
            </span>
        </div>
        <div class="content">
            <div class="heaad">
                <div class="card-header2"><span id="datetime"><?php echo date('Y-m-d h:i:s A'); ?></span></div>
                <div class="icon-btn icon-btn3" title="Branch" style="font-size: 14px;"><?php echo $branchName; ?> Branch</div>
            </div>
            <div class="cards">
                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-header">
                            <h2><i class="fa-solid fa-calendar-check"></i> Registration and Appointments</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-title">Today's Registration: <?php echo $todayInquiries ?></div>
                        <div class="card-sub">Total Registrations: <?php echo $totalInquiries ?></div>
                    </div>

                    <div class="card-body2">
                        <div class="card-title">Appointments Conducted: <?php echo $todayAppointmentsConducted; ?></div>
                        <div class="card-sub">Appointments in Queue: <?php echo $todayAppointmentsInQueue; ?></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-header">
                            <h2><i class="fa-solid fa-envelope"></i> Inquiry</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-title">Today's Inquiry: <?= $todayquickInquiry; ?></div>
                        <div class="card-sub">Total Inquiry: <?= $totalquickInquiry; ?></div>
                    </div>

                    <div class="card-body2">
                        <div class="card-title">Today's Test's Inquiry: <?= $todaytestInquiry ?></div>
                        <div class="card-sub">Total Test's Inquiry: <?= $totaltestInquiry ?></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-header">
                            <h2><i class="fa-solid fa-user-group"></i> Patients</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-title">Enrolled Patients Today: <?php echo $todayPatients; ?></div>
                        <div class="card-sub">Total Enrolled Patients: <?php echo $totalPatients; ?> </div>
                    </div>

                    <div class="card-body2">
                        <div class="card-title">Ongoing Treatments: <?= $ongoingPatients; ?></div>
                        <div class="card-sub">Discharged Patients: <?= $dischargedPatients; ?></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-header">
                            <h2> <i class="fa-solid fa-vial"></i> Tests</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-title">Tests Scheduled Today: <?php echo $todayPendingTests; ?></div>
                        <div class="card-sub">Tests in Queue: <?php echo $totalPendingTests; ?></div>
                    </div>

                    <div class="card-body2">
                        <div class="card-title">Tests Conducted Today: <?php echo $todayCompletedTests; ?></div>
                        <div class="card-sub">Total Tests Conducted : <?php echo $totalCompletedTests; ?></div>
                    </div>
                </div>

                <div class="card spcard2">
                    <div class="card-header-flex">
                        <div class="card-header">
                            <h2><i class="fa-solid fa-file-invoice-dollar"></i> Payments</h2>
                        </div>
                    </div>
                    <div>
                        <div class="card-body">
                            <div class="card-title">Payment Received Today: ₹<?= number_format($todayPaid, 2) ?></div>
                            <div class="card-sub">Total Payment Received: ₹<?= number_format($totalPaid, 2) ?></div>
                        </div>
                        <div class="card-body2">
                            <div class="card-title">Treatments: ₹<?= number_format($todayPatientTreatmentPaid, 2) ?></div>
                            <div class="card-sub">From ongoing patient treatments.</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-header">
                            <h2><i class="fa-solid fa-money-bill-transfer"></i> Today's Collections</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-title">Registration: ₹<?= number_format($todayRegistrationPaid, 2) ?></div>
                        <div class="card-sub">From new patient registrations.</div>
                    </div>
                    <div class="card-body2">
                        <div class="card-title">Tests: ₹<?= number_format($todayTestPaid, 2) ?></div>
                        <div class="card-sub">From diagnostic tests.</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <main class="main2">
        <section class="schedule">
            <div class="schedule-header">
                <h2>Schedule</h2>
                <span class="arrow" onclick="window.location.href='schedule.php';">↗</span>
            </div>

            <div class="text">
                <h2>Today's Schedule <?php echo $todayDisplay; ?></h2>
            </div>
            <br>
            <!-- Timeline -->
            <div class="timeline">
                <?php if (isset($groupedSchedules[$today]) && count($groupedSchedules[$today]) > 0): ?>
                    <?php foreach ($groupedSchedules[$today] as $slot): ?>
                        <div class="time-slot">
                            <div class="time"><?= date("h:i a", strtotime($slot['appointment_time'])) ?></div>
                            <div class="circle">•</div>
                            <div class="event <?= strtolower($slot['status']) ?>">
                                <div class="event-title"><?= htmlspecialchars($slot['patient_name']) ?></div>
                                <div class="event-time">
                                    <?= date("h:i a", strtotime($slot['appointment_time'])) ?> -
                                    <?= date("h:i a", strtotime($slot['appointment_time'] . " +30 minutes")) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-events">No appointments scheduled for today.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="form">
            <div class="form-box2 form-wrapper">
                <div class="quick-view-header">
                    <h2 style="font-weight: 700;">New Registration</h2>
                    <div class="slider-toggle" id="sliderToggle">
                        <div class="slider-indicator"></div>
                        <button class="active" data-index="0">Registration</button>
                        <button data-index="1">Test</button>
                    </div>
                </div>

                <!-- Inquiry Form -->
                <!-- <?php if ($success): ?>
            <p style="color:green;">Registration form submitted successfully!</p>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <ul style="color:red;">
                <?php foreach ($errors as $err) echo "<li>$err</li>"; ?>
            </ul>
            <?php endif; ?> -->

                <form id="inquiryForm" method="post" class="form-section active">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                    <div>
                        <label>Enter Patient Name *</label>
                        <input type="text" name="patient_name" required>
                    </div>
                    <div>
                        <label>Age *</label>
                        <input type="number" name="age" min="1" required>
                    </div>

                    <div class="select-wrapper">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label>Referred By *</label>
                        <input list="referrers-list" id="registration_referred_by" name="referred_by" placeholder="Type or select a doctor" required>
                        <datalist id="referrers-list">
                            <?php foreach ($referrers as $referrer): ?>
                                <option value="<?= htmlspecialchars($referrer) ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="select-wrapper">
                        <label>Cheif Complain *</label>
                        <select name="conditionType">
                            <option value="other">Select your condition</option>
                            <option value="neck_pain">Neck Pain</option>
                            <option value="back_pain">Back Pain</option>
                            <option value="low_back_pain">Low Back Pain</option>
                            <option value="radiating_pain">Radiating Pain</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label>Occupation</label>
                        <input type="text" name="occupation">
                    </div>

                    <div>
                        <label>Enter Patient Phone No *</label>
                        <input type="text" name="phone" required maxlength="10">
                    </div>

                    <div>
                        <label>Enter Patient Email</label>
                        <input type="email" name="email">
                    </div>

                    <div>
                        <label>Address</label>
                        <input type="text" name="address">
                    </div>

                    <div>
                        <label>Amount *</label>
                        <input type="number" name="amount" step="0.01" required>
                    </div>
                    <div class="select-wrapper">
                        <label>Payment Method *</label>
                        <select name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi-boi">UPI-BOI</option>
                            <option value="upi-hdfc">UPI-HDFC</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label>Describe Condition / Remarks</label>
                        <input type="text" name="remarks"></input>
                    </div>
                    <div class="select-wrapper">
                        <label>How did you hear about us</label>
                        <select name="referralSource">
                            <option value="self">Select</option>
                            <option value="doctor_referral">Doctor Referral</option>
                            <option value="web_search">Web Search</option>
                            <option value="social_media">Social Media</option>
                            <option value="returning_patient">Returning Patient</option>
                            <option value="local_event">Local Event</option>
                            <option value="advertisement">Advertisement</option>
                            <option value="employee">Employee</option>
                            <option value="family">Family</option>
                            <option value="self">Self</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="select-wrapper">
                        <label>Consultation Type *</label>
                        <select name="inquiry_type" required>
                            <option value="">Select Consultation Type</option>
                            <option value="In-Clinic">In-Clinic</option>
                            <option value="Speech-Therapy">Speech Therapy</option>
                            <option value="Phone">Home-Visit</option>
                            <option value="Online">Virtual/Online</option>
                        </select>
                    </div>

                    <div>
                        <label>Appointment Date</label>
                        <input type="date" name="appointment_date">
                    </div>

                    <div class="select-wrapper">
                        <label>Time Slot *</label>
                        <select name="appointment_time" id="appointment_time" required>
                        </select>
                    </div>

                    <div class="submit-btn2">
                        <button type="submit">Submit</button>
                    </div>
                </form>


                <form id="testForm" class="form-section" method="POST" action="../api/test_submission.php">
                    <!-- CSRF token, not changed -->
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                    <div>
                        <label>Patient Name *</label>
                        <input type="text" name="patient_name" placeholder="Enter Patient Name" required>
                    </div>
                    <div>
                        <label>Age *</label>
                        <input type="number" name="age" max="150" required>
                    </div>

                    <div>
                        <label>DOB</label>
                        <input type="date" name="dob">
                    </div>

                    <div class="select-wrapper">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label>Parents/Guardian</label>
                        <input type="text" name="parents" placeholder="Parents/Guardian Name">
                    </div>
                    <div>
                        <label>Relation</label>
                        <input type="text" name="relation" placeholder="e.g., Father, Mother">
                    </div>

                    <div>
                        <label>Enter Patient Phone No *</label>
                        <!-- Corrected name to match database: phone_number -->
                        <input type="text" name="phone_number" placeholder="+911234567890" maxlength="10" required>
                    </div>

                    <div>
                        <label>Enter Alternate Phone No</label>
                        <!-- Corrected name to match database: alternate_phone_no -->
                        <input type="text" name="alternate_phone_no" placeholder="+911234567890" maxlength="10">
                    </div>

                    <div>
                        <label>Referred By *</label>
                        <input list="referrers-list" id="test_form_referred_by" name="referred_by" placeholder="Type or select a doctor" required>
                        <datalist id="referrers-list">
                            <?php foreach ($referrers as $referrer): ?>
                                <option value="<?= htmlspecialchars($referrer) ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="select-wrapper">
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

                    <div class="select-wrapper">
                        <label>Limb</label>
                        <select name="limb">
                            <option value="">Select Limb</option>
                            <option value="upper_limb">Upper Limb</option>
                            <option value="lower_limb">Lower Limb</option>
                            <option value="both">Both</option>
                            <option value="none">None</option>
                        </select>
                    </div>

                    <div>
                        <label>Receipt No </label>
                        <input type="text" name="receipt_no">
                    </div>

                    <div>
                        <label>Date of Visit *</label>
                        <input type="date" name="visit_date" required>
                    </div>

                    <div>
                        <label>Assigned Test Date *</label>
                        <input type="date" name="assigned_test_date" required>
                    </div>

                    <div class="select-wrapper">
                        <label>Test Done By *</label>
                        <select name="test_done_by" required>
                            <option value="">Select Staff</option>
                            <option value="achal">Achal</option>
                            <option value="ashish">Ashish</option>
                            <option value="pancham">Pancham</option>
                            <option value="sayan">Sayan</option>
                        </select>
                    </div>

                    <div>
                        <label>Total Amount *</label>
                        <input type="number" name="total_amount" step="0.01" placeholder="Enter Amount" required>
                    </div>

                    <div>
                        <label>Advance Amount</label>
                        <input type="number" name="advance_amount" step="0.01" value="0"
                            placeholder="Enter Advance Amount">
                    </div>

                    <div>
                        <label>Due Amount</label>
                        <input type="number" name="due_amount" step="0.01" value="0" placeholder="Enter Due Amount">
                    </div>

                    <div>
                        <label>Discount</label>
                        <input type="number" name="discount" step="0.01" value="0" placeholder="Enter Discount">
                    </div>

                    <div class="select-wrapper">
                        <label>Payment Method *</label>
                        <select name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="upi-boi">UPI-BOI</option>
                            <option value="upi-hdfc">UPI-HDFC</option>
                            <option value="card">Card</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="col-span-2 submit-btn2">
                        <button type="submit">Submit Test</button>
                    </div>
                </form>
            </div>
        </section>
        <section>
            <div class="form-box">
                <div class="form-tabs">
                    <button id="inquiryTabUnique" class="tab-btn active">Inquiry</button>
                    <button id="testTabUnique" class="tab-btn">Test Inquiry</button>
                </div>

                <!-- Inquiry Form -->
                <form id="uniqueInquiryForm" class="form-content active">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                    <div class="form-row">
                        <div>
                            <label for="inquiry_patient_name">Patient Name *</label>
                            <input type="text" id="inquiry_patient_name" name="patient_name" placeholder="Name" required>
                        </div>
                        <div>
                            <label for="inquiry_age">Age *</label>
                            <input type="number" id="inquiry_age" name="age" placeholder="Age" min="1" required>
                        </div>
                        <div>
                            <label for="inquiry_gender">Gender *</label>
                            <select id="inquiry_gender" name="gender" required>
                                <option value="" disabled selected>Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="inquiry_referralSource">How did you hear about us? *</label>
                            <select id="inquiry_referralSource" name="referralSource" required>
                                <option value="" disabled selected>Select</option>
                                <option value="doctor_referral">Doctor Referral</option>
                                <option value="web_search">Web Search</option>
                                <option value="social_media">Social Media</option>
                                <option value="returning_patient">Returning Patient</option>
                                <option value="local_event">Local Event</option>
                                <option value="advertisement">Advertisement</option>
                                <option value="employee">Employee</option>
                                <option value="family">Family</option>
                                <option value="self">Self</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="inquiry_conditionType">Chief Complaint *</label>
                            <select id="inquiry_conditionType" name="conditionType" required>
                                <option value="" disabled selected>Select</option>
                                <option value="neck_pain">Neck Pain</option>
                                <option value="back_pain">Back Pain</option>
                                <option value="low_back_pain">Low Back Pain</option>
                                <option value="radiating_pain">Radiating Pain</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="inquiry_phone">Mobile No. *</label>
                            <input type="text" id="inquiry_phone" name="phone" placeholder="Mobile No." required maxlength="10">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="inquiry_remarks">Review</label>
                            <textarea id="inquiry_remarks" name="remarks" placeholder="Review"></textarea>
                        </div>
                        <div>
                            <label for="inquiry_expected_date">Expected Date *</label>
                            <input type="date" id="inquiry_expected_date" name="expected_date" required>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Submit</button>
                </form>

                <!-- Test Inquiry Form -->
                <form id="uniqueTestForm" class="form-content form-content2">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                    <div class="form-row">
                        <div>
                            <label for="test_patient_name">Patient Name *</label>
                            <input type="text" id="test_patient_name" name="patient_name" placeholder="Patient Name" required>
                        </div>
                        <div>
                            <label>Test Name *</label>
                            <select name="test_name" required>
                                <option value="">Select Test</option>
                                <!-- Your form options do not match your database enum, but we'll fix the PHP to match your form -->
                                <option value="eeg">EEG</option>
                                <option value="ncv">NCV</option>
                                <option value="emg">EMG</option>
                                <option value="rns">RNS</option>
                                <option value="bera">BERA</option>
                                <option value="vep">VEP</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="test_inquiry_referred_by">Referred By *</label>
                            <input list="referrers-list" id="test_inquiry_referred_by" name="referred_by" placeholder="Type or select a doctor" required>
                            <datalist id="referrers-list">
                                <?php foreach ($referrers as $referrer): ?>
                                    <option value="<?= htmlspecialchars($referrer) ?>">
                                    <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label for="test_phone_number">Mobile No. *</label>
                            <input type="text" id="test_phone_number" name="phone_number" placeholder="Mobile No." required maxlength="10">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="test_expected_visit_date">Expected Visit Date *</label>
                            <input type="date" id="test_expected_visit_date" name="expected_visit_date" required>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Submit</button>
                </form>
            </div>
        </section>
    </main>

    <div id="toast-container"></div>

    <!-- What's New Changelog Modal -->
    <div id="changelog-modal-overlay" class="changelog-overlay">
        <div class="changelog-modal">
            <!-- New Animated Intro Section -->
            <div id="changelog-intro" class="changelog-intro">
                <div class="icon"><i class="fa-solid fa-rocket"></i></div>
                <h2>System Updated!</h2>
                <p id="changelog-version-text"></p>
            </div>

            <!-- Main Changelog Content -->
            <div class="changelog-body" id="changelog-body">
                <!-- Content will be injected by JS -->
                <div class="changelog-loader"></div>
            </div>
            <div class="changelog-footer">
                <button onclick="window.location.href='changelog.html'">View Changelog</button>
                <button id="changelog-close-btn">Got it!</button>
            </div>
        </div>
    </div>

    <!-- NEW: System Update Overlay -->
    <div id="system-update-overlay" class="system-update-overlay">
        <div class="system-update-popup">
            <i class="fa-solid fa-gears fa-spin"></i>
            <h2>System Update in Progress</h2>
            <p>The system is currently being updated. Please wait a few minutes and try again. We appreciate your
                patience.</p>
            <p style="background-color:#007bff; width: auto; color: #ffffffff; padding: 4px; border-radius: 10px;">Expected time <strong>5 min</strong>.</p>
        </div>
    </div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        const currentUserId = <?= (int)($_SESSION['uid'] ?? 0) ?>;
    </script>

    <script src="../js/chat.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // ==========================================================
            // NEW: System Update Overlay Control
            // ==========================================================
            const systemUpdateOverlay = document.getElementById('system-update-overlay');

            function toggleSystemUpdate(show) {
                if (systemUpdateOverlay) {
                    systemUpdateOverlay.style.display = show ? 'flex' : 'none';
                }
            }

            // Example Usage: You can call these from your browser's console to test
            // To show: toggleSystemUpdate(true)
            // To hide: toggleSystemUpdate(false)
            window.toggleSystemUpdate = toggleSystemUpdate(true);

            // ==========================================================
            // NEW: System Update Banner Control
            // ==========================================================
            const systemUpdateBanner = document.getElementById('system-update-banner');
            const bannerMessageSpan = document.getElementById('update-banner-message');

            function toggleUpdateBanner(show, message = null) {
                if (systemUpdateBanner) {
                    systemUpdateBanner.style.display = show ? 'block' : 'none';
                    if (show && message && bannerMessageSpan) {
                        bannerMessageSpan.innerHTML = message; // Use innerHTML to allow for <strong> etc.
                    }
                }
            }

            // Example Usage (from browser console):
            // To show: toggleUpdateBanner(true)
            // To show with custom message: toggleUpdateBanner(true, "<strong>Maintenance Alert:</strong> System will be offline at midnight.")
            // To hide: toggleUpdateBanner(false)
            window.toggleUpdateBanner = toggleUpdateBanner(true);

            // ==========================================================
            // 1. Core Utilities: Toast Notifications
            // ==========================================================

            const toastContainer = document.getElementById("toast-container");

            function showToast(message, type = 'success') {
                if (!toastContainer) {
                    console.error("Toast container not found.");
                    return;
                }
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.textContent = message;

                toastContainer.appendChild(toast);

                setTimeout(() => toast.classList.add('show'), 10);

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        if (toastContainer.contains(toast)) {
                            toastContainer.removeChild(toast);
                        }
                    }, 500);
                }, 5000);
            }

            // ==========================================================
            // 2. Generic Form Handler
            // ==========================================================
            function attachFormHandler(formId, endpoint, successMsg = "Submitted successfully!") {
                const form = document.getElementById(formId);
                if (!form) return;

                form.addEventListener("submit", async function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const payload = Object.fromEntries(formData.entries());

                    try {
                        const response = await fetch(endpoint, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (result.success) {
                            showToast(result.message || successMsg, "success");
                            this.reset();
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);

                        } else {
                            showToast(result.message || "Submission failed.", "error");
                        }
                    } catch (error) {
                        console.error(`Error submitting ${formId}:`, error);
                        showToast("Something went wrong. Please try again.", "error");
                    }
                });
            }

            // ==========================================================
            // 3. Attach handlers to forms
            // ==========================================================
            attachFormHandler("uniqueInquiryForm", "../api/inquiry_submission.php", "Inquiry submitted successfully!");
            attachFormHandler("uniqueTestForm", "../api/inquiry_test_submission.php", "Test submitted successfully!");
            attachFormHandler("inquiryForm", "../api/registration_submission.php", "Registration submitted successfully!");

            <?php if (isset($_SESSION['success'])): ?>
                showToast("<?= htmlspecialchars($_SESSION['success']) ?>", 'success');
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['errors'])): ?>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    showToast("<?= htmlspecialchars($error) ?>", 'error');
                <?php endforeach;
                unset($_SESSION['errors']); ?>
            <?php endif; ?>

            // ==========================================================
            // 4. Time Slot Management (NOW DYNAMIC!)
            // ==========================================================
            const dateInput = document.querySelector("input[name='appointment_date']");
            const slotSelect = document.getElementById("appointment_time");

            /**
             * Fetches and populates time slots for a specific date.
             * @param {string} dateString - The date in 'YYYY-MM-DD' format.
             */
            function fetchSlotsForDate(dateString) {
                if (!dateString || !slotSelect) return; // Don't run if there's no date or select box

                // Clear existing options and show a loading state
                slotSelect.innerHTML = '<option>Loading slots...</option>';

                // Fetch slots for the given date
                fetch(`../api/get_slots.php?date=${dateString}`)
                    .then(res => res.json())
                    .then(data => {
                        // Clear the loading message
                        slotSelect.innerHTML = '';

                        if (data.success && data.slots.length > 0) {
                            data.slots.forEach(slot => {
                                const opt = document.createElement("option");
                                opt.value = slot.time;
                                opt.textContent = slot.label;
                                if (slot.disabled) {
                                    opt.disabled = true;
                                    opt.textContent += " (Booked)";
                                }
                                slotSelect.appendChild(opt);
                            });
                        } else {
                            // Handle cases with no slots or an error
                            const errorOption = document.createElement("option");
                            errorOption.textContent = data.message || "No slots available.";
                            errorOption.disabled = true;
                            slotSelect.appendChild(errorOption);
                            console.error(data.message);
                        }
                    })
                    .catch(err => {
                        slotSelect.innerHTML = '<option>Error loading slots.</option>';
                        console.error("Error fetching slots:", err);
                    });
            }

            // --- Attach the Event Listener ---
            // When the user picks a new date, re-fetch the slots.
            dateInput.addEventListener('change', (event) => {
                fetchSlotsForDate(event.target.value);
            });

            // --- Initial Load ---
            // When the page first loads, set today's date and fetch slots for today.
            const today = new Date().toISOString().split('T')[0]; // Gets today's date as 'YYYY-MM-DD'
            dateInput.value = today; // Set the input to today by default
            dateInput.min = today; // Optional: prevent booking for past dates
            fetchSlotsForDate(today);


            function updateDateTime() {
                var now = new Date();
                var datetimeString = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
                document.getElementById('datetime').textContent = datetimeString;
            }

            setInterval(updateDateTime, 1000); // Update every second
            // ==========================================================
            // 5. Final touches
            // ==========================================================
            document.body.classList.add("loaded");
        });

        // Hamburger Menu Logic
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.getElementById('hamburger-menu');
            const nav = document.querySelector('nav');

            if (hamburger && nav) {
                hamburger.addEventListener('click', function(event) {
                    event.stopPropagation();
                    const isOpening = !nav.classList.contains('open');
                    if (isOpening) {
                        nav.style.display = 'flex'; // Set display before animation
                        setTimeout(() => nav.classList.add('open'), 10); // Allow repaint
                    } else {
                        nav.classList.remove('open');
                    }
                });

                document.addEventListener('click', function(event) {
                    if (nav.classList.contains('open') && !nav.contains(event.target)) {
                        nav.classList.remove('open');
                    }
                });
            }
        });
    </script>
</body>

</html>