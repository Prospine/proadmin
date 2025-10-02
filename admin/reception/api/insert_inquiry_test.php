<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once '../../common/db.php';
require_once '../../common/logger.php'; // 1. Added the logger
header('Content-Type: application/json');

// --- CSRF token check --- 
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid CSRF token. Please refresh and try again."]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Get session variables for logging
    if (!isset($_SESSION['branch_id']) || !isset($_SESSION['uid']) || !isset($_SESSION['username'])) {
        throw new Exception("User session details are incomplete. Please log in again.");
    }
    $branch_id = $_SESSION['branch_id'];
    $user_id = $_SESSION['uid'];
    $username = $_SESSION['username'];

    // Collect posted values
    $inquiry_id          = $_POST['inquiry_id'] ?: null;
    $patient_name        = trim($_POST['name'] ?? '');
    $age                 = $_POST['age'] ?: null;
    $dob                 = $_POST['dob'] ?: null;
    $gender              = $_POST['gender'] ?: null;
    $parents             = trim($_POST['parents'] ?? '') ?: null;
    $relation            = trim($_POST['relation'] ?? '') ?: null;
    $phone_number        = trim($_POST['mobile_number'] ?? '');
    $alternate_phone_no  = trim($_POST['alternate_phone_no'] ?? '') ?: null;
    $referred_by         = trim($_POST['reffered_by'] ?? '') ?: null;
    $test_name           = $_POST['testname'] ?: null;
    $limb                = $_POST['limb'] ?: null;
    $visit_date          = $_POST['visit_date'] ?: null;
    $assigned_test_date  = $_POST['assigned_test_date'] ?: null;
    $receipt_no          = trim($_POST['receipt_no'] ?? '') ?: null;
    $test_done_by        = $_POST['test_done_by'] ?: null;
    $total_amount        = is_numeric($_POST['total_amount'] ?? null) ? floatval($_POST['total_amount']) : 0;
    $advance_amount      = is_numeric($_POST['advance_amount'] ?? null) ? floatval($_POST['advance_amount']) : 0;
    $due_amount          = is_numeric($_POST['due_amount'] ?? null) ? floatval($_POST['due_amount']) : 0;
    $discount            = is_numeric($_POST['discount'] ?? null) ? floatval($_POST['discount']) : 0;
    $payment_method      = $_POST['payment_method'] ?: null;

    // Required fields check
    if (!$patient_name || !$phone_number || !$gender || !$test_name || !$visit_date || !$assigned_test_date || !$test_done_by || !$payment_method) {
        throw new Exception('Please fill all required fields');
    }

    // Payment status logic
    $payment_status = 'pending';
    if ($due_amount == 0 && $total_amount > 0) {
        $payment_status = 'paid';
    } elseif ($advance_amount > 0 && $due_amount > 0) {
        $payment_status = 'partial';
    }

    // Insert query
    $stmt = $pdo->prepare("
        INSERT INTO tests (
            inquiry_id, patient_name, age, dob, gender, parents, relation,
            phone_number, alternate_phone_no, referred_by, test_name, limb,
            visit_date, assigned_test_date, test_done_by,
            total_amount, advance_amount, due_amount, discount,
            payment_method, payment_status, branch_id
        ) VALUES (
            :inquiry_id, :patient_name, :age, :dob, :gender, :parents, :relation,
            :phone_number, :alternate_phone_no, :referred_by, :test_name, :limb,
            :visit_date, :assigned_test_date, :test_done_by,
            :total_amount, :advance_amount, :due_amount, :discount,
            :payment_method, :payment_status, :branch_id
        )
    ");

    $stmt->execute([
        ':inquiry_id'         => $inquiry_id,
        ':patient_name'       => $patient_name,
        ':age'                => $age,
        ':dob'                => $dob,
        ':gender'             => $gender,
        ':parents'            => $parents,
        ':relation'           => $relation,
        ':phone_number'       => $phone_number,
        ':alternate_phone_no' => $alternate_phone_no,
        ':referred_by'        => $referred_by,
        ':test_name'          => $test_name,
        ':limb'               => $limb,
        ':visit_date'         => $visit_date,
        ':assigned_test_date' => $assigned_test_date,
        ':test_done_by'       => $test_done_by,
        ':total_amount'       => $total_amount,
        ':advance_amount'     => $advance_amount,
        ':due_amount'         => $due_amount,
        ':discount'           => $discount,
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

    echo json_encode(['success' => true, 'message' => 'Test record added successfully!']);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
