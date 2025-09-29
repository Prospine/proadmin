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
// DYNAMIC SQL & DATA FETCHING
// -------------------------

function getTestData($pdo, $branchId, $filters)
{
    // Base SQL query
    $sql = "SELECT 
                t.assigned_test_date, t.patient_name, t.test_name, t.referred_by,
                t.test_done_by, t.total_amount, t.advance_amount, t.due_amount, t.payment_status
            FROM tests t";

    // Prepare WHERE clauses and parameters
    $whereClauses = ['t.branch_id = :branch_id'];
    $params = [':branch_id' => $branchId];

    // Dynamically add filters to the query
    if (!empty($filters['start_date'])) {
        $whereClauses[] = 't.assigned_test_date >= :start_date';
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $whereClauses[] = 't.assigned_test_date <= :end_date';
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['test_name'])) {
        $whereClauses[] = 't.test_name = :test_name';
        $params[':test_name'] = $filters['test_name'];
    }
    if (!empty($filters['referred_by'])) {
        $whereClauses[] = 't.referred_by = :referred_by';
        $params[':referred_by'] = $filters['referred_by'];
    }
    if (!empty($filters['test_done_by'])) {
        $whereClauses[] = 't.test_done_by = :test_done_by';
        $params[':test_done_by'] = $filters['test_done_by'];
    }
    if (!empty($filters['payment_status'])) {
        $whereClauses[] = 't.payment_status = :payment_status';
        $params[':payment_status'] = $filters['payment_status'];
    }

    // Combine WHERE clauses
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " ORDER BY t.assigned_test_date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if this is a JavaScript fetch (AJAX) request
if (isset($_GET['fetch'])) {
    try {
        $tests = getTestData($pdo, $branchId, $_GET);
        header('Content-Type: application/json');
        echo json_encode(['tests' => $tests]);
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
$tests = [];
$branchName = '';
try {
    // Get distinct values for filter dropdowns
    $filterQueries = [
        'test_names' => "SELECT DISTINCT test_name FROM tests WHERE branch_id = ? ORDER BY test_name",
        'referred_by_list' => "SELECT DISTINCT referred_by FROM tests WHERE branch_id = ? AND referred_by IS NOT NULL AND referred_by != '' ORDER BY referred_by",
        'test_done_by_list' => "SELECT DISTINCT test_done_by FROM tests WHERE branch_id = ? ORDER BY test_done_by"
    ];

    foreach ($filterQueries as $key => $query) {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$branchId]);
        $filterOptions[$key] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    $filterOptions['payment_statuses'] = ['pending', 'partial', 'paid', 'cancelled'];

    // Get initial data for the table
    $today = new DateTime();
    $defaultFilters = [
        'start_date' => $_GET['start_date'] ?? $today->format('Y-m-01'),
        'end_date' => $_GET['end_date'] ?? $today->format('Y-m-d')
    ];
    $tests = getTestData($pdo, $branchId, $defaultFilters);

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
    <title>Test Reports</title>
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
                <a href="patients.php">Patients</a>
                <a href="appointments.php">Appointments</a>
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
                <h2>Test Reports</h2>
                <div class="toggle-container">
                    <button class="toggle-btn active">Tests Report</button>
                    <button class="toggle-btn" onclick="window.location.href = 'clinic_reports.php';">Registration Reports</button>
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
                        <select name="test_name" id="test_name">
                            <option value="">All Test Names</option>
                            <?php foreach ($filterOptions['test_names'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="referred_by" id="referred_by">
                            <option value="">All Referrers</option>
                            <?php foreach ($filterOptions['referred_by_list'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="test_done_by" id="test_done_by">
                            <option value="">All Performers</option>
                            <?php foreach ($filterOptions['test_done_by_list'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="payment_status" id="payment_status">
                            <option value="">All Payment Statuses</option>
                            <?php foreach ($filterOptions['payment_statuses'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="apply-filter-btn" class="btn-filter">
                            <i class="fa-solid fa-filter"></i> Apply Filter
                        </button>
                        <button type="button" id="apply-filter-btn" class="btn-filter" onclick="window.location.href='reports.php'">
                            <i class="fa-solid fa-rotate-right"></i>Reset
                        </button>
                    </div>
                </form>
            </div>

        </div>
        <div id="filter-status-message" class="filter-status" style="display: none;"></div>

        <div class="table-container modern-table">
            <div id="loader" class="loader" style="display: none;">Loading...</div>
            <table id="testsReportTable">
                <thead>
                    <tr>
                        <th>Test Date</th>
                        <th>Patient Name</th>
                        <th>Test Name</th>
                        <th>Referred By</th>
                        <th>Performed By</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                    <?php if (empty($tests)) : ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No test records found for the default period.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($tests as $test) : ?>
                            <tr>
                                <td><?= htmlspecialchars($test['assigned_test_date']) ?></td>
                                <td><?= htmlspecialchars($test['patient_name']) ?></td>
                                <td><?= htmlspecialchars(strtoupper(str_replace('_', ' ', (string) $test['test_name']))) ?></td>
                                <td><?= htmlspecialchars($test['referred_by']) ?></td>
                                <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $test['test_done_by']))) ?></td>
                                <td><?= number_format((float)$test['total_amount'], 2) ?></td>
                                <td><?= number_format((float)$test['advance_amount'], 2) ?></td>
                                <td><?= number_format((float)$test['due_amount'], 2) ?></td>
                                <td><span class="status-pill status-<?= htmlspecialchars(strtolower($test['payment_status'])) ?>"><?= ucfirst(htmlspecialchars($test['payment_status'])) ?></span></td>
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
    <script src="../js/reports.js"></script>
</body>

</html>