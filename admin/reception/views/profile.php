<?php


declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Role check:
if (!isset($_SESSION['uid'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/db.php';

$userId = $_SESSION['uid'];

// --- NEW: Fetch User Details ---
$userDetails = [];
try {
    // --- CORRECTED QUERY ---
    // A single query to join users, employees, and branches to get all required data.
    $stmtUser = $pdo->prepare("
        SELECT 
            u.username, u.role, u.created_at AS user_created_at,
            e.employee_id, e.first_name, e.last_name, e.job_title, e.phone_number, e.address, e.date_of_birth, e.date_of_joining, e.is_active, e.photo_path,
            b.branch_name, b.logo_primary_path, b.clinic_name, b.address_line_1, b.address_line_2, b.city, b.state, b.pincode, b.phone_primary, b.email AS branch_email
        FROM 
            users u
        LEFT JOIN 
            employees e ON u.id = e.user_id
        LEFT JOIN 
            branches b ON u.branch_id = b.branch_id
        WHERE 
            u.id = :user_id LIMIT 1");
    $stmtUser->execute([':user_id' => $userId]);
    $userDetails = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    // Log error but continue
    error_log("Could not fetch user details: " . $e->getMessage());
    // You might want to set an error message to display to the user
}
// --- END: Fetch User Details ---

// Set branch details from the unified query
$branchName = $userDetails['branch_name'] ?? 'Reception';
$branchDetails['logo_primary_path'] = $userDetails['logo_primary_path'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/dark.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <style>
        .main {
            padding: 2rem;
        }

        /* --- NEW: Modern Profile Page Layout --- */
        .profile-page-container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        .profile-header-modern {
            background: var(--bg-primary);
            border-radius: 16px;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color-primary);
        }

        .profile-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--bg-profile-gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 600;
            text-transform: uppercase;
            flex-shrink: 0;
        }
        
        /* --- NEW: Photo Upload Styles --- */
        .profile-avatar-container {
            position: relative;
            cursor: pointer;
        }
        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .upload-overlay {
            position: absolute;
            inset: 0;
            background-color: rgba(0,0,0,0.5);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .profile-avatar-container:hover .upload-overlay {
            opacity: 1;
        }

        .profile-header-info {
            flex-grow: 1;
        }

        .profile-header-info h2 {
            margin: 0;
            font-size: 2rem;
            color: var(--text-primary);
            font-weight: 700;
        }

        .profile-header-info p {
            margin: 4px 0 0;
            color: var(--text-secondary);
            font-size: 1rem;
            text-transform: capitalize;
        }

        .profile-header-actions .action-btn {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color-primary);
        }

        body.dark .profile-header-actions .action-btn {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        .profile-tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 2px solid var(--border-color-primary);
            margin-bottom: 2rem;
        }

        .profile-tab {
            padding: 0.75rem 1.25rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transform: translateY(2px);
            transition: color 0.2s, border-color 0.2s;
            text-decoration: none;
        }

        .profile-tab:hover {
            color: var(--text-primary);
        }

        .profile-tab.active {
            color: var(--color-primary);
            border-bottom-color: var(--color-primary);
        }

        .profile-content-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .detail-card {
            background: var(--bg-primary);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color-primary);
        }

        .detail-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: -0.5rem 0 1.5rem 0;
            border-bottom: 1px solid var(--border-color-primary);
            padding-bottom: 1rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .detail-card-title i {
            color: var(--color-primary);
        }

        .profile-details .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--bg-tertiary);
            font-size: 0.95rem;
        }

        .profile-details .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: var(--text-secondary);
            margin-right: 1rem;
        }

        .detail-value {
            color: var(--text-primary);
            text-align: right;
        }

        .detail-value.status-active {
            color: var(--color-success);
            font-weight: 600;
        }

        .detail-value.status-inactive {
            color: var(--color-error);
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .profile-content-area {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .profile-header-modern {
                flex-direction: column;
                text-align: center;
            }
            .profile-header-actions {
                margin-top: 1rem;
            }
            .main {
                padding: 1rem;
            }
            .detail-card {
                padding: 1.5rem;
            }
        }

        /* --- NEW: Toast Notification Styles --- */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow-md);
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            transform: translateX(100%);
            color: #ffffff;
            font-weight: 500;
        }
        .toast.success { background-color: var(--color-success); }
        .toast.error { background-color: var(--color-error); }
        .toast.info { background-color: var(--color-info); }
        .toast.show {
            opacity: 1;
            transform: translateX(0);
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
                    <img src="/admin/assets/images/image.png" alt="ProSpine Logo">
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
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php">Expenses</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" title="Branch"><?= htmlspecialchars($branchName) ?> Branch</div>
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
            <div class="profile" onclick="openForm()">
                <?= !empty($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'U' ?>
            </div>
        </div>
        <div class="hamburger-menu" id="hamburger-menu"><i class="fa-solid fa-bars"></i></div>
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

    <main class="main">
        <div class="profile-page-container">
            <?php
                $displayName = $userDetails['first_name'] ?? $userDetails['username'];
                $displayInitial = !empty($displayName) ? strtoupper(substr($displayName, 0, 1)) : '?';
                $fullName = trim(($userDetails['first_name'] ?? '') . ' ' . ($userDetails['last_name'] ?? ''));
                $photoPath = $userDetails['photo_path'] ?? null;
            ?>
            <!-- Modern Profile Header -->
            <div class="profile-header-modern">
                <div class="profile-avatar-container" onclick="document.getElementById('photo-upload-input').click();" title="Change Profile Photo">
                    <div class="profile-avatar-large">
                        <?php if ($photoPath): ?>
                            <img src="/admin/<?= htmlspecialchars($photoPath) ?>?v=<?= time() ?>" alt="Profile Photo">
                        <?php else: ?>
                            <?= htmlspecialchars($displayInitial) ?>
                        <?php endif; ?>
                    </div>
                    <div class="upload-overlay"><i class="fa-solid fa-camera fa-2x"></i></div>
                </div>
                <input type="file" id="photo-upload-input" accept="image/jpeg,image/png,image/gif" style="display: none;">

                <div class="profile-header-info">
                    <h2><?= !empty($fullName) ? htmlspecialchars($fullName) : htmlspecialchars($userDetails['username'] ?? 'N/A') ?></h2>
                    <p><?= htmlspecialchars($userDetails['job_title'] ?? ucfirst($userDetails['role'] ?? 'N/A')) ?></p>
                </div>
                <!-- <div class="profile-header-actions">
                    <button class="action-btn" onclick="window.location.href='#'"><i class="fa-solid fa-pencil"></i> Edit Profile</button>
                </div> -->
            </div>

            <!-- Tabs -->
            <!-- <div class="profile-tabs">
                <a href="profile.php" class="profile-tab active">Overview</a>
                <a href="#" class="profile-tab">Settings</a>
            </div> -->

            <!-- Tab Content -->
            <div class="profile-content-area">
                <!-- Left Column -->
                <div class="detail-card">
                    <h3 class="detail-card-title"><i class="fa-solid fa-id-card"></i> Profile Information</h3>
                    <div class="profile-details">
                        <div class="detail-item"><span class="detail-label">Full Name</span> <span class="detail-value"><?= !empty($fullName) ? htmlspecialchars($fullName) : 'Not Set' ?></span></div>
                        <div class="detail-item"><span class="detail-label">Employee ID</span> <span class="detail-value"><?= htmlspecialchars((string)($userDetails['employee_id'] ?? 'N/A')) ?></span></div>
                        <div class="detail-item"><span class="detail-label">Phone Number</span> <span class="detail-value"><?= htmlspecialchars($userDetails['phone_number'] ?? 'Not set') ?></span></div>
                        <div class="detail-item"><span class="detail-label">Address</span> <span class="detail-value"><?= htmlspecialchars($userDetails['address'] ?? 'Not set') ?></span></div>
                        <div class="detail-item"><span class="detail-label">Date of Birth</span> <span class="detail-value"><?= !empty($userDetails['date_of_birth']) ? date('d M Y', strtotime($userDetails['date_of_birth'])) : 'Not set' ?></span></div>
                        <div class="detail-item"><span class="detail-label">Date of Joining</span> <span class="detail-value"><?= !empty($userDetails['date_of_joining']) ? date('d M Y', strtotime($userDetails['date_of_joining'])) : 'N/A' ?></span></div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <div class="detail-card">
                        <h3 class="detail-card-title"><i class="fa-solid fa-shield-halved"></i> Account & Security</h3>
                        <div class="profile-details">
                            <div class="detail-item"><span class="detail-label">Username</span> <span class="detail-value"><?= htmlspecialchars($userDetails['username'] ?? 'N/A') ?></span></div>
                            <div class="detail-item"><span class="detail-label">Role</span> <span class="detail-value"><?= htmlspecialchars(ucfirst($userDetails['role'] ?? 'N/A')) ?></span></div>
                            <div class="detail-item"><span class="detail-label">Account Created</span> <span class="detail-value"><?= !empty($userDetails['user_created_at']) ? date('d M Y', strtotime($userDetails['user_created_at'])) : 'N/A' ?></span></div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <?php if (isset($userDetails['is_active'])): ?>
                                    <span class="detail-value <?= $userDetails['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $userDetails['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="detail-value">N/A</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h3 class="detail-card-title"><i class="fa-solid fa-hospital"></i> Branch Information</h3>
                        <div class="profile-details">
                            <div class="detail-item"><span class="detail-label">Branch</span> <span class="detail-value"><?= htmlspecialchars($userDetails['branch_name'] ?? 'N/A') ?></span></div>
                            <div class="detail-item"><span class="detail-label">Clinic</span> <span class="detail-value"><?= htmlspecialchars($userDetails['clinic_name'] ?? 'N/A') ?></span></div>
                            <div class="detail-item"><span class="detail-label">Contact</span> <span class="detail-value"><?= htmlspecialchars($userDetails['phone_primary'] ?? 'N/A') ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="toast-container"></div>
    <script src="../js/theme.js"></script>
    <script src="../js/nav_toggle.js"></script>
    <script>
        // Simplified popup JS for this page
        function openForm() {
            document.getElementById("myMenu").style.display = "block";
        }

        function closeForm() {
            document.getElementById("myMenu").style.display = "none";
        }

        // --- NEW: showToast function definition ---
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById("toast-container");
            if (!toastContainer) {
                console.error("Toast container not found.");
                return;
            }
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.textContent = message;
            toastContainer.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => { if (toastContainer.contains(toast)) { toastContainer.removeChild(toast); } }, 500);
            }, 5000);
        }

        // --- NEW: Photo Upload JavaScript ---
        document.addEventListener('DOMContentLoaded', function() {
            const photoInput = document.getElementById('photo-upload-input');
            if (!photoInput) return;

            photoInput.addEventListener('change', async function(event) {
                const file = event.target.files[0];
                if (!file) return;

                // Optional: Client-side size check
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    showToast('File is too large. Maximum size is 5MB.', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('profile_photo', file);

                showToast('Uploading photo...', 'info');

                try {
                    const response = await fetch('../api/upload_employee_photo.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        showToast(result.message, 'success');
                        // Update the image on the page without a full reload
                        const avatarDiv = document.querySelector('.profile-avatar-large');
                        avatarDiv.innerHTML = `<img src="${result.filePath}?v=${new Date().getTime()}" alt="Profile Photo">`;
                    } else {
                        throw new Error(result.message || 'Upload failed.');
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    showToast(error.message, 'error');
                } finally {
                    // Clear the input value to allow re-uploading the same file if needed
                    photoInput.value = '';
                }
            });
        });
    </script>
</body>

</html>