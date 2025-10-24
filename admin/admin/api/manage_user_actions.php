<?php

declare(strict_types=1);
session_start();

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../../common/db.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// --- Security & Authorization ---
if (!isset($_SESSION['uid']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    $response['message'] = 'You do not have permission to perform this action.';
    echo json_encode($response);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;

if (!$action) {
    http_response_code(400);
    $response['message'] = 'No action specified.';
    echo json_encode($response);
    exit();
}

try {
    switch ($action) {
        case 'create':

            $stmtUser = $pdo->prepare("INSERT INTO users (username, password, email, role, branch_id, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtUser->execute([
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['email'] ?: null,
                $data['role'],
                $data['branch_id'] ?: null,
                $data['is_active'] ?? 1
            ]);

            $response = ['success' => true, 'message' => 'User account created successfully.'];
            break;

        case 'create_employee':
            // --- Validation ---
            if (empty($data['first_name']) || empty($data['last_name']) || empty($data['date_of_joining'])) {
                throw new Exception('First name, last name, and joining date are required.');
            }

            // FIX: The live database has user_id as NOT NULL and UNIQUE.
            // We insert a unique negative placeholder to satisfy the constraints, then set it to NULL.
            // A better long-term fix is to `ALTER TABLE employees MODIFY user_id INT(11) NULL;`
            
            // Generate a unique negative number based on timestamp to avoid collisions.
            $placeholderUserId = -time();

            $stmtEmployee = $pdo->prepare(
                "INSERT INTO employees (user_id, first_name, last_name, phone_number, address, date_of_joining) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmtEmployee->execute([
                $placeholderUserId, // Use unique negative placeholder
                $data['last_name'],
                $data['first_name'], // Corrected order
                $data['phone_number'] ?: null,
                $data['address'] ?: null,
                $data['date_of_joining']
            ]);
            $employeeId = $pdo->lastInsertId();
            $stmtSetNull = $pdo->prepare("UPDATE employees SET user_id = NULL WHERE employee_id = ?");
            $stmtSetNull->execute([$employeeId]);

            $response = ['success' => true, 'message' => 'Employee profile created successfully.'];
            break;

        case 'update':
            // --- Validation ---
            $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$userId || empty($data['username']) || empty($data['role'])) {
                throw new Exception('Invalid data provided for update.');
            }

            $pdo->beginTransaction();

            // 1. Unlink any previous employee from this user
            $stmtUnlink = $pdo->prepare("UPDATE employees SET user_id = NULL WHERE user_id = ?");
            $stmtUnlink->execute([$userId]);

            // 2. Link the new employee (if one was selected)
            $employeeId = filter_var($data['employee_id'] ?? null, FILTER_VALIDATE_INT);
            if ($employeeId) {
                // Check if the employee is already linked to another user
                $stmtCheck = $pdo->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
                $stmtCheck->execute([$employeeId]);
                $existingLink = $stmtCheck->fetchColumn();
                if ($existingLink !== null && $existingLink !== $userId) {
                    throw new Exception('This employee is already linked to another user account.');
                }

                $stmtLink = $pdo->prepare("UPDATE employees SET user_id = ? WHERE employee_id = ?");
                $stmtLink->execute([$userId, $employeeId]);

                // --- NEW: Map role to job_title and update employee ---
                $role = $data['role'];
                $jobTitle = null;
                switch ($role) {
                    case 'reception':
                        $jobTitle = 'Receptionist';
                        break;
                    case 'doctor':
                        $jobTitle = 'Doctor';
                        break;
                    case 'jrdoctor':
                        $jobTitle = 'Junior Doctor';
                        break;
                    case 'admin':
                        $jobTitle = 'Administrator';
                        break;
                }
                $stmtUpdateJob = $pdo->prepare("UPDATE employees SET job_title = ? WHERE employee_id = ?");
                $stmtUpdateJob->execute([$jobTitle, $employeeId]);
            }

            // 3. Update User details
            $stmtUser = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, branch_id = ?, is_active = ? WHERE id = ?");
            $stmtUser->execute([
                $data['username'],
                $data['email'] ?: null,
                $data['role'],
                $data['branch_id'] ?: null,
                (int)$data['is_active'],
                $userId
            ]);

            $pdo->commit();
            $response = ['success' => true, 'message' => 'User details updated successfully.'];
            break;

        case 'update_employee':
            // --- Validation ---
            $employeeId = filter_var($data['employee_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$employeeId || empty($data['first_name']) || empty($data['last_name']) || empty($data['date_of_joining'])) {
                throw new Exception('Employee ID, name, and joining date are required.');
            }

            $stmt = $pdo->prepare(
                "UPDATE employees SET 
                    first_name = :first_name, 
                    last_name = :last_name, 
                    job_title = :job_title, 
                    phone_number = :phone_number, 
                    address = :address, 
                    date_of_birth = :date_of_birth, 
                    date_of_joining = :date_of_joining, 
                    is_active = :is_active
                WHERE employee_id = :employee_id"
            );

            $data['date_of_birth'] = empty($data['date_of_birth']) ? null : $data['date_of_birth'];
            
            // Remove the 'action' key as it's not part of the SQL statement's parameters
            unset($data['action']);

            $stmt->execute($data);

            $response = ['success' => true, 'message' => 'Employee details updated successfully.'];
            break;

        case 'change_password':
            // --- Validation ---
            $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$userId || empty($data['new_password'])) {
                throw new Exception('User ID and new password are required.');
            }
            if (strlen($data['new_password']) < 8) {
                throw new Exception('Password must be at least 8 characters long.');
            }

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([
                password_hash($data['new_password'], PASSWORD_DEFAULT),
                $userId
            ]);

            if ($stmt->rowCount()) {
                $response = ['success' => true, 'message' => 'Password changed successfully.'];
            } else {
                throw new Exception('Could not change password. User not found.');
            }
            break;

        default:
            http_response_code(400);
            $response['message'] = 'Invalid action specified.';
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    // Check for duplicate entry error
    if ($e->getCode() == 23000) {
        if (str_contains($e->getMessage(), 'username')) {
            $response['message'] = 'This username is already taken. Please choose another.';
        } elseif (str_contains($e->getMessage(), 'email')) {
            $response['message'] = 'This email is already registered. Please use another.';
        } elseif (str_contains($e->getMessage(), 'user_id_unique')) {
            $response['message'] = 'This employee is already linked to another user.';
        } else {
            $response['message'] = 'A database constraint failed. This can be due to a duplicate entry or a missing required field (like user_id).';
        }
    } else {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
    error_log("User management error: " . $e->getMessage());
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    $response['message'] = $e->getMessage();
    error_log("User management error: " . $e->getMessage());
}

echo json_encode($response);
