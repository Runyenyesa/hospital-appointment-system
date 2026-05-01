<?php
/**
 * Doctor - My Patients
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireDoctor();

$pageTitle = 'My Patients';
$activeMenu = 'patients';

$db = getDB();
$doctorId = currentUserId();

// Get distinct patients who had appointments with this doctor
$stmt = $db->prepare("SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email, u.phone, u.date_of_birth, u.gender,
                            (SELECT COUNT(*) FROM appointments WHERE patient_id = u.user_id AND doctor_id = ? AND status = 'completed') as visits
                     FROM users u
                     JOIN appointments a ON u.user_id = a.patient_id
                     WHERE a.doctor_id = ?
                     ORDER BY visits DESC");
$stmt->execute([$doctorId, $doctorId]);
$patients = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-people me-2"></i>My Patients</h2>
    <p>Patients you have consulted</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr><th>Patient</th><th>Contact</th><th>Age/Gender</th><th>Visits</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $p): 
                        $age = $p['date_of_birth'] ? floor((time() - strtotime($p['date_of_birth'])) / 31556926) : '-';
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-2" style="width: 32px; height: 32px; font-size: 0.75rem;"><?php echo strtoupper(substr($p['first_name'], 0, 1)); ?></div>
                                <?php echo e($p['first_name'] . ' ' . $p['last_name']); ?>
                            </div>
                        </td>
                        <td><?php echo e($p['email']); ?><br><small class="text-muted"><?php echo e($p['phone']); ?></small></td>
                        <td><?php echo $age; ?> yrs / <?php echo e($p['gender']); ?></td>
                        <td><span class="badge bg-primary"><?php echo $p['visits']; ?></span></td>
                        <td>
                            <a href="<?php echo APP_URL; ?>/pages/doctor/records.php?patient_id=<?php echo $p['user_id']; ?>" class="btn btn-sm btn-info">Records</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($patients)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No patients yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>