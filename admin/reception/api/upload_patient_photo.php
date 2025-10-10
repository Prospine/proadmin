<?php

declare(strict_types=1);
session_start();

header('Content-Type: application/json');

// Basic security and session check
if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../../common/db.php';
require_once '../../common/logger.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $registrationId = $data['registration_id'] ?? null;
    $imageData = $data['image_data'] ?? null;

    if (!$registrationId || !is_numeric($registrationId) || !$imageData) {
        http_response_code(400);
        $response['message'] = 'Invalid input. Registration ID and image data are required.';
    } else {
        try {
            // --- Decode and Save Image ---
            // Get image data (e.g., "data:image/jpeg;base64,ABC...")
            list($type, $imageData) = explode(';', $imageData);
            list(, $imageData)      = explode(',', $imageData);
            $imageData = base64_decode($imageData);

            // --- 2. Prepare File Path and Save Image ---
            $uploadDir = '../../uploads/patient_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = "reg_{$registrationId}_" . time() . ".jpeg";
            $filePath = $uploadDir . $fileName;

            if (file_put_contents($filePath, $imageData)) {
                // --- 3. Update Database with Relative Path ---
                $relativePath = 'uploads/patient_photos/' . $fileName;

                $stmt = $pdo->prepare("UPDATE registration SET patient_photo_path = :path WHERE registration_id = :id AND branch_id = :branch_id");
                $stmt->execute(['path' => $relativePath, 'id' => $registrationId, 'branch_id' => $_SESSION['branch_id']]);

                if ($stmt->rowCount() > 0) {
                    log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'], 'UPDATE', 'registration', (int)$registrationId, ['patient_photo_path' => 'old'], ['patient_photo_path' => $relativePath]);
                    $response = ['success' => true, 'message' => 'Photo uploaded successfully!', 'filePath' => '/proadmin/' . $relativePath];
                } else {
                    $response['message'] = 'Failed to update patient record. Patient may not exist in this branch.';
                }
            } else {
                http_response_code(500);
                $response['message'] = 'Failed to save image file to the server.';
            }
        } catch (Exception $e) {
            error_log("Photo upload failed for registration ID {$registrationId}: " . $e->getMessage());
            http_response_code(500);
            $response['message'] = 'Server error during photo upload.';
        }
    }
}

echo json_encode($response);