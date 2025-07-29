<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verify Email - My Tasking – Secure & Smart Task Management System</title>
    <meta name="title" content="Verify Email - My Tasking – Secure & Smart Task Management System" />
    <meta name="description" content="My Tasking lets you manage tasks with stage tracking (started, pending, completed), secure signup via email verification, two-step verification for updates, and safe account deletion." />
    <meta name="keywords" content="My Tasking, task management, PHP task app, secure todo list, email verification, two step verification, delete account security, productivity, Abdul Kareem" />
    <meta name="author" content="Abdul Kareem" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="https://my-tasking.wuaze.com/" />

    <meta property="og:image" content="https://res.cloudinary.com/dn0hsbnpl/image/upload/v1750869151/task_manager/ovnr1x8brn4guj49j7n9.png" />
    <meta name="image" content="https://res.cloudinary.com/dn0hsbnpl/image/upload/v1750869151/task_manager/ovnr1x8brn4guj49j7n9.png" />

    <link rel="icon" type="image/png" href="https://res.cloudinary.com/dn0hsbnpl/image/upload/v1750869151/task_manager/ovnr1x8brn4guj49j7n9.png" />

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="../js/fontawesome.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="../css/style.css" />
</head>

<body>
    <?php
    // Include database configuration
    require '../config/config.php';

    // Initialize flags for success and error messages
    $success = false;
    $error = '';

    // Check if token is provided in URL query string
    if (isset($_GET['token'])) {
        $token = $_GET['token'];
        // Query the database to find unverified user with matching token
        // $result = mysqli_query($conn, "SELECT * FROM users WHERE verification_token='$token' AND is_verified = 0");
        $result = mysqli_query($conn, "SELECT *, UNIX_TIMESTAMP(updated_at) AS token_time_unix FROM users WHERE verification_token='$token' AND is_verified = 0");
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            $token_time = $user['token_time_unix']; // Already in UNIX format
            $current_time = time();

            if (($current_time - $token_time) <= 300) {
                // Mark as verified
                mysqli_query($conn, "UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token='$token'");
                $success = true;
            } else {
                $error = "Verification link expired";
            }
        } else {
            $error = "Invalid or expired token.";
        }
    } else {
        // Token parameter not provided in URL
        $error = "No token provided.";
    };
    ?>

    <?php if ($success) : ?>
        <!-- Show success message on email verification -->

        <body class="flex items-center justify-center min-h-screen bg-gray-100">
            <div class="form_container bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
                <h2 class="text-green-500 text-center">Email verified successfully</h2>
                <p class="text-center w-full"><a href='./login.php' class="text-blue-500">Login now</a></p>
            </div>
        </body>
    <?php endif; ?>

    <?php if ($error): ?>
        <!-- Show error message if verification failed -->

        <body class="flex items-center justify-center min-h-screen bg-gray-100">
            <div class="form_container bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
                <p class="text-red-500 text-center"><?= $error ?></p>
                <p class="text-gray-500 text-center">Please <a href="./signup.php" class="text-blue-500">register</a> again or request a new link.</p>
            </div>
        </body>
    <?php endif; ?>
</body>

</html>