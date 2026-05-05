<?php
require_once 'includes/config.php';
$db = getDB();

try {
    echo "Adding proposed_date column...\n";
    $db->exec("ALTER TABLE appointments ADD COLUMN proposed_date DATE DEFAULT NULL AFTER appointment_date");
} catch (Exception $e) {
    echo "proposed_date column might already exist or error: " . $e->getMessage() . "\n";
}

try {
    echo "Adding proposed_time column...\n";
    $db->exec("ALTER TABLE appointments ADD COLUMN proposed_time TIME DEFAULT NULL AFTER start_time");
} catch (Exception $e) {
    echo "proposed_time column might already exist or error: " . $e->getMessage() . "\n";
}

try {
    echo "Updating status ENUM to include 'proposed'...\n";
    $db->exec("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending','approved','rejected','completed','cancelled','no_show','proposed') DEFAULT 'pending'");
    echo "Status ENUM updated.\n";
} catch (Exception $e) {
    echo "Error updating status ENUM: " . $e->getMessage() . "\n";
}

echo "DB Migration complete.\n";
