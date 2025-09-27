<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require 'admin/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $branch = $_POST['branch'] ?? '';

    // Map branch names to IDs
    $branchMap = [
        'bhagalpur_branch' => 1,
        'siliguri_branch'  => 2,
        // Add more branches here later...
    ];

    if (!isset($branchMap[$branch])) {
        exit('Invalid branch selected.');
    }

    $branchId = $branchMap[$branch];

    if ($name && $phone && $branch) {
        $stmt = $pdo->prepare("
            INSERT INTO appointment_requests (fullName, phone, location, branch_id, created_at, status) 
            VALUES (:fullName, :phone, :location, :branch_id, NOW(), 'new')
        ");
        $stmt->execute([
            ':fullName'  => $name,
            ':phone'     => $phone,
            ':location'  => $branch,
            ':branch_id' => $branchId
        ]);

        echo "Form submitted successfully!";
    } else {
        echo "Please fill all required fields.";
    }
}
