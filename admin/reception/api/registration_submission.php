<?php
require_once '../../common/db.php';
require_once '../../common/logger.php'; // 1. Added the logger
session_start();
header('Content-Type: application/json');

// --- CSRF token check ---
// Note: A CSRF check here is good, but make sure your front-end sends the token
// if (empty($_SESSION['csrf'])) {
//     echo json_encode(["success" => false, "message" => "Invalid session. Refresh and try again."]);
//     exit;
// }

$data = json_decode(file_get_contents("php://input"), true);

try {
    // Collect and sanitize inputs
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
    $appointment_time  = $data['appointment_time'] ?? null;
    $consultation_amt  = (float)($data['amount'] ?? 0);
    $payment_method    = $data['payment_method'] ?? 'cash';
    $remarks           = trim($data['remarks'] ?? '');

    // Get session variables for logging
    if (!isset($_SESSION['branch_id']) || !isset($_SESSION['uid']) || !isset($_SESSION['username'])) {
        throw new Exception("User session details are incomplete. Please log in again.");
    }
    $branch_id = $_SESSION['branch_id'];
    $user_id = $_SESSION['uid'];
    $username = $_SESSION['username'];

    // Validation
    if ($patient_name === '' || $phone === '' || $gender === '' || $age <= 0 || $consultation_amt < 0) { // Allow 0 amount
        throw new Exception("Please fill in all required fields: Name, Phone, Gender, and Age.");
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

    // 2. Logging the successful creation
    $newRegistrationId = $pdo->lastInsertId();
    $logDetailsAfter = [
        'patient_name' => $patient_name,
        'phone_number' => $phone,
        'age' => $age,
        'chief_complain' => $chief_complain,
        'consultation_amount' => $consultation_amt
    ];

    log_activity(
        $pdo,
        $user_id,
        $username,
        $branch_id,
        'CREATE',
        'registration',
        (int)$newRegistrationId,
        null, // details_before is null for a new record
        $logDetailsAfter // details_after contains the new data
    );


    echo json_encode(["success" => true, "message" => "Registration record submitted successfully!"]);
} catch (Throwable $e) {
    error_log($e->getMessage()); // Good practice to log the actual error to the server's error log
    echo json_encode(["success" => false, "message" => "An error occurred: " . $e->getMessage()]);
}
