<?php
require_once __DIR__ . '/../includes/session.php';

if (isLoggedIn()) {
    header('Location: ' . dashboardUrlForRole(currentRole()));
} else {
    header('Location: /public/login.php');
}
exit;
