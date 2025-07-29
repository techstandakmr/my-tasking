<?php
// Include configuration and database connection
require_once __DIR__ . '/../config/config.php';

// Include authentication script to ensure user is logged in
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ .'/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
// Check if task_id is set in the GET request and is not empty
if (isset($_GET['task_id']) && !empty($_GET['task_id'])) {
    $taskId = $_GET['task_id'];

    // Get the current logged-in user's ID from session to ensure authorization
    $userId = $_SESSION['current_user']['id']; // ensure user is authorized

    // Prepare SQL statement to delete the task that matches the task_id and belongs to the logged-in user
    $stmt = mysqli_prepare($conn, "DELETE FROM tasks WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ss", $taskId, $userId);

    // Execute the deletion query
    if (mysqli_stmt_execute($stmt)) {
        // If deletion is successful, redirect to the source page
        echo "success";
        header("Location: {$_ENV['APP_URL']}{$_GET['sourcePage']}.php");
        exit();
    } else {
        // If deletion fails, show error message
        echo "Failed to delete task.";
    }
} else {
    // If task_id is missing or empty, show invalid ID message
    echo "Invalid task ID.";
}
