<?php
/**
 * Header Template
 * Included at the top of every protected page
 */

if (!isset($pageTitle)) $pageTitle = APP_NAME;
if (!isset($activeMenu)) $activeMenu = '';

$currentUser = currentUser();
$unreadCount = unreadNotificationCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> | <?php echo e(APP_NAME); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <?php if (isset($extraCSS)): ?>
        <?php echo $extraCSS; ?>
    <?php endif; ?>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-hospital fs-3 mb-2 d-block"></i>
                <h4><?php echo e(APP_NAME); ?></h4>
                <small>Management System</small>
            </div>
            
            <ul class="nav-menu">
                <?php 
                // Define menu items based on role
                $menuItems = [];
                
                if (hasRole('admin')) {
                    $menuItems = [
                        ['dashboard', 'Dashboard', 'bi-speedometer2', '/pages/admin/dashboard.php'],
                        ['users', 'Users', 'bi-people', '/pages/admin/users.php'],
                        ['departments', 'Departments', 'bi-building', '/pages/admin/departments.php'],
                        ['appointments', 'Appointments', 'bi-calendar-check', '/pages/admin/appointments.php'],
                        ['reports', 'Reports', 'bi-graph-up', '/pages/admin/reports.php'],
                        ['settings', 'Settings', 'bi-gear', '/pages/admin/settings.php'],
                    ];
                } elseif (hasRole('doctor')) {
                    $menuItems = [
                        ['dashboard', 'Dashboard', 'bi-speedometer2', '/pages/doctor/dashboard.php'],
                        ['appointments', 'My Appointments', 'bi-calendar-check', '/pages/doctor/appointments.php'],
                        ['schedule', 'Schedule', 'bi-clock', '/pages/doctor/schedule.php'],
                        ['patients', 'Patients', 'bi-people', '/pages/doctor/patients.php'],
                        ['records', 'Medical Records', 'bi-file-medical', '/pages/doctor/records.php'],
                    ];
                } elseif (hasRole('receptionist')) {
                    $menuItems = [
                        ['dashboard', 'Dashboard', 'bi-speedometer2', '/pages/receptionist/dashboard.php'],
                        ['appointments', 'Appointments', 'bi-calendar-check', '/pages/receptionist/appointments.php'],
                        ['walkins', 'Walk-ins', 'bi-person-walking', '/pages/receptionist/walkins.php'],
                        ['patients', 'Patients', 'bi-people', '/pages/receptionist/patients.php'],
                        ['doctors', 'Doctors', 'bi-heart-pulse', '/pages/receptionist/doctors.php'],
                    ];
                } elseif (hasRole('patient')) {
                    $menuItems = [
                        ['dashboard', 'Dashboard', 'bi-speedometer2', '/pages/patient/dashboard.php'],
                        ['book', 'Book Appointment', 'bi-calendar-plus', '/pages/patient/book.php'],
                        ['appointments', 'My Appointments', 'bi-calendar-check', '/pages/patient/appointments.php'],
                        ['history', 'Medical History', 'bi-file-medical', '/pages/patient/history.php'],
                        ['profile', 'My Profile', 'bi-person', '/pages/patient/profile.php'],
                    ];
                }
                
                foreach ($menuItems as $item): 
                ?>
                    <li>
                        <a href="<?php echo APP_URL . $item[3]; ?>" 
                           class="<?php echo $activeMenu === $item[0] ? 'active' : ''; ?>">
                            <i class="bi <?php echo $item[2]; ?>"></i>
                            <span><?php echo e($item[1]); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
                
                <li class="mt-3">
                    <a href="<?php echo APP_URL; ?>/pages/auth/logout.php">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <small>v<?php echo e(APP_VERSION); ?></small>
            </div>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-custom mb-0">
                            <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo e($pageTitle); ?></li>
                        </ol>
                    </nav>
                </div>
                
                <div class="header-right">
                    <div class="dropdown">
                        <button class="notification-btn" data-bs-toggle="dropdown">
                            <i class="bi bi-bell"></i>
                            <?php if ($unreadCount > 0): ?>
                                <span class="notification-badge"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" style="width: 320px; max-height: 400px; overflow-y: auto;">
                            <h6 class="dropdown-header">Notifications</h6>
                            <?php
                            $db = getDB();
                            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                            $stmt->execute([currentUserId()]);
                            $notifications = $stmt->fetchAll();
                            
                            if (empty($notifications)):
                            ?>
                                <div class="dropdown-item text-center py-3 text-muted">
                                    <small>No notifications</small>
                                </div>
                            <?php else:
                                foreach ($notifications as $notif):
                            ?>
                                <a class="dropdown-item py-2" href="#">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-1" style="min-width: 0;">
                                            <p class="mb-1 fw-medium" style="font-size: 0.85rem;"><?php echo e($notif['title']); ?></p>
                                            <p class="mb-0 text-muted text-truncate" style="font-size: 0.8rem;"><?php echo e($notif['message']); ?></p>
                                            <small class="text-muted"><?php echo timeAgo($notif['created_at']); ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php 
                                endforeach;
                            endif;
                            ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-center text-primary" href="<?php echo APP_URL; ?>/pages/<?php echo e($_SESSION['role_slug']); ?>/notifications.php">
                                View All
                            </a>
                        </div>
                    </div>
                    
                    <div class="dropdown">
                        <div class="user-dropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div class="user-info">
                                <div class="user-name"><?php echo e($_SESSION['full_name'] ?? 'User'); ?></div>
                                <div class="user-role"><?php echo e($_SESSION['role_name'] ?? ''); ?></div>
                            </div>
                            <i class="bi bi-chevron-down" style="font-size: 0.75rem; color: #94a3b8;"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/pages/<?php echo e($_SESSION['role_slug']); ?>/profile.php">
                                <i class="bi bi-person me-2"></i>Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/pages/<?php echo e($_SESSION['role_slug']); ?>/settings.php">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/pages/auth/logout.php">
                                <i class="bi bi-box-arrow-left me-2"></i>Logout
                            </a></li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <!-- Mobile Overlay -->
            <div class="mobile-overlay" id="mobileOverlay"></div>
            
            <!-- Content Area -->
            <main class="content-area animate-fade-in">
                <?php showFlash(); ?>
