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
    // --- NEW: Fetch distinct values for filter dropdowns ---
    $filterOptions = [];
    $filterQueries = [
        'referred_by' => "SELECT DISTINCT reffered_by FROM registration WHERE branch_id = :branch_id AND reffered_by IS NOT NULL AND reffered_by != '' ORDER BY reffered_by",
        'conditions' => "SELECT DISTINCT chief_complain FROM registration WHERE branch_id = :branch_id AND chief_complain IS NOT NULL AND chief_complain != '' ORDER BY chief_complain",
        'inquiry_types' => "SELECT DISTINCT consultation_type FROM registration WHERE branch_id = :branch_id AND consultation_type IS NOT NULL AND consultation_type != '' ORDER BY consultation_type",
    ];

    // --- NEW: Fetch users for the 'Approved By' dropdown ---
    $stmtUsers = $pdo->prepare("SELECT id, username FROM users WHERE branch_id = :branch_id AND role != 'superadmin' ORDER BY username");
    $stmtUsers->execute([':branch_id' => $branchId]);
    $branchUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    foreach ($filterQueries as $key => $query) {
        $stmt = $pdo->prepare($query);
        $stmt->execute([':branch_id' => $branchId]);
        // Use FETCH_COLUMN to get a simple array of values
        $filterOptions[$key] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }


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
            pm.patient_uid, -- Here is our shiny new UID!
            reg.patient_photo_path,
            reg.consultation_type -- Fetch the inquiry type
        FROM
            registration AS reg
        LEFT JOIN
            patient_master AS pm ON reg.master_patient_id = pm.master_patient_id
        WHERE
            reg.branch_id = :branch_id
        ORDER BY
            reg.registration_id DESC
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

        @media screen and (max-width: 1024px) {
            .filter-bar {
                margin: 0;
                display: flex;
            }

            #searchInput {
                width: 500px;
            }

            .filter-options {
                display: flex;
                width: auto;
            }

            .filter-options select {
                max-width: 80px !important;
            }

            .sort-btn {
                margin: 0;
            }

            .drawer {
                max-height: 80vh;
            }

            .add-to-patient-drawer {
                left: 50%;
                z-index: 99999999999999999;
            }
        }

        #closeDrawer {
            font-size: 28px;
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
                <a href="dashboard.php"><i class="fa-solid fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="inquiry.php"><i class="fa-solid fa-magnifying-glass"></i><span>Inquiry</span></a>
                <a class="active" href="registration.php"><i class="fa-solid fa-user-plus"></i><span>Registration</span></a>
                <a href="appointments.php"><i class="fa-solid fa-calendar-check"></i><span>Appointments</span></a>
                <a href="patients.php"><i class="fa-solid fa-users"></i><span>Patients</span></a>
                <a href="billing.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>Billing</span></a>
                <a href="attendance.php"><i class="fa-solid fa-user-check"></i><span>Attendance</span></a>
                <a href="tests.php"><i class="fa-solid fa-vial"></i><span>Tests</span></a>
                <a href="reports.php"><i class="fa-solid fa-chart-line"></i><span>Reports</span></a>
                <a href="expenses.php"><i class="fa-solid fa-money-bill-wave"></i><span>Expenses</span></a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"> <?php echo $branchName; ?> Branch </div>
            <div class="icon-btn" id="theme-toggle"> <i id="theme-icon" class="fa-solid fa-moon"></i> </div>
            <div class="icon-btn icon-btn2" title="Notifications" onclick="openNotif()">🔔</div>
            <div class="profile" onclick="openForm()">S</div>
        </div>
        <!-- Hamburger Menu Icon (for mobile) -->
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
                <h2>Registration</h2>

                <!-- NEW: Filter and Search Bar -->
                <div class="filter-bar">
                    <div class="search-container">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by name, ID, condition, etc...">
                    </div>

                    <div class="filter-options">
                        <select id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="consulted">Consulted</option>
                            <option value="closed">Closed</option>
                        </select>
                        <select id="genderFilter">
                            <option value="">All Genders</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <select id="inquiryTypeFilter">
                            <option value="">All Inquiry Types</option>
                            <?php foreach ($filterOptions['inquiry_types'] as $inquiryType): ?>
                                <option value="<?= htmlspecialchars($inquiryType) ?>"><?= htmlspecialchars($inquiryType) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="referredByFilter">
                            <option value="">All Referrers</option>
                            <?php foreach ($filterOptions['referred_by'] as $referrer): ?>
                                <option value="<?= htmlspecialchars($referrer) ?>"><?= htmlspecialchars($referrer) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="conditionFilter">
                            <option value="">All Conditions</option>
                            <?php foreach ($filterOptions['conditions'] as $condition): ?>
                                <option value="<?= htmlspecialchars($condition) ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $condition))) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button id="sortDirectionBtn" class="sort-btn" title="Toggle Sort Direction">
                            <i class="fa-solid fa-sort"></i>
                        </button>
                        <button class="sort-btn" onclick="window.location.reload();">
                            <i class="fa-solid fa-rotate"></i>&nbsp; <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>


            <div id="quickTable" class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th data-key="patient_uid" class="sortable">ID</th>
                            <th>Photo</th>
                            <th data-key="patient_name" class="sortable">Name</th>
                            <!-- <th data-key="phone" class="sortable">Phone</th> -->
                            <th data-key="age" class="sortable">Age</th>
                            <th data-key="gender" class="sortable">Gender</th>
                            <th data-key="consultation_type" class="sortable">Inquiry Type</th>
                            <th data-key="reffered_by" class="sortable">Reffered By</th>
                            <th data-key="chief_complain" class="sortable">Condition Type</th>
                            <th data-key="consultation_amount" class="sortable numeric">Amount</th>
                            <th data-key="created_at" class="sortable">Date</th>
                            <th data-key="status">Status</th>
                            <th>Update Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="registrationTableBody">
                        <?php if (!empty($inquiries)): ?>
                            <?php foreach ($inquiries as $row): ?>
                                <tr data-id="<?= htmlspecialchars((string) $row['patient_uid'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?php
                                    $initial = !empty($row['patient_name']) ? strtoupper(substr($row['patient_name'], 0, 1)) : '?';
                                    ?>
                                    <td><?= htmlspecialchars($row['patient_uid'] ?? 'N/A') ?></td>
                                    <td data-label="Photo">
                                        <div class="photo-cell" data-registration-id="<?= htmlspecialchars((string)$row['registration_id']) ?>" title="Click to capture/update photo">
                                            <?php if (!empty($row['patient_photo_path'])): ?>
                                                <img src="/proadmin/admin/<?= htmlspecialchars($row['patient_photo_path']) ?>?v=<?= time() ?>" alt="Photo" class="table-photo">
                                            <?php else: ?>
                                                <div class="table-initials"><?= $initial ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td data-label="Name" class="name"><?= htmlspecialchars($row['patient_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- <td><?= htmlspecialchars($row['phone_number'], ENT_QUOTES, 'UTF-8') ?></td> -->
                                    <td data-label="Age"><?= htmlspecialchars((string) $row['age'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Gender"><?= htmlspecialchars($row['gender'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Inquiry Type"><?= htmlspecialchars($row['consultation_type'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Referred By"><?= htmlspecialchars($row['reffered_by'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Condition"><?= htmlspecialchars($row['chief_complain'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Amount" class="numeric">₹ <?= htmlspecialchars((string) $row['consultation_amount'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Date"><?= htmlspecialchars(date('d M Y', strtotime($row['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td data-label="Status">
                                        <span class="pill <?php echo strtolower($row['status']) ?>">
                                            <?php echo htmlspecialchars((string) $row['status']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Update Status"> <select data-id="<?php echo $row['registration_id'] ?>">
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'consulted' ? 'selected' : '' ?>>Consulted
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'closed' ? 'selected' : '' ?>>Closed
                                            </option>
                                        </select> </td>
                                    <td data-label="Action">
                                        <button class="action-btn" data-id="<?= htmlspecialchars((string) $row['registration_id'], ENT_QUOTES, 'UTF-8') ?>">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="no-data">No inquiries found</td>
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
                            <label for="treatmentTimeSlot">Time Slot *</label>
                            <select id="treatmentTimeSlot" name="treatment_time_slot" required>
                                <option value="">Select a date first</option>
                            </select>
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
                            <label for="discountApprovedBy">Discount Approved By</label>
                            <select id="discountApprovedBy" name="discount_approved_by">
                                <option value="">Not Required</option>
                                <?php foreach ($branchUsers as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
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

    <!-- NEW: Add Speech Patient Drawer -->
    <div class="add-to-patient-drawer" id="addSpeechPatientDrawer" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="add-drawer-header">
            <h3>Add Speech Patient</h3>
            <button type="button" class="add-drawer-close" aria-label="Close">&times;</button>
        </div>
        <div class="add-drawer-body">
            <form id="addSpeechPatientForm">
                <input type="hidden" id="speechRegistrationId" name="registrationId">
                <div class="form-group">
                    <label for="speechTreatmentType">Select Treatment Type</label>
                    <div class="treatment-options">
                        <label class="treatment-option" data-cost="500">
                            <input type="radio" name="treatmentType" value="daily" required>
                            <div class="treatment-option-info">
                                <h4>Daily Session</h4>
                                <p>₹500 per day</p>
                            </div>
                        </label>
                        <label class="treatment-option" data-cost="11000">
                            <input type="radio" name="treatmentType" value="package" required>
                            <div class="treatment-option-info">
                                <h4>Package</h4>
                                <p>₹11,000 for 26 days</p>
                            </div>
                        </label>
                    </div>
                </div>
                <div class="form-grid-container">
                    <div class="results-grid">
                        <div class="form-group" id="speechTreatmentDaysGroup">
                            <label for="speechTreatmentDays">Number of Days</label>
                            <input type="number" id="speechTreatmentDays" name="treatmentDays" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="speechStartDate">Start Date</label>
                            <input type="date" id="speechStartDate" name="startDate" required>
                        </div>
                        <div class="form-group">
                            <label for="speechEndDate">End Date</label>
                            <input type="date" id="speechEndDate" name="endDate" readonly>
                        </div>
                        <div class="form-group">
                            <label for="speechTreatmentTimeSlot">Time Slot *</label>
                            <select id="speechTreatmentTimeSlot" name="treatment_time_slot" required>
                                <option value="">Select a date first</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="speechTotalCost">Total Cost</label>
                            <input type="number" id="speechTotalCost" name="totalCost" readonly>
                        </div>
                    </div>
                    <div class="results-grid">
                        <div class="form-group">
                            <label for="speechDiscount">Discount (%)</label>
                            <input type="number" id="speechDiscount" name="discount" min="0" max="100" value="0">
                        </div>
                        <div class="form-group">
                            <label for="speechDiscountApprovedBy">Discount Approved By</label>
                            <select id="speechDiscountApprovedBy" name="discount_approved_by">
                                <option value="">Not Required</option>
                                <?php foreach ($branchUsers as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="speechAdvancePayment">Advance Payment (₹)</label>
                            <input type="number" id="speechAdvancePayment" name="advancePayment" min="0" value="0" required>
                        </div>
                        <div class="form-group">
                            <label for="speechDueAmount">Due Amount</label>
                            <input type="number" id="speechDueAmount" name="dueAmount" readonly>
                        </div>
                        <div class="form-group">
                            <label for="speechPaymentMethod">Payment Method *</label>
                            <select id="speechPaymentMethod" name="payment_method" required>
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

                <button type="submit" class="submit-button">Add Speech Patient</button>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>

    <!-- NEW: Photo Capture Modal -->

    <div id="toast-container"></div>

    <!-- NEW: Photo Capture Modal -->
    <div id="photo-modal-overlay" class="photo-modal-overlay">
        <div class="photo-modal">
            <h3 class="photo-modal-title">Capture Patient Photo</h3>

            <div class="photo-modal-body">
                <video id="webcam-feed" autoplay playsinline></video>
                <canvas id="photo-canvas" class="hidden"></canvas>
            </div>
            <p id="webcam-error" class="webcam-error hidden">Could not access the webcam. Please check permissions and try again.</p>

            <div id="initial-controls" class="photo-modal-footer">
                <button id="close-photo-modal-1" type="button" class="modal-btn cancel-btn">Cancel</button>
                <button id="capture-photo-btn" type="button" class="modal-btn capture-btn">
                    <i class="fa-solid fa-camera"></i> Click Photo
                </button>
            </div>

            <div id="confirm-controls" class="photo-modal-footer hidden">
                <button id="close-photo-modal-2" type="button" class="modal-btn cancel-btn">Cancel</button>
                <button id="retake-photo-btn" type="button" class="modal-btn retake-btn">
                    <i class="fa-solid fa-refresh"></i> Retake
                </button>
                <button id="upload-photo-btn" type="button" class="modal-btn upload-btn">
                    <i class="fa-solid fa-upload"></i> Upload
                </button>
            </div>
        </div>
    </div>


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
    </script>

</body>

</html>