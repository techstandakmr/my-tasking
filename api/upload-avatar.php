<?php
header("Content-Type: application/json");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../'); // adjust path if .env is in parent folder
$dotenv->load();

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

$currentUser = $_SESSION['current_user'];
$userId = $currentUser['id'];
$cloudData = [
    'cloud' => [
        'cloud_name' => $_ENV['CLOUD_NAME'],
        'api_key'    => (int) $_ENV['CLOUD_API_KEY'],
        'api_secret' => $_ENV['CLOUD_API_SECRET'],
    ],
];
if (isset($_FILES['croppedImage']) && $_FILES['croppedImage']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['croppedImage']['tmp_name'];

    // Configure Cloudinary
    $cloudinary = new Cloudinary($cloudData);

    try {
        $result = $cloudinary->uploadApi()->upload($tmpName, [
            'folder' => 'task_manager',
            'public_id' => $userId, //use user id as public id
            'overwrite' => true,
            'resource_type' => 'image'
        ]);

        $imageUrl = $result['secure_url'];

        // Update in database
        $updateQuery = "UPDATE users SET avatar=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($stmt, 'ss', $imageUrl, $userId);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "success" => true,
                "fileURL" => $imageUrl,
                "message" => "Upload successful"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to update DB"
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Cloudinary error: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "No image or upload error"
    ]);
}
