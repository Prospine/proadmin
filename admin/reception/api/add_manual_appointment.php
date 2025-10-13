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
$serviceType = $data['service_type'] ?? null;
$appointmentDate = $data['appointment_date'] ?? null;
$timeSlot = $data['time_slot'] ?? null;

if (!$patientId || !$serviceType || !$appointmentDate || !$timeSlot) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Check slot capacity
    $capacity = ($serviceType === 'physio') ? 10 : 1;

    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) FROM patient_appointments
        WHERE branch_id = ? AND service_type = ? AND appointment_date = ? AND time_slot = ?
    ");
    $stmtCount->execute([$branchId, $serviceType, $appointmentDate, $timeSlot]);
    $currentBookings = (int)$stmtCount->fetchColumn();

    if ($currentBookings >= $capacity) {
        throw new Exception("This time slot is already full. Please select another slot.");
    }

    // 2. Insert the new appointment
    $stmtInsert = $pdo->prepare("
        INSERT INTO patient_appointments (patient_id, branch_id, service_type, appointment_date, time_slot, status)
        VALUES (?, ?, ?, ?, ?, 'scheduled')
    ");
    $stmtInsert->execute([$patientId, $branchId, $serviceType, $appointmentDate, $timeSlot]);
    $newAppointmentId = $pdo->lastInsertId();

    // 3. Log the activity
    $logDetails = ['patient_id' => $patientId, 'date' => $appointmentDate, 'time' => $timeSlot, 'service' => $serviceType];
    log_activity($pdo, $userId, $username, $branchId, 'CREATE', 'patient_appointments', (int)$newAppointmentId, null, $logDetails);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Appointment added to schedule successfully!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Add Manual Appointment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}