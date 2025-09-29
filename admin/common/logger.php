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

/**
 * Creates notifications for all users in a specific branch with given roles.
 *
 * @param PDO $pdo The database connection.
 * @param int $branchId The ID of the branch to notify.
 * @param array $roles An array of roles to send the notification to (e.g., ['doctor', 'jrdoctor']).
 * @param string $message The notification message.
 * @param ?string $linkUrl The optional URL for the notification link.
 */
function create_notification_for_roles(PDO $pdo, int $branchId, array $roles, string $message, ?string $linkUrl = null)
{
    if (empty($roles)) {
        return; // Don't do anything if no roles are specified
    }

    // 1. Find all users with the specified roles in the given branch
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $sql_users = "SELECT id FROM users WHERE branch_id = ? AND role IN ({$placeholders})";

    $stmt_users = $pdo->prepare($sql_users);
    $params = array_merge([$branchId], $roles);
    $stmt_users->execute($params);
    $userIds = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

    if (empty($userIds)) {
        return; // No users with these roles found in this branch
    }

    // 2. Prepare the notification insert statement
    $sql_insert = "INSERT INTO notifications (user_id, branch_id, message, link_url) VALUES (?, ?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql_insert);

    // 3. Loop through the user IDs and create a notification for each one
    foreach ($userIds as $userId) {
        $stmt_insert->execute([$userId, $branchId, $message, $linkUrl]);
    }
}
