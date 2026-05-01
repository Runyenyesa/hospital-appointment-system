<?php
/**
 * Home Page / Landing Page
 */
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    $dashboard = match($_SESSION['role_slug']) {
        'admin' => '/pages/admin/dashboard.php',
        'doctor' => '/pages/doctor/dashboard.php',
        'receptionist' => '/pages/receptionist/dashboard.php',
        'patient' => '/pages/patient/dashboard.php',
        default => '/index.php'
    };
    redirect($dashboard);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(APP_NAME); ?> - Appointment Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --primary-dark: #1d4ed8; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; }
        .hero { background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 50%, #f0f9ff 100%); padding: 80px 0; }
        .hero h1 { font-size: 3rem; font-weight: 700; color: #1e293b; }
        .hero .lead { color: #64748b; font-size: 1.2rem; }
        .feature-card { padding: 30px; border-radius: 16px; background: #fff; border: 1px solid #e2e8f0; height: 100%; transition: transform 0.2s; }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .feature-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 16px; }
        .navbar-brand { font-weight: 700; color: var(--primary) !important; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .footer { background: #1e293b; color: #94a3b8; padding: 40px 0; }
        .role-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-hospital me-2"></i><?php echo e(APP_NAME); ?></a>
            <div class="d-flex gap-2">
                <a href="<?php echo APP_URL; ?>/pages/auth/login.php" class="btn btn-outline-primary">Login</a>
                <a href="<?php echo APP_URL; ?>/pages/auth/register.php" class="btn btn-primary">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container text-center">
            <h1 class="mb-3">Healthcare Made Simple</h1>
            <p class="lead mb-4">Book appointments, track your medical history, and connect with healthcare professionals — all in one place.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="<?php echo APP_URL; ?>/pages/auth/register.php" class="btn btn-primary btn-lg px-4">
                    <i class="bi bi-person-plus me-2"></i>Get Started
                </a>
                <a href="<?php echo APP_URL; ?>/pages/auth/login.php" class="btn btn-outline-primary btn-lg px-4">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>System Features</h2>
                <p class="text-muted">Comprehensive healthcare management for everyone</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background: #eff6ff; color: var(--primary);">
                            <i class="bi bi-person"></i>
                        </div>
                        <h5>Patients</h5>
                        <p class="text-muted mb-3">Book appointments, view medical history, and receive notifications about your healthcare journey.</p>
                        <span class="role-badge" style="background: #eff6ff; color: var(--primary);"><i class="bi bi-circle-fill" style="font-size: 6px;"></i> Patient Portal</span>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background: #ecfdf5; color: #10b981;">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <h5>Doctors</h5>
                        <p class="text-muted mb-3">Manage schedules, view patient records, add consultation notes, and track appointments.</p>
                        <span class="role-badge" style="background: #ecfdf5; color: #10b981;"><i class="bi bi-circle-fill" style="font-size: 6px;"></i> Doctor Dashboard</span>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background: #fffbeb; color: #f59e0b;">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h5>Receptionists</h5>
                        <p class="text-muted mb-3">Handle appointment approvals, manage walk-ins, assign doctors, and coordinate patient flow.</p>
                        <span class="role-badge" style="background: #fffbeb; color: #f59e0b;"><i class="bi bi-circle-fill" style="font-size: 6px;"></i> Front Desk</span>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto" style="background: #f5f3ff; color: #8b5cf6;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h5>Administrators</h5>
                        <p class="text-muted mb-3">Full system control, user management, analytics, departments, and system configuration.</p>
                        <span class="role-badge" style="background: #f5f3ff; color: #8b5cf6;"><i class="bi bi-circle-fill" style="font-size: 6px;"></i> Admin Control</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Appointment Workflow -->
    <section class="py-5" style="background: #f8fafc;">
        <div class="container">
            <div class="text-center mb-5">
                <h2>How It Works</h2>
                <p class="text-muted">Simple 4-step appointment process</p>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-3 text-center">
                    <div class="mx-auto mb-3" style="width: 60px; height: 60px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="bi bi-calendar-plus"></i>
                    </div>
                    <h5>1. Book</h5>
                    <p class="text-muted small">Patient requests appointment with preferred doctor and time</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mx-auto mb-3" style="width: 60px; height: 60px; background: #f59e0b; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h5>2. Approve</h5>
                    <p class="text-muted small">Receptionist reviews and approves the appointment request</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mx-auto mb-3" style="width: 60px; height: 60px; background: #10b981; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <h5>3. Consult</h5>
                    <p class="text-muted small">Doctor sees patient and provides consultation</p>
                </div>
                <div class="col-md-3 text-center">
                    <div class="mx-auto mb-3" style="width: 60px; height: 60px; background: #8b5cf6; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="bi bi-file-medical"></i>
                    </div>
                    <h5>4. Record</h5>
                    <p class="text-muted small">Medical record is created and accessible to the patient</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p class="mb-2"><i class="bi bi-hospital me-2"></i><strong><?php echo e(APP_NAME); ?></strong></p>
            <p class="mb-0 small">Appointment Management System v<?php echo e(APP_VERSION); ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>