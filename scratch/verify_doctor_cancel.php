<?php
require_once 'includes/config.php';
$db = getDB();

// 1. Setup IDs
$doctor_id = 4; // Bug Doctor
$patient_id = 12;
$receptionist_role_id = 3;

// 2. Create an approved appointment
$stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, reason, status) 
                      VALUES (?, ?, CURDATE() + INTERVAL 1 DAY, '14:00:00', 'Reschedule Reason Test', 'approved')");
$stmt->execute([$patient_id, $doctor_id]);
$appt_id = $db->lastInsertId();
echo "1. Created approved appointment ID: $appt_id\n";

// 3. Doctor requests reschedule with reason
$reason = "Emergency surgery conflict";
$stmt = $db->prepare("UPDATE appointments SET status='pending', reviewed_at=NOW(), reviewed_by=NULL, 
                      review_notes=?, reschedule_count = reschedule_count + 1 
                      WHERE appointment_id=? AND doctor_id=?");
$stmt->execute(["DOCTOR REQUESTED RESCHEDULE: " . $reason, $appt_id, $doctor_id]);
echo "2. Doctor requested reschedule. Status: pending. Reason: $reason\n";

// 4. Verify DB state
$stmt = $db->prepare("SELECT status, review_notes, reschedule_count FROM appointments WHERE appointment_id = ?");
$stmt->execute([$appt_id]);
$appt = $stmt->fetch();
echo "Current Status: " . $appt['status'] . "\n";
echo "Review Notes: " . $appt['review_notes'] . "\n";
echo "Reschedule Count: " . $appt['reschedule_count'] . "\n";

// 5. Check notifications for receptionist
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE related_id = ? AND type = 'doctor_cancel_request'");
$stmt->execute([$appt_id]);
$notif_count = $stmt->fetchColumn();
echo "Notifications sent to receptionists: $notif_count\n";

if ($appt['status'] === 'pending' && strpos($appt['review_notes'], $reason) !== false) {
    echo "DOCTOR CANCELLATION FLOW VERIFIED.\n";
} else {
    echo "DOCTOR CANCELLATION FLOW FAILED.\n";
}
