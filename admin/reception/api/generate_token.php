<?php

declare(strict_types=1);
session_start();
header('Content-Type: application/json');

require_once '../../common/db.php';
require_once '../../common/logger.php';

// --- Validation and Auth ---
if (!isset($_SESSION['uid'], $_SESSION['branch_id'], $_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
if ($patientId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Patient ID.']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$userId = $_SESSION['uid'];
$username = $_SESSION['username'];
$tokenDate = date('Y-m-d');

try {
    $pdo->beginTransaction();

    // --- Generate a Unique Token UID ---
    $datePrefix = date('ymd'); // e.g., 251010
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tokens WHERE token_date = ?");
    $stmt->execute([$tokenDate]);
    $todayCount = $stmt->fetchColumn();
    $nextTokenNumber = $todayCount + 1;
    $tokenUid = 'T' . $datePrefix . '-' . str_pad((string)$nextTokenNumber, 2, '0', STR_PAD_LEFT);

    // --- Get the patient's service type ---
    $stmtService = $pdo->prepare("SELECT service_type FROM patients WHERE patient_id = ?");
    $stmtService->execute([$patientId]);
    $serviceType = $stmtService->fetchColumn();
    if (!$serviceType) {
        throw new Exception("Could not determine service type for the patient.");
    }

    // --- Insert the new token record ---
    $stmt = $pdo->prepare(
        "INSERT INTO tokens (token_uid, branch_id, patient_id, token_date, service_type) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$tokenUid, $branchId, $patientId, $tokenDate, $serviceType]);
    $newTokenId = $pdo->lastInsertId();

    // --- Log the activity ---
    log_activity(
        $pdo,
        $userId,
        $username,
        $branchId,
        'CREATE',
        'tokens',
        (int)$newTokenId,
        null,
        ['token_uid' => $tokenUid, 'patient_id' => $patientId]
    );

    // --- Fetch comprehensive data for the print modal ---
    $stmt = $pdo->prepare("
        SELECT
            r.patient_name,
            p.assigned_doctor,
            (SELECT COUNT(*) FROM attendance WHERE patient_id = p.patient_id) as attendance_count,
            p.treatment_days,
            (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE patient_id = p.patient_id) as total_paid
        FROM patients p
        JOIN registration r ON p.registration_id = r.registration_id
        WHERE p.patient_id = :patient_id
    ");
    $stmt->execute([':patient_id' => $patientId]);
    $patientData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patientData) {
        throw new Exception("Patient data not found after creating token.");
    }

    $pdo->commit();

    // --- Prepare and send the response ---
    $response = [
        'success' => true,
        'token_uid' => $tokenUid,
        'patient_name' => $patientData['patient_name'],
        'assigned_doctor' => $patientData['assigned_doctor'] ?? 'N/A',
        'attendance_progress' => ($patientData['attendance_count'] ?? 0) . ' / ' . ($patientData['treatment_days'] ?? '-'),
        'total_paid' => number_format((float)($patientData['total_paid'] ?? 0), 2),
        'token_date' => date('d M Y, h:i A')
    ];

    echo json_encode($response);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Token generation failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while generating the token.']);
}