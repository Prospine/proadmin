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
require_once '../../common/logger.php'; // Our trusty logger

// -------------------------
// Session & Branch Checks
// -------------------------
if (!isset($_SESSION['branch_id'], $_SESSION['uid'], $_SESSION['username'])) {
    // Redirect or handle error if essential session data is missing
    $_SESSION['errors'] = ['Your session is incomplete. Please log in again.'];
    header('Location: ../../login.php');
    exit();
}
$branchId = $_SESSION['branch_id'];
$userId = $_SESSION['uid'];
$username = $_SESSION['username'];

// -------------------------
// FORM SUBMISSION (CREATE EXPENSE)
// -------------------------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize data from the form
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

    // Check if voucher number already exists for this branch
    $stmtCheck = $pdo->prepare("SELECT 1 FROM expenses WHERE branch_id = ? AND voucher_no = ?");
    $stmtCheck->execute([$branchId, $voucher_no]);
    if ($stmtCheck->fetch()) {
        $errors[] = "Voucher No. '{$voucher_no}' already exists for this branch. Please use a unique number.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO expenses (branch_id, user_id, voucher_no, expense_date, paid_to, description, amount, amount_in_words, payment_method) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
                $payment_method

            ]);
            $newExpenseId = $pdo->lastInsertId();

            // Log this creation event!
            $logDetailsAfter = [
                'voucher_no' => $voucher_no,
                'paid_to' => $paid_to,
                'amount' => $amount,
                'payment_method' => $payment_method,
                'status' => 'pending' // Initial status
            ];
            log_activity(
                $pdo,
                $userId,
                $username,
                $branchId,
                'CREATE',
                'expenses',
                (int)$newExpenseId,
                null,
                $logDetailsAfter
            );

            $pdo->commit();
            $_SESSION['success'] = "Expense voucher #{$voucher_no} added successfully!";
            header('Location: expenses.php'); // Redirect to clear the form
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    // If there were errors, store them in session to display after redirect
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

    // Fetch branch name for the header
    $stmtBranch = $pdo->prepare("SELECT branch_name FROM branches WHERE branch_id = :branch_id");
    $stmtBranch->execute(['branch_id' => $branchId]);
    $branchName = $stmtBranch->fetchColumn() ?? '';
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
    <style>
        /* ===== Modal Body ===== */
        .modal-body {
            padding: 24px;
            background: #f9fafb;
            /* Light gray background */
            border-radius: 12px;
            font-family: "Inter", "Segoe UI", sans-serif;
            max-height: 70vh;
            overflow-y: auto;
        }

        body.dark .modal-body {
            background: #202020ff;
            color: #fff;
        }

        /* ===== Form Grid: 2 inputs per row ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            /* border: 1px solid; */
            width: 90%;
        }

        /* Full-width fields (like description) */
        .form-group.full-width {
            grid-column: 1 / -1;
        }

        /* ===== Form Groups ===== */
        .form-group {
            display: flex;
            flex-direction: column;
        }

        /* ===== Labels ===== */
        .form-group label {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
            color: #374151;
        }

        body.dark .form-group label {
            color: #fff;
        }

        /* ===== Inputs & Textarea ===== */
        .form-group input,
        .form-group textarea,
        .form-group select {
            padding: 12px 14px;
            font-size: 15px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            outline: none;
            transition: all 0.2s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Focus Effect */
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* ===== Button Styling ===== */
        .form-actions {
            margin-top: 24px;
            text-align: right;
        }

        .action-btn {
            background: linear-gradient(135deg, #7e7e7eff, #000000ff);
            color: #fff;
            font-weight: 600;
            /* padding: 5px 10px; */
            width: 180px;
            height: 50px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.5s ease;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }

        body.dark .action-btn {
            background: linear-gradient(135deg, #dfdfdfff, #a0a0a0ff);
            color: #000000ff;
            font-weight: 600;
        }

        .action-btn:hover {
            background: linear-gradient(135deg, #000000ff, #858585ff);
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.4);
        }

        .action-btn:active {
            transform: scale(0.97);
        }

        /* ===== Message styles for success/error alerts ===== */
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            color: #fff;
        }

        .message.success {
            background-color: #28a745;
        }

        .message.error {
            background-color: #dc3545;
        }

        /* ===== Modal Popup Styles ===== */
        .modal-overlay {
            display: none;
            /* Hidden by default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 1.5rem 2rem;
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.3s;
        }

        body.dark .modal-content {
            background-color: #202020ff;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 2rem;
            font-weight: bold;
            color: var(--secondary-text-color, #6b7280);
            cursor: pointer;
            line-height: 1;
        }

        .close-modal-btn:hover {
            color: var(--text-color, #111827);
        }

        body.dark .close-modal-btn:hover {
            color: #000;
        }

        /* ===== Animation ===== */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* ===== Responsive: stack inputs on small screens ===== */
        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
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
                <a href="billing.php">Billing</a>
                <a href="attendance.php">Attendance</a>
                <a href="tests.php">Tests</a>
                <a href="reports.php">Reports</a>
                <a href="expenses.php" class="active">Expenses</a>
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

    <div id="expense-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Expense Voucher</h3>
                <button id="close-modal-btn" class="close-modal-btn">&times;</button>
            </div>
            <div class="modal-body">
                <form action="expenses.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="voucher_no">Voucher No. *</label>
                            <input type="text" id="voucher_no" name="voucher_no" required>
                        </div>
                        <div class="form-group">
                            <label for="expense_date">Date *</label>
                            <input type="date" id="expense_date" name="expense_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="paid_to">Paid To *</label>
                            <input type="text" id="paid_to" name="paid_to" required>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount (₹) *</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="amount_in_words">Amount in Words</label>
                            <input type="text" id="amount_in_words" name="amount_in_words" readonly>
                        </div>

                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="net_banking">Net Banking</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group full-width">
                            <label for="description">Being (Description) *</label>
                            <textarea id="description" name="description" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="action-btn">Save Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <main class="main">
        <div class="dashboard-container">
            <div class="top-bar">
                <h2>Manage Expenses</h2>
                <div class="budget" style="display:flex; justify-content: space-between; width: 500px;">
                    <p class="budget-text"> Budget Allocated Today : ₹1000</p>
                    <button id="add-expense-btn" class="action-btn"><i class="fa fa-plus"></i> Add New Expense</button>
                </div>
            </div>

            <?php if ($successMessage): ?>
                <div class="message success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>
            <?php if (!empty($sessionErrors)): ?>
                <div class="message error">
                    <ul>
                        <?php foreach ($sessionErrors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="table-container modern-table">
                <h3>Expense History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Voucher No.</th>
                            <th>Date</th>
                            <th>Paid To</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Amount in Words</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No expenses found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?= htmlspecialchars($expense['voucher_no']) ?></td>
                                    <td><?= htmlspecialchars($expense['expense_date']) ?></td>
                                    <td><?= htmlspecialchars($expense['paid_to']) ?></td>
                                    <td><?= htmlspecialchars($expense['description']) ?></td>
                                    <td><?= number_format((float)$expense['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($expense['amount_in_words']) ?></td>
                                    <td><?= htmlspecialchars($expense['payment_method']) ?></td>
                                    <td><span class="status-pill status-<?= htmlspecialchars(strtolower($expense['status'])) ?>"><?= htmlspecialchars(ucfirst($expense['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="../js/theme.js"></script>
    <script src="../js/dashboard.js"></script>

    <script>
        // JS for number to words (unchanged)
        function numberToWords(num) {
            const a = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
            const b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
            const s = ['', 'hundred', 'thousand', 'lakh', 'crore'];

            function inWords(n) {
                let str = '';
                if (n > 19) {
                    str += b[Math.floor(n / 10)] + ' ' + a[n % 10];
                } else {
                    str += a[n];
                }
                return str.trim();
            }

            let word = '';
            let numStr = num.toString();
            let [rupees, paise] = numStr.split('.');

            rupees = parseInt(rupees, 10);

            if (rupees > 9999999) word += inWords(Math.floor(rupees / 10000000)) + ' ' + s[4] + ' ';
            rupees %= 10000000;
            if (rupees > 99999) word += inWords(Math.floor(rupees / 100000)) + ' ' + s[3] + ' ';
            rupees %= 100000;
            if (rupees > 999) word += inWords(Math.floor(rupees / 1000)) + ' ' + s[2] + ' ';
            rupees %= 1000;
            if (rupees > 99) word += inWords(Math.floor(rupees / 100)) + ' ' + s[1] + ' ';
            rupees %= 100;
            if (rupees > 0) word += inWords(rupees);

            word = word.trim();

            if (word) {
                word += ' rupees';
            }

            if (paise && parseInt(paise, 10) > 0) {
                paise = parseInt(paise.slice(0, 2), 10);
                if (word) {
                    word += ' and ';
                }
                word += inWords(paise) + ' paise';
            }

            return word ? word.charAt(0).toUpperCase() + word.slice(1) + ' only' : '';
        }

        const amountInput = document.getElementById('amount');
        const amountInWordsInput = document.getElementById('amount_in_words');

        amountInput.addEventListener('input', function() {
            const num = parseFloat(this.value);
            if (!isNaN(num) && num > 0) {
                amountInWordsInput.value = numberToWords(num);
            } else {
                amountInWordsInput.value = '';
            }
        });

        // JavaScript for Modal Control
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('expense-modal');
            const openModalBtn = document.getElementById('add-expense-btn');
            const closeModalBtn = document.getElementById('close-modal-btn');

            // Function to open the modal
            function openModal() {
                modal.style.display = 'flex';
            }

            // Function to close the modal
            function closeModal() {
                modal.style.display = 'none';
            }

            // Event listeners
            openModalBtn.addEventListener('click', openModal);
            closeModalBtn.addEventListener('click', closeModal);

            // Close modal if user clicks on the background overlay
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            // Close modal if user presses the Escape key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>

</body>

</html>