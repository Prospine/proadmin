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

// Determine the date to show. Default to today if no date is selected.
$selectedDate = $_GET['date'] ?? date('Y-m-d');

try {
    $stmt = $pdo->prepare("
        SELECT
            a.attendance_id,
            a.attendance_date,
            a.remarks,
            r.patient_name,
            p.patient_id,
            p.treatment_type,
            p.treatment_days, -- ADDED: Get the total treatment days
            (
                SELECT COUNT(*)
                FROM attendance
                WHERE patient_id = p.patient_id
            ) AS attendance_count -- ADDED: Subquery to count total attendance
        FROM
            attendance a
        JOIN
            patients p ON a.patient_id = p.patient_id
        JOIN
            registration r ON p.registration_id = r.registration_id
        WHERE
            a.attendance_date = :attendance_date
            AND p.branch_id = :branch_id
        ORDER BY
            a.created_at DESC
    ");
    $stmt->execute([
        ':attendance_date' => $selectedDate,
        ':branch_id' => $branchId
    ]);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch branch name
    $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmtBranch->execute(['branch_id' => $branchId]);
    $branchName = $stmtBranch->fetch()['branch_name'] ?? '';
} catch (PDOException $e) {
    die("Error fetching attendance data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Attendance</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">

    <style>
        /* ---------- small page styles ---------- */
        .date-picker-form {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 220px;
        }

        .date-picker-form label {
            font-weight: 500;
        }

        .date-picker-form input[type="date"] {
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 16px;
            font-size: 1rem;
            box-shadow: none;
        }

        .dark-mode .date-picker-form input[type="date"] {
            background-color: #333;
            border-color: #555;
            color: #fff;
        }

        /* ---------- Drawer & Calendar ---------- */
        .drawer-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: none;
            /* toggled to flex when opening */
            justify-content: flex-end;
            z-index: 1200;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .drawer-panel {
            background: #fff;
            width: 600px;
            max-width: 95%;
            height: 100%;
            transform: translateX(100%);
            /* hidden */
            transition: transform 0.32s cubic-bezier(.2, .9, .2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: -18px 24px 60px rgba(11, 22, 40, 0.18);
        }

        .drawer-panel.is-open {
            transform: translateX(0);
        }

        .drawer-header {
            padding: 16px 20px;
            background: #fafafa;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .drawer-header h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            color: #111827;
        }

        .drawer-close-btn {
            background: #0f172a;
            color: #fff;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .drawer-body {
            padding: 18px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Calendar header (month + nav) */
        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .month-btn {
            background: transparent;
            border: 1px solid #e6e6e6;
            padding: 6px 8px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: #000;
        }

        .month-label {
            font-weight: 700;
            font-size: 0.95rem;
        }

        /* Calendar grid */
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            align-items: start;
        }

        .calendar .day-head {
            font-size: 0.8rem;
            text-align: center;
            color: #6b7280;
            font-weight: 600;
        }

        .date-cell {
            height: 64px;
            /* a little bigger */
            min-height: 64px;
            border-radius: 8px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: transform 0.12s ease, box-shadow 0.12s ease;
            user-select: none;
            position: relative;
            padding: 6px;
            font-weight: 600;
            color: #0f172a;
        }

        .date-cell:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(13, 27, 41, 0.06);
        }

        .date-cell.empty {
            background: transparent;
            cursor: default;
            box-shadow: none;
            transform: none;
        }

        .date-cell .date-number {
            font-size: 1rem;
            line-height: 1;
        }

        .date-cell .date-sub {
            font-size: 0.72rem;
            color: #6b7280;
            margin-top: 4px;
            font-weight: 500;
        }

        .date-cell.attended {
            background: linear-gradient(180deg, #ecfdf5, #bbf7d0);
            border: 1px solid rgba(34, 197, 94, 0.18);
            color: #064e3b;
        }

        .date-cell.selected {
            outline: 3px solid rgba(37, 99, 235, 0.16);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.12);
        }

        /* Details box */
        #attendance-date-details {
            margin-top: 6px;
            padding: 14px;
            border-radius: 8px;
            background: #fbfdff;
            border: 1px solid #eef2ff;
            display: none;
        }

        #attendance-date-details h3 {
            margin: 0 0 8px 0;
            font-size: 1rem;
        }

        #attendance-date-details p {
            margin: 0;
            color: #374151;
            font-size: 0.95rem;
        }

        /* small responsive tweak */
        @media (max-width: 640px) {
            .drawer-panel {
                width: 95%;
            }

            .date-cell {
                height: 56px;
            }
        }

        /* dark mode support */
        body.dark .drawer-panel {
            background: #3a3a3aff;
            color: #e6eef8;
        }

        body.dark .drawer-header {
            background: #071126;
            border-bottom-color: #09203a;

        }

        body.dark .drawer-header h2 {
            color: #fff;
        }

        body.dark .date-cell {
            background: #071826;
            color: #cfe7ff;
        }

        body.dark .date-cell.empty {
            background: transparent;
        }

        body.dark .date-cell.attended {
            background: linear-gradient(180deg, #042e1a, #0b5130);
            color: #d2f8e2;
            border-color: rgba(34, 197, 94, 0.08);
        }

        body.dark #attendance-date-details {
            background: #061925;
            border-color: #083046;
            color: #d7e9ff;
        }

        body.dark #attendance-date-details p{
            color: #ddd;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="../../assets/images/image.png" alt="Pro Physio Logo" class="logo" />
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a href="registration.php">Registration</a>
                <a href="patients.php">Patients</a>
                <a href="appointments.php">Appointments</a>
                <a href="billing.php">Billing</a>
                <a href="attendance.php" class="active">Attendance</a>
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"><?php echo htmlspecialchars($branchName, ENT_QUOTES, 'UTF-8'); ?> Branch</div>
            <div class="icon-btn" id="theme-toggle">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </div>
            <div class="icon-btn icon-btn2" title="Notifications" onclick="openNotif()">🔔</div>
            <div class="profile" onclick="openForm()">S</div>
        </div>
    </header>

    <div class="menu" id="myMenu"> <span class="closebtn" onclick="closeForm()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="#">Profile</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
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
                <h2>Attendance for <?php echo htmlspecialchars(date('d M Y', strtotime($selectedDate))); ?></h2>
                <form method="GET" action="" class="date-picker-form">
                    <label for="date-picker">View Another Date:</label>
                    <input type="date" id="date-picker" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>" onchange="this.form.submit()">
                </form>
            </div>

            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Treatment Type</th>
                            <th>Progress (Days)</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance_records)) : ?>
                            <?php foreach ($attendance_records as $row) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['patient_id']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['patient_name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst((string) $row['treatment_type'])); ?></td>

                                    <td>
                                        <?php
                                        // Display progress only if treatment_days is set
                                        if (!empty($row['treatment_days']) && (int)$row['treatment_days'] > 0) {
                                            echo htmlspecialchars((string) $row['attendance_count']) . ' / ' . htmlspecialchars((string) $row['treatment_days']);
                                        } else {
                                            echo htmlspecialchars((string) $row['attendance_count']) . ' / -'; // For daily types without a set total
                                        }
                                        ?>
                                    </td>

                                    <td><?php echo htmlspecialchars((string) $row['remarks']); ?></td>
                                    <td>
                                        <button class="action-btn view-attendance-btn" data-id="<?php echo (int) $row['patient_id']; ?>">Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="no-data">No attendance records found for this date.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Drawer overlay & panel -->
    <div class="drawer-overlay" id="attendance-drawer-overlay" aria-hidden="true">
        <div class="drawer-panel" id="attendance-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="attendance-drawer-title">
            <div class="drawer-header">
                <h2 id="attendance-drawer-title">Attendance Calendar</h2>
                <button class="drawer-close-btn" id="attendance-drawer-close" aria-label="Close drawer">&times;</button>
            </div>

            <div class="drawer-body" id="attendance-drawer-body">
                <!-- calendar header -->
                <div class="calendar-header">
                    <div class="month-nav">
                        <button class="month-btn" id="month-prev" title="Previous month">◀</button>
                        <div class="month-label" id="attendance-month-label">Month</div>
                        <button class="month-btn" id="month-next" title="Next month">▶</button>
                    </div>
                    <div style="font-size:0.9rem; color:#6b7280;">Attended dates are highlighted</div>
                </div>

                <!-- calendar -->
                <div class="calendar" id="attendance-calendar" aria-hidden="false">
                    <!-- dynamic content injected here -->
                </div>

                <!-- date details -->
                <div id="attendance-date-details" aria-live="polite">
                    <h3 id="attendance-details-date">Date Details</h3>
                    <p id="attendance-details-text">Click a date to see details here.</p>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/dashboard.js"></script>
    <script src="../js/theme.js"></script>

    <script>
        (function() {
            // defaults from PHP
            const DEFAULT_PAGE_DATE = <?php echo json_encode($selectedDate); ?>; // 'YYYY-MM-DD'

            // DOM references
            const overlay = document.getElementById('attendance-drawer-overlay');
            const panel = document.getElementById('attendance-drawer-panel');
            const titleEl = document.getElementById('attendance-drawer-title');
            const closeBtn = document.getElementById('attendance-drawer-close');
            const calendarEl = document.getElementById('attendance-calendar');
            const monthLabel = document.getElementById('attendance-month-label');
            const prevBtn = document.getElementById('month-prev');
            const nextBtn = document.getElementById('month-next');
            const detailsBox = document.getElementById('attendance-date-details');
            const detailsDateEl = document.getElementById('attendance-details-date');
            const detailsTextEl = document.getElementById('attendance-details-text');

            let attendanceSet = new Set(); // YYYY-MM-DD strings
            let attendanceMap = {}; // optional: { 'YYYY-MM-DD': {...remarks...} }
            let currentYear, currentMonth; // numeric (month 0-11)

            // Helpers
            const formatMonthLabel = (y, m) => {
                const d = new Date(y, m, 1);
                return d.toLocaleString('default', {
                    month: 'long',
                    year: 'numeric'
                });
            };

            const pad = (n) => (n < 10 ? '0' + n : '' + n);

            const isoDate = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;

            const openDrawer = () => {
                overlay.style.display = 'flex';
                // force reflow then open
                panel.offsetHeight;
                panel.classList.add('is-open');
                overlay.setAttribute('aria-hidden', 'false');
            };

            const closeDrawer = () => {
                panel.classList.remove('is-open');
                overlay.setAttribute('aria-hidden', 'true');
                // wait for transition then hide overlay
                setTimeout(() => {
                    overlay.style.display = 'none';
                    // reset details
                    detailsBox.style.display = 'none';
                    detailsTextEl.textContent = 'Click a date to see details here.';
                }, 320);
            };

            // Render calendar for given year/month (month: 0-11)
            const renderCalendar = (year, month) => {
                calendarEl.innerHTML = ''; // clear
                monthLabel.textContent = formatMonthLabel(year, month);

                // Day headers
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                for (let i = 0; i < 7; i++) {
                    const dh = document.createElement('div');
                    dh.className = 'day-head';
                    dh.textContent = days[i];
                    calendarEl.appendChild(dh);
                }

                // First day index and last day number
                const first = new Date(year, month, 1);
                const firstIndex = first.getDay();
                const lastDay = new Date(year, month + 1, 0).getDate();

                // fill empty cells before first day
                for (let i = 0; i < firstIndex; i++) {
                    const empty = document.createElement('div');
                    empty.className = 'date-cell empty';
                    calendarEl.appendChild(empty);
                }

                // create each date cell
                for (let day = 1; day <= lastDay; day++) {
                    const cell = document.createElement('div');
                    cell.className = 'date-cell';
                    const dateStr = isoDate(year, month, day);
                    cell.dataset.date = dateStr;

                    // the number
                    const num = document.createElement('div');
                    num.className = 'date-number';
                    num.textContent = day;
                    cell.appendChild(num);

                    // small subtext (weekday short)
                    const sub = document.createElement('div');
                    sub.className = 'date-sub';
                    const weekday = new Date(year, month, day).toLocaleString('default', {
                        weekday: 'short'
                    });
                    sub.textContent = weekday;
                    cell.appendChild(sub);

                    // mark attended if present
                    if (attendanceSet.has(dateStr)) {
                        cell.classList.add('attended');
                    }

                    // click handler for details
                    cell.addEventListener('click', () => {
                        // ignore clicks on empty cells
                        if (cell.classList.contains('empty')) return;

                        // deselect previous selection
                        const prev = calendarEl.querySelector('.date-cell.selected');
                        if (prev) prev.classList.remove('selected');
                        cell.classList.add('selected');

                        // show details
                        detailsBox.style.display = 'block';
                        detailsDateEl.textContent = new Date(year, month, day).toLocaleDateString();
                        if (attendanceMap && attendanceMap[dateStr]) {
                            // if API returned a map for more details
                            detailsTextEl.textContent = `Status: Present\nRemarks: ${attendanceMap[dateStr].remarks || attendanceMap[dateStr].note || '—'}`;
                        } else {
                            if (attendanceSet.has(dateStr)) {
                                detailsTextEl.textContent = 'Status: Present — attendance recorded for this date.';
                            } else {
                                detailsTextEl.textContent = 'Status: Absent — no attendance record for this date.';
                            }
                        }
                    });

                    calendarEl.appendChild(cell);
                }
            };

            // Fetch attendance history for a patient and open drawer
            const openDrawerWithHistory = async (patientId) => {
                try {
                    // loading
                    calendarEl.innerHTML = '<div style="grid-column:1/-1;padding:12px;color:#6b7280;">Loading attendance history…</div>';
                    detailsBox.style.display = 'none';
                    titleEl.textContent = 'Loading…';
                    openDrawer();

                    const response = await fetch(`/proadmin/admin/reception/api/get_attendance_history.php?id=${encodeURIComponent(patientId)}`);
                    if (!response.ok) throw new Error('Network response was not ok');

                    const data = await response.json();

                    if (!data.success) {
                        calendarEl.innerHTML = `<div style="grid-column:1/-1;padding:12px;color:#b91c1c;">Error: ${data.message || 'Unable to load history'}</div>`;
                        titleEl.textContent = 'Attendance Calendar';
                        return;
                    }

                    // build attendance set & optional map
                    const dates = Array.isArray(data.attendance_dates) ? data.attendance_dates : [];
                    attendanceSet = new Set(dates);
                    attendanceMap = data.attendance_map || {}; // optional: if your API returns extra info keyed by date

                    // set title
                    titleEl.textContent = `History — ${data.patient_name || ''}`;

                    // initial month: prefer the page-selected date's month; fallback to current month
                    const [prefY, prefM] = (DEFAULT_PAGE_DATE || new Date().toISOString().slice(0, 10)).split('-').map(Number);
                    currentYear = prefY || new Date().getFullYear();
                    currentMonth = (prefM ? prefM - 1 : new Date().getMonth());

                    renderCalendar(currentYear, currentMonth);

                } catch (err) {
                    console.error('Attendance drawer error', err);
                    calendarEl.innerHTML = `<div style="grid-column:1/-1;padding:12px;color:#b91c1c;">Could not fetch attendance history.</div>`;
                    titleEl.textContent = 'Attendance Calendar';
                }
            };

            // wire up view buttons in the table
            document.querySelectorAll('.view-attendance-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const pid = this.dataset.id;
                    openDrawerWithHistory(pid);
                });
            });

            // month nav
            prevBtn.addEventListener('click', () => {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar(currentYear, currentMonth);
            });
            nextBtn.addEventListener('click', () => {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar(currentYear, currentMonth);
            });

            // close handlers
            closeBtn.addEventListener('click', closeDrawer);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) closeDrawer();
            });
            // esc key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && overlay.style.display === 'flex') closeDrawer();
            });

            // initial hidden state of details box
            detailsBox.style.display = 'none';
        })();
    </script>
</body>

</html>