<?php
require_once 'includes/auth.php';

$data = [
    'role_id' => 2, // Doctor
    'email' => 'testdoctor@test.com',
    'password' => 'password123',
    'first_name' => 'Test',
    'last_name' => 'Doctor'
];

echo "Registering user...\n";
$reg = registerUser($data);
print_r($reg);

if ($reg['success']) {
    echo "\nAttempting login...\n";
    $login = loginUser('testdoctor@test.com', 'password123');
    print_r($login);
    
    if (!$login['success']) {
        echo "\nDebugging login failure:\n";
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute(['testdoctor@test.com']);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "User found in DB.\n";
            echo "Active: " . $user['is_active'] . "\n";
            echo "Hash: " . $user['password_hash'] . "\n";
            echo "Password Verify: " . (password_verify('password123', $user['password_hash']) ? 'TRUE' : 'FALSE') . "\n";
        } else {
            echo "User NOT found in DB.\n";
        }
    }
}
?>
