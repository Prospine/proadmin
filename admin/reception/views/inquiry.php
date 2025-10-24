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

    // --- Pagination Setup ---
    $records_per_page = 15;

    // --- Quick Inquiry Pagination & Data ---
    $page_quick = isset($_GET['page_quick']) ? (int)$_GET['page_quick'] : 1;
    $offset_quick = ($page_quick - 1) * $records_per_page;

    // Get total count for quick inquiries
    $stmtTotalQuick = $pdo->prepare("SELECT COUNT(*) FROM quick_inquiry WHERE branch_id = :branch_id");
    $stmtTotalQuick->execute([':branch_id' => $branchId]);
    $total_records_quick = (int)$stmtTotalQuick->fetchColumn();
    $total_pages_quick = ceil($total_records_quick / $records_per_page);

    // Fetch paginated quick inquiries
    $stmtQuick = $pdo->prepare("
        SELECT inquiry_id, name, phone_number, age, gender, referralSource, chief_complain, review, expected_visit_date, created_at, status
        FROM quick_inquiry
        WHERE branch_id = :branch_id
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmtQuick->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
    $stmtQuick->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $stmtQuick->bindValue(':offset', $offset_quick, PDO::PARAM_INT);
    $stmtQuick->execute();
    $quick_inquiries = $stmtQuick->fetchAll(PDO::FETCH_ASSOC);

    // --- Test Inquiry Pagination & Data ---
    $page_test = isset($_GET['page_test']) ? (int)$_GET['page_test'] : 1;
    $offset_test = ($page_test - 1) * $records_per_page;

    // Get total count for test inquiries
    $stmtTotalTest = $pdo->prepare("SELECT COUNT(*) FROM test_inquiry WHERE branch_id = :branch_id");
    $stmtTotalTest->execute([':branch_id' => $branchId]);
    $total_records_test = (int)$stmtTotalTest->fetchColumn();
    $total_pages_test = ceil($total_records_test / $records_per_page);

    // Fetch paginated test inquiries
    $stmtTest = $pdo->prepare("
        SELECT inquiry_id, name, testname, reffered_by, mobile_number, expected_visit_date, created_at, status
        FROM test_inquiry
        WHERE branch_id = :branch_id
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmtTest->bindValue(':branch_id', $branchId, PDO::PARAM_INT);
    $stmtTest->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $stmtTest->bindValue(':offset', $offset_test, PDO::PARAM_INT);
    $stmtTest->execute();
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
    <title>Inquiry - ProAdmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script src="https://cdn.tailwindcss.com/forms@0.5.7/plugin.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <!-- <link rel="stylesheet" href="../css/inquiry.css"> -->

    <style>
        /* Custom scrollbar for WebKit browsers */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .dark ::-webkit-scrollbar-track {
            background: #2d3748;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #555;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #777;
        }

        /* Pill status styles */
        .pill {
            padding: 0.25rem 0.75rem;
            /* py-1 px-3 */
            font-size: 0.75rem;
            /* text-xs */
            font-weight: 600;
            /* font-semibold */
            border-radius: 9999px;
            /* rounded-full */
            white-space: nowrap;
        }

        .pill.pending {
            background-color: #fef9c3;
            /* bg-yellow-100 */
            color: #92400e;
            /* text-yellow-800 */
        }

        .pill.visited {
            background-color: #dcfce7;
            /* bg-green-100 */
            color: #166534;
            /* text-green-800 */
        }

        .pill.cancelled {
            background-color: #fee2e2;
            /* bg-red-100 */
            color: #991b1b;
            /* text-red-800 */
        }

        .dark .pill.pending {
            background-color: rgba(74, 58, 20, 0.5);
            /* dark:bg-yellow-900/50 */
            color: #fde047;
            /* dark:text-yellow-300 */
        }

        .dark .pill.visited {
            background-color: rgba(22, 78, 58, 0.5);
            /* dark:bg-green-900/50 */
            color: #86efac;
            /* dark:text-green-300 */
        }

        .dark .pill.cancelled {
            background-color: rgba(76, 29, 29, 0.5);
            /* dark:bg-red-900/50 */
            color: #fca5a5;
            /* dark:text-red-300 */
        }

        /* Responsive Table to Cards */
        @media (max-width: 1024px) {
            .responsive-table thead {
                display: none;
            }

            .responsive-table tr {
                display: block;
                margin-bottom: 1rem;
                border-radius: 0.75rem;
                border: 1px solid #e5e7eb;
                /* border-gray-200 */
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
                /* shadow-md */
                background-color: #ffffff;
            }

            .dark .responsive-table tr {
                border-color: #374151;
                /* dark:border-gray-700 */
                background-color: #1f2937;
                /* dark:bg-gray-800 */
            }

            .responsive-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #f3f4f6;
                /* border-gray-100 */
            }

            .dark .responsive-table td {
                border-bottom-color: #374151;
                /* dark:border-gray-700 */
            }

            .responsive-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #6b7280;
                /* text-gray-500 */
            }

            .dark .responsive-table td::before {
                color: #9ca3af;
                /* dark:text-gray-400 */
            }

            .responsive-table td:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>

<body class="bg-gray-100 dark:bg-gray-900 font-sans">
    <!-- Mobile Blocker Overlay -->
    <div class="mobile-blocker md:hidden fixed inset-0 bg-gray-100 dark:bg-gray-900 z-[1000] flex items-center justify-center p-6">
        <div class="mobile-blocker-popup bg-white dark:bg-gray-800 p-8 rounded-lg shadow-2xl text-center max-w-sm">
            <i class="fa-solid fa-mobile-screen-button popup-icon text-5xl text-teal-600 dark:text-teal-400 mb-4"></i>
            <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">Mobile View Not Supported</h2>
            <p class="text-gray-600 dark:text-gray-300 mb-6">The admin panel is designed for desktop use. For the best experience on your mobile device, please download our dedicated application.</p>
            <a href="/download-app/index.html" class="mobile-download-btn inline-flex items-center justify-center gap-2 px-6 py-3 bg-teal-600 text-white font-semibold rounded-lg shadow-md hover:bg-teal-700 transition-all">
                <i class="fa-solid fa-download"></i> Download App
            </a>
        </div>
    </div>
    <header class="flex items-center justify-between h-26 px-4 md:px-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="logo-container flex items-center">
            <div class="logo h-30 flex items-center">
                <?php if (!empty($branchDetails['logo_primary_path'])): ?>
                    <img src="/admin/<?= htmlspecialchars($branchDetails['logo_primary_path']) ?>" alt="Primary Clinic Logo" class="h-20 w-30">
                <?php else: ?>
                    <div class="logo-placeholder text-sm font-semibold text-gray-500 dark:text-gray-400">Primary Logo N/A</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="hamburger-menu md:hidden" id="hamburger-menu">
            <button class="flex items-center justify-center w-10 h-10 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
        </div>

        <nav class="hidden lg:flex items-center gap-1">
            <a href="dashboard.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-tachometer-alt w-4 text-center"></i><span>Dashboard</span></a>
            <a href="inquiry.php" class="active flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium bg-teal-50 dark:bg-teal-900/50 text-teal-600 dark:text-teal-400"><i class="fa-solid fa-magnifying-glass w-4 text-center"></i><span>Inquiry</span></a>
            <a href="registration.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-user-plus w-4 text-center"></i><span>Registration</span></a>
            <a href="appointments.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-calendar-check w-4 text-center"></i><span>Appointments</span></a>
            <a href="patients.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-users w-4 text-center"></i><span>Patients</span></a>
            <a href="billing.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-file-invoice-dollar w-4 text-center"></i><span>Billing</span></a>
            <a href="attendance.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-user-check w-4 text-center"></i><span>Attendance</span></a>
            <a href="tests.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-vial w-4 text-center"></i><span>Tests</span></a>
            <a href="reports.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-chart-line w-4 text-center"></i><span>Reports</span></a>
            <a href="expenses.php" class="flex items-center gap-2 px-3 py-2 rounded-md text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"><i class="fa-solid fa-money-bill-wave w-4 text-center"></i><span>Expenses</span></a>
        </nav>

        <div class="nav-actions flex items-center gap-2">
            <div class="icon-btn hidden md:flex items-center justify-center px-3 py-1.5 rounded-full text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/50 text-sm font-medium" title="Branch"><?php echo $branchName; ?> Branch</div>
            <button class="icon-btn flex items-center justify-center w-9 h-9 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></button>
            <button class="icon-btn icon-btn2 flex items-center justify-center w-9 h-9 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" title="Notifications" onclick="openNotif()"><i class="fa-solid fa-bell"></i></button>
            <button class="profile flex items-center justify-center w-9 h-9 rounded-full bg-teal-600 text-white font-semibold cursor-pointer hover:bg-teal-700 transition-all" onclick="openForm()">R</button>
            <button id="menuBtn" class="lg:hidden text-gray-700 dark:text-gray-300 hover:text-teal-600 focus:outline-none">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
        </div>
    </header>
    <!-- Drawer Navigation -->
    <div id="drawerNav" class="fixed top-0 right-0 h-full w-64 bg-white dark:bg-gray-800 shadow-2xl transform translate-x-full transition-transform duration-300 z-[100]">
        <div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2"><i class="fa-solid fa-bars text-teal-500"></i> Navigation</h2>
            <button id="closeBtn" class="text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
        </div>
        <nav class="flex flex-col p-4 space-y-1 text-gray-700 dark:text-gray-300 font-medium"></nav>
    </div>
    <div id="drawer-overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[99] hidden"></div>

    <div class="menu hidden fixed top-16 right-4 md:right-6 w-48 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-[101]" id="myMenu">
        <div class="p-1">
            <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"><i class="fa-solid fa-user-circle w-4 text-center"></i> Profile</a>
            <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/50 rounded-md"><i class="fa-solid fa-sign-out-alt w-4 text-center"></i> Logout</a>
        </div>
    </div>

    <div class="notification hidden fixed top-16 right-4 md:right-20 w-64 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-[101]" id="myNotif">
        <div class="p-2">
            <a href="changelog.html" class="active2 flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">View Changes (1)</a>
        </div>
    </div>
    <main class="main p-4 md:p-6">
        <div class="dashboard-container bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="top-bar flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                <h2 class="text-xl lg:text-2xl font-bold text-gray-800 dark:text-white">Inquiry Management</h2>

                <div class="wrapper flex flex-col lg:flex-row items-stretch lg:items-center gap-4 w-full lg:w-auto">
                    <!-- NEW: Filter and Search Bar -->
                    <div class="filter-bar flex-grow flex flex-wrap items-center gap-2">
                        <div class="search-container relative w-full md:w-64">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="searchInput" placeholder="Search..." class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                        </div>

                        <!-- Filters for Quick Inquiry Table -->
                        <div id="quickInquiryFilters" class="filter-options flex gap-2">
                            <select id="quickStatusFilter" class="text-sm p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none dark:text-white">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="visited">Visited</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Filters for Test Inquiry Table -->
                        <div id="testInquiryFilters" class="filter-options hidden">
                            <select id="testStatusFilter" class="text-sm p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none dark:text-white">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="visited">Visited</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="tog flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <div class="toggle-container relative flex bg-gray-100 dark:bg-gray-700 p-1 rounded-lg w-64 ">
                            <div class="slider-indicator absolute top-1 left-1 h-[calc(100%-8px)] w-[calc(50%-4px)] bg-white/30 dark:bg-gray-800/30 rounded-md shadow-sm transition-transform duration-300 ease-in-out"></div>
                            <button id="quickBtn" class="toggle-btn flex-1 px-3 py-1.5 text-sm font-medium rounded-md bg-white dark:bg-gray-800 shadow text-gray-800 dark:text-white">Quick Inquiry</button>
                            <button id="testBtn" class="toggle-btn flex-1 px-3 py-1.5 text-sm font-medium rounded-md text-gray-500 dark:text-gray-400">Test Inquiry</button>
                        </div>

                        <div class="toggle-container flex bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                            <button class="toggle-btn flex-1 px-3 py-1.5 text-sm font-medium rounded-md text-gray-500 dark:text-gray-400" onclick="window.location.href = 'online_inquiry.php';">Online</button>
                            <button class="toggle-btn flex-1 px-3 py-1.5 text-sm font-medium rounded-md text-gray-500 dark:text-gray-400" onclick="window.location.href = 'online_inquiry_booked.php';">Booked</button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Quick Inquiry Table -->
            <div id="quickTable" class="table-container overflow-x-auto">
                <table class="responsive-table w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Name</th>
                            <th scope="col" class="px-6 py-3">Phone</th>
                            <th scope="col" class="px-6 py-3">Age</th>
                            <th scope="col" class="px-6 py-3">Gender</th>
                            <th scope="col" class="px-6 py-3">Referral</th>
                            <th scope="col" class="px-6 py-3">Complain</th>
                            <th scope="col" class="px-6 py-3">Expected Visit</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Update Status</th>
                            <th scope="col" class="px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($quick_inquiries)): ?>
                            <?php foreach ($quick_inquiries as $row): ?>
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td data-label="Name" class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars((string) $row['name']) ?></td>
                                    <td data-label="Phone" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['phone_number']) ?></td>
                                    <td data-label="Age" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['age']) ?></td>
                                    <td data-label="Gender" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['gender']) ?></td>
                                    <td data-label="Referral" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['referralSource']) ?></td>
                                    <td data-label="Complain" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['chief_complain']) ?></td>
                                    <td data-label="Expected Visit" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['expected_visit_date']) ?></td>
                                    <td data-label="Status" class="px-6 py-4"><span class="pill <?php echo strtolower($row['status']) ?>"><?php echo htmlspecialchars((string) $row['status']) ?></span></td>
                                    <td data-label="Update Status" class="px-6 py-4">
                                        <select data-id="<?php echo $row['inquiry_id'] ?>" data-type="quick" class="text-sm p-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                                            <option <?php echo strtolower($row['status']) === 'visited' ? 'selected' : '' ?>>Visited</option>
                                            <option <?php echo strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                    </td>
                                    <td data-label="Action" class="px-6 py-4">
                                        <button class="open-drawer px-3 py-1.5 text-xs font-medium text-white bg-teal-600 rounded-md hover:bg-teal-700" data-id="<?php echo $row['inquiry_id'] ?>" data-type="quick">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="bg-white dark:bg-gray-800">
                                <td colspan="10" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No Quick Inquiry found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Quick Inquiry Pagination -->
            <div id="quickPagination" class="flex justify-between items-center mt-4">
                <span class="text-sm text-gray-700 dark:text-gray-400">
                    Showing <span class="font-semibold text-gray-900 dark:text-white"><?= $offset_quick + 1 ?></span> to <span class="font-semibold text-gray-900 dark:text-white"><?= min($offset_quick + $records_per_page, $total_records_quick) ?></span> of <span class="font-semibold text-gray-900 dark:text-white"><?= $total_records_quick ?></span> Entries
                </span>
                <div class="inline-flex -space-x-px text-sm h-8">
                    <a href="?page_quick=<?= max(1, $page_quick - 1) ?>" class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white <?= $page_quick <= 1 ? 'pointer-events-none opacity-50' : '' ?>">Previous</a>
                    <?php for ($i = 1; $i <= $total_pages_quick; $i++): ?>
                        <a href="?page_quick=<?= $i ?>" class="flex items-center justify-center px-3 h-8 leading-tight <?= $i == $page_quick ? 'text-blue-600 border border-blue-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page_quick=<?= min($total_pages_quick, $page_quick + 1) ?>" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white <?= $page_quick >= $total_pages_quick ? 'pointer-events-none opacity-50' : '' ?>">Next</a>
                </div>
            </div>

            <!-- Test Inquiry Table -->
            <div id="testTable" class="table-container overflow-x-auto hidden">
                <table class="responsive-table w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Name</th>
                            <th scope="col" class="px-6 py-3">Test Name</th>
                            <th scope="col" class="px-6 py-3">Referred By</th>
                            <th scope="col" class="px-6 py-3">Mobile</th>
                            <th scope="col" class="px-6 py-3">Expected Visit</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Update Status</th>
                            <th scope="col" class="px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($test_inquiries)): ?>
                            <?php foreach ($test_inquiries as $row): ?>
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td data-label="Name" class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars((string) $row['name']) ?></td>
                                    <td data-label="Test Name" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['testname']) ?></td>
                                    <td data-label="Referred By" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['reffered_by'] ?? '-') ?></td>
                                    <td data-label="Mobile" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['mobile_number']) ?></td>
                                    <td data-label="Expected Visit" class="px-6 py-4"><?php echo htmlspecialchars((string) $row['expected_visit_date']) ?></td>
                                    <td data-label="Status" class="px-6 py-4"><span class="pill <?php echo strtolower($row['status']) ?>"><?php echo htmlspecialchars((string) $row['status']) ?></span></td>
                                    <td data-label="Update Status" class="px-6 py-4">
                                        <select data-id="<?php echo $row['inquiry_id'] ?>" data-type="test" class="text-sm p-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                                            <option <?php echo strtolower($row['status']) === 'visited' ? 'selected' : '' ?>>Visited</option>
                                            <option <?php echo strtolower($row['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            <option <?php echo strtolower($row['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                    </td>
                                    <td data-label="Action" class="px-6 py-4">
                                        <button class="open-drawer px-3 py-1.5 text-xs font-medium text-white bg-teal-600 rounded-md hover:bg-teal-700" data-id="<?php echo $row['inquiry_id'] ?>" data-type="test">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="bg-white dark:bg-gray-800">
                                <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No Test Inquiry found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Test Inquiry Pagination -->
            <div id="testPagination" class="hidden flex justify-between items-center mt-4">
                <span class="text-sm text-gray-700 dark:text-gray-400">
                    Showing <span class="font-semibold text-gray-900 dark:text-white"><?= $offset_test + 1 ?></span> to <span class="font-semibold text-gray-900 dark:text-white"><?= min($offset_test + $records_per_page, $total_records_test) ?></span> of <span class="font-semibold text-gray-900 dark:text-white"><?= $total_records_test ?></span> Entries
                </span>
                <div class="inline-flex -space-x-px text-sm h-8">
                    <a href="?page_test=<?= max(1, $page_test - 1) ?>" class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white <?= $page_test <= 1 ? 'pointer-events-none opacity-50' : '' ?>">Previous</a>
                    <?php for ($i = 1; $i <= $total_pages_test; $i++): ?>
                        <a href="?page_test=<?= $i ?>" class="flex items-center justify-center px-3 h-8 leading-tight <?= $i == $page_test ? 'text-blue-600 border border-blue-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?page_test=<?= min($total_pages_test, $page_test + 1) ?>" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white <?= $page_test >= $total_pages_test ? 'pointer-events-none opacity-50' : '' ?>">Next</a>
                </div>
            </div>
        </div>
    </main>

    <!-- NEW: Drawer Overlay -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[99] hidden"></div>

    <div id="rightDrawer" class="drawer fixed top-0 right-0 h-full w-full max-w-2xl bg-white dark:bg-gray-800 shadow-2xl transform translate-x-full transition-transform duration-300 z-[100]" aria-hidden="true">
        <div class="drawer-header flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 id="drawerTitle" class="text-gray-800 dark:text-white">Inquiry Details</h2>
            <button class="close-drawer text-gray-500 hover:text-red-500 text-2xl font-bold">&times;</button>
        </div>

        <div id="drawerMessage" class="drawer-message p-1 dark:text-gray-400 text-sm text-center font-semibold hidden"></div>

        <div class="drawer-body p-2 space-y-2 overflow-y-auto" style="height: calc(100% - 120px);">

            <!-- Quick Inquiry Form -->
            <form id="quickForm" class="inquiry-form hidden space-y-6" method="post">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="inquiry_id" id="inquiry_id" value="">

                <!-- Personal Information -->
                <div class="info-card bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Personal Information</h3>
                    <div class="card-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label for="qf-name" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Patient Name *</label>
                            <input type="text" id="qf-name" name="name" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="qf-age" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Age *</label>
                            <input type="number" id="qf-age" name="age" min="1" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="qf-gender" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Gender *</label>
                            <select id="qf-gender" name="gender" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="detail-item">
                            <label for="qf-phone" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Phone *</label>
                            <input type="text" id="qf-phone" name="phone_number" maxlength="10" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="qf-email" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Email</label>
                            <input type="email" id="qf-email" name="email" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="qf-occupation" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Occupation *</label>
                            <input type="text" id="qf-occupation" name="occupation" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item md:col-span-2">
                            <label for="qf-address" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Address</label>
                            <input type="text" id="qf-address" name="address" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>
                </div>

                <!-- Referral Information -->
                <div class="info-card bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Referral Information</h3>
                    <div class="card-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label for="qf-referred" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Referred By *</label>
                            <input type="text" id="qf-referred" name="referred_by" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="qf-source" class="block text-sm font-medium text-gray-600 dark:text-gray-400">How did you hear about us</label>
                            <select id="qf-source" name="referralSource" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
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
                <div class="info-card bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Medical Details</h3>
                    <div class="card-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label for="qf-complain" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Chief Complain *</label>
                            <select id="qf-complain" name="chief_complain" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                                <option value="other">Select your condition</option>
                                <option value="neck_pain">Neck Pain</option>
                                <option value="back_pain">Back Pain</option>
                                <option value="low_back_pain">Low Back Pain</option>
                                <option value="radiating_pain">Radiating Pain</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="detail-item md:col-span-2">
                            <label for="qf-review" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Describe Condition / Remarks</label>
                            <input type="text" id="qf-review" name="review" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>
                </div>

                <!-- Consultation & Appointment -->
                <div class="info-card bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Consultation & Appointment</h3>
                    <div class="card-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label for="qf-inquiry-type" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Consultation Type *</label>
                            <select id="qf-inquiry-type" name="inquiry_type" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Select Consultation Type</option>
                                <option value="In-Clinic">In-Clinic</option>
                                <option value="Home-Visit">Home-Visit</option>
                                <option value="Virtual/Online">Virtual/Online</option>
                            </select>
                        </div>
                        <div>
                            <label for="qf-appt-date" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Appointment Date</label>
                            <input type="date" id="qf-appt-date" name="appointment_date" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="appointment_time" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Time Slot *</label>
                            <select id="appointment_time" name="appointment_time" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"></select>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="submit-btn2 pt-4">
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">Register</button>
                </div>
            </form>


            <!-- Test Inquiry Form -->
            <form id="testForm" class="inquiry-form hidden space-y-6" method="POST" action="../api/test_submission.php">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="inquiry_id" id="inquiry_id_test" value="">

                <!-- Patient Information -->
                <div class="info-card bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Patient Information</h3>
                    <div class="card-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label for="tf-name" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Patient Name *</label>
                            <input type="text" id="tf-name" name="name" placeholder="Enter Patient Name" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item"><label for="tf-age" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Age *</label><input type="number" id="tf-age" name="age" max="150" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"></div>
                        <div class="detail-item"><label for="tf-dob" class="block text-sm font-medium text-gray-600 dark:text-gray-400">DOB</label><input type="date" id="tf-dob" name="dob" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"></div>
                        <div class="detail-item"><label for="tf-gender" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Gender *</label><select id="tf-gender" name="gender" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select></div>
                        <div class="detail-item"><label for="tf-parents" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Parents/Guardian</label><input type="text" id="tf-parents" name="parents" placeholder="Parents/Guardian Name" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"></div>
                        <div class="detail-item"><label for="tf-relation" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Relation</label><input type="text" id="tf-relation" name="relation" placeholder="e.g., Father, Mother" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"></div>
                        <div class="detail-item"><label for="tf-phone" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Phone No *</label><input type="text" id="tf-phone" name="mobile_number" placeholder="+911234567890" maxlength="10" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"></div>
                        <div class="detail-item"><label for="tf-alt-phone" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Alternate Phone No</label><input type="text" id="tf-alt-phone" name="alternate_phone_no" placeholder="+911234567890" maxlength="10" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500"></div>
                    </div>
                </div>

                <!-- Referral Information -->
                <div class="info-card bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Referral Information</h3>
                    <div class="card-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label for="tf-referred" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Referred By</label>
                            <input type="text" id="tf-referred" name="reffered_by" placeholder="Doctor/Clinic Name" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>
                </div>

                <!-- Test Details -->
                <div class="info-card bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Test Details</h3>
                    <div class="card-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label for="tf-testname" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Test Name *</label>
                            <select id="tf-testname" name="testname" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
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
                            <label for="tf-limb" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Limb</label>
                            <select id="tf-limb" name="limb" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Select Limb</option>
                                <option value="upper_limb">Upper Limb</option>
                                <option value="lower_limb">Lower Limb</option>
                                <option value="both">Both Limbs</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="detail-item">
                            <label for="tf-visit-date" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Date of Visit *</label>
                            <input type="date" id="tf-visit-date" name="visit_date" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="tf-assigned-date" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Assigned Test Date *</label>
                            <input type="date" id="tf-assigned-date" name="assigned_test_date" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="tf-done-by" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Test Done By *</label>
                            <select id="tf-done-by" name="test_done_by" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                                <option value="">Select Staff</option>
                                <option value="achal">Achal</option>
                                <option value="ashish">Ashish</option>
                                <option value="pancham">Pancham</option>
                                <option value="sayan">Sayan</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="info-card bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-md font-semibold mb-4 text-gray-800 dark:text-gray-200">Payment Details</h3>
                    <div class="card-grid grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="detail-item">
                            <label for="tf-total" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Total Amount *</label>
                            <input type="number" id="tf-total" name="total_amount" step="0.01" placeholder="Enter Amount" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="tf-advance" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Advance Amount</label>
                            <input type="number" id="tf-advance" name="advance_amount" step="0.01" value="0" placeholder="Enter Advance Amount" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="tf-due" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Due Amount</label>
                            <input type="number" id="tf-due" name="due_amount" step="0.01" value="0" placeholder="Enter Due Amount" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="tf-discount" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Discount</label>
                            <input type="number" id="tf-discount" name="discount" step="0.01" value="0" placeholder="Enter Discount" class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
                        </div>
                        <div class="detail-item">
                            <label for="tf-payment" class="block text-sm font-medium text-gray-600 dark:text-gray-400">Payment Method *</label>
                            <select id="tf-payment" name="payment_method" required class="mt-1 block w-full text-sm px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-900 dark:text-white focus:ring-teal-500 focus:border-teal-500">
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
                <div class="submit-btn2 pt-4">
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">Submit Test</button>
                </div>
            </form>
        </div>
    </div>
    <div id="toast-container" class="fixed top-20 right-6 w-full max-w-xs z-[9999] space-y-3"></div>


    <script src="../js/theme.js"></script>
    <script src="../js/inquiry.js"></script>
    <script src="../js/nav_toggle.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        // All JavaScript has been moved to /js/inquiry.js
    </script>
</body>

</html>