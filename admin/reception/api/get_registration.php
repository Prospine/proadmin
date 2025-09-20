<?php

require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$id = $_GET['id'] ?? 0;

// Fetch registration details
$stmt = $pdo->prepare("SELECT * FROM registration WHERE registration_id = ?");
$stmt->execute([$id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);

$response = $registration ?: [];

// Check if patient exists with the same registration_id
$patientStmt = $pdo->prepare("SELECT patient_id FROM patients WHERE registration_id = ?");
$patientStmt->execute([$id]);
$patient = $patientStmt->fetch(PDO::FETCH_ASSOC);

if ($patient) {
    $response['patient_exists'] = true;
    $response['patient_message'] = "✅ Patient already exists with Registration ID {$id}.";
} else {
    $response['patient_exists'] = false;
    $response['patient_message'] = "⚠️ No patient found for this Registration ID. You can add them.";
}

header('Content-Type: application/json');
echo json_encode($response);
