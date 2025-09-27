<?php

declare(strict_types=1);
session_start();

// Error Reporting (Dev Only)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Auth / Session Checks
if (!isset($_SESSION['uid'])) {
    $_SESSION['errors'] = ['You must be logged in to submit a test.'];
    header('Location: ../login.php');
    exit();
}

require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection
require_once '../../common/logger.php'; // 1. Added the logger

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'reception'], true)) {
    $_SESSION['errors'] = ['Access denied. Insufficient permissions.'];
    header('Location: dashboard.php');
    exit();
}

// Ensure all required session details exist for logging
if (!isset($_SESSION['branch_id']) || !isset($_SESSION['uid']) || !isset($_SESSION['username'])) {
    $_SESSION['errors'] = ['User session details are incomplete. Please log in again.'];
    header('Location: ../login.php');
    exit();
}
$branch_id = $_SESSION['branch_id'];
$user_id = $_SESSION['uid'];
$username = $_SESSION['username'];


$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        // Collect inputs, handle optional fields with null
        $visit_date         = $_POST['visit_date'] ?? '';
        $assigned_test_date = $_POST['assigned_test_date'] ?? '';
        $patient_name       = trim($_POST['patient_name'] ?? '');
        $age                = filter_var($_POST['age'], FILTER_VALIDATE_INT, ['options' => ['max_range' => 150]]);
        $dob                = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender             = $_POST['gender'] ?? '';
        $parents            = !empty(trim($_POST['parents'] ?? '')) ? trim($_POST['parents']) : null;
        $relation           = !empty(trim($_POST['relation'] ?? '')) ? trim($_POST['relation']) : null;
        $phone_number       = trim($_POST['phone_number'] ?? '');
        $alternate_phone_no = !empty(trim($_POST['alternate_phone_no'] ?? '')) ? trim($_POST['alternate_phone_no']) : null;
        $referred_by        = !empty(trim($_POST['referred_by'] ?? '')) ? trim($_POST['referred_by']) : null;
        $test_name          = $_POST['test_name'] ?? '';
        $limb               = !empty($_POST['limb']) ? $_POST['limb'] : null;
        $test_done_by       = $_POST['test_done_by'] ?? '';
        $total_amount       = filter_var($_POST['total_amount'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
        $advance_amount     = filter_var($_POST['advance_amount'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: 0.0;
        $due_amount         = filter_var($_POST['due_amount'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: 0.0;
        $payment_method     = $_POST['payment_method'] ?? '';

        // ... (Your extensive validation code remains here) ...

        if (empty($errors)) {
            try {
                // Payment status logic
                $payment_status = 'pending';
                if ($due_amount == 0 && $total_amount > 0) {
                    $payment_status = 'paid';
                } elseif ($advance_amount > 0 && $due_amount > 0) {
                    $payment_status = 'partial';
                }

                $stmt = $pdo->prepare("
                    INSERT INTO tests (
                        visit_date, assigned_test_date, patient_name, phone_number,
                        gender, age, dob, parents, relation, alternate_phone_no,
                        limb, test_name, referred_by, test_done_by,
                        total_amount, advance_amount, due_amount, payment_method,
                        payment_status, branch_id
                    ) VALUES (
                        :visit_date, :assigned_test_date, :patient_name, :phone_number,
                        :gender, :age, :dob, :parents, :relation, :alternate_phone_no,
                        :limb, :test_name, :referred_by, :test_done_by,
                        :total_amount, :advance_amount, :due_amount, :payment_method,
                        :payment_status, :branch_id
                    )
                ");

                $stmt->execute([
                    ':visit_date'         => $visit_date,
                    ':assigned_test_date' => $assigned_test_date,
                    ':patient_name'       => $patient_name,
                    ':phone_number'       => $phone_number,
                    ':gender'             => $gender,
                    ':age'                => $age,
                    ':dob'                => $dob,
                    ':parents'            => $parents,
                    ':relation'           => $relation,
                    ':alternate_phone_no' => $alternate_phone_no,
                    ':limb'               => $limb,
                    ':test_name'          => $test_name,
                    ':referred_by'        => $referred_by,
                    ':test_done_by'       => $test_done_by,
                    ':total_amount'       => $total_amount,
                    ':advance_amount'     => $advance_amount,
                    ':due_amount'         => $due_amount,
                    ':payment_method'     => $payment_method,
                    ':payment_status'     => $payment_status,
                    ':branch_id'          => $branch_id
                ]);

                // 2. Logging the successful creation
                $newTestId = $pdo->lastInsertId();
                $logDetailsAfter = [
                    'patient_name' => $patient_name,
                    'test_name' => $test_name,
                    'assigned_test_date' => $assigned_test_date,
                    'total_amount' => $total_amount,
                    'payment_status' => $payment_status
                ];

                log_activity(
                    $pdo,
                    $user_id,
                    $username,
                    $branch_id,
                    'CREATE',
                    'tests',
                    (int)$newTestId,
                    null, // details_before is null for a new record
                    $logDetailsAfter // details_after contains the new data
                );

                $success_message = 'Test record added successfully!';
            } catch (Throwable $e) {
                error_log($e->getMessage());
                $errors[] = 'Failed to save test record: ' . $e->getMessage();
            }
        }
    }

    // Redirect with messages
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
    if ($success_message) {
        $_SESSION['success'] = $success_message;
    }
    header('Location: ../views/dashboard.php');
    exit();
}
