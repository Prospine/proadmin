<?php
require_once '../../common/db.php';
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (empty($data['patient_name']) || empty($data['age']) || empty($data['gender']) || empty($data['phone'])) {
        throw new Exception("Required fields are missing.");
    }

    // Get branch_id from session
    if (!isset($_SESSION['branch_id'])) {
        throw new Exception("Branch ID not found in session.");
    }
    $branch_id = $_SESSION['branch_id'];

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

    echo json_encode(["success" => true, "message" => "Inquiry saved successfully."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
