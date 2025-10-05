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
// DYNAMIC SQL & DATA FETCHING FOR REGISTRATIONS
// -------------------------

function getRegistrationData($pdo, $branchId, $filters)
{
    // Base SQL query for the registration table
    $sql = "SELECT 
                r.appointment_date, r.patient_name, r.age, r.gender,
                r.chief_complain, r.referralSource, r.reffered_by, r.consultation_type,
                r.consultation_amount, r.payment_method, r.status
            FROM registration r";

    // Prepare WHERE clauses and parameters
    $whereClauses = ['r.branch_id = :branch_id'];
    $params = [':branch_id' => $branchId];

    // Dynamically add filters to the query
    if (!empty($filters['start_date'])) {
        $whereClauses[] = 'r.appointment_date >= :start_date';
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $whereClauses[] = 'r.appointment_date <= :end_date';
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['chief_complain'])) {
        $whereClauses[] = 'r.chief_complain = :chief_complain';
        $params[':chief_complain'] = $filters['chief_complain'];
    }
    if (!empty($filters['referralSource'])) {
        $whereClauses[] = 'r.referralSource = :referralSource';
        $params[':referralSource'] = $filters['referralSource'];
    }
    if (!empty($filters['reffered_by'])) {
        $whereClauses[] = 'r.reffered_by = :reffered_by';
        $params[':reffered_by'] = $filters['reffered_by'];
    }
    if (!empty($filters['consultation_type'])) {
        $whereClauses[] = 'r.consultation_type = :consultation_type';
        $params[':consultation_type'] = $filters['consultation_type'];
    }
    if (!empty($filters['status'])) {
        $whereClauses[] = 'r.status = :status';
        $params[':status'] = $filters['status'];
    }

    // Combine WHERE clauses
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " ORDER BY r.appointment_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if this is a JavaScript fetch (AJAX) request
if (isset($_GET['fetch'])) {
    try {
        $registrations = getRegistrationData($pdo, $branchId, $_GET);
        header('Content-Type: application/json');
        echo json_encode(['registrations' => $registrations]);
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
$registrations = [];
$branchName = '';
try {
    // Get distinct values for filter dropdowns
    $filterQueries = [
        'complains' => "SELECT DISTINCT chief_complain FROM registration WHERE branch_id = ? ORDER BY chief_complain",
        'sources' => "SELECT DISTINCT referralSource FROM registration WHERE branch_id = ? ORDER BY referralSource",
        'consultation_types' => "SELECT DISTINCT consultation_type FROM registration WHERE branch_id = ? ORDER BY consultation_type",
        'reffered_by' => "SELECT DISTINCT reffered_by FROM registration WHERE branch_id = ? ORDER BY reffered_by",
        'statuses' => "SELECT DISTINCT status FROM registration WHERE branch_id = ? ORDER BY status"
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
    $registrations = getRegistrationData($pdo, $branchId, $defaultFilters);

    // Get branch name
    $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmtBranch->execute(['branch_id' => $branchId]);
    $branchName = $stmtBranch->fetchColumn() ?? '';
} catch (PDOException $e) {
    die("Error fetching initial page data: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Reports</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
</head>

<body>
    <header>
        <div class="logo-container"> <img src="../../assets/images/image.png" alt="Pro Physio Logo" class="logo" />
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
                <h2>Registration Reports</h2>
                <div class="toggle-container">
                    <button class="toggle-btn" onclick="window.location.href = 'reports.php';">Tests Report</button>
                    <button class="toggle-btn active">Registration Reports</button>
                    <button class="toggle-btn" onclick="window.location.href = 'patient_reports.php';">Patient Reports</button>
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
                        <select name="chief_complain" id="chief_complain">
                            <option value="">All Conditions</option>
                            <?php foreach ($filterOptions['complains'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $option))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="referralSource" id="referralSource">
                            <option value="">All Sources</option>
                            <?php foreach ($filterOptions['sources'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $option))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="reffered_by" id="reffered_by">
                            <option value="">All Referrals</option>
                            <?php foreach ($filterOptions['reffered_by'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', ($option)))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="consultation_type" id="consultation_type">
                            <option value="">All Consultation Types</option>
                            <?php foreach ($filterOptions['consultation_types'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $option))) ?></option>
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
                        <button type="button" class="btn-filter" onclick="window.location.href='clinic_reports.php'">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </button>
                    </div>
                </form>
            </div>

            <div id="filter-status-message" class="filter-status" style="display: none;"></div>

            <div class="table-container modern-table">
                <div id="loader" class="loader" style="display: none;">Loading...</div>
                <table id="registrationReportTable">
                    <thead>
                        <tr>
                            <th>Appt. Date</th>
                            <th>Patient Name</th>
                            <th>Age</th>
                            <!-- <th>Gender</th> -->
                            <th>Condition</th>
                            <th>Source</th>
                            <th>Referred By</th>
                            <th>Consultation</th>
                            <th>Amount</th>
                            <th>Pay Mode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="report-tbody">
                        <?php if (empty($registrations)) : ?>
                            <tr>
                                <td colspan="10" style="text-align: center;">No registration records found for the default period.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($registrations as $reg) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($reg['appointment_date']) ?></td>
                                    <td><?= htmlspecialchars($reg['patient_name']) ?></td>
                                    <td><?= htmlspecialchars((string)$reg['age']) ?></td>
                                    <!-- <td><?= htmlspecialchars($reg['gender']) ?></td> -->
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $reg['chief_complain']))) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $reg['referralSource']))) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $reg['reffered_by']))) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $reg['consultation_type']))) ?></td>
                                    <td><?= number_format((float)$reg['consultation_amount'], 2) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($reg['payment_method'])) ?></td>
                                    <td><span class="status-pill status-<?= htmlspecialchars(strtolower($reg['status'])) ?>"><?= htmlspecialchars($reg['status']) ?></span></td>
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
    <script src="../js/clinic_reports.js"></script>
</body>

</html>