<?php
/**
 * Admin - Reports & Analytics
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireAdmin();

$pageTitle = 'Reports & Analytics';
$activeMenu = 'reports';

$db = getDB();

// Date range filter
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Appointment statistics by status
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM appointments 
                       WHERE appointment_date BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$dateFrom, $dateTo]);
$statusStats = $stmt->fetchAll();

// Appointments by department
$stmt = $db->prepare("SELECT d.dept_name, COUNT(*) as count 
                       FROM appointments a
                       LEFT JOIN departments d ON a.dept_id = d.dept_id
                       WHERE a.appointment_date BETWEEN ? AND ?
                       GROUP BY a.dept_id ORDER BY count DESC");
$stmt->execute([$dateFrom, $dateTo]);
$deptStats = $stmt->fetchAll();

// Appointments trend (daily)
$stmt = $db->prepare("SELECT appointment_date, COUNT(*) as count 
                       FROM appointments 
                       WHERE appointment_date BETWEEN ? AND ?
                       GROUP BY appointment_date ORDER BY appointment_date");
$stmt->execute([$dateFrom, $dateTo]);
$trendData = $stmt->fetchAll();

// User growth
$stmt = $db->query("SELECT DATE(created_at) as date, COUNT(*) as count, role_id 
                       FROM users 
                       WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                       GROUP BY DATE(created_at), role_id ORDER BY date");
$userGrowth = $stmt->fetchAll();

// Top doctors by completed appointments
$stmt = $db->prepare("SELECT u.first_name, u.last_name, u.specialization, COUNT(*) as count
                       FROM appointments a
                       JOIN users u ON a.doctor_id = u.user_id
                       WHERE a.status = 'completed' AND a.appointment_date BETWEEN ? AND ?
                       GROUP BY a.doctor_id ORDER BY count DESC LIMIT 10");
$stmt->execute([$dateFrom, $dateTo]);
$topDoctors = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-graph-up me-2"></i>Reports & Analytics</h2>
    <p>System-wide statistics and trends</p>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5>Filter</h5>
        <form method="GET" class="d-flex gap-2">
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo e($dateFrom); ?>">
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo e($dateTo); ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Status Distribution -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5>Appointment Status Distribution</h5>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Status</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($statusStats as $s): ?>
                            <tr>
                                <td><?php echo statusBadge($s['status']); ?></td>
                                <td><strong><?php echo $s['count']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($statusStats)): ?>
                            <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Department Stats -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5>Appointments by Department</h5>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Department</th><th>Appointments</th></tr></thead>
                        <tbody>
                            <?php foreach ($deptStats as $d): ?>
                            <tr>
                                <td><?php echo e($d['dept_name'] ?? 'Unassigned'); ?></td>
                                <td><strong><?php echo $d['count']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($deptStats)): ?>
                            <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Doctors -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5>Top Performing Doctors</h5>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Doctor</th><th>Specialization</th><th>Completed</th></tr></thead>
                        <tbody>
                            <?php foreach ($topDoctors as $doc): ?>
                            <tr>
                                <td>Dr. <?php echo e($doc['first_name'] . ' ' . $doc['last_name']); ?></td>
                                <td><?php echo e($doc['specialization']); ?></td>
                                <td><strong><?php echo $doc['count']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topDoctors)): ?>
                            <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Trend Summary -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5>Daily Appointment Trend</h5>
            </div>
            <div class="card-body">
                <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Count</th></tr></thead>
                        <tbody>
                            <?php foreach ($trendData as $t): ?>
                            <tr>
                                <td><?php echo formatDate($t['appointment_date']); ?></td>
                                <td><strong><?php echo $t['count']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($trendData)): ?>
                            <tr><td colspan="2" class="text-center text-muted">No data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>