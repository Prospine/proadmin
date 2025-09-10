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
    $stmt = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $branchName = $stmt->fetch()['branch_name'] ?? '';

    // --- Appointments ---
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM appointments 
                           WHERE branch_id = :branch_id 
                             AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayAppointments = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM appointments 
                           WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $totalAppointments = (int) $stmt->fetch()['count'];

    // Appointments Requests

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM appointment_requests 
                           WHERE branch_id = :branch_id 
                             AND DATE(created_at) = :today");
    $stmt->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayAppointmentsReq = (int) $stmt->fetch()['count'];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS count 
                           FROM appointment_requests 
                           WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $totalAppointmentsReq = (int) $stmt->fetch()['count'];

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
        ORDER BY appointment_date, appointment_time
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
    $todayAppointments = $totalAppointments = 0;
    $todayAppointmentsReq = $totalAppointmentsReq = 0;
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
    <title>Document</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
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
                <a href="#" class="active">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a href="#">Registration</a>
                <a href="#">Patients</a>
                <a href="#">Appointments</a>
                <a href="#">Billing</a>
                <a href="#">Attendance</a>
                <a href="#">Tests</a>
                <a href="#">Reports</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"><?php echo $branchName; ?> Branch</div>
            <div class="icon-btn" id="theme-toggle">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </div>
            <div class="icon-btn icon-btn2" title="Notifications" onclick="openNotif()">🔔</div>
            <div class="profile" onclick="openForm()">S</div>
        </div>
    </header>
    <div class="menu" id="myMenu">
        <span class="closebtn" onclick="closeForm()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="#">Profile</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
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
    <main>
        <div class="content">
            <div class="card-header2"><?php echo $todayDisplay; ?></div>
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
                        <div class="card-title">Today's Appointments: <?php echo $todayAppointments; ?></div>
                        <div class="card-title">Today's Appointment Requests: <?php echo $todayAppointmentsReq; ?></div>
                        <div class="card-sub">Total Appointments: <?php echo $totalAppointments; ?></div>
                        <div class="card-sub">Total Appointments Requests: <?php echo $totalAppointmentsReq; ?></div>
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

                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-header">
                            <h2><i class="fa-solid fa-file-invoice-dollar"></i> Payments</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-title">Payment Received Today: ₹<?= number_format($todayPaid, 2) ?></div>
                        <div class="card-sub">Total Payment Received: ₹<?= number_format($totalPaid, 2) ?></div>
                    </div>

                    <div class="card-body2">
                        <div class="card-title">Total Dues Today : ₹<?= number_format($todayDues, 2) ?></div>
                        <div class="card-sub">Total Dues Amount: ₹<?= number_format($totalDues, 2) ?></div>
                    </div>
                </div>

            </div>
        </div>
    </main>
    <main class="main2">
        <section class="schedule">
            <div class="schedule-header">
                <h2>Schedule</h2>
                <span class="arrow">↗</span>
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
                            <div class="time"><?= date("H:i", strtotime($slot['appointment_time'])) ?></div>
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

                    <div>
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
                        <input type="text" name="referred_by" required>
                    </div>

                    <div>
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
                        <label>Occupation *</label>
                        <input type="text" name="occupation" required>
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
                    <div>
                        <label>Payment Method *</label>
                        <select name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label>Describe Condition / Remarks</label>
                        <input type="text" name="remarks"></input>
                    </div>
                    <div>
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
                    <div>
                        <label>Consultation Type *</label>
                        <select name="inquiry_type" required>
                            <option value="">Select Consultation Type</option>
                            <option value="In-Clinic">In-Clinic</option>
                            <option value="Phone">Home-Visit</option>
                            <option value="Phone">Virtual/Online</option>
                        </select>
                    </div>

                    <div>
                        <label>Appointment Date</label>
                        <input type="date" name="appointment_date">
                    </div>

                    <div>
                        <label>Time</label>
                        <input type="time" name="time">
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

                    <div>
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
                        <label>Referred By</label>
                        <input type="text" name="referred_by" placeholder="Doctor/Clinic Name">
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

                    <div>
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
                        <label>Receipt No *</label>
                        <input type="text" name="receipt_no" required>
                    </div>

                    <div>
                        <label>Date of Visit *</label>
                        <input type="date" name="visit_date" required>
                    </div>

                    <div>
                        <label>Assigned Test Date *</label>
                        <input type="date" name="assigned_test_date" required>
                    </div>

                    <div>
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
                        <input type="number" placeholder="Enter Discount">
                    </div>

                    <div>
                        <label>Payment Method *</label>
                        <select name="payment_method" required>
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
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
                            <label for="test_referred_by">Referred By *</label>
                            <input type="text" id="test_referred_by" name="referred_by" placeholder="Referred By" required>
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

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
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
            // ==========================================================
            // 4. PHP Session-based Toast (Initial Load)
            // ==========================================================
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
            // 5. Final touches
            // ==========================================================
            document.body.classList.add("loaded");
        });
    </script>

</body>

</html>