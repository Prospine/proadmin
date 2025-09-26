<?php

declare(strict_types=1);
session_start();
header("Content-Type: application/json");

require_once '../../common/db.php'; // contains $pdo

try {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['test_id']) || !isset($input['payment_status'])) {
        echo json_encode(["status" => "error", "message" => "Invalid request"]);
        exit;
    }

    $test_id = (int)$input['test_id'];
    $payment_status = $input['payment_status'];

    // allowed statuses
    $allowed = ['unpaid', 'partial', 'paid'];
    if (!in_array($payment_status, $allowed, true)) {
        echo json_encode(["status" => "error", "message" => "Invalid payment status"]);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tests 
                           SET payment_status = :status, updated_at = NOW() 
                           WHERE test_id = :id");
    $stmt->execute([
        ':status' => $payment_status,
        ':id' => $test_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Payment status updated successfully",
            "data" => [
                "test_id" => $test_id,
                "payment_status" => $payment_status
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "No changes made"]);
    }
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
