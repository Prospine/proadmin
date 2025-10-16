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
    // Set timezone and get today's date for filtering
    date_default_timezone_set('Asia/Kolkata');
    $today = date('Y-m-d');

    // --- NEW: Fetch data for the schedule grid ---
    $stmtSchedule = $pdo->prepare("
        SELECT
            r.registration_id,
            r.patient_name,
            r.appointment_time,
            r.status,
            pm.patient_uid
        FROM registration r
        LEFT JOIN patient_master pm ON r.master_patient_id = pm.master_patient_id
        WHERE r.branch_id = :branch_id
          AND r.appointment_date = :today
          AND r.appointment_time IS NOT NULL
          AND r.status NOT IN ('closed', 'cancelled')
    ");
    $stmtSchedule->execute([':branch_id' => $branchId, ':today' => $today]);
    $todaysAppointments = $stmtSchedule->fetchAll(PDO::FETCH_ASSOC);

    // Process appointments into a time-keyed array for easy lookup
    $appointmentsByTime = [];
    foreach ($todaysAppointments as $app) {
        // Normalize time to H:i:s format to match time slots
        $timeKey = date('H:i:00', strtotime($app['appointment_time']));
        $appointmentsByTime[$timeKey] = $app;
    }

    // Define the time slots for the grid columns
    $timeSlots = [];
    $startTime = new DateTime('09:00');
    $endTime = new DateTime('19:00'); // Grid will go up to 18:00 - 18:30 slot
    $interval = new DateInterval('PT30M');
    $timePeriod = new DatePeriod($startTime, $interval, $endTime);

    foreach ($timePeriod as $time) {
        $timeSlots[] = $time->format('H:i:s');
    }
    // --- END: New schedule data logic ---

    // --- MODIFIED QUERY ---
    // We now JOIN with the patient_master table to fetch the patient_uid.
    // A LEFT JOIN is used to ensure that even old registration records
    // without a master_patient_id will still be displayed.
    $stmt = $pdo->prepare("
        SELECT
            reg.registration_id,
            reg.patient_name,
            reg.phone_number,
            reg.age,
            reg.gender,
            reg.chief_complain,
            reg.reffered_by,
            reg.consultation_amount,
            reg.created_at,
            reg.consultation_type,
            reg.status,
            pm.patient_uid -- Here is our shiny new UID!
        FROM
            registration AS reg
        LEFT JOIN
            patient_master AS pm ON reg.master_patient_id = pm.master_patient_id
        WHERE
            reg.branch_id = :branch_id AND DATE(reg.created_at) = :today
        ORDER BY
            reg.created_at DESC
    ");
    $stmt->execute([':branch_id' => $branchId, ':today' => $today]);
    // The $inquiries variable will now contain the 'patient_uid' for each record
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
} catch (PDOException $e) {
    error_log("Error fetching Registration Details: " . $e->getMessage());
    die("Error fetching Registration Details. Please try again later.");
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/registration.css">
    <!-- NEW: Link to schedule styles for the grid -->
    <link rel="stylesheet" href="../css/schedule.css">

    <style>
        /* NEW: Styles for the side-by-side layout */
        .appointments-layout {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .schedule-column {
            flex: 0 0 240px;
            /* A slightly more comfortable fixed width */
        }

        .registrations-column {
            flex: 1 1 auto;
            /* Table takes remaining space */
        }

        /* Minor override for this page to make the grid fit well */
        .schedule-grid-wrapper {
            margin-bottom: 2rem;
        }

        /* NEW: Override min-width from schedule.css for this vertical view */
        .schedule-column .schedule-grid {
            min-width: unset;
            /* Remove the large minimum width */
            width: 400px;
        }

        .patient-message {
            margin-top: 8px;
            padding: 16px 20px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
            background-color: #f7f9fc;
        }

        .patient-message:empty {
            display: none;
        }

        .patient-message:contains("✅") {
            background-color: #e6f7ed;
            color: #1a7f37;
            border: 1px solid #1a7f37;
        }

        .patient-message:contains("⚠️") {
            background-color: #fff4e5;
            color: #8a6d3b;
            border: 1px solid #d6a05b;
        }

        body.dark .patient-message {
            background-color: var(--card-bg2);
            color: var(--text-color);
        }

        button {
            position: relative;
            width: auto;
        }

        body.dark .schedule-grid .is-today {
            background-color: #242424;
        }

        body.dark .schedule-grid th.is-today {
            color: #fff;
        }

        @media screen and (max-width: 1024px) {
            .modern-table td:first-child {
                font-size: 1rem;
            }

            .schedule-grid td {
                height: 35px;
            }
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
            <a href="/download-app/index.html" class="mobile-download-btn">
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
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a href="registration.php">Registration</a>
                <a class="active" href="appointments.php">Appointments</a>
                <a href="patients.php">Patients</a>
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
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="dashboard-container">
            <div class="top-bar">
                <h2>Today's Appointments</h2>
            </div>

            <!-- NEW: Main layout container -->
            <div class="appointments-layout">

                <!-- Left Column: Vertical Schedule -->
                <div class="schedule-column">
                    <div class="schedule-grid-wrapper">
                        <table class="schedule-grid">
                            <thead>
                                <tr>
                                    <th class="time-header-col">Time</th>
                                    <th class="date-header-col is-today">
                                        <div><?= date('D') ?></div>
                                        <div style="font-size: 1.2rem;"><?= date('d M') ?></div>
                                    </th>
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
                                        <td class="is-today">
                                            <?php if (isset($appointmentsByTime[$time])):
                                                $appointment = $appointmentsByTime[$time];
                                                $uid = htmlspecialchars($appointment['patient_uid'] ?? '');
                                                $regId = htmlspecialchars((string)($appointment['registration_id'] ?? ''));
                                            ?>
                                                <div class="appointment-card <?= strtolower(htmlspecialchars($appointment['status'])) ?>" data-regid="<?= $regId ?>">
                                                    <span class="appointment-uid"><?= $uid ?: 'Legacy' ?></span>
                                                    <?= htmlspecialchars($appointment['patient_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Column: Registrations Table -->
                <div class="registrations-column">
                    <div id="quickTable" class="table-container modern-table">
                        <table>
                            <thead>
                                <tr>
                                    <th data-key="id" class="sortable">ID <span class="sort-indicator"></span></th>
                                    <th data-key="name" class="sortable">Name <span class="sort-indicator"></span></th>
                                    <th data-key="age" class="sortable">Age</th>
                                    <th data-key="gender" class="sortable">Gender</th>
                                    <th data-key="consultation_type" class="sortable">Inquiry Type</th>
                                    <th data-key="reffered_by" class="sortable">Referred By</th>
                                    <th data-key="conditionType" class="sortable">Condition Type</th>
                                    <th data-key="status">Status</th>
                                    <th>Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inquiries)): ?>
                                    <?php foreach ($inquiries as $row): ?>
                                        <tr data-id="<?= htmlspecialchars((string) $row['patient_uid'], ENT_QUOTES, 'UTF-8') ?>">
                                            <td><?= htmlspecialchars($row['patient_uid'] ?? 'N/A') ?></td>
                                            <td class="name"><?= htmlspecialchars($row['patient_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) $row['age'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['gender'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['consultation_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['reffered_by'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($row['chief_complain'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <span class="pill <?php echo strtolower($row['status']) ?>">
                                                    <?php echo htmlspecialchars((string) $row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <select data-id="<?php echo $row['registration_id'] ?>">
                                                    <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending
                                                    </option>
                                                    <option <?php echo strtolower($row['status']) === 'consulted' ? 'selected' : '' ?>>Consulted
                                                    </option>
                                                    <option <?php echo strtolower($row['status']) === 'closed' ? 'selected' : '' ?>>Closed
                                                    </option>
                                                </select>
                                            </td>
                                            <td></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="no-data">No new Appointments today.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
    <div id="toast-container"></div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/registration.js"></script>
    <script src="../js/nav_toggle.js"></script>

    <script>
        // write code for toast-container
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.classList.add('toast', `toast-${type}`);
            toast.textContent = message;
            toastContainer.appendChild(toast);
            setTimeout(() => {
                toastContainer.removeChild(toast);
            }, 3000);
        }

        /* ------------------------------
       Status update helper (reusable)
    ------------------------------ */
        async function updateStatus(id, type, status, pillElement = null) {
            try {
                const res = await fetch('../api/update_registration_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        id,
                        type,
                        status
                    })
                });
                const json = await res.json();
                if (json.success) {
                    if (pillElement) {
                        pillElement.textContent = status;
                        pillElement.className = 'pill ' + status.toLowerCase();
                    }
                    showToast('Status updated', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    return true;
                } else {
                    showToast(json.message || 'Update failed', 'error');
                    return false;
                }
            } catch (err) {
                console.error('updateStatus error', err);
                showToast('Network error', 'error');
                return false;
            }
        }

        // --- Attach Event Listeners to Status Dropdowns ---
        // This is the missing piece to make your status updates work.
        document.querySelectorAll('#quickTable tbody select[data-id]').forEach(select => {
            select.addEventListener('change', function() {
                const id = this.dataset.id;
                // The 'type' for this table is always 'registration'
                const type = 'registration';
                const status = this.value;
                const pillElement = this.closest('tr')?.querySelector('.pill');

                updateStatus(id, type, status, pillElement);
            });
        });
    </script>

</body>

</html>