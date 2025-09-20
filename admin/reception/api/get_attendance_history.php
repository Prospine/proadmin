<?php

declare(strict_types=1);
session_start();

header('Content-Type: application/json');
require_once '../../common/db.php';

if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit();
}

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A valid patient ID is required.']);
    exit();
}
$patientId = (int)$_GET['id'];

try {
    // Get the patient's name for the drawer header
    $stmtInfo = $pdo->prepare("
        SELECT r.patient_name
        FROM patients p
        JOIN registration r ON p.registration_id = r.registration_id
        WHERE p.patient_id = :patient_id
    ");
    $stmtInfo->execute([':patient_id' => $patientId]);
    $patientInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if (!$patientInfo) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Patient not found.']);
        exit();
    }

    // Get all attendance dates for this patient
    $stmtDates = $pdo->prepare("
        SELECT attendance_date
        FROM attendance
        WHERE patient_id = :patient_id
    ");
    $stmtDates->execute([':patient_id' => $patientId]);

    // Use fetchAll with PDO::FETCH_COLUMN to get a simple array of dates
    $attendanceDates = $stmtDates->fetchAll(PDO::FETCH_COLUMN, 0);

    $response = [
        'success' => true,
        'patient_name' => $patientInfo['patient_name'],
        'attendance_dates' => $attendanceDates
    ];

    echo json_encode($response);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
