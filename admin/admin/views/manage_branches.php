<?php

declare(strict_types=1);
session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);

// --- Security & Setup ---
if (!isset($_SESSION['uid'])) { // Add a role check later, e.g., if ($_SESSION['role'] !== 'admin')
    header('Location: ../../login.php');
    exit();
}
require_once '../../common/db.php';
require_once '../../common/logger.php';

$errors = [];
$successMessage = '';

// --- FORM SUBMISSION LOGIC (CREATE/UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_branch') {
    $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;

    // --- Sanitize Text Inputs ---
    $branch_name = trim($_POST['branch_name'] ?? '');
    $clinic_name = trim($_POST['clinic_name'] ?? '');
    $address_line_1 = trim($_POST['address_line_1'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $phone_primary = trim($_POST['phone_primary'] ?? '');
    $phone_secondary = trim($_POST['phone_secondary'] ?? '');

    // --- Validation ---
    if (empty($branch_name) || empty($clinic_name) || empty($phone_primary)) {
        $errors[] = "Branch Name, Clinic Name, and Primary Phone are required.";
    }

    // --- File Upload Handling ---
    $uploadDir = '../../uploads/logos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $logoPrimaryPath = $_POST['existing_logo_primary'] ?? null;
    $logoSecondaryPath = $_POST['existing_logo_secondary'] ?? null;

    // Helper function for handling file uploads
    function handleLogoUpload($fileKey, $branchId, $type, $uploadDir)
    {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES[$fileKey];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                return ['error' => "Invalid file type for {$type} logo. Only JPG, PNG, GIF are allowed."];
            }
            if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
                return ['error' => "{$type} logo file is too large. Max size is 2MB."];
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = "branch_" . ($branchId ?? 'new') . "_{$type}_" . time() . "." . $extension;
            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return ['path' => 'uploads/logos/' . $fileName]; // Return relative path
            } else {
                return ['error' => "Failed to move {$type} logo file."];
            }
        }
        return ['path' => null]; // No file uploaded
    }

    if (empty($errors)) {
        $primaryResult = handleLogoUpload('logo_primary', $branchId, 'primary', $uploadDir);
        if (isset($primaryResult['error'])) $errors[] = $primaryResult['error'];
        if (isset($primaryResult['path'])) $logoPrimaryPath = $primaryResult['path'];

        $secondaryResult = handleLogoUpload('logo_secondary', $branchId, 'secondary', $uploadDir);
        if (isset($secondaryResult['error'])) $errors[] = $secondaryResult['error'];
        if (isset($secondaryResult['path'])) $logoSecondaryPath = $secondaryResult['path'];
    }

    // --- Database Operation ---
    if (empty($errors)) {
        try {
            if ($branchId) { // UPDATE existing branch
                $stmt = $pdo->prepare(
                    "UPDATE branches SET branch_name=?, clinic_name=?, address_line_1=?, city=?, phone_primary=?, phone_secondary=?, logo_primary_path=?, logo_secondary_path=? WHERE branch_id=?"
                );
                $stmt->execute([$branch_name, $clinic_name, $address_line_1, $city, $phone_primary, $phone_secondary, $logoPrimaryPath, $logoSecondaryPath, $branchId]);
                $successMessage = "Branch details updated successfully!";
            } else { // INSERT new branch
                $stmt = $pdo->prepare(
                    "INSERT INTO branches (branch_name, clinic_name, address_line_1, city, phone_primary, phone_secondary, logo_primary_path, logo_secondary_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$branch_name, $clinic_name, $address_line_1, $city, $phone_primary, $phone_secondary, $logoPrimaryPath, $logoSecondaryPath]);
                $successMessage = "New branch added successfully!";
            }
            $_SESSION['success'] = $successMessage;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $_SESSION['errors'] = ["Database error: " . $e->getMessage()];
        }
        header("Location: manage_branches.php"); // Redirect to prevent resubmission
        exit();
    } else {
        $_SESSION['errors'] = $errors;
    }
}


// --- DATA FETCHING FOR DISPLAY ---
$branches = [];
$branchToEdit = null;
try {
    // Fetch all branches for the list
    $branches = $pdo->query("SELECT * FROM branches ORDER BY branch_name")->fetchAll(PDO::FETCH_ASSOC);

    // If an 'edit' ID is in the URL, fetch that specific branch to pre-populate the form
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $editId = (int)$_GET['edit'];
        $stmt = $pdo->prepare("SELECT * FROM branches WHERE branch_id = ?");
        $stmt->execute([$editId]);
        $branchToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error fetching branch data: " . $e->getMessage());
}

// Retrieve and clear session messages
$sessionErrors = $_SESSION['errors'] ?? [];
$sessionSuccess = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Branches</title>
    <link rel="stylesheet" href="../../reception/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="icon" href="../../assets/images/favicon.png" type="image/x-icon" />
    <style>
        .main-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            padding: 20px;
        }

        .form-container,
        .list-container {
            background-color: var(--bg-primary);
            padding: 25px;
            border-radius: var(--border-radius-card);
            box-shadow: var(--shadow-md);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-secondary);
        }

        .form-group input[type="text"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color-primary);
            border-radius: var(--border-radius-btn);
            box-sizing: border-box;
        }

        .logo-preview {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }

        .logo-preview img {
            max-height: 60px;
            max-width: 150px;
            border: 1px solid var(--border-color-primary);
            padding: 5px;
            border-radius: 5px;
        }

        .logo-preview span {
            color: var(--text-tertiary);
            font-style: italic;
        }

        .action-btn {
            width: 100%;
            padding: 12px;
            font-size: 1rem;
        }

        .list-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .list-container th,
        .list-container td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color-primary);
            text-align: left;
        }

        .list-container th {
            font-weight: 600;
        }
    </style>
</head>

<body>
    <header>
        <!-- Your standard header here -->
    </header>
    <main class="main">
        <div class="top-bar">
            <h2>Manage Clinic Branches</h2>
        </div>

        <!-- Display Session Messages -->
        <?php if ($sessionSuccess): ?><div class="message success" style="margin: 0 20px 20px;"><?= htmlspecialchars($sessionSuccess) ?></div><?php endif; ?>
        <?php if (!empty($sessionErrors)): ?>
            <div class="message error" style="margin: 0 20px 20px;">
                <ul><?php foreach ($sessionErrors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="main-container">
            <div class="form-container">
                <h3><?= $branchToEdit ? 'Edit Branch Details' : 'Add New Branch' ?></h3>
                <form action="manage_branches.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_branch">
                    <?php if ($branchToEdit): ?>
                        <input type="hidden" name="branch_id" value="<?= $branchToEdit['branch_id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="branch_name">Branch Name (e.g., Siliguri)</label>
                        <input type="text" id="branch_name" name="branch_name" value="<?= htmlspecialchars($branchToEdit['branch_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="clinic_name">Full Clinic Name</label>
                        <input type="text" id="clinic_name" name="clinic_name" value="<?= htmlspecialchars($branchToEdit['clinic_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="address_line_1">Address</label>
                        <input type="text" id="address_line_1" name="address_line_1" value="<?= htmlspecialchars($branchToEdit['address_line_1'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?= htmlspecialchars($branchToEdit['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone_primary">Primary Phone</label>
                        <input type="text" id="phone_primary" name="phone_primary" value="<?= htmlspecialchars($branchToEdit['phone_primary'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_secondary">Other Phone Numbers (comma-separated)</label>
                        <input type="text" id="phone_secondary" name="phone_secondary" value="<?= htmlspecialchars($branchToEdit['phone_secondary'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="logo_primary">Primary Logo (e.g., ProSpine)</label>
                        <input type="file" id="logo_primary" name="logo_primary" accept="image/*">
                        <?php if (!empty($branchToEdit['logo_primary_path'])): ?>
                            <div class="logo-preview">
                                <img src="../../<?= htmlspecialchars($branchToEdit['logo_primary_path']) ?>" alt="Primary Logo Preview">
                                <span>Current Logo</span>
                            </div>
                            <input type="hidden" name="existing_logo_primary" value="<?= htmlspecialchars($branchToEdit['logo_primary_path']) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="logo_secondary">Secondary Logo (e.g., Manipal)</label>
                        <input type="file" id="logo_secondary" name="logo_secondary" accept="image/*">
                        <?php if (!empty($branchToEdit['logo_secondary_path'])): ?>
                            <div class="logo-preview">
                                <img src="../../<?= htmlspecialchars($branchToEdit['logo_secondary_path']) ?>" alt="Secondary Logo Preview">
                                <span>Current Logo</span>
                            </div>
                            <input type="hidden" name="existing_logo_secondary" value="<?= htmlspecialchars($branchToEdit['logo_secondary_path']) ?>">
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="action-btn"><?= $branchToEdit ? 'Save Changes' : 'Add New Branch' ?></button>
                    <?php if ($branchToEdit): ?>
                        <a href="manage_branches.php" style="display: block; text-align: center; margin-top: 15px;">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="list-container">
                <h3>Existing Branches</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Branch Name</th>
                            <th>Clinic Name</th>
                            <th>City</th>
                            <th>Primary Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($branches)): ?>
                            <tr>
                                <td colspan="5">No branches found. Add one using the form.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($branches as $branch): ?>
                                <tr>
                                    <td><?= htmlspecialchars($branch['branch_name']) ?></td>
                                    <td><?= htmlspecialchars($branch['clinic_name']) ?></td>
                                    <td><?= htmlspecialchars($branch['city']) ?></td>
                                    <td><?= htmlspecialchars($branch['phone_primary']) ?></td>
                                    <td><a href="?edit=<?= $branch['branch_id'] ?>" class="action-btn" style="width: auto; padding: 6px 12px; font-size: 0.9rem;">Edit</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <script src="../js/theme.js"></script>
</body>

</html>