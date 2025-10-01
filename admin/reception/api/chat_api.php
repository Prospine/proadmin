<?php

declare(strict_types=1);
session_start();
require_once '../../common/db.php';
require_once '../../common/config.php'; // Our new secret key!
header('Content-Type: application/json');

if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUserId = (int)$_SESSION['uid'];
$action = $_GET['action'] ?? '';

// =============================
// --- ENCRYPTION HELPERS ---
// =============================
const ENCRYPTION_METHOD = 'aes-256-cbc';

/**
 * Encrypts a message.
 * @param string $plaintext The message to encrypt.
 * @param string $key The secret encryption key.
 * @return string The base64 encoded payload (iv:ciphertext).
 */
function encryptMessage(string $plaintext, string $key): string
{
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $ciphertext = openssl_encrypt($plaintext, ENCRYPTION_METHOD, $key, 0, $iv);
    // Combine IV and ciphertext, then base64 encode for safe storage.
    return base64_encode($iv) . ':' . base64_encode($ciphertext);
}

/**
 * Decrypts a message payload.
 * @param string $payload The base64 encoded payload (iv:ciphertext).
 * @param string $key The secret encryption key.
 * @return string|false The original message or false on failure.
 */
function decryptMessage(string $payload, string $key)
{
    list($iv_b64, $ciphertext_b64) = explode(':', $payload, 2);
    if (!$iv_b64 || !$ciphertext_b64) {
        return false; // Invalid payload format
    }
    $iv = base64_decode($iv_b64);
    $ciphertext = base64_decode($ciphertext_b64);
    return openssl_decrypt($ciphertext, ENCRYPTION_METHOD, $key, 0, $iv);
}


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
        // ENCRYPT the message before storing it
        $encryptedMessage = encryptMessage($messageText, CHAT_ENCRYPTION_KEY);

        $stmt = $pdo->prepare(
            "INSERT INTO chat_messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)"
        );
        $stmt->execute([$currentUserId, $receiverId, $encryptedMessage]);

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
if ($action === 'fetch') {
    $partnerId = (int)($_GET['partner_id'] ?? 0);

    if (empty($partnerId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing partner ID.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmtFetch = $pdo->prepare(
            "SELECT message_id, sender_id, message_text, created_at, is_read 
     FROM chat_messages
     WHERE (sender_id = ? AND receiver_id = ?)
        OR (sender_id = ? AND receiver_id = ?)
     ORDER BY created_at ASC"
        );

        $stmtFetch->execute([$currentUserId, $partnerId, $partnerId, $currentUserId]);
        $messages = $stmtFetch->fetchAll(PDO::FETCH_ASSOC);

        // DECRYPT messages after fetching
        $decryptedMessages = array_map(function ($msg) {
            $msg['message_text'] = decryptMessage($msg['message_text'], CHAT_ENCRYPTION_KEY);
            return $msg;
        }, $messages);

        $stmtUpdate = $pdo->prepare(
            "UPDATE chat_messages SET is_read = 1 
             WHERE sender_id = ? AND receiver_id = ? AND is_read = 0"
        );
        $stmtUpdate->execute([$partnerId, $currentUserId]);

        $pdo->commit();

        echo json_encode(['success' => true, 'messages' => $decryptedMessages]);
    } catch (Exception $e) { // Catch generic Exception for decryption failures
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        error_log("Chat Fetch Error: " . $e->getMessage()); // Log the actual error
        echo json_encode(['success' => false, 'message' => 'Could not retrieve or decrypt messages.']);
    }
    exit;
}

// If no valid action is provided
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
