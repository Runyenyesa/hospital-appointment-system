<?php
/**
 * Patient - Book Appointment
 */
require_once __DIR__ . '/../../includes/middleware.php';
requirePatient();

$pageTitle = 'Book Appointment';
$activeMenu = 'book';

$db = getDB();
$patientId = currentUserId();

// Get all active departments
$stmt = $db->query("SELECT * FROM departments WHERE is_active = 1 ORDER BY dept_name");
$departments = $stmt->fetchAll();

// Get all active doctors with departments
$stmt = $db->query("SELECT u.user_id, u.first_name, u.last_name, u.specialization, u.consultation_fee,
                           COALESCE(u.dept_id, 0) as dept_id, d.dept_name
                    FROM users u
                    LEFT JOIN departments d ON u.dept_id = d.dept_id
                    WHERE u.role_id = 2 AND u.is_active = 1
                    ORDER BY u.first_name");
$doctors = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = !empty($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : null;
    $deptId = !empty($_POST['dept_id']) ? (int) $_POST['dept_id'] : null;
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['start_time'] ?? '';
    $type = $_POST['appointment_type'] ?? 'regular';
    $reason = trim($_POST['reason'] ?? '');
    $symptoms = trim($_POST['symptoms'] ?? '');
    
    // Validation
    if (empty($date) || empty($time)) {
        $error = 'Please select both date and time';
    } elseif (empty($reason)) {
        $error = 'Please provide a reason for your visit';
    } elseif (empty($doctorId) && empty($deptId)) {
        $error = 'Please select a doctor or department';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'Cannot book appointments in the past';
    } else {
        // Check if slot is available
        $checkSlot = true;
        if ($doctorId) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM appointments 
                                  WHERE doctor_id = ? AND appointment_date = ? AND start_time = ? AND status IN ('pending', 'approved')");
            $stmt->execute([$doctorId, $date, $time]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'This time slot is already booked for the selected doctor. Please choose another time.';
                $checkSlot = false;
            }
        }
        
        if ($checkSlot) {
            $stmt = $db->prepare("INSERT INTO appointments 
                (patient_id, doctor_id, dept_id, appointment_date, start_time, appointment_type, reason, symptoms, requested_by, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$patientId, $doctorId, $deptId, $date, $time, $type, $reason, $symptoms, $patientId]);
            
            $appointmentId = $db->lastInsertId();
            
            // Notify patient
            sendNotification($patientId, 'system', 'Appointment Requested',
                'Your appointment request for ' . formatDate($date) . ' at ' . formatTime($time) . ' has been submitted and is pending approval.',
                $appointmentId, 'appointment');
            
            $success = 'Appointment requested successfully! You will be notified once it is approved.';
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-calendar-plus me-2"></i>Book an Appointment</h2>
    <p>Schedule your visit with our healthcare professionals</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success"><?php echo e($success); ?> <a href="<?php echo APP_URL; ?>/pages/patient/appointments.php">View My Appointments</a></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" data-validate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select Department <span class="text-muted">(optional if doctor selected)</span></label>
                    <select name="dept_id" class="form-select" id="deptSelect">
                        <option value="">-- Choose Department --</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['dept_id']; ?>"><?php echo e($dept['dept_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Select Doctor <span class="text-muted">(optional if department selected)</span></label>
                    <select name="doctor_id" class="form-select" id="doctorSelect">
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?php echo $doc['user_id']; ?>" data-dept="<?php echo $doc['dept_id']; ?>">
                            Dr. <?php echo e($doc['first_name'] . ' ' . $doc['last_name']); ?> 
                            (<?php echo e($doc['specialization']); ?>)
                            <?php if ($doc['consultation_fee']): ?> - $<?php echo $doc['consultation_fee']; ?><?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Preferred Date *</label>
                    <input type="date" name="appointment_date" class="form-control" required data-min-today 
                           value="<?php echo e($_POST['appointment_date'] ?? ''); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Preferred Time *</label>
                    <select name="start_time" class="form-select" required>
                        <option value="">-- Select Time --</option>
                        <?php 
                        $times = ['09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30',
                                  '14:00','14:30','15:00','15:30','16:00','16:30','17:00'];
                        foreach ($times as $t):
                        ?>
                        <option value="<?php echo $t; ?>:00" <?php echo ($_POST['start_time'] ?? '') === $t . ':00' ? 'selected' : ''; ?>>
                            <?php echo date('h:i A', strtotime($t)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Appointment Type</label>
                <select name="appointment_type" class="form-select">
                    <option value="regular" <?php echo ($_POST['appointment_type'] ?? '') === 'regular' ? 'selected' : ''; ?>>Regular Checkup</option>
                    <option value="follow_up" <?php echo ($_POST['appointment_type'] ?? '') === 'follow_up' ? 'selected' : ''; ?>>Follow-up Visit</option>
                    <option value="emergency" <?php echo ($_POST['appointment_type'] ?? '') === 'emergency' ? 'selected' : ''; ?>>Urgent / Emergency</option>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Reason for Visit *</label>
                <textarea name="reason" class="form-control" rows="2" required placeholder="Briefly describe why you need to see the doctor..."><?php echo e($_POST['reason'] ?? ''); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Symptoms <span class="text-muted">(optional)</span></label>
                <textarea name="symptoms" class="form-control" rows="2" placeholder="Describe any symptoms you're experiencing..."><?php echo e($_POST['symptoms'] ?? ''); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-calendar-plus me-2"></i>Request Appointment</button>
                <a href="<?php echo APP_URL; ?>/pages/patient/dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('deptSelect');
    const doctorSelect = document.getElementById('doctorSelect');
    const doctorOptions = Array.from(doctorSelect.querySelectorAll('option:not([value=""])'));

    function filterDoctors() {
        const deptId = deptSelect.value;
        let count = 0;
        
        doctorOptions.forEach(opt => {
            const docDeptId = opt.getAttribute('data-dept');
            if (!deptId || docDeptId === deptId || docDeptId === '0') {
                opt.style.display = '';
                count++;
            } else {
                opt.style.display = 'none';
            }
        });

        // If current selection is hidden, reset it
        if (doctorSelect.value !== '') {
            const selectedOpt = doctorSelect.options[doctorSelect.selectedIndex];
            if (selectedOpt.style.display === 'none') {
                doctorSelect.value = '';
            }
        }
    }

    function filterDepartments() {
        const doctorId = doctorSelect.value;
        if (!doctorId) return;

        const selectedDocOpt = doctorSelect.options[doctorSelect.selectedIndex];
        const docDeptId = selectedDocOpt.getAttribute('data-dept');

        if (docDeptId && docDeptId !== '0') {
            deptSelect.value = docDeptId;
        }
    }

    deptSelect.addEventListener('change', filterDoctors);
    
    doctorSelect.addEventListener('change', function() {
        if (this.value !== '') {
            filterDepartments();
        }
    });

    // Initial run in case of back navigation or validation errors
    if (deptSelect.value) filterDoctors();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>