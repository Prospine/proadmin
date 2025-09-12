<?php
header('Content-Type: application/json');

// Ensure this script is only accessed via POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get the raw JSON data from the request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate incoming data
if (!isset($data['registration_id']) || !isset($data['remarks'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing Registration ID or remarks.']);
    exit();
}

$registration_id = $data['registration_id'];
$remarks = trim($data['remarks']);

// Check if remarks is not empty after trimming whitespace
if (empty($remarks)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Remarks cannot be empty.']);
    exit();
}

// Include your database connection file
try {
    if (!file_exists('../../common/db.php')) {
        throw new Exception("The 'db.php' file was not found. Please check the file path.");
    }
    require '../../common/db.php'; // Adjust the path to your db.php file

    // Check if the PDO object was successfully created in db.php
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("Database connection failed. Please check your credentials in 'db.php'.");
    }

    // Prepare a statement to prevent SQL injection and update the correct column
    $stmt = $pdo->prepare("UPDATE registration SET remarks = ? WHERE registration_id = ?");
    $stmt->execute([$remarks, $registration_id]);

    // Check if a row was affected
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Remarks updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No rows updated. The registration may not exist.']);
    }
} catch (PDOException $e) {
    // Catch specific PDO database errors
    error_log("Database error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Catch other general errors (like file not found)
    error_log("General error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
