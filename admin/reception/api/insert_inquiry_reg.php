<?php

declare(strict_types=1);
require_once '../../common/db.php';
require_once '../../common/logger.php'; // 1. Added the logger
session_start();
header('Content-Type: application/json');

// --- CSRF token check ---
// Ensure this matches the session key and the hidden input name in your form
if (empty($_SESSION['csrf_token']) || ($_POST['csrf'] ?? '') !== $_SESSION['csrf_token']) {
    echo json_encode(["success" => false, "message" => "Invalid CSRF token. Please refresh the page and try again."]);
    exit;
}

try {
    // Get session variables for logging
    if (!isset($_SESSION['branch_id']) || !isset($_SESSION['uid']) || !isset($_SESSION['username'])) {
        throw new Exception("User session details are incomplete. Please log in again.");
    }
    $branch_id = $_SESSION['branch_id'];
    $user_id = $_SESSION['uid'];
    $username = $_SESSION['username'];

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
    $appointment_time  = $_POST['appointment_time'] ?? null; // Corrected from 'time'
    $consultation_amt  = (float)($_POST['amount'] ?? 0);
    $payment_method    = $_POST['payment_method'] ?? 'cash';
    $remarks           = trim($_POST['review'] ?? '');
    $status            = 'Pending';
    $inquiry_id        = !empty($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : null;

    // Validation
    if ($patient_name === '' || $phone === '' || $gender === '' || $age <= 0) {
        throw new Exception("Please fill in all required fields.");
    }

    // Insert into DB
    // (Your INSERT statement is good, no changes needed there)
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

    // 2. Logging the successful creation
    $newRegistrationId = $pdo->lastInsertId();
    $logDetailsAfter = [
        'patient_name' => $patient_name,
        'phone_number' => $phone,
        'age' => $age,
        'chief_complain' => $chief_complain,
        'consultation_amount' => $consultation_amt,
        'converted_from_inquiry_id' => $inquiry_id
    ];

    log_activity(
        $pdo,
        $user_id,
        $username,
        $branch_id,
        'CREATE',
        'registration',
        (int)$newRegistrationId,
        null,
        $logDetailsAfter
    );

    // If an inquiry was converted, we should also log the status update
    if ($inquiry_id) {
        log_activity(
            $pdo,
            $user_id,
            $username,
            $branch_id,
            'UPDATE',
            'quick_inquiry',
            (int)$inquiry_id,
            ['status' => 'pending'], // Assuming it was pending
            ['status' => 'visited'] // Or 'converted' if you add that status
        );
    }

    echo json_encode(["success" => true, "message" => "Registration saved successfully!"]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
}
