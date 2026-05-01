<?php
/**
 * Receptionist Dashboard
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireReceptionist();

$pageTitle = 'Receptionist Dashboard';
$activeMenu = 'dashboard';

$db = getDB();

// Stats
$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURDATE()");
$todayCount = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$pendingCount = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'approved' AND appointment_date = CURDATE()");
$confirmedToday = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role_id = 4 AND DATE(created_at) = CURDATE()");
$newPatientsToday = $stmt->fetchColumn();

// Pending approvals (needs attention)
$stmt = $db->query("SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, p.phone,
                            d.first_name as doctor_first, d.last_name as doctor_last, d.specialization,
                            dept.dept_name
                     FROM appointments a
                     JOIN users p ON a.patient_id = p.user_id
                     LEFT JOIN users d ON a.doctor_id = d.user_id
                     LEFT JOIN departments dept ON a.dept_id = dept.dept_id
                     WHERE a.status = 'pending'
                     ORDER BY a.created_at DESC LIMIT 8");
$pendingApprovals = $stmt->fetchAll();

// Today's confirmed appointments
$stmt = $db->query("SELECT a.*, p.first_name as patient_first, p.last_name as patient_last,
                            d.first_name as doctor_first, d.last_name as doctor_last, d.specialization
                     FROM appointments a
                     JOIN users p ON a.patient_id = p.user_id
                     LEFT JOIN users d ON a.doctor_id = d.user_id
                     WHERE a.appointment_date = CURDATE() AND a.status IN ('approved', 'completed')
                     ORDER BY a.start_time");
$todaySchedule = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-speedometer2 me-2"></i>Reception Dashboard</h2>
    <p>Manage appointments and front desk operations</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-value"><?php echo $todayCount; ?></div>
            <div class="stat-label">Today's Requests</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-value"><?php echo $pendingCount; ?></div>
            <div class="stat-label">Pending Approval</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-value"><?php echo $confirmedToday; ?></div>
            <div class="stat-label">Confirmed Today</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-person-plus"></i></div>
            <div class="stat-value"><?php echo $newPatientsToday; ?></div>
            <div class="stat-label">New Patients</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Pending Approvals -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-hourglass-split me-2 text-warning"></i>Pending Approvals</h5>
                <a href="<?php echo APP_URL; ?>/pages/receptionist/appointments.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingApprovals)): ?>
                <div class="empty-state py-5">
                    <i class="bi bi-check-circle text-success"></i>
                    <p>No pending approvals. All caught up!</p>
                </div>
                <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>Patient</th><th>Requested</th><th>Department/Doctor</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingApprovals as $appt): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($appt['patient_first'] . ' ' . $appt['patient_last']); ?></strong><br>
                                    <small class="text-muted"><?php echo e($appt['phone']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo formatDate($appt['appointment_date']); ?></strong><br>
                                    <small class="text-muted"><?php echo formatTime($appt['start_time']); ?></small>
                                </td>
                                <td>
                                    <?php if ($appt['doctor_first']): ?>
                                        Dr. <?php echo e($appt['doctor_first'] . ' ' . $appt['doctor_last']); ?><br>
                                        <small class="text-muted"><?php echo e($appt['specialization']); ?></small>
                                    <?php elseif ($appt['dept_name']): ?>
                                        <span class="badge bg-light text-dark"><?php echo e($appt['dept_name']); ?></span><br>
                                        <small class="text-muted">Doctor not assigned</small>
                                    <?php else: ?>
                                        <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Assign doctor/dept</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo APP_URL; ?>/pages/receptionist/appointments.php?action=approve&id=<?php echo $appt['appointment_id']; ?>" 
                                       class="btn btn-sm btn-success" onclick="return confirm('Approve this appointment?');">
                                        <i class="bi bi-check-lg"></i>
                                    </a>
                                    <a href="<?php echo APP_URL; ?>/pages/receptionist/appointments.php?action=reject&id=<?php echo $appt['appointment_id']; ?>" 
                                       class="btn btn-sm btn-danger" onclick="return confirm('Reject this appointment?');">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                    <a href="<?php echo APP_URL; ?>/pages/receptionist/appointments.php?action=edit&id=<?php echo $appt['appointment_id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Today's Schedule -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-calendar-day me-2"></i>Today's Schedule</h5>
                <span class="badge bg-primary"><?php echo date('M d'); ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todaySchedule)): ?>
                <div class="empty-state py-4">
                    <i class="bi bi-calendar-x"></i>
                    <p>No confirmed appointments today</p>
                </div>
                <?php else: ?>
                <?php foreach ($todaySchedule as $appt): ?>
                <div class="appointment-list-item px-3">
                    <div class="appt-time">
                        <?php echo formatTime($appt['start_time']); ?>
                    </div>
                    <div class="appt-info">
                        <h6 class="mb-0"><?php echo e($appt['patient_first'] . ' ' . $appt['patient_last']); ?></h6>
                        <small class="text-muted">
                            Dr. <?php echo e($appt['doctor_first'] . ' ' . $appt['doctor_last']); ?> 
                            &bull; <?php echo e($appt['specialization']); ?>
                        </small>
                    </div>
                    <div class="appt-status">
                        <?php echo statusBadge($appt['status']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/pages/receptionist/appointments.php" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-check me-2"></i>All Appointments
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/receptionist/walkins.php" class="btn btn-outline-primary">
                        <i class="bi bi-person-walking me-2"></i>Walk-in Patient
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/receptionist/patients.php?action=add" class="btn btn-outline-primary">
                        <i class="bi bi-person-plus me-2"></i>Register Patient
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>