<?php
require_once '../../common/db.php';
require_once '../../common/logger.php';
session_start();
header('Content-Type: application/json');

// Get the JSON input from the front-end
$data = json_decode(file_get_contents("php://input"), true);

// Start a transaction. This is CRITICAL. If any step fails, the whole operation is cancelled.
$pdo->beginTransaction();

try {
    // --- Step 1: Collect and Sanitize Inputs ---
    $patient_name      = trim($data['patient_name'] ?? '');
    $phone             = trim($data['phone'] ?? '');
    $email             = trim($data['email'] ?? '');
    $gender            = $data['gender'] ?? '';
    $age               = (int)($data['age'] ?? 0);
    $dob               = $data['dob'] ?? null; // Added DOB for the master record
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

    // --- Step 2: Session and Validation ---
    if (!isset($_SESSION['branch_id'], $_SESSION['uid'], $_SESSION['username'])) {
        throw new Exception("User session details are incomplete. Please log in again.");
    }
    $branch_id = $_SESSION['branch_id'];
    $user_id   = $_SESSION['uid'];
    $username  = $_SESSION['username'];

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
    // If the patient already existed, we'll just use their existing masterPatientId.

    // --- Step 5: Create the Branch-Specific Registration Record ---
    // This links the registration event to the central patient profile.
    $stmtReg = $pdo->prepare("
        INSERT INTO registration 
        (master_patient_id, branch_id, patient_name, phone_number, email, gender, age, chief_complain, referralSource, reffered_by, occupation, address, consultation_type, appointment_date, appointment_time, consultation_amount, payment_method, remarks, status)
        VALUES
        (:master_patient_id, :branch_id, :patient_name, :phone, :email, :gender, :age, :chief_complain, :referralSource, :referred_by, :occupation, :address, :consultation_type, :appointment_date, :appointment_time, :consultation_amount, :payment_method, :remarks, 'Pending')
    ");
    $stmtReg->execute([
        ':master_patient_id'   => $masterPatientId,
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
    $newRegistrationId = $pdo->lastInsertId();

    // --- Step 6: Log the Activity ---
    $logDetailsAfter = [
        'patient_name' => $patient_name,
        'phone_number' => $phone,
        'new_patient_uid' => $patientUID,
        'master_patient_id' => $masterPatientId,
        'consultation_amount' => $consultation_amt
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
    error_log("Registration API Error: " . $e->getMessage()); // Log the detailed error
    echo json_encode(["success" => false, "message" => "An error occurred during registration. Please check the logs."]);
}
