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

   // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
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
    <link rel="stylesheet" href="../css/attendance.css">

    <style>
        @media (max-width: 1024px) {

            .filter-bar {
                /* margin: 0; */
                display: flex;
                justify-content: normal !important;
                width: auto;
            }

            .sort-btn {
                margin: 0;
            }

            .search-container {
                width: auto;
            }

            .search-container input {
                width: 300px;
            }

            .filter-options select {
                margin: 0;
            }

            .drawer-panel {
                margin-top: 20px;
                margin-right: 10px;
                border-radius: 30px;
                height: 90vh;
            }
        }
    </style>
</head>

<body data-page-date="<?php echo htmlspecialchars($selectedDate); ?>">
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
        <div class="hamburger-menu" id="hamburger-menu">
            <i class="fa-solid fa-bars"></i>
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

                <!-- UNIFIED CONTROL BAR -->
                <div class="filter-bar">
                    <div class="search-container">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by Patient Name or ID...">
                    </div>
                    <div class="filter-options">
                        <select id="treatmentFilter">
                            <option value="">All Treatments</option>
                            <option value="daily">Daily</option>
                            <option value="advance">Advance</option>
                            <option value="package">Package</option>
                        </select>
                    </div>

                    <button id="sortDirectionBtn" class="sort-btn" title="Toggle Sort Direction">
                        <i class="fa-solid fa-sort"></i>
                    </button>

                    <form method="GET" action="" class="date-picker-form" id="formm">
                        <input type="date" id="date-picker" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                        <button type="submit" class="btn-go">Go</button>
                    </form>
                </div>
            </div>


            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th data-key="id" class="sortable">Patient ID</th>
                            <th data-key="name" class="sortable">Patient Name</th>
                            <th data-key="treatment" class="sortable">Treatment Type</th>
                            <th data-key="progress">Progress (Days)</th>
                            <th data-key="remarks">Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <?php if (!empty($attendance_records)) : ?>
                            <?php foreach ($attendance_records as $row) : ?>
                                <tr>
                                    <td data-label="Id"><?php echo htmlspecialchars((string) $row['patient_id']); ?></td>
                                    <td data-label="Name"><?php echo htmlspecialchars((string) $row['patient_name']); ?></td>
                                    <td data-label="Treatment Type"><?php echo htmlspecialchars(ucfirst((string) $row['treatment_type'])); ?></td>

                                    <td data-label="Progress(Days)">
                                        <?php
                                        // Display progress only if treatment_days is set
                                        if (!empty($row['treatment_days']) && (int)$row['treatment_days'] > 0) {
                                            echo htmlspecialchars((string) $row['attendance_count']) . ' / ' . htmlspecialchars((string) $row['treatment_days']);
                                        } else {
                                            echo htmlspecialchars((string) $row['attendance_count']) . ' / -'; // For daily types without a set total
                                        }
                                        ?>
                                    </td>

                                    <td data-label="Remarks"><?php echo htmlspecialchars((string) $row['remarks']); ?></td>
                                    <td data-label="Action">
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
    <script src="../js/attendance.js"></script>
    <script src="../js/nav_toggle.js"></script>

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

                    const response = await fetch(`../api/get_attendance_history.php?id=${encodeURIComponent(patientId)}`);
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