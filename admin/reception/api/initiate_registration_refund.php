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
$refundAmount = filter_var($data['refund_amount'] ?? null, FILTER_VALIDATE_FLOAT);
$refundReason = trim($data['refund_reason'] ?? '');

if (!$registrationId || !$refundAmount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required refund details (ID, Amount).']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtFetch = $pdo->prepare("SELECT consultation_amount FROM registration WHERE registration_id = ? AND branch_id = ?");
    $stmtFetch->execute([$registrationId, $_SESSION['branch_id']]);
    $reg = $stmtFetch->fetch();

    if (!$reg || $refundAmount > (float)$reg['consultation_amount']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Refund amount cannot exceed the amount paid.']);
        $pdo->rollBack();
        exit;
    }

    $stmtUpdate = $pdo->prepare("UPDATE registration SET refund_status = 'initiated' WHERE registration_id = ?");
    $stmtUpdate->execute([$registrationId]);

    $logDetails = ['refund_amount' => $refundAmount, 'reason' => $refundReason, 'new_status' => 'initiated'];
    log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'], 'UPDATE', 'registration', $registrationId, ['refund_status' => 'no'], $logDetails);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Refund has been successfully initiated.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log("Registration Refund Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}