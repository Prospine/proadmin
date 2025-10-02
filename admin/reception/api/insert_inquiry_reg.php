<?php

declare(strict_types=1);
require_once '../../common/db.php';
require_once '../../common/logger.php'; // 1. Added the logger
session_start();
header('Content-Type: application/json');

// --- CSRF token check --- 
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Invalid CSRF token. Please refresh and try again."]);
    exit;
}

// Start a transaction. If any step fails, the whole operation is cancelled.
$pdo->beginTransaction();

try {
    // --- Step 1: Session and Validation ---
    if (!isset($_SESSION['branch_id']) || !isset($_SESSION['uid']) || !isset($_SESSION['username'])) {
        throw new Exception("User session details are incomplete. Please log in again.");
    }
    $branch_id = $_SESSION['branch_id'];
    $user_id = $_SESSION['uid'];
    $username = $_SESSION['username'];

    // --- Step 2: Collect and Sanitize Inputs ---
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

    if (empty($patient_name) || empty($phone) || empty($gender) || $age <= 0) {
        throw new Exception("Please fill in all required fields: Name, Phone, Gender, and Age.");
    }

    // --- Step 3: Generate the Unique Patient UID ---
    $today = date('Y-m-d');
    // Atomic "increment-or-create" query. This is safe from race conditions.
    $pdo->exec("
        INSERT INTO daily_patient_counter (entry_date, counter) VALUES ('$today', 1)
        ON DUPLICATE KEY UPDATE counter = counter + 1
    ");
    // Fetch the new serial number for today
    $stmtCounter = $pdo->prepare("SELECT counter FROM daily_patient_counter WHERE entry_date = ?");
    $stmtCounter->execute([$today]);
    $serialNumber = $stmtCounter->fetchColumn();
    // Construct the final UID (e.g., 2510011)
    $patientUID = date('ymd') . $serialNumber;

    // --- Step 4: Check for Existing Patient and Create Master Record ---
    $stmtCheck = $pdo->prepare("SELECT master_patient_id FROM patient_master WHERE phone_number = ? LIMIT 1");
    $stmtCheck->execute([$phone]);
    $masterPatientId = $stmtCheck->fetchColumn();

    if (!$masterPatientId) {
        // This person is new to the entire clinic network. Create a master record.
        $stmtMaster = $pdo->prepare(
            "INSERT INTO patient_master (patient_uid, full_name, phone_number, gender, age, first_registered_branch_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmtMaster->execute([
            $patientUID,
            $patient_name,
            $phone,
            $gender,
            $age,
            $branch_id
        ]);
        // Get the ID of the new master record we just created.
        $masterPatientId = $pdo->lastInsertId();
    }

    // --- Step 5: Create the Branch-Specific Registration Record ---
    $stmtReg = $pdo->prepare("
        INSERT INTO registration 
        (master_patient_id, branch_id, inquiry_id, patient_name, phone_number, email, gender, age, chief_complain, referralSource, reffered_by, occupation, address, consultation_type, appointment_date, appointment_time, consultation_amount, payment_method, remarks, status)
        VALUES
        (:master_patient_id, :branch_id, :inquiry_id, :patient_name, :phone, :email, :gender, :age, :chief_complain, :referralSource, :referred_by, :occupation, :address, :consultation_type, :appointment_date, :appointment_time, :consultation_amount, :payment_method, :remarks, :status)
    ");
    $stmtReg->execute([
        ':master_patient_id'   => $masterPatientId,
        ':branch_id'           => $branch_id,
        ':inquiry_id'          => $inquiry_id, // Keep linking to the original inquiry
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
    $newRegistrationId = $pdo->lastInsertId();

    // --- Step 6: Log the Activity ---
    $logDetailsAfter = [
        'patient_name' => $patient_name,
        'phone_number' => $phone,
        'new_patient_uid' => $patientUID,
        'master_patient_id' => $masterPatientId,
        'consultation_amount' => $consultation_amt,
        'converted_from_inquiry_id' => $inquiry_id
    ];
    log_activity($pdo, $user_id, $username, $branch_id, 'CREATE', 'registration', (int)$newRegistrationId, null, $logDetailsAfter);

    // --- Step 7: Finalize and Respond ---
    // If we've made it this far, everything is successful. Commit the transaction.
    $pdo->commit();
    echo json_encode([
        "success" => true,
        "message" => "Patient registered successfully!",
        "patient_uid" => $patientUID, // Send the new ID back to the front-end!
        "registration_id" => $newRegistrationId
    ]);
} catch (Throwable $e) {
    // Something went wrong. Roll back all database changes from this attempt.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Registration API Error (from Inquiry): " . $e->getMessage()); // Log the detailed error
    echo json_encode(["success" => false, "message" => "An error occurred during registration. Please check the logs."]);
}
