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
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/schedule.css">
    <style>
        /* NEW: Styles for the info notice */
        .schedule-notice {
            background-color: #fff4dbff;
            border: 1px solid #fff3bdff;
            color: #9e6c00ff;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        body.dark .schedule-notice {
            background-color: #1c3b55;
            color: #cce4ff;
            border-color: #3a6a97;
        }

        /* Styles for the Reschedule Modal */
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
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-primary);
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
            border-radius: 8px;
        }

        .today-btn{
            /* margin-top: 10px; */
            height: auto;
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

            <!-- NEW: Informational Notice -->
            <div class="schedule-notice">
                <i class="fa-solid fa-circle-info"></i>
                <span>To reschedule an appointment, simply click on the patient's card in the calendar below.</span>
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
                                                 data-regid="<?= $regId ?>"
                                                 data-date="<?= htmlspecialchars($appointment['appointment_date']) ?>">
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

    <!-- Reschedule Modal -->
    <div class="modal-overlay" id="reschedule-modal">
        <div class="modal-content">
            <button class="modal-close" id="close-reschedule-modal">&times;</button>
            <h3 style="margin-bottom: 1.5rem;">Reschedule Appointment</h3>
            <form id="reschedule-form">
                <input type="hidden" name="registration_id" id="reschedule-registration-id" required>

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
                <button type="submit" class="today-btn" id="save-reschedule-btn">Update Appointment</button>
            </form>
        </div>
    </div>

    <script src="../js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rescheduleModal = document.getElementById('reschedule-modal');
            const closeRescheduleBtn = document.getElementById('close-reschedule-modal');
            const rescheduleForm = document.getElementById('reschedule-form');
            const registrationIdInput = document.getElementById('reschedule-registration-id');
            const patientNameInput = document.getElementById('reschedule-patient-name');
            const dateInput = document.getElementById('reschedule-date');
            const timeSlotSelect = document.getElementById('reschedule-time-slot');

            // --- Modal Open/Close ---
            document.querySelectorAll('.appointment-card').forEach(card => {
                card.addEventListener('click', () => {
                    const regId = card.dataset.regid;
                    const appointmentDate = card.dataset.date; // Get the date from the card's data attribute
                    const patientName = card.textContent.replace(card.querySelector('.appointment-uid').textContent, '').trim();

                    registrationIdInput.value = regId;
                    patientNameInput.value = patientName;
                    dateInput.value = appointmentDate; // Set the correct date from the clicked card

                    fetchAndPopulateSlots(dateInput.value);
                    rescheduleModal.classList.add('is-open');
                });
            });

            closeRescheduleBtn.addEventListener('click', () => rescheduleModal.classList.remove('is-open'));
            rescheduleModal.addEventListener('click', (e) => {
                if (e.target === rescheduleModal) {
                    rescheduleModal.classList.remove('is-open');
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
                        alert('Appointment rescheduled successfully!');
                        window.location.reload();
                    } else {
                        throw new Error(result.message || 'Failed to reschedule appointment.');
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                } finally {
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