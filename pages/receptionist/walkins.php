<?php
/**
 * Receptionist - Walk-in Patients
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireReceptionist();

$pageTitle = 'Walk-in Patients';
$activeMenu = 'walkins';

$db = getDB();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if patient exists by email
    $email = trim($_POST['email'] ?? '');
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingPatient = $stmt->fetch();
    
    if (!$existingPatient) {
        // Register new patient quickly
        $patientData = [
            'role_id' => ROLE_PATIENT,
            'email' => $email,
            'password' => 'password123', // temporary, should be changed
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
        ];
        $result = registerUser($patientData);
        if (!$result['success']) {
            $error = $result['error'];
        } else {
            $patientId = $result['user_id'];
        }
    } else {
        $patientId = $existingPatient['user_id'];
    }
    
    if (empty($error) && isset($patientId)) {
        // Create walk-in appointment
        $doctorId = !empty($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : null;
        $deptId = !empty($_POST['dept_id']) ? (int) $_POST['dept_id'] : null;
        $reason = trim($_POST['reason'] ?? '');
        $symptoms = trim($_POST['symptoms'] ?? '');
        
        $stmt = $db->prepare("INSERT INTO appointments 
            (patient_id, doctor_id, dept_id, appointment_date, start_time, appointment_type, reason, symptoms, requested_by, status) 
            VALUES (?, ?, ?, CURDATE(), CURTIME(), 'walk_in', ?, ?, ?, 'approved')");
        $stmt->execute([$patientId, $doctorId, $deptId, $reason, $symptoms, currentUserId()]);
        
        sendNotification($patientId, 'appointment_approved', 'Walk-in Registered',
            'Your walk-in appointment has been registered. Please wait for your turn.', $db->lastInsertId(), 'appointment');
        
        $success = 'Walk-in patient registered and appointment created!';
    }
}

// Get doctors
$stmt = $db->query("SELECT user_id, first_name, last_name, specialization FROM users WHERE role_id = 2 AND is_active = 1 ORDER BY first_name");
$doctors = $stmt->fetchAll();

// Get departments
$stmt = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY dept_name");
$departments = $stmt->fetchAll();

// Recent walk-ins today
$stmt = $db->query("SELECT a.*, p.first_name as patient_first, p.last_name as patient_last, p.phone, p.email,
                            d.first_name as doctor_first, d.last_name as doctor_last
                     FROM appointments a
                     JOIN users p ON a.patient_id = p.user_id
                     LEFT JOIN users d ON a.doctor_id = d.user_id
                     WHERE a.appointment_type = 'walk_in' AND DATE(a.created_at) = CURDATE()
                     ORDER BY a.created_at DESC");
$walkinsToday = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-person-walking me-2"></i>Walk-in Patients</h2>
    <p>Register walk-in patients and create appointments instantly</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo e($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-person-plus me-2"></i>Register Walk-in</h5>
            </div>
            <div class="card-body">
                <form method="POST" data-validate>
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
                        <input type="email" name="email" class="form-control" required placeholder="Used to check existing patient">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Assign Doctor</label>
                            <select name="doctor_id" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach ($doctors as $doc): ?>
                                <option value="<?php echo $doc['user_id']; ?>">Dr. <?php echo e($doc['first_name'] . ' ' . $doc['last_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <select name="dept_id" class="form-select">
                                <option value="">-- Select --</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['dept_id']; ?>"><?php echo e($dept['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Visit *</label>
                        <textarea name="reason" class="form-control" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Symptoms</label>
                        <textarea name="symptoms" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-2"></i>Register & Approve
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-list-check me-2"></i>Today's Walk-ins</h5>
                <span class="badge bg-primary"><?php echo date('M d, Y'); ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Reason</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($walkinsToday as $w): ?>
                            <tr>
                                <td><?php echo formatTime($w['start_time']); ?></td>
                                <td><?php echo e($w['patient_first'] . ' ' . $w['patient_last']); ?><br><small><?php echo e($w['phone']); ?></small></td>
                                <td><?php echo $w['doctor_first'] ? 'Dr. ' . e($w['doctor_first'] . ' ' . $w['doctor_last']) : '<span class="text-warning">Not assigned</span>'; ?></td>
                                <td><?php echo e($w['reason']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($walkinsToday)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No walk-ins today</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>