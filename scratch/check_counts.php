<?php
require_once 'includes/config.php';
$db = getDB();

echo "Appointment Status Counts:\n";
$stmt = $db->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['status'] . ": " . $row['count'] . "\n";
}

echo "\nMedical Records Patient IDs:\n";
$stmt = $db->query("SELECT DISTINCT patient_id FROM medical_records");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Patient ID: " . $row['patient_id'] . "\n";
}

echo "\nCompleted appointments without medical records:\n";
$stmt = $db->query("
    SELECT a.appointment_id, a.patient_id, a.status, mr.record_id
    FROM appointments a
    LEFT JOIN medical_records mr ON a.appointment_id = mr.appointment_id
    WHERE a.status = 'completed' AND mr.record_id IS NULL
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Appt ID: " . $row['appointment_id'] . " | Patient ID: " . $row['patient_id'] . "\n";
}
