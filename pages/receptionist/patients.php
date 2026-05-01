<?php
/**
 * Receptionist - Patient Management
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireReceptionist();

$pageTitle = 'Patients';
$activeMenu = 'patients';

$db = getDB();

// Quick patient registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $data = [
        'role_id' => ROLE_PATIENT,
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? 'password123',
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name' => trim($_POST['last_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
    ];
    $result = registerUser($data);
    if ($result['success']) {
        flashMessage('Patient registered successfully', 'success');
    } else {
        flashMessage($result['error'], 'error');
    }
    redirect('/pages/receptionist/patients.php');
}

// Get all patients
$search = $_GET['search'] ?? '';
$params = [];
$where = "WHERE u.role_id = 4";

if ($search) {
    $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params = array_fill(0, 4, "%$search%");
}

$query = "SELECT u.*, 
            (SELECT COUNT(*) FROM appointments WHERE patient_id = u.user_id) as total_appointments,
            (SELECT MAX(appointment_date) FROM appointments WHERE patient_id = u.user_id AND status = 'completed') as last_visit
          FROM users u $where ORDER BY u.created_at DESC";

$pagination = paginate($query, $params, 15);
$patients = $pagination['data'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex justify-content-between">
    <div>
        <h2><i class="bi bi-people me-2"></i>Patient Management</h2>
        <p>View and register patients</p>
    </div>
    <a href="?action=add" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Register Patient</a>
</div>

<?php if (($_GET['action'] ?? '') === 'add'): ?>
<div class="card">
    <div class="card-header"><h5>Quick Register Patient</h5></div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="add_patient" value="1">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Temporary Password</label>
                <input type="text" name="password" class="form-control" value="password123">
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
            <a href="?" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-header">
        <h5>All Patients</h5>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo e($search); ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead><tr><th>Name</th><th>Contact</th><th>Appointments</th><th>Last Visit</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($patients as $p): ?>
                    <tr>
                        <td><?php echo e($p['first_name'] . ' ' . $p['last_name']); ?></td>
                        <td><?php echo e($p['email']); ?><br><small><?php echo e($p['phone']); ?></small></td>
                        <td><?php echo $p['total_appointments']; ?></td>
                        <td><?php echo $p['last_visit'] ? formatDate($p['last_visit']) : '-'; ?></td>
                        <td><span class="badge bg-<?php echo $p['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($patients)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No patients found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="d-flex justify-content-center py-3">
            <nav><ul class="pagination">
                <?php if ($pagination['hasPrev']): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $pagination['page']-1; ?>&search=<?php echo e($search); ?>">Previous</a></li>
                <?php endif; ?>
                <?php for ($i=1; $i<=$pagination['totalPages']; $i++): ?>
                <li class="page-item <?php echo $i==$pagination['page']?'active':''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo e($search); ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($pagination['hasNext']): ?>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $pagination['page']+1; ?>&search=<?php echo e($search); ?>">Next</a></li>
                <?php endif; ?>
            </ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
