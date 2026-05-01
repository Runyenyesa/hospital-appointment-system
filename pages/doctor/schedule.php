<?php
/**
 * Doctor - Schedule Management
 */
require_once __DIR__ . '/../../includes/middleware.php';
requireDoctor();

$pageTitle = 'My Schedule';
$activeMenu = 'schedule';

$db = getDB();
$doctorId = currentUserId();

$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Add schedule slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $day = (int) $_POST['day_of_week'];
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $duration = (int) ($_POST['slot_duration'] ?? 30);
    
    $stmt = $db->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$doctorId, $day, $start, $end, $duration]);
    flashMessage('Schedule slot added', 'success');
    redirect('/pages/doctor/schedule.php');
}

// Delete slot
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM doctor_schedules WHERE schedule_id = ? AND doctor_id = ?");
    $stmt->execute([(int) $_GET['delete'], $doctorId]);
    flashMessage('Schedule slot removed', 'success');
    redirect('/pages/doctor/schedule.php');
}

// Get current schedule
$stmt = $db->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week, start_time");
$stmt->execute([$doctorId]);
$schedules = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-clock me-2"></i>My Schedule</h2>
    <p>Manage your weekly availability</p>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5>Add Time Slot</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_slot" value="1">
                    <div class="mb-3">
                        <label class="form-label">Day of Week</label>
                        <select name="day_of_week" class="form-select" required>
                            <?php foreach ($days as $idx => $day): ?>
                            <option value="<?php echo $idx; ?>"><?php echo $day; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slot Duration (minutes)</label>
                        <select name="slot_duration" class="form-select">
                            <option value="15">15 min</option>
                            <option value="30" selected>30 min</option>
                            <option value="45">45 min</option>
                            <option value="60">60 min</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Slot</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5>Current Weekly Schedule</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Day</th><th>Start</th><th>End</th><th>Duration</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?php echo $days[$s['day_of_week']]; ?></td>
                                <td><?php echo formatTime($s['start_time']); ?></td>
                                <td><?php echo formatTime($s['end_time']); ?></td>
                                <td><?php echo $s['slot_duration']; ?> min</td>
                                <td>
                                    <a href="?delete=<?php echo $s['schedule_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this slot?');"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($schedules)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No schedule slots set</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>