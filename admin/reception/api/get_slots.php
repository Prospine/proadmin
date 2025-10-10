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

// --- MODIFICATION START ---
// Get the date from the query parameter (e.g., ?date=2025-09-30).
// If it's not provided, default to the current date.
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Basic validation to ensure it's a valid date format
if (!DateTime::createFromFormat('Y-m-d', $selectedDate)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit;
}
// --- MODIFICATION END ---


try {
    // Fetch filled slots for the SELECTED date.
    // IMPORTANT: I've changed the query to check `appointment_date` instead of `created_at`.
    // This is likely what you want. A registration created today could be for an appointment tomorrow.
    $stmt = $pdo->prepare("
        SELECT appointment_time 
        FROM registration 
        WHERE appointment_date = :selected_date -- Changed from DATE(created_at)
          AND branch_id = :branch_id
          AND appointment_time IS NOT NULL
    ");
    $stmt->execute([
        ':selected_date' => $selectedDate, // Changed from $today
        ':branch_id' => $branchId
    ]);
    $filledSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Normalize filled times to H:i format
    $filledSlots = array_map(fn($t) => date('H:i', strtotime($t)), $filledSlots);

    // Generate slots (11:00 AM to 7:00 PM, every 30 minutes)
    $slots = [];
    $start = new DateTime('09:00');
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
