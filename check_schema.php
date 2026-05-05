<?php
require_once 'includes/config.php';

try {
    $db = getDB();
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $col) {
        echo "Field: " . $col['Field'] . " | Type: " . $col['Type'] . " | Null: " . $col['Null'] . " | Default: " . $col['Default'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
