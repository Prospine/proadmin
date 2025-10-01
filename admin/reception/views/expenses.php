<?php

declare(strict_types=1);
session_start();

// -------------------------
// Dependencies & Config
// -------------------------
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!isset($_SESSION['uid'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../common/auth.php';
require_once '../../common/db.php';
require_once '../../common/logger.php';

// -------------------------
// Session & Branch Checks
// -------------------------
if (!isset($_SESSION['branch_id'], $_SESSION['uid'], $_SESSION['username'])) {
    $_SESSION['errors'] = ['Your session is incomplete. Please log in again.'];
    header('Location: ../../login.php');
    exit();
}
$branchId = $_SESSION['branch_id'];
$userId = $_SESSION['uid'];
$username = $_SESSION['username'];

// -------------------------
// FORM SUBMISSION (CREATE & UPLOAD)
// -------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create_expense'; // Differentiate between actions

    // --- ACTION: CREATE A NEW EXPENSE ---
    if ($action === 'create_expense') {
        $voucher_no = trim($_POST['voucher_no'] ?? '');
        $expense_date = $_POST['expense_date'] ?? '';
        $paid_to = trim($_POST['paid_to'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);
        $payment_method = trim($_POST['payment_method'] ?? '');
        $amount_in_words = trim($_POST['amount_in_words'] ?? '');

        // Validation
        if (empty($voucher_no) || empty($expense_date) || empty($paid_to) || empty($description) || $amount === false || $amount <= 0 || empty($payment_method)) {
            $errors[] = "Please fill out all fields correctly. Amount must be greater than zero.";
        }

        $stmtCheck = $pdo->prepare("SELECT 1 FROM expenses WHERE branch_id = ? AND voucher_no = ?");
        $stmtCheck->execute([$branchId, $voucher_no]);
        if ($stmtCheck->fetch()) {
            $errors[] = "Voucher No. '{$voucher_no}' already exists for this branch. Please use a unique number.";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // --- MODIFIED: Dynamic Budget and Status Logic ---
                // 1. Get the active budget for the specific expense date
                $stmtBudget = $pdo->prepare("
                    SELECT daily_budget_amount FROM branch_budgets
                    WHERE branch_id = ? AND effective_from_date <= ?
                    ORDER BY effective_from_date DESC
                    LIMIT 1
                ");
                $stmtBudget->execute([$branchId, $expense_date]);
                $dailyBudget = (float)($stmtBudget->fetchColumn() ?? 0.00);

                // 2. Get total approved for that date BEFORE this new transaction
                $stmtToday = $pdo->prepare("
                    SELECT SUM(amount) FROM expenses
                    WHERE branch_id = ? AND expense_date = ? AND status = 'approved'
                ");
                $stmtToday->execute([$branchId, $expense_date]);
                $totalApprovedToday = (float)$stmtToday->fetchColumn();

                // 3. Calculate remaining budget for that day and determine status
                $remainingBudgetBeforeThis = $dailyBudget - $totalApprovedToday;
                $status = ($amount <= $remainingBudgetBeforeThis) ? 'approved' : 'pending';
                // --- END OF MODIFICATION ---

                $stmt = $pdo->prepare(
                    "INSERT INTO expenses (branch_id, user_id, voucher_no, expense_date, paid_to, description, amount, amount_in_words, payment_method, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $branchId,
                    $userId,
                    $voucher_no,
                    $expense_date,
                    $paid_to,
                    $description,
                    $amount,
                    $amount_in_words,
                    $payment_method,
                    $status
                ]);
                $newExpenseId = $pdo->lastInsertId();

                $logDetailsAfter = ['voucher_no' => $voucher_no, 'amount' => $amount, 'status' => $status];
                log_activity($pdo, $userId, $username, $branchId, 'CREATE', 'expenses', (int)$newExpenseId, null, $logDetailsAfter);

                $pdo->commit();
                $_SESSION['success'] = "Expense voucher #{$voucher_no} added successfully! Status: " . ucfirst($status);
                header('Location: expenses.php');
                exit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }

    // --- ACTION: UPLOAD A BILL IMAGE ---
    if ($action === 'upload_bill') {
        // This part remains unchanged
        $expenseId = $_POST['expense_id'] ?? null;
        $file = $_FILES['bill_image'] ?? null;

        if (!$expenseId || !$file || $file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Invalid upload request or file error.";
        } else {
            $uploadDir = '../../uploads/expenses/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = "Invalid file type. Only JPG, PNG, and PDF are allowed.";
            } elseif ($file['size'] > 5 * 1024 * 1024) { // 5 MB limit
                $errors[] = "File is too large. Maximum size is 5MB.";
            } else {
                $fileName = "expense_{$expenseId}_" . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $destination = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $relativePath = 'uploads/expenses/' . $fileName;
                    $stmtUpdate = $pdo->prepare("UPDATE expenses SET bill_image_path = ? WHERE expense_id = ? AND branch_id = ?");
                    $stmtUpdate->execute([$relativePath, $expenseId, $branchId]);
                    $_SESSION['success'] = "Bill uploaded successfully!";
                } else {
                    $errors[] = "Failed to move the uploaded file.";
                }
            }
        }
        $_SESSION['errors'] = $errors;
        header('Location: expenses.php');
        exit();
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: expenses.php');
        exit();
    }
}


// -------------------------
// DATA FETCHING FOR DISPLAY
// -------------------------
try {
    // Fetch all expenses for the current branch
    $stmtExpenses = $pdo->prepare("
        SELECT e.*, u.username as creator_username
        FROM expenses e
        LEFT JOIN users u ON e.user_id = u.id
        WHERE e.branch_id = :branch_id
        ORDER BY e.expense_date DESC, e.created_at DESC
    ");
    $stmtExpenses->execute([':branch_id' => $branchId]);
    $expenses = $stmtExpenses->fetchAll(PDO::FETCH_ASSOC);

    // Fetch branch name
    $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmtBranch->execute(['branch_id' => $branchId]);
    $branchName = $stmtBranch->fetchColumn() ?? '';

    // --- MODIFIED: Calculate Today's Budget and Spending ---
    $todayDate = date('Y-m-d');

    // 1. Get the current active budget for this branch for today
    $stmtBudget = $pdo->prepare("
        SELECT daily_budget_amount FROM branch_budgets
        WHERE branch_id = :branch_id AND effective_from_date <= :today_date
        ORDER BY effective_from_date DESC
        LIMIT 1
    ");
    $stmtBudget->execute([':branch_id' => $branchId, ':today_date' => $todayDate]);
    $dailyBudget = (float)($stmtBudget->fetchColumn() ?? 0.00); // Default to 0 if no budget is set

    // 2. Get the total of approved expenses for today
    $stmtToday = $pdo->prepare("
        SELECT SUM(amount) FROM expenses
        WHERE branch_id = :branch_id AND expense_date = :today_date AND status = 'approved'
    ");
    $stmtToday->execute([':branch_id' => $branchId, ':today_date' => $todayDate]);
    $totalApprovedToday = (float)$stmtToday->fetchColumn();

    // 3. Calculate the remaining budget for display
    $remainingBudget = $dailyBudget - $totalApprovedToday;
    // --- END OF MODIFICATION ---

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Retrieve and clear session messages
$sessionErrors = $_SESSION['errors'] ?? [];
$successMessage = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/patients.css">
    <link rel="stylesheet" href="../css/inquiry.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../css/expenses.css">
    <style>
    
    </style>
</head>

<body>
    <header>
        <!-- Your header remains the same -->
        <div class="logo-container"><img src="../../assets/images/image.png" alt="Pro Physio Logo" class="logo" />
        </div>
        <nav>
            <div class="nav-links"><a href="dashboard.php">Dashboard</a><a href="inquiry.php">Inquiry</a><a href="registration.php">Registration</a><a href="patients.php">Patients</a><a href="appointments.php">Appointments</a><a href="billing.php">Billing</a><a href="attendance.php">Attendance</a><a href="tests.php">Tests</a><a href="reports.php">Reports</a><a href="expenses.php" class="active">Expenses</a>
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
    <div class="menu" id="myMenu"><span class="closebtn" onclick="closeForm()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="#">Profile</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
    <div class="notification" id="myNotif"><span class="closebtn" onclick="closeNotif()">&times;</span>
        <div class="popup">
            <ul>
                <li><a href="changelog.html" class="active2">View Changes (1) </a></li>
                <li><a href="#">You have 3 new appointments.</a></li>
                <li><a href="#">Dr. Smith is available for consultation.</a></li>
                <li><a href="#">New patient registered: John Doe.</a></li>
            </ul>
        </div>
    </div>


    <!-- Add Expense Modal (existing) -->
    <div id="expense-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Expense Voucher</h3>
                <button id="close-modal-btn" class="close-modal-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form action="expenses.php" method="POST">
                    <!-- NEW: Add hidden action field -->
                    <input type="hidden" name="action" value="create_expense">
                    <div class="form-grid">
                        <div class="form-group"><label for="voucher_no">Voucher No. *</label><input type="text" id="voucher_no" name="voucher_no" required></div>
                        <div class="form-group"><label for="expense_date">Date *</label><input type="date" id="expense_date" name="expense_date" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="form-group"><label for="paid_to">Paid To *</label><input type="text" id="paid_to" name="paid_to" required></div>
                        <div class="form-group"><label for="amount">Amount (₹) *</label><input type="number" id="amount" name="amount" step="0.01" min="0.01" required></div>
                        <div class="form-group"><label for="amount_in_words">Amount in Words</label><input type="text" id="amount_in_words" name="amount_in_words" readonly></div>
                        <div class="form-group"><label for="payment_method">Payment Method *</label><select id="payment_method" name="payment_method" required>
                                <option value="">--Select--</option>
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="net_banking">Net Banking</option>
                                <option value="other">Other</option>
                            </select></div>
                        <div class="form-group full-width"><label for="description">Being (Description) *</label><textarea id="description" name="description" rows="2" required></textarea></div>
                    </div>
                    <div class="form-actions"><button type="submit" class="action-btn">Save Expense</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- NEW: Upload Bill Modal -->
    <div id="upload-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Upload Bill</h3>
                <button id="close-upload-modal-btn" class="close-modal-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form action="expenses.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_bill">
                    <input type="hidden" id="upload_expense_id" name="expense_id">
                    <div class="form-group">
                        <label for="bill_image">Select Bill File (JPG, PNG, PDF) *</label>
                        <input type="file" id="bill_image" name="bill_image" accept="image/jpeg,image/png,application/pdf" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="action-btn">Upload Bill</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- NEW: View Expense Details Modal -->
    <div id="view-modal" class="modal-overlay">
        <div class="modal-content large">
            <div class="modal-header">
                <h3>Expense Details</h3>
                <button id="close-view-modal-btn" class="close-modal-btn">&times;</button>
            </div>
            <div class="modal-body" id="view-modal-body">
                <!-- Content will be injected by JavaScript -->
            </div>
        </div>
    </div>

    <!-- NEW: Image Preview Modal -->
    <div id="image-preview-modal" class="modal-overlay image-modal">
        <span id="close-image-modal-btn" class="close-modal-btn image-close-btn">&times;</span>
        <img id="modal-image-content" src="" alt="Bill Preview">
    </div>

    <main class="main">
        <div class="dashboard-container">
            <div class="top-bar">
                <h2>Manage Expenses</h2>
                <div class="budget-container">
                    <p class="budget-text">Today's Budget: ₹<?= number_format($dailyBudget, 2) ?></p>
                    <p class="budget-text">Today's Remaining Budget:
                        <span class="<?= $remainingBudget < 0 ? 'negative' : '' ?>">
                            ₹<?= number_format($remainingBudget, 2) ?>
                        </span>
                    </p>
                    <button id="add-expense-btn" class="action-btn"><i class="fa fa-plus"></i> Add New Expense</button>
                </div>
            </div>

            <!-- Session Messages -->
            <?php if ($successMessage): ?><div class="message success"><?= htmlspecialchars($successMessage) ?></div><?php endif; ?>
            <?php if (!empty($sessionErrors)): ?>
                <div class="message error">
                    <ul><?php foreach ($sessionErrors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($remainingBudget < 0): ?>
                <p class="budget-warning">You have used your daily budget</p>
            <?php endif; ?>

            <div class="table-container modern-table">
                <h3>Expense History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Voucher No.</th>
                            <th>Date</th>
                            <th>Paid To</th>
                            <th>Amount (₹)</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th style="text-align: center;">Actions</th>
                            <th style="text-align: center;">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No expenses found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= htmlspecialchars($expense['voucher_no']) ?></td>
                                    <td><?= htmlspecialchars(date('d M Y', strtotime($expense['expense_date']))) ?></td>
                                    <td><?= htmlspecialchars($expense['paid_to']) ?></td>
                                    <td style="text-align: right;"><?= number_format((float)$expense['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $expense['payment_method']))) ?></td>
                                    <td><span class="status-pill status-<?= htmlspecialchars(strtolower($expense['status'])) ?>"><?= htmlspecialchars(ucfirst($expense['status'])) ?></span></td>
                                    <!-- NEW: Action Buttons -->
                                    <td>
                                        <button class="action-btn upload-btn" data-expense-id="<?= $expense['expense_id'] ?>"><i class="fa fa-upload"></i> Upload</button>
                                    </td>
                                    <td>
                                        <button class="action-btn view-btn" data-expense-details='<?= htmlspecialchars(json_encode($expense), ENT_QUOTES, 'UTF-8') ?>'><i class="fa fa-eye"></i> View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Toast container for displaying messages -->
    <div id="toast-container"></div>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/expenses.js"></script>

    <script>
        // This script block will handle showing toast messages from PHP session variables.
        document.addEventListener('DOMContentLoaded', function() {
            // The showToast function is available from your other scripts.
            // We just need to call it with the messages from PHP.
            <?php if ($successMessage): ?>
                showToast('<?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>', 'success');
            <?php endif; ?>

            <?php if (!empty($sessionErrors)): ?>
                <?php foreach ($sessionErrors as $error): ?>
                    showToast('<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>', 'error');
                <?php endforeach; ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>