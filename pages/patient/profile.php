<?php
/**
 * Patient - My Profile
 */
require_once __DIR__ . '/../../includes/middleware.php';
requirePatient();

$pageTitle = 'My Profile';
$activeMenu = 'profile';

$db = getDB();
$patientId = currentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("UPDATE users SET 
        first_name = ?, last_name = ?, phone = ?, date_of_birth = ?, gender = ?, 
        address = ?, city = ?, country = ?, zip_code = ? 
        WHERE user_id = ?");
    $stmt->execute([
        trim($_POST['first_name'] ?? ''),
        trim($_POST['last_name'] ?? ''),
        trim($_POST['phone'] ?? ''),
        $_POST['date_of_birth'] ?? null,
        $_POST['gender'] ?? 'other',
        trim($_POST['address'] ?? ''),
        trim($_POST['city'] ?? ''),
        trim($_POST['country'] ?? 'USA'),
        trim($_POST['zip_code'] ?? ''),
        $patientId
    ]);
    
    // Update session name
    $_SESSION['full_name'] = trim($_POST['first_name'] ?? '') . ' ' . trim($_POST['last_name'] ?? '');
    
    flashMessage('Profile updated successfully', 'success');
    redirect('/pages/patient/profile.php');
}

$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$patientId]);
$user = $stmt->fetch();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-person me-2"></i>My Profile</h2>
    <p>Update your personal information</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" data-validate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" required value="<?php echo e($user['first_name']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required value="<?php echo e($user['last_name']); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?php echo e($user['email']); ?>" disabled>
                <small class="text-muted">Email cannot be changed</small>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?php echo e($user['phone']); ?>">
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo e($user['date_of_birth']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>>Prefer not to say</option>
                        <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2"><?php echo e($user['address']); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control" value="<?php echo e($user['city']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control" value="<?php echo e($user['country']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Zip Code</label>
                    <input type="text" name="zip_code" class="form-control" value="<?php echo e($user['zip_code']); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Save Changes</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
