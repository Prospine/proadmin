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

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'reception'], true)) {
    $_SESSION['errors'] = ['Access denied. Insufficient permissions.'];
    header('Location: dashboard.php');
    exit();
}

// ✅ Ensure branch_id exists in session
$branch_id = $_SESSION['branch_id'] ?? null;
if ($branch_id === null) {
    $_SESSION['errors'] = ['No branch assigned. Please log in again.'];
    header('Location: ../login.php');
    exit();
}

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
        // FIX: If `dob` is empty, set it to null
        $dob               = !empty($_POST['dob']) ? $_POST['dob'] : null;
        $gender             = $_POST['gender'] ?? '';
        // FIX: If optional string fields are empty, set to null
        $parents            = !empty(trim($_POST['parents'] ?? '')) ? trim($_POST['parents']) : null;
        $relation           = !empty(trim($_POST['relation'] ?? '')) ? trim($_POST['relation']) : null;
        // Corrected form field name from 'patient_phone_number' to 'phone_number'
        $phone_number       = trim($_POST['phone_number'] ?? '');
        // FIX: If alternate phone is empty, set to null
        $alternate_phone_no = !empty(trim($_POST['alternate_phone_no'] ?? '')) ? trim($_POST['alternate_phone_no']) : null;
        // FIX: If referred_by is empty, set to null
        $referred_by        = !empty(trim($_POST['referred_by'] ?? '')) ? trim($_POST['referred_by']) : null;
        $test_name          = $_POST['test_name'] ?? '';
        // FIX: If limb is empty, set to null.
        $limb               = !empty($_POST['limb']) ? $_POST['limb'] : null;
        $test_done_by       = $_POST['test_done_by'] ?? '';
        $total_amount       = filter_var($_POST['total_amount'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
        // These are fine as they default to 0.0, which the DB accepts.
        $advance_amount     = filter_var($_POST['advance_amount'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: 0.0;
        $due_amount         = filter_var($_POST['due_amount'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: 0.0;
        $payment_method     = $_POST['payment_method'] ?? '';

        // Define valid options from your form and database schema
        $valid_genders = ['Male', 'Female', 'Other'];
        // VALIDATION LIST FOR TEST NAME MUST MATCH YOUR FORM OPTIONS!
        $valid_test_names = ['eeg', 'ncv', 'emg', 'rns', 'bera', 'vep', 'other'];
        $valid_limbs = ['upper_limb', 'lower_limb', 'both', 'none'];
        $valid_staff = ['achal', 'ashish', 'pancham', 'sayan'];
        $valid_payment_methods = ['cash', 'upi', 'card', 'cheque', 'other'];

        // Validation - now much more specific and accurate
        if (empty($visit_date) || empty($assigned_test_date) || empty($patient_name) || !$age || $total_amount === false) {
            $errors[] = 'Please fill all required text and number fields correctly.';
        }
        if (!DateTime::createFromFormat('Y-m-d', $visit_date) || !DateTime::createFromFormat('Y-m-d', $assigned_test_date)) {
            $errors[] = 'Invalid date format for visit or test date.';
        }
        if (!in_array($gender, $valid_genders, true)) {
            $errors[] = 'Please select a valid gender.';
        }
        if (!in_array($test_name, $valid_test_names, true)) {
            $errors[] = 'Please select a valid test name.';
        }
        if (!in_array($test_done_by, $valid_staff, true)) {
            $errors[] = 'Please select a valid staff member.';
        }
        if (!in_array($payment_method, $valid_payment_methods, true)) {
            $errors[] = 'Please select a valid payment method.';
        }
        // Limb validation now allows null
        if ($limb !== null && !in_array($limb, $valid_limbs, true)) {
            $errors[] = 'Please select a valid limb.';
        }

        // Use the phone_number variable
        if (empty($phone_number) || !preg_match('/^\+?\d{10,15}$/', $phone_number)) {
            $errors[] = 'Invalid patient phone number format.';
        }
        // Alternate phone number validation now allows null
        if ($alternate_phone_no !== null && !preg_match('/^\+?\d{10,15}$/', $alternate_phone_no)) {
            $errors[] = 'Invalid alternate phone number format.';
        }

        if (empty($errors)) {
            try {
                // Payment status logic
                $payment_status = 'pending';
                if ($due_amount == 0 && $total_amount > 0) {
                    $payment_status = 'paid';
                } elseif ($advance_amount > 0 && $due_amount > 0) {
                    $payment_status = 'partial';
                }

                // ✅ Insert with all fields from your database schema
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
