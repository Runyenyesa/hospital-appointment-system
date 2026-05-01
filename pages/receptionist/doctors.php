<?php
/**
 * Receptionist - Doctor Schedules
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireReceptionist();

$pageTitle = 'Doctor Schedules';
$activeMenu = 'doctors';

$db = getDB();

$stmt = $db->query("SELECT u.user_id, u.first_name, u.last_name, u.specialization, u.consultation_fee, u.phone, u.email,
                           d.dept_name
                    FROM users u
                    LEFT JOIN departments d ON u.dept_id = d.dept_id
                    WHERE u.role_id = 2 AND u.is_active = 1
                    ORDER BY u.first_name");
$doctors = $stmt->fetchAll();

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-heart-pulse me-2"></i>Doctor Schedules</h2>
    <p>View all doctors and their availability</p>
</div>

<div class="row g-4">
    <?php foreach ($doctors as $doc): 
        $stmt = $db->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week, start_time");
        $stmt->execute([$doc['user_id']]);
        $schedules = $stmt->fetchAll();
    ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div class="user-avatar me-3"><?php echo strtoupper(substr($doc['first_name'], 0, 1)); ?></div>
                    <div>
                        <h6 class="mb-0">Dr. <?php echo e($doc['first_name'] . ' ' . $doc['last_name']); ?></h6>
                        <small class="text-muted"><?php echo e($doc['specialization']); ?> &bull; <?php echo e($doc['dept_name']); ?></small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($schedules)): ?>
                <p class="text-muted mb-0">No schedule set</p>
                <?php else: ?>
                <div class="table-container">
                    <table class="table table-sm">
                        <thead><tr><th>Day</th><th>Time</th></tr></thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?php echo $days[$s['day_of_week']]; ?></td>
                                <td><?php echo formatTime($s['start_time']); ?> - <?php echo formatTime($s['end_time']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($doctors)): ?>
    <div class="col-12 text-center py-4 text-muted">No doctors found</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
