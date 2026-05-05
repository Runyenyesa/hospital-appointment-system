<?php
require_once 'includes/config.php';
$db = getDB();

// 1. Create a new doctor
$doctor_email = 'new_doc_' . time() . '@hospital.com';
$stmt = $db->prepare("INSERT INTO users (role_id, email, password_hash, first_name, last_name, is_active, dept_id, specialization) 
                      VALUES (?, ?, 'hash', 'New', 'Doctor', 1, 1, 'Cardiologist')");
$stmt->execute([ROLE_DOCTOR, $doctor_email]);
$doctor_id = $db->lastInsertId();
echo "1. Created new doctor ID: $doctor_id with email: $doctor_email\n";

// 2. Check if doctor appears in the query used by book.php
$stmt = $db->query("SELECT u.user_id, u.first_name, u.last_name, u.dept_id
                    FROM users u
                    WHERE u.role_id = 2 AND u.is_active = 1 AND u.user_id = $doctor_id");
$doc = $stmt->fetch();

if ($doc) {
    echo "VERIFIED: New doctor appears in the query results.\n";
    echo "Doctor Name: " . $doc['first_name'] . " " . $doc['last_name'] . "\n";
    echo "Department ID: " . $doc['dept_id'] . "\n";
} else {
    echo "FAILED: New doctor does not appear in the query results.\n";
}

// 3. Clean up (optional, but good for repeatability)
// $db->exec("DELETE FROM users WHERE user_id = $doctor_id");
