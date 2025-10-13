<?php

declare(strict_types=1);
session_start();

// Error Reporting (Dev Only)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Auth / Session Checks
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
$branchId = $_SESSION['branch_id'] ?? null;

if (! $branchId) {
    http_response_code(403);
    exit('Branch not assigned.');
}

try {
    // Appointments
    $stmt = $pdo->prepare("
        SELECT 
            id,
            consultationType,
            fullName,
            phone,
            gender,
            age,
            medical_condition,
            occupation,
            conditionType,
            contactMethod,
            created_at,
            status
        FROM appointments
        WHERE branch_id = :branch_id
        ORDER BY created_at DESC
    ");
    $stmt->execute([':branch_id' => $branchId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

   // Branch name
    $stmtBranch = $pdo->prepare("SELECT * FROM branches WHERE branch_id = :branch_id LIMIT 1");
    $stmtBranch->execute([':branch_id' => $branchId]);
    $branchDetails = $stmtBranch->fetch(PDO::FETCH_ASSOC);
    $branchName = $branchDetails['branch_name'];
} catch (PDOException $e) {
    die("Error fetching appointments: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <style>
        .pill.confirmed {
            background: #077394;
            color: #fff;
            padding: 10px 20px;
            border-radius: 12px;
        }

        .pill.completed {
            background: #077341;
            color: #fff;
            padding: 10px 20px;
            border-radius: 12px;
        }

        .pill.discarded {
            background: #bc0505;
            color: #fff;
            padding: 10px 20px;
            border-radius: 12px;
        }

        .pill.contacted {
            background: #9c4503;
            color: #fff;
            padding: 10px 20px;
            border-radius: 12px;
        }

        .toggle-container {
            width: auto;
        }

        .drawer {
            position: fixed;
            top: 15px;
            right: -620px;
            /* hidden by default */
            width: 600px;
            height: 96%;
            background: #fff;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2);
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }

        body.dark .drawer {
            background-color: var(--accent-color);
        }

        .drawer.open {
            right: 0;
        }

        .drawer .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }


        .drawer .header h2 {
            border-bottom: none;
        }

        body.dark .drawer .header h2 {
            color: var(--text-color);
        }

        .drawer-content {
            padding: 20px;
        }

        body.dark .drawer-content {
            background-color: var(--card-bg3);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            /* float: right; */
            color: #000;
        }

        /* Drawer Header */
        #drawer h2 {
            margin-bottom: 15px;
            font-size: 20px;
            padding-bottom: 8px;
            /* color: #333; */
        }

        /* Grid Layout */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 12px;
        }

        /* Info Cards */
        .info-card {
            background: #f9f9f9;
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .info-card span {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #555;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        body.dark .info-card span {
            color: #999;
        }

        .info-card p {
            font-size: 14px;
            margin: 0;
            color: #222;
            word-wrap: break-word;
        }

        .wrapper {
            display: flex;
            width: auto;
            gap: 10px;
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
                <a href="inquiry.php" class="active">Inquiry</a>
                <a href="registration.php">Registration</a>
                <a href="appointments.php">Appointments</a>
                <a href="patients.php">Patients</a>
                <a href="billing.php">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"><?php echo $branchName; ?> Branch</div>
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
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
                <div class="wrapper">
                    <div class="toggle-container">
                        <button class="toggle-btn" onclick="window.location.href = 'inquiry.php';">Quick Inquiry</button>
                        <button id="testBtn" class="toggle-btn" onclick="window.location.href = 'inquiry.php';">Test Inquiry</button>
                    </div>
                    <div class="toggle-container">
                        <button class="toggle-btn" onclick="window.location.href = 'online_inquiry.php';">Online Inquiry</button>
                        <button class="toggle-btn active" onclick="window.location.href = 'online_inquiry_booked.php';">Online Inquiry Booked</button>
                    </div>
                </div>
            </div>

            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Condition</th>
                            <th>Occupation</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Update Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($appointments)): ?>
                            <?php foreach ($appointments as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['id']) ?></td>
                                    <td><?php echo htmlspecialchars($row['consultationType']) ?></td>
                                    <td><?php echo htmlspecialchars($row['fullName']) ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']) ?></td>
                                    <td><?php echo htmlspecialchars($row['gender']) ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['age']) ?></td>
                                    <td><?php echo htmlspecialchars($row['medical_condition']) ?></td>
                                    <td><?php echo htmlspecialchars($row['occupation']) ?></td>
                                    <td><?php echo htmlspecialchars($row['conditionType']) ?></td>
                                    <td><?php echo htmlspecialchars($row['contactMethod']) ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']) ?></td>
                                    <td>
                                        <span class="pill <?php echo strtolower($row['status']) ?>">
                                            <?php echo htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select data-id="<?php echo $row['id'] ?>" data-type="test">
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option <?php echo strtolower($row['status']) === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option <?php echo strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            <option <?php echo strtolower($row['status']) === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class="action-btn open-drawer" data-id="<?php echo (int) $row['id']; ?>">View</button>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13">No Appointments found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="toast-container"></div>
    </main>

    <div id="drawer" class="drawer">
        <div class="drawer-content">
            <div class="header">
                <h2>Appointment Information</h2>
                <button id="closeDrawer" class="close-btn">&times;</button>
            </div>
            <div id="drawer-body">
                <!-- Appointment details will load here -->
            </div>
        </div>
    </div>


    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        // Toast helper
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerText = message;

            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll("table select").forEach(select => {
                select.addEventListener("change", async function() {
                    const id = this.dataset.id;
                    const type = this.dataset.type;
                    const status = this.value;

                    try {
                        const res = await fetch("../api/update_appointments_status.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: new URLSearchParams({
                                id,
                                type,
                                status
                            })
                        });

                        const data = await res.json();
                        if (data.success) {
                            const pill = this.closest("tr").querySelector(".pill");
                            pill.textContent = status;
                            pill.className = "pill " + status.toLowerCase();
                            showToast("Status updated to " + status, "success");
                        } else {
                            showToast(data.message || "Update failed", 'error');
                        }
                    } catch (err) {
                        console.error("Error:", err);
                        showToast("Network error", 'error');
                    }
                });
            });

            const drawer = document.getElementById("drawer");
            const drawerBody = document.getElementById("drawer-body");
            const closeDrawer = document.getElementById("closeDrawer");

            // Open drawer on button click
            document.querySelectorAll(".open-drawer").forEach(btn => {
                btn.addEventListener("click", async () => {
                    const id = btn.dataset.id;

                    // Load appointment details via AJAX (dummy example)
                    try {
                        const res = await fetch("../api/get_appointment_details.php?id=" + id);
                        const data = await res.json();

                        drawerBody.innerHTML = `
                    <div class="card-grid">
        <div class="info-card"><span>ID</span><p>${data.id}</p></div>
        <div class="info-card"><span>Patient ID</span><p>${data.patient_id}</p></div>
        <div class="info-card"><span>Branch ID</span><p>${data.branch_id}</p></div>
        <div class="info-card"><span>Consultation Type</span><p>${data.consultationType}</p></div>
        <div class="info-card"><span>Full Name</span><p>${data.fullName}</p></div>
        <div class="info-card"><span>Email</span><p>${data.email}</p></div>
        <div class="info-card"><span>Phone</span><p>${data.phone}</p></div>
        <div class="info-card"><span>Gender</span><p>${data.gender}</p></div>
        <div class="info-card"><span>Date of Birth</span><p>${data.dob}</p></div>
        <div class="info-card"><span>Age</span><p>${data.age}</p></div>
        <div class="info-card"><span>Occupation</span><p>${data.occupation}</p></div>
        <div class="info-card"><span>Address</span><p>${data.address}</p></div>
        <div class="info-card"><span>Medical Condition</span><p>${data.medical_condition}</p></div>
        <div class="info-card"><span>Condition Type</span><p>${data.conditionType}</p></div>
        <div class="info-card"><span>Referral Source</span><p>${data.referralSource}</p></div>
        <div class="info-card"><span>Contact Method</span><p>${data.contactMethod}</p></div>
        <div class="info-card"><span>Location</span><p>${data.location}</p></div>
        <div class="info-card"><span>Created At</span><p>${data.created_at}</p></div>
        <div class="info-card"><span>Status</span><p>${data.status}</p></div>
        <div class="info-card"><span>Payment Status</span><p>${data.payment_status}</p></div>
        <div class="info-card"><span>Payment Amount</span><p>${data.payment_amount}</p></div>
        <div class="info-card"><span>Payment Method</span><p>${data.payment_method}</p></div>
        <div class="info-card"><span>Payment Date</span><p>${data.payment_date}</p></div>
        <div class="info-card"><span>Transaction ID</span><p>${data.transaction_id}</p></div>
    </div>
                `;
                    } catch (err) {
                        drawerBody.innerHTML = "<p>Error loading details.</p>";
                    }

                    drawer.classList.add("open");
                });
            });

            // Close drawer
            closeDrawer.addEventListener("click", () => {
                drawer.classList.remove("open");
            });
        });
    </script>
</body>

</html>