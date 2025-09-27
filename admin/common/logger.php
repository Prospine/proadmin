<?php

require __DIR__ . '/db.php';

function log_activity(
    PDO $pdo,
    ?int $userId,
    string $username,
    ?int $branchId,
    string $actionType,
    ?string $targetTable = null,
    ?int $targetId = null,
    ?array $detailsBefore = null,
    ?array $detailsAfter = null
) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $sql = "INSERT INTO audit_log (user_id, username, branch_id, action_type, target_table, target_id, details_before, details_after, ip_address) 
            VALUES (:user_id, :username, :branch_id, :action_type, :target_table, :target_id, :details_before, :details_after, :ip_address)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':user_id' => $userId,
        ':username' => $username,
        ':branch_id' => $branchId,
        ':action_type' => $actionType,
        ':target_table' => $targetTable,
        ':target_id' => $targetId,
        ':details_before' => $detailsBefore ? json_encode($detailsBefore) : null,
        ':details_after' => $detailsAfter ? json_encode($detailsAfter) : null,
        ':ip_address' => $ipAddress
    ]);
}
