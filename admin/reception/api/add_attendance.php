<?php

declare(strict_types=1);
session_start();
require_once '../../common/db.php';

header('Content-Type: application/json');

try {
    // ----------------------
    // 1) Auth + basic input
    // ----------------------
    if (!isset($_SESSION['uid'])) {
        throw new Exception('Unauthorized');
    }

    $patientId     = (int)($_POST['patient_id'] ?? 0);
    $paymentAmount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : 0.0;
    $mode          = trim((string)($_POST['mode'] ?? ''));
    $remarks       = trim((string)($_POST['remarks'] ?? ''));

    if ($patientId <= 0) {
        throw new Exception('Invalid patient ID');
    }

    // ----------------------
    // 2) Begin Transaction
    // ----------------------
    $pdo->beginTransaction();

    // ----------------------
    // 3) Fetch & lock patient row (for safety)
    // ----------------------
    $pstmt = $pdo->prepare("
        SELECT patient_id, branch_id, treatment_type, treatment_cost_per_day, total_amount, due_amount, treatment_days, package_cost
        FROM patients
        WHERE patient_id = ?
        FOR UPDATE
    ");
    $pstmt->execute([$patientId]);
    $patient = $pstmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception('Patient not found');
    }

    // Optional branch safety: ensure patient belongs to user's branch
    if (isset($_SESSION['branch_id'])) {
        $sessionBranch = (int)$_SESSION['branch_id'];
        $patientBranch = (int)($patient['branch_id'] ?? 0);
        if ($patientBranch !== 0 && $sessionBranch !== $patientBranch) {
            throw new Exception('Permission denied (branch mismatch)');
        }
    }

    // ----------------------
    // 4) Duplicate attendance check (today)
    // ----------------------
    $dupStmt = $pdo->prepare("
        SELECT COUNT(*) FROM attendance
        WHERE patient_id = ? AND attendance_date = CURDATE()
    ");
    $dupStmt->execute([$patientId]);
    if ((int)$dupStmt->fetchColumn() > 0) {
        throw new Exception('Attendance already marked for today');
    }

    // ----------------------
    // 5) Determine per-day cost
    // ----------------------
    $treatmentType = strtolower((string)$patient['treatment_type']);
    $costPerDay = 0.0;

    if ($treatmentType === 'package') {
        if ((int)($patient['treatment_days'] ?? 0) > 0) {
            $costPerDay = (float)($patient['package_cost'] ?? 0) / (int)($patient['treatment_days']);
        }
    } elseif ($treatmentType === 'daily' || $treatmentType === 'advance') {
        $costPerDay = (float)($patient['treatment_cost_per_day'] ?? 0);
    }

    if ($costPerDay <= 0) {
        throw new Exception('Invalid or missing per-day cost for this patient.');
    }

    // ----------------------
    // 6) Get the current authoritative balance from payments & attendance
    // This is the SINGLE SOURCE OF TRUTH for all patients.
    // ----------------------
    $paidStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS paid FROM payments WHERE patient_id = ?");
    $paidStmt->execute([$patientId]);
    $paidSum = (float)$paidStmt->fetchColumn();

    $consumedStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE patient_id = ? AND attendance_date < CURDATE()");
    $consumedStmt->execute([$patientId]);
    $attendedTotal = (int)$consumedStmt->fetchColumn();

    $effectiveBalance = $paidSum - ($attendedTotal * $costPerDay);
    $needed = max(0.0, $costPerDay - $effectiveBalance);

    // ----------------------
    // 7) Payment & business logic
    // ----------------------
    $paymentId = null;

    if ($paymentAmount > 0.0) {
        if ($mode === '') {
            throw new Exception('Payment mode is required when payment amount is provided.');
        }

        // CORRECTED: changed `created_by` to `created_at` with NOW()
        $insPay = $pdo->prepare("
            INSERT INTO payments (patient_id, payment_date, amount, mode, remarks, created_at)
            VALUES (?, CURDATE(), ?, ?, ?, NOW())
        ");
        $insPay->execute([$patientId, $paymentAmount, $mode, $remarks]);
        $paymentId = (int)$pdo->lastInsertId();
    } else {
        if ($effectiveBalance < $costPerDay) {
            $neededFormatted = number_format($needed, 2, '.', '');
            throw new Exception("Insufficient balance. Please collect at least ₹{$neededFormatted} before marking attendance.");
        }
    }

    // ----------------------
    // 8) Insert attendance row (link to payment if one exists)
    // ----------------------
    if ($remarks === '') {
        $remarks = 'Auto: ' . ucfirst($treatmentType) . ' attendance';
    }

    $insAtt = $pdo->prepare("
        INSERT INTO attendance (patient_id, attendance_date, remarks, payment_id)
        VALUES (?, CURDATE(), ?, ?)
    ");
    $insAtt->execute([$patientId, $remarks, $paymentId]);
    $attendanceId = (int)$pdo->lastInsertId();

    // ----------------------
    // 9) Recalculate summary fields & update patient row
    // ----------------------
    // Now that payments and attendance are updated, re-calculate the final summary fields
    $recalcPaid = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE patient_id = ?");
    $recalcPaid->execute([$patientId]);
    $authoritativePaid = (float)$recalcPaid->fetchColumn();

    $recalcDue = (float)$patient['total_amount'] - $authoritativePaid;

    $updatePatient = $pdo->prepare("
        UPDATE patients
        SET advance_payment = ?, due_amount = ?
        WHERE patient_id = ?
    ");
    $updatePatient->execute([$authoritativePaid, $recalcDue, $patientId]);

    $pdo->commit();

    // fetch updated patient values to return
    $resultPatient = $pdo->prepare("SELECT advance_payment, due_amount FROM patients WHERE patient_id = ?");
    $resultPatient->execute([$patientId]);
    $upd = $resultPatient->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'         => 'success',
        'message'        => 'Attendance recorded successfully',
        'attendance_id'  => $attendanceId,
        'payment_id'     => $paymentId,
        'advance_payment' => isset($upd['advance_payment']) ? (float)$upd['advance_payment'] : $authoritativePaid,
        'due_amount'     => isset($upd['due_amount']) ? (float)$upd['due_amount'] : $recalcDue
    ]);
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('add_attendance.php error: ' . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}
