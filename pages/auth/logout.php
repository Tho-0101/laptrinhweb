<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';

// Logout user
logoutUser();

// Redirect to home
header('Location: ../../index.php');
exit;
?>
