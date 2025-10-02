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
            reg.status,
            pm.patient_uid -- Here is our shiny new UID!
        FROM
            registration AS reg
        LEFT JOIN
            patient_master AS pm ON reg.master_patient_id = pm.master_patient_id
        WHERE
            reg.branch_id = :branch_id
        ORDER BY
            reg.created_at DESC
    ");
    $stmt->execute([':branch_id' => $branchId]);
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
    <title>Registration</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="../css/registration.css">

    <style>
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
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <div class="logo">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/proadmin/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo">
                <?php else: ?>
                    <div class="logo-placeholder">Primary Logo N/A</div>
                <?php endif; ?>
            </div>
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.php">Inquiry</a>
                <a class="active" href="registration.php">Registration</a>
                <a href="patients.php">Patients</a>
                <a href="appointments.php">Appointments</a>
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
                <h2>Registration</h2>
            </div>
            <div id="quickTable" class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th data-key="id" class="sortable">ID <span class="sort-indicator"></span></th>
                            <th data-key="name" class="sortable">Name <span class="sort-indicator"></span></th>
                            <!-- <th data-key="phone" class="sortable">Phone</th> -->
                            <th data-key="age" class="sortable">Age</th>
                            <th data-key="gender" class="sortable">Gender</th>
                            <th data-key="reffered_by" class="sortable">Reffered By</th>
                            <th data-key="conditionType" class="sortable">Condition Type</th>
                            <th data-key="consultation_amount" class="sortable">Amount</th>
                            <th data-key="created_at" class="sortable">Date</th>
                            <th data-key="status">Status</th>
                            <th>Update Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($inquiries)): ?>
                            <?php foreach ($inquiries as $row): ?>
                                <tr data-id="<?= htmlspecialchars((string) $row['patient_uid'], ENT_QUOTES, 'UTF-8') ?>">
                                    <td><?= htmlspecialchars($row['patient_uid'] ?? 'N/A') ?></td>
                                    <td class="name"><?= htmlspecialchars($row['patient_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- <td><?= htmlspecialchars($row['phone_number'], ENT_QUOTES, 'UTF-8') ?></td> -->
                                    <td><?= htmlspecialchars((string) $row['age'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['gender'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['reffered_by'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['chief_complain'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="numeric">₹ <?= htmlspecialchars((string) $row['consultation_amount'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><small><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td>
                                        <span class="pill <?php echo strtolower($row['status']) ?>">
                                            <?php echo htmlspecialchars((string) $row['status']) ?>
                                        </span>
                                    </td>
                                    <td> <select data-id="<?php echo $row['registration_id'] ?>">
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'consulted' ? 'selected' : '' ?>>Consulted
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'closed' ? 'selected' : '' ?>>Closed
                                            </option>
                                        </select> </td>
                                    <td>
                                        <button class="action-btn" data-id="<?= htmlspecialchars((string) $row['registration_id'], ENT_QUOTES, 'UTF-8') ?>">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="no-data">No inquiries found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="drawer" class="drawer">
        <div class="drawer-content">
            <button id="closeDrawer">&times;</button>
            <div id="drawer-body"></div>
        </div>
    </div>

    <div class="add-to-patient-drawer" id="addPatientDrawer" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="add-drawer-header">
            <h3>Add Patient</h3>
            <button type="button" class="add-drawer-close" aria-label="Close">&times;</button>
        </div>
        <div class="add-drawer-body">
            <form id="addPatientForm">
                <input type="hidden" id="registrationId" name="registrationId">
                <div class="form-group">
                    <label for="treatmentType">Select Treatment Type</label>
                    <div class="treatment-options">
                        <label class="treatment-option" data-cost="600">
                            <input type="radio" name="treatmentType" value="daily" required>
                            <div class="treatment-option-info">
                                <h4>Daily Treatment</h4>
                                <p>₹600 per day</p>
                            </div>
                        </label>
                        <label class="treatment-option" data-cost="1000">
                            <input type="radio" name="treatmentType" value="advance" required>
                            <div class="treatment-option-info">
                                <h4>Advance Treatment</h4>
                                <p>₹1000 per day</p>
                            </div>
                        </label>
                        <label class="treatment-option" data-cost="30000">
                            <input type="radio" name="treatmentType" value="package" required>
                            <div class="treatment-option-info">
                                <h4>RSDT</h4>
                                <p>₹30,000 for 21 days</p>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="form-grid-container">
                    <div class="results-grid">
                        <div class="form-group" id="treatmentDaysGroup">
                            <label for="treatmentDays">Number of Days</label>
                            <input type="number" id="treatmentDays" name="treatmentDays" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="startDate">Start Date</label>
                            <input type="date" id="startDate" name="startDate" required>
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date</label>
                            <input type="date" id="endDate" name="endDate" readonly>
                        </div>
                        <div class="form-group">
                            <label for="totalCost">Total Cost</label>
                            <input type="number" id="totalCost" name="totalCost" readonly>
                        </div>
                    </div>
                    <div class="results-grid">
                        <div class="form-group">
                            <label for="discount">Discount (%)</label>
                            <input type="number" id="discount" name="discount" min="0" max="100" value="0">
                        </div>

                        <div class="form-group">
                            <label for="advancePayment">Advance Payment (₹)</label>
                            <input type="number" id="advancePayment" name="advancePayment" min="0" value="0" required>
                        </div>
                        <div class="form-group">
                            <label for="dueAmount">Due Amount</label>
                            <input type="number" id="dueAmount" name="dueAmount" readonly>
                        </div>
                        <div class="form-group">
                            <label for="paymentMethod">Payment Method *</label>
                            <select id="paymentMethod" name="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-button">Add Patient</button>
            </form>
        </div>
    </div>
    <div id="toast-container"></div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/registration.js"></script>

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
    </script>

</body>

</html>