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
$registrationId = filter_var($data['registration_id'] ?? null, FILTER_VALIDATE_INT);
$newDate = $data['new_date'] ?? null;
$newTimeSlot = $data['new_time_slot'] ?? null;

if (!$registrationId || !$newDate || !$newTimeSlot) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: registration_id, new_date, or new_time_slot.']);
    exit;
}

if (!DateTime::createFromFormat('Y-m-d', $newDate) || !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $newTimeSlot)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date or time format.']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "UPDATE registration SET appointment_date = :new_date, appointment_time = :new_time WHERE registration_id = :reg_id AND branch_id = :branch_id"
    );

    $stmt->execute([
        ':new_date' => $newDate,
        ':new_time' => $newTimeSlot,
        ':reg_id' => $registrationId,
        ':branch_id' => $branchId
    ]);

    log_activity($pdo, $userId, $username, $branchId, 'UPDATE', 'registration', $registrationId, null, ['rescheduled_to' => "$newDate $newTimeSlot"]);

    echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully!']);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Reschedule Registration Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}