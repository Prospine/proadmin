<?php

declare(strict_types=1);
session_start();

// --- Basic Setup & Security ---
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!isset($_SESSION['uid'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/db.php';

$branchId = $_SESSION['branch_id'] ?? null;
if (!$branchId) {
    die('Error: Branch information is missing from your session.');
}

// --- Date & Navigation Logic ---
$week_start_str = $_GET['week_start'] ?? 'now';
try {
    $startOfWeek = new DateTime($week_start_str, new DateTimeZone('Asia/Kolkata'));
} catch (Exception $e) {
    $startOfWeek = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
}

// --- THE FIX ---
// Instead of 'last sunday', which can be ambiguous, we check the day of the week.
// If the day is not Sunday (0), we modify it to the 'last Sunday'.
// If it IS Sunday, we do nothing, because we're already at the start of the week.
if ($startOfWeek->format('w') != 0) {
    $startOfWeek->modify('last sunday');
}
// --- END OF FIX ---


$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+6 days');

$prevWeek = clone $startOfWeek;
$prevWeek->modify('-1 week');
$nextWeek = clone $startOfWeek;
$nextWeek->modify('+1 week');

$branchName = '';
$appointmentsByDateAndTime = [];

try {
    // --- Database Fetching (No changes needed here) ---
    $stmt = $pdo->prepare("
        SELECT
            r.registration_id,
            r.patient_name,
            r.appointment_date,
            r.appointment_time,
            r.status,
            pm.patient_uid
        FROM registration r
        LEFT JOIN patient_master pm ON r.master_patient_id = pm.master_patient_id
        WHERE r.branch_id = :branch_id
          AND r.appointment_date BETWEEN :start_date AND :end_date
          AND r.appointment_time IS NOT NULL
    ");
    $stmt->execute([
        ':branch_id' => $branchId,
        ':start_date' => $startOfWeek->format('Y-m-d'),
        ':end_date' => $endOfWeek->format('Y-m-d')
    ]);
    $allAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Data Processing (No changes needed here) ---
    foreach ($allAppointments as $app) {
        $date = $app['appointment_date'];
        $time = date('H:i:00', strtotime($app['appointment_time']));
        if (!isset($appointmentsByDateAndTime[$date])) {
            $appointmentsByDateAndTime[$date] = [];
        }
        $appointmentsByDateAndTime[$date][$time] = $app;
    }

    $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchName = $stmtBranch->fetchColumn() ?? 'Clinic';
} catch (PDOException $e) {
    die("Database Error: Could not fetch schedule data. " . $e->getMessage());
}

// --- Define the Time Slots for the Grid (No changes needed here) ---
$timeSlots = [];
$startTime = new DateTime('09:00');
$endTime = new DateTime('19:00');
$interval = new DateInterval('PT30M');
$period = new DatePeriod($startTime, $interval, $endTime);
foreach ($period as $dt) {
    $timeSlots[] = $dt->format('H:i:s');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Schedule - <?= htmlspecialchars($branchName) ?></title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/schedule.css">
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
                <a href="patients.php">Patients</a>
                <a href="billing.php">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"> <?= htmlspecialchars($branchName) ?> Branch </div>
            <div class="icon-btn" id="theme-toggle"> <i id="theme-icon" class="fa-solid fa-moon"></i> </div>
            <div class="icon-btn icon-btn2" title="Notifications">🔔</div>
            <div class="profile">S</div>
        </div>
    </header>

    <main class="main">
        <div class="schedule-container">
            <div class="schedule-header">
                <div class="schedule-title">
                    Weekly Schedule <small style="font-weight: 400;">(<?= $startOfWeek->format('d M') ?> - <?= $endOfWeek->format('d M, Y') ?>)</small>
                </div>
                <div class="schedule-nav">
                    <a href="?week_start=<?= $prevWeek->format('Y-m-d') ?>"><i class="fa fa-chevron-left"></i> Prev Week</a>
                    <a href="?week_start=today" class="today-btn">Today</a>
                    <a href="?week_start=<?= $nextWeek->format('Y-m-d') ?>">Next Week <i class="fa fa-chevron-right"></i></a>
                </div>
            </div>
            <div class="schedule-grid-wrapper">
                <table class="schedule-grid">
                    <thead>
                        <tr>
                            <th class="time-header-col">Time</th>
                            <?php
                            $headerDay = clone $startOfWeek;
                            for ($i = 0; $i < 7; $i++):
                                $isTodayClass = ($headerDay->format('Y-m-d') == date('Y-m-d')) ? 'is-today' : '';
                            ?>
                                <th class="date-header-col <?= $isTodayClass ?>">
                                    <div><?= $headerDay->format('D') ?></div>
                                    <div style="font-size: 1.2rem;"><?= $headerDay->format('d') ?></div>
                                </th>
                            <?php
                                $headerDay->modify('+1 day');
                            endfor;
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $time): ?>
                            <tr>
                                <td class="time-row-col">
                                    <?php
                                    $startTime = strtotime($time);
                                    $endTime = strtotime('+30 minutes', $startTime);
                                    ?>
                                    <?= date('g:i', $startTime) ?> - <?= date('g:i A', $endTime) ?>
                                </td>
                                <?php
                                $currentDay = clone $startOfWeek;
                                for ($i = 0; $i < 7; $i++):
                                    $dayStr = $currentDay->format('Y-m-d');
                                    $isTodayClass = ($dayStr == date('Y-m-d')) ? 'is-today' : '';
                                ?>
                                    <td class="<?= $isTodayClass ?>">
                                        <?php if (isset($appointmentsByDateAndTime[$dayStr][$time])):
                                            $appointment = $appointmentsByDateAndTime[$dayStr][$time];
                                            $uid = htmlspecialchars($appointment['patient_uid'] ?? '');
                                            $regId = htmlspecialchars((string)($appointment['registration_id'] ?? ''));
                                        ?>
                                            <div class="appointment-card <?= strtolower(htmlspecialchars($appointment['status'])) ?>"
                                                data-uid="<?= $uid ?>"
                                                data-regid="<?= $regId ?>">
                                                <span class="appointment-uid"><?= $uid ?: 'Legacy' ?></span>
                                                <?= htmlspecialchars($appointment['patient_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php
                                    $currentDay->modify('+1 day');
                                endfor;
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../js/theme.js"></script>

</body>

</html>