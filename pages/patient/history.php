<?php
/**
 * Patient - Medical History
 */
require_once __DIR__ . '/../../includes/middleware.php';
requirePatient();

$pageTitle = 'Medical History';
$activeMenu = 'history';

$db = getDB();
$patientId = currentUserId();

// Get medical records with doctor and appointment info
$stmt = $db->prepare("SELECT mr.*, 
                            d.first_name as doctor_first, d.last_name as doctor_last, d.specialization,
                            a.appointment_date, a.appointment_id
                     FROM medical_records mr
                     JOIN users d ON mr.doctor_id = d.user_id
                     LEFT JOIN appointments a ON mr.appointment_id = a.appointment_id
                     WHERE mr.patient_id = ?
                     ORDER BY mr.created_at DESC");
$stmt->execute([$patientId]);
$records = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-file-medical me-2"></i>Medical History</h2>
    <p>Your complete medical records and consultation history</p>
</div>

<?php if (empty($records)): ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <i class="bi bi-file-medical"></i>
            <p>No medical records yet. Records will appear after your first completed appointment.</p>
            <a href="<?php echo APP_URL; ?>/pages/patient/book.php" class="btn btn-primary mt-2">Book Appointment</a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="timeline">
    <?php foreach ($records as $record): ?>
    <div class="timeline-item success">
        <div class="timeline-content">
            <div class="d-flex justify-content-between mb-2">
                <h6 class="mb-0">Dr. <?php echo e($record['doctor_first'] . ' ' . $record['doctor_last']); ?></h6>
                <small class="text-muted"><?php echo formatDate($record['created_at']); ?></small>
            </div>
            <p class="mb-1"><strong>Specialization:</strong> <?php echo e($record['specialization']); ?></p>
            
            <?php if ($record['appointment_date']): ?>
            <p class="mb-1 text-muted"><small>Appointment: <?php echo formatDate($record['appointment_date']); ?></small></p>
            <?php endif; ?>
            
            <?php if ($record['diagnosis']): ?>
            <div class="mb-2">
                <span class="badge bg-danger">Diagnosis</span>
                <p class="mb-0 mt-1"><?php echo nl2br(e($record['diagnosis'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($record['prescription']): ?>
            <div class="mb-2">
                <span class="badge bg-primary">Prescription</span>
                <p class="mb-0 mt-1"><?php echo nl2br(e($record['prescription'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($record['tests_recommended']): ?>
            <div class="mb-2">
                <span class="badge bg-warning text-dark">Tests</span>
                <p class="mb-0 mt-1"><?php echo nl2br(e($record['tests_recommended'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($record['test_results']): ?>
            <div class="mb-2">
                <span class="badge bg-success">Test Results</span>
                <p class="mb-0 mt-1"><?php echo nl2br(e($record['test_results'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>