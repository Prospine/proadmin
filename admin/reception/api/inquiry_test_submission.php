<?php
session_start();
require_once '../../common/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

try {
    if (empty($data['patient_name']) || empty($data['test_name']) || empty($data['phone_number'])) {
        throw new Exception("Required fields are missing.");
    }

    if (empty($_SESSION['branch_id'])) {
        throw new Exception("Branch ID not found in session.");
    }

    $branch_id = $_SESSION['branch_id'];

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

    echo json_encode(["success" => true, "message" => "Test inquiry saved successfully."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
