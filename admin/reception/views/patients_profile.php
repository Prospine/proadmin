<?php

declare(strict_types=1);
session_start();

// Error Reporting (Dev Only)
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
    exit(errorPage("Access Denied", "Branch information is missing from your session."));
}

// -------------------------
// Get patient_id from URL
// -------------------------
$patientId = $_GET['patient_id'] ?? null;
if (!$patientId || !is_numeric($patientId)) {
    echo errorPage("Error", "No patient ID provided or the ID is invalid.");
    exit();
}
$patientId = (int)$patientId;

try {

    //branch name
    $stmt = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmt->execute(['branch_id' => $branchId]);
    $branchName = $stmt->fetch()['branch_name'] ?? '';

    // -------------------------
    // Fetch Patient & Registration Data
    // -------------------------
    $stmt = $pdo->prepare("
        SELECT 
            p.*, r.*, pm.patient_uid,
            p.status AS patient_status
        FROM patients p
        LEFT JOIN registration r ON p.registration_id = r.registration_id
        LEFT JOIN patient_master pm ON p.master_patient_id = pm.master_patient_id
        WHERE p.patient_id = :id AND p.branch_id = :branch
    ");
    $stmt->execute(['id' => $patientId, 'branch' => $branchId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        echo errorPage("Not Found", "Patient with ID #{$patientId} not found.");
        exit();
    }

    // --- Calculate Financials ---
    $totalBilled = (float)($patient['total_amount'] ?? 0);
    $dueAmount = (float)($patient['due_amount'] ?? 0);
    $paidAmount = $totalBilled - $dueAmount;
    $paymentPercentage = ($totalBilled > 0) ? round(($paidAmount / $totalBilled) * 100) : 0;

    // --- MODIFIED: Fetch and Process Attendance Data for Multi-Month Calendar ---
    // 1. Fetch all attendance records for the patient
    $attendanceStmt = $pdo->prepare("
        SELECT attendance_date, remarks 
        FROM attendance 
        WHERE patient_id = :patient_id
        ORDER BY attendance_date ASC
    ");
    $attendanceStmt->execute(['patient_id' => $patientId]);
    $attendanceRecords = $attendanceStmt->fetchAll(PDO::FETCH_KEY_PAIR); // Creates a ['YYYY-MM-DD' => 'remarks'] map

    // 2. Determine the date range for the calendars
    $calendarData = [];
    if (!empty($attendanceRecords)) {
        $firstDateStr = array_key_first($attendanceRecords);
        $lastDateStr = array_key_last($attendanceRecords);

        $startDate = new DateTime($firstDateStr);
        $endDate = new DateTime($lastDateStr);

        // Limit to the last 3 months of activity as requested
        $threeMonthsAgo = (clone $endDate)->modify('-2 months')->modify('first day of this month');
        if ($startDate < $threeMonthsAgo) {
            $startDate = $threeMonthsAgo;
        }

        // The DatePeriod is exclusive of the end date. To ensure the last month is included,
        // we need to extend the end date to the beginning of the next month.
        $periodEndDate = (clone $endDate)->modify('first day of next month');
        $period = new DatePeriod($startDate, new DateInterval('P1M'), $periodEndDate);

        foreach ($period as $dt) $calendarData[] = ['year' => (int)$dt->format('Y'), 'month' => (int)$dt->format('m')];
    }
} catch (PDOException $e) {
    error_log("Error fetching patient profile: " . $e->getMessage());
    echo errorPage("Database Error", "Unable to fetch patient profile.");
    exit();
}

function errorPage(string $title, string $message): string
{
    return "<div style='text-align: center; padding: 40px; font-family: sans-serif;'><h1 style='font-size: 24px; color: #dc2626;'>{$title}</h1><p style='color: #4b5563;'>{$message}</p><a href='javascript:history.back()' style='display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #3b82f6; color: white; border-radius: 8px; text-decoration: none;'>Go Back</a></div>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Patient Profile - <?= htmlspecialchars($patient['patient_name']) ?></title>
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <style type="text/tailwindcss">
        :root {
            --primary-color: #4F46E5;
            --background-light: #F9FAFB;
            --card-light: #FFFFFF;
            --text-light-primary: #1F2937;
            --text-light-secondary: #6B7280;
            --border-light: #E5E7EB;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        /* --- MODIFIED: Attendance Calendar Styles for Horizontal Layout --- */
        .calendar-container {
            display: flex;
            gap: 1.5rem; /* 24px */
            overflow-x: auto;
            padding-bottom: 1rem; /* For scrollbar visibility */
        }
        .attendance-calendar {
            display: grid;
            grid-template-columns: repeat(7, 2rem); /* Fixed size for each day */
            gap: 4px;
        }
        .calendar-day {
            @apply w-full aspect-square rounded-md border border-gray-200 bg-gray-100;
            position: relative;
        }
        .calendar-day.present {
            @apply bg-green-500 border-green-600;
        }
        .calendar-day.future {
            @apply bg-white border-gray-200;
        }
        .calendar-day .tooltip {
            @apply invisible absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 transform whitespace-nowrap rounded-md bg-gray-800 px-3 py-1.5 text-xs font-semibold text-white opacity-0 transition-opacity;
        }
        .calendar-day:hover .tooltip {
            @apply visible opacity-100;
        }

        .p-4{
            margin: 10px;
            border-radius: 20px;
            background-color: #ffffffa5;
        }

        .top-header{
            padding: 0;
        }

        .bg-card-light{
            background-color: var(--card-light);
        }
        .bg-background-light{
            background-color: var(--background-light);
        }
        .text-text-light-primary{
            color: var(--text-light-primary);
        }

        .tab-btn{
            padding: 4px;
            max-width: 200px;
            margin-top: -10px;
            margin-right: 10px;
            margin-bottom: 8px;
        }

        /* write body.dark mode for the page */        
        body.dark {
            --background-light: #1a1a1a;
            --card-light: #2d2d2d;
            --text-light-primary: #e0e0e0;
            --text-light-secondary: #a0a0a0;
            --border-light: #444444;
        }

        body.dark .p-4 {
            background-color: #2d2d2d;
        }

        body.dark .bg-card-light {
            background-color: var(--card-light);
            border-color: var(--border-light);
        }

        body.dark .bg-background-light {
            background-color: var(--background-light);
        }

        body.dark .text-text-light-primary {
            color: var(--text-light-primary);
        }

        body.dark .text-text-light-secondary {
            color: var(--text-light-secondary);
        }

        body.dark .border-border-light {
            border-color: var(--border-light);
        }

        body.dark .hover\:bg-gray-50:hover {
            background-color: #3a3a3a;
        }

        body.dark .bg-indigo-100 {
            background-color: #4f46e533; /* A darker shade of indigo */
        }

        body.dark .bg-green-100 {
            background-color: #10b98133; /* Darker green */
        }

        body.dark .text-green-800 {
            color: #34d399; /* Lighter green */
        }

        body.dark .bg-blue-100 {
            background-color: #3b82f633; /* Darker blue */
        }

        body.dark .text-blue-800 {
            color: #60a5fa; /* Lighter blue */
        }

        body.dark .bg-gray-100 {
            background-color: #4a4a4a
        }
    </style>
</head>

<body class="bg-background-light text-text-light-primary">
    <header>
        <div class="logo-container"> <img src="../../assets/images/image.png" alt="Pro Physio Logo" class="logo" />
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a href="registration.php">Registration</a>
                <a class="active" href="patients.php">Patients</a>
                <a href="appointments.php">Appointments</a>
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
        </div>
    </header>
    <div class="p-4 md:p-8">
        <header class="top-header flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-text-light-primary">Patient Profile for <?= htmlspecialchars($patient['patient_name']) ?>.</h1>
            </div>
        </header>
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-1 space-y-8">
                <div class="bg-card-light p-6 rounded-xl shadow-sm border border-border-light">
                    <div class="flex flex-col items-center">
                        <div class="w-24 h-24 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                            <span class="text-5xl font-bold text-primary-color" style="color: var(--primary-color);"><?= htmlspecialchars(substr($patient['patient_name'], 0, 1)) ?></span>
                        </div>
                        <h2 class="text-2xl font-bold text-text-light-primary"><?= htmlspecialchars($patient['patient_name']) ?></h2>
                        <p class="text-sm text-text-light-secondary">Patient ID: <?= htmlspecialchars((string)($patient['patient_id'] ?? 'N/A')) ?></p>
                        <span class="mt-2 px-3 py-1 text-xs font-semibold rounded-full 
                            <?php $status = $patient['patient_status'] ?? 'inactive';
                            if ($status == 'active') echo 'bg-green-100 text-green-800';
                            elseif ($status == 'completed') echo 'bg-blue-100 text-blue-800';
                            else echo 'bg-gray-100 text-gray-800'; ?>">
                            <?= strtoupper(htmlspecialchars($status)) ?>
                        </span>
                    </div>
                    <div class="mt-6 space-y-4 text-sm">
                        <div class="flex items-center"><span class="material-symbols-outlined text-text-light-secondary mr-3">phone</span><span><?= htmlspecialchars($patient['phone_number'] ?? 'N/A') ?></span></div>
                        <div class="flex items-center"><span class="material-symbols-outlined text-text-light-secondary mr-3">email</span><span><?= htmlspecialchars($patient['email'] ?? 'N/A') ?></span></div>
                        <div class="flex items-center"><span class="material-symbols-outlined text-text-light-secondary mr-3">home</span><span><?= htmlspecialchars($patient['address'] ?? 'N/A') ?></span></div>
                    </div>
                </div>
                <div class="bg-card-light p-6 rounded-xl shadow-sm border border-border-light">
                    <h3 class="text-xl font-bold text-text-light-primary mb-4">Financial Summary</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <p class="text-text-light-secondary">Total Billed:</p>
                            <p class="font-medium text-text-light-primary">₹<?= number_format($totalBilled, 2) ?></p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-text-light-secondary">Paid:</p>
                            <p class="font-medium text-green-600">₹<?= number_format($paidAmount, 2) ?></p>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-text-light-secondary">Due Amount:</p>
                            <p class="font-medium text-red-600">₹<?= number_format($dueAmount, 2) ?></p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-border-light">
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full" style="width: <?= $paymentPercentage ?>%; background-color: var(--primary-color);"></div>
                            </div>
                            <p class="text-xs text-right mt-1 text-text-light-secondary"><?= $paymentPercentage ?>% Paid</p>
                        </div>
                    </div>
                </div>
                <div class="bg-card-light p-6 rounded-xl shadow-sm border border-border-light">
                    <h3 class="text-xl font-bold text-text-light-primary mb-4">Assigned Doctor</h3>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center"><span class="material-symbols-outlined text-text-light-secondary">
                                <?= ($patient['assigned_doctor'] == 'Not Assigned') ? 'person_off' : 'person' ?>
                            </span></div>
                        <div>
                            <p class="font-semibold text-text-light-primary"><?= htmlspecialchars($patient['assigned_doctor']) ?></p>
                            <p class="text-sm text-text-light-secondary"><?= ($patient['assigned_doctor'] == 'Not Assigned') ? 'Assign a doctor to this patient' : 'Primary Physician' ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="xl:col-span-2 space-y-8">
                <div class="bg-card-light rounded-xl shadow-sm border border-border-light">
                    <div class="p-6">
                        <div id="profile-tabs" class="flex border-b border-border-light -mb-px">
                            <button data-tab="demographics" class="tab-btn active px-4 py-3 text-sm font-semibold border-b-2 text-primary-color border-primary-color">Demographics</button>
                            <button data-tab="consultations" class="tab-btn px-4 py-3 text-sm font-semibold text-text-light-secondary border-b-2 border-transparent hover:text-primary-color hover:border-gray-300 transition">Consultations</button>
                            <button data-tab="treatment" class="tab-btn px-4 py-3 text-sm font-semibold text-text-light-secondary border-b-2 border-transparent hover:text-primary-color hover:border-gray-300 transition">Treatment Plan</button>
                        </div>
                        <div id="demographics-content" class="tab-content mt-6">
                            <h3 class="text-lg font-semibold text-text-light-primary mb-4">Patient Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <p class="font-medium text-text-light-secondary">Age</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars((string)$patient['age']) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Gender</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars($patient['gender']) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Occupation</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars($patient['occupation'] ?? 'N/A') ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Referral Source</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars($patient['referralSource'] ?? 'N/A') ?></p>
                                </div>
                                <div class="md:col-span-2">
                                    <p class="font-medium text-text-light-secondary">Inquiry Date</p>
                                    <p class="text-text-light-primary"><?= date('M d, Y', strtotime($patient['created_at'])) ?></p>
                                </div>
                            </div>
                            <hr class="my-6 border-border-light" />
                            <h3 class="text-lg font-semibold text-text-light-primary mb-4">Initial Complaint</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <p class="font-medium text-text-light-secondary">Chief Complaint</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars(str_replace('_', ' ', $patient['chief_complain'])) ?></p>
                                </div>
                                <div class="md:col-span-2">
                                    <p class="font-medium text-text-light-secondary">Remarks</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars($patient['remarks'] ?? 'N/A') ?></p>
                                </div>
                            </div>
                        </div>
                        <div id="consultations-content" class="tab-content mt-6 hidden p-6">
                            <h3 class="text-xl font-bold text-text-light-primary mb-4">Latest Consultation</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <p class="font-medium text-text-light-secondary">Consultation ID</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars((string)$patient['registration_id']) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Type</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars($patient['consultation_type']) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Appointment Date</p>
                                    <p class="text-text-light-primary"><?= date('M d, Y - h:i A', strtotime($patient['appointment_date'] . ' ' . $patient['appointment_time'])) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Fee</p>
                                    <p class="text-text-light-primary">₹<?= number_format((float)$patient['consultation_amount'], 2) ?> (Paid via <?= htmlspecialchars($patient['payment_method']) ?>)</p>
                                </div>
                                <div class="md:col-span-2">
                                    <p class="font-medium text-text-light-secondary">Doctor's Notes</p>
                                    <p class="text-text-light-primary italic"><?= htmlspecialchars($patient['doctor_notes'] ?? 'N/A') ?></p>
                                </div>
                                <div class="md:col-span-2">
                                    <p class="font-medium text-text-light-secondary">Prescription</p>
                                    <p class="text-text-light-primary italic"><?= htmlspecialchars($patient['prescription'] ?? 'N/A') ?></p>
                                </div>
                            </div>
                        </div>
                        <div id="treatment-content" class="tab-content mt-6 hidden p-6">
                            <h3 class="text-xl font-bold text-text-light-primary mb-4">Current Treatment Plan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <div>
                                    <p class="font-medium text-text-light-secondary">Plan Type</p>
                                    <p class="text-text-light-primary"><?= ucwords(htmlspecialchars($patient['treatment_type'])) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Total Days</p>
                                    <p class="text-text-light-primary"><?= htmlspecialchars((string)$patient['treatment_days']) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Start Date</p>
                                    <p class="text-text-light-primary"><?= date('M d, Y', strtotime($patient['start_date'])) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">End Date</p>
                                    <p class="text-text-light-primary"><?= date('M d, Y', strtotime($patient['end_date'])) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Cost per Day</p>
                                    <p class="text-text-light-primary">₹<?= number_format((float)$patient['treatment_cost_per_day'], 2) ?></p>
                                </div>
                                <div>
                                    <p class="font-medium text-text-light-secondary">Total Cost</p>
                                    <p class="font-bold text-text-light-primary">₹<?= number_format((float)$patient['total_amount'], 2) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NEW: Attendance Calendar Card -->
                <div class="bg-card-light p-6 rounded-xl shadow-sm border border-border-light">
                    <h3 class="text-xl font-bold text-text-light-primary mb-4">Attendance Map</h3>
                    <div class="calendar-container">
                        <?php if (empty($calendarData)): ?>
                            <p class="text-text-light-secondary">No attendance records found for this patient.</p>
                        <?php else: ?>
                            <?php foreach ($calendarData as $cal):
                                $year = $cal['year'];
                                $month = $cal['month'];
                                $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
                                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                                $firstDayOfWeek = date('w', strtotime("$year-$month-01"));
                            ?>
                                <div class="flex-shrink-0">
                                    <h4 class="text-sm font-semibold text-center mb-2"><?= $monthName ?></h4>
                                    <div class="attendance-calendar">
                                        <?php for ($i = 0; $i < $firstDayOfWeek; $i++) echo '<div class="calendar-day empty"></div>'; ?>
                                        <?php for ($day = 1; $day <= $daysInMonth; $day++):
                                            $currentDateStr = sprintf('%d-%02d-%02d', $year, $month, $day);
                                            $isPresent = isset($attendanceRecords[$currentDateStr]);
                                            $isFuture = $currentDateStr > date('Y-m-d');
                                            $class = 'calendar-day';
                                            $tooltipText = date('d M, Y', strtotime($currentDateStr));

                                            if ($isFuture) {
                                                $class .= ' future';
                                                $tooltipText .= ' (Future)';
                                            } elseif ($isPresent) {
                                                $class .= ' present';
                                                $tooltipText .= ' - Present: ' . htmlspecialchars($attendanceRecords[$currentDateStr]);
                                            } else {
                                                $tooltipText .= ' - Absent';
                                            }
                                        ?>
                                            <div class='<?= $class ?>'>
                                                <div class='tooltip'><?= $tooltipText ?></div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/theme.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabsContainer = document.getElementById('profile-tabs');
            const tabButtons = tabsContainer.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabsContainer.addEventListener('click', function(event) {
                const clickedButton = event.target.closest('.tab-btn');
                if (!clickedButton) return;

                const tabId = clickedButton.dataset.tab;

                // Update button styles
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'text-primary-color', 'border-primary-color');
                    btn.classList.add('text-text-light-secondary', 'border-transparent');
                });
                clickedButton.classList.add('active', 'text-primary-color', 'border-primary-color');
                clickedButton.classList.remove('text-text-light-secondary', 'border-transparent');


                // Show/hide content
                tabContents.forEach(content => {
                    if (content.id === `${tabId}-content`) {
                        content.classList.remove('hidden');
                    } else {
                        content.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</body>

</html>