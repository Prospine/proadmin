<?php

declare(strict_types=1);
require_once '../../common/db.php';
session_start();
header('Content-Type: application/json');

// --- CSRF token check ---
if (empty($_SESSION['csrf_token']) || ($_POST['csrf'] ?? '') !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "message" => "Invalid CSRF token. Refresh and try again."]);
    exit;
}

try {
    // Collect inputs from form
    $patient_name      = trim($_POST['name'] ?? '');
    $phone             = trim($_POST['phone_number'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $gender            = $_POST['gender'] ?? '';
    $age               = (int)($_POST['age'] ?? 0);
    $chief_complain    = $_POST['chief_complain'] ?? 'other';
    $referralSource    = $_POST['referralSource'] ?? 'self';
    $referred_by       = trim($_POST['referred_by'] ?? '');
    $occupation        = trim($_POST['occupation'] ?? '');
    $address           = trim($_POST['address'] ?? '');
    $consultation_type = $_POST['inquiry_type'] ?? 'In-Clinic';
    $appointment_date  = $_POST['appointment_date'] ?? null;
    $appointment_time  = $_POST['time'] ?? null;
    $consultation_amt  = (float)($_POST['amount'] ?? 0);
    $payment_method    = $_POST['payment_method'] ?? 'cash';
    $remarks           = trim($_POST['review'] ?? '');
    $status            = 'pending'; // always pending for new registration
    $inquiry_id        = !empty($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : null;

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
        (branch_id, inquiry_id, patient_name, phone_number, email, gender, age, chief_complain, referralSource, reffered_by, occupation, address, consultation_type, appointment_date, appointment_time, consultation_amount, payment_method, remarks, status, created_at, updated_at)
        VALUES
        (:branch_id, :inquiry_id, :patient_name, :phone, :email, :gender, :age, :chief_complain, :referralSource, :referred_by, :occupation, :address, :consultation_type, :appointment_date, :appointment_time, :consultation_amount, :payment_method, :remarks, :status, NOW(), NOW())
    ");
    $stmt->execute([
        ':branch_id'           => $branch_id,
        ':inquiry_id'          => $inquiry_id,
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
        ':remarks'             => $remarks,
        ':status'              => $status
    ]);

    echo json_encode(["success" => true, "message" => "Registration saved successfully!"]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
