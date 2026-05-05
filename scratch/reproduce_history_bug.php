<?php
require_once 'includes/config.php';
$db = getDB();

// 1. Create a patient and doctor if they don't exist
$patient_email = 'bugpatient@test.com';
$doctor_email = 'bugdoctor@test.com';

$stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$patient_email]);
$patient_id = $stmt->fetchColumn();

if (!$patient_id) {
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, first_name, last_name, role_id, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmt->execute([$patient_email, password_hash('password123', PASSWORD_DEFAULT), 'Bug', 'Patient', 1]);
    $patient_id = $db->lastInsertId();
}

$stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$doctor_email]);
$doctor_id = $stmt->fetchColumn();

if (!$doctor_id) {
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, first_name, last_name, role_id, is_active) VALUES (?, ?, ?, ?, ?, 2)");
    $stmt->execute([$doctor_email, password_hash('password123', PASSWORD_DEFAULT), 'Bug', 'Doctor', 2]);
    $doctor_id = $db->lastInsertId();
}

// 2. Create a completed appointment WITHOUT a medical record
$stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, reason, status, completed_at) 
                      VALUES (?, ?, CURDATE(), '10:00:00', '10:30:00', 'Bug Test', 'completed', NOW())");
$stmt->execute([$patient_id, $doctor_id]);
$appt_id = $db->lastInsertId();

echo "Created completed appointment ID: $appt_id for patient ID: $patient_id\n";

// 3. Test the NEW query in history.php
$stmt = $db->prepare("
    SELECT 
        'record' as type,
        mr.record_id,
        mr.created_at as event_date,
        mr.diagnosis,
        mr.prescription,
        mr.tests_recommended,
        mr.test_results,
        a.appointment_date,
        a.appointment_id,
        a.reason as appointment_reason,
        d.first_name as doctor_first, 
        d.last_name as doctor_last, 
        d.specialization
    FROM medical_records mr
    JOIN users d ON mr.doctor_id = d.user_id
    LEFT JOIN appointments a ON mr.appointment_id = a.appointment_id
    WHERE mr.patient_id = ?

    UNION

    SELECT 
        'appointment' as type,
        NULL as record_id,
        a.completed_at as event_date,
        NULL as diagnosis,
        NULL as prescription,
        NULL as tests_recommended,
        NULL as test_results,
        a.appointment_date,
        a.appointment_id,
        a.reason as appointment_reason,
        d.first_name as doctor_first, 
        d.last_name as doctor_last, 
        d.specialization
    FROM appointments a
    JOIN users d ON a.doctor_id = d.user_id
    LEFT JOIN medical_records mr ON a.appointment_id = mr.appointment_id
    WHERE a.patient_id = ? AND a.status = 'completed' AND mr.record_id IS NULL

    ORDER BY event_date DESC
");
$stmt->execute([$patient_id, $patient_id]);
$records = $stmt->fetchAll();

echo "Records found in history for patient $patient_id: " . count($records) . "\n";

if (count($records) > 0) {
    echo "FIX VERIFIED: Completed appointment now shows up in history.\n";
} else {
    echo "FIX FAILED: Still no records found.\n";
}
