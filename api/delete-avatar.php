<?php
require_once '../config/config.php';
require_once '../auth/auth.php';
require_once '../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Admin\AdminApi;

$currentUser = $_SESSION['current_user'];
$userId = $currentUser['id'];

// Step 1: Fetch current avatar URL from database
$query = "SELECT avatar FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 's', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
$publicId = 'task_manager/'.$userId;
$avatarUrl = $user['avatar'];

if ($avatarUrl && filter_var($avatarUrl, FILTER_VALIDATE_URL)) {
    // Step 2: Initialize Cloudinary
    $cloudinary = new Cloudinary([
        'cloud' => [
            'cloud_name' => $_ENV['CLOUD_NAME'],
            'api_key'    => $_ENV['CLOUD_API_KEY'],
            'api_secret' => $_ENV['CLOUD_API_SECRET'],
        ]
    ]);

    try {
        // Step 3: Delete from Cloudinary
        $cloudinary->uploadApi()->destroy($publicId, ['resource_type' => 'image']);
    } catch (Exception $e) {
        // Optional: Log the error or show warning
        error_log("Cloudinary delete error: " . $e->getMessage());
    }
}

// Step 4: Set avatar to NULL in DB
$updateQuery = "UPDATE users SET avatar = NULL WHERE id = ?";
$stmt = mysqli_prepare($conn, $updateQuery);
mysqli_stmt_bind_param($stmt, 's', $userId);
mysqli_stmt_execute($stmt);

// Step 5: Update session
$_SESSION['current_user']['avatar'] = null;

// Step 6: Redirect or send JSON
header("Location: {$_ENV['APP_URL']}");
exit();
