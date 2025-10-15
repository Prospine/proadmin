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
// DYNAMIC SQL & DATA FETCHING FOR INQUIRIES
// -------------------------

function getInquiryData($pdo, $branchId, $filters)
{
    // Base SQL query for the quick_inquiry table
    $sql = "SELECT 
                i.created_at, i.name, i.age, i.gender, i.referralSource,
                i.chief_complain, i.phone_number, i.status
            FROM quick_inquiry i";

    // Prepare WHERE clauses and parameters
    $whereClauses = ['i.branch_id = :branch_id'];
    $params = [':branch_id' => $branchId];

    // Dynamically add filters to the query
    if (!empty($filters['start_date'])) {
        $whereClauses[] = 'DATE(i.created_at) >= :start_date';
        $params[':start_date'] = $filters['start_date'];
    }
    if (!empty($filters['end_date'])) {
        $whereClauses[] = 'DATE(i.created_at) <= :end_date';
        $params[':end_date'] = $filters['end_date'];
    }
    if (!empty($filters['referralSource'])) {
        $whereClauses[] = 'i.referralSource = :referralSource';
        $params[':referralSource'] = $filters['referralSource'];
    }
    if (!empty($filters['chief_complain'])) {
        $whereClauses[] = 'i.chief_complain = :chief_complain';
        $params[':chief_complain'] = $filters['chief_complain'];
    }
    if (!empty($filters['status'])) {
        $whereClauses[] = 'i.status = :status';
        $params[':status'] = $filters['status'];
    }

    // Combine WHERE clauses
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $sql .= " ORDER BY i.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if this is a JavaScript fetch (AJAX) request
if (isset($_GET['fetch'])) {
    try {
        $inquiries = getInquiryData($pdo, $branchId, $_GET);
        header('Content-Type: application/json');
        echo json_encode(['inquiries' => $inquiries]);
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
$inquiries = [];
$branchName = '';
try {
    // Get distinct values for filter dropdowns
    $filterQueries = [
        'sources' => "SELECT DISTINCT referralSource FROM quick_inquiry WHERE branch_id = ? ORDER BY referralSource",
        'complains' => "SELECT DISTINCT chief_complain FROM quick_inquiry WHERE branch_id = ? ORDER BY chief_complain",
        'statuses' => "SELECT DISTINCT status FROM quick_inquiry WHERE branch_id = ? ORDER BY status"
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
    $inquiries = getInquiryData($pdo, $branchId, $defaultFilters);

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
    <title>Inquiry Reports</title>
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
            <div class="profile" onclick="openForm()">R</div>
        </div>

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
                <h2>Inquiry Reports</h2>
                <div class="toggle-container">
                    <button class="toggle-btn" onclick="window.location.href = 'reports.php';">Tests Report</button>
                    <button class="toggle-btn" onclick="window.location.href = 'clinic_reports.php';">Registration Reports</button>
                    <button class="toggle-btn" onclick="window.location.href = 'patient_reports.php';">Patient Reports</button>
                    <button class="toggle-btn active">Inquiry Reports</button>
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
                        <select name="referralSource" id="referralSource">
                            <option value="">All Sources</option>
                            <?php foreach ($filterOptions['sources'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $option))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="chief_complain" id="chief_complain">
                            <option value="">All Conditions</option>
                            <?php foreach ($filterOptions['complains'] as $option) : ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $option))) ?></option>
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
                        <button type="button" class="btn-filter" onclick="window.location.href='inquiry_reports.php'">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </button>
                    </div>
                </form>
            </div>

        </div>
        <div id="filter-status-message" class="filter-status" style="display: none;"></div>

        <div class="table-container modern-table">
            <div id="loader" class="loader" style="display: none;">Loading...</div>
            <table id="inquiryReportTable">
                <thead>
                    <tr>
                        <th>Inquiry Date</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Source</th>
                        <th>Condition</th>
                        <th>Phone</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="report-tbody">
                    <?php if (empty($inquiries)) : ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No inquiry records found for the selected period.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($inquiries as $inquiry) : ?>
                            <tr>
                                <td data-label="Inquiry Date"><?= htmlspecialchars(date('Y-m-d', strtotime($inquiry['created_at']))) ?></td>
                                <td data-label="Name"><?= htmlspecialchars($inquiry['name']) ?></td>
                                <td data-label="Age"><?= htmlspecialchars((string)$inquiry['age']) ?></td>
                                <td data-label="Gender"><?= htmlspecialchars($inquiry['gender']) ?></td>
                                <td data-label="Source"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $inquiry['referralSource']))) ?></td>
                                <td data-label="Condition"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $inquiry['chief_complain']))) ?></td>
                                <td data-label="Phone"><?= htmlspecialchars($inquiry['phone_number']) ?></td>
                                <td data-label="Status"><span class="status-pill status-<?= htmlspecialchars(strtolower($inquiry['status'])) ?>"><?= htmlspecialchars(ucfirst($inquiry['status'])) ?></span></td>
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
    <script src="../js/inquiry_reports.js"></script>
    <script src="../js/nav_toggle.js"></script>

</body>

</html>