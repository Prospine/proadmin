<?php
require_once '../../common/db.php';
require_once '../../common/logger.php';

session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (empty($data['patient_name']) || empty($data['age']) || empty($data['gender']) || empty($data['phone'])) {
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
        INSERT INTO quick_inquiry 
        (name, age, gender, referralSource, chief_complain, phone_number, review, expected_visit_date, branch_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['patient_name'],
        $data['age'],
        $data['gender'],
        $data['referralSource'] ?? 'self',
        $data['conditionType'] ?? 'other',
        $data['phone'],
        $data['remarks'] ?? null,
        $data['expected_date'] ?? null,
        $branch_id
    ]);

    // 2. Logging the successful creation
    $newInquiryId = $pdo->lastInsertId();
    $logDetailsAfter = [
        'name' => $data['patient_name'],
        'age' => $data['age'],
        'phone_number' => $data['phone'],
        'referralSource' => $data['referralSource'] ?? 'self'
    ];

    log_activity(
        $pdo,
        $user_id,
        $username,
        $branch_id,
        'CREATE',
        'quick_inquiry',
        (int)$newInquiryId,
        null, // details_before is null for a new record
        $logDetailsAfter // details_after contains the new data
    );


    echo json_encode(["success" => true, "message" => "Inquiry saved successfully."]);
} catch (Exception $e) {
    // Note: Logging is not performed if an exception is caught before the DB insert.
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}