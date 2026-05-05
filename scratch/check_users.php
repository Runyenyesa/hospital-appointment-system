<?php
require_once 'includes/config.php';
$db = getDB();

echo "--- Users Table ---\n";
$stmt = $db->query("DESCRIBE users");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
