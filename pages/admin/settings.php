<?php
/**
 * Admin - System Settings
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireAdmin();

$pageTitle = 'System Settings';
$activeMenu = 'settings';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    flashMessage('Settings saved successfully', 'success');
    redirect('/pages/admin/settings.php');
}

$stmt = $db->query("SELECT * FROM system_settings ORDER BY setting_key");
$settings = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-gear me-2"></i>System Settings</h2>
    <p>Configure hospital and system parameters</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <?php foreach ($settings as $setting): ?>
            <div class="mb-3">
                <label class="form-label">
                    <?php echo e(str_replace('_', ' ', ucfirst($setting['setting_key']))); ?>
                    <small class="text-muted d-block"><?php echo e($setting['description']); ?></small>
                </label>
                
                <?php if ($setting['setting_type'] === 'boolean'): ?>
                <select name="settings[<?php echo e($setting['setting_key']); ?>]" class="form-select">
                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Yes</option>
                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>No</option>
                </select>
                <?php else: ?>
                <input type="text" name="settings[<?php echo e($setting['setting_key']); ?>]" class="form-control" 
                       value="<?php echo e($setting['setting_value']); ?>">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Save Settings</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
