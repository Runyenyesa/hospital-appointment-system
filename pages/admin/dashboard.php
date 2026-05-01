<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireAdmin();

$pageTitle = 'Admin Dashboard';
$activeMenu = 'dashboard';

$db = getDB();

// Get dashboard statistics
$stats = [];
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 4 AND is_active = 1");
$stats['total_patients'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 2 AND is_active = 1");
$stats['total_doctors'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURDATE()");
$stats['today_appointments'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$stats['pending_appointments'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'approved' AND appointment_date >= CURDATE()");
$stats['upcoming_appointments'] = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 3 AND is_active = 1");
$stats['total_staff'] = $stmt->fetchColumn();

// Recent appointments
$stmt = $db->query("SELECT a.*, p.first_name as patient_first, p.last_name as patient_last,
                            d.first_name as doctor_first, d.last_name as doctor_last
                     FROM appointments a
                     JOIN users p ON a.patient_id = p.user_id
                     LEFT JOIN users d ON a.doctor_id = d.user_id
                     ORDER BY a.created_at DESC LIMIT 10");
$recentAppointments = $stmt->fetchAll();

// Recent users
$stmt = $db->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h2>
    <p>Overview of hospital operations and system statistics</p>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-value"><?php echo $stats['today_appointments']; ?></div>
            <div class="stat-label">Today's Appointments</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value"><?php echo $stats['pending_appointments']; ?></div>
            <div class="stat-label">Pending Approvals</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-people"></i></div>
            <div class="stat-value"><?php echo $stats['total_patients']; ?></div>
            <div class="stat-label">Total Patients</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-heart-pulse"></i></div>
            <div class="stat-value"><?php echo $stats['total_doctors']; ?></div>
            <div class="stat-label">Active Doctors</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon secondary"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-value"><?php echo $stats['upcoming_appointments']; ?></div>
            <div class="stat-label">Upcoming Appointments</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-person-badge"></i></div>
            <div class="stat-value"><?php echo $stats['total_staff']; ?></div>
            <div class="stat-label">Staff Members</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon danger"><i class="bi bi-x-circle"></i></div>
            <div class="stat-value">
                <?php
                $stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'cancelled' AND DATE(cancelled_at) = CURDATE()");
                echo $stmt->fetchColumn();
                ?>
            </div>
            <div class="stat-label">Cancelled Today</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value">
                <?php
                $stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'completed' AND DATE(completed_at) = CURDATE()");
                echo $stmt->fetchColumn();
                ?>
            </div>
            <div class="stat-label">Completed Today</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Appointments -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-calendar3 me-2"></i>Recent Appointments</h5>
                <a href="<?php echo APP_URL; ?>/pages/admin/appointments.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAppointments as $appt): ?>
                            <tr>
                                <td><?php echo e($appt['patient_first'] . ' ' . $appt['patient_last']); ?></td>
                                <td><?php echo $appt['doctor_first'] ? e($appt['doctor_first'] . ' ' . $appt['doctor_last']) : '<span class="text-muted">Not assigned</span>'; ?></td>
                                <td><?php echo formatDate($appt['appointment_date']); ?></td>
                                <td><span class="badge bg-light text-dark"><?php echo e(ucfirst(str_replace('_', ' ', $appt['appointment_type']))); ?></span></td>
                                <td><?php echo statusBadge($appt['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentAppointments)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No appointments yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-people me-2"></i>Recent Users</h5>
                <a href="<?php echo APP_URL; ?>/pages/admin/users.php" class="btn btn-sm btn-primary">Manage</a>
            </div>
            <div class="card-body p-0">
                <?php foreach ($recentUsers as $user): ?>
                <div class="d-flex align-items-center p-3 border-bottom" style="border-color: #f1f5f9 !important;">
                    <div class="user-avatar me-3" style="width: 40px; height: 40px; font-size: 0.8rem;">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                    </div>
                    <div class="flex-1" style="min-width: 0;">
                        <h6 class="mb-0 text-truncate" style="font-size: 0.9rem;"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                        <small class="text-muted"><?php echo e($user['role_name']); ?> &bull; <?php echo timeAgo($user['created_at']); ?></small>
                    </div>
                    <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>" style="font-size: 0.7rem;">
                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($recentUsers)): ?>
                <div class="text-center py-4 text-muted">No users yet</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/pages/admin/users.php?action=add" class="btn btn-outline-primary">
                        <i class="bi bi-person-plus me-2"></i>Add New User
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/admin/departments.php" class="btn btn-outline-primary">
                        <i class="bi bi-building me-2"></i>Manage Departments
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/admin/reports.php" class="btn btn-outline-primary">
                        <i class="bi bi-graph-up me-2"></i>View Reports
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/admin/settings.php" class="btn btn-outline-primary">
                        <i class="bi bi-gear me-2"></i>System Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>