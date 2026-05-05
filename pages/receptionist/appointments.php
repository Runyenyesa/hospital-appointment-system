<?php
/**
 * Receptionist - Appointment Management
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireReceptionist();

$pageTitle = 'Manage Appointments';
$activeMenu = 'appointments';

$db = getDB();
$action = $_GET['action'] ?? 'list';

// Approve appointment
if ($action === 'approve' && !empty($_GET['id'])) {
    try {
        $apptId = (int) $_GET['id'];
        
        // Verify appointment exists and is pending
        $stmt = $db->prepare("SELECT patient_id, doctor_id, status FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$apptId]);
        $appt = $stmt->fetch();
        
        if (!$appt) {
            flashMessage('Appointment not found', 'error');
        } elseif ($appt['status'] !== 'pending') {
            flashMessage('This appointment has already been processed', 'info');
        } else {
            $stmt = $db->prepare("UPDATE appointments SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE appointment_id=?");
            $stmt->execute([currentUserId(), $apptId]);
            
            sendNotification($appt['patient_id'], 'appointment_approved', 'Appointment Approved',
                'Your appointment has been approved by the reception.', $apptId, 'appointment');
            
            if ($appt['doctor_id']) {
                sendNotification($appt['doctor_id'], 'doctor_assigned', 'New Appointment',
                    'A new appointment has been assigned to you.', $apptId, 'appointment');
            }
            
            flashMessage('Appointment approved successfully', 'success');
        }
    } catch (Exception $e) {
        flashMessage('Error: ' . $e->getMessage(), 'error');
    }
    redirect('/pages/receptionist/appointments.php');
}

// Reject appointment
if ($action === 'reject' && !empty($_GET['id'])) {
    try {
        $apptId = (int) $_GET['id'];
        $reason = $_GET['reason'] ?? 'Not available at requested time';
        
        $stmt = $db->prepare("UPDATE appointments SET status='rejected', reviewed_by=?, reviewed_at=NOW(), review_notes=? WHERE appointment_id=?");
        $stmt->execute([currentUserId(), $reason, $apptId]);
        
        $stmt = $db->prepare("SELECT patient_id FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$apptId]);
        $patientId = $stmt->fetchColumn();
        
        if ($patientId) {
            sendNotification($patientId, 'appointment_rejected', 'Appointment Rejected',
                'Your appointment was rejected. Reason: ' . $reason, $apptId, 'appointment');
        }
        
        flashMessage('Appointment rejected', 'warning');
    } catch (Exception $e) {
        flashMessage('Error: ' . $e->getMessage(), 'error');
    }
    redirect('/pages/receptionist/appointments.php');
}

// Update appointment (assign doctor, reschedule, or propose new time)
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $apptId = (int) ($_POST['appointment_id'] ?? 0);
    $doctorId = !empty($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : null;
    $deptId = !empty($_POST['dept_id']) ? (int) $_POST['dept_id'] : null;
    $newDate = $_POST['appointment_date'] ?? null;
    $newTime = $_POST['start_time'] ?? null;
    $propDate = $_POST['proposed_date'] ?? null;
    $propTime = $_POST['proposed_time'] ?? null;
    $isProposal = isset($_POST['is_proposal']) && $_POST['is_proposal'] == '1';
    $notes = trim($_POST['notes'] ?? '');
    
    if ($isProposal && (!empty($propDate) || !empty($propTime))) {
        $stmt = $db->prepare("UPDATE appointments SET 
            doctor_id = ?, dept_id = ?, 
            proposed_date = ?, proposed_time = ?,
            status = 'proposed',
            review_notes = ?, reviewed_by = ?, reviewed_at = NOW(),
            reschedule_count = reschedule_count + 1
            WHERE appointment_id = ?");
        $stmt->execute([$doctorId, $deptId, $propDate, $propTime, $notes, currentUserId(), $apptId]);
        
        $msg = 'Appointment proposal sent to patient.';
        $notifTitle = 'New Appointment Proposal';
        $notifMsg = 'The receptionist has proposed an alternative time for your appointment: ' . formatDate($propDate) . ' at ' . formatTime($propTime);
        $notifType = 'appointment_proposed';
    } else {
        $stmt = $db->prepare("UPDATE appointments SET 
            doctor_id = ?, dept_id = ?, 
            appointment_date = CASE WHEN ? != '' THEN ? ELSE appointment_date END, 
            start_time = CASE WHEN ? != '' THEN ? ELSE start_time END, 
            notes = ?, reviewed_by = ?, reviewed_at = NOW(),
            reschedule_count = reschedule_count + 1
            WHERE appointment_id = ?");
        $stmt->execute([$doctorId, $deptId, $newDate, $newDate, $newTime, $newTime, $notes, currentUserId(), $apptId]);
        
        $msg = 'Appointment updated successfully';
        $notifTitle = 'Appointment Updated';
        $notifMsg = 'Your appointment details have been updated. Please check your appointments.';
        $notifType = 'appointment_approved';
    }
    
    $stmt = $db->prepare("SELECT patient_id FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$apptId]);
    $patientId = $stmt->fetchColumn();
    
    sendNotification($patientId, $notifType, $notifTitle, $notifMsg, $apptId, 'appointment');
    
    flashMessage($msg, 'success');
    redirect('/pages/receptionist/appointments.php');
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$params = [];
$where = "WHERE 1=1";

if ($statusFilter) {
    $where .= " AND a.status = ?";
    $params[] = $statusFilter;
}
if ($dateFilter) {
    $where .= " AND a.appointment_date = ?";
    $params[] = $dateFilter;
}

$query = "SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, p.phone, p.email,
                 d.first_name as doctor_first, d.last_name as doctor_last, d.specialization,
                 dept.dept_name, r.first_name as reviewed_by_name
          FROM appointments a
          JOIN users p ON a.patient_id = p.user_id
          LEFT JOIN users d ON a.doctor_id = d.user_id
          LEFT JOIN departments dept ON a.dept_id = dept.dept_id
          LEFT JOIN users r ON a.reviewed_by = r.user_id
          $where
          ORDER BY a.appointment_date DESC, a.created_at DESC";

$pagination = paginate($query, $params, 20);
$appointments = $pagination['data'];

// Get doctors and departments for edit form
$stmt = $db->query("SELECT user_id, first_name, last_name, specialization FROM users WHERE role_id = 2 AND is_active = 1 ORDER BY first_name");
$doctors = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY dept_name");
$departments = $stmt->fetchAll();

// Edit appointment
$editAppt = null;
if ($action === 'edit' && !empty($_GET['id'])) {
    $stmt = $db->prepare("SELECT a.*, p.first_name as patient_first, p.last_name as patient_last
                          FROM appointments a
                          JOIN users p ON a.patient_id = p.user_id
                          WHERE a.appointment_id = ?");
    $stmt->execute([(int) $_GET['id']]);
    $editAppt = $stmt->fetch();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-calendar-check me-2"></i>Manage Appointments</h2>
        <p>Approve, reject, reschedule, or assign doctors to appointments</p>
    </div>
</div>

<?php if ($action === 'edit' && $editAppt): ?>
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-pencil-square me-2"></i>Edit Appointment</h5>
        <a href="?" class="btn btn-sm btn-secondary">Back</a>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <strong>Patient:</strong> <?php echo e($editAppt['patient_first'] . ' ' . $editAppt['patient_last']); ?><br>
            <strong>Current Date/Time:</strong> <?php echo formatDate($editAppt['appointment_date']); ?> at <?php echo formatTime($editAppt['start_time']); ?><br>
            <strong>Reason:</strong> <?php echo e($editAppt['reason']); ?>
        </div>
        
        <form method="POST" action="?action=update">
            <input type="hidden" name="appointment_id" value="<?php echo $editAppt['appointment_id']; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Assign Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">-- Select Doctor --</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?php echo $doc['user_id']; ?>" <?php echo ($editAppt['doctor_id'] == $doc['user_id']) ? 'selected' : ''; ?>>
                            Dr. <?php echo e($doc['first_name'] . ' ' . $doc['last_name']); ?> (<?php echo e($doc['specialization']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <select name="dept_id" class="form-select">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['dept_id']; ?>" <?php echo ($editAppt['dept_id'] == $dept['dept_id']) ? 'selected' : ''; ?>>
                            <?php echo e($dept['dept_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Reschedule Date (Confirm now)</label>
                    <input type="date" name="appointment_date" class="form-control" value="<?php echo $editAppt['appointment_date']; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Reschedule Time (Confirm now)</label>
                    <input type="time" name="start_time" class="form-control" value="<?php echo $editAppt['start_time']; ?>">
                </div>
            </div>

            <div class="card bg-light mb-3">
                <div class="card-body">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_proposal" value="1" id="isProposal">
                        <label class="form-check-label fw-bold" for="isProposal">
                            Propose Alternative Time (Patient must accept)
                        </label>
                    </div>
                    <div class="row proposal-fields">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Proposed Date</label>
                            <input type="date" name="proposed_date" class="form-control form-control-sm" value="<?php echo $editAppt['proposed_date']; ?>">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Proposed Time</label>
                            <input type="time" name="proposed_time" class="form-control form-control-sm" value="<?php echo $editAppt['proposed_time']; ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Internal notes..."><?php echo e($editAppt['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Update Appointment</button>
                <a href="?action=approve&id=<?php echo $editAppt['appointment_id']; ?>" class="btn btn-success" onclick="return confirm('Approve this appointment?');">
                    <i class="bi bi-check-lg me-2"></i>Approve
                </a>
                <a href="?" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <h5>All Appointments</h5>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <input type="date" name="date" class="form-control form-select-sm" value="<?php echo e($dateFilter); ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient</th><th>Date/Time</th><th>Doctor</th><th>Type</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($appt['patient_first'] . ' ' . $appt['patient_last']); ?></strong><br>
                            <small class="text-muted"><?php echo e($appt['phone']); ?></small>
                        </td>
                        <td><?php echo formatDate($appt['appointment_date']); ?><br><small><?php echo formatTime($appt['start_time']); ?></small></td>
                        <td>
                            <?php if ($appt['doctor_first']): ?>
                                Dr. <?php echo e($appt['doctor_first'] . ' ' . $appt['doctor_last']); ?><br>
                                <small class="text-muted"><?php echo e($appt['specialization']); ?></small>
                            <?php else: ?>
                                <span class="text-warning">Not assigned</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e(ucfirst(str_replace('_', ' ', $appt['appointment_type']))); ?></td>
                        <td>
                            <?php echo statusBadge($appt['status']); ?>
                            <?php if (strpos($appt['review_notes'] ?? '', 'DOCTOR REQUESTED RESCHEDULE') === 0): ?>
                                <div class="mt-1">
                                    <span class="badge bg-danger">Dr. Reschedule Req</span>
                                    <small class="d-block text-danger mt-1" style="font-size: 0.75rem;">
                                        <?php echo e(str_replace('DOCTOR REQUESTED RESCHEDULE: ', '', $appt['review_notes'])); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($appt['status'] === 'pending'): ?>
                            <a href="?action=approve&id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Approve?');"><i class="bi bi-check-lg"></i></a>
                            <a href="?action=reject&id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Reject?');"><i class="bi bi-x-lg"></i></a>
                            <a href="?action=edit&id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-info" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php elseif ($appt['status'] === 'approved'): ?>
                            <a href="?action=edit&id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-info" title="Reschedule/Edit"><i class="bi bi-pencil"></i></a>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No appointments found</td></tr>
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
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>