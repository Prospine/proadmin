<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
error_reporting(E_ALL);

// --- Security & Setup ---
if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/db.php';
require_once '../../common/logger.php';

$adminUserId = $_SESSION['uid'];
$adminUsername = $_SESSION['username'];

// --- FORM SUBMISSION LOGIC (UPDATE STATUS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $expenseId = filter_input(INPUT_POST, 'expense_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['status'] ?? '');

    if ($expenseId && in_array($newStatus, ['pending', 'approved', 'rejected', 'paid'])) {
        try {
            $pdo->beginTransaction();

            // Fetch current state for logging
            $stmtBefore = $pdo->prepare("SELECT * FROM expenses WHERE expense_id = ?");
            $stmtBefore->execute([$expenseId]);
            $detailsBefore = $stmtBefore->fetch(PDO::FETCH_ASSOC);

            $sql = "UPDATE expenses SET status = :status";
            $params = [':status' => $newStatus, ':expense_id' => $expenseId];

            if ($newStatus === 'approved') {
                $sql .= ", approved_by_user_id = :approved_by, approved_at = NOW()";
                $params[':approved_by'] = $adminUserId;
            } else {
                // If status is changed to something else, clear approval info
                $sql .= ", approved_by_user_id = NULL, approved_at = NULL";
            }

            $sql .= " WHERE expense_id = :expense_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Fetch updated state for logging
            $stmtAfter = $pdo->prepare("SELECT * FROM expenses WHERE expense_id = ?");
            $stmtAfter->execute([$expenseId]);
            $detailsAfter = $stmtAfter->fetch(PDO::FETCH_ASSOC);

            log_activity($pdo, $adminUserId, $adminUsername, $detailsBefore['branch_id'], 'UPDATE', 'expenses', $expenseId, $detailsBefore, $detailsAfter);

            $pdo->commit();
            $_SESSION['success'] = "Expense #{$expenseId} status updated to '" . ucfirst($newStatus) . "'.";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['errors'] = ["Database error: " . $e->getMessage()];
        }
    } else {
        $_SESSION['errors'] = ["Invalid data provided for status update."];
    }

    header("Location: manage_expenses.php"); // Redirect to prevent resubmission
    exit();
}

// --- DATA FETCHING FOR DISPLAY ---
$filterBranch = $_GET['branch'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$expenses = [];
$branches = [];
$totalAmount = 0.0;

try {
    // Fetch all branches for the filter dropdown
    $branches = $pdo->query("SELECT branch_id, branch_name FROM branches ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

    // Base query to fetch expenses from all branches
    $sql = "
        SELECT 
            e.*, 
            b.branch_name,
            creator.username AS creator_username,
            approver.username AS approver_username
        FROM expenses e
        JOIN branches b ON e.branch_id = b.branch_id
        LEFT JOIN users creator ON e.user_id = creator.id
        LEFT JOIN users approver ON e.approved_by_user_id = approver.id
        WHERE 1=1
    ";

    $params = [];
    if (!empty($filterBranch)) {
        $sql .= " AND e.branch_id = :branch_id";
        $params[':branch_id'] = $filterBranch;
    }
    if (!empty($filterStatus)) {
        $sql .= " AND e.status = :status";
        $params[':status'] = $filterStatus;
    }
    if (!empty($startDate)) {
        $sql .= " AND e.expense_date >= :start_date";
        $params[':start_date'] = $startDate;
    }
    if (!empty($endDate)) {
        $sql .= " AND e.expense_date <= :end_date";
        $params[':end_date'] = $endDate;
    }

    $sql .= " ORDER BY e.expense_date DESC, e.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total amount for the filtered results
    $totalAmount = array_sum(array_column($expenses, 'amount'));
} catch (PDOException $e) {
    die("Error fetching expense data: " . $e->getMessage());
}

// Retrieve and clear session messages
$sessionErrors = $_SESSION['errors'] ?? [];
$sessionSuccess = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Expenses</title>
    <link rel="stylesheet" href="../../reception/css/dashboard.css">
    <link rel="stylesheet" href="../../reception/css/patients.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <style>
        .main {
            padding: 1.5rem;
        }
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            flex-wrap: nowrap; /* Ensure items stay in a single line */
            align-items: center;
            padding: 1rem;
            background-color: var(--bg-secondary);
            border-radius: var(--border-radius-card);
            margin-bottom: 1.5rem;
            min-width: 98%;
        }
        .filter-bar select, .filter-bar input {
            /* Ensure inputs/selects don't take up too much space */
            flex-shrink: 0;
            min-width: 180px; /* Limit width to prevent excessive stretching */
            width: auto; /* Allow natural sizing up to max-width */
            padding: 0.6rem;
            border-radius: var(--border-radius-btn);
            border: 1px solid var(--border-color-primary);
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        .filter-bar .action-btn {
            padding: 0.6rem 1.2rem;
            flex-shrink: 0; /* Prevent buttons from shrinking */
            margin: 0;
        }
        .total-amount-card {
            margin-left: auto;
            padding: 0.6rem 1.2rem;
            background-color: var(--bg-tertiary);
            border-radius: var(--border-radius-btn);
            font-weight: 600;
            font-size: 1.1rem;
            flex-shrink: 0; /* Prevent total card from shrinking */
        }
        .status-select {
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid var(--border-color-secondary);
        }
        .update-btn {
            padding: 4px 8px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        .bill-link {
            color: var(--color-link);
            text-decoration: none;
        }
        .bill-link:hover {
            text-decoration: underline;
        }

        /* --- NEW: Table & Status Pill Styles --- */
        .modern-table table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
        }
        .modern-table thead {
            background-color: var(--bg-tertiary);
        }
        .modern-table th {
            padding: 1rem 1.25rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border-color-primary);
        }
        .modern-table td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color-primary);
            color: var(--text-primary);
            vertical-align: middle;
        }
        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }
        .modern-table tbody tr:hover {
            background-color: var(--bg-secondary);
        }
        .status-pill {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 50rem;
            text-align: center;
        }
        .status-pending { background-color: #fff3cd; color: #664d03; }
        .status-approved { background-color: #d1e7dd; color: #0f5132; }
        .status-rejected { background-color: #f8d7da; color: #842029; }
        .status-paid { background-color: #cfe2ff; color: #052c65; }
    </style>
</head>

<body>
    <header>
        <div class="logo-container">
            <div class="logo"><img src="/proadmin/admin/assets/images/image.png" alt="ProSpine Logo" /></div>
        </div>
        <nav>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php">Manage Users</a>
                <a href="manage_employees.php">Manage Employees</a>
                <a href="manage_expenses.php" class="active">Manage Expenses</a>
                <a href="manage_branches.php">Manage Branches</a>
            </div>
        </nav>
        <div class="nav-actions">
            <div class="icon-btn" id="theme-toggle"><i id="theme-icon" class="fa-solid fa-moon"></i></div>
            <a href="../../reception/views/logout.php" class="icon-btn" title="Logout"><i class="fa-solid fa-sign-out-alt"></i></a>
        </div>
    </header>

    <main class="main">
        <div class="top-bar">
            <h2>Global Expense Management</h2>
        </div>

        <!-- Display Session Messages -->
        <?php if ($sessionSuccess) : ?><div class="message success" style="margin-bottom: 1rem;"><?= htmlspecialchars($sessionSuccess) ?></div><?php endif; ?>
        <?php if (!empty($sessionErrors)) : ?>
            <div class="message error" style="margin-bottom: 1rem;">
                <ul><?php foreach ($sessionErrors as $error) : ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="filter-bar">
            <form method="GET" action="" style="display: contents;">
                <select name="branch" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $branch) : ?>
                        <option value="<?= $branch['branch_id'] ?>" <?= $filterBranch == $branch['branch_id'] ? 'selected' : '' ?>><?= htmlspecialchars($branch['branch_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $filterStatus == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $filterStatus == 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $filterStatus == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="paid" <?= $filterStatus == 'paid' ? 'selected' : '' ?>>Paid</option>
                </select>
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" title="Start Date">
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" title="End Date">
                <button type="submit" class="action-btn">Filter</button>
                <a href="manage_expenses.php" class="action-btn secondary">Reset</a>
            </form>
            <div class="total-amount-card">
                Total: ₹<?= number_format($totalAmount, 2) ?>
            </div>
        </div>

        <div class="table-container modern-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Branch</th>
                        <th>Voucher</th>
                        <th>Date</th>
                        <th>Paid To</th>
                        <th>Amount (₹)</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th>Approved By</th>
                        <th>Bill</th>
                        <th style="width: 250px;">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)) : ?>
                        <tr>
                            <td colspan="11" style="text-align: center;">No expenses found for the selected filters.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($expenses as $expense) : ?>
                            <tr>
                                <td><?= $expense['expense_id'] ?></td>
                                <td><?= htmlspecialchars($expense['branch_name']) ?></td>
                                <td><?= htmlspecialchars($expense['voucher_no']) ?></td>
                                <td><?= date('d M Y', strtotime($expense['expense_date'])) ?></td>
                                <td><?= htmlspecialchars($expense['paid_to']) ?></td>
                                <td style="text-align: right;"><?= number_format((float)$expense['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($expense['creator_username'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="status-pill status-<?= htmlspecialchars(strtolower($expense['status'])) ?>">
                                        <?= htmlspecialchars(ucfirst($expense['status'])) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($expense['approver_username'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (!empty($expense['bill_image_path'])) : ?>
                                        <a href="/proadmin/admin/<?= htmlspecialchars($expense['bill_image_path']) ?>" target="_blank" class="bill-link">
                                            <i class="fa-solid fa-file-invoice"></i> View
                                        </a>
                                    <?php else : ?>
                                        <span>No Bill</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form action="manage_expenses.php" method="POST" style="display: flex; align-items: center; gap: 5px;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="expense_id" value="<?= $expense['expense_id'] ?>">
                                        <select name="status" class="status-select">
                                            <option value="pending" <?= $expense['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="approved" <?= $expense['status'] == 'approved' ? 'selected' : '' ?>>Approve</option>
                                            <option value="rejected" <?= $expense['status'] == 'rejected' ? 'selected' : '' ?>>Reject</option>
                                            <option value="paid" <?= $expense['status'] == 'paid' ? 'selected' : '' ?>>Paid</option>
                                        </select>
                                        <button type="submit" class="action-btn update-btn">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="toast-container"></div>

    <script src="../../reception/js/theme.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to show toast messages
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.textContent = message;

                container.appendChild(toast);

                setTimeout(() => toast.classList.add('show'), 10);

                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => {
                        if (container.contains(toast)) {
                            container.removeChild(toast);
                        }
                    }, 500);
                }, 5000);
            }

            // Display session messages from PHP
            <?php if ($sessionSuccess) : ?>
                showToast('<?= htmlspecialchars($sessionSuccess, ENT_QUOTES, 'UTF-8') ?>', 'success');
            <?php endif; ?>

            <?php if (!empty($sessionErrors)) : ?>
                <?php foreach ($sessionErrors as $error) : ?>
                    showToast('<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>', 'error');
                <?php endforeach; ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>