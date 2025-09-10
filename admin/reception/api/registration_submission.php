<?php
require_once '../../common/db.php';
session_start();
header('Content-Type: application/json');

// --- CSRF token check ---
if (empty($_SESSION['csrf'])) {
    echo json_encode(["success" => false, "message" => "Invalid session. Refresh and try again."]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

try {
    // Collect inputs
    $patient_name      = trim($data['patient_name'] ?? '');
    $phone             = trim($data['phone'] ?? '');
    $email             = trim($data['email'] ?? '');
    $gender            = $data['gender'] ?? '';
    $age               = (int)($data['age'] ?? 0);
    $chief_complain    = $data['conditionType'] ?? 'other';
    $referralSource    = $data['referralSource'] ?? 'self';
    $referred_by       = trim($data['referred_by'] ?? '');
    $occupation        = trim($data['occupation'] ?? '');
    $address           = trim($data['address'] ?? '');
    $consultation_type = $data['inquiry_type'] ?? 'in-clinic';
    $appointment_date  = $data['appointment_date'] ?? null;
    $appointment_time  = $data['time'] ?? null;
    $consultation_amt  = (float)($data['amount'] ?? 0);
    $payment_method    = $data['payment_method'] ?? 'cash';
    $remarks           = trim($data['remarks'] ?? '');

    $branch_id = $_SESSION['branch_id'] ?? null;

    // Validation
    if ($patient_name === '' || $phone === '' || $gender === '' || $age <= 0 || $consultation_amt <= 0) {
        throw new Exception("Please fill in all required fields.");
    }
    if (!$branch_id) {
        throw new Exception("Branch not assigned to session. Please log in again.");
    }

    // Insert into DB
    $stmt = $pdo->prepare("
        INSERT INTO registration 
        (branch_id, patient_name, phone_number, email, gender, age, chief_complain, referralSource, reffered_by, occupation, address, consultation_type, appointment_date, appointment_time, consultation_amount, payment_method, remarks, status, created_at, updated_at)
        VALUES
        (:branch_id, :patient_name, :phone, :email, :gender, :age, :chief_complain, :referralSource, :referred_by, :occupation, :address, :consultation_type, :appointment_date, :appointment_time, :consultation_amount, :payment_method, :remarks, 'Pending', NOW(), NOW())
    ");
    $stmt->execute([
        ':branch_id'           => $branch_id,
        ':patient_name'        => $patient_name,
        ':phone'               => $phone,
        ':email'               => $email,
        ':gender'              => $gender,
        ':age'                 => $age,
        ':chief_complain'      => $chief_complain,
        ':referralSource'      => $referralSource,
        ':referred_by'         => $referred_by,
        ':occupation'          => $occupation,
        ':address'             => $address,
        ':consultation_type'   => $consultation_type,
        ':appointment_date'    => $appointment_date ?: null,
        ':appointment_time'    => $appointment_time ?: null,
        ':consultation_amount' => $consultation_amt,
        ':payment_method'      => $payment_method,
        ':remarks'             => $remarks
    ]);

    echo json_encode(["success" => true, "message" => "Registration record submitted successfully!"]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
