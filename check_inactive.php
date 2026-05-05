<?php
require_once 'includes/config.php';
$db = getDB();
$users = $db->query('SELECT email, is_active, role_id FROM users WHERE is_active = 0')->fetchAll();
if (empty($users)) {
    echo "No inactive users found.\n";
} else {
    foreach ($users as $u) {
        echo "Email: " . $u['email'] . " | Active: " . $u['is_active'] . " | Role: " . $u['role_id'] . "\n";
    }
}
?>
