<?php
/**
 * Admin - Reports
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireAdmin();

$pageTitle = 'Hospital Reports';
$activeMenu = 'reports';

$db = getDB();

// Report Settings
$reportType = $_GET['type'] ?? 'appointments';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$deptId = $_GET['dept_id'] ?? '';

// Fetch Data based on report type
$data = [];
$summary = [];

switch ($reportType) {
    case 'appointments':
        $params = [$dateFrom, $dateTo];
        $where = "WHERE a.appointment_date BETWEEN ? AND ?";
        if ($deptId) {
            $where .= " AND (a.dept_id = ? OR d_user.dept_id = ?)";
            $params[] = $deptId;
            $params[] = $deptId;
        }
        
        $query = "SELECT a.*, p.first_name as p_first, p.last_name as p_last, 
                         d_user.first_name as d_first, d_user.last_name as d_last,
                         dept.dept_name
                  FROM appointments a
                  JOIN users p ON a.patient_id = p.user_id
                  JOIN users d_user ON a.doctor_id = d_user.user_id
                  LEFT JOIN departments dept ON (a.dept_id = dept.dept_id OR d_user.dept_id = dept.dept_id)
                  $where
                  ORDER BY a.appointment_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        // Summary stats
        $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM appointments a 
                             JOIN users d_user ON a.doctor_id = d_user.user_id
                             $where GROUP BY status");
        $stmt->execute($params);
        $summary = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        break;

    case 'patients':
        $params = [$dateFrom, $dateTo];
        $query = "SELECT u.*, 
                         (SELECT COUNT(*) FROM appointments WHERE patient_id = u.user_id AND appointment_date BETWEEN ? AND ?) as total_appointments,
                         (SELECT MAX(appointment_date) FROM appointments WHERE patient_id = u.user_id AND appointment_date BETWEEN ? AND ?) as last_visit
                  FROM users u
                  WHERE u.role_id = (SELECT role_id FROM roles WHERE role_slug = 'patient')
                  ORDER BY total_appointments DESC, u.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
        $data = $stmt->fetchAll();
        break;

    case 'doctors':
        $params = [$dateFrom, $dateTo];
        $query = "SELECT u.*, d.dept_name,
                         (SELECT COUNT(*) FROM appointments WHERE doctor_id = u.user_id AND status = 'completed' AND appointment_date BETWEEN ? AND ?) as completed_appointments,
                         (SELECT SUM(consultation_fee) FROM appointments a 
                          WHERE a.doctor_id = u.user_id AND a.status = 'completed' AND a.appointment_date BETWEEN ? AND ?) as total_revenue
                  FROM users u
                  LEFT JOIN departments d ON u.dept_id = d.dept_id
                  WHERE u.role_id = (SELECT role_id FROM roles WHERE role_slug = 'doctor')
                  ORDER BY completed_appointments DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
        $data = $stmt->fetchAll();
        break;

    case 'departments':
        $params = [$dateFrom, $dateTo];
        $query = "SELECT d.dept_name, 
                         (SELECT COUNT(*) FROM appointments a 
                          LEFT JOIN users u ON a.doctor_id = u.user_id
                          WHERE (a.dept_id = d.dept_id OR u.dept_id = d.dept_id) 
                          AND a.appointment_date BETWEEN ? AND ?) as total_appointments,
                         (SELECT COUNT(DISTINCT user_id) FROM users WHERE dept_id = d.dept_id AND role_id = (SELECT role_id FROM roles WHERE role_slug = 'doctor')) as total_doctors
                  FROM departments d
                  ORDER BY total_appointments DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$dateFrom, $dateTo]);
        $data = $stmt->fetchAll();
        break;
}

// Get departments for filter
$departments = $db->query("SELECT * FROM departments WHERE is_active = 1")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="d-print-none mb-4">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-graph-up me-2"></i>Hospital Reports</h2>
            <p>Generate and print detailed system reports</p>
        </div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer me-2"></i>Print Report
        </button>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Report Type</label>
                    <select name="type" class="form-select">
                        <option value="appointments" <?php echo $reportType === 'appointments' ? 'selected' : ''; ?>>Appointments Overview</option>
                        <option value="patients" <?php echo $reportType === 'patients' ? 'selected' : ''; ?>>Patient Directory</option>
                        <option value="doctors" <?php echo $reportType === 'doctors' ? 'selected' : ''; ?>>Doctor Performance</option>
                        <option value="departments" <?php echo $reportType === 'departments' ? 'selected' : ''; ?>>Department Statistics</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label fw-bold">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $dateFrom; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $dateTo; ?>">
                </div>

                <?php if ($reportType === 'appointments'): ?>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Department</label>
                    <select name="dept_id" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['dept_id']; ?>" <?php echo $deptId == $dept['dept_id'] ? 'selected' : ''; ?>>
                            <?php echo e($dept['dept_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark w-100">
                        <i class="bi bi-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Printable Area -->
<div class="report-container">
    <div class="d-none d-print-block text-center mb-4">
        <h1 class="mb-1"><?php echo e(APP_NAME); ?></h1>
        <h3 class="text-muted"><?php echo ucfirst($reportType); ?> Report</h3>
        <p>Generated on: <?php echo date('F j, Y, g:i a'); ?></p>
        <hr>
    </div>

    <?php if ($reportType === 'appointments'): ?>
        <div class="row mb-4 d-print-flex">
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-primary text-white border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <h6 class="mb-1">Total</h6>
                        <h3 class="mb-0"><?php echo array_sum($summary); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-success text-white border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <h6 class="mb-1">Completed</h6>
                        <h3 class="mb-0"><?php echo $summary['completed'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-warning text-dark border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <h6 class="mb-1">Pending</h6>
                        <h3 class="mb-0"><?php echo $summary['pending'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="card bg-danger text-white border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <h6 class="mb-1">Cancelled</h6>
                        <h3 class="mb-0"><?php echo $summary['cancelled'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo formatDate($row['appointment_date']); ?></div>
                                    <small class="text-muted"><?php echo formatTime($row['start_time']); ?></small>
                                </td>
                                <td><?php echo e($row['p_first'] . ' ' . $row['p_last']); ?></td>
                                <td>Dr. <?php echo e($row['d_first'] . ' ' . $row['d_last']); ?></td>
                                <td><?php echo e($row['dept_name'] ?? '-'); ?></td>
                                <td><?php echo statusBadge($row['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($reportType === 'patients'): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Member Since</th>
                                <th>Visits</th>
                                <th>Last Visit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></div>
                                    <small class="text-muted"><?php echo e($row['gender']); ?>, <?php echo e($row['date_of_birth']); ?></small>
                                </td>
                                <td>
                                    <div><?php echo e($row['email']); ?></div>
                                    <small class="text-muted"><?php echo e($row['phone']); ?></small>
                                </td>
                                <td><?php echo date('M Y', strtotime($row['created_at'])); ?></td>
                                <td><span class="badge bg-info"><?php echo $row['total_appointments']; ?></span></td>
                                <td><?php echo $row['last_visit'] ? formatDate($row['last_visit']) : 'Never'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($reportType === 'doctors'): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Department</th>
                                <th>Completed Cases</th>
                                <th>Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <td>Dr. <?php echo e($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                <td><?php echo e($row['specialization']); ?></td>
                                <td><?php echo e($row['dept_name'] ?? '-'); ?></td>
                                <td><span class="badge bg-success"><?php echo $row['completed_appointments']; ?></span></td>
                                <td><strong>$<?php echo number_format($row['total_revenue'] ?? 0, 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($reportType === 'departments'): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Department Name</th>
                                <th>Doctors</th>
                                <th>Total Appointments</th>
                                <th>Load Share (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalAll = array_sum(array_column($data, 'total_appointments'));
                            foreach ($data as $row): 
                                $share = $totalAll > 0 ? round(($row['total_appointments'] / $totalAll) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td class="fw-bold"><?php echo e($row['dept_name']); ?></td>
                                <td><?php echo $row['total_doctors']; ?></td>
                                <td><?php echo $row['total_appointments']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                            <div class="progress-bar" style="width: <?php echo $share; ?>%"></div>
                                        </div>
                                        <small><?php echo $share; ?>%</small>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .sidebar, .top-header, .d-print-none {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .wrapper {
        display: block !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .report-container {
        padding: 20px;
    }
    body {
        background: white !important;
    }
    .badge {
        border: 1px solid #000;
        color: #000 !important;
        background: transparent !important;
    }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>