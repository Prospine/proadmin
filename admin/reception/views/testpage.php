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
    $todayPayments = $attPayments->fetchAll(PDO::FETCH_COLUMN);

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
    foreach ($todayPayments as $pay) {
        $amount = (float) $pay;
        $totalPaid += $amount;
        $todayPaid += $amount;
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
    $referrers = [];
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
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <style>
        /* General Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(120deg, #e3e5e6, #e0f2f1);
            color: #1f2937;
            transition: background 0.2s, color 0.2s;
            line-height: 1.5;
        }

        body.dark {
            background: linear-gradient(147deg, #121212 0%, #434343 74%);
            color: #e5e7eb;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input,
        select,
        textarea {
            font-family: inherit;
            font-size: 100%;
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

        @keyframes slideUp {
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

        /* Utility Animation Classes */
        .animate-fade-in {
            animation: fadeIn 0.15s ease-out forwards;
        }

        .animate-fade-out {
            animation: fadeOut 0.15s ease-in forwards;
        }

        .animate-zoom-in-95 {
            animation: zoomIn95 0.15s ease-out forwards;
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
            animation: slideUp 0.3s ease-out forwards;
        }

        .animate-slide-up-2 {
            animation: slideUp 0.3s ease-out 0.2s forwards;
            opacity: 0;
        }

        .animate-slide-up-3 {
            animation: slideUp 0.3s ease-out 0.4s forwards;
            opacity: 0;
        }

        .animate-pulse-custom {
            animation: pulseCustom 0.3s ease-in-out;
        }

        .animate-card-pop {
            animation: cardPop 0.4s ease-out forwards;
        }

        /* Respect Reduced Motion */
        @media (prefers-reduced-motion: reduce) {

            .animate-fade-in,
            .animate-fade-out,
            .animate-zoom-in-95,
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

        /* Header */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 6.5rem;
            padding: 0 1.5rem;
            background: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        body.dark header {
            background: rgba(31, 41, 55, 0.8);
            border-bottom-color: #374151;
        }

        .logo-container .logo img {
            height: 5rem;
            width: auto;
        }

        .logo-placeholder {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
        }

        body.dark .logo-placeholder {
            color: #9ca3af;
        }

        /* Navigation */
        nav {
            display: none;
            position: absolute;
            top: 4rem;
            left: 0;
            width: 100%;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }

        body.dark nav {
            background: #1f2937;
            border-bottom-color: #374151;
        }

        nav.open {
            display: flex;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            padding: 0 0.5rem;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            border-radius: 0.375rem;
            transition: background 0.2s, color 0.2s;
        }

        .nav-links a:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        body.dark .nav-links a:hover {
            background: #374151;
            color: #ffffff;
        }

        .nav-links a.active {
            background: #e6fffa;
            color: #0d9488;
        }

        body.dark .nav-links a.active {
            background: #0d9488;
            color: #ffffff;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .icon-btn {
            width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #6b7280;
            background: transparent;
            transition: background 0.2s, color 0.2s;
        }

        .icon-btn:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        body.dark .icon-btn:hover {
            background: #374151;
            color: #ffffff;
        }

        .profile {
            width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #0d9488;
            color: #ffffff;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .profile:hover {
            background: #0b8277;
        }

        /* Hamburger Menu */
        .hamburger-menu {
            display: flex;
        }

        @media (min-width: 768px) {
            .hamburger-menu {
                display: none;
            }

            nav {
                display: flex;
                position: static;
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }

            .nav-links {
                flex-direction: row;
                gap: 0.25rem;
            }
        }

        /* Drawer Navigation */
        #drawerNav {
            position: fixed;
            top: 0;
            right: 0;
            height: 100%;
            width: 16rem;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2);
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            z-index: 50;
        }

        body.dark #drawerNav {
            background: linear-gradient(135deg, #1f2937, #111827);
        }

        #drawerNav.open {
            transform: translateX(0);
        }

        #drawerNav .flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(to bottom, #ffffff, #f9fafb);
        }

        body.dark #drawerNav .flex {
            border-bottom-color: #374151;
            background: linear-gradient(to bottom, #1f2937, #111827);
        }

        #drawerNav nav a {
            display: block;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            transition: background 0.2s, transform 0.2s;
        }

        body.dark #drawerNav nav a {
            color: #d1d5db;
        }

        #drawerNav nav a:hover {
            background: rgba(59, 130, 246, 0.1);
            transform: translateX(4px);
        }

        body.dark #drawerNav nav a:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        #drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(4px);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 40;
        }

        #drawer-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        /* Mobile Blocker */
        .mobile-blocker {
            position: fixed;
            inset: 0;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 1000;
        }

        body.dark .mobile-blocker {
            background: #1f2937;
        }

        .mobile-blocker-popup {
            background: #ffffff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 20rem;
        }

        body.dark .mobile-blocker-popup {
            background: #1f2937;
            color: #ffffff;
        }

        .mobile-download-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #0d9488;
            color: #ffffff;
            font-weight: 600;
            border-radius: 0.375rem;
            transition: background 0.2s;
        }

        .mobile-download-btn:hover {
            background: #0b8277;
        }

        @media (min-width: 768px) {
            .mobile-blocker {
                display: none;
            }
        }

        /* Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        body.dark .card {
            background: #1f2937;
            border-color: #374151;
        }

        .card-header-flex {
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .card-header h2 {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
        }

        .card-body {
            padding: 1.5rem 1.5rem 0;
        }

        .card-body2 {
            padding: 1.5rem;
            border-top: 1px solid #f3f4f6;
            margin-top: auto;
        }

        body.dark .card-body2 {
            border-top-color: #374151;
        }

        .card-title {
            font-size: 1.875rem;
            font-weight: 700;
        }

        .card-sub {
            font-size: 0.875rem;
            color: #6b7280;
        }

        body.dark .card-sub {
            color: #9ca3af;
        }

        /* Schedule Section */
        .schedule {
            background: #ffffff;
            border: 1px solid rgba(229, 231, 235, 0.6);
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        body.dark .schedule {
            background: #1f2937;
            border-color: rgba(55, 65, 81, 0.6);
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .schedule-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .arrow {
            font-size: 1.125rem;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }

        .arrow:hover {
            color: #0d9488;
        }

        body.dark .arrow {
            color: #9ca3af;
        }

        body.dark .arrow:hover {
            color: #2dd4bf;
        }

        .timeline {
            position: relative;
            border-left: 2px solid #e5e7eb;
            padding-left: 5rem;
            margin-left: 1rem;
        }

        body.dark .timeline {
            border-left-color: #374151;
        }

        .time-slot {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .time {
            position: absolute;
            left: -5rem;
            top: 1.25rem;
            width: 4rem;
            text-align: right;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .circle {
            position: absolute;
            left: -0.25rem;
            top: 1.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            border: 4px solid #ffffff;
        }

        body.dark .circle {
            border-color: #1f2937;
        }

        .event {
            margin-left: 1.5rem;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        body.dark .event {
            border-color: #374151;
            background: rgba(55, 65, 81, 0.5);
        }

        .event.pending {
            border-color: #fed7aa;
            background: #fff7ed;
        }

        body.dark .event.pending {
            border-color: #9a3412;
            background: rgba(154, 52, 18, 0.5);
        }

        .event.consulted,
        .event.completed {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        body.dark .event.consulted,
        body.dark .event.completed {
            border-color: #15803d;
            background: rgba(21, 128, 61, 0.5);
        }

        .event-title {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .event-time {
            font-size: 0.75rem;
            color: #6b7280;
        }

        body.dark .event-time {
            color: #9ca3af;
        }

        .no-events {
            font-size: 0.875rem;
            color: #6b7280;
            font-style: italic;
            padding-left: 1rem;
        }

        body.dark .no-events {
            color: #9ca3af;
        }

        /* Forms */
        .form {
            background: #ffffff;
            border: 1px solid rgba(229, 231, 235, 0.6);
            border-radius: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            width: 100%;
        }

        body.dark .form {
            background: #1f2937;
            border-color: rgba(55, 65, 81, 0.6);
        }

        .quick-view-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .quick-view-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .slider-toggle {
            display: flex;
            padding: 0.25rem;
            background: #f3f4f6;
            border-radius: 0.5rem;
            width: 20rem;
            position: relative;
        }

        body.dark .slider-toggle {
            background: #374151;
        }

        .slider-indicator {
            position: absolute;
            top: 0.25rem;
            left: 0.25rem;
            height: 2rem;
            width: calc(50% - 0.25rem);
            background: #ffffff;
            border-radius: 0.375rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }

        body.dark .slider-indicator {
            background: #1f2937;
        }

        .slider-toggle button {
            flex: 1;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            background: transparent;
            border: none;
            cursor: pointer;
            position: relative;
            z-index: 10;
            transition: color 0.2s;
        }

        .slider-toggle button.active {
            color: #1f2937;
        }

        body.dark .slider-toggle button {
            color: #9ca3af;
        }

        body.dark .slider-toggle button.active {
            color: #ffffff;
        }

        .form-section {
            display: none;
            opacity: 0;
            transition: opacity 0.15s ease-out;
        }

        .form-section.active {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            opacity: 1;
        }

        .form-content {
            display: none;
            opacity: 0;
            transition: opacity 0.15s ease-out;
        }

        .form-content.active {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            opacity: 1;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }

        body.dark label {
            color: #d1d5db;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 0.5rem 0.75rem;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        body.dark input,
        body.dark select,
        body.dark textarea {
            background: #374151;
            border-color: #4b5563;
            color: #e5e7eb;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
        }

        .submit-btn,
        .submit-btn2 {
            width: 100%;
            padding: 0.625rem 1rem;
            background: #0d9488;
            color: #ffffff;
            font-weight: 500;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .submit-btn:hover,
        .submit-btn2:hover {
            background: #0b8277;
        }

        /* Toast Notifications */
        #toast-container {
            position: fixed;
            top: 5rem;
            right: 1.5rem;
            max-width: 20rem;
            z-index: 9999;
        }

        .toast {
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: #ffffff;
            font-weight: 600;
            transform: translateX(100%);
        }

        .toast.success {
            background: #16a34a;
        }

        .toast.error {
            background: #dc2626;
        }

        /* System Update Overlay */
        .system-update-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            z-index: 9998;
        }

        .system-update-overlay.show {
            display: flex;
        }

        .system-update-popup {
            background: #ffffff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 28rem;
        }

        body.dark .system-update-popup {
            background: #1f2937;
            color: #ffffff;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .cards {
                grid-template-columns: 1fr;
            }

            .form-section.active {
                grid-template-columns: 1fr;
            }

            #toast-container {
                top: 1rem;
                right: 0.5rem;
                max-width: 90%;
            }
        }

        /* --- FIX: UPDATED Media Query to close 1024-1280 gap --- */
        @media (min-width: 769px) and (max-width: 1279px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .main2 {
                grid-template-columns: 1fr; /* This was causing the stacking issue on tablets */
            }
        }

        @media (min-width: 1280px) {
            .main2 {
                grid-template-columns: 2fr 3fr 2fr;
                /* 3-column layout for large desktops */
            }
        }

        /* --- FIX: Base Layout Styles --- */
        main {
            padding: 1.5rem;
            max-width: 100%;
            overflow-x: hidden;
        }

        .content {
            margin-bottom: 1.5rem;
        }

        .heaad {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1rem;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        body.dark .heaad {
            background: #1f2937;
            border-color: #374151;
        }

        .card-header2 {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
        }

        body.dark .card-header2 {
            color: #9ca3af;
        }

        .icon-btn3 {
            font-size: 1rem;
            font-weight: 600;
            color: #0d9488;
            background: #e6fffa;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }

        body.dark .icon-btn3 {
            background: #115e59;
            color: #ccfbf1;
        }
        main{
            display: flex;
            /* flex-direction: column; */
            /* align-items: center; */
            /* justify-content: center; */
        }

        .main2 {
            display: grid;
            grid-template-columns: 1fr; /* Default mobile-first: stacks columns */
            gap: 1.5rem;
        }

        @media (min-width: 769px) {
            .main2 { grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); }
        }
        /* --- FIX: System Update Banners --- */
        #system-update-banner,
        #system-updated-banner {
            padding: 1rem;
            text-align: center;
            font-weight: 500;
            display: none;
            /* Hidden by default, shown by JS */
        }

        #system-update-banner {
            background: #fef9c3;
            color: #854d0e;
            border-bottom: 1px solid #fde047;
        }

        #system-updated-banner {
            background: #cffafe;
            color: #0891b2;
            border-bottom: 1px solid #67e8f9;
        }

        #system-update-banner i,
        #system-updated-banner i {
            margin-right: 0.5rem;
        }

        /* --- FIX: Profile & Notification Popups --- */
        .menu,
        .notification {
            position: fixed;
            top: 5rem;
            right: 1.5rem;
            width: 200px;
            z-index: 100;
            display: none;
            /* Hidden by default */
        }

        .popup {
            background: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            animation: zoomIn95 0.15s ease-out forwards;
        }

        body.dark .popup {
            background: #1f2937;
            border-color: #374151;
        }

        .closebtn {
            position: absolute;
            top: 0.5rem;
            right: 0.75rem;
            font-size: 1.5rem;
            color: #9ca3af;
            cursor: pointer;
            line-height: 1;
        }

        .closebtn:hover {
            color: #1f2937;
        }

        body.dark .closebtn:hover {
            color: #ffffff;
        }

        .popup ul {
            list-style: none;
            padding: 0.5rem;
            margin: 0;
        }

        .popup ul li a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            border-radius: 0.375rem;
            transition: background 0.2s;
        }

        body.dark .popup ul li a {
            color: #d1d5db;
        }

        .popup ul li a:hover {
            background: #f3f4f6;
            color: #1f2937;
        }

        body.dark .popup ul li a:hover {
            background: #374151;
            color: #ffffff;
        }

        .popup ul li a i {
            width: 1.25rem;
            text-align: center;
            color: #6b7280;
        }

        body.dark .popup ul li a i {
            color: #9ca3af;
        }

        .popup ul li.logout a {
            color: #dc2626;
        }

        .popup ul li.logout a i {
            color: #dc2626;
        }

        .popup ul li.logout a:hover {
            background: #fee2e2;
            color: #b91c1c;
        }

        body.dark .popup ul li.logout a:hover {
            background: #450a0a;
            color: #fca5a5;
        }

        .notification .popup ul {
            margin-top: 1.5rem;
            /* Space for close button */
        }

        a.active2 {
            font-weight: 700;
            color: #0d9488;
        }

        /* --- FIX: Chat / Inbox CSS --- */
        .chat-inbox {
            position: fixed;
            bottom: 1rem;
            right: 1.5rem;
            width: 600px;
            height: 70vh;
            max-width: 90vw;
            max-height: 600px;
            z-index: 100;
            display: none;
            /* Hidden by default */
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        body.dark .chat-inbox {
            border-color: #374151;
        }

        .chat-container {
            display: flex;
            width: 100%;
            height: 100%;
            background: #ffffff;
        }

        body.dark .chat-container {
            background: #1f2937;
        }

        .chat-sidebar {
            width: 200px;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        body.dark .chat-sidebar {
            border-right-color: #374151;
        }

        .chat-sidebar-header {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 0.5rem;
        }

        body.dark .chat-sidebar-header {
            border-bottom-color: #374151;
        }

        #chat-user-search {
            width: 100%;
            font-size: 0.875rem;
            padding: 0.375rem 0.5rem;
            border-radius: 0.375rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
        }

        body.dark #chat-user-search {
            background: #374151;
            border-color: #4b5563;
            color: #e5e7eb;
        }

        #chat-refresh-btn {
            flex-shrink: 0;
            width: 2rem;
            height: 2rem;
            border: none;
            background: #f3f4f6;
            color: #6b7280;
            border-radius: 0.375rem;
            cursor: pointer;
        }

        body.dark #chat-refresh-btn {
            background: #374151;
            color: #9ca3af;
        }

        .chat-sidebar-header .closebtn {
            position: static;
            font-size: 1.25rem;
            padding: 0.25rem;
            color: #9ca3af;
        }

        .chat-user-list {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
        }

        .chat-loader {
            padding: 1rem;
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
        }

        body.dark .chat-loader {
            color: #9ca3af;
        }

        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            /* Prevents flex overflow */
        }

        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        body.dark .chat-header {
            border-bottom-color: #374151;
        }

        #chat-header-name {
            font-size: 1.125rem;
            font-weight: 600;
        }

        .encryption-status {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.125rem;
        }

        body.dark .encryption-status {
            color: #9ca3af;
        }

        .encryption-status i {
            color: #16a34a;
            margin-right: 0.25rem;
        }

        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #f9fafb;
        }

        body.dark .chat-messages {
            background: #111827;
        }

        .chat-input-area {
            display: flex;
            padding: 0.75rem;
            border-top: 1px solid #e5e7eb;
            background: #ffffff;
        }

        body.dark .chat-input-area {
            border-top-color: #374151;
            background: #1f2937;
        }

        #chat-message-input {
            flex: 1;
            padding: 0.625rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem 0 0 0.375rem;
            font-size: 0.875rem;
            outline: none;
        }

        body.dark #chat-message-input {
            background: #374151;
            border-color: #4b5563;
            color: #e5e7eb;
        }

        #chat-send-btn {
            padding: 0.625rem 1rem;
            border: none;
            background: #0d9488;
            color: #ffffff;
            cursor: pointer;
            border-radius: 0 0.375rem 0.375rem 0;
            font-size: 1rem;
        }

        #chat-send-btn:hover {
            background: #0b8277;
        }

        .chat-welcome-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }

        body.dark .chat-welcome-main {
            color: #9ca3af;
        }

        .chat-welcome-main i {
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        body.dark .chat-welcome-main i {
            color: #6b7280;
        }

        .chat-welcome-main h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        body.dark .chat-welcome-main h3 {
            color: #e5e7eb;
        }

        .chat-welcome-main p {
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        /* JS will toggle this class */
        .chat-inbox.chat-active .chat-main {
            display: flex;
        }

        .chat-inbox.chat-active .chat-welcome-main {
            display: none;
        }

        .chat-inbox:not(.chat-active) .chat-main {
            display: none;
        }

        .chat-inbox:not(.chat-active) .chat-welcome-main {
            display: flex;
        }

        @media (max-width: 768px) {
            .chat-inbox {
                width: 95vw;
                height: 85vh;
                max-height: none;
                bottom: 0.5rem;
                right: 0.5rem;
            }

            .chat-sidebar {
                width: 100px;
            }

            .chat-sidebar-header {
                flex-direction: column;
            }

            .chat-sidebar-header .closebtn {
                display: block;
                position: absolute;
                top: 0.5rem;
                right: 0.5rem;
                z-index: 101;
            }

            /* Simple mobile: hide sidebar when a chat is active */
            .chat-inbox.chat-active .chat-sidebar {
                display: none;
            }

            .chat-inbox.chat-active .chat-main {
                width: 100%;
            }

            /* And hide main when no chat is active */
            .chat-inbox:not(.chat-active) .chat-sidebar {
                width: 100%;
                display: flex;
            }

            .chat-inbox:not(.chat-active) .chat-main {
                display: none;
            }

            .chat-inbox:not(.chat-active) .chat-welcome-main {
                display: none;
            }
        }

        /* --- FIX: End of Added Styles --- */
    </style>
</head>

<body>
    <div class="mobile-blocker">
        <div class="mobile-blocker-popup">
            <i class="fa-solid fa-mobile-screen-button popup-icon"></i>
            <h2>Mobile View Not Supported</h2>
            <p>The admin panel is designed for desktop use. For the best experience on your mobile device, please download our dedicated application.</p>
            <a href="/download-app/index.html" class="mobile-download-btn">
                <i class="fa-solid fa-download"></i> Download App
            </a>
        </div>
    </div>

    <header>
        <div class="logo-container">
            <div class="logo">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/proadmin/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo">
                <?php else: ?>
                    <div class="logo-placeholder">Primary Logo N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="hamburger-menu">
            <button>
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <nav>
            <div class="nav-links">
                <a href="dashboard.php" class="active">
                    <i class="fa-solid fa-tachometer-alt"></i><span>Dashboard</span>
                </a>
                <a href="inquiry.php">
                    <i class="fa-solid fa-magnifying-glass"></i><span>Inquiry</span>
                </a>
                <a href="registration.php">
                    <i class="fa-solid fa-user-plus"></i><span>Registration</span>
                </a>
                <a href="appointments.php">
                    <i class="fa-solid fa-calendar-check"></i><span>Appointments</span>
                </a>
                <a href="patients.php">
                    <i class="fa-solid fa-users"></i><span>Patients</span>
                </a>
                <a href="billing.php">
                    <i class="fa-solid fa-file-invoice-dollar"></i><span>Billing</span>
                </a>
                <a href="attendance.php">
                    <i class="fa-solid fa-user-check"></i><span>Attendance</span>
                </a>
                <a href="tests.php">
                    <i class="fa-solid fa-vial"></i><span>Tests</span>
                </a>
                <a href="reports.php">
                    <i class="fa-solid fa-chart-line"></i><span>Reports</span>
                </a>
                <a href="expenses.php">
                    <i class="fa-solid fa-money-bill-wave"></i><span>Expenses</span>
                </a>
            </div>
        </nav>

        <div class="nav-actions">
            <button class="icon-btn" id="theme-toggle">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </button>
            <button class="icon-btn" title="Inbox" onclick="openInbox()">
                <i class="fa-solid fa-inbox"></i>
            </button>
            <button class="icon-btn" title="Notifications" onclick="openNotif()">
                <i class="fa-solid fa-bell"></i>
            </button>
            <button class="profile" onclick="openForm()">R</button>
            <button id="menuBtn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </header>

    <div id="drawerNav">
        <div class="flex">
            <h2><i class="fa-solid fa-bars"></i> Navigation</h2>
            <button id="closeBtn">&times;</button>
        </div>
        <nav></nav>
    </div>

    <div id="drawer-overlay"></div>

    <div class="menu" id="myMenu">
        <div class="popup">
            <span class="closebtn" onclick="closeForm()">&times;</span>
            <ul>
                <li><a href="profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                <li class="logout"><a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="notification" id="myNotif">
        <div class="popup">
            <span class="closebtn" onclick="closeNotif()">&times;</span>
            <ul>
                <li><a href="changelog.html" class="active2">View Changes (1)</a></li>
            </ul>
        </div>
    </div>

    <div class="chat-inbox" id="myInbox">
        <div class="chat-container">
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <input type="text" id="chat-user-search" placeholder="Search users...">
                    <button id="chat-refresh-btn" title="Refresh Chat"><i class="fa-solid fa-sync"></i></button>
                    <span class="closebtn" onclick="closeInbox()">&times;</span>
                </div>
                <div class="chat-user-list" id="chat-user-list">
                    <div class="chat-loader">Loading users...</div>
                </div>
            </div>
            <div class="chat-main">
                <div class="chat-header">
                    <h2 id="chat-header-name"></h2>
                    <div class="encryption-status"><i class="fa-solid fa-lock"></i> Messages are end-to-end encrypted</div>
                </div>
                <div class="chat-messages" id="chat-messages"></div>
                <div class="chat-input-area">
                    <input type="text" id="chat-message-input" placeholder="Type your message...">
                    <button id="chat-send-btn"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
            <div class="chat-welcome-main">
                <i class="fa-solid fa-comments"></i>
                <h3>Select a chat</h3>
                <p>Choose a user from the list to start messaging.</p>
                <div class="encryption-status"><i class="fa-solid fa-lock"></i> Messages are end-to-end encrypted</div>
            </div>
        </div>
    </div>


    <div id="system-update-banner">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span id="update-banner-message"><strong>System Update Scheduled:</strong> The system will be undergoing maintenance today from 10:00 PM to 11:00 PM.</span>
    </div>
    <div id="system-updated-banner">
        <i class="fa-solid fa-circle-check"></i>
        <span id="updated-banner-message"><strong>System Update Complete!</strong> The system has been successfully updated.</span>
    </div>

    <div class="content">
        <div class="heaad">
            <div class="card-header2"><span id="datetime"><?php echo date('Y-m-d h:i:s A'); ?></span></div>
            <div class="icon-btn3"><?php echo $branchName; ?> Branch</div>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-header-flex">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-calendar-check"></i> Registration & Appointments</h2>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= $todayInquiries ?></div>
                    <div class="card-sub">Total Registrations: <?= $totalInquiries ?></div>
                </div>
                <div class="card-body2">
                    <div class="card-title"><?= $todayAppointmentsConducted; ?></div>
                    <div class="card-sub">Conducted Today</div>
                    <div class="card-title"><?= $todayAppointmentsInQueue; ?></div>
                    <div class="card-sub">Appointments in Queue</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header-flex">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-envelope"></i> Inquiry</h2>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= $todayquickInquiry; ?></div>
                    <div class="card-sub">Today's Quick Inquiries</div>
                </div>
                <div class="card-body2">
                    <div class="card-title"><?= $todaytestInquiry; ?></div>
                    <div class="card-sub">Today's Test Inquiries</div>
                    <div class="card-title"><?= $totalquickInquiry + $totaltestInquiry; ?></div>
                    <div class="card-sub">Total Inquiries</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header-flex">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-user-group"></i> Patients</h2>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= $todayPatients; ?></div>
                    <div class="card-sub">Enrolled Today</div>
                </div>
                <div class="card-body2">
                    <div class="card-title"><?= $totalPatients; ?></div>
                    <div class="card-sub">Total Enrolled Patients</div>
                    <div class="card-title"><?= $ongoingPatients; ?></div>
                    <div class="card-sub">Ongoing Treatments</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header-flex">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-vial"></i> Tests</h2>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= $todayPendingTests; ?></div>
                    <div class="card-sub">Scheduled Today</div>
                </div>
                <div class="card-body2">
                    <div class="card-title"><?= $totalPendingTests; ?></div>
                    <div class="card-sub">Tests in Queue</div>
                    <div class="card-title"><?= $totalCompletedTests; ?></div>
                    <div class="card-sub">Total Tests Conducted</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header-flex">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-file-invoice-dollar"></i> Payments</h2>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-title">₹<?= number_format($todayPaid, 2) ?></div>
                    <div class="card-sub">Payment Received Today</div>
                    <div class="card-title">₹<?= number_format($totalPaid, 2) ?></div>
                    <div class="card-sub">Total Payment Received</div>
                </div>
                <div class="card-body2">
                    <div class="card-title">₹<?= number_format($todayPatientTreatmentPaid, 2) ?></div>
                    <div class="card-sub">From ongoing patient treatments.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header-flex">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-money-bill-transfer"></i> Today's Collections</h2>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-title">₹<?= number_format($todayRegistrationPaid, 2) ?></div>
                    <div class="card-sub">From new patient registrations.</div>
                </div>
                <div class="card-body2">
                    <div class="card-title">₹<?= number_format($todayTestPaid, 2) ?></div>
                    <div class="card-sub">From diagnostic tests.</div>
                </div>
            </div>
        </div>
    </div>
    <main class="main">
        <div class="main2">
            <section class="schedule">
                <div class="schedule-header">
                    <h2>Schedule</h2>
                    <span class="arrow" onclick="window.location.href='schedule.php';">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </span>
                </div>
                <div class="text">
                    <h2>Today's Schedule (<?php echo $todayDisplay; ?>)</h2>
                </div>
                <div class="timeline">
                    <?php if (isset($groupedSchedules[$today]) && count($groupedSchedules[$today]) > 0): ?>
                        <?php foreach ($groupedSchedules[$today] as $slot): ?>
                            <?php
                            $status = strtolower($slot['status']);
                            $dotColor = $status === 'pending' ? 'bg-orange-500' : ($status === 'consulted' || $status === 'completed' ? 'bg-green-500' : 'bg-gray-400');
                            $timeColor = $status === 'pending' ? 'color: #f97316;' : ($status === 'consulted' || $status === 'completed' ? 'color: #16a34a;' : 'color: #6b7280;');
                            ?>
                            <div class="time-slot">
                                <div class="time" style="<?= $timeColor ?>">
                                    <?= date("h:i a", strtotime($slot['appointment_time'])) ?>
                                </div>
                                <div class="circle <?= $dotColor ?>"></div>
                                <div class="event <?= $status ?>">
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
                        <h2>New Registration</h2>
                        <div class="slider-toggle" id="sliderToggle">
                            <div class="slider-indicator"></div>
                            <button class="active" data-index="0">Registration</button>
                            <button data-index="1">Test</button>
                        </div>
                    </div>

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
                            <label>Chief Complaint *</label>
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
                            <input type="text" name="remarks">
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
                            <select name="appointment_time" id="appointment_time" required></select>
                        </div>
                        <div class="submit-btn2">
                            <button type="submit">Submit</button>
                        </div>
                    </form>

                    <form id="testForm" class="form-section" method="POST" action="../api/test_submission.php">
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
                            <input type="text" name="phone_number" placeholder="+911234567890" maxlength="10" required>
                        </div>
                        <div>
                            <label>Enter Alternate Phone No</label>
                            <input type="text" name="alternate_phone_no" placeholder="+911234567890" maxlength="10">
                        </div>
                        <div>
                            <label>Referred By *</label>
                            <input list="referrers-list" id="test_form_referred_by" name="referred_by" placeholder="Type or select a doctor" required>
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
                                <option value="both">Both Limbs</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div>
                            <label>Receipt No</label>
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
                            <input type="number" name="advance_amount" step="0.01" value="0" placeholder="Enter Advance Amount">
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
                        <div class="submit-btn2">
                            <button type="submit">Submit Test</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="form">
                <div class="form-box">
                    <div class="form-tabs">
                        <button id="inquiryTabUnique" class="tab-btn active" data-form="uniqueInquiryForm">Inquiry</button>
                        <button id="testTabUnique" class="tab-btn" data-form="uniqueTestForm">Test Inquiry</button>
                    </div>

                    <form id="uniqueInquiryForm" action="../api/inquiry_submission.php" method="POST" class="form-content active">
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
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="inquiry_gender">Gender *</label>
                                <select id="inquiry_gender" name="gender" required>
                                    <option value="" disabled selected>Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label for="inquiry_type">Inquiry Service *</label>
                                <select name="inquiry_type" id="inquiry_type" required>
                                    <option value="" disabled selected>select inquiry service</option>
                                    <option value="physio">Physio</option>
                                    <option value="speech_therapy">Speech Therapy</option>
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
                            <div>
                                <label for="inquiry_communication_type">Inquiry Communication Type *</label>
                                <select id="inquiry_communication_type" name="communication_type" required>
                                    <option value="" disabled selected>Select</option>
                                    <option value="by_visit">By Visit</option>
                                    <option value="phone">Phone</option>
                                    <option valuea="web">Web</option>
                                    <option value="email">Email</option>
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
                                <label for="inquiry_remarks">Remarks</label>
                                <textarea id="inquiry_remarks" name="remarks" placeholder="Remarks..."></textarea>
                            </div>
                            <div>
                                <label for="inquiry_expected_date">Plan to visit Date *</label>
                                <input type="date" id="inquiry_expected_date" name="expected_date" required>
                            </div>
                        </div>
                        <button type="submit" class="submit-btn">Submit</button>
                    </form>

                    <form id="uniqueTestForm" action="../api/inquiry_test_submission.php" method="POST" class="form-content">
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
        </div>
    </main>

    <div id="toast-container"></div>
    <div id="system-update-overlay">
        <div class="system-update-popup">
            <i class="fa-solid fa-gears fa-spin"></i>
            <h2>System Update in Progress</h2>
            <p>The system is currently being updated. Please wait a few minutes and try again.</p>
        </div>
    </div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        const currentUserId = <?= (int)($_SESSION['uid'] ?? 0) ?>;
    </script>

    <script src="../js/chat.js"></script>

    <script>
        // --- FIX: Added missing popup/drawer functions ---
        function openForm() {
            document.getElementById("myMenu").style.display = "block";
        }

        function closeForm() {
            document.getElementById("myMenu").style.display = "none";
        }

        function openNotif() {
            document.getElementById("myNotif").style.display = "block";
        }

        function closeNotif() {
            document.getElementById("myNotif").style.display = "none";
        }

        function openInbox() {
            document.getElementById("myInbox").style.display = "block";
            fetchChatUsers(); // Fetch users when inbox is opened
        }

        function closeInbox() {
            document.getElementById("myInbox").style.display = "none";
        }
        // --- End of FIX ---


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
            window.toggleSystemUpdate = toggleSystemUpdate();

            // ==========================================================
            // NEW: System Update Banner Control
            // ==========================================================
            const preUpdateBanner = document.getElementById('system-update-banner');
            const postUpdateBanner = document.getElementById('system-updated-banner');
            const preUpdateMessageSpan = document.getElementById('update-banner-message');
            const postUpdateMessageSpan = document.getElementById('updated-banner-message');

            const POST_UPDATE_BANNER_KEY = 'postUpdateBannerShown_v2.2.6'; // Change this key for new versions

            /**
             * Toggles system update banners.
             * @param {boolean} show - Whether to show or hide a banner.
             * @param {number} type - 1 for pre-update (yellow), 2 for post-update (blue).
             * @param {string|null} message - A custom message to display.
             */
            function toggleUpdateBanner(show, type = 1, message = null) {
                // Always hide both first to reset state
                if (preUpdateBanner) preUpdateBanner.style.display = 'none';
                if (postUpdateBanner) postUpdateBanner.style.display = 'none';

                if (!show) {
                    // If hiding, we're done.
                    return;
                }

                if (type === 1 && preUpdateBanner) {
                    // Show the persistent pre-update banner
                    preUpdateBanner.style.display = 'block';
                    if (message && preUpdateMessageSpan) {
                        preUpdateMessageSpan.innerHTML = message;
                    }
                } else if (type === 2 && postUpdateBanner) {
                    // Show the temporary post-update banner
                    if (localStorage.getItem(POST_UPDATE_BANNER_KEY)) {
                        return; // Don't show if it has already been seen this session
                    }
                    postUpdateBanner.style.display = 'block';
                    if (message && postUpdateMessageSpan) {
                        postUpdateMessageSpan.innerHTML = message;
                    }
                    localStorage.setItem(POST_UPDATE_BANNER_KEY, 'true');
                    setTimeout(() => {
                        postUpdateBanner.style.display = 'none';
                    }, 30000); // Hide after 10 seconds
                }
            }

            // Example Usage (from browser console):
            // To show pre-update banner: toggleUpdateBanner(true, 1, "System will be down at 10 PM.")
            // To show post-update banner: toggleUpdateBanner(true, 2)
            // To hide: toggleUpdateBanner(false)
            window.toggleUpdateBanner = toggleUpdateBanner(true, 2, "System is going to be Updated. Please STOP any work for 5 Minutes");

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


            // --- FIX: Added Drawer Navigation Logic ---
            const menuBtn = document.getElementById('menuBtn');
            const closeBtn = document.getElementById('closeBtn');
            const drawerNav = document.getElementById('drawerNav');
            const drawerOverlay = document.getElementById('drawer-overlay');

            if (menuBtn && closeBtn && drawerNav && drawerOverlay) {
                menuBtn.addEventListener('click', () => {
                    drawerNav.classList.add('open');
                    drawerOverlay.classList.add('show');
                });
                closeBtn.addEventListener('click', () => {
                    drawerNav.classList.remove('open');
                    drawerOverlay.classList.remove('show');
                });
                drawerOverlay.addEventListener('click', () => {
                    drawerNav.classList.remove('open');
                    drawerOverlay.classList.remove('show');
                });
            }

            // --- FIX: Added Registration/Test Toggle Logic ---
            const sliderToggle = document.getElementById("sliderToggle");
            const formSections = document.querySelectorAll(".form-section");
            const sliderIndicator = document.querySelector(".slider-indicator");

            if (sliderToggle && formSections.length > 0 && sliderIndicator) {
                sliderToggle.addEventListener("click", (e) => {
                    if (e.target.tagName !== "BUTTON") return;

                    const
                        targetIndex = e.target.dataset.index;
                    if (!targetIndex) return;

                    // Move indicator
                    sliderIndicator.style.transform = `translateX(${targetIndex * 100}%)`;

                    // Toggle active button
                    sliderToggle.querySelector("button.active").classList.remove("active");
                    e.target.classList.add("active");

                    // Toggle active form
                    formSections.forEach((form, index) => {
                        if (index == targetIndex) {
                            form.classList.add("active");
                        } else {
                            form.classList.remove("active");
                        }
                    });
                });
            }

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