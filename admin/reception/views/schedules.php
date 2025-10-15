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

// --- Service Type & Date Navigation Logic ---
$serviceType = $_GET['service_type'] ?? 'physio'; // Default to 'physio'
$view = $_GET['view'] ?? 'today'; // Default to 'today' view
$week_start_str = $_GET['week_start'] ?? 'now';

try {
    $startOfWeek = new DateTime($week_start_str, new DateTimeZone('Asia/Kolkata'));
} catch (Exception $e) {
    $startOfWeek = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
}

// If the day is not Sunday (0), modify it to the 'last Sunday'.
if ($startOfWeek->format('w') != 0) {
    $startOfWeek->modify('last sunday');
}

$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+6 days');

$prevWeek = clone $startOfWeek;
$prevWeek->modify('-1 week');
$nextWeek = clone $startOfWeek;
$nextWeek->modify('+1 week');

$branchDetails = [];
$appointmentsByDateAndTime = [];

try {
    // --- Database Fetching ---
    $stmt = $pdo->prepare("
        SELECT
            pa.appointment_id,
            pa.appointment_date,
            pa.time_slot,
            pa.status,
            p.patient_id,
            pm.patient_uid,
            r.patient_name,
            a.attendance_id IS NOT NULL AS attended
        FROM patient_appointments pa
        JOIN patients p ON pa.patient_id = p.patient_id
        JOIN registration r ON p.registration_id = r.registration_id
        LEFT JOIN patient_master pm ON r.master_patient_id = pm.master_patient_id
        LEFT JOIN attendance a ON p.patient_id = a.patient_id AND pa.appointment_date = a.attendance_date
        WHERE pa.branch_id = :branch_id
          AND pa.service_type = :service_type
          AND pa.appointment_date BETWEEN :start_date AND :end_date
    ");
    $stmt->execute([
        ':branch_id' => $branchId,
        ':service_type' => $serviceType,
        ':start_date' => $startOfWeek->format('Y-m-d'),
        ':end_date' => $endOfWeek->format('Y-m-d')
    ]);
    $allAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Data Processing ---
    foreach ($allAppointments as $app) {
        $date = $app['appointment_date'];
        $time = date('H:i:00', strtotime($app['time_slot']));
        if (!isset($appointmentsByDateAndTime[$date])) {
            $appointmentsByDateAndTime[$date] = [];
        }
        // Allow multiple appointments in the same slot
        $appointmentsByDateAndTime[$date][$time][] = $app;
    }

    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
} catch (PDOException $e) {
    die("Database Error: Could not fetch schedule data. " . $e->getMessage());
}

// --- Define the Time Slots for the Grid ---
// NEW: Dynamic time slot generation based on service type
$timeSlots = [];
if ($serviceType === 'physio') {
    $startTime = new DateTime('09:00');
    $endTime = new DateTime('19:30'); // Physio ends at 7:30 PM
    $interval = new DateInterval('PT90M'); // 1.5 hours
} else { // speech_therapy
    $startTime = new DateTime('15:00');
    $endTime = new DateTime('19:00'); // Speech therapy ends at 7 PM
    $interval = new DateInterval('PT60M'); // 1 hour
}

$period = new DatePeriod($startTime, $interval, $endTime);
foreach ($period as $dt) {
    // We store the start time of the slot
    $timeSlots[] = $dt->format('H:i:s');
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedules - <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $serviceType))) ?></title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/schedule.css">
    <!-- NEW: Add toast notification styles -->
    <style>
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            padding: 12px 18px;
            border-radius: 8px;
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.4s ease-in-out;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast.success {
            background-color: #27ae60;
        }

        .toast.error {
            background-color: #e74c3c;
        }
    </style>
    <style>
        /* NEW: Improved styling for appointment cards */
        .appointment-card {
            color: #fff;
            /* White text for better contrast */
            padding: 6px 8px;
            font-size: 0.85rem;
            border-left: 4px solid rgba(0, 0, 0, 0.3);
        }

        .appointment-card.scheduled {
            background-color: #2563eb;
            /* A strong blue for scheduled */
            border-left-color: #1d4ed8;
        }

        .appointment-card.completed {
            background-color: #16a34a;
            /* A clear green for completed */
            border-left-color: #15803d;
        }

        .appointment-card.missed {
            background-color: #dc2626;
            /* A strong red for missed */
            border-left-color: #b91c1c;
            opacity: 0.85;
        }

        .appointment-card {
            margin-bottom: 4px;
            /* Add space between multiple cards in one slot */
        }

        .appointment-card:last-child {
            margin-bottom: 0;
        }

        .schedule-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .schedule-tabs a {
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .schedule-tabs a.active,
        .schedule-tabs a:hover {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        /* NEW: Styles for Today's View horizontal scroll */
        .today-view-grid .appointments-list {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 8px 4px;
            /* Add some padding for scrollbar and cards */
        }

        .today-view-grid .appointment-card {
            flex: 0 0 160px;
            /* Give cards a fixed width so they don't shrink */
            margin-bottom: 0;
        }

        /* NEW: Styles for richer search results */
        #patient-search-results {
            border: 1px solid var(--border-color-primary);
            max-height: 250px;
            overflow-y: auto;
            background: var(--bg-primary);
        }

        .search-result-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color-primary);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background-color: #eef7ff;
        }

        body.dark .search-result-item:hover {
            background-color: #2a3a5e;
        }

        .search-result-item .name {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-primary);
        }

        .search-result-item .uid {
            font-size: 0.8rem;
            color: var(--primary-color);
            font-family: monospace;
        }

        .search-result-item .details {
            display: flex;
            gap: 1rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* NEW: Styles for the Add to Schedule Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .modal-overlay.is-open {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--bg-primary);
            padding: 2rem;
            border-radius: var(--border-radius-card);
            width: 90%;
            max-width: 800px;
            position: relative;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        #add-schedule-form {
            overflow-y: auto;
            /* Allow vertical scrolling */
            padding-right: 1rem;
            /* Add space for scrollbar */
            margin-right: -1rem;
            /* Counteract padding */
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            color: #000;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        body.dark .modal-close {
            color: #fff;
        }


        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color-primary);
            border-radius: var(--border-radius-btn);
        }

        .btn-primary {
            margin-top: 25px;
            margin-left: 20px;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius-btn);
            cursor: pointer;
            font-weight: 600;
        }

        .btn-primary:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .today-btn2 {
            color: #000;
            background-color: #fff;
            padding: 0.55rem 1.5rem;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <div class="logo">
                <?php if (!empty($branchDetails['logo_primary_path'])) : ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo">
                <?php else : ?>
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
                <!-- <a class="active" href="schedules.php">Schedules</a> -->
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"> <?php echo $branchName; ?> Branch </div>
            <div class="icon-btn" id="theme-toggle"> <i id="theme-icon" class="fa-solid fa-moon"></i> </div>
            <div class="icon-btn icon-btn2" title="Notifications" onclick="openNotif()">🔔</div>
            <div class="profile" onclick="openForm()">R</div>
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
                <li><a href="profile.php"><i class="fa-solid fa-user-circle"></i> Profile</a></li>
                <li class="logout"><a href="logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
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
        <div class="schedule-container">
            <div class="schedule-header">
                <div class="schedule-title">
                    Treatment Schedule
                </div>
                <div class="schedule-nav-wrapper" style="display: flex; align-items: center; gap: 1rem;">
                    <div class="schedule-nav">
                        <?php if ($view === 'week') : ?>
                            <a href="?view=week&week_start=<?= $prevWeek->format('Y-m-d') ?>&service_type=<?= $serviceType ?>"><i class="fa fa-chevron-left"></i> Prev Week</a>
                            <a href="?view=week&week_start=today&service_type=<?= $serviceType ?>" class="today-btn">This Week</a>
                            <a href="?view=week&week_start=<?= $nextWeek->format('Y-m-d') ?>&service_type=<?= $serviceType ?>">Next Week <i class="fa fa-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                    <!-- View Toggle Button -->
                    <?php if ($view === 'today') : ?>
                        <button class="today-btn" id="add-to-schedule-btn"><i class="fa fa-plus"></i> Add to Schedule</button>
                        <a href="?view=week&service_type=<?= $serviceType ?>" class="today-btn today-btn2">View Weekly</a>
                    <?php else : ?>
                        <a href="?view=today&service_type=<?= $serviceType ?>" class="today-btn today-btn2">View Today</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="schedule-tabs">
                <a href="?service_type=physio&week_start=<?= $_GET['week_start'] ?? 'today' ?>" class="<?= $serviceType === 'physio' ? 'active' : '' ?>">Physiotherapy</a>
                <a href="?service_type=speech_therapy&week_start=<?= $_GET['week_start'] ?? 'today' ?>" class="<?= $serviceType === 'speech_therapy' ? 'active' : '' ?>">Speech Therapy</a>
            </div>

            <?php if ($view === 'today') : ?>
                <!-- ================== TODAY'S VIEW ================== -->
                <div class="schedule-grid-wrapper">
                    <table class="schedule-grid today-view-grid">
                        <thead>
                            <tr>
                                <th class="time-header-col">Time</th>
                                <th class="date-header-col is-today">
                                    <div>Appointments for <?= date('l, d M Y') ?></div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $todayStr = date('Y-m-d');
                            foreach ($timeSlots as $time) : ?>
                                <tr>
                                    <td class="time-row-col">
                                        <?= date('g:i A', strtotime($time)) ?>
                                    </td>
                                    <td class="is-today">
                                        <div class="appointments-list">
                                            <?php if (isset($appointmentsByDateAndTime[$todayStr][$time])) : ?>
                                                <?php foreach ($appointmentsByDateAndTime[$todayStr][$time] as $appointment) :
                                                    $uid = htmlspecialchars($appointment['patient_uid'] ?? '');
                                                    $patientId = htmlspecialchars((string)($appointment['patient_id'] ?? ''));

                                                    // NEW: Determine card color based on attendance
                                                    $cardClass = 'scheduled'; // Default
                                                    if ($appointment['attended']) {
                                                        $cardClass = 'completed';
                                                    } elseif (new DateTime($appointment['appointment_date']) < new DateTime('today')) {
                                                        $cardClass = 'missed';
                                                    }
                                                ?>
                                                    <div class="appointment-card <?= $cardClass ?>"
                                                        data-patient-id="<?= $patientId ?>"
                                                        data-appointment-id="<?= htmlspecialchars((string)($appointment['appointment_id'] ?? '')) ?>"
                                                        data-patient-name="<?= htmlspecialchars($appointment['patient_name']) ?>"
                                                        data-appointment-date="<?= htmlspecialchars($appointment['appointment_date']) ?>"
                                                        title="Click to reschedule">

                                                        <span class="appointment-uid"><?= $uid ?: 'ID: ' . $patientId ?></span>
                                                        <?= htmlspecialchars($appointment['patient_name']) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <span style="color: var(--text-secondary); font-style: italic; padding-left: 8px;">No appointments in this slot.</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <!-- ================== WEEKLY VIEW (Original) ================== -->
                <div class="schedule-grid-wrapper">
                    <table class="schedule-grid">
                        <thead>
                            <tr>
                                <th class="time-header-col">Time</th>
                                <?php
                                $currentDay = clone $startOfWeek;
                                for ($i = 0; $i < 7; $i++) :
                                    $dayStr = $currentDay->format('Y-m-d');
                                    $isTodayClass = ($dayStr == date('Y-m-d')) ? 'is-today' : '';
                                ?>
                                    <th class="date-header-col <?= $isTodayClass ?>">
                                        <div><?= $currentDay->format('D') ?></div>
                                        <div style="font-size: 1.2rem;"><?= $currentDay->format('d') ?></div>
                                    </th>
                                <?php
                                    $currentDay->modify('+1 day');
                                endfor;
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timeSlots as $time) : ?>
                                <tr>
                                    <td class="time-row-col">
                                        <?= date('g:i A', strtotime($time)) ?>
                                    </td>
                                    <?php
                                    $currentDay = clone $startOfWeek;
                                    for ($i = 0; $i < 7; $i++) :
                                        $dayStr = $currentDay->format('Y-m-d');
                                        $isTodayClass = ($dayStr == date('Y-m-d')) ? 'is-today' : '';
                                    ?>
                                        <td class="<?= $isTodayClass ?>">
                                            <?php if (isset($appointmentsByDateAndTime[$dayStr][$time])) : ?>
                                                <?php foreach ($appointmentsByDateAndTime[$dayStr][$time] as $appointment) :
                                                    $uid = htmlspecialchars($appointment['patient_uid'] ?? '');
                                                    $patientId = htmlspecialchars((string)($appointment['patient_id'] ?? ''));

                                                    // NEW: Determine card color based on attendance
                                                    $cardClass = 'scheduled'; // Default
                                                    if ($appointment['attended']) {
                                                        $cardClass = 'completed';
                                                    } elseif (new DateTime($appointment['appointment_date']) < new DateTime('today')) {
                                                        $cardClass = 'missed';
                                                    }
                                                ?>
                                                    <div class="appointment-card <?= $cardClass ?>"
                                                        data-patient-id="<?= $patientId ?>"
                                                        data-appointment-id="<?= htmlspecialchars((string)($appointment['appointment_id'] ?? '')) ?>"
                                                        data-patient-name="<?= htmlspecialchars($appointment['patient_name']) ?>"
                                                        data-appointment-date="<?= htmlspecialchars($appointment['appointment_date']) ?>"
                                                        title="Click to reschedule">

                                                        <span class="appointment-uid"><?= $uid ?: 'ID: ' . $patientId ?></span>
                                                        <?= htmlspecialchars($appointment['patient_name']) ?>
                                                    </div>
                                                <?php endforeach; ?>
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
            <?php endif; ?>
        </div>
    </main>

    <!-- ================== NEW: Reschedule Modal ================== -->
    <div class="modal-overlay" id="reschedule-modal">
        <div class="modal-content">
            <button class="modal-close" id="close-reschedule-modal">&times;</button>
            <h3 style="flex-shrink: 0; margin-bottom: 1.5rem;">Reschedule Appointment</h3>
            <form id="reschedule-form">
                <input type="hidden" name="appointment_id" id="reschedule-appointment-id" required>
                <input type="hidden" name="service_type" id="reschedule-service-type" value="<?= htmlspecialchars($serviceType) ?>">

                <div class="form-group">
                    <label>Patient</label>
                    <input type="text" id="reschedule-patient-name" readonly style="background: var(--bg-tertiary);">
                </div>

                <div class="form-group">
                    <label for="reschedule-date">New Date</label>
                    <input type="date" id="reschedule-date" name="new_date" required>
                </div>

                <div class="form-group">
                    <label for="reschedule-time-slot">New Time Slot</label>
                    <select id="reschedule-time-slot" name="new_time_slot" required>
                        <option value="">Select a date first</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn-primary" id="save-reschedule-btn">Update Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NEW: Toast container -->
    <div id="toast-container"></div>

    <!-- ================== NEW: Add to Schedule Modal ================== -->
    <div class="modal-overlay" id="add-schedule-modal">
        <div class="modal-content">
            <button class="modal-close" id="close-schedule-modal">&times;</button>
            <h3 style="flex-shrink: 0; margin-bottom: 1.5rem;">Add Manual Appointment</h3>
            <form id="add-schedule-form">
                <input type="hidden" name="patient_id" id="modal-patient-id" required>
                <input type="hidden" name="service_type" id="modal-service-type" value="<?= htmlspecialchars($serviceType) ?>">

                <div class="form-group">
                    <label for="patient-search">Search Patient (Name, UID, Phone No)</label>
                    <input type="text" id="patient-search" placeholder="Start typing to search..." autocomplete="off" required>
                    <div id="patient-search-results"></div>
                </div>

                <div class="form-group">
                    <label for="modal-date">Date</label>
                    <input type="date" id="modal-date" name="appointment_date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="modal-time-slot">Time Slot</label>
                    <select id="modal-time-slot" name="time_slot" required>
                        <option value="">Select a date first</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn-primary" id="save-schedule-btn">Save Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- NEW: Toast container -->
    <div id="toast-container"></div>

    <!-- ================== NEW: Add to Schedule Modal ================== -->
    <div class="modal-overlay" id="add-schedule-modal">
        <div class="modal-content">
            <button class="modal-close" id="close-schedule-modal">&times;</button>
            <h3 style="flex-shrink: 0; margin-bottom: 1.5rem;">Add Manual Appointment</h3>
            <form id="add-schedule-form">
                <input type="hidden" name="patient_id" id="modal-patient-id" required>
                <input type="hidden" name="service_type" id="modal-service-type" value="<?= htmlspecialchars($serviceType) ?>">

                <div class="form-group">
                    <label for="patient-search">Search Patient (Name, UID, Phone No)</label>
                    <input type="text" id="patient-search" placeholder="Start typing to search..." autocomplete="off" required>
                    <div id="patient-search-results"></div>
                </div>

                <div class="form-group">
                    <label for="modal-date">Date</label>
                    <input type="date" id="modal-date" name="appointment_date" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="modal-time-slot">Time Slot</label>
                    <select id="modal-time-slot" name="time_slot" required>
                        <option value="">Select a date first</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn-primary" id="save-schedule-btn">Save Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/theme.js"></script>
    <script>
        // NEW: Toast notification function
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;

            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);

            setTimeout(() => {
                toast.classList.remove('show');
                toast.addEventListener('transitionend', () => toast.remove(), {
                    once: true
                });
            }, 4000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addScheduleBtn = document.getElementById('add-to-schedule-btn');
            const modal = document.getElementById('add-schedule-modal');
            const closeModalBtn = document.getElementById('close-schedule-modal');
            const patientSearchInput = document.getElementById('patient-search');
            const patientSearchResults = document.getElementById('patient-search-results');
            const patientIdInput = document.getElementById('modal-patient-id');
            const modalDateInput = document.getElementById('modal-date');
            const modalTimeSlotSelect = document.getElementById('modal-time-slot');
            const modalServiceType = document.getElementById('modal-service-type').value;
            const addScheduleForm = document.getElementById('add-schedule-form');
            const saveScheduleBtn = document.getElementById('save-schedule-btn');
            // NEW: Reschedule Modal Elements
            const rescheduleModal = document.getElementById('reschedule-modal');
            const closeRescheduleBtn = document.getElementById('close-reschedule-modal');
            const rescheduleForm = document.getElementById('reschedule-form');
            const rescheduleAppointmentIdInput = document.getElementById('reschedule-appointment-id');
            const reschedulePatientNameInput = document.getElementById('reschedule-patient-name');
            const rescheduleDateInput = document.getElementById('reschedule-date');
            const rescheduleTimeSlotSelect = document.getElementById('reschedule-time-slot');
            const saveRescheduleBtn = document.getElementById('save-reschedule-btn'); // Also define this

            if (addScheduleBtn) {
                addScheduleBtn.addEventListener('click', () => modal.classList.add('is-open'));
            }
            closeModalBtn.addEventListener('click', () => modal.classList.remove('is-open'));
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.classList.remove('is-open');
            });
            // NEW: Reschedule modal open/close
            closeRescheduleBtn.addEventListener('click', () => rescheduleModal.classList.remove('is-open'));
            rescheduleModal.addEventListener('click', (e) => {
                if (e.target === rescheduleModal) rescheduleModal.classList.remove('is-open');
            });


            // --- NEW: Time Slot Fetching Logic ---
            const generateTimeSlots = (serviceType) => {
                const slots = [];
                let start, end, interval;
                if (serviceType === 'physio') {
                    start = new Date('1970-01-01T09:00:00');
                    end = new Date('1970-01-01T19:30:00');
                    interval = 90;
                } else { // speech_therapy
                    start = new Date('1970-01-01T15:00:00');
                    end = new Date('1970-01-01T19:00:00');
                    interval = 60;
                }
                while (start < end) {
                    const time = start.toTimeString().substring(0, 5);
                    const label = start.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    slots.push({
                        time,
                        label
                    });
                    start.setMinutes(start.getMinutes() + interval);
                }
                return slots;
            };

            const fetchAndPopulateSlots = async (dateString, serviceType, targetSelect = modalTimeSlotSelect) => {
                targetSelect.innerHTML = '<option value="">Loading...</option>';
                targetSelect.disabled = true;
                try {
                    const res = await fetch(`../api/get_treatment_slots.php?date=${dateString}&service_type=${serviceType}`);
                    const data = await res.json();
                    targetSelect.innerHTML = '';
                    if (!data.success) throw new Error(data.message);

                    const capacity = serviceType === 'physio' ? 10 : 1;
                    const allSlots = generateTimeSlots(serviceType);

                    allSlots.forEach(slot => {
                        const bookedCount = data.booked[`${slot.time}:00`] || 0;
                        const isFull = bookedCount >= capacity;
                        const option = document.createElement('option');
                        option.value = slot.time;
                        option.textContent = `${slot.label} (${bookedCount}/${capacity} booked)`;
                        option.disabled = isFull;
                        targetSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error(`Error fetching slots:`, error);
                    targetSelect.innerHTML = `<option value="">Error loading slots</option>`;
                } finally {
                    targetSelect.disabled = false;
                }
            };

            let searchTimeout;
            patientSearchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                const query = patientSearchInput.value;
                patientIdInput.value = ''; // Clear hidden input

                if (query.length < 2) {
                    patientSearchResults.innerHTML = '';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`../api/search_patients.php?q=${query}`)
                        .then(res => res.json())
                        .then(data => {
                            patientSearchResults.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(patient => {
                                    const itemDiv = document.createElement('div');
                                    itemDiv.className = 'search-result-item';
                                    itemDiv.dataset.patientId = patient.patient_id;
                                    itemDiv.dataset.patientName = patient.patient_name;
                                    itemDiv.innerHTML = `
                                    <div class="name">${patient.patient_name} <span class="uid">(${patient.patient_uid || 'N/A'})</span></div>
                                    <div class="details">
                                        <span><i class="fa-solid fa-user"></i> ${patient.age || 'N/A'} yrs, ${patient.gender || 'N/A'}</span>
                                        <span><i class="fa-solid fa-phone"></i> ${patient.phone_number || 'N/A'}</span>
                                    </div>
                                `;
                                    patientSearchResults.appendChild(itemDiv);
                                });
                            } else {
                                patientSearchResults.innerHTML = '<div class="search-result-item">No patients found.</div>';
                            }
                        });
                }, 300);
            });

            patientSearchResults.addEventListener('click', (e) => {
                const resultItem = e.target.closest('.search-result-item');
                if (resultItem && resultItem.dataset.patientId) {
                    patientIdInput.value = resultItem.dataset.patientId;
                    patientSearchInput.value = resultItem.dataset.patientName;
                    patientSearchResults.innerHTML = '';
                }
            });

            // --- NEW: Form Submission Logic ---
            modalDateInput.addEventListener('change', () => {
                fetchAndPopulateSlots(modalDateInput.value, modalServiceType);
            });

            addScheduleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                saveScheduleBtn.disabled = true;
                saveScheduleBtn.textContent = 'Saving...';

                const formData = new FormData(addScheduleForm);
                const payload = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch('../api/add_manual_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (result.success) {
                        showToast('Appointment added successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(result.message || 'Failed to save appointment.');
                    }
                } catch (error) {
                    showToast('Error: ' + error.message, 'error');
                } finally {
                    saveScheduleBtn.disabled = false;
                    saveScheduleBtn.textContent = 'Save Appointment';
                }
            });

            // Initial load for today's date
            fetchAndPopulateSlots(modalDateInput.value, modalServiceType);

            // --- NEW: Reschedule Logic (Moved inside DOMContentLoaded) ---
            document.body.addEventListener('click', function(e) {
                const card = e.target.closest('.appointment-card');
                if (!card || !card.dataset.patientId) return;

                // Populate reschedule modal with data from the clicked card
                rescheduleAppointmentIdInput.value = card.dataset.appointmentId;
                reschedulePatientNameInput.value = card.dataset.patientName;
                rescheduleDateInput.value = card.dataset.appointmentDate;

                // Fetch slots for the appointment's current date
                fetchAndPopulateSlots(card.dataset.appointmentDate, modalServiceType, rescheduleTimeSlotSelect);

                // Open the modal
                rescheduleModal.classList.add('is-open');
            });

            rescheduleDateInput.addEventListener('change', () => {
                fetchAndPopulateSlots(rescheduleDateInput.value, modalServiceType, rescheduleTimeSlotSelect);
            });

            rescheduleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                saveRescheduleBtn.disabled = true;
                saveRescheduleBtn.textContent = 'Updating...';

                const formData = new FormData(rescheduleForm);
                const payload = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch('../api/reschedule_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();

                    if (result.success) {
                        showToast('Appointment rescheduled successfully!', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        throw new Error(result.message || 'Failed to reschedule appointment.');
                    }
                } catch (error) {
                    showToast('Error: ' + error.message, 'error');
                } finally {
                    saveRescheduleBtn.disabled = false;
                    saveRescheduleBtn.textContent = 'Update Appointment';
                }
            });
        });
    </script>
</body>

</html>