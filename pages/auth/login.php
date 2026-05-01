<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../../includes/auth.php';

requireGuest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            flashMessage('Welcome back, ' . $_SESSION['full_name'] . '!', 'success');
            
            $redirect = match($_SESSION['role_slug']) {
                'admin' => '/pages/admin/dashboard.php',
                'doctor' => '/pages/doctor/dashboard.php',
                'receptionist' => '/pages/receptionist/dashboard.php',
                'patient' => '/pages/patient/dashboard.php',
                default => '/index.php'
            };
            redirect($redirect);
        } else {
            $error = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <i class="bi bi-hospital fs-1 mb-2 d-block"></i>
                <h3><?php echo e(APP_NAME); ?></h3>
                <p class="mb-0 opacity-75">Appointment Management System</p>
            </div>
            
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" data-validate>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="#" class="text-decoration-none" style="font-size: 0.9rem;">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </button>
                </form>
                
                <div class="mt-3 text-center">
                    <small class="text-muted">Demo Accounts:</small>
                    <div class="mt-2">
                        <span class="badge bg-secondary me-1">admin@hospital.com</span>
                        <span class="badge bg-secondary me-1">dr.smith@hospital.com</span>
                        <span class="badge bg-secondary me-1">reception@hospital.com</span>
                        <span class="badge bg-secondary">patient@demo.com</span>
                    </div>
                    <small class="text-muted d-block mt-1">Password: password123</small>
                </div>
            </div>
            
            <div class="auth-footer">
                Don't have an account? <a href="<?php echo APP_URL; ?>/pages/auth/register.php">Register as Patient</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>