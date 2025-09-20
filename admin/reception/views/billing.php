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

    // Fetch branch name (safer method)
    $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmtBranch->execute(['branch_id' => $branchId]);
    $branchName = $stmtBranch->fetchColumn() ?? ''; // CHANGED: Safer fetch method

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
    <!-- <link rel="stylesheet" href="../css/patients.css"> -->
    <style>
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            /* bumped higher */
            display: none;
        }

        .drawer-panel {
            position: fixed;
            top: 10px;
            right: 20px;
            width: 650px;
            max-width: 100%;
            height: 98%;
            background-color: #ffffff;
            border-radius: 30px;
            box-shadow: -5px 0 20px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            /* start hidden */
            transition: transform 0.3s ease-in-out;
            z-index: 10000;
            /* above overlay */
        }

        .drawer-panel.is-open {
            transform: translateX(0);
            /* slide in */
        }


        /* Drawer body content */
        .drawer-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex-grow: 1;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Section heading inside drawer */
        .drawer-body h4 {
            margin: 0 0 1rem;
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
        }

        /* Payment list container */
        .payment-list {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* Payment Group Container */
        .payment-item {
            background: #fff;
            border-radius: 8px;
            padding: 15px 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .payment-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        /* Group Title */
        .payment-item>p strong {
            font-size: 1rem;
            color: #222;
            display: block;
            margin-top: 4px;
            margin-bottom: 8px;
            border-left: 4px solid #4f46e5;
            /* Indigo accent */
            padding-left: 8px;
        }

        /* Payment List */
        .payment-item ul {
            list-style: none;
            /* Remove bullets */
            margin: 0;
            padding: 0;
        }

        .payment-item ul li {
            padding: 6px 10px;
            margin-top: 14px;
            margin-bottom: 6px;
            border-radius: 6px;
            background: #f9fafb;
            font-size: 0.9rem;
            color: #444;
            border-left: 3px solid #e5e7eb;
        }

        /* Last item has no extra spacing */
        .payment-item ul li:last-child {
            margin-bottom: 0;
        }


        /* Title inside a payment item */
        .payment-item p strong {
            display: block;
            font-size: 1rem;
            color: #222;
            margin-bottom: 0.4rem;
        }

        /* Regular text */
        .payment-item p {
            margin: 0.2rem 0;
            font-size: 0.9rem;
            color: #555;
        }

        /* Dark mode support */
        .dark-mode .drawer-body h4 {
            color: #fff;
            border-bottom-color: #34495e;
        }

        .dark-mode .payment-item {
            background: #34495e;
            color: #ecf0f1;
        }

        .dark-mode .payment-item p strong {
            color: #fff;
        }

        .pill.active {
            background: #077341;
            color: #fff;
            padding: 10px 20px;
            border-radius: 12px;
        }

        .pill.inactive {
            background: #bc0505;
            color: #fff;
            padding: 10px 20px;
            border-radius: 12px;
        }

        .pill.completed {
            background: #077394;
            color: #fff;
            padding: 10px 20px;
            border-radius: 12px;
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
                <a href="appointments.php">Appointments</a>
                <a href="billing.php" class="active">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="#">Tests</a>
                <a href="#">Reports</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Settings"><?php echo htmlspecialchars($branchName, ENT_QUOTES, 'UTF-8'); ?> Branch</div>
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
            </ul>
        </div>
    </div>

    <main class="main">
        <div class="dashboard-container">
            <div class="top-bar">
                <h2>Billing Overview</h2>
            </div>
            <div class="table-container modern-table">
                <table>
                    <thead>
                        <tr>
                            <th>Patient ID</th>
                            <th>Patient Name</th>
                            <th class="numeric">Total Bill</th>
                            <th class="numeric">Total Paid</th>
                            <th class="numeric">Outstanding Due</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patients)) : ?>
                            <?php foreach ($patients as $row) : ?>
                                <?php
                                $total_billable = (float)$row['consultation_amount'] + (float)$row['treatment_total_amount'];
                                $total_paid = (float)$row['consultation_amount'] + (float)$row['total_paid_from_payments'];
                                $outstanding_due = $total_billable - $total_paid;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $row['patient_id']); ?></td>
                                    <td><?php echo htmlspecialchars((string) $row['patient_name']); ?></td>
                                    <td class="numeric">₹<?php echo number_format($total_billable, 2); ?></td>
                                    <td class="numeric">₹<?php echo number_format($total_paid, 2); ?></td>
                                    <td class="numeric"><strong>₹<?php echo number_format($outstanding_due, 2); ?></strong></td>
                                    <td>
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
                                    <td>
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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const drawerOverlay = document.getElementById("drawer-overlay");
            const drawerPanel = document.getElementById("drawer-panel");
            const drawerHeader = document.getElementById("drawer-patient-name");
            const drawerBody = document.getElementById("drawer-body");
            const closeDrawerButton = document.getElementById("closeDrawer");
            const viewButtons = document.querySelectorAll(".open-drawer"); // CHANGED: More specific selector

            const closeDrawer = () => {
                if (drawerPanel) drawerPanel.classList.remove('is-open');
                if (drawerOverlay) setTimeout(() => drawerOverlay.style.display = 'none', 300);
            };

            const openDrawerWithDetails = async (patientId) => {
                if (!patientId || !drawerOverlay) return;

                try {
                    drawerBody.innerHTML = '<p>Loading details...</p>';
                    drawerOverlay.style.display = 'block';
                    setTimeout(() => drawerPanel.classList.add('is-open'), 10);

                    // CHANGED: Using a root-relative path for reliability
                    const response = await fetch(`/proadmin/admin/reception/api/get_billing_details.php?id=${patientId}`);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        drawerHeader.textContent = data.patient_name || 'Billing Details';

                        let html = '<h4>Transaction History</h4><div class="payment-list">';

                        // Consultation always at top
                        html += `
        <div class="payment-item">
            <p><strong>Consultation Fee Paid</strong></p>
            <p>Amount: ₹${parseFloat(data.consultation_amount).toFixed(2)}</p>
        </div>
    `;

                        if (data.payments.length > 0) {
                            // 🔹 Group payments by type (p.remarks or status)
                            const grouped = {};
                            data.payments.forEach(p => {
                                const key = p.status || p.remarks || "Other";
                                if (!grouped[key]) grouped[key] = [];
                                grouped[key].push(p);
                            });

                            // 🔹 Render grouped payments
                            Object.keys(grouped).forEach(type => {
                                html += `
                <div class="payment-item">
                    <p><strong>${type}</strong></p>
                    <ul style="margin:0; padding-left:1.2rem; color:#555; font-size:0.9rem;">
                        ${grouped[type].map(p => `
                            <li>
                                Date: ${p.payment_date} | 
                                Amount: ₹${parseFloat(p.amount).toFixed(2)} | 
                                Mode: ${p.mode}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
                            });
                        } else {
                            html += '<p>No other payments have been recorded.</p>';
                        }

                        html += '</div>';
                        drawerBody.innerHTML = html;

                    } else {
                        drawerBody.innerHTML = `<p>Error: ${data.message}</p>`;
                    }

                } catch (error) {
                    console.error("Fetch error:", error);
                    drawerBody.innerHTML = '<p>Could not fetch patient details. Please try again.</p>';
                }
            };

            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const patientId = this.dataset.id;
                    openDrawerWithDetails(patientId);
                });
            });

            if (closeDrawerButton) closeDrawerButton.addEventListener('click', closeDrawer);
            if (drawerOverlay) drawerOverlay.addEventListener('click', (e) => {
                if (e.target === drawerOverlay) {
                    closeDrawer();
                }
            });
        });
    </script>

</body>

</html>