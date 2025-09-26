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
if (!$branchId) {
    http_response_code(403);
    exit('Branch not assigned.');
}

try {
    // Appointments Requests
    $stmtQuick = $pdo->prepare("
        SELECT id, fullName, phone, location, created_at, status
        FROM appointment_requests
        WHERE branch_id = :branch_id
        ORDER BY created_at DESC
    ");
    $stmtQuick->execute([':branch_id' => $branchId]);
    $appointment_requests = $stmtQuick->fetchAll(PDO::FETCH_ASSOC);

    // branch name
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
    <title>Appointments</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="../css/inquiry.css">

    <style>
        .pill.new {
            background: #077394ff;
            color: #fff;
            padding: 10px 20px;
        }

        .pill.converted {
            background: #077341ff;
            color: #fff;
            padding: 10px 20px;
        }

        .pill.discarded {
            background: #bc0505ff;
            color: #fff;
            padding: 10px 20px;
        }

        .pill.contacted {
            background: #9c4503ff;
            color: #fff;
            padding: 10px 20px;
        }

        .toggle-container {
            width: auto;
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
                <a href="inquiry.php">Inquiry</a>
                <a href="registration.php">Registration</a>
                <a href="patients.php">Patients</a>
                <a href="appointments.php" class="active">Appointments</a>
                <a href="billing.php">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"><?php echo $branchName; ?> Branch</div>
            <div class="icon-btn" id="theme-toggle">
                <i id="theme-icon" class="fa-solid fa-moon"></i>
            </div>
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
                <h2>Appointments Requests</h2>
                <div class="toggle-container">
                    <button class="toggle-btn active">Appointments Requests</button>
                    <button class="toggle-btn" onclick="window.location.href = 'appointmentss.php';">Appointments Booked</button>
                </div>
            </div>

            <!-- Appointments Requests Table -->
            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($appointment_requests)): ?>
                            <?php foreach ($appointment_requests as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['fullName']) ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['phone']) ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['location']) ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['created_at']) ?></td>
                                    <td>
                                        <span class="pill <?php echo strtolower($row['status']) ?>">
                                            <?php echo htmlspecialchars((string) $row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select data-id="<?php echo $row['id'] ?>" data-type="quick">
                                            <option <?php echo strtolower($row['status']) === 'new' ? 'selected' : '' ?>>New</option>
                                            <option <?php echo strtolower($row['status']) === 'contacted' ? 'selected' : '' ?>>Contacted</option>
                                            <option <?php echo strtolower($row['status']) === 'converted' ? 'selected' : '' ?>>Converted</option>
                                            <option <?php echo strtolower($row['status']) === 'discarded' ? 'selected' : '' ?>>Discarded</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No Appointments Requests found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div id="toast-container"></div>
    </main>

    <script src="../js/theme.js"></script>
    <!-- <script src="../js/dashboard.js"></script> -->
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
        });
    </script>
</body>

</html>