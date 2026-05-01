<?php
/**
 * Doctor - Medical Records
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireDoctor();

$pageTitle = 'Medical Records';
$activeMenu = 'records';

$db = getDB();
$doctorId = currentUserId();

// Get records created by this doctor
$patientId = $_GET['patient_id'] ?? null;
$params = [$doctorId];
$where = "WHERE mr.doctor_id = ?";

if ($patientId) {
    $where .= " AND mr.patient_id = ?";
    $params[] = (int) $patientId;
}

$stmt = $db->prepare("SELECT mr.*, p.first_name as patient_first, p.last_name as patient_last,
                            a.appointment_date, a.reason
                     FROM medical_records mr
                     JOIN users p ON mr.patient_id = p.user_id
                     LEFT JOIN appointments a ON mr.appointment_id = a.appointment_id
                     $where
                     ORDER BY mr.created_at DESC");
$stmt->execute($params);
$records = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-file-medical me-2"></i>Medical Records</h2>
    <p>Records you have created</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr><th>Patient</th><th>Date</th><th>Diagnosis</th><th>Prescription</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?php echo e($r['patient_first'] . ' ' . $r['patient_last']); ?></td>
                        <td><?php echo formatDate($r['created_at']); ?></td>
                        <td><?php echo e(substr($r['diagnosis'], 0, 100)) . (strlen($r['diagnosis']) > 100 ? '...' : ''); ?></td>
                        <td><?php echo e(substr($r['prescription'], 0, 100)) . (strlen($r['prescription']) > 100 ? '...' : ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($records)): ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted">No records found. Create them from appointments.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>