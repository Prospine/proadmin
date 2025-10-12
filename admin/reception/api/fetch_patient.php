<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once '../../common/db.php'; // PDO connection

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    // Main patient + registration info
    $stmt = $pdo->prepare("
        SELECT 
            p.patient_id,
            p.treatment_type,
            p.service_type,
            p.treatment_cost_per_day,
            p.package_cost,
            p.treatment_days,
            p.total_amount,
            p.advance_payment,
            p.discount_percentage,
            p.discount_approved_by,
            u_approver.username AS discount_approver_name,
            p.due_amount,
            p.payment_method AS treatment_payment_method,
            p.assigned_doctor,
            p.start_date,
            p.end_date,
            p.status AS patient_status,
            
            r.registration_id,
            r.patient_name,
            r.phone_number,
            r.age,
            r.email,
            r.gender,
            r.chief_complain,
            r.referralSource,
            r.reffered_by,
            r.occupation,
            r.address,
            r.consultation_type,
            r.appointment_date,
            r.appointment_time,
            r.consultation_amount,
            r.payment_method,
            r.remarks,
            r.doctor_notes,
            r.prescription,
            r.follow_up_date,
            r.status AS registration_status,
            r.created_at,
            r.updated_at
        FROM patients p
        LEFT JOIN registration r ON p.registration_id = r.registration_id
        LEFT JOIN users u_approver ON p.discount_approved_by = u_approver.id
        WHERE p.patient_id = :id
    ");
    $stmt->execute(['id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Attendance summary
        $stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS attendance_completed,
        MAX(attendance_date) AS last_attendance_date
    FROM attendance
    WHERE patient_id = :id
");
        $stmt->execute(['id' => $id]);
        $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

        // Merge attendance into $data
        $data['attendance_completed'] = (int) ($attendance['attendance_completed'] ?? 0);
        $data['last_attendance_date'] = $attendance['last_attendance_date'] ?? null;


        // 3. Fetch all payments at once
        $stmt = $pdo->prepare("
    SELECT amount, payment_date
    FROM payments
    WHERE patient_id = :id
");
        $stmt->execute(['id' => $id]);
        $allPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalPaid = 0;
        $todayPaid = 0;
        $today = date('Y-m-d');

        foreach ($allPayments as $payment) {
            $totalPaid += (float)$payment['amount'];
            if ($payment['payment_date'] === $today) {
                $todayPaid += (float)$payment['amount'];
            }
        }

        $data['total_paid'] = $totalPaid;
        $data['today_paid'] = $todayPaid;

        // 4. Calculate per-day consumed amount
        $costPerDay = 0;
        $treatmentDays = (int) ($data['treatment_days'] ?? 0);

        if (strtolower($data['treatment_type'] ?? '') === 'package' && $treatmentDays > 0) {
            $costPerDay = (float)($data['package_cost'] ?? 0) / $treatmentDays;
        } else {
            $costPerDay = (float)($data['treatment_cost_per_day'] ?? 0);
        }

        $data['cost_per_day'] = $costPerDay;
        $data['consumed_amount'] = $costPerDay * $data['attendance_completed'];

        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'No patient found']);
    }
}
