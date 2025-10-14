<?php

declare(strict_types=1);
session_start();

require_once '../../common/db.php';
require_once '../../common/logger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['uid'], $_SESSION['branch_id'], $_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$registrationId = filter_var($data['registration_id'] ?? null, FILTER_VALIDATE_INT);

if (!$registrationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Registration ID.']);
    exit;
}

// Whitelist of columns that can be updated
$allowedFields = [
    'patient_name', 'phone_number', 'email', 'age', 'gender',
    'chief_complain', 'consultation_type', 'referralSource', 'reffered_by',
    'consultation_amount', 'payment_method', 'address', 'follow_up_date'
];

$updateFields = [];
$params = [];

foreach ($allowedFields as $field) {
    if (isset($data[$field])) {
        $value = $data[$field];
        // Convert empty strings for date fields to NULL to prevent SQL errors.
        if ($field === 'follow_up_date' && $value === '') {
            $value = null;
        }
        $updateFields[] = "{$field} = :{$field}";
        $params[$field] = $value;
    }
}

if (empty($updateFields)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid fields to update.']);
    exit;
}

$params['registration_id'] = $registrationId;
$params['branch_id'] = $_SESSION['branch_id'];

$sql = "UPDATE registration SET " . implode(', ', $updateFields) . " WHERE registration_id = :registration_id AND branch_id = :branch_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        // For simplicity, we are not logging the 'before' state here, but you could add it.
        log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'], 'UPDATE', 'registration', $registrationId, null, $data);
        echo json_encode(['success' => true, 'message' => 'Registration details updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes were made or record not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Update Registration Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>