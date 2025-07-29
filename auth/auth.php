<?php
require_once __DIR__ . '/../config/config.php';  // Includes the config file where $conn is defined
require_once __DIR__ .'/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
session_start();  // Start the session to access or store user data
// Function to check if the user is logged in using a token stored in a cookie
// If the token is not set in cookies, redirect to login page
if (!isset($_COOKIE['task_manager_user_token'])) {
    header("Location: {$_ENV['APP_URL']}auth/login.php");
    exit();
}

// Retrieve token from cookie
$token = $_COOKIE['task_manager_user_token'];

// Prepare a SQL statement to fetch user info based on token
$stmt = mysqli_prepare($conn, "SELECT id, name, email, avatar, title, description FROM users WHERE token = ?");
mysqli_stmt_bind_param($stmt, "s", $token);  // Bind the token as a string
mysqli_stmt_execute($stmt);  // Execute the prepared statement
$result = mysqli_stmt_get_result($stmt);  // Get result set
$user = mysqli_fetch_assoc($result);  // Fetch user data as associative array

// If no user is found, invalidate the cookie and redirect to login
if (!$user) {
    setcookie('task_manager_user_token', '', time() - 3600, "/");  // Expire the cookie
    header("Location: {$_ENV['APP_URL']}auth/login.php");
    exit();
}

// Store the authenticated user's info in the session
$_SESSION['current_user'] = $user;
