<?php
require 'admin/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consultationType = $_POST['consultationType'] ?? '';
    $fullName         = trim($_POST['fullName'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $gender           = $_POST['gender'] ?? '';
    $dob              = $_POST['dob'] ?? null;
    $age              = $_POST['age'] ?? null;
    $occupation       = trim($_POST['occupation'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $conditionType    = $_POST['conditionType'] ?? 'other';
    $condition        = trim($_POST['condition'] ?? '');
    $referralSource   = $_POST['referralSource'] ?? 'self';
    $contactMethod    = $_POST['contactMethod'] ?? 'Phone';
    $branch           = $_POST['branch'] ?? '';

    // basic validations
    $validConsultations = ['virtual', 'clinic', 'home'];

    // Branch mapping
    $branchMap = [
        'bhagalpur_branch' => 1,
        'siliguri_branch'  => 2,
        // Add more branches here later...
    ];

    if (!in_array($consultationType, $validConsultations)) {
        exit("❌ Invalid consultation type.");
    }
    if (!isset($branchMap[$branch])) {
        exit("❌ Invalid branch selected.");
    }

    $branchId = $branchMap[$branch];

    if (!$fullName || !$phone || !$age || !$occupation || !$condition) {
        exit("⚠️ Please fill all required fields.");
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO appointments 
            (consultationType, fullName, email, phone, gender, dob, age, occupation, address, medical_condition, conditionType, referralSource, contactMethod, location, branch_id, created_at, status) 
            VALUES 
            (:consultationType, :fullName, :email, :phone, :gender, :dob, :age, :occupation, :address, :medical_condition, :conditionType, :referralSource, :contactMethod, :location, :branch_id, NOW(), 'pending')
        ");

        $stmt->execute([
            ':consultationType'  => $consultationType,
            ':fullName'          => $fullName,
            ':email'             => $email,
            ':phone'             => $phone,
            ':gender'            => $gender,
            ':dob'               => $dob ?: null,
            ':age'               => $age,
            ':occupation'        => $occupation,
            ':address'           => $address,
            ':medical_condition' => $condition,
            ':conditionType'     => $conditionType,
            ':referralSource'    => $referralSource,
            ':contactMethod'     => $contactMethod,
            ':location'          => $branch,
            ':branch_id'         => $branchId
        ]);

        exit("✅ Appointment submitted successfully!");
    } catch (Throwable $e) {
        http_response_code(500);
        exit("❌ Server error. Please try again later.");
    }
}
