<?php
session_start();
require_once '../../common/db.php';
require_once '../../common/logger.php'; // 1. Added the logger
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (empty($data['patient_name']) || empty($data['test_name']) || empty($data['phone_number'])) {
        throw new Exception("Required fields are missing.");
    }

    // Get session variables for logging
    if (!isset($_SESSION['branch_id']) || !isset($_SESSION['uid']) || !isset($_SESSION['username'])) {
        throw new Exception("User session details are incomplete.");
    }
    $branch_id = $_SESSION['branch_id'];
    $user_id = $_SESSION['uid'];
    $username = $_SESSION['username'];

    $stmt = $pdo->prepare("
        INSERT INTO test_inquiry 
        (name, testname, reffered_by, mobile_number, expected_visit_date, branch_id) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['patient_name'],
        $data['test_name'],
        $data['referred_by'] ?? null,
        $data['phone_number'],
        $data['expected_visit_date'] ?? null,
        $branch_id
    ]);

    // 2. Logging the successful creation
    $newTestInquiryId = $pdo->lastInsertId();
    $logDetailsAfter = [
        'name' => $data['patient_name'],
        'testname' => $data['test_name'],
        'mobile_number' => $data['phone_number'],
        'reffered_by' => $data['referred_by'] ?? null
    ];

    log_activity(
        $pdo,
        $user_id,
        $username,
        $branch_id,
        'CREATE',
        'test_inquiry',
        (int)$newTestInquiryId,
        null, // details_before is null for a new record
        $logDetailsAfter // details_after contains the new data
    );

    echo json_encode(["success" => true, "message" => "Test inquiry saved successfully."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
