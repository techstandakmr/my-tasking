<?php
// logout.php

// Start session
session_start();

// Destroy all session variables
session_unset();
session_destroy();
require_once __DIR__ .'/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
// Remove the login cookie if exists
if (isset($_COOKIE['task_manager_user_token'])) {
// Clear cookie
setcookie('task_manager_user_token', '', time() - 3600, "/", "", true, true);  // Expire cookie
}

// Redirect to the login page
header("Location: {$_ENV['APP_URL']}auth/login.php");
exit();
?>