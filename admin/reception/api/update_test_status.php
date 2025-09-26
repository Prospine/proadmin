<?php

declare(strict_types=1);
session_start();
header("Content-Type: application/json");

require_once '../../common/db.php'; // contains $pdo

try {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['test_id']) || !isset($input['test_status'])) {
        echo json_encode(["status" => "error", "message" => "Invalid request"]);
        exit;
    }

    $test_id = (int)$input['test_id'];
    $test_status = $input['test_status'];

    // allowed statuses
    $allowed = ['pending', 'completed', 'cancelled'];
    if (!in_array($test_status, $allowed, true)) {
        echo json_encode(["status" => "error", "message" => "Invalid test status"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tests 
                           SET test_status = :status, updated_at = NOW() 
                           WHERE test_id = :id");
    $stmt->execute([
        ':status' => $test_status,
        ':id' => $test_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Test status updated successfully",
            "data" => [
                "test_id" => $test_id,
                "test_status" => $test_status
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No changes made"]);
    }
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
