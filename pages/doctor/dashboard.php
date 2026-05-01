<?php
/**
 * Doctor Dashboard
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireDoctor();

$pageTitle = 'Doctor Dashboard';
$activeMenu = 'dashboard';

$db = getDB();
$doctorId = currentUserId();

// Stats
$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE()");
$stmt->execute([$doctorId]);
$todayCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'approved' AND appointment_date >= CURDATE()");
$stmt->execute([$doctorId]);
$upcomingCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'completed'");
$stmt->execute([$doctorId]);
$completedCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM medical_records WHERE doctor_id = ?");
$stmt->execute([$doctorId]);
$recordsCount = $stmt->fetchColumn();

// Today's schedule
$stmt = $db->prepare("SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, p.phone as patient_phone,
                            p.email as patient_email, p.date_of_birth
                     FROM appointments a
                     JOIN users p ON a.patient_id = p.user_id
                     WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() AND a.status IN ('approved', 'pending', 'completed')
                     ORDER BY a.start_time");
$stmt->execute([$doctorId]);
$todayAppointments = $stmt->fetchAll();

// Upcoming appointments
$stmt = $db->prepare("SELECT a.*, p.first_name as patient_first, p.last_name as patient_last
                     FROM appointments a
                     JOIN users p ON a.patient_id = p.user_id
                     WHERE a.doctor_id = ? AND a.appointment_date > CURDATE() AND a.status = 'approved'
                     ORDER BY a.appointment_date, a.start_time LIMIT 5");
$stmt->execute([$doctorId]);
$upcomingAppointments = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-speedometer2 me-2"></i>Doctor Dashboard</h2>
    <p>Welcome back, Dr. <?php echo e($_SESSION['full_name'] ?? ''); ?>. Here's your day at a glance.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-value"><?php echo $todayCount; ?></div>
            <div class="stat-label">Today's Patients</div>
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
    <!-- Today's Schedule -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-calendar-day me-2"></i>Today's Schedule (<?php echo date('l, M d, Y'); ?>)</h5>
                <a href="<?php echo APP_URL; ?>/pages/doctor/appointments.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($todayAppointments)): ?>
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <p>No appointments scheduled for today</p>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($todayAppointments as $appt): ?>
                    <div class="timeline-item <?php echo $appt['status'] === 'completed' ? 'success' : ($appt['status'] === 'pending' ? 'warning' : 'primary'); ?>">
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1"><?php echo e($appt['patient_first'] . ' ' . $appt['patient_last']); ?></h6>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i><?php echo formatTime($appt['start_time']); ?>
                                        <?php if ($appt['appointment_type'] === 'walk_in'): ?>
                                            <span class="badge bg-info ms-2">Walk-in</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div><?php echo statusBadge($appt['status']); ?></div>
                            </div>
                            <p class="mb-1 mt-1 text-muted" style="font-size: 0.85rem;">
                                <strong>Reason:</strong> <?php echo e($appt['reason']); ?>
                            </p>
                            <?php if ($appt['status'] === 'approved'): ?>
                            <div class="mt-2">
                                <a href="<?php echo APP_URL; ?>/pages/doctor/appointments.php?action=complete&id=<?php echo $appt['appointment_id']; ?>" 
                                   class="btn btn-sm btn-success"
                                   onclick="return confirm('Mark this appointment as completed?');">
                                    <i class="bi bi-check-lg me-1"></i>Complete
                                </a>
                                <a href="<?php echo APP_URL; ?>/pages/doctor/appointments.php?action=notes&id=<?php echo $appt['appointment_id']; ?>" 
                                   class="btn btn-sm btn-info ms-1">
                                    <i class="bi bi-journal-text me-1"></i>Add Notes
                                </a>
                                <a href="<?php echo APP_URL; ?>/pages/doctor/appointments.php?action=cancel&id=<?php echo $appt['appointment_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger ms-1"
                                   onclick="return confirm('Cancel this appointment?');">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Upcoming & Quick Actions -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-calendar-week me-2"></i>Upcoming Appointments</h5>
            </div>
            <div class="card-body p-0">
                <?php foreach ($upcomingAppointments as $appt): ?>
                <div class="appointment-list-item px-3">
                    <div class="appt-time"><?php echo date('d', strtotime($appt['appointment_date'])); ?><br><small><?php echo date('M', strtotime($appt['appointment_date'])); ?></small></div>
                    <div class="appt-info">
                        <h6 class="mb-0"><?php echo e($appt['patient_first'] . ' ' . $appt['patient_last']); ?></h6>
                        <small class="text-muted"><?php echo formatTime($appt['start_time']); ?> &bull; <?php echo e($appt['reason']); ?></small>
                    </div>
                    <div class="appt-status"><?php echo statusBadge($appt['status']); ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($upcomingAppointments)): ?>
                <div class="text-center py-4 text-muted">No upcoming appointments</div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/pages/doctor/schedule.php" class="btn btn-outline-primary">
                        <i class="bi bi-clock me-2"></i>Manage Schedule
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/doctor/patients.php" class="btn btn-outline-primary">
                        <i class="bi bi-people me-2"></i>My Patients
                    </a>
                    <a href="<?php echo APP_URL; ?>/pages/doctor/records.php" class="btn btn-outline-primary">
                        <i class="bi bi-file-medical me-2"></i>Medical Records
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>