<?php

declare(strict_types=1);
session_start();

require_once '../../common/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not authenticated.']);
    exit;
}

$testId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$testId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing test ID.']);
    exit;
}

try {
    // 1. Fetch the main test record (the "order header")
    $stmtMain = $pdo->prepare("SELECT * FROM tests WHERE test_id = ? AND branch_id = ?");
    $stmtMain->execute([$testId, $_SESSION['branch_id']]);
    $mainTest = $stmtMain->fetch(PDO::FETCH_ASSOC);

    if (!$mainTest) {
        http_response_code(404);
        echo json_encode(['error' => 'Test record not found.']);
        exit;
    }

    // 2. Fetch all associated test items from the new table
    $stmtItems = $pdo->prepare("SELECT * FROM test_items WHERE test_id = ? ORDER BY item_id ASC");
    $stmtItems->execute([$testId]);
    $testItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // 3. Combine the results and send back
    $mainTest['test_items'] = $testItems;

    echo json_encode($mainTest);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Fetch Test Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error while fetching test details.']);
}