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
    $stmt = $pdo->prepare("
        SELECT
            p.patient_id,
            r.patient_name,
            r.consultation_amount,
            p.total_amount AS treatment_total_amount,
            p.status AS treatment_status,
            (
                SELECT COALESCE(SUM(amount), 0)
                FROM payments
                WHERE patient_id = p.patient_id
            ) AS total_paid_from_payments
        FROM
            patients p
        JOIN
            registration r ON p.registration_id = r.registration_id
        WHERE
            p.branch_id = :branch_id
        ORDER BY
            p.created_at DESC
    ");
    $stmt->execute([':branch_id' => $branchId]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
} catch (PDOException $e) {
    die("Error fetching patient billing data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/billings.css">
    <style>
        /* === Filter & Search Bar === */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            max-width: 800px;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background: none;
            border-radius: 0.75rem;
        }

        .search-container {
            flex: 1 1 300px;
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
            padding: 0.6rem 1rem 0.6rem 2.5rem;
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
            padding: 0.6rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #ddd;
            background: #fff;
            color: #000;
            font-size: 0.9rem;
            cursor: pointer;
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
                /* margin: 0; */
                display: flex;
                width: auto;
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
                height: 90vh;
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
                <a href="billing.php" class="active">Billing</a>
                <a href="attendance.php">Attendance</a>
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
                <h2>Billing Overview</h2>

                <!-- NEW: Filter and Search Bar -->
                <div class="filter-bar">
                    <div class="search-container">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by Patient Name or ID...">
                    </div>

                    <div class="filter-options">
                        <select id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button id="sortDirectionBtn" class="sort-btn" title="Toggle Sort Direction">
                        <i class="fa-solid fa-sort"></i>
                    </button>
                </div>
            </div>


            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th data-key="id" class="sortable">Patient ID</th>
                            <th data-key="name" class="sortable">Patient Name</th>
                            <th data-key="bill" class="sortable numeric">Total Bill</th>
                            <th data-key="paid" class="sortable numeric">Total Paid</th>
                            <th data-key="due" class="sortable numeric">Expected Due Amount</th>
                            <th data-key="status">Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="billingTableBody">
                        <?php if (!empty($patients)) : ?>
                            <?php foreach ($patients as $row) : ?>
                                <?php
                                $total_billable = (float)$row['consultation_amount'] + (float)$row['treatment_total_amount'];
                                $total_paid = (float)$row['consultation_amount'] + (float)$row['total_paid_from_payments'];
                                $outstanding_due = $total_billable - $total_paid;
                                ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars((string) $row['patient_id']); ?></td>
                                    <td data-label="Patient Name"><?php echo htmlspecialchars((string) $row['patient_name']); ?></td>
                                    <td data-label="Total Bill" class="numeric">₹<?php echo number_format($total_billable, 2); ?></td>
                                    <td data-label="Total Paid" class="numeric">₹<?php echo number_format($total_paid, 2); ?></td>
                                    <td data-label="Expected Due Amount" class="numeric"><strong>₹<?php echo number_format($outstanding_due, 2); ?></strong></td>
                                    <td data-label="Status">
                                        <?php if (!empty($row['treatment_status'])) :
                                            $statusClass = strtolower($row['treatment_status']);
                                        ?>
                                            <span class="pill <?php echo htmlspecialchars($statusClass); ?>">
                                                <?php echo htmlspecialchars(ucfirst($row['treatment_status'])); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="pill pending">Unknown</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Actions">
                                        <button class="action-btn open-drawer" data-id="<?php echo (int) $row['patient_id']; ?>">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="no-data">No patient billing records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="drawer-overlay" id="drawer-overlay" style="display: none;">
            <div class="drawer-panel" id="drawer-panel">
                <div class="drawer-header">
                    <h2 id="drawer-patient-name">Patient Details</h2>
                    <button id="closeDrawer" class="drawer-close-btn">&times;</button>
                </div>
                <div class="drawer-body" id="drawer-body"></div>
            </div>
        </div>
    </main>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/billings.js"></script>
    <script src="../js/nav_toggle.js"></script>

</body>

</html>