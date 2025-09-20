<?php

declare(strict_types=1);
session_start();

// Set the content type to JSON for API responses
header('Content-Type: application/json');

require_once '../../common/db.php';

// Basic security check: ensure the user is logged in
if (!isset($_SESSION['uid'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

// -------------------------
// Input Validation
// -------------------------
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'A valid patient ID is required.']);
    exit();
}
$patientId = (int)$_GET['id'];

// -------------------------
// Fetch Data from Database
// -------------------------
try {
    // Query 1: Get the patient's basic info (name, consultation fee)
    $stmtInfo = $pdo->prepare("
        SELECT r.patient_name, r.consultation_amount
        FROM patients p
        JOIN registration r ON p.registration_id = r.registration_id
        WHERE p.patient_id = :patient_id
    ");
    $stmtInfo->execute([':patient_id' => $patientId]);
    $patientInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if (!$patientInfo) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Patient not found.']);
        exit();
    }

    // Query 2: Get all payments associated with this patient
    $stmtPayments = $pdo->prepare("
        SELECT payment_date, amount, mode, remarks
        FROM payments
        WHERE patient_id = :patient_id
        ORDER BY payment_date ASC
    ");
    $stmtPayments->execute([':patient_id' => $patientId]);
    $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

    // -------------------------
    // Prepare and Return JSON Response
    // -------------------------
    $response = [
        'success' => true,
        'patient_name' => $patientInfo['patient_name'],
        'consultation_amount' => (float)$patientInfo['consultation_amount'],
        'payments' => $payments
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // In a production environment, you would log the error instead of echoing it
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
