<?php
require_once '../../common/db.php';

header('Content-Type: application/json');

$registrationId = isset($_GET['registrationId']) ? (int)$_GET['registrationId'] : 0;

if (!$registrationId) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE registration_id = ? LIMIT 1");
$stmt->execute([$registrationId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if ($patient) {
    echo json_encode(['exists' => true, 'patientId' => $patient['patient_id']]);
} else {
    echo json_encode(['exists' => false]);
}
