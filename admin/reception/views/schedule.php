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
          AND r.status NOT IN ('closed', 'cancelled')
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

    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <style>
        /* Custom scrollbar for WebKit browsers */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .dark ::-webkit-scrollbar-track {
            background: #2d3748;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #555;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #777;
        }

        @media (max-width: 1024px) and (min-width: 768px) {
            .text-2xl {
                font-size: 1.5rem;
                /* Smaller title for tablet */
            }

            .schedule-container .flex.items-center.gap-2.md\:gap-4 a {
                padding: 0.375rem 0.75rem;
                /* py-1.5 px-3 */
                font-size: 0.75rem;
                /* text-xs */
            }
        }
    </style>
</head>

<body class="bg-gray-100 dark:bg-gray-900 font-sans">
    <header class="flex items-center justify-between h-26 px-4 md:px-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="logo-container flex items-center">
            <div class="logo h-30 flex items-center">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo" class="h-20 w-30">
                <?php else: ?>
                    <div class="logo-placeholder text-sm font-semibold text-gray-500 dark:text-gray-400">Primary Logo N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <nav class="hidden lg:flex items-center gap-1">
            <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-tachometer-alt w-4 text-center"></i><span>Dashboard</span></a>
            <a href="inquiry.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-magnifying-glass w-4 text-center"></i><span>Inquiry</span></a>
            <a href="registration.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-user-plus w-4 text-center"></i><span>Registration</span></a>
            <a href="appointments.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-calendar-check w-4 text-center"></i><span>Appointments</span></a>
            <a href="patients.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-users w-4 text-center"></i><span>Patients</span></a>
            <a href="billing.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-file-invoice-dollar w-4 text-center"></i><span>Billing</span></a>
            <a href="attendance.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-user-check w-4 text-center"></i><span>Attendance</span></a>
            <a href="tests.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-vial w-4 text-center"></i><span>Tests</span></a>
            <a href="reports.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-chart-line w-4 text-center"></i><span>Reports</span></a>
            <a href="expenses.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-money-bill-wave w-4 text-center"></i><span>Expenses</span></a>
        </nav>

        <div class="nav-actions flex items-center gap-2">
            <div class="icon-btn hidden md:flex items-center justify-center px-3 py-1.5 rounded-full text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/50 text-sm font-medium" title="Branch"><?= htmlspecialchars($branchName) ?> Branch</div>
            <button class="icon-btn flex items-center justify-center w-9 h-9 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></button>
            <button class="icon-btn icon-btn2 flex items-center justify-center w-9 h-9 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="Notifications" onclick="openNotif()"><i class="fa-solid fa-bell"></i></button>
            <button class="profile flex items-center justify-center w-9 h-9 rounded-full bg-teal-600 text-white font-semibold cursor-pointer hover:bg-teal-700 transition-all" onclick="openForm()">S</button>
        </div>
    </header>

    <div class="menu hidden fixed top-16 right-4 md:right-6 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-[101]" id="myMenu">
        <div class="p-1">
            <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"><i class="fa-solid fa-user-circle w-4 text-center"></i> Profile</a>
            <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50 rounded-md"><i class="fa-solid fa-sign-out-alt w-4 text-center"></i> Logout</a>
        </div>
    </div>

    <div class="notification hidden fixed top-16 right-4 md:right-20 w-64 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-[101]" id="myNotif">
        <div class="p-2">
            <a href="changelog.html" class="active2 flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">View Changes (1)</a>
        </div>
    </div>

    <main class="p-4 md:p-6">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                <div class="text-2xl font-bold text-gray-800 dark:text-white w-full">
                    Weekly Schedule <small style="font-weight: 400;">(<?= $startOfWeek->format('d M') ?> - <?= $endOfWeek->format('d M, Y') ?>)</small>
                </div>
                <div class="flex flex-wrap md:flex-nowrap items-center gap-2 md:gap-4 w-full md:w-auto justify-start">
                    <a href="dashboard.php" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="?week_start=<?= $prevWeek->format('Y-m-d') ?>" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><i class="fa fa-chevron-left"></i> Prev Week</a>
                    <a href="?week_start=today" class="px-4 py-2 text-sm font-medium text-white bg-teal-600 border border-teal-600 rounded-lg hover:bg-teal-700">Today</a>
                    <a href="?week_start=<?= $nextWeek->format('Y-m-d') ?>" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">Next Week <i class="fa fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="flex items-center gap-3 p-4 mb-6 rounded-lg border bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800/30 dark:text-yellow-300">
                <i class="fa-solid fa-circle-info"></i>
                <span>To reschedule an appointment, simply click on the patient's card in the calendar below.</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1200px] border-collapse text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="p-3 font-semibold text-left text-gray-600 dark:text-gray-300 w-40">Time</th>
                            <?php
                            $headerDay = clone $startOfWeek;
                            for ($i = 0; $i < 7; $i++):
                                $isTodayClass = ($headerDay->format('Y-m-d') == date('Y-m-d')) ? 'is-today' : '';
                            ?>
                                <th class="p-3 font-semibold text-center text-gray-600 dark:text-gray-300 <?= $isTodayClass ? 'bg-teal-50 dark:bg-teal-900/30' : '' ?>">
                                    <div class="text-xs"><?= $headerDay->format('D') ?></div>
                                    <div class="text-xl"><?= $headerDay->format('d') ?></div>
                                </th>
                            <?php
                                $headerDay->modify('+1 day');
                            endfor;
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $time): ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="p-3 font-medium text-gray-500 dark:text-gray-400 align-top">
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
                                    $isTodayClass = ($dayStr == date('Y-m-d')) ? 'bg-gray-50/50 dark:bg-gray-800/50' : '';
                                ?>
                                    <td class="p-2 border-l border-gray-200 dark:border-gray-700 align-top <?= $isTodayClass ?>">
                                        <?php if (isset($appointmentsByDateAndTime[$dayStr][$time])):
                                            $appointment = $appointmentsByDateAndTime[$dayStr][$time];
                                            $uid = htmlspecialchars($appointment['patient_uid'] ?? '');
                                            $regId = htmlspecialchars((string)($appointment['registration_id'] ?? ''));
                                            $statusClass = match (strtolower($appointment['status'])) {
                                                'consulted', 'completed' => 'bg-green-500',
                                                'pending' => 'bg-orange-500',
                                                'closed', 'cancelled' => 'bg-red-500',
                                                default => 'bg-blue-500',
                                            };
                                        ?>
                                            <div class="appointment-card p-2 rounded-md text-white text-xs cursor-pointer hover:opacity-90 transition-opacity <?= $statusClass ?>"
                                                data-regid="<?= $regId ?>"
                                                data-date="<?= htmlspecialchars($appointment['appointment_date']) ?>"
                                                data-patient-name="<?= htmlspecialchars($appointment['patient_name']) ?>">
                                                <div class="font-bold truncate"><?= htmlspecialchars($appointment['patient_name']) ?></div>
                                                <div class="text-white/80"><?= $uid ?: 'Legacy' ?></div>
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

    <div id="reschedule-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] hidden items-center justify-center">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-2xl w-full max-w-md m-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Reschedule Appointment</h3>
                <button class="text-gray-500 hover:text-red-500 text-2xl font-bold" id="close-reschedule-modal">&times;</button>
            </div>
            <form id="reschedule-form">
                <input type="hidden" name="registration_id" id="reschedule-registration-id" required>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Patient</label>
                        <input type="text" id="reschedule-patient-name" readonly class="block w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label for="reschedule-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Date</label>
                        <input type="date" id="reschedule-date" name="new_date" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label for="reschedule-time-slot" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Time Slot</label>
                        <select id="reschedule-time-slot" name="new_time_slot" required class="block w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">Select a date first</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 text-right">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center py-2.5 px-6 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500" id="save-reschedule-btn">Update Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        // --- Popup Functions (from dashboard.js) ---
        function openForm() {
            document.getElementById("myMenu").classList.toggle("hidden");
        }

        function openNotif() {
            document.getElementById("myNotif").classList.toggle("hidden");
        }
        // Close popups if clicked outside (from dashboard.js)
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.profile, .menu')) document.getElementById("myMenu").classList.add('hidden');
            if (!event.target.closest('.icon-btn2, .notification')) document.getElementById("myNotif").classList.add('hidden');
        });

        // --- Schedule Page Logic ---
        document.addEventListener('DOMContentLoaded', function() {
            const rescheduleModal = document.getElementById('reschedule-modal');
            const closeRescheduleBtn = document.getElementById('close-reschedule-modal');
            const rescheduleForm = document.getElementById('reschedule-form');
            const registrationIdInput = document.getElementById('reschedule-registration-id');
            const patientNameInput = document.getElementById('reschedule-patient-name');
            const dateInput = document.getElementById('reschedule-date');
            const timeSlotSelect = document.getElementById('reschedule-time-slot');

            // --- Modal Open/Close ---
            document.body.addEventListener('click', function(e) {
                const card = e.target.closest('.appointment-card');
                if (!card) return;

                const regId = card.dataset.regid;
                const appointmentDate = card.dataset.date;
                const patientName = card.dataset.patientName;
                registrationIdInput.value = regId;
                patientNameInput.value = patientName;
                dateInput.value = appointmentDate; // Set the correct date from the clicked card

                fetchAndPopulateSlots(dateInput.value);
                rescheduleModal.classList.remove('hidden');
                rescheduleModal.classList.add('flex');
            });

            closeRescheduleBtn.addEventListener('click', () => rescheduleModal.classList.add('hidden'));
            rescheduleModal.addEventListener('click', (e) => {
                if (e.target === rescheduleModal) {
                    rescheduleModal.classList.add('hidden');
                }
            });

            // --- Slot Fetching ---
            const fetchAndPopulateSlots = async (dateString) => {
                timeSlotSelect.innerHTML = '<option value="">Loading...</option>';
                timeSlotSelect.disabled = true;
                try {
                    const res = await fetch(`../api/get_slots.php?date=${dateString}`);
                    const data = await res.json();
                    timeSlotSelect.innerHTML = '';
                    if (!data.success) throw new Error(data.message);

                    if (data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot.time;
                            option.textContent = slot.label;
                            if (slot.disabled) {
                                option.disabled = true;
                                option.textContent += " (Booked)";
                            }
                            timeSlotSelect.appendChild(option);
                        });
                    } else {
                        timeSlotSelect.innerHTML = '<option value="">No slots available</option>';
                    }
                } catch (error) {
                    console.error(`Error fetching slots:`, error);
                    timeSlotSelect.innerHTML = `<option value="">Error loading slots</option>`;
                } finally {
                    timeSlotSelect.disabled = false;
                }
            };

            dateInput.addEventListener('change', () => {
                fetchAndPopulateSlots(dateInput.value);
            });

            // --- Form Submission ---
            rescheduleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const saveBtn = document.getElementById('save-reschedule-btn');
                saveBtn.disabled = true;
                saveBtn.textContent = 'Updating...';

                const formData = new FormData(rescheduleForm);
                const payload = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch('../api/reschedule_registration.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();

                    if (result.success) {
                        window.location.reload();
                    } else {
                        throw new Error(result.message || 'Failed to reschedule appointment.');
                    }
                } catch (error) {} finally {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Update Appointment';
                }
            });

            // Add data-date attribute to table cells for easier retrieval
            document.querySelectorAll('.schedule-grid tbody td:not(.time-row-col)').forEach((td, index) => {
                const dayIndex = index % 7;
                const header = document.querySelectorAll('.schedule-grid thead th.date-header-col')[dayIndex];
                const day = header.querySelector('div:last-child').textContent;
                const monthYear = "<?= $startOfWeek->format('M Y') ?>"; // A bit of a hack, but works for weekly view
                // This part is tricky without a full date on the header. Let's assume we can rebuild it.
                // For simplicity, the JS will rely on the card's data attributes if we add them.
            });
        });
    </script>
</body>

</html>