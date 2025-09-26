<?php
session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header("Content-Type: application/json");
require_once '../../common/db.php'; // defines $pdo

if (!isset($pdo) || !$pdo) {
    echo json_encode(["status" => "error", "message" => "Database connection not initialized"]);
    exit;
}

try {
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['test_id']) || !isset($input['amount'])) {
        echo json_encode(["status" => "error", "message" => "Invalid request"]);
        exit;
    }

    $test_id = intval($input['test_id']);
    $amount  = floatval($input['amount']);

    // Fetch current test data
    $stmt = $pdo->prepare("SELECT total_amount, advance_amount, due_amount, payment_status 
                           FROM tests WHERE test_id = ?");
    $stmt->execute([$test_id]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(["status" => "error", "message" => "Test not found"]);
        exit;
    }

    $advance = floatval($row['advance_amount']);
    $due     = floatval($row['due_amount']);

    if ($amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid payment amount"]);
        exit;
    }

    if ($amount > $due) {
        echo json_encode(["status" => "error", "message" => "Payment exceeds due amount"]);
        exit;
    }

    // Calculate new values
    $new_advance = $advance + $amount;
    $new_due     = $due - $amount;
    $new_status  = ($new_due == 0) ? "paid" : "partial";

    // Update record
    $update = $pdo->prepare("UPDATE tests 
        SET advance_amount = ?, due_amount = ?, payment_status = ?, updated_at = NOW() 
        WHERE test_id = ?");
    $success = $update->execute([$new_advance, $new_due, $new_status, $test_id]);

    if ($success) {
        echo json_encode([
            "status"  => "success",
            "message" => "Payment updated successfully",
            "data"    => [
                "advance_amount" => $new_advance,
                "due_amount"     => $new_due,
                "payment_status" => $new_status
            ]
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Database update failed"]);
    }
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
