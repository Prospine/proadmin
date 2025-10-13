<?php

declare(strict_types=1);
session_start();

// -------------------------
// Dependencies & Config
// -------------------------
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!isset($_SESSION['uid'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/auth.php';
require_once '../../common/db.php';

$branchId = $_SESSION['branch_id'] ?? null;
if (!$branchId) {
    http_response_code(403);
    exit('Branch not assigned.');
}

// -------------------------
// DYNAMIC SQL & DATA FETCHING FOR PATIENTS
// -------------------------

function getPatientData($pdo, $branchId, $filters)
{
    // Base SQL query joining patients with registration to get names
    $sql = "SELECT 
                p.patient_id,
                r.patient_name,
                p.assigned_doctor,
                p.treatment_type,
                p.total_amount,
                p.advance_payment,
                p.due_amount,
                p.start_date,
                p.end_date,
                p.status
            FROM patients p
            JOIN registration r ON p.registration_id = r.registration_id";

    // Prepare WHERE clauses and parameters
    $whereClauses = ['p.branch_id = :branch_id'];
    $params = [':branch_id' => $branchId];

    // Dynamically add filters to the query
    if (!empty($filters['start_date'])) {
        $whereClauses[] = 'p.start_date >= :start_date';
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $whereClauses[] = 'p.start_date <= :end_date'; // Note: Filtering by start date within range
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['assigned_doctor'])) {
        $whereClauses[] = 'p.assigned_doctor = :assigned_doctor';
        $params[':assigned_doctor'] = $filters['assigned_doctor'];
    }
    if (!empty($filters['treatment_type'])) {
        $whereClauses[] = 'p.treatment_type = :treatment_type';
        $params[':treatment_type'] = $filters['treatment_type'];
    }
    if (!empty($filters['status'])) {
        $whereClauses[] = 'p.status = :status';
        $params[':status'] = $filters['status'];
    }

    // Combine WHERE clauses
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " ORDER BY p.start_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if this is a JavaScript fetch (AJAX) request
if (isset($_GET['fetch'])) {
    try {
        $patients = getPatientData($pdo, $branchId, $_GET);
        header('Content-Type: application/json');
        echo json_encode(['patients' => $patients]);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// For initial page load, fetch data for filters and default view
$filterOptions = [];
$patients = [];
$branchName = '';
try {
    // Get distinct values for filter dropdowns
    $filterQueries = [
        'doctors' => "SELECT DISTINCT assigned_doctor FROM patients WHERE branch_id = ? ORDER BY assigned_doctor",
        'treatment_types' => "SELECT DISTINCT treatment_type FROM patients WHERE branch_id = ? ORDER BY treatment_type",
        'statuses' => "SELECT DISTINCT status FROM patients WHERE branch_id = ? ORDER BY status"
    ];

    foreach ($filterQueries as $key => $query) {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$branchId]);
        $filterOptions[$key] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get initial data for the table
    $today = new DateTime();
    $defaultFilters = [
        'start_date' => $_GET['start_date'] ?? $today->format('Y-m-01'),
        'end_date' => $_GET['end_date'] ?? $today->format('Y-m-d')
    ];
    $patients = getPatientData($pdo, $branchId, $defaultFilters);

     // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
} catch (PDOException $e) {
    die("Error fetching initial page data: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Reports</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />

    <style>
        .filter-bar {
            width: auto;
            padding: 10px !important;
            /* height: 50px; */
            margin-bottom: 15px;
        }

        @media (max-width: 1024px) {
            .main {
                margin: 0;
            }

            .filter-bar {
                /* margin: 0; */
                display: flex;
                width: auto;
                padding: 10px !important;
            }

            .filter-bar input[type="date"],
            .filter-bar select {
                min-width: 175px !important;
                max-width: 175px !important;
            }

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
                <a href="reports.php" class="active">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"> <?php echo $branchName; ?> Branch </div>
            <div class="icon-btn" id="theme-toggle"> <i id="theme-icon" class="fa-solid fa-moon"></i> </div>
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
                <li><a href="#">You have 3 new appointments.</a></li>
                <li><a href="#">Dr. Smith is available for consultation.</a></li>
                <li><a href="#">New patient registered: John Doe.</a></li>
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="dashboard-container">
            <div class="top-bar">
                <h2>Patient Reports</h2>
                <div class="toggle-container">
                    <button class="toggle-btn" onclick="window.location.href = 'reports.php';">Tests Report</button>
                    <button class="toggle-btn" onclick="window.location.href = 'clinic_reports.php';">Registration Reports</button>
                    <button class="toggle-btn active">Patient Reports</button>
                    <button class="toggle-btn" onclick="window.location.href = 'inquiry_reports.php';">Inquiry Reports</button>
                </div>
            </div>

            <div class="filter-bar">
                <form id="filter-form">
                    <div>
                        <label for="start_date">From:</label>
                        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($defaultFilters['start_date']) ?>">
                        <label for="end_date">To:</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($defaultFilters['end_date']) ?>">
                    </div>
                    <div>
                        <select name="assigned_doctor" id="assigned_doctor">
                            <option value="">All Doctors</option>
                            <?php foreach ($filterOptions['doctors'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="treatment_type" id="treatment_type">
                            <option value="">All Treatment Types</option>
                            <?php foreach ($filterOptions['treatment_types'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <?php foreach ($filterOptions['statuses'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="apply-filter-btn" class="btn-filter">
                            <i class="fa-solid fa-filter"></i> Apply Filter
                        </button>
                        <button type="button" class="btn-filter" onclick="window.location.href='patient_reports.php'">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </button>
                    </div>
                </form>
            </div>

            <div id="filter-status-message" class="filter-status" style="display: none;"></div>

            <div class="table-container modern-table">
                <div id="loader" class="loader" style="display: none;">Loading...</div>
                <table id="patientReportTable">
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th>Assigned Doctor</th>
                            <th>Treatment</th>
                            <th>Total Amt</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="report-tbody">
                        <?php if (empty($patients)) : ?>
                            <tr>
                                <td colspan="10" style="text-align: center;">No patient records found for the selected period.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($patients as $patient) : ?>
                                <tr>
                                    <td data-label="Patient ID"><?= htmlspecialchars((string)$patient['patient_id']) ?></td>
                                    <td data-label="Patient Name"><?= htmlspecialchars($patient['patient_name']) ?></td>
                                    <td data-label="Assigned Doctor"><?= htmlspecialchars($patient['assigned_doctor']) ?></td>
                                    <td data-label="Treatment"><?= htmlspecialchars(ucfirst($patient['treatment_type'])) ?></td>
                                    <td data-label="Total Amt"><?= number_format((float)$patient['total_amount'], 2) ?></td>
                                    <td data-label="Paid"><?= number_format((float)$patient['advance_payment'], 2) ?></td>
                                    <td data-label="Due"><?= number_format((float)$patient['due_amount'], 2) ?></td>
                                    <td data-label="Start Date"><?= htmlspecialchars($patient['start_date']) ?></td>
                                    <td data-label="End Date"><?= htmlspecialchars($patient['end_date']) ?></td>
                                    <td data-label="Status"><span class="status-pill status-<?= htmlspecialchars(strtolower($patient['status'])) ?>"><?= htmlspecialchars(ucfirst($patient['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/patient_reports.js"></script>
    <script src="../js/nav_toggle.js"></script>

</body>

</html>