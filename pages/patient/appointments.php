<?php
/**
 * Patient - My Appointments
 */
require_once __DIR__ . '/../../includes/middleware.php';
requirePatient();

$pageTitle = 'My Appointments';
$activeMenu = 'appointments';

$db = getDB();
$patientId = currentUserId();
$action = $_GET['action'] ?? 'list';

// Cancel own appointment
if ($action === 'cancel' && !empty($_GET['id'])) {
    $apptId = (int) $_GET['id'];
    $reason = $_POST['cancellation_reason'] ?? 'Cancelled by patient';
    
    $stmt = $db->prepare("UPDATE appointments SET status='cancelled', cancelled_at=NOW(), cancelled_by=?, cancellation_reason=? 
                          WHERE appointment_id=? AND patient_id=? AND status IN ('pending', 'approved')");
    $stmt->execute([$patientId, $reason, $apptId, $patientId]);
    
    if ($stmt->rowCount() > 0) {
        flashMessage('Appointment cancelled successfully', 'success');
    } else {
        flashMessage('Unable to cancel this appointment', 'error');
    }
    redirect('/pages/patient/appointments.php');
}

// Reschedule
if ($action === 'reschedule' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $apptId = (int) ($_POST['appointment_id'] ?? 0);
    $newDate = $_POST['new_date'] ?? '';
    $newTime = $_POST['new_time'] ?? '';
    
    if (empty($newDate) || empty($newTime)) {
        flashMessage('Please select both date and time', 'error');
    } else {
        $stmt = $db->prepare("UPDATE appointments SET appointment_date=?, start_time=?, status='pending', 
                              reschedule_count = reschedule_count + 1 WHERE appointment_id=? AND patient_id=?");
        $stmt->execute([$newDate, $newTime, $apptId, $patientId]);
        
        flashMessage('Appointment rescheduled. Pending approval.', 'success');
    }
    redirect('/pages/patient/appointments.php');
}

// Get all appointments for this patient
$statusFilter = $_GET['status'] ?? '';
$params = [$patientId];
$where = "WHERE patient_id = ?";

if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$query = "SELECT a.*, d.first_name as doctor_first, d.last_name as doctor_last, d.specialization, dept.dept_name
          FROM appointments a
          LEFT JOIN users d ON a.doctor_id = d.user_id
          LEFT JOIN departments dept ON a.dept_id = dept.dept_id
          $where
          ORDER BY a.appointment_date DESC, a.created_at DESC";

$pagination = paginate($query, $params, 15);
$appointments = $pagination['data'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-calendar-check me-2"></i>My Appointments</h2>
        <p>View and manage your appointment history</p>
    </div>
    <a href="<?php echo APP_URL; ?>/pages/patient/book.php" class="btn btn-primary">
        <i class="bi bi-calendar-plus me-2"></i>Book New
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h5>Appointment History</h5>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Doctor/Department</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td>
                            <strong><?php echo formatDate($appt['appointment_date']); ?></strong><br>
                            <small class="text-muted"><?php echo formatTime($appt['start_time']); ?></small>
                        </td>
                        <td>
                            <?php if ($appt['doctor_first']): ?>
                                Dr. <?php echo e($appt['doctor_first'] . ' ' . $appt['doctor_last']); ?><br>
                                <small class="text-muted"><?php echo e($appt['specialization']); ?></small>
                            <?php elseif ($appt['dept_name']): ?>
                                <?php echo e($appt['dept_name']); ?>
                            <?php else: ?>
                                <span class="text-muted">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($appt['reason']); ?></td>
                        <td><?php echo statusBadge($appt['status']); ?></td>
                        <td>
                            <?php if ($appt['status'] === 'pending' || $appt['status'] === 'approved'): ?>
                                <?php if (strtotime($appt['appointment_date']) >= strtotime(date('Y-m-d'))): ?>
                                <a href="?action=cancel&id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this appointment?');">
                                    <i class="bi bi-x-lg me-1"></i>Cancel
                                </a>
                                <?php endif; ?>
                            <?php elseif ($appt['status'] === 'completed'): ?>
                                <a href="<?php echo APP_URL; ?>/pages/patient/history.php?appointment_id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye me-1"></i>View Record
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No appointments found. <a href="book.php">Book your first appointment</a></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="d-flex justify-content-center py-3">
            <nav><ul class="pagination">
                <?php if ($pagination['hasPrev']): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $pagination['page']-1; ?>&status=<?php echo e($statusFilter); ?>">Previous</a></li>
                <?php endif; ?>
                <?php for ($i=1; $i<=$pagination['totalPages']; $i++): ?>
                <li class="page-item <?php echo $i==$pagination['page']?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo e($statusFilter); ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($pagination['hasNext']): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $pagination['page']+1; ?>&status=<?php echo e($statusFilter); ?>">Next</a></li>
                <?php endif; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>