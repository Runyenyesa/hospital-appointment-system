<?php
require_once 'includes/config.php';

try {
    $db = getDB();
    
    // Correct hash for 'password123'
    $newHash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Update all demo users (those with the old placeholder hash)
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' OR email IN ('admin@hospital.com', 'dr.smith@hospital.com', 'reception@hospital.com', 'patient@demo.com')");
    $stmt->execute([$newHash]);
    
    echo "Successfully updated " . $stmt->rowCount() . " demo accounts to use 'password123'.\n";
    
} catch (Exception $e) {
    echo "Error updating passwords: " . $e->getMessage() . "\n";
}
?>
