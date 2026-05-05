<?php
require_once 'includes/config.php';
$db = getDB();
$roles = $db->query('SELECT * FROM roles')->fetchAll();
foreach ($roles as $r) {
    echo "ID: " . $r['role_id'] . " | Name: " . $r['role_name'] . " | Slug: " . $r['role_slug'] . "\n";
}
?>
