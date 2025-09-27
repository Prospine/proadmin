<?php
// Database connection
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require 'admin/db.php';

try {
    // Get POST data
    $full_name = $_POST['name'] ?? '';
    $email     = $_POST['email'] ?? '';
    $phone     = $_POST['phone'] ?? '';
    $message   = $_POST['message'] ?? '';

    if (!empty($full_name) && !empty($email) && !empty($phone) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO job_applications (full_name, email, phone, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $phone, $message]);

        echo json_encode(["status" => "success", "message" => "Application submitted successfully!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "DB Error: " . $e->getMessage()]);
}
