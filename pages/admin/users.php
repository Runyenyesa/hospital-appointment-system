<?php
/**
 * Admin - User Management
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireAdmin();

$pageTitle = 'User Management';
$activeMenu = 'users';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $userId = $_POST['user_id'] ?? null;
        $data = [
            'role_id' => (int) ($_POST['role_id'] ?? ROLE_PATIENT),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'dept_id' => !empty($_POST['dept_id']) ? (int) $_POST['dept_id'] : null,
            'specialization' => trim($_POST['specialization'] ?? ''),
            'license_number' => trim($_POST['license_number'] ?? ''),
            'qualification' => trim($_POST['qualification'] ?? ''),
            'experience_years' => !empty($_POST['experience_years']) ? (int) $_POST['experience_years'] : null,
            'consultation_fee' => !empty($_POST['consultation_fee']) ? (float) $_POST['consultation_fee'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if ($userId) {
            // Update existing user
            if (empty($data['password'])) {
                // Don't update password if not provided
                $stmt = $db->prepare("UPDATE users SET role_id=?, email=?, first_name=?, last_name=?, phone=?, 
                                      dept_id=?, specialization=?, license_number=?, qualification=?, 
                                      experience_years=?, consultation_fee=?, is_active=? WHERE user_id=?");
                $stmt->execute([
                    $data['role_id'], $data['email'], $data['first_name'], $data['last_name'], $data['phone'],
                    $data['dept_id'], $data['specialization'], $data['license_number'], $data['qualification'],
                    $data['experience_years'], $data['consultation_fee'], $data['is_active'], $userId
                ]);
            } else {
                $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                $stmt = $db->prepare("UPDATE users SET role_id=?, email=?, password_hash=?, first_name=?, last_name=?, phone=?, 
                                      dept_id=?, specialization=?, license_number=?, qualification=?, 
                                      experience_years=?, consultation_fee=?, is_active=? WHERE user_id=?");
                $stmt->execute([
                    $data['role_id'], $data['email'], $hash, $data['first_name'], $data['last_name'], $data['phone'],
                    $data['dept_id'], $data['specialization'], $data['license_number'], $data['qualification'],
                    $data['experience_years'], $data['consultation_fee'], $data['is_active'], $userId
                ]);
            }
            logActivity('user_updated', 'user', $userId);
            flashMessage('User updated successfully', 'success');
        } else {
            // Create new user
            $result = registerUser($data);
            if ($result['success']) {
                logActivity('user_created_by_admin', 'user', $result['user_id']);
                flashMessage('User created successfully', 'success');
            } else {
                flashMessage($result['error'], 'error');
            }
        }
        redirect('/pages/admin/users.php');
    }
    
    if ($action === 'delete' && !empty($_POST['user_id'])) {
        $userId = (int) $_POST['user_id'];
        // Prevent self-deletion
        if ($userId === currentUserId()) {
            flashMessage('You cannot delete your own account', 'error');
        } else {
            $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
            logActivity('user_deactivated', 'user', $userId);
            flashMessage('User deactivated successfully', 'success');
        }
        redirect('/pages/admin/users.php');
    }
}

// Get departments for dropdown
$stmt = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY dept_name");
$departments = $stmt->fetchAll();

// Get roles
$stmt = $db->query("SELECT * FROM roles WHERE is_active = 1");
$roles = $stmt->fetchAll();

// Get users list with pagination
$roleFilter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

$params = [];
$where = "WHERE 1=1";

if ($roleFilter) {
    $where .= " AND u.role_id = ?";
    $params[] = $roleFilter;
}

if ($search) {
    $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query = "SELECT u.*, r.role_name, r.role_slug, d.dept_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.role_id 
          LEFT JOIN departments d ON u.dept_id = d.dept_id 
          $where 
          ORDER BY u.created_at DESC";

$pagination = paginate($query, $params, 15);
$users = $pagination['data'];

// Edit user data
$editUser = null;
if ($action === 'edit' && !empty($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([(int) $_GET['id']]);
    $editUser = $stmt->fetch();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-people me-2"></i>User Management</h2>
        <p>Manage all system users</p>
    </div>
    <a href="?action=add" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Add User
    </a>
</div>

<?php if ($action === 'add' || ($action === 'edit' && $editUser)): ?>
<!-- Add/Edit Form -->
<div class="card">
    <div class="card-header">
        <h5><?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?></h5>
        <a href="?" class="btn btn-sm btn-secondary">Cancel</a>
    </div>
    <div class="card-body">
        <form method="POST" action="?action=<?php echo $action; ?>" data-validate>
            <?php if ($editUser): ?>
                <input type="hidden" name="user_id" value="<?php echo $editUser['user_id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role *</label>
                    <select name="role_id" class="form-select" required>
                        <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['role_id']; ?>" 
                                <?php echo ($editUser && $editUser['role_id'] == $role['role_id']) || (!$editUser && $role['role_id'] == ROLE_PATIENT) ? 'selected' : ''; ?>>
                            <?php echo e($role['role_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?php echo e($editUser['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" required 
                           value="<?php echo e($editUser['first_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required 
                           value="<?php echo e($editUser['last_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo e($editUser['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <?php echo $editUser ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" name="password" class="form-control" <?php echo $editUser ? '' : 'required'; ?>>
                </div>
            </div>
            
            <!-- Doctor-specific fields -->
            <div id="doctorFields" style="display: <?php echo ($editUser && $editUser['role_id'] == ROLE_DOCTOR) ? 'block' : 'none'; ?>;">
                <hr>
                <h6 class="mb-3">Doctor Details</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Department</label>
                        <select name="dept_id" class="form-select">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['dept_id']; ?>" 
                                    <?php echo ($editUser && $editUser['dept_id'] == $dept['dept_id']) ? 'selected' : ''; ?>>
                                <?php echo e($dept['dept_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-control" 
                               value="<?php echo e($editUser['specialization'] ?? ''); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">License Number</label>
                        <input type="text" name="license_number" class="form-control" 
                               value="<?php echo e($editUser['license_number'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Experience (Years)</label>
                        <input type="number" name="experience_years" class="form-control" 
                               value="<?php echo e($editUser['experience_years'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Consultation Fee ($)</label>
                        <input type="number" step="0.01" name="consultation_fee" class="form-control" 
                               value="<?php echo e($editUser['consultation_fee'] ?? ''); ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Qualification</label>
                    <input type="text" name="qualification" class="form-control" 
                           value="<?php echo e($editUser['qualification'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" 
                       <?php echo (!$editUser || $editUser['is_active']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="isActive">Active Account</label>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-2"></i><?php echo $editUser ? 'Update' : 'Create'; ?> User
                </button>
                <a href="?" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('select[name="role_id"]').addEventListener('change', function() {
    document.getElementById('doctorFields').style.display = this.value == '<?php echo ROLE_DOCTOR; ?>' ? 'block' : 'none';
});
</script>

<?php else: ?>
<!-- Users List -->
<div class="card">
    <div class="card-header">
        <h5>All Users</h5>
        <form method="GET" class="d-flex gap-2" style="max-width: 400px;">
            <input type="hidden" name="action" value="list">
            <select name="role" class="form-select form-select-sm" style="width: auto;">
                <option value="">All Roles</option>
                <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['role_id']; ?>" <?php echo $roleFilter == $role['role_id'] ? 'selected' : ''; ?>>
                    <?php echo e($role['role_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo e($search); ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-2" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                </div>
                                <span><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo e($user['email']); ?></td>
                        <td><span class="badge bg-light text-dark"><?php echo e($user['role_name']); ?></span></td>
                        <td><?php echo e($user['dept_name'] ?? '-'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo timeAgo($user['created_at']); ?></td>
                        <td>
                            <a href="?action=edit&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-info me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="?action=delete" class="d-inline" onsubmit="return confirm('Deactivate this user?');">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No users found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="d-flex justify-content-center py-3">
            <nav>
                <ul class="pagination">
                    <?php if ($pagination['hasPrev']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $pagination['page'] - 1; ?>&role=<?php echo e($roleFilter); ?>&search=<?php echo e($search); ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <li class="page-item <?php echo $i == $pagination['page'] ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo e($roleFilter); ?>&search=<?php echo e($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if ($pagination['hasNext']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $pagination['page'] + 1; ?>&role=<?php echo e($roleFilter); ?>&search=<?php echo e($search); ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>