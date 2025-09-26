<?php
// get_patient_summary.php
header('Content-Type: application/json');
require_once '../../common/db.php';

try {
    if (!isset($_GET['patient_id'])) {
        echo json_encode(['error' => 'patient_id is required']);
        exit;
    }

    $patient_id = intval($_GET['patient_id']);
    $today = date('Y-m-d');

    // 1. Fetch patient name
    $stmt = $pdo->prepare("
        SELECT r.patient_name
        FROM patients p
        INNER JOIN registration r ON p.registration_id = r.registration_id
        WHERE p.patient_id = :patient_id
        LIMIT 1
    ");
    $stmt->execute([':patient_id' => $patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        echo json_encode(['error' => 'Patient not found']);
        exit;
    }

    // 2. Fetch TOTAL paid (all time)
    $stmtTotal = $pdo->prepare("
        SELECT COALESCE(SUM(amount),0) AS total_paid
        FROM payments
        WHERE patient_id = :patient_id
    ");
    $stmtTotal->execute([':patient_id' => $patient_id]);
    $totalPaid = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total_paid'];

    // 3. Fetch TODAY's payment sum
    $stmt2 = $pdo->prepare("
        SELECT COALESCE(SUM(amount),0) as today_paid
        FROM payments
        WHERE patient_id = :patient_id AND payment_date = :today
    ");
    $stmt2->execute([
        ':patient_id' => $patient_id,
        ':today' => $today
    ]);
    $todayPaid = $stmt2->fetch(PDO::FETCH_ASSOC)['today_paid'];

    // 4. Fetch attendance count
    $stmt3 = $pdo->prepare("
        SELECT COUNT(*) as attendance_present
        FROM attendance
        WHERE patient_id = :patient_id
    ");
    $stmt3->execute([':patient_id' => $patient_id]);
    $attendance = $stmt3->fetch(PDO::FETCH_ASSOC)['attendance_present'];

    // Build response
    $response = [
        'name' => $patient['patient_name'],
        'total_paid' => $totalPaid,
        'today_paid' => $todayPaid,
        'attendance' => $attendance
    ];

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
