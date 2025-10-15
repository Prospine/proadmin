<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

// --- Security & Session Check ---
if (!isset($_SESSION['uid'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit();
}

require_once '../../common/db.php';
require_once '../../common/logger.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];
$userId = $_SESSION['uid'];
$username = $_SESSION['username'];
$branchId = $_SESSION['branch_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['profile_photo'] ?? null;

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        $response['message'] = 'No file was uploaded or an upload error occurred.';
        echo json_encode($response);
        exit();
    }

    // --- File Validation ---
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowedTypes)) {
        $response['message'] = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
    } elseif ($file['size'] > $maxSize) {
        $response['message'] = 'File is too large. Maximum size is 5MB.';
    } else {
        try {
            // --- File Handling ---
            $uploadDir = '../../uploads/employee_photo/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // --- NEW: Check if directory is writable ---
            if (!is_writable($uploadDir)) {
                http_response_code(500);
                $response['message'] = 'Server error: The upload directory is not writable. Please check permissions.';
                throw new Exception("Upload directory {$uploadDir} is not writable.");
            }

            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = "employee_{$userId}_" . time() . '.' . $fileExtension;
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $relativePath = 'uploads/employee_photo/' . $fileName;

                // --- Database Update ---
                $stmt = $pdo->prepare("UPDATE employees SET photo_path = :path WHERE user_id = :user_id");
                $stmt->execute([':path' => $relativePath, ':user_id' => $userId]);

                if ($stmt->rowCount() > 0) {
                    log_activity($pdo, $userId, $username, $branchId, 'UPDATE', 'employees', $userId, ['photo_path' => 'old'], ['photo_path' => $relativePath]);
                    $response = ['success' => true, 'message' => 'Profile photo updated successfully!', 'filePath' => '/admin/' . $relativePath];
                } else {
                    // This might happen if the user doesn't have an employee record yet.
                    $response['message'] = 'Could not find an employee record to update.';
                }
            } else {
                http_response_code(500);
                $response['message'] = 'Failed to save the uploaded file.';
            }
        } catch (Exception $e) {
            error_log("Employee photo upload failed for user ID {$userId}: " . $e->getMessage());
            http_response_code(500);
            $response['message'] = 'A server error occurred during the upload process.';
        }
    }
}

echo json_encode($response);
