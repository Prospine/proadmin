<?php

declare(strict_types=1);
session_start();

require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection

header('Content-Type: application/json');

// Ensure user logged in
if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$today = date('Y-m-d');

try {
    // Fetch filled slots for today (based on created_at date)
    $stmt = $pdo->prepare("
        SELECT appointment_time 
        FROM registration 
        WHERE DATE(created_at) = :today 
          AND branch_id = :branch_id
          AND appointment_time IS NOT NULL
    ");
    $stmt->execute([
        ':today' => $today,
        ':branch_id' => $branchId
    ]);
    $filledSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Normalize filled times to H:i format
    $filledSlots = array_map(fn($t) => date('H:i', strtotime($t)), $filledSlots);

    // Generate slots (11:00 AM to 7:00 PM, every 30 minutes)
    $slots = [];
    $start = new DateTime('11:00');
    $end   = new DateTime('19:00');

    while ($start < $end) {
        $time = $start->format('H:i');
        $slots[] = [
            'time'     => $time,
            'label'    => $start->format('h:i A'),
            'disabled' => in_array($time, $filledSlots)
        ];
        $start->modify('+30 minutes');
    }

    echo json_encode(['success' => true, 'slots' => $slots]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
