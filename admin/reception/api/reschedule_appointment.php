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
$appointmentId = filter_var($data['appointment_id'] ?? null, FILTER_VALIDATE_INT);
$serviceType = $data['service_type'] ?? null;
$newDate = $data['new_date'] ?? null;
$newTimeSlot = $data['new_time_slot'] ?? null;

if (!$appointmentId || !$serviceType || !$newDate || !$newTimeSlot) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields for rescheduling.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Check capacity of the NEW slot
    $capacity = ($serviceType === 'physio') ? 10 : 1;

    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) FROM patient_appointments
        WHERE branch_id = ? AND service_type = ? AND appointment_date = ? AND time_slot = ?
    ");
    $stmtCount->execute([$branchId, $serviceType, $newDate, $newTimeSlot . ':00']);
    $currentBookings = (int)$stmtCount->fetchColumn();

    if ($currentBookings >= $capacity) {
        throw new Exception("The new time slot is already full. Please select another slot.");
    }

    // 2. Update the appointment
    $stmtUpdate = $pdo->prepare("
        UPDATE patient_appointments
        SET appointment_date = ?, time_slot = ?
        WHERE appointment_id = ? AND branch_id = ?
    ");
    $stmtUpdate->execute([$newDate, $newTimeSlot, $appointmentId, $branchId]);

    // 3. Log the activity
    $logDetails = ['appointment_id' => $appointmentId, 'new_date' => $newDate, 'new_time' => $newTimeSlot];
    log_activity($pdo, $userId, $username, $branchId, 'UPDATE', 'patient_appointments', $appointmentId, null, $logDetails);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Reschedule Appointment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}