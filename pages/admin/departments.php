<?php
/**
 * Admin - Department Management
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireAdmin();

$pageTitle = 'Departments';
$activeMenu = 'departments';

$db = getDB();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $deptId = $_POST['dept_id'] ?? null;
        $name = trim($_POST['dept_name'] ?? '');
        $code = trim($_POST['dept_code'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($deptId) {
            $stmt = $db->prepare("UPDATE departments SET dept_name=?, dept_code=?, description=?, location=?, is_active=? WHERE dept_id=?");
            $stmt->execute([$name, $code, $desc, $location, $active, $deptId]);
            flashMessage('Department updated successfully', 'success');
        } else {
            $stmt = $db->prepare("INSERT INTO departments (dept_name, dept_code, description, location, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $code, $desc, $location, $active]);
            flashMessage('Department added successfully', 'success');
        }
        redirect('/pages/admin/departments.php');
    }
    
    if ($action === 'delete' && !empty($_POST['dept_id'])) {
        $stmt = $db->prepare("UPDATE departments SET is_active = 0 WHERE dept_id = ?");
        $stmt->execute([(int) $_POST['dept_id']]);
        flashMessage('Department deactivated', 'success');
        redirect('/pages/admin/departments.php');
    }
}

$stmt = $db->query("SELECT * FROM departments ORDER BY dept_name");
$departments = $stmt->fetchAll();

$editDept = null;
if ($action === 'edit' && !empty($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM departments WHERE dept_id = ?");
    $stmt->execute([(int) $_GET['id']]);
    $editDept = $stmt->fetch();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h2><i class="bi bi-building me-2"></i>Department Management</h2>
        <p>Manage hospital departments and specialties</p>
    </div>
    <a href="?action=add" class="btn btn-primary"><i class="bi bi-plus me-2"></i>Add Department</a>
</div>

<?php if ($action === 'add' || ($action === 'edit' && $editDept)): ?>
<div class="card">
    <div class="card-header">
        <h5><?php echo $editDept ? 'Edit Department' : 'Add Department'; ?></h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($editDept): ?><input type="hidden" name="dept_id" value="<?php echo $editDept['dept_id']; ?>"><?php endif; ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department Name *</label>
                    <input type="text" name="dept_name" class="form-control" required value="<?php echo e($editDept['dept_name'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department Code</label>
                    <input type="text" name="dept_code" class="form-control" value="<?php echo e($editDept['dept_code'] ?? ''); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo e($editDept['description'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" value="<?php echo e($editDept['location'] ?? ''); ?>">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="isActive" <?php echo (!$editDept || $editDept['is_active']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="isActive">Active</label>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $editDept ? 'Update' : 'Create'; ?></button>
            <a href="?" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body p-0">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Location</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td><?php echo e($dept['dept_code'] ?? '-'); ?></td>
                        <td><?php echo e($dept['dept_name']); ?></td>
                        <td><?php echo e($dept['location'] ?? '-'); ?></td>
                        <td><span class="badge bg-<?php echo $dept['is_active'] ? 'success' : 'secondary'; ?>"><?php echo $dept['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                        <td>
                            <a href="?action=edit&id=<?php echo $dept['dept_id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="?action=delete" class="d-inline" onsubmit="return confirm('Delete this department?');">
                                <input type="hidden" name="dept_id" value="<?php echo $dept['dept_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>