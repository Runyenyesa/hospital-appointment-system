<?php
require_once 'includes/config.php';
$db = getDB();

// 1. Setup Patient and Receptionist IDs
$patient_id = 12; // From previous reproduction
$receptionist_id = 1; // Assuming 1 exists

// 2. Create a pending appointment
$stmt = $db->prepare("INSERT INTO appointments (patient_id, appointment_date, start_time, reason, status) 
                      VALUES (?, CURDATE() + INTERVAL 1 DAY, '09:00:00', 'Negotiation Test', 'pending')");
$stmt->execute([$patient_id]);
$appt_id = $db->lastInsertId();
echo "1. Created pending appointment ID: $appt_id\n";

// 3. Receptionist proposes a new time
$prop_date = date('Y-m-d', strtotime('+2 days'));
$prop_time = '11:00:00';
$stmt = $db->prepare("UPDATE appointments SET proposed_date = ?, proposed_time = ?, status = 'proposed', reviewed_by = ?, reviewed_at = NOW() 
                      WHERE appointment_id = ?");
$stmt->execute([$prop_date, $prop_time, $receptionist_id, $appt_id]);
echo "2. Receptionist proposed: $prop_date at $prop_time. Status: proposed\n";

// 4. Verify status
$stmt = $db->prepare("SELECT status, proposed_date, proposed_time FROM appointments WHERE appointment_id = ?");
$stmt->execute([$appt_id]);
$appt = $stmt->fetch();
echo "Current Status: " . $appt['status'] . " | Prop Date: " . $appt['proposed_date'] . "\n";

// 5. Patient accepts proposal
$stmt = $db->prepare("UPDATE appointments SET appointment_date = proposed_date, start_time = proposed_time, status = 'approved', 
                      proposed_date = NULL, proposed_time = NULL WHERE appointment_id = ? AND status = 'proposed'");
$stmt->execute([$appt_id]);
echo "3. Patient accepted proposal.\n";

// 6. Verify final status
$stmt = $db->prepare("SELECT status, appointment_date, start_time FROM appointments WHERE appointment_id = ?");
$stmt->execute([$appt_id]);
$final = $stmt->fetch();
echo "Final Status: " . $final['status'] . " | Final Date: " . $final['appointment_date'] . " | Final Time: " . $final['start_time'] . "\n";

if ($final['status'] === 'approved' && $final['appointment_date'] === $prop_date) {
    echo "NEGOTIATION FLOW VERIFIED.\n";
} else {
    echo "NEGOTIATION FLOW FAILED.\n";
}
