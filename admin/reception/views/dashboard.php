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
        WHERE branch_id = :branch_id AND DATE(created_at) = :today AND status != 'close'
    ");
    $stmtRegPayments->execute(['branch_id' => $branchId, 'today' => $today]);
    $todayRegistrationPaid = (float)$stmtRegPayments->fetchColumn();

    $stmtTestPayments = $pdo->prepare("
        SELECT SUM(advance_amount) FROM tests 
        WHERE branch_id = :branch_id AND DATE(visit_date) = :today AND test_status != 'cancelled'
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
                              AND payment_status IN ('paid', 'partial') AND test_status != 'cancelled'");
    $tests->execute(['branch_id' => $branchId]);
    $tests = $tests->fetchAll(PDO::FETCH_ASSOC);

    $inquiries = $pdo->prepare("SELECT consultation_amount, created_at 
                                FROM registration 
                                WHERE branch_id = :branch_id AND status != 'closed'");
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
          AND status NOT IN ('closed', 'cancelled')
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
    <?php
    // Add CSP header
    header("Content-Security-Policy: style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com;");
    ?>
    <title>Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configure Tailwind CDN for 'class' based dark mode
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>

    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />

    <style>
        body {
            background: linear-gradient(120deg, #e3e5e6, #e0f2f1);
            /* Changed from yellow/gold to light teal */
        }

        .dark body {
            /* Dark mode gradient from your original CSS */
            background: linear-gradient(147deg, #000000ff 100%, #000000ff 100%);
        }


        /* JS-controlled element visibility */
        .form-section,
        .form-content {
            display: none;
            opacity: 0;
            transition: opacity 0.15s ease-out;
        }

        .form-section.active,
        .form-content.active {
            display: grid;
            opacity: 1;
        }

        nav.open {
            display: flex !important;
        }

        .menu,
        .notification,
        .chat-inbox {
            display: none;
        }

        /* Dark mode datalist fix */
        input[list]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        @keyframes zoomIn95 {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes zoomOut95 {
            from {
                opacity: 1;
                transform: scale(1);
            }

            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }

        @keyframes slideInFromBottom5 {
            from {
                transform: translateY(1.25rem);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideOutToBottom5 {
            from {
                transform: translateY(0);
                opacity: 1;
            }

            to {
                transform: translateY(1.25rem);
                opacity: 0;
            }
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(0.5) rotate(180deg);
            }

            to {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }

        @keyframes slideUp1 {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp2 {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp3 {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulseCustom {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.05);
            }
        }

        @keyframes cardPop {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Utility classes */
        .animate-fade-in {
            animation: fadeIn 0.15s ease-out forwards;
        }

        .animate-fade-out {
            animation: fadeOut 0.15s ease-in forwards;
        }

        .animate-zoom-in-95 {
            animation: zoomIn95 0.15s ease-out forwards;
        }

        .animate-zoom-out-95 {
            animation: zoomOut95 0.15s ease-in forwards;
        }

        .animate-slide-in-bottom-5 {
            animation: slideInFromBottom5 0.3s ease-out forwards;
        }

        .animate-slide-out-bottom-5 {
            animation: slideOutToBottom5 0.2s ease-in forwards;
        }

        .animate-pop-in {
            animation: popIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
        }

        .animate-slide-up-1 {
            animation: slideUp1 0.3s ease-out forwards;
        }

        .animate-slide-up-2 {
            animation: slideUp2 0.3s ease-out 0.2s forwards;
            opacity: 0;
        }

        .animate-slide-up-3 {
            animation: slideUp3 0.3s ease-out 0.4s forwards;
            opacity: 0;
        }

        .animate-pulse-custom {
            animation: pulseCustom 0.3s ease-in-out;
        }

        .animate-card-pop {
            animation: cardPop 0.4s ease-out forwards;
        }

        /* Accessibility: Respect reduced motion */
        @media (prefers-reduced-motion: reduce) {

            .animate-fade-in,
            .animate-fade-out,
            .animate-zoom-in-95,
            .animate-zoom-out-95,
            .animate-slide-in-bottom-5,
            .animate-slide-out-bottom-5,
            .animate-pop-in,
            .animate-slide-up-1,
            .animate-slide-up-2,
            .animate-slide-up-3,
            .animate-pulse-custom,
            .animate-card-pop {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }

        /* Responsive fixes */
        @media (max-width: 768px) {
            .grid-cols-1 {
                grid-template-columns: 1fr;
            }

            #toast-container {
                top: 1rem;
                right: 0.5rem;
                max-width: 90%;
            }

            .system-update-overlay {
                padding: 1rem;
            }

            .system-update-popup {
                max-width: 90%;
                padding: 1rem;
            }
        }

        @media (min-width: 769px) and (max-width: 1024px) {
            .grid-cols-1 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* =================================== */
        /* NEW: Tablet-Only Responsive Styles  */
        /* =================================== */
        @media (max-width: 1024px) and (min-width: 768px) {

            /* Hide the main navigation bar on tablets */
            header nav {
                display: none;
            }

            /* Show the new hamburger button for the drawer */
            #menuBtn {
                display: inline-flex;
            }

            /* Make cards smaller and adjust layout */
            .cards {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 0.5rem;
            }

            .card {
                transform: scale(0.85);
                transform-origin: top left;
                padding: 0.75rem;
            }

            /* Adjust main content layout for tablets */
            .main2 {
                grid-template-columns: 1fr;
                /* Stack schedule and forms */
                gap: 1rem;
            }

            .schedule,
            .form,
            section:last-child {
                width: 100%;
            }

            /* NEW: Horizontal Schedule for Tablet View */
            .main2 .schedule .timeline {
                display: flex;
                overflow-x: auto;
                border-left: none;
                padding-left: 0;
                padding-bottom: 1rem; /* Space for scrollbar */
                gap: 1rem;
            }

            .main2 .schedule .time-slot {
                flex: 0 0 200px; /* Give each card a fixed width */
                position: static; /* Override absolute positioning of children */
                margin-bottom: 0;
                display: flex;
                flex-direction: column;
            }

            .main2 .schedule .time,
            .main2 .schedule .circle {
                position: static; /* Override absolute positioning */
                width: auto;
                text-align: left;
                margin: 0;
            }

            .main2 .schedule .time {
                order: 1; /* Show time below the event card */
                margin-top: 0.5rem;
            }

            .main2 .schedule .event {
                margin-left: 0;
                order: 0; /* Show event card first */
            }
        }

        /* =================================== */
        /* NEW: Drawer Navigation Styles       */
        /* =================================== */
        #drawerNav {
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            backdrop-filter: blur(10px);
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }

        .dark #drawerNav {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }

        #drawerNav.open {
            transform: translateX(0);
        }

        #drawer-overlay {
            transition: opacity 0.3s ease;
        }

        #drawer-overlay.show {
            opacity: 1;
        }

        /* Hide the old hamburger menu on tablets */
        @media (max-width: 1024px) and (min-width: 768px) {
            .hamburger-menu {
                display: none;
            }
        }

        /* General styles for better responsiveness */
        body {
            background: linear-gradient(120deg, #e3e5e6, #e0f2f1);
        }

        .dark body {
            background: linear-gradient(147deg, #121212ff 0%, #434343 74%);
        }

        .form-section,
        .form-content {
            display: none;
            opacity: 0;
            transition: opacity 0.15s ease-out;
        }

        .form-section.active,
        .form-content.active {
            display: grid;
            opacity: 1;
        }

        nav.open {
            display: flex !important;
        }

        .menu,
        .notification,
        .chat-inbox {
            display: none;
        }

        .dark input[list]::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        /* Drawer link styles for beauty */
        #drawerNav nav a {
            transition: all 0.2s ease;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
        }

        #drawerNav nav a:hover {
            background: rgba(59, 130, 246, 0.1);
            transform: translateX(4px);
        }

        .dark #drawerNav nav a:hover {
            background: rgba(59, 130, 246, 0.2);
        }
    </style>
</head>

<body class="text-gray-900 dark:text-gray-100 font-sans transition-colors duration-200">

    <div class="mobile-blocker md:hidden fixed inset-0 bg-gray-100 dark:bg-gray-900 z-[1000] flex items-center justify-center p-6">
        <div class="mobile-blocker-popup bg-white dark:bg-gray-800 p-8 rounded-lg shadow-2xl text-center max-w-sm">
            <i class="fa-solid fa-mobile-screen-button popup-icon text-5xl text-teal-600 dark:text-teal-400 mb-4"></i>
            <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">Mobile View Not Supported</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">The admin panel is designed for desktop use. For the best experience on your mobile device, please download our dedicated application.</p>
            <a href="/download-app/index.html" class="mobile-download-btn inline-flex items-center justify-center gap-2 px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg shadow-md hover:bg-teal-700 transition-all">
                <i class="fa-solid fa-download"></i> Download App
            </a>
        </div>
    </div>

    <header class="flex items-center justify-between h-26 px-4 md:px-6 bg-white/80 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="logo-container flex items-center">
            <div class="logo h-30 flex items-center">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo" class="h-20 w-30">
                <?php else: ?>
                    <div class="logo-placeholder text-sm font-semibold text-gray-500 dark:text-gray-400">Primary Logo N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="hamburger-menu md:hidden" id="hamburger-menu">
            <button class="flex items-center justify-center w-10 h-10 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
        </div>

        <nav class="hidden absolute lg:relative top-16 left-0 lg:top-0 lg:left-0 w-full lg:w-auto bg-white dark:bg-gray-800 lg:bg-transparent dark:lg:bg-transparent shadow-md lg:shadow-none border-b lg:border-none dark:border-gray-700 lg:flex flex-col lg:flex-row py-4 lg:py-0">
            <div class="nav-links flex flex-col lg:flex-row lg:space-x-1 px-2 lg:px-0">
                <a href="dashboard.php" class="active flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium bg-teal-50 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400">
                    <i class="fa-solid fa-tachometer-alt w-4 text-center"></i><span>Dashboard</span>
                </a>
                <a href="inquiry.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-magnifying-glass w-4 text-center"></i><span>Inquiry</span>
                </a>
                <a href="registration.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-user-plus w-4 text-center"></i><span>Registration</span>
                </a>
                <a href="appointments.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-calendar-check w-4 text-center"></i><span>Appointments</span>
                </a>
                <a href="patients.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-users w-4 text-center"></i><span>Patients</span>
                </a>
                <a href="billing.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-file-invoice-dollar w-4 text-center"></i><span>Billing</span>
                </a>
                <a href="attendance.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-user-check w-4 text-center"></i><span>Attendance</span>
                </a>
                <a href="tests.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-vial w-4 text-center"></i><span>Tests</span>
                </a>
                <a href="reports.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-chart-line w-4 text-center"></i><span>Reports</span>
                </a>
                <a href="expenses.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white">
                    <i class="fa-solid fa-money-bill-wave w-4 text-center"></i><span>Expenses</span>
                </a>
            </div>
        </nav>

        <div class="nav-actions flex items-center gap-2">
            <button class="icon-btn flex items-center justify-center w-9 h-9 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" id="theme-toggle">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </button>
            <button class="inbox icon-btn icon-btn2 flex items-center justify-center w-9 h-9 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="Inbox" onclick="openInbox()">
                <i class="fa-solid fa-inbox"></i>
            </button>
            <button class="icon-btn icon-btn2 flex items-center justify-center w-9 h-9 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="Notifications" onclick="openNotif()">
                <i class="fa-solid fa-bell"></i>
            </button>
            <button class="profile flex items-center justify-center w-9 h-9 rounded-full bg-teal-600 text-white font-semibold cursor-pointer hover:bg-teal-700 transition-all" onclick="openForm()">
                R
            </button>

            <!-- Hamburger for tablets -->
            <button id="menuBtn" class="lg:hidden text-gray-700 hover:text-blue-600 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </header>

    <!-- ======= RIGHT-SIDE DRAWER NAV ======= -->
    <div id="drawerNav"
        class="fixed top-0 right-0 h-full w-64 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-bars text-teal-500"></i>
                Navigation
            </h2>
            <button id="closeBtn" class="text-gray-500 hover:text-red-500 text-xl font-bold transition-colors">&times;</button>
        </div>
        <nav class="flex flex-col p-4 space-y-1 text-gray-700 dark:text-gray-300 font-medium overflow-y-auto">
            <!-- Drawer navigation links will be populated by JS -->
        </nav>
    </div>

    <!-- Drawer Overlay -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 hidden pointer-events-none"></div>

    <div class="menu" id="myMenu">
        <div class="popup absolute top-16 right-4 md:right-6 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-[1001]"> <!-- Inner popup, animations applied here by JS -->
            <span class="closebtn absolute -top-2 -right-2 w-6 h-6 flex items-center justify-center bg-gray-200 dark:bg-gray-600 rounded-full text-gray-600 dark:text-gray-200 cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-500" onclick="closeForm()">&times;</span>
            <ul class="py-1">
                <li><a href="profile.php" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-user-circle w-4 text-center"></i> Profile
                    </a></li>
                <li class="logout"><a href="logout.php" class="flex items-center gap-3 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-sign-out-alt w-4 text-center"></i> Logout
                    </a></li>
            </ul>
        </div>
    </div>

    <div class="notification" id="myNotif">
        <div class="popup absolute top-16 right-4 md:right-20 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-[1001]"> <!-- Inner popup, animations applied here by JS -->
            <span class="closebtn absolute -top-2 -right-2 w-6 h-6 flex items-center justify-center bg-gray-200 dark:bg-gray-600 rounded-full text-gray-600 dark:text-gray-200 cursor-pointer hover:bg-gray-300 dark:hover:bg-gray-500" onclick="closeNotif()">&times;</span>
            <ul class="py-1">
                <li><a href="changelog.html" class="active2 flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                        View Changes (1)
                    </a></li>
            </ul>
        </div>
    </div>

    <div class="chat-inbox" id="myInbox">
        <div class="chat-container flex w-full h-full fixed bottom-5 right-5 w-full max-w-2xl lg:max-w-4xl max-h-[90vh] bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-[1050] overflow-hidden">

            <div class="chat-sidebar w-1.5/3 flex flex-col border-r border-gray-200 dark:border-gray-700">
                <div class="chat-sidebar-header flex items-center p-3 border-b border-gray-200 dark:border-gray-700 gap-2">
                    <input type="text" id="chat-user-search" placeholder="Search users..." class="flex-1 w-full px-3 py-1.5 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    <button id="chat-refresh-btn" class="chat-header-btn flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50" title="Refresh Chat">
                        <i class="fa-solid fa-sync"></i>
                    </button>
                    <span class="closebtn flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-2xl" onclick="closeInbox()">&times;</span>
                </div>
                <div class="chat-user-list flex-1 overflow-y-auto p-2 space-y-1" id="chat-user-list">
                    <div class="chat-loader text-center p-4 text-sm text-gray-500 dark:text-gray-400">Loading users...</div>
                </div>
            </div>

            <div class="chat-main flex-1 flex flex-col hidden">
                <div class="chat-header p-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white" id="chat-header-name"></h2>
                    <div class="encryption-status text-xs text-gray-400 dark:text-gray-500 pt-1">
                        <i class="fa-solid fa-lock"></i> Messages are end-to-end encrypted
                    </div>
                </div>

                <div class="chat-messages flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900" id="chat-messages">
                </div>

                <div class="chat-input-area flex items-center p-3 border-t border-gray-200 dark:border-gray-700 gap-3">
                    <input type="text" id="chat-message-input" placeholder="Type your message..." class="flex-1 w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-full shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    <button id="chat-send-btn" class="w-10 h-10 flex-shrink-0 flex items-center justify-center rounded-full bg-teal-600 text-white hover:bg-teal-700">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
            </div>

            <div class="chat-welcome-main flex-1 flex flex-col items-center justify-center text-sm text-gray-500 dark:text-gray-400 p-4">
                <i class="fa-solid fa-comments text-4xl mb-4 text-gray-400"></i>
                <h3 class="font-semibold text-lg text-gray-700 dark:text-gray-300">Select a chat</h3>
                <p>Choose a user from the list to start messaging.</p>
                <div class="encryption-status text-center text-xs text-gray-400 dark:text-gray-500 pt-4">
                    <i class="fa-solid fa-lock"></i> Messages are end-to-end encrypted
                </div>
            </div>

        </div>
    </div>

    <main class="p-4 md:p-6">
        <div id="system-update-banner" class="system-update-banner flex items-center gap-3 p-4 mb-4 rounded-lg border bg-yellow-50 border-yellow-300 text-yellow-800 dark:bg-yellow-950 dark:border-yellow-800 dark:text-yellow-200">
            <i class="fa-solid fa-triangle-exclamation text-lg"></i>
            <span id="update-banner-message" class="text-sm">
                <strong>System Update Scheduled:</strong> The system will be undergoing maintenance today from 10:00 PM to 11:00 PM. Access may be intermittent.
            </span>
        </div>
        <div id="system-updated-banner" class="system-updated-banner hidden items-center gap-3 p-4 mb-4 rounded-lg border bg-green-50 border-green-300 text-green-800 dark:bg-green-950 dark:border-green-800 dark:text-green-200">
            <i class="fa-solid fa-circle-check text-lg"></i>
            <span id="updated-banner-message" class="text-sm">
                <strong>System Update Complete!</strong> The system has been successfully updated. You can now continue your work.
            </span>
        </div>

        <div class="content bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm p-4 border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <div class="heaad flex justify-between items-center mb-4">
                <div class="card-header2 text-sm text-gray-600 dark:text-gray-400"><span id="datetime"><?php echo date('Y-m-d h:i:s A'); ?></span></div>
                <div class="icon-btn icon-btn3 px-3 py-1.5 rounded-md border bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300" title="Branch">
                    <?php echo $branchName; ?> Branch
                </div>
            </div>

            <div class="cards grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 gap-6">

                <div class="card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <div class="card-header-flex p-6 pb-2">
                        <div class="card-header">
                            <h2 class="flex items-center gap-3 text-base font-semibold text-gray-800 dark:text-gray-100">
                                <i class="fa-solid fa-calendar-check text-teal-500"></i>
                                <span>Registration & Appointments</span>
                            </h2>
                        </div>
                    </div>
                    <div class="card-body p-6 pt-2">
                        <div class="card-title text-3xl font-bold"><?= $todayInquiries ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Total Registrations: <?= $totalInquiries ?></div>
                    </div>
                    <div class="card-body2 p-6 pt-4 border-t border-gray-100 dark:border-gray-700 mt-auto">
                        <div class="card-title text-xl font-semibold"><?= $todayAppointmentsConducted; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Conducted Today</div>
                        <div class="card-title text-xl font-semibold mt-2"><?= $todayAppointmentsInQueue; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Appointments in Queue</div>
                    </div>
                </div>

                <div class="card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <div class="card-header-flex p-6 pb-2">
                        <div class="card-header">
                            <h2 class="flex items-center gap-3 text-base font-semibold text-gray-800 dark:text-gray-100">
                                <i class="fa-solid fa-envelope text-teal-500"></i>
                                <span>Inquiry</span>
                            </h2>
                        </div>
                    </div>
                    <div class="card-body p-6 pt-2">
                        <div class="card-title text-3xl font-bold"><?= $todayquickInquiry; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Today's Quick Inquiries</div>
                    </div>
                    <div class="card-body2 p-6 pt-4 border-t border-gray-100 dark:border-gray-700 mt-auto">
                        <div class="card-title text-xl font-semibold"><?= $todaytestInquiry; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Today's Test Inquiries</div>
                        <div class="card-title text-xl font-semibold mt-2"><?= $totalquickInquiry + $totaltestInquiry; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Total Inquiries</div>
                    </div>
                </div>

                <div class="card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <div class="card-header-flex p-6 pb-2">
                        <div class="card-header">
                            <h2 class="flex items-center gap-3 text-base font-semibold text-gray-800 dark:text-gray-100">
                                <i class="fa-solid fa-user-group text-teal-500"></i>
                                <span>Patients</span>
                            </h2>
                        </div>
                    </div>
                    <div class="card-body p-6 pt-2">
                        <div class="card-title text-3xl font-bold"><?= $todayPatients; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Enrolled Today</div>
                    </div>
                    <div class="card-body2 p-6 pt-4 border-t border-gray-100 dark:border-gray-700 mt-auto">
                        <div class="card-title text-xl font-semibold"><?= $totalPatients; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Total Enrolled Patients</div>
                        <div class="card-title text-xl font-semibold mt-2"><?= $ongoingPatients; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Ongoing Treatments</div>
                    </div>
                </div>

                <div class="card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <div class="card-header-flex p-6 pb-2">
                        <div class="card-header">
                            <h2 class="flex items-center gap-3 text-base font-semibold text-gray-800 dark:text-gray-100">
                                <i class="fa-solid fa-vial text-teal-500"></i>
                                <span>Tests</span>
                            </h2>
                        </div>
                    </div>
                    <div class="card-body p-6 pt-2">
                        <div class="card-title text-3xl font-bold"><?= $todayPendingTests; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Scheduled Today</div>
                    </div>
                    <div class="card-body2 p-6 pt-4 border-t border-gray-100 dark:border-gray-700 mt-auto">
                        <div class="card-title text-xl font-semibold"><?= $totalPendingTests; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Tests in Queue</div>
                        <div class="card-title text-xl font-semibold mt-2"><?= $totalCompletedTests; ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Total Tests Conducted</div>
                    </div>
                </div>

                <div class="card spcard2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <div class="card-header-flex p-6 pb-2">
                        <div class="card-header">
                            <h2 class="flex items-center gap-3 text-base font-semibold text-gray-800 dark:text-gray-100">
                                <i class="fa-solid fa-file-invoice-dollar text-teal-500"></i>
                                <span>Payments</span>
                            </h2>
                        </div>
                    </div>
                    <div class="card-body card-body3 p-6 pt-2">
                        <div class="card-title text-xl font-bold">₹<?= number_format($todayPaid, 2) ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Payment Received Today</div>
                        <div class="card-title text-xl font-semibold mt-2">₹<?= number_format($totalPaid, 2) ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">Total Payment Received</div>
                    </div>
                    <div class="card-body2 p-6 pt-4 border-t border-gray-100 dark:border-gray-700 mt-auto">
                        <div class="card-title text-xl font-semibold">₹<?= number_format($todayPatientTreatmentPaid, 2) ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">From ongoing patient treatments.</div>
                    </div>
                </div>

                <div class="card bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col">
                    <div class="card-header-flex p-6 pb-2">
                        <div class="card-header">
                            <h2 class="flex items-center gap-3 text-base font-semibold text-gray-800 dark:text-gray-100">
                                <i class="fa-solid fa-money-bill-transfer text-teal-500"></i>
                                <span>Today's Collections</span>
                            </h2>
                        </div>
                    </div>
                    <div class="card-body p-6 pt-2">
                        <div class="card-title text-xl font-semibold">₹<?= number_format($todayRegistrationPaid, 2) ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">From new patient registrations.</div>
                    </div>
                    <div class="card-body2 p-6 pt-4 border-t border-gray-100 dark:border-gray-700 mt-auto">
                        <div class="card-title text-xl font-semibold">₹<?= number_format($todayTestPaid, 2) ?></div>
                        <div class="card-sub text-sm text-gray-500 dark:text-gray-400">From diagnostic tests.</div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <main class="main2 p-6 pt-0 grid grid-cols-1 xl:grid-cols-7 gap-6">

        <section class="schedule xl:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/60 dark:border-gray-700/60 shadow-sm p-6">
            <div class="schedule-header flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Schedule</h2>
                <span class="arrow text-lg text-gray-400 dark:text-gray-500 hover:text-teal-600 dark:hover:text-teal-400 cursor-pointer transition-all" onclick="window.location.href='schedule.php';">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </span>
            </div>

            <div class="text mb-4">
                <h2 class="text-base font-medium text-gray-700 dark:text-gray-300">Today's Schedule (<?php echo $todayDisplay; ?>)</h2>
            </div>

            <div class="timeline relative border-l-2 border-gray-200 dark:border-gray-700 space-y-6 pl-20">
                <?php if (isset($groupedSchedules[$today]) && count($groupedSchedules[$today]) > 0): ?>
                    <?php foreach ($groupedSchedules[$today] as $slot): ?>

                        <?php
                        // Define color variables based on status
                        $status = strtolower($slot['status']);
                        $dotColor = 'bg-gray-400';
                        $timeColor = 'text-gray-400 dark:text-gray-500';
                        $eventColors = 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-700';

                        if ($status === 'pending') {
                            $dotColor = 'bg-orange-500'; // Orange dot for both modes
                            $timeColor = 'text-orange-600 dark:text-orange-400'; // Orange time text for both modes
                            $eventColors = 'bg-orange-50 dark:bg-orange-950/50 border-orange-200 dark:border-orange-800'; // Orange background for both modes
                        } elseif ($status === 'consulted' || $status === 'completed') { // Catches both
                            $dotColor = 'bg-green-500';
                            $timeColor = 'text-green-600 dark:text-green-400';
                            $eventColors = 'bg-green-50 dark:bg-green-950/50 border-green-200 dark:border-green-800';
                        } elseif ($status === 'cancelled') {
                            $dotColor = 'bg-gray-400'; // Use a neutral gray for cancelled
                            $timeColor = 'text-gray-400 dark:text-gray-500';
                            $eventColors = 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-700';
                        }
                        ?>

                        <div class="time-slot relative">
                            <div class="time absolute -left-20 top-5 w-16 text-right text-xs font-semibold <?= $timeColor ?>">
                                <?= date("h:i a", strtotime($slot['appointment_time'])) ?>
                            </div>

                            <div class="circle absolute -left-[4px] top-5 w-4 h-4 <?= $dotColor ?> rounded-full border-4 border-white dark:border-gray-800"></div>

                            <div class="event <?= $status ?> ml-6 p-4 rounded-lg border <?= $eventColors ?>">
                                <div class="event-title font-semibold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($slot['patient_name']) ?></div>
                                <div class="event-time text-xs text-gray-500 dark:text-gray-400">
                                    <?= date("h:i a", strtotime($slot['appointment_time'])) ?> -
                                    <?= date("h:i a", strtotime($slot['appointment_time'] . " +30 minutes")) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-events text-sm text-gray-500 dark:text-gray-400 italic pl-4">No appointments scheduled for today.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="form xl:col-span-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/60 dark:border-gray-700/60 shadow-sm p-6 w-full">
            <div class="form-box2 form-wrapper">
                <div class="quick-view-header flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">New Registration</h2>

                    <div class="slider-toggle relative flex p-1 bg-gray-100 dark:bg-gray-700 rounded-lg w-80" id="sliderToggle">

                        <div class="slider-indicator absolute top-1 left-1 h-8 w-[calc(50%-4px)] bg-white dark:bg-gray-800 rounded-md shadow-sm transition-transform duration-300 ease-in-out"></div>

                        <button class="active relative z-10 flex-1 px-3 py-1.5 text-sm font-medium text-gray-800 dark:text-white whitespace-nowrap" data-index="0">Registration</button>
                        <button class="relative z-10 flex-1 px-3 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap" data-index="1">Test</button>
                    </div>
                </div>

                <form id="inquiryForm" method="post" class="form-section active grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enter Patient Name *</label>
                        <input type="text" name="patient_name" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Age *</label>
                        <input type="number" name="age" min="1" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender *</label>
                        <select name="gender" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Referred By *</label>
                        <input list="referrers-list" id="registration_referred_by" name="referred_by" placeholder="Type or select a doctor" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        <datalist id="referrers-list">
                            <?php foreach ($referrers as $referrer): ?>
                                <option value="<?= htmlspecialchars($referrer) ?>">
                                <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chief Complaint *</label>
                        <select name="conditionType" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="other">Select your condition</option>
                            <option value="neck_pain">Neck Pain</option>
                            <option value="back_pain">Back Pain</option>
                            <option value="low_back_pain">Low Back Pain</option>
                            <option value="radiating_pain">Radiating Pain</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Occupation</label>
                        <input type="text" name="occupation" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enter Patient Phone No *</label>
                        <input type="text" name="phone" required maxlength="10" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enter Patient Email</label>
                        <input type="email" name="email" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                        <input type="text" name="address" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount *</label>
                        <input type="number" name="amount" step="0.01" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method *</label>
                        <select name="payment_method" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi-boi">UPI-BOI</option>
                            <option value="upi-hdfc">UPI-HDFC</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Describe Condition / Remarks</label>
                        <input type="text" name="remarks" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500"></input>
                    </div>
                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">How did you hear about us</label>
                        <select name="referralSource" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Consultation Type *</label>
                        <select name="inquiry_type" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select Consultation Type</option>
                            <option value="In-Clinic">In-Clinic</option>
                            <option value="Speech-Therapy">Speech Therapy</option>
                            <option value="Phone">Home-Visit</option>
                            <option value="Online">Virtual/Online</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Appointment Date</label>
                        <input type="date" name="appointment_date" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Slot *</label>
                        <select name="appointment_time" id="appointment_time" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </select>
                    </div>

                    <div class="submit-btn2 md:col-span-2 mt-4">
                        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 dark:focus:ring-offset-gray-800 transition-all">Submit</button>
                    </div>
                </form>


                <form id="testForm" class="form-section hidden grid grid-cols-1 md:grid-cols-2 gap-4" method="POST" action="../api/test_submission.php">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Patient Name *</label>
                        <input type="text" name="patient_name" placeholder="Enter Patient Name" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Age *</label>
                        <input type="number" name="age" max="150" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">DOB</label>
                        <input type="date" name="dob" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender *</label>
                        <select name="gender" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parents/Guardian</label>
                        <input type="text" name="parents" placeholder="Parents/Guardian Name" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Relation</label>
                        <input type="text" name="relation" placeholder="e.g., Father, Mother" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enter Patient Phone No *</label>
                        <input type="text" name="phone_number" placeholder="+911234567890" maxlength="10" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Enter Alternate Phone No</label>
                        <input type="text" name="alternate_phone_no" placeholder="+911234567890" maxlength="10" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Referred By *</label>
                        <input list="referrers-list" id="test_form_referred_by" name="referred_by" placeholder="Type or select a doctor" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Name *</label>
                        <select name="test_name" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Limb</label>
                        <select name="limb" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select Limb</option>
                            <option value="upper_limb">Upper Limb</option>
                            <option value="lower_limb">Lower Limb</option>
                            <option value="both">Both Limbs</option>
                            <option value="none">None</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Receipt No </label>
                        <input type="text" name="receipt_no" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date of Visit *</label>
                        <input type="date" name="visit_date" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assigned Test Date *</label>
                        <input type="date" name="assigned_test_date" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Done By *</label>
                        <select name="test_done_by" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select Staff</option>
                            <option value="achal">Achal</option>
                            <option value="ashish">Ashish</option>
                            <option value="pancham">Pancham</option>
                            <option value="sayan">Sayan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total Amount *</label>
                        <input type="number" name="total_amount" step="0.01" placeholder="Enter Amount" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Advance Amount</label>
                        <input type="number" name="advance_amount" step="0.01" value="0" placeholder="Enter Advance Amount" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Amount</label>
                        <input type="number" name="due_amount" step="0.01" value="0" placeholder="Enter Due Amount" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount</label>
                        <input type="number" name="discount" step="0.01" value="0" placeholder="Enter Discount" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>

                    <div class="select-wrapper">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Payment Method *</label>
                        <select name="payment_method" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="upi-boi">UPI-BOI</option>
                            <option value="upi-hdfc">UPI-HDFC</option>
                            <option value="card">Card</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="col-span-2 submit-btn2 md:col-span-2 mt-4">
                        <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 dark:focus:ring-offset-gray-800 transition-all">Submit Test</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/60 dark:border-gray-700/60 shadow-sm p-6">
            <div class="form-box">
                <div class="form-tabs flex p-1 bg-gray-100 dark:bg-gray-700 rounded-lg mb-6">
                    <button id="inquiryTabUnique" class="tab-btn active flex-1 px-3 py-1.5 text-sm font-medium bg-white dark:bg-gray-800 rounded-md shadow-sm text-gray-800 dark:text-white"
                        data-form="uniqueInquiryForm">Inquiry</button>
                    <button id="testTabUnique" class="tab-btn flex-1 px-3 py-1.5 text-sm font-medium text-gray-500 dark:text-gray-400"
                        data-form="uniqueTestForm">Test Inquiry</button>
                </div>

                <form id="uniqueInquiryForm" action="../api/inquiry_submission.php" method="POST" class="form-content active grid grid-cols-1 gap-4">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                    <div class="form-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="inquiry_patient_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Patient Name *</label>
                            <input type="text" id="inquiry_patient_name" name="patient_name" placeholder="Name" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div>
                            <label for="inquiry_age" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Age *</label>
                            <input type="number" id="inquiry_age" name="age" placeholder="Age" min="1" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>
                    <div class="form-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="inquiry_gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Gender *</label>
                            <select id="inquiry_gender" name="gender" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                                <option value="" disabled selected>Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="inquiry_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inquiry Service *</label>
                            <select name="inquiry_type" id="inquiry_type" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                                <option value="" disabled selected>select inquiry service</option>
                                <option value="physio">Physio</option>
                                <option value="speech_therapy">Speech Therapy</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="inquiry_referralSource" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">How did you hear about us? *</label>
                            <select id="inquiry_referralSource" name="referralSource" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
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

                        <div>
                            <label for="inquiry_communication_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inquiry Communication Type *</label>
                            <select id="inquiry_communication_type" name="communication_type" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                                <option value="" disabled selected>Select</option>
                                <option value="by_visit">By Visit</option>
                                <option value="phone">Phone</option>
                                <option value="web">Web</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="inquiry_conditionType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chief Complaint *</label>
                            <select id="inquiry_conditionType" name="conditionType" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                                <option value="" disabled selected>Select</option>
                                <option value="neck_pain">Neck Pain</option>
                                <option value="back_pain">Back Pain</option>
                                <option value="low_back_pain">Low Back Pain</option>
                                <option value="radiating_pain">Radiating Pain</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="inquiry_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mobile No. *</label>
                            <input type="text" id="inquiry_phone" name="phone" placeholder="Mobile No." required maxlength="10" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <div class="form-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="inquiry_remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                            <textarea id="inquiry_remarks" name="remarks" placeholder="Remarks... " class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500 min-h-[80px]"></textarea>
                        </div>
                        <div>
                            <label for="inquiry_expected_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plan to visit Date *</label>
                            <input type="date" id="inquiry_expected_date" name="expected_date" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <button type="submit" class="submit-btn w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 dark:focus:ring-offset-gray-800 transition-all mt-4">Submit</button>
                </form>

                <form id="uniqueTestForm" action="../api/inquiry_test_submission.php" method="POST" class="form-content form-content2 hidden grid grid-cols-1 gap-4">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                    <div class="form-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="test_patient_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Patient Name *</label>
                            <input type="text" id="test_patient_name" name="patient_name" placeholder="Patient Name" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test Name *</label>
                            <select name="test_name" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
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
                    </div>

                    <div class="form-row grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="test_inquiry_referred_by" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Referred By *</label>
                            <input list="referrers-list" id="test_inquiry_referred_by" name="referred_by" placeholder="Type or select a doctor" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div>
                            <label for="test_phone_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mobile No. *</label>
                            <input type="text" id="test_phone_number" name="phone_number" placeholder="Mobile No." required maxlength="10" class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label for="test_expected_visit_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Expected Visit Date *</label>
                            <input type="date" id="test_expected_visit_date" name="expected_visit_date" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <button type="submit" class="submit-btn w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 dark:focus:ring-offset-gray-800 transition-all mt-4">Submit</button>
                </form>
            </div>
        </section>
    </main>

    <div id="toast-container" class="fixed top-20 right-6 w-full max-w-sm z-[9999] space-y-3">
    </div>

    <div id="system-update-overlay" class="system-update-overlay hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[9998] flex items-center justify-center p-6">
        <div class="system-update-popup bg-white dark:bg-gray-800 p-8 rounded-lg shadow-2xl text-center max-w-md">
            <i class="fa-solid fa-gears fa-spin text-5xl text-teal-600 dark:text-teal-400 mb-6"></i>
            <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">System Update in Progress</h2>
            <p class="text-gray-600 dark:text-gray-300">The system is currently being updated. Please wait a few minutes and try again. We appreciate your patience.</p>
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
            // NEW: Tab handler for the new Quick Inquiry form
            // ==========================================================
            const inquiryTabs = document.querySelector('.form-box'); // Use class selector
            if (inquiryTabs) {
                const inquiryTabButtons = inquiryTabs.querySelectorAll('.tab-btn');
                const inquiryForms = inquiryTabs.querySelectorAll('.form-content');

                inquiryTabButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        // Update buttons
                        inquiryTabButtons.forEach(btn => {
                            btn.classList.remove('active', 'bg-white', 'dark:bg-gray-800', 'shadow-sm', 'text-gray-800', 'dark:text-white');
                            btn.classList.add('text-gray-500', 'dark:text-gray-400');
                        });
                        button.classList.add('active', 'bg-white', 'dark:bg-gray-800', 'shadow-sm', 'text-gray-800', 'dark:text-white');
                        button.classList.remove('text-gray-500', 'dark:text-gray-400');

                        // Get target form
                        const targetFormId = button.getAttribute('data-form');

                        // Update forms
                        document.getElementById('uniqueInquiryForm').classList.toggle('hidden', targetFormId !== 'uniqueInquiryForm');
                        document.getElementById('uniqueTestForm').classList.toggle('hidden', targetFormId !== 'uniqueTestForm');
                    });
                });
            }

            // ==========================================================
            // NEW: Tab handler for the Registration/Test form
            // ==========================================================
            const sliderToggle = document.getElementById('sliderToggle');
            if (sliderToggle) {
                const sliderButtons = sliderToggle.querySelectorAll('button');
                const sliderIndicator = sliderToggle.querySelector('.slider-indicator');
                const sliderForms = sliderToggle.closest('section.form').querySelectorAll('.form-section');

                sliderButtons.forEach((button, index) => {
                    button.addEventListener('click', () => {
                        // Update buttons
                        sliderButtons.forEach(btn => {
                            btn.classList.remove('active', 'text-gray-800', 'dark:text-white');
                            btn.classList.add('text-gray-500', 'dark:text-gray-400');
                        });
                        button.classList.add('active', 'text-gray-800', 'dark:text-white');
                        button.classList.remove('text-gray-500', 'dark:text-gray-400');

                        // Show form
                        sliderForms.forEach((form, formIndex) => {
                            // Use Tailwind's 'hidden' class for visibility
                            if (index === formIndex) {
                                form.classList.remove('hidden');
                            } else {
                                form.classList.add('hidden');
                            }
                            form.classList.toggle('active', index === formIndex);
                        });

                        // Also update the header title
                        const headerTitle = sliderToggle.closest('.form-wrapper').querySelector('.quick-view-header h2');
                        if (headerTitle) {
                            headerTitle.textContent = index === 0 ? 'New Registration' : 'New Test';
                        }

                        // Move indicator
                        if (sliderIndicator) {
                            sliderIndicator.style.transform = `translateX(calc(${index * 100}% + ${index * 1}px))`;
                        }
                    });
                });
            }


            // ==========================================================
            // ORIGINAL SCRIPT (INTACT)
            // ==========================================================
            const systemUpdateOverlay = document.getElementById('system-update-overlay');

            function toggleSystemUpdate(show) {
                if (systemUpdateOverlay) {
                    // Use Tailwind classes to show/hide
                    systemUpdateOverlay.classList.toggle('hidden', !show);
                    systemUpdateOverlay.classList.toggle('flex', show);
                }
            }

            window.toggleSystemUpdate = toggleSystemUpdate; // Removed auto-call

            const preUpdateBanner = document.getElementById('system-update-banner');
            const postUpdateBanner = document.getElementById('system-updated-banner');
            const preUpdateMessageSpan = document.getElementById('update-banner-message');
            const postUpdateMessageSpan = document.getElementById('updated-banner-message');

            const POST_UPDATE_BANNER_KEY = 'postUpdateBannerShown_v2.2.6';

            function toggleUpdateBanner(show, type = 1, message = null) {
                if (preUpdateBanner) preUpdateBanner.style.display = 'none';
                if (postUpdateBanner) postUpdateBanner.style.display = 'none';

                if (!show) {
                    return;
                }

                if (type === 1 && preUpdateBanner) {
                    preUpdateBanner.style.display = 'flex'; // Use flex for this
                    if (message && preUpdateMessageSpan) {
                        preUpdateMessageSpan.innerHTML = message;
                    }
                } else if (type === 2 && postUpdateBanner) {
                    if (localStorage.getItem(POST_UPDATE_BANNER_KEY)) {
                        return;
                    }
                    postUpdateBanner.style.display = 'flex'; // Use flex for this
                    if (message && postUpdateMessageSpan) {
                        postUpdateMessageSpan.innerHTML = message;
                    }
                    localStorage.setItem(POST_UPDATE_BANNER_KEY, 'true');
                    setTimeout(() => {
                        if (postUpdateBanner) postUpdateBanner.style.display = 'none';
                    }, 30000);
                }
            }

            // This was your original call - let's default it to hidden and let you call it
            // toggleUpdateBanner(true, 2, "<strong>System Update Complete!</strong> The system has been successfully updated. You can now continue your work.");
            // Example of pre-update banner
            toggleUpdateBanner();
            //  "<strong>System Update Scheduled:</strong> Maintenance today from 10:00 PM."

            const toastContainer = document.getElementById("toast-container");

            function showToast(message, type = 'success') {
                if (!toastContainer) {
                    console.error("Toast container not found.");
                    return;
                }
                const toast = document.createElement('div');
                const baseClasses = 'p-4 rounded-md shadow-lg text-white font-semibold transform opacity-0 translate-x-full'; // Base classes for toast
                const typeClasses = type === 'success' ? 'bg-green-600' : (type === 'error' ? 'bg-red-600' : 'bg-blue-600'); // Teal is main, but toasts often use standard success/error/info colors
                toast.className = `toast ${type} ${baseClasses} ${typeClasses} animate-slide-in-bottom-5`; // Apply custom slide-in animation
                toast.textContent = message;

                toastContainer.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    // The 'animate-slide-in-bottom-5' class handles the entry animation
                }, 10);

                // Animate out
                setTimeout(() => {
                    toast.classList.remove('animate-slide-in-bottom-5'); // Remove entry animation
                    toast.classList.add('animate-slide-out-bottom-5'); // Add exit animation
                    setTimeout(() => {
                        if (toastContainer.contains(toast)) {
                            toastContainer.removeChild(toast);
                        }
                    }, 500);
                }, 5000);
            }

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

            const dateInput = document.querySelector("input[name='appointment_date']");
            const slotSelect = document.getElementById("appointment_time");

            function fetchSlotsForDate(dateString) {
                if (!dateString || !slotSelect) return;

                slotSelect.innerHTML = '<option>Loading slots...</option>';
                slotSelect.disabled = true; // Disable during load

                fetch(`../api/get_slots.php?date=${dateString}`)
                    .then(res => res.json())
                    .then(data => {
                        slotSelect.innerHTML = '';
                        slotSelect.disabled = false;

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
                            const errorOption = document.createElement("option");
                            errorOption.textContent = data.message || "No slots available.";
                            errorOption.value = "";
                            errorOption.disabled = true;
                            slotSelect.appendChild(errorOption);
                        }
                    })
                    .catch(err => {
                        slotSelect.innerHTML = '<option>Error loading slots.</option>';
                        slotSelect.disabled = true;
                        console.error("Error fetching slots:", err);
                    });
            }

            if (dateInput) {
                dateInput.addEventListener('change', (event) => {
                    fetchSlotsForDate(event.target.value);
                });

                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
                dateInput.min = today;
                fetchSlotsForDate(today);
            }


            function updateDateTime() {
                var now = new Date();
                // Format: YYYY-MM-DD HH:MM:SS AM/PM
                var date = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2) + '-' + ('0' + now.getDate()).slice(-2);
                var time = ('0' + (now.getHours() % 12 || 12)).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' + ('0' + now.getSeconds()).slice(-2);
                var ampm = now.getHours() >= 12 ? 'PM' : 'AM';
                var datetimeString = date + ' ' + time + ' ' + ampm;

                const dtElement = document.getElementById('datetime');
                if (dtElement) {
                    dtElement.textContent = datetimeString;
                }
            }

            setInterval(updateDateTime, 1000);
            updateDateTime(); // Initial call

            // We keep your "loaded" class logic
            document.body.classList.add("loaded");
        });

        // NEW: Drawer Navigation for Tablet
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('menuBtn');
            const closeBtn = document.getElementById('closeBtn');
            const drawerNav = document.getElementById('drawerNav');
            const drawerOverlay = document.getElementById('drawer-overlay');
            const mainNavLinks = document.querySelector('header nav .nav-links');
            const drawerLinksContainer = document.querySelector('#drawerNav nav');

            if (menuBtn && closeBtn && drawerNav && drawerOverlay && mainNavLinks && drawerLinksContainer) {
                // Clone main navigation links into the drawer
                drawerLinksContainer.innerHTML = mainNavLinks.innerHTML;

                function openDrawer() {
                    drawerNav.classList.add('open');
                    drawerOverlay.classList.remove('hidden');
                    drawerOverlay.classList.add('show');
                    document.body.style.overflow = 'hidden'; // Prevent body scroll
                }

                function closeDrawer() {
                    drawerNav.classList.remove('open');
                    drawerOverlay.classList.add('hidden');
                    drawerOverlay.classList.remove('show');
                    document.body.style.overflow = ''; // Restore body scroll
                }

                menuBtn.addEventListener('click', openDrawer);

                closeBtn.addEventListener('click', closeDrawer);

                drawerOverlay.addEventListener('click', closeDrawer);

                // Close on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && drawerNav.classList.contains('open')) {
                        closeDrawer();
                    }
                });
            }
        });
    </script>
</body>

</html>