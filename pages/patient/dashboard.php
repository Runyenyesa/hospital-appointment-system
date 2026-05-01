<?php
/**
 * Patient Dashboard
 */
require_once __DIR__ . '/../../includes/middleware.php';
requirePatient();

$pageTitle = 'Patient Dashboard';
$activeMenu = 'dashboard';

$db = getDB();
$patientId = currentUserId();

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
$stmt->execute([$patientId]);
$totalAppointments = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'approved' AND appointment_date >= CURDATE()");
$stmt->execute([$patientId]);
$upcomingCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'completed'");
$stmt->execute([$patientId]);
$completedCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM medical_records WHERE patient_id = ?");
$stmt->execute([$patientId]);
$recordsCount = $stmt->fetchColumn();

// Upcoming appointments
$stmt = $db->prepare("SELECT a.*, d.first_name as doctor_first, d.last_name as doctor_last, d.specialization, dept.dept_name
                     FROM appointments a
                     LEFT JOIN users d ON a.doctor_id = d.user_id
                     LEFT JOIN departments dept ON a.dept_id = dept.dept_id
                     WHERE a.patient_id = ? AND a.status = 'approved' AND a.appointment_date >= CURDATE()
                     ORDER BY a.appointment_date, a.start_time LIMIT 5");
$stmt->execute([$patientId]);
$upcomingAppointments = $stmt->fetchAll();

// Recent notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$patientId]);
$notifications = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-speedometer2 me-2"></i>My Dashboard</h2>
    <p>Welcome, <?php echo e($_SESSION['full_name'] ?? ''); ?>. Manage your health appointments here.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-value"><?php echo $totalAppointments; ?></div>
            <div class="stat-label">Total Appointments</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-value"><?php echo $upcomingCount; ?></div>
            <div class="stat-label">Upcoming</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?php echo $completedCount; ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon warning"><i class="bi bi-file-medical"></i></div>
            <div class="stat-value"><?php echo $recordsCount; ?></div>
            <div class="stat-label">Medical Records</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Upcoming Appointments -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5><i class="bi bi-calendar-week me-2"></i>Upcoming Appointments</h5>
                <a href="<?php echo APP_URL; ?>/pages/patient/book.php" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Book New</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingAppointments)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>No upcoming appointments. <a href="<?php echo APP_URL; ?>/pages/patient/book.php">Book one now</a></p>
                </div>
                <?php else: ?>
                <?php foreach ($upcomingAppointments as $appt): ?>
                <div class="appointment-list-item">
                    <div class="appt-time">
                        <?php echo date('d', strtotime($appt['appointment_date'])); ?><br>
                        <small><?php echo date('M', strtotime($appt['appointment_date'])); ?></small>
                    </div>
                    <div class="appt-info">
                        <h6 class="mb-0">
                            <?php if ($appt['doctor_first']): ?>
                                Dr. <?php echo e($appt['doctor_first'] . ' ' . $appt['doctor_last']); ?>
                                <span class="text-muted" style="font-weight: normal;">(<?php echo e($appt['specialization']); ?>)</span>
                            <?php elseif ($appt['dept_name']): ?>
                                <?php echo e($appt['dept_name']); ?>
                            <?php else: ?>
                                <span class="text-muted">Doctor to be assigned</span>
                            <?php endif; ?>
                        </h6>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i><?php echo formatTime($appt['start_time']); ?> 
                            &bull; <?php echo e($appt['reason']); ?>
                        </small>
                    </div>
                    <div class="appt-status">
                        <?php echo statusBadge($appt['status']); ?>
                        <a href="<?php echo APP_URL; ?>/pages/patient/appointments.php?action=cancel&id=<?php echo $appt['appointment_id']; ?>" 
                           class="btn btn-sm btn-outline-danger mt-1 d-block"
                           onclick="return confirm('Cancel this appointment?');">
                            Cancel
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Notifications & Quick Actions -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-bell me-2"></i>Notifications</h5>
            </div>
            <div class="card-body p-0">
                <?php foreach ($notifications as $notif): ?>
                <div class="p-3 border-bottom" style="border-color: #f1f5f9 !important; <?php echo !$notif['is_read'] ? 'background: #eff6ff;' : ''; ?>">
                    <h6 class="mb-1" style="font-size: 0.85rem;"><?php echo e($notif['title']); ?></h6>
                    <p class="mb-1 text-muted" style="font-size: 0.8rem;"><?php echo e($notif['message']); ?></p>
                    <small class="text-muted"><?php echo timeAgo($notif['created_at']); ?></small>
                </div>
                <?php endforeach; ?>
                <?php if (empty($notifications)): ?>
                <div class="text-center py-4 text-muted">No notifications</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/pages/patient/book.php" class="btn btn-primary">
                        <i class="bi bi-calendar-plus me-2"></i>Book Appointment
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/patient/appointments.php" class="btn btn-outline-primary">
                        <i class="bi bi-calendar-check me-2"></i>My Appointments
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/patient/history.php" class="btn btn-outline-primary">
                        <i class="bi bi-file-medical me-2"></i>Medical History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
