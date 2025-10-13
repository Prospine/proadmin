<?php

declare(strict_types=1);
session_start();

require_once '../../common/db.php';
require_once '../../common/logger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['uid'], $_SESSION['branch_id'], $_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$userId = $_SESSION['uid'];
$username = $_SESSION['username'];

$data = json_decode(file_get_contents('php://input'), true);

// --- Validation ---
$patientId = filter_var($data['patient_id'] ?? null, FILTER_VALIDATE_INT);
$patientName = trim($data['patient_name'] ?? '');
$testName = trim($data['test_name'] ?? '');
$totalAmount = filter_var($data['total_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$advanceAmount = filter_var($data['advance_amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$discount = filter_var($data['discount'] ?? 0, FILTER_VALIDATE_FLOAT);

if (!$patientId || empty($patientName) || empty($testName) || $totalAmount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Patient, Test Name, and Total Amount are required.']);
    exit;
}

$dueAmount = max(0.0, $totalAmount - $discount - $advanceAmount);

$paymentStatus = ($dueAmount <= 0) ? 'paid' : (($advanceAmount > 0) ? 'partial' : 'pending');

try {
    $pdo->beginTransaction();

    // Generate a unique test_uid
    $datePrefix = date('ymd');
    $stmtUid = $pdo->prepare("SELECT COUNT(*) FROM tests WHERE test_uid LIKE ?");
    $stmtUid->execute(["$datePrefix%"]);
    $todayCount = $stmtUid->fetchColumn();
    $testUid = $datePrefix . str_pad((string)($todayCount + 1), 2, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare(
        "INSERT INTO tests (patient_id, branch_id, test_uid, patient_name, age, gender, phone_number, alternate_phone_no, dob, parents, relation, referred_by, test_name, limb, assigned_test_date, test_done_by, total_amount, advance_amount, discount, due_amount, payment_method, payment_status,  test_status, visit_date)
         VALUES (:patient_id, :branch_id, :test_uid, :patient_name, :age, :gender, :phone_number, :alternate_phone_no, :dob, :parents, :relation, :referred_by, :test_name, :limb, :assigned_test_date, :test_done_by, :total_amount, :advance_amount, :discount, :due_amount, :payment_method, :payment_status, 'pending', :visit_date)"
    );

    $stmt->execute([
        ':patient_id' => $patientId,
        ':branch_id' => $branchId,
        ':test_uid' => $testUid,
        ':patient_name' => $patientName,
        ':age' => $data['age'] ?? null,
        ':gender' => $data['gender'] ?? null,
        ':phone_number' => $data['phone_number'] ?? null,
        ':alternate_phone_no' => $data['alternate_phone_no'] ?? null,
        ':dob' => !empty($data['dob']) ? $data['dob'] : null,
        ':parents' => $data['parents'] ?? null,
        ':relation' => $data['relation'] ?? null,
        ':referred_by' => $data['referred_by'] ?? null,
        ':test_name' => $testName,
        ':limb' => !empty($data['limb']) ? $data['limb'] : null,
        ':assigned_test_date' => $data['assigned_test_date'] ?? date('Y-m-d'),
        ':test_done_by' => $data['test_done_by'] ?? null,
        ':total_amount' => $totalAmount,
        ':advance_amount' => $advanceAmount,
        ':discount' => $discount,
        ':due_amount' => $dueAmount,
        ':payment_method' => $data['payment_method'] ?? 'cash',
        ':payment_status' => $paymentStatus,
        ':visit_date' => $data['visit_date'] ?? date('Y-m-d')
    ]);

    $newTestId = $pdo->lastInsertId();
    log_activity($pdo, $userId, $username, $branchId, 'CREATE', 'tests', (int)$newTestId, null, ['patient_id' => $patientId, 'test_name' => $testName]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Test added successfully for the patient.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log("Add Test for Patient Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>