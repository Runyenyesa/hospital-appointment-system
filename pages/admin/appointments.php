<?php
/**
 * Admin - All Appointments
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireAdmin();

$pageTitle = 'All Appointments';
$activeMenu = 'appointments';

$db = getDB();

$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$params = [];
$where = "WHERE 1=1";

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

$query = "SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, p.phone, p.email,
                 d.first_name as doctor_first, d.last_name as doctor_last, d.specialization,
                 dept.dept_name
          FROM appointments a
          JOIN users p ON a.patient_id = p.user_id
          LEFT JOIN users d ON a.doctor_id = d.user_id
          LEFT JOIN departments dept ON a.dept_id = dept.dept_id
          $where
          ORDER BY a.appointment_date DESC, a.created_at DESC";

$pagination = paginate($query, $params, 25);
$appointments = $pagination['data'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-calendar-check me-2"></i>All Appointments</h2>
    <p>System-wide appointment overview</p>
</div>

<div class="card">
    <div class="card-header">
        <h5>Appointments</h5>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <input type="date" name="date_from" class="form-control form-select-sm" value="<?php echo e($dateFrom); ?>">
            <input type="date" name="date_to" class="form-control form-select-sm" value="<?php echo e($dateTo); ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Type</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td>#<?php echo $appt['appointment_id']; ?></td>
                        <td><?php echo e($appt['patient_first'] . ' ' . $appt['patient_last']); ?></td>
                        <td><?php echo $appt['doctor_first'] ? 'Dr. ' . e($appt['doctor_first'] . ' ' . $appt['doctor_last']) : '<span class="text-muted">-</span>'; ?></td>
                        <td><?php echo formatDate($appt['appointment_date']); ?><br><small><?php echo formatTime($appt['start_time']); ?></small></td>
                        <td><?php echo e(ucfirst(str_replace('_', ' ', $appt['appointment_type']))); ?></td>
                        <td><?php echo statusBadge($appt['status']); ?></td>
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

<?php include __DIR__ . '/../../includes/footer.php'; ?>
