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
$registrationId = filter_var($data['registrationId'] ?? null, FILTER_VALIDATE_INT);
$treatmentType = $data['treatmentType'] ?? null;
$treatmentDays = filter_var($data['treatmentDays'] ?? null, FILTER_VALIDATE_INT);
$startDate = $data['startDate'] ?? null;
$totalCost = filter_var($data['totalCost'] ?? 0, FILTER_VALIDATE_FLOAT);
$advancePayment = filter_var($data['advancePayment'] ?? 0, FILTER_VALIDATE_FLOAT);
$discount = filter_var($data['discount'] ?? 0, FILTER_VALIDATE_FLOAT);
$dueAmount = filter_var($data['dueAmount'] ?? 0, FILTER_VALIDATE_FLOAT);
$paymentMethod = $data['payment_method'] ?? null;
$discountApprovedBy = filter_var($data['discount_approved_by'] ?? null, FILTER_VALIDATE_INT);
$timeSlot = $data['time_slot'] ?? null; // NEW: Get time slot

if (!$registrationId || !$treatmentType || !$treatmentDays || !$startDate || !$paymentMethod) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// NEW: Validate time slot
if (empty($timeSlot)) {
    throw new Exception('A time slot must be selected for the treatment plan.');
}

try {
    $pdo->beginTransaction();

    // 1. Check if patient already exists for this registration ID
    $stmtCheck = $pdo->prepare("SELECT 1 FROM patients WHERE registration_id = ?");
    $stmtCheck->execute([$registrationId]);
    if ($stmtCheck->fetch()) {
        throw new Exception("This registration has already been converted to a patient.");
    }

    // 2. Calculate end date
    $endDate = (new DateTime($startDate))->modify('+' . ($treatmentDays - 1) . ' days')->format('Y-m-d');

    // 3. Treatment cost logic (mirroring add_patient.php)
    $treatmentCostPerDay = null;
    $packageCost = null;

    if ($treatmentType === 'daily') {
        if (!$treatmentDays || $treatmentDays <= 0) {
            throw new Exception('Number of days is required for daily treatment.');
        }
        // The total cost is calculated on the front-end, so we derive the per-day cost from it.
        $treatmentCostPerDay = $totalCost / $treatmentDays;
    } elseif ($treatmentType === 'package') {
        $packageCost = $totalCost;
    } else {
        throw new Exception('Invalid treatment type selected for speech therapy.');
    }

    // 3. Insert into patients table
    $stmtPatient = $pdo->prepare(
        "INSERT INTO patients (branch_id, registration_id, service_type, treatment_type, treatment_days, total_amount, advance_payment, discount_percentage, discount_approved_by, due_amount, start_date, end_date, payment_method, treatment_cost_per_day, package_cost, treatment_time_slot)
         VALUES (:branch_id, :reg_id, :service_type, :treat_type, :treat_days, :total, :advance, :discount, :approved_by, :due, :start, :end, :payment_method, :cost_per_day, :pkg_cost, :time_slot)"
    );

    $stmtPatient->execute([
        ':branch_id' => $branchId,
        ':reg_id' => $registrationId,
        ':service_type' => 'speech_therapy', // This is the key change
        ':treat_type' => $treatmentType,
        ':treat_days' => $treatmentDays,
        ':total' => $totalCost,
        ':advance' => $advancePayment,
        ':discount' => $discount,
        ':approved_by' => $discountApprovedBy ?: null,
        ':due' => $dueAmount,
        ':start' => $startDate,
        ':end' => $endDate,
        ':payment_method' => $paymentMethod,
        ':cost_per_day' => $treatmentCostPerDay,
        ':pkg_cost' => $packageCost,
        ':time_slot' => $timeSlot
    ]);
    $newPatientId = $pdo->lastInsertId();

    // NEW: Insert the first appointment into the new table
    $apptStmt = $pdo->prepare("
        INSERT INTO patient_appointments (patient_id, branch_id, appointment_date, time_slot, service_type, status)
        VALUES (?, ?, ?, ?, 'speech_therapy', 'scheduled')
    ");
    $apptStmt->execute([
        $newPatientId,
        $branchId,
        $startDate,
        $timeSlot
    ]);

    // 4. If there was an advance payment, record it in the payments table
    if ($advancePayment > 0) {
        $stmtPayment = $pdo->prepare(
            "INSERT INTO payments (patient_id, payment_date, amount, mode, remarks) VALUES (?, ?, ?, ?, ?)"
        );
        $stmtPayment->execute([$newPatientId, $startDate, $advancePayment, $paymentMethod, 'Initial advance payment']);
    }

    // 5. Update the original registration status to 'Consulted'
    $stmtUpdateReg = $pdo->prepare("UPDATE registration SET status = 'Consulted' WHERE registration_id = ?");
    $stmtUpdateReg->execute([$registrationId]);

    // 6. Log the activity
    log_activity($pdo, $userId, $username, $branchId, 'CREATE', 'patients', (int)$newPatientId, null, ['service_type' => 'speech_therapy', 'total_amount' => $totalCost]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Speech therapy patient added successfully!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Add Speech Patient Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
};
