<?php

declare(strict_types=1);

require_once '../../common/db.php';

session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$pdo->beginTransaction();

try {
    $branchId = $_SESSION['branch_id'] ?? null;
    if (!$branchId) {
        throw new Exception('Branch ID is missing. Please re-login.');
    }

    // ✅ Validate required fields
    if (empty($data['registrationId'])) {
        throw new Exception('Registration ID is required.');
    }
    if (empty($data['treatmentType'])) {
        throw new Exception('Treatment type is required.');
    }
    if (!isset($data['totalCost'])) {
        throw new Exception('Total cost is required.');
    }

    $registrationId  = $data['registrationId'];
    $treatmentType   = $data['treatmentType'];
    $treatmentDays   = isset($data['treatmentDays']) ? (int)$data['treatmentDays'] : null;
    $totalAmount     = (float)$data['totalCost'];
    $discount        = isset($data['discount']) ? (float)$data['discount'] : 0;
    $advancePayment  = isset($data['advancePayment']) ? (float)$data['advancePayment'] : 0;
    $dueAmount       = isset($data['dueAmount']) ? (float)$data['dueAmount'] : 0;
    $startDate       = $data['startDate'];
    $endDate         = $data['endDate'];
    $paymentMethod   = $data['payment_method'] ?? null;

    // 2️⃣ Treatment cost logic
    $treatmentCostPerDay = null;
    $packageCost = null;

    if ($treatmentType === 'daily' || $treatmentType === 'advance') {
        if (!$treatmentDays) {
            throw new Exception('Number of days is required for daily/advance treatment.');
        }
        $treatmentCostPerDay = $totalAmount / $treatmentDays;
    } elseif ($treatmentType === 'package') {
        $packageCost = $totalAmount;
        if (!$treatmentDays) $treatmentDays = 22;
    } else {
        throw new Exception('Invalid treatment type selected.');
    }

    // Insert into patients table
    $stmt = $pdo->prepare("
        INSERT INTO patients 
        (registration_id, branch_id, treatment_type, treatment_cost_per_day, package_cost, treatment_days, total_amount, payment_method, discount_percentage, advance_payment, due_amount, start_date, end_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $registrationId,
        $branchId,
        $treatmentType,
        $treatmentCostPerDay,
        $packageCost,
        $treatmentDays,
        $totalAmount,
        $paymentMethod,
        $discount,
        $advancePayment,
        $dueAmount,
        $startDate,
        $endDate
    ]);

    // Retrieve the patient_id of the newly inserted record
    $patientId = (int)$pdo->lastInsertId();

    // CRITICAL FIX: Insert into payments table if there was an advance payment
    if ($advancePayment > 0) {
        $paymentStmt = $pdo->prepare("
            INSERT INTO payments (patient_id, payment_date, amount, mode, remarks)
            VALUES (?, NOW(), ?, ?, 'Initial advance payment')
        ");
        $paymentStmt->execute([
            $patientId,
            $advancePayment,
            $paymentMethod
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Patient added successfully.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error adding patient: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
