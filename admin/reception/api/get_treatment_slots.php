<?php

declare(strict_types=1);
session_start();
header('Content-Type: application/json');

require_once '../../common/db.php';

if (!isset($_SESSION['branch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$date = $_GET['date'] ?? date('Y-m-d');
$serviceType = $_GET['service_type'] ?? 'physio'; // Default to physio

if (!in_array($serviceType, ['physio', 'speech_therapy'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid service type.']);
    exit;
}

try {
    // 1. Get counts of existing appointments for the given date, service, and branch
    $stmt = $pdo->prepare("
        SELECT time_slot, COUNT(*) as booked_count
        FROM patient_appointments
        WHERE branch_id = :branch_id AND appointment_date = :date AND service_type = :service_type
        GROUP BY time_slot
    ");
    $stmt->execute([
        ':branch_id' => $branchId,
        ':date' => $date,
        ':service_type' => $serviceType
    ]);
    $bookedSlots = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode([
        'success' => true,
        // Return only the booked slots and their counts
        'booked' => $bookedSlots
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error fetching treatment slots: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Could not fetch time slots.']);
}