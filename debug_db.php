<?php
require_once 'includes/config.php';

try {
    $db = getDB();
    $stmt = $db->query("SELECT user_id, email, password_hash, is_active, role_id FROM users ORDER BY user_id DESC LIMIT 5");
    $users = $stmt->fetchAll();
    
    echo "Last 5 users:\n";
    foreach ($users as $u) {
        echo "ID: " . $u['user_id'] . " | Email: " . $u['email'] . " | Active: " . $u['is_active'] . " | Role: " . $u['role_id'] . " | Hash Length: " . strlen($u['password_hash']) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
