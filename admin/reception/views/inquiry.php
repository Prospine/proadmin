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

if (! isset($_SESSION['uid'])) {

    header('Location: ../../login.php');

    exit();
}
require_once '../../common/auth.php';
require_once '../../common/db.php';
// -------------------------

if (!isset($csrf)) {
    $csrf = $_SESSION['csrf_token'] ?? null;
    if (!$csrf) {
        $csrf = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $csrf;
    }
}

// Branch-based Access Only

// -------------------------

$branchId = $_SESSION['branch_id'] ?? null;

if (! $branchId) {

    http_response_code(403);

    exit('Branch not assigned.');
}

try {

    // Quick Inquiry

    $stmtQuick = $pdo->prepare("

SELECT inquiry_id, name, phone_number, age, gender, referralSource, chief_complain, review, expected_visit_date, created_at, status
FROM quick_inquiry
WHERE branch_id = :branch_id
ORDER BY created_at DESC

");

    $stmtQuick->execute([':branch_id' => $branchId]);

    $quick_inquiries = $stmtQuick->fetchAll(PDO::FETCH_ASSOC);

    // Test Inquiry

    $stmtTest = $pdo->prepare("

SELECT inquiry_id, name, testname, reffered_by, mobile_number, expected_visit_date, created_at, status

FROM test_inquiry

WHERE branch_id = :branch_id

ORDER BY created_at DESC

");

    $stmtTest->execute([':branch_id' => $branchId]);

    $test_inquiries = $stmtTest->fetchAll(PDO::FETCH_ASSOC);

    // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
} catch (PDOException $e) {

    die("Error fetching inquiries: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">

    <style>
        .toggle-container {
            width: auto;
        }

        .wrapper {
            display: flex;
            width: auto;
            gap: 10px;
        }
    </style>
</head>

<body>
    <!-- Mobile Blocker Overlay -->
    <div class="mobile-blocker">
        <div class="mobile-blocker-popup">
            <i class="fa-solid fa-mobile-screen-button popup-icon"></i>
            <h2>Mobile View Not Supported</h2>
            <p>The admin panel is designed for desktop use. For the best experience on your mobile device, please download our dedicated application.</p>
            <a href="/download-app/index.html" class="mobile-download-btn">
                <i class="fa-solid fa-download"></i> Download App
            </a>
        </div>
    </div>
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
                <a href="inquiry.php" class="active"><i class="fa-solid fa-magnifying-glass"></i><span>Inquiry</span></a>
                <a href="registration.php"><i class="fa-solid fa-user-plus"></i><span>Registration</span></a>
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
            <div class="icon-btn" title="Settings"><?php echo $branchName; ?> Branch</div>
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
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
    <div class="notification" id="myNotif">
        <span class="closebtn" onclick="closeNotif()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="changelog.html" class="active2">View Changes (1) </a></li>
            </ul>
        </div>
    </div>
    <main class="main">
        <div class="dashboard-container">
            <div class="top-bar">
                <h2>Inquiry</h2>

                <div class="wrapper">
                    <!-- NEW: Filter and Search Bar -->
                    <div class="filter-bar">
                        <div class="search-container">
                            <i class="fa-solid fa-search"></i>
                            <input type="text" id="searchInput" placeholder="Search by name, phone, complain, etc...">
                        </div>

                        <!-- Filters for Quick Inquiry Table -->
                        <div id="quickInquiryFilters" class="filter-options">
                            <select id="quickStatusFilter">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="visited">Visited</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Filters for Test Inquiry Table -->
                        <div id="testInquiryFilters" class="filter-options hidden">
                            <select id="testStatusFilter">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="visited">Visited</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="tog">
                        <div class="toggle-container">
                            <button id="quickBtn" class="toggle-btn active">Quick Inquiry</button>
                            <button id="testBtn" class="toggle-btn">Test Inquiry</button>
                        </div>

                        <div class="toggle-container">
                            <button class="toggle-btn" onclick="window.location.href = 'online_inquiry.php';">Online Inquiry</button>
                            <button class="toggle-btn" onclick="window.location.href = 'online_inquiry_booked.php';">Online Inquiry Booked</button>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Quick Inquiry Table -->
            <div id="quickTable" class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Referral Source</th>
                            <th>Chief Complain</th>
                            <th>Review</th>
                            <th>Expected Visit</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Update Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody> <?php if (! empty($quick_inquiries)): ?> <?php foreach ($quick_inquiries as $row): ?>
                                <tr>
                                    <td> <?php echo htmlspecialchars((string) $row['name']) ?> </td>
                                    <td data-label="Phone"><?php echo htmlspecialchars((string) $row['phone_number']) ?></td>
                                    <td data-label="Age"><?php echo htmlspecialchars((string) $row['age']) ?></td>
                                    <td data-label="Gender"><?php echo htmlspecialchars((string) $row['gender']) ?></td>
                                    <td data-label="Referral"><?php echo htmlspecialchars((string) $row['referralSource']) ?></td>
                                    <td data-label="Complain"><?php echo htmlspecialchars((string) $row['chief_complain']) ?></td>
                                    <td data-label="Review"><?php echo htmlspecialchars((string) $row['review']) ?></td>
                                    <td data-label="Expected Visit"><?php echo htmlspecialchars((string) $row['expected_visit_date']) ?></td>
                                    <td data-label="Created At"><?php echo htmlspecialchars((string) $row['created_at']) ?></td>
                                    <td data-label="Status"><span class="pill <?php echo strtolower($row['status']) ?>">
                                            <?php echo htmlspecialchars((string) $row['status']) ?> </span>
                                    </td>
                                    <td data-label="Update Status"><select data-id="<?php echo $row['inquiry_id'] ?>" data-type="quick">
                                            <option <?php echo strtolower($row['status']) === 'visited' ? 'selected' : '' ?>>Visited
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending
                                            </option>
                                        </select></td>
                                    <td data-label="Action"><button class="action-btn open-drawer" data-id="<?php echo $row['inquiry_id'] ?>"
                                            data-type="quick"> View </button></td>
                                </tr> <?php endforeach; ?> <?php else: ?>
                            <tr>
                                <td colspan="12">No Quick Inquiry found</td>
                            </tr> <?php endif; ?>
                    </tbody>
                </table>
            </div> <!-- Test Inquiry Table -->
            <div id="testTable" class="table-container modern-table hidden">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Test Name</th>
                            <th>Referred By</th>
                            <th>Mobile</th>
                            <th>Expected Visit</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Update Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody> <?php if (! empty($test_inquiries)): ?> <?php foreach ($test_inquiries as $row): ?>
                                <tr>
                                    <td> <?php echo htmlspecialchars((string) $row['name']) ?> </td>
                                    <td data-label="Test Name"><?php echo htmlspecialchars((string) $row['testname']) ?></td>
                                    <td data-label="Referred By"><?php echo htmlspecialchars((string) $row['reffered_by'] ?? '-') ?></td>
                                    <td data-label="Mobile"><?php echo htmlspecialchars((string) $row['mobile_number']) ?></td>
                                    <td data-label="Expected Visit"><?php echo htmlspecialchars((string) $row['expected_visit_date']) ?></td>
                                    <td data-label="Created At"><?php echo htmlspecialchars((string) $row['created_at']) ?></td>
                                    <td data-label="Status"><span class="pill <?php echo strtolower($row['status']) ?>">
                                            <?php echo htmlspecialchars((string) $row['status']) ?> </span></td>
                                    <td data-label="Update Status"><select data-id="<?php echo $row['inquiry_id'] ?>" data-type="test">
                                            <option <?php echo strtolower($row['status']) === 'visited' ? 'selected' : '' ?>>Visited
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending
                                            </option>
                                        </select></td>
                                    <td data-label="Action"><button class="action-btn open-drawer" data-id="<?php echo $row['inquiry_id'] ?>"
                                            data-type="test"> View </button></td>
                                </tr> <?php endforeach; ?> <?php else: ?>
                            <tr>
                                <td colspan="9">No Test Inquiry found</td>
                            </tr> <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="toast-container"></div>
    </main>

    <div id="rightDrawer" class="drawer" aria-hidden="true">
        <div class="drawer-header">
            <h2 id="drawerTitle">Inquiry Details</h2>
            <button class="close-drawer" aria-label="Close drawer">&times;</button>
        </div>

        <div id="drawerMessage" class="drawer-message"></div>
        <div class="msg" style="text-align: center; color: #fc2222ff; font-weight: bold; font-size: 14px; background-color: #fac6c6ff; margin: 0px 8px; border-radius: 10px;">
            <p>Currently do not use this for Registration/Test. Use the form in the Dashboard instead.</p>
        </div>

        <div class="drawer-body">

            <!-- Quick Inquiry Form -->
            <form id="quickForm" class="inquiry-form hidden" method="post">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="inquiry_id" id="inquiry_id" value="">

                <!-- Personal Information -->
                <div class="info-card">
                    <h3>Personal Information</h3>
                    <div class="card-grid">
                        <div class="first">
                            <div class="detail-item">
                                <label>Enter Patient Name *</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="detail-item">
                                <label>Age *</label>
                                <input type="number" name="age" min="1" required>
                            </div>
                            <div class="detail-item">
                                <label>Gender *</label>
                                <select name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="detail-item">
                                <label>Phone *</label>
                                <input type="text" name="phone_number" maxlength="10" required>
                            </div>
                        </div>
                        <div class="second">
                            <div class="detail-item">
                                <label>Email</label>
                                <input type="email" name="email">
                            </div>
                            <div class="detail-item full-width-section">
                                <label>Address</label>
                                <input type="text" name="address">
                            </div>
                            <div class="detail-item">
                                <label>Occupation *</label>
                                <input type="text" name="occupation" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Referral Information -->
                <div class="info-card">
                    <h3>Referral Information</h3>
                    <div class="card-grid">
                        <div class="detail-item">
                            <label>Referred By *</label>
                            <input type="text" name="referred_by" required>
                        </div>
                        <div class="detail-item">
                            <label>How did you hear about us</label>
                            <select name="referralSource">
                                <option value="self">Select</option>
                                <option value="doctor_referral">Doctor Referral</option>
                                <option value="web_search">Web Search</option>
                                <option value="social_media">Social Media</option>
                                <option value="returning_patient">Returning Patient</option>
                                <option value="local_event">Local Event</option>
                                <option value="advertisement">Advertisement</option>
                                <option value="employee">Employee</option>
                                <option value="family">Family</option>
                                <option value="self">Self</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Medical Details -->
                <div class="info-card">
                    <h3>Medical Details</h3>
                    <div class="card-grid">
                        <div class="detail-item">
                            <label>Chief Complain *</label>
                            <select name="chief_complain">
                                <option value="other">Select your condition</option>
                                <option value="neck_pain">Neck Pain</option>
                                <option value="back_pain">Back Pain</option>
                                <option value="low_back_pain">Low Back Pain</option>
                                <option value="radiating_pain">Radiating Pain</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="detail-item full-width-section">
                            <label>Describe Condition / Remarks</label>
                            <input type="text" name="review">
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="info-card">
                    <h3>Payment Details</h3>
                    <div class="card-grid">
                        <div class="detail-item">
                            <label>Amount *</label>
                            <input type="number" name="amount" step="0.01" required>
                        </div>
                        <div class="detail-item">
                            <label>Payment Method *</label>
                            <select name="payment_method" required>
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

                <!-- Consultation & Appointment -->
                <div class="info-card">
                    <h3>Consultation & Appointment</h3>
                    <div class="card-grid">
                        <div class="detail-item">
                            <label>Consultation Type *</label>
                            <select name="inquiry_type" required>
                                <option value="">Select Consultation Type</option>
                                <option value="In-Clinic">In-Clinic</option>
                                <option value="Home-Visit">Home-Visit</option>
                                <option value="Virtual/Online">Virtual/Online</option>
                            </select>
                        </div>
                        <div>
                            <label>Appointment Date</label>
                            <input type="date" name="appointment_date">
                        </div>

                        <div class="select-wrapper">
                            <label>Time Slot *</label>
                            <select name="appointment_time" id="appointment_time" required>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="submit-btn2">
                    <button type="submit">Register</button>
                </div>
            </form>


            <!-- Test Inquiry Form -->
            <form id="testForm" class="inquiry-form hidden" method="POST" action="../api/test_submission.php">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="inquiry_id" id="inquiry_id" value="">

                <!-- Patient Information -->
                <div class="info-card">
                    <h3>Patient Information</h3>
                    <div class="card-grid">
                        <div class="detail-item">
                            <label>Patient Name *</label>
                            <input type="text" name="name" placeholder="Enter Patient Name" required>
                        </div>
                        <div class="detail-item">
                            <label>Age *</label>
                            <input type="number" name="age" max="150" required>
                        </div>
                        <div class="detail-item">
                            <label>DOB</label>
                            <input type="date" name="dob">
                        </div>
                        <div class="detail-item">
                            <label>Gender *</label>
                            <select name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="detail-item">
                            <label>Parents/Guardian</label>
                            <input type="text" name="parents" placeholder="Parents/Guardian Name">
                        </div>
                        <div class="detail-item">
                            <label>Relation</label>
                            <input type="text" name="relation" placeholder="e.g., Father, Mother">
                        </div>
                        <div class="detail-item">
                            <label>Phone No *</label>
                            <input type="text" name="mobile_number" placeholder="+911234567890" maxlength="10" required>
                        </div>
                        <div class="detail-item">
                            <label>Alternate Phone No</label>
                            <input type="text" name="alternate_phone_no" placeholder="+911234567890" maxlength="10">
                        </div>
                    </div>
                </div>

                <!-- Referral Information -->
                <div class="info-card">
                    <h3>Referral Information</h3>
                    <div class="card-grid">
                        <div class="detail-item">
                            <label>Referred By</label>
                            <input type="text" name="reffered_by" placeholder="Doctor/Clinic Name">
                        </div>
                    </div>
                </div>

                <!-- Test Details -->
                <div class="info-card">
                    <h3>Test Details</h3>
                    <div class="card-grid">
                        <div class="detail-item">
                            <label>Test Name *</label>
                            <select name="testname" required>
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
                        <div class="detail-item">
                            <label>Limb</label>
                            <select name="limb">
                                <option value="">Select Limb</option>
                                <option value="upper_limb">Upper Limb</option>
                                <option value="lower_limb">Lower Limb</option>
                                <option value="both">Both Limbs</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="detail-item">
                            <label>Date of Visit *</label>
                            <input type="date" name="visit_date" required>
                        </div>
                        <div class="detail-item">
                            <label>Assigned Test Date *</label>
                            <input type="date" name="assigned_test_date" required>
                        </div>

                        <div class="detail-item">
                            <label>Test Done By *</label>
                            <select name="test_done_by" required>
                                <option value="">Select Staff</option>
                                <option value="achal">Achal</option>
                                ion value="ashish">Ashish</option>
                                <option value="pancham">Pancham</option>
                                <option value="sayan">Sayan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="info-card">
                    <h3>Payment Details</h3>
                    <div class="card-grid">
                        <div class="detail-item">
                            <label>Total Amount *</label>
                            <input type="number" name="total_amount" step="0.01" placeholder="Enter Amount" required>
                        </div>
                        <div class="detail-item">
                            <label>Advance Amount</label>
                            <input type="number" name="advance_amount" step="0.01" value="0" placeholder="Enter Advance Amount">
                        </div>
                        <div class="detail-item">
                            <label>Due Amount</label>
                            <input type="number" name="due_amount" step="0.01" value="0" placeholder="Enter Due Amount">
                        </div>
                        <div class="detail-item">
                            <label>Discount</label>
                            <input type="number" name="discount" step="0.01" value="0" placeholder="Enter Discount">
                        </div>
                        <div class="detail-item">
                            <label>Payment Method *</label>
                            <select name="payment_method" required>
                                <option value="">Select Method</option>
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="card">Card</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="submit-btn2">
                    <button type="submit">Submit Test</button>
                </div>
            </form>
        </div>
    </div>
    <div id="toast-container"></div>


    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/inquiry.js"></script>
    <script src="../js/nav_toggle.js"></script>
    <script>
        // 4. Get Time slots
        const slotSelect = document.getElementById("appointment_time");

        fetch("../api/get_slots.php")
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    data.slots.forEach(slot => {
                        const opt = document.createElement("option");
                        opt.value = slot.time;
                        opt.textContent = slot.label;
                        if (slot.disabled) {
                            opt.disabled = true;
                            opt.textContent += " (Booked)";
                        }
                        slotSelect.appendChild(opt);
                    });
                } else {
                    console.error(data.message);
                }
            })
            .catch(err => console.error("Error fetching slots:", err));

        // ==========================================================
        // 4. Time Slot Management (NOW DYNAMIC!)
        // ==========================================================
        const dateInput = document.querySelector("input[name='appointment_date']");

        /**
         * Fetches and populates time slots for a specific date.
         * @param {string} dateString - The date in 'YYYY-MM-DD' format.
         */
        function fetchSlotsForDate(dateString) {
            if (!dateString || !slotSelect) return; // Don't run if there's no date or select box

            // Clear existing options and show a loading state
            slotSelect.innerHTML = '<option>Loading slots...</option>';

            // Fetch slots for the given date
            fetch(`../api/get_slots.php?date=${dateString}`)
                .then(res => res.json())
                .then(data => {
                    // Clear the loading message
                    slotSelect.innerHTML = '';

                    if (data.success && data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const opt = document.createElement("option");
                            opt.value = slot.time;
                            opt.textContent = slot.label;
                            if (slot.disabled) {
                                opt.disabled = true;
                                opt.textContent += " (Booked)";
                            }
                            slotSelect.appendChild(opt);
                        });
                    } else {
                        // Handle cases with no slots or an error
                        const errorOption = document.createElement("option");
                        errorOption.textContent = data.message || "No slots available.";
                        errorOption.disabled = true;
                        slotSelect.appendChild(errorOption);
                        console.error(data.message);
                    }
                })
                .catch(err => {
                    slotSelect.innerHTML = '<option>Error loading slots.</option>';
                    console.error("Error fetching slots:", err);
                });
        }

        // --- Attach the Event Listener ---
        // When the user picks a new date, re-fetch the slots.
        dateInput.addEventListener('change', (event) => {
            fetchSlotsForDate(event.target.value);
        });

        // --- Initial Load ---
        // When the page first loads, set today's date and fetch slots for today.
        const today = new Date().toISOString().split('T')[0]; // Gets today's date as 'YYYY-MM-DD'
        dateInput.value = today; // Set the input to today by default
        dateInput.min = today; // Optional: prevent booking for past dates
        fetchSlotsForDate(today);
    </script>
</body>

</html>