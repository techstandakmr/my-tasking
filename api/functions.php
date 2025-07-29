<?php
// Function to fetch all tasks for a specific user, ordered by last updated time (descending)
function fetchUserTasks($conn, $userId)
{
    // Prepare SQL query to get tasks by user ID
    $stmt = mysqli_prepare($conn, "SELECT * FROM tasks WHERE user_id = ? ORDER BY updated_at DESC");
    mysqli_stmt_bind_param($stmt, "s", $userId); // 's' denotes string type

    // Execute query
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Fetch tasks into an array
    $tasks = [];
    while ($task = mysqli_fetch_assoc($result)) {
        $tasks[] = $task;
    }

    // Return array of tasks
    return $tasks;
};

// Function to delete expired OTP entries from the database
function deleteExtraOTP($conn) {
    // Calculate cutoff time (5 minutes ago)
    $now = time()-300;

    // Prepare delete query: delete OTPs older than 5 minutes
    $deleteStmt = mysqli_prepare($conn, "DELETE FROM otp WHERE UNIX_TIMESTAMP(updated_at) < ?");
    mysqli_stmt_bind_param($deleteStmt, "s", $now);
    mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);
};

// Function to delete user accounts that haven't been verified within 3 days of creation
function deleteUnverifiedAccounts($conn) {
  // Calculate the Unix timestamp for 3 days ago
    $cutoff = time() - (3 * 24 * 60 * 60); // 3 days in seconds

    $is_verified = 0;

    // Prepare delete query using UNIX_TIMESTAMP(created_at)
    $deleteStmt = mysqli_prepare($conn, "
        DELETE FROM users 
        WHERE is_verified = ? AND UNIX_TIMESTAMP(created_at) < ?
    ");
    mysqli_stmt_bind_param($deleteStmt, "is", $is_verified, $cutoff);
    mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);
};
