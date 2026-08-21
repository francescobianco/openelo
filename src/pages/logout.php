<?php
/**
 * Chess School Platform - Logout
 */

require_once SRC_PATH . '/auth.php';
require_once SRC_PATH . '/utils.php';

logoutInstructor();
setFlash('success', __('auth_logged_out'));

header('Location: ?page=login');
exit;
