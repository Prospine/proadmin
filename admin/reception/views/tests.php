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
    // --- NEW: Fetch distinct values for filters ---
    $filterOptions = [];
    $filterQueries = [
        'test_names' => "SELECT DISTINCT test_name FROM tests WHERE branch_id = ? ORDER BY test_name",
        'payment_statuses' => "SELECT DISTINCT payment_status FROM tests WHERE branch_id = ? ORDER BY payment_status",
        'test_statuses' => "SELECT DISTINCT test_status FROM tests WHERE branch_id = ? ORDER BY test_status",
    ];

    foreach ($filterQueries as $key => $query) {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$branchId]);
        $filterOptions[$key] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Fetch all tests for the branch
    $stmt = $pdo->prepare("
    SELECT 
        t.*,
        t.test_uid -- Fetch the new UID
    FROM tests t
    WHERE t.branch_id = :branch_id AND t.test_status != 'cancelled'
    ORDER BY t.created_at DESC
");
    $stmt->execute([':branch_id' => $branchId]);
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
} catch (PDOException $e) {
    die("Error fetching test records: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tests</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/test.css">

    <style>
        /* === Filter & Search Bar === */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            /* border: 1px solid; */
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background: none;
            border-radius: 0.75rem;
        }

        .search-container {
            flex: 1 1 200px;
            min-width: 250px;
            position: relative;
        }

        .search-container .fa-search {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .search-container input {
            width: 100%;
            padding: 0.8rem 1rem 0.6rem 2.5rem;
            border-radius: 0.5rem;
            border: 1px solid #ddd;
            font-size: 0.9rem;
        }

        .filter-options {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-options select,
        .sort-btn {
            width: 180px;
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #ddd;
            background: #fff;
            color: #000;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .sort-btn {
            width: 80px;
            /* border-radius: 50%; */
        }

        body.dark .filter-bar {
            background: var(--card-bg);
        }

        body.dark .search-container input,
        body.dark .filter-options select,
        body.dark .sort-btn {
            background: var(--card-bg3);
            border-color: var(--border-color);
            color: var(--text-color);
        }

        @media (max-width: 1024px) {

            .filter-bar {
                width: auto;
            }

            .drawer {
                margin: 0;
                margin-right: 30px;
                width: 720px;
                height: 90vh;
                z-index: 99999999;
            }
        }

        /* --- NEW: Modal Styles --- */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 100000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.is-visible {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: var(--card-bg, #fff);
            border-radius: 8px;
            padding: 1.5rem;
            width: 90%;
            max-width: 800px;
        }

        body.dark .modal-content {
            background-color: #333;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
        }

        .form-grid-condensed {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            min-width: 700px;
        }

        .drawer-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            text-align: right;
        }

        .drawer-footer .action-btn {
            padding: 0.6rem 1.2rem;
        }

        .button-box {
            margin-top: 10px;
            margin-right: 20px;
            margin-left: -170px;
            height: 70px;
            align-items: center;
            text-align: center;
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
                <a href="tests.php" class="active">Tests</a>
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
                <h2>Tests Overview</h2>

                <!-- NEW: Filter and Search Bar -->
                <div class="filter-bar">
                    <div class="search-container">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by Patient Name, Test Name...">
                    </div>
                    <div class="filter-options">
                        <select id="testNameFilter">
                            <option value="">All Tests</option>
                            <?php foreach ($filterOptions['test_names'] as $option): ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(strtoupper($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="paymentStatusFilter">
                            <option value="">All Payment Statuses</option>
                            <?php foreach ($filterOptions['payment_statuses'] as $option): ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="testStatusFilter">
                            <option value="">All Test Statuses</option>
                            <?php foreach ($filterOptions['test_statuses'] as $option): ?>
                                <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars(ucfirst($option)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button id="sortDirectionBtn" class="sort-btn" title="Toggle Sort Direction">
                            <i class="fa-solid fa-sort"></i>
                        </button>
                    </div>
                </div>
                <div class="button-box">
                    <button onclick="window.location.href='cancelledtests.php'">Cancelled Test</button>
                </div>
            </div>


            <!-- Table -->
            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th data-key="id" class="sortable">Test UID</th>
                            <th data-key="name" class="sortable">Name</th>
                            <th data-key="test_name" class="sortable">Test Name</th>
                            <th data-key="due" class="sortable numeric">Paid Amount</th>
                            <th data-key="due" class="sortable numeric">Due Amount</th>
                            <th data-key="payment_status">Payment Status</th>
                            <th data-key="test_status">Test Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="testsTableBody">
                        <?php foreach ($tests as $row): ?>
                            <tr>
                                <td data-label="Test UID"><?= htmlspecialchars($row['test_uid'] ?: (string)$row['test_id']) ?></td>
                                <td data-label="Name"><?= htmlspecialchars($row['patient_name']) ?></td>
                                <td data-label="Test Name"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', (string) $row['test_name']))) ?></td>
                                <td data-label="Due Amount">₹<?= number_format((float)$row['advance_amount'], 2) ?></td>
                                <td data-label="Due Amount">₹<?= number_format((float)$row['due_amount'], 2) ?></td>
                                <td data-label="Payment Status">
                                    <span class="pill <?php echo strtolower($row['payment_status']); ?>">
                                        <?php echo ucfirst($row['payment_status']); ?>
                                    </span>
                                </td>
                                <td data-label="Test Status">
                                    <span class="pill <?php echo strtolower($row['test_status']); ?>">
                                        <?php echo ucfirst($row['test_status']); ?>
                                    </span>
                                </td>
                                <td data-label="Actions">
                                    <button class="action-btn open-drawer" data-id="<?= (int)$row['test_id'] ?>">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Toast Container -->
    <div id="toast-container"></div>


    <div id="test-drawer" class="drawer">
        <div class="drawer-header">
            <h3>Test Details</h3>
            <button class="close-drawer">&times;</button>
        </div>
        <div class="drawer-content">
            <!-- Dynamic content goes here -->
        </div>
    </div>

    <!-- NEW: Add Test Item Modal -->
    <div id="add-test-item-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Test Item</h3>
                <button class="close-modal-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addTestItemForm">
                    <input type="hidden" name="test_id" id="item_test_id"> <!-- This links to the main test order -->
                    <div class="form-grid-condensed">
                        <div class="form-group">
                            <label>Test Name *</label>
                            <select name="test_name" required>
                                <option value="">Select Test</option>
                                <option value="eeg">EEG</option>
                                <option value="ncv">NCV</option>
                                <option value="emg">EMG</option>
                                <option value="rns">RNS</option>
                                <option value="bera">BERA</option>
                                <option value="vep">VEP</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Limb</label>
                            <select name="limb">
                                <option value="">Select Limb</option>
                                <option value="upper_limb">Upper Limb</option>
                                <option value="lower_limb">Lower Limb</option>
                                <option value="both">Both Limbs</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assigned Test Date *</label>
                            <input type="date" name="assigned_test_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Test Done By *</label>
                            <select name="test_done_by" required>
                                <option value="">Select Staff</option>
                                <option value="achal">Achal</option>
                                <option value="ashish">Ashish</option>
                                <option value="pancham">Pancham</option>
                                <option value="sayan">Sayan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Referred By</label>
                            <input type="text" name="referred_by" placeholder="Dr. Name">
                        </div>
                        <div class="form-group">
                            <label>Total Amount *</label>
                            <input type="number" name="total_amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Discount</label>
                            <input type="number" name="discount" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Advance Amount</label>
                            <input type="number" name="advance_amount" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Due Amount</label>
                            <input type="number" name="due_amount" step="0.01" value="0" readonly style="background: var(--bg-tertiary);">
                        </div>
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="upi-boi">UPI-BOI</option>
                                <option value="upi-hdfc">UPI-HDFC</option>
                                <option value="card">Card</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions"><button type="submit" class="action-btn">Save Test Item</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/test.js"></script>
    <script src="../js/nav_toggle.js"></script>

</body>

</html>