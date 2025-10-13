<?php

declare(strict_types=1);
session_start();

require_once '../../common/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['branch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$searchTerm = $_GET['q'] ?? '';

if (strlen($searchTerm) < 2) {
    echo json_encode([]); // Return empty array if search term is too short
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            p.patient_id,
            r.patient_name,
            pm.patient_uid,
            r.age,
            r.gender,
            r.phone_number
        FROM registration r
        LEFT JOIN patients p ON r.registration_id = p.registration_id
        LEFT JOIN patient_master pm ON r.master_patient_id = pm.master_patient_id
        WHERE
            r.branch_id = :branch_id AND (
                r.patient_name LIKE :term_name OR
                pm.patient_uid LIKE :term_uid OR
                r.phone_number LIKE :term_phone
            )
        LIMIT 10
    ");

    $stmt->execute([
        ':branch_id' => $branchId,
        // Use two separate placeholders to avoid driver issues
        ':term_name' => "%$searchTerm%",
        ':term_uid' => "%$searchTerm%",
        ':term_phone' => "%$searchTerm%"
    ]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}