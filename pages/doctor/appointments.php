<?php
/**
 * Doctor - My Appointments
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireDoctor();

$pageTitle = 'My Appointments';
$activeMenu = 'appointments';

$db = getDB();
$doctorId = currentUserId();
$action = $_GET['action'] ?? 'list';

if ($action === 'mark_attendance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $apptId = (int) ($_POST['appointment_id'] ?? 0);
    $status = $_POST['attendance_status'] ?? 'completed'; // 'completed' or 'no_show'
    
    $stmt = $db->prepare("UPDATE appointments SET status=?, completed_at=NOW(), completed_by=? WHERE appointment_id=? AND doctor_id=?");
    $stmt->execute([$status, $doctorId, $apptId, $doctorId]);
    
    $stmt = $db->prepare("SELECT patient_id, appointment_date FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$apptId]);
    $appt = $stmt->fetch();
    
    $statusLabel = $status === 'completed' ? 'Done' : 'Not Done (No-show)';
    $msg = "Appointment marked as $statusLabel";
    
    sendNotification($appt['patient_id'], 'appointment_status_update', "Appointment $statusLabel",
        "Your appointment on " . formatDate($appt['appointment_date']) . " has been marked as $statusLabel.", $apptId, 'appointment');
    
    // Notify receptionists
    $stmt = $db->query("SELECT user_id FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_slug = 'receptionist')");
    $receptionists = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($receptionists as $recId) {
        sendNotification($recId, 'appointment_status_update', 'Appointment Outcome',
            "Appointment #$apptId marked as $statusLabel by Dr. " . $_SESSION['full_name'], $apptId, 'appointment');
    }

    flashMessage($msg, 'success');
    redirect('/pages/doctor/appointments.php');
}

if ($action === 'cancel' && !empty($_GET['id'])) {
    $apptId = (int) $_GET['id'];
    $reason = $_POST['cancellation_reason'] ?? 'Not specified';
    
    // Set status back to pending so receptionist can reschedule, and store doctor's reason
    $stmt = $db->prepare("UPDATE appointments SET status='pending', reviewed_at=NOW(), reviewed_by=NULL, 
                          review_notes=?, reschedule_count = reschedule_count + 1 
                          WHERE appointment_id=? AND doctor_id=?");
    $stmt->execute(["DOCTOR REQUESTED RESCHEDULE: " . $reason, $apptId, $doctorId]);
    
    // Get patient info for notification
    $stmt = $db->prepare("SELECT patient_id, appointment_date FROM appointments WHERE appointment_id = ?");
    $stmt->execute([$apptId]);
    $apptData = $stmt->fetch();
    
    // Notify Patient
    sendNotification($apptData['patient_id'], 'appointment_reschedule_request', 'Appointment Reschedule Needed',
        'Your appointment on ' . formatDate($apptData['appointment_date']) . ' needs to be rescheduled by the doctor. Reason: ' . $reason, $apptId, 'appointment');
    
    // Notify all Receptionists
    $stmt = $db->query("SELECT user_id FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_slug = 'receptionist')");
    $receptionists = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($receptionists as $recId) {
        sendNotification($recId, 'doctor_cancel_request', 'Doctor Reschedule Request',
            'Dr. ' . $_SESSION['full_name'] . ' requested to reschedule appointment #' . $apptId . '. Reason: ' . $reason, $apptId, 'appointment');
    }
    
    flashMessage('Reschedule request sent to receptionist', 'success');
    redirect('/pages/doctor/appointments.php');
}

if ($action === 'notes' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $apptId = (int) ($_POST['appointment_id'] ?? 0);
    $notes = trim($_POST['doctor_notes'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $tests = trim($_POST['tests_recommended'] ?? '');
    
    // Update appointment notes
    $stmt = $db->prepare("UPDATE appointments SET doctor_notes = ? WHERE appointment_id = ? AND doctor_id = ?");
    $stmt->execute([$notes, $apptId, $doctorId]);
    
    // Create medical record if diagnosis provided
    if ($diagnosis || $prescription || $tests) {
        $stmt = $db->prepare("SELECT patient_id FROM appointments WHERE appointment_id = ?");
        $stmt->execute([$apptId]);
        $patientId = $stmt->fetchColumn();
        
        $stmt = $db->prepare("INSERT INTO medical_records 
            (patient_id, appointment_id, doctor_id, diagnosis, prescription, tests_recommended) 
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patientId, $apptId, $doctorId, $diagnosis, $prescription, $tests]);
    }
    
    flashMessage('Notes saved successfully', 'success');
    redirect('/pages/doctor/appointments.php');
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$params = [$doctorId];
$where = "WHERE a.doctor_id = ?";

if ($statusFilter) {
    $where .= " AND a.status = ?";
    $params[] = $statusFilter;
}
if ($dateFrom) {
    $where .= " AND a.appointment_date >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND a.appointment_date <= ?";
    $params[] = $dateTo;
}

$query = "SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, p.phone, p.email
          FROM appointments a
          JOIN users p ON a.patient_id = p.user_id
          $where
          ORDER BY a.appointment_date DESC, a.start_time DESC";

$pagination = paginate($query, $params, 20);
$appointments = $pagination['data'];

// Get single appointment for notes
$noteAppointment = null;
if ($action === 'notes' && !empty($_GET['id'])) {
    $stmt = $db->prepare("SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, p.user_id as patient_id
                          FROM appointments a
                          JOIN users p ON a.patient_id = p.user_id
                          WHERE a.appointment_id = ? AND a.doctor_id = ?");
    $stmt->execute([(int) $_GET['id'], $doctorId]);
    $noteAppointment = $stmt->fetch();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-calendar-check me-2"></i>My Appointments</h2>
        <p>View and manage your appointments</p>
    </div>
</div>

<?php if ($action === 'notes' && $noteAppointment): ?>
<!-- Add Notes Form -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-journal-text me-2"></i>Consultation Notes</h5>
        <a href="?" class="btn btn-sm btn-secondary">Back</a>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <strong>Patient:</strong> <?php echo e($noteAppointment['patient_first'] . ' ' . $noteAppointment['patient_last']); ?><br>
            <strong>Date:</strong> <?php echo formatDate($noteAppointment['appointment_date']); ?> at <?php echo formatTime($noteAppointment['start_time']); ?><br>
            <strong>Reason:</strong> <?php echo e($noteAppointment['reason']); ?>
        </div>
        
        <form method="POST" action="?action=notes">
            <input type="hidden" name="appointment_id" value="<?php echo $noteAppointment['appointment_id']; ?>">
            
            <div class="mb-3">
                <label class="form-label">Consultation Notes</label>
                <textarea name="doctor_notes" class="form-control" rows="4" placeholder="Enter your consultation notes..."><?php echo e($noteAppointment['doctor_notes'] ?? ''); ?></textarea>
            </div>
            
            <hr>
            <h6 class="mb-3">Medical Record</h6>
            
            <div class="mb-3">
                <label class="form-label">Diagnosis</label>
                <textarea name="diagnosis" class="form-control" rows="2" placeholder="Diagnosis..."></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Prescription</label>
                <textarea name="prescription" class="form-control" rows="2" placeholder="Prescribed medications..."></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tests Recommended</label>
                <textarea name="tests_recommended" class="form-control" rows="2" placeholder="Recommended tests..."></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Save Notes</button>
                <a href="?" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<!-- Filter & List -->
<div class="card">
    <div class="card-header">
        <h5>Appointments</h5>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo e($dateFrom); ?>">
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo e($dateTo); ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr><th>Patient</th><th>Date</th><th>Time</th><th>Type</th><th>Reason</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?php echo e($appt['patient_first'] . ' ' . $appt['patient_last']); ?></td>
                        <td><?php echo formatDate($appt['appointment_date']); ?></td>
                        <td><?php echo formatTime($appt['start_time']); ?></td>
                        <td><?php echo e(ucfirst(str_replace('_', ' ', $appt['appointment_type']))); ?></td>
                        <td><?php echo e($appt['reason']); ?></td>
                        <td><?php echo statusBadge($appt['status']); ?></td>
                        <td>
                            <?php if ($appt['status'] === 'approved'): ?>
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-check-circle me-1"></i>Mark Outcome
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <form method="POST" action="?action=mark_attendance">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                            <input type="hidden" name="attendance_status" value="completed">
                                            <button type="submit" class="dropdown-item text-success">
                                                <i class="bi bi-check-lg me-2"></i>Done (Attended)
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="?action=mark_attendance">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                            <input type="hidden" name="attendance_status" value="no_show">
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-x-lg me-2"></i>Not Done (No-show)
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            <a href="?action=notes&id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-journal-text"></i></a>
                            <button type="button" class="btn btn-sm btn-danger" title="Request Reschedule" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $appt['appointment_id']; ?>">
                                <i class="bi bi-calendar-x"></i>
                            </button>

                            <!-- Cancel/Reschedule Modal -->
                            <div class="modal fade" id="cancelModal<?php echo $appt['appointment_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form class="modal-content" method="POST" action="?action=cancel&id=<?php echo $appt['appointment_id']; ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Request Reschedule</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Please provide a reason why you need to reschedule this appointment. This will be sent to the receptionist.</p>
                                            <div class="mb-3">
                                                <label class="form-label">Reason for Reschedule</label>
                                                <textarea name="cancellation_reason" class="form-control" rows="3" required placeholder="e.g. Personal emergency, surgical conflict..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-danger">Submit Request</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php elseif ($appt['status'] === 'completed'): ?>
                            <a href="?action=notes&id=<?php echo $appt['appointment_id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i> View Notes</a>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($appointments)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No appointments found</td></tr>
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