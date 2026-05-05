<?php
require_once 'includes/config.php';
$db = getDB();

echo "--- Appointments Table ---\n";
$stmt = $db->query("DESCRIBE appointments");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n--- Medical Records Table ---\n";
$stmt = $db->query("DESCRIBE medical_records");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
