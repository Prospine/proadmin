<?php

declare(strict_types=1);
session_start();
require_once '../../common/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUserId = (int)$_SESSION['uid'];
$action = $_GET['action'] ?? '';

// =============================
// --- ACTION: SEND MESSAGE ---
// =============================
if ($action === 'send') {
    $data = json_decode(file_get_contents('php://input'), true);
    $receiverId = (int)($data['receiver_id'] ?? 0);
    $messageText = trim($data['message_text'] ?? '');

    if (empty($receiverId) || empty($messageText)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing receiver or message text.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO chat_messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)"
        );
        $stmt->execute([$currentUserId, $receiverId, $messageText]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}


// ==============================
// --- ACTION: FETCH MESSAGES ---
// ==============================
// ==============================
// --- ACTION: FETCH MESSAGES ---
// ==============================
if ($action === 'fetch') {
    $partnerId = (int)($_GET['partner_id'] ?? 0);

    if (empty($partnerId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing partner ID.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Step 1: Fetch the conversation (Corrected Query)
        $stmtFetch = $pdo->prepare(
            "SELECT message_id, sender_id, message_text, created_at 
             FROM chat_messages
             WHERE (sender_id = ? AND receiver_id = ?)
                OR (sender_id = ? AND receiver_id = ?)
             ORDER BY created_at ASC"
        );
        // Provide the variables for all four placeholders
        $stmtFetch->execute([$currentUserId, $partnerId, $partnerId, $currentUserId]);
        $messages = $stmtFetch->fetchAll(PDO::FETCH_ASSOC);

        // Step 2: Mark messages from the partner as read (this part was already correct)
        $stmtUpdate = $pdo->prepare(
            "UPDATE chat_messages SET is_read = 1 
             WHERE sender_id = ? AND receiver_id = ? AND is_read = 0"
        );
        $stmtUpdate->execute([$partnerId, $currentUserId]);

        $pdo->commit();

        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// If no valid action is provided
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
