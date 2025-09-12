<?php

declare(strict_types=1);
session_start();

// -------------------------
// Error Reporting (Dev Only)
// -------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// -------------------------
// Auth / Session Checks
// -------------------------
require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection

header('Content-Type: application/json');

// Check login & branch
if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$type     = $_GET['type'] ?? '';
$id       = $_GET['id'] ?? null;

if (!$id || !in_array($type, ['quick', 'test'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    if ($type === 'quick') {
        // Quick inquiry (same as your working version)
        $stmt = $pdo->prepare("
            SELECT qi.inquiry_id, qi.name, qi.phone_number, qi.age, qi.gender, qi.referralSource, 
                   qi.chief_complain, qi.review, qi.expected_visit_date, qi.created_at, qi.status,
                   r.registration_id, r.created_at AS registered_at
            FROM quick_inquiry qi
            LEFT JOIN registration r 
                   ON qi.inquiry_id = r.inquiry_id 
                  AND r.branch_id = :reg_branch_id
            WHERE qi.inquiry_id = :id 
              AND qi.branch_id = :branch_id
            LIMIT 1
        ");
        $params = [
            ':id'            => $id,
            ':branch_id'     => $branchId,
            ':reg_branch_id' => $branchId
        ];
    } else {
        // Test inquiry — check against tests table, not registration
        $stmt = $pdo->prepare("
            SELECT ti.inquiry_id,
                   ti.name,
                   ti.testname,
                   ti.reffered_by,
                   ti.mobile_number,
                   ti.expected_visit_date,
                   t.test_id,
                   t.created_at AS test_created_at
            FROM test_inquiry ti
            LEFT JOIN tests t
                   ON ti.inquiry_id = t.inquiry_id
                  AND t.branch_id = :test_branch_id
            WHERE ti.inquiry_id = :inquiry_id
              AND ti.branch_id = :inquiry_branch_id
            LIMIT 1
        ");
        $params = [
            ':inquiry_id'        => $id,
            ':inquiry_branch_id' => $branchId,
            ':test_branch_id'    => $branchId
        ];
    }

    $stmt->execute($params);
    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inquiry) {
        // Check if test exists (for test inquiry) or registered (for quick inquiry)
        $alreadyExists = ($type === 'quick')
            ? !empty($inquiry['registration_id'])
            : !empty($inquiry['test_id']);

        echo json_encode([
            'success'            => true,
            'data'               => $inquiry,
            'already_registered' => $alreadyExists,
            'message'            => $alreadyExists
                ? ($type === 'quick'
                    ? 'This person is already registered (Reg ID: ' . $inquiry['registration_id'] . ')'
                    : 'Test already exists (Test ID: ' . $inquiry['test_id'] . ')')
                : 'Not yet registered'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
