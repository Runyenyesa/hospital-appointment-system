<?php
/**
 * Logout Handler
 */
require_once __DIR__ . '/../../includes/auth.php';

logoutUser();
flashMessage('You have been logged out successfully', 'info');
redirect('/pages/auth/login.php');
