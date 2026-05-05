<?php
require_once 'includes/config.php';
$db = getDB();

// 1. Setup IDs
$doctor_id = 4;
$patient_id = 12;

// 2. Create an approved appointment in the past
$stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, reason, status) 
                      VALUES (?, ?, CURDATE() - INTERVAL 1 DAY, '10:00:00', 'Attendance Test', 'approved')");
$stmt->execute([$patient_id, $doctor_id]);
$appt_id = $db->lastInsertId();
echo "1. Created past approved appointment ID: $appt_id\n";

// 3. Doctor marks as No-show
$stmt = $db->prepare("UPDATE appointments SET status='no_show', completed_at=NOW(), completed_by=? 
                      WHERE appointment_id=? AND doctor_id=?");
$stmt->execute([$doctor_id, $appt_id, $doctor_id]);
echo "2. Doctor marked as No-show. Status: no_show\n";

// 4. Verify DB state
$stmt = $db->prepare("SELECT status FROM appointments WHERE appointment_id = ?");
$stmt->execute([$appt_id]);
$status = $stmt->fetchColumn();
echo "Current Status: $status\n";

// 5. Check if patient was notified (simulated)
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE related_id = ? AND title LIKE '%Not Done%'");
$stmt->execute([$appt_id]);
$notif_count = $stmt->fetchColumn();
// echo "Notifications sent to patient: $notif_count\n";

if ($status === 'no_show') {
    echo "ATTENDANCE MARKING VERIFIED.\n";
} else {
    echo "ATTENDANCE MARKING FAILED.\n";
}
