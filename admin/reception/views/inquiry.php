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
require_once '../../common/db.php'; // PDO connection
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

    //branch name

    $stmt = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");

    $stmt->execute(['branch_id' => $branchId]);

    $branchName = $stmt->fetch()['branch_name'] ?? '';
} catch (PDOException $e) {

    die("Error fetching inquiries: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">
</head>

<body>
    <header>
        <div class="logo-container"> <img src="../../assets/images/image.png" alt="Pro Physio Logo" class="logo" />
        </div>
        <nav>
            <div class="nav-links"> <a href="dashboard.php">Dashboard</a> <a href="inquiry.php"
                    class="active">Inquiry</a> <a href="#">Registration</a> <a href="#">Patients</a> <a
                    href="#">Appointments</a> <a href="#">Billing</a> <a href="#">Attendance</a> <a href="#">Tests</a><a href="#">Reports</a> </div>
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
                <h2>Inquiry</h2>
                <div class="toggle-container"> <button id="quickBtn" class="toggle-btn active">Quick Inquiry</button>
                    <button id="testBtn" class="toggle-btn">Test Inquiry</button>
                </div>
            </div> <!-- Quick Inquiry Table -->
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
                                    <td> <?php echo htmlspecialchars((string) $row['phone_number']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['age']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['gender']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['referralSource']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['chief_complain']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['review']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['expected_visit_date']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['created_at']) ?> </td>
                                    <td> <span class="pill <?php echo strtolower($row['status']) ?>">
                                            <?php echo htmlspecialchars((string) $row['status']) ?> </span> </td>
                                    <td> <select data-id="<?php echo $row['inquiry_id'] ?>" data-type="quick">
                                            <option <?php echo strtolower($row['status']) === 'visited' ? 'selected' : '' ?>>Visited
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending
                                            </option>
                                        </select> </td>
                                    <td> <button class="action-btn open-drawer" data-id="<?php echo $row['inquiry_id'] ?>"
                                            data-type="quick"> View </button> </td>
                                </tr> <?php endforeach; ?> <?php else: ?>
                            <tr>
                                <td colspan="11">No Quick Inquiry found</td>
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
                                    <td> <?php echo htmlspecialchars((string) $row['testname']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['reffered_by'] ?? '-') ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['mobile_number']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['expected_visit_date']) ?> </td>
                                    <td> <?php echo htmlspecialchars((string) $row['created_at']) ?> </td>
                                    <td> <span class="pill <?php echo strtolower($row['status']) ?>">
                                            <?php echo htmlspecialchars((string) $row['status']) ?> </span> </td>
                                    <td> <select data-id="<?php echo $row['inquiry_id'] ?>" data-type="test">
                                            <option <?php echo strtolower($row['status']) === 'visited' ? 'selected' : '' ?>>Visited
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled
                                            </option>
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending
                                            </option>
                                        </select> </td>
                                    <td> <button class="action-btn open-drawer" data-id="<?php echo $row['inquiry_id'] ?>"
                                            data-type="test"> View </button> </td>
                                </tr> <?php endforeach; ?> <?php else: ?>
                            <tr>
                                <td colspan="8">No Test Inquiry found</td>
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
                        <div class="detail-item">
                            <label>Appointment Date</label>
                            <input type="date" name="appointment_date">
                        </div>
                        <div class="detail-item">
                            <label>Time</label>
                            <input type="time" name="time">
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="submit-btn2">
                    <button type="submit">Save</button>
                </div>
            </form>


            <!-- Test Inquiry Form -->
            <!-- Test Inquiry Form -->
            <form id="testForm" class="inquiry-form hidden" method="POST" action="../api/test_submission.php">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
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
                                <option value="both">Both</option>
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
    <script src="../js/inquiry.js"></script>
    <script src="../js/dashboard.js"></script>
</body>

</html>