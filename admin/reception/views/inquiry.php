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
require_once '../../common/db.php'; // PDO connection

// -------------------------
// Branch-based Access Only
// -------------------------
$branchId = $_SESSION['branch_id'] ?? null;
if (!$branchId) {
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
    <style>
        /* --- Base Drawer Styling --- */
        .drawer-content {
            padding: 24px;
            /* Reduced padding */
            font-family: 'Inter', system-ui, sans-serif;
            color: #1a202c;
            background: #ffffff;
        }

        /* --- Main Flex Container for All Cards --- */
        .inquiry-details {
            display: flex;
            flex-direction: column;
            gap: 16px;
            /* Reduced space between each category card */
        }

        /* --- Core Card Styling --- */
        .info-card {
            background: #f8fafc;
            padding: 16px;
            /* Reduced padding */
            border-radius: 8px;
            /* Slightly smaller radius */
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            /* Less intense shadow */
        }

        /* --- Card Title / Category Heading --- */
        .info-card h3 {
            font-size: 1rem;
            /* Smaller font size */
            font-weight: 600;
            color: #2d3748;
            margin-top: 0;
            margin-bottom: 12px;
            /* Reduced margin */
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            /* Reduced padding */
        }

        /* --- Detail Grid within each Card --- */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            /* Smaller min-width */
            gap: 12px 20px;
            /* Reduced gaps */
        }

        /* --- Detail Item Styling (Label & Value) --- */
        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-item .label {
            font-size: 0.7rem;
            /* Smaller font size */
            font-weight: 500;
            color: #718096;
            margin-bottom: 2px;
            /* Reduced margin */
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-item .value {
            font-size: 0.9rem;
            /* Smaller font size */
            font-weight: 600;
            color: #2d3748;
        }

        /* --- Special Full-Width Section for Chief Complain and Review --- */
        .info-card .full-width-section {
            display: flex;
            /* Forces to span all columns */
        }

        .info-card .full-width-section .value {
            /* background: #ffffff; */
            /* padding: 12px; */
            /* Reduced padding */
            /* border-radius: 6px; */
            /* Smaller radius */
            /* border: 1px solid #cbd5e0; */
            font-size: 0.85rem;
            /* Smaller font size */
            font-weight: 400;
            /* line-height: 1.5; */
            /* Reduced line height for compactness */
        }

        /* --- Status Badge Styling --- */
        .status-badge-container {
            display: flex;
            align-items: center;
        }

        .status-badge-container .label {
            margin-right: 8px;
            /* Reduced margin */
        }

        .status {
            display: inline-flex;
            padding: 4px 10px;
            /* Smaller padding */
            border-radius: 9999px;
            font-size: 0.65rem;
            /* Smaller font size */
            font-weight: 700;
            text-transform: uppercase;
            color: #ffffff;
            white-space: nowrap;
        }

        .status.visited {
            background: #047857;
        }

        .status.cancelled {
            background: #c53030;
        }

        .status.pending {
            background: #d69e2e;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <img src="../../assets/images/image.png" alt="Pro Physio Logo" class="logo" />
        </div>

        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="inquiry.html" class="active">Inquiry</a>
                <a href="#">Registration</a>
                <a href="#">Patients</a>
                <a href="#">Appointments</a>
                <a href="#">Billing</a>
                <a href="#">Attendance</a>
                <a href="#">Tests</a>
                <a href="#">Reports</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings">
                <?php echo $branchName; ?> Branch
            </div>
            <div class="icon-btn" id="theme-toggle">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </div>
            <div class="icon-btn icon-btn2" title="Notifications" onclick="openNotif()">🔔</div>
            <div class="profile" onclick="openForm()">S</div>
        </div>
    </header>
    <div class="menu" id="myMenu">
        <span class="closebtn" onclick="closeForm()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="#">Profile</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <div class="notification" id="myNotif">
        <span class="closebtn" onclick="closeNotif()">&times;</span>
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
                <div class="toggle-container">
                    <button id="quickBtn" class="toggle-btn active">Quick Inquiry</button>
                    <button id="testBtn" class="toggle-btn">Test Inquiry</button>
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
                    <tbody>
                        <?php if (!empty($quick_inquiries)): ?>
                            <?php foreach ($quick_inquiries as $row): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars((string) $row['name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['phone_number']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['age']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['gender']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['referralSource']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['chief_complain']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['review']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['expected_visit_date']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['created_at']) ?>
                                    </td>
                                    <td>
                                        <span class="pill <?= strtolower($row['status']) ?>">
                                            <?= htmlspecialchars((string) $row['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <select data-id="<?= $row['inquiry_id'] ?>" data-type="quick">
                                            <option <?= strtolower($row['status']) === 'visited' ? 'selected' : '' ?>>Visited</option>
                                            <option <?= strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            <option <?= strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                    </td>

                                    <td>
                                        <button class="action-btn open-drawer"
                                            data-id="<?= $row['inquiry_id'] ?>"
                                            data-type="quick">
                                            View
                                        </button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11">No Quick Inquiry found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Test Inquiry Table -->
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
                    <tbody>
                        <?php if (!empty($test_inquiries)): ?>
                            <?php foreach ($test_inquiries as $row): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars((string) $row['name']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['testname']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['reffered_by'] ?? '-') ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['mobile_number']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['expected_visit_date']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $row['created_at']) ?>
                                    </td>
                                    <td>
                                        <span class="pill <?= strtolower($row['status']) ?>">
                                            <?= htmlspecialchars((string) $row['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <select data-id="<?= $row['inquiry_id'] ?>" data-type="test">
                                            <option <?= strtolower($row['status']) === 'visited' ? 'selected' : '' ?>>Visited</option>
                                            <option <?= strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            <option <?= strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class="action-btn open-drawer"
                                            data-id="<?= $row['inquiry_id'] ?>"
                                            data-type="test">
                                            View
                                        </button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">No Test Inquiry found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        <div id="toast-container"></div>
    </main>

    <div id="rightDrawer" class="drawer">
        <div class="drawer-header">
            <h2 id="drawerTitle">Inquiry Details</h2>
            <span class="close-drawer">&times;</span>
        </div>
        <div class="drawer-body" id="drawerContent">
            <p>Loading...</p>
        </div>
    </div>


    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/inquiry.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const drawer = document.getElementById("rightDrawer");
            const drawerContent = document.getElementById("drawerContent");
            const drawerTitle = document.getElementById("drawerTitle");
            const closeBtn = document.querySelector(".close-drawer");

            // Open drawer on button click
            document.querySelectorAll(".open-drawer").forEach(btn => {
                btn.addEventListener("click", () => {
                    const inquiryId = btn.getAttribute("data-id");
                    const inquiryType = btn.getAttribute("data-type"); // quick or test

                    drawer.classList.add("open");

                    // Update drawer header
                    drawerTitle.textContent =
                        inquiryType === "quick" ? "Quick Inquiry Details" : "Test Inquiry Details";

                    // Show loading
                    drawerContent.innerHTML = `<p>Fetching details for ${inquiryType} inquiry ID: <b>${inquiryId}</b>...</p>`;

                    // Example fetch (replace with your backend endpoints)

                    fetch(`../api/fetch_inquiry.php?id=${inquiryId}&type=${inquiryType}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                let d = data.data;

                                if (inquiryType === "quick") {
                                    drawerContent.innerHTML = `
        <div class="inquiry-details">

    <div class="info-card">
        <h3>Personal Information</h3>
        <div class="card-grid">
            <div class="detail-item">
                <div class="label">Name:</div>
                <div class="value">${d.name}</div>
            </div>
            <div class="detail-item">
                <div class="label">Phone:</div>
                <div class="value">${d.phone_number}</div>
            </div>
            <div class="detail-item">
                <div class="label">Age:</div>
                <div class="value">${d.age}</div>
            </div>
            <div class="detail-item">
                <div class="label">Gender:</div>
                <div class="value">${d.gender}</div>
            </div>
            <div class="detail-item">
                <div class="label">Referral:</div>
                <div class="value">${d.referralSource}</div>
            </div>
            
        </div>

        <br>
            <h3>Medical Details</h3>
            <div class="card-grid">
            <div class="detail-item">
                <div class="label">Chief Complain:</div>
                <div class="value">${d.chief_complain}</div>
            </div>
            <div class="detail-item full-width-section">
                <div class="label">Review:</div>
                <div class="value">${d.review}</div>
            </div>
            </div>
    </div>


    <div class="info-card">
        <h3>Visit Details</h3>
        <div class="card-grid">
            <div class="detail-item">
                <div class="label">Expected Visit:</div>
                <div class="value">${d.expected_visit_date}</div>
            </div>
            <div class="detail-item">
                <div class="label">Created At:</div>
                <div class="value">${d.created_at}</div>
            </div>
            <div class="detail-item status-badge-container">
                <div class="label">Status:</div>
                <div class="value">
                    <span class="status ${d.status.toLowerCase()}">${d.status}</span>
                </div>
            </div>
        </div>
    </div>

</div>
    `;
                                } else {
                                    drawerContent.innerHTML = `
        <div class="inquiry-details">
            <div class="label">Name:</div><div class="value">${d.name}</div>
            <div class="label">Test Name:</div><div class="value">${d.testname}</div>
            <div class="label">Referred By:</div><div class="value">${d.reffered_by ?? '-'}</div>
            <div class="label">Mobile:</div><div class="value">${d.mobile_number}</div>
            <div class="label">Expected Visit:</div><div class="value">${d.expected_visit_date}</div>
            <div class="label">Status:</div>
                <div class="value"><span class="status ${d.status.toLowerCase()}">${d.status}</span></div>
            <div class="label">Created At:</div><div class="value">${d.created_at}</div>
        </div>
    `;
                                }
                            } else {
                                drawerContent.innerHTML = `<p class="error">${data.message}</p>`;
                            }
                        })
                        .catch(err => {
                            drawerContent.innerHTML = `<p class="error">Error: ${err}</p>`;
                        });

                });
            });

            // Close drawer
            closeBtn.addEventListener("click", () => {
                drawer.classList.remove("open");
            });

            // Optional: close on ESC key
            document.addEventListener("keydown", (e) => {
                if (e.key === "Escape") drawer.classList.remove("open");
            });
        });
    </script>

</body>

</html>