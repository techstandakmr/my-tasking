<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Notification</title>

    <meta name="title" content="Notification – Secure & Smart Task Management System" />
    <meta name="description" content="My Tasking lets you manage tasks with stage tracking (started, pending, completed), secure signup via email verification, two-step verification for updates, and safe account deletion." />
    <meta name="keywords" content="My Tasking, task management, PHP task app, secure todo list, email verification, two step verification, delete account security, productivity, Abdul Kareem" />
    <meta name="author" content="Abdul Kareem" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="https://my-tasking.wuaze.com/" />

    <meta property="og:image" content="https://res.cloudinary.com/dn0hsbnpl/image/upload/v1750869151/task_manager/ovnr1x8brn4guj49j7n9.png" />
    <meta name="image" content="https://res.cloudinary.com/dn0hsbnpl/image/upload/v1750869151/task_manager/ovnr1x8brn4guj49j7n9.png" />

    <link rel="icon" type="image/png" href="https://res.cloudinary.com/dn0hsbnpl/image/upload/v1750869151/task_manager/ovnr1x8brn4guj49j7n9.png" />

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="./js/fontawesome.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="./css/style.css" />
</head>

<body>
    <?php
    // Include authentication check
    include './auth/auth.php';
    // Include helper functions
    require_once './api/functions.php';
    // Delete extra OTP records from database
    deleteExtraOTP($conn);
    // Get current logged-in user details from session
    $currentUser = $_SESSION['current_user'];
    $userId = $currentUser['id'];

    // ✅ Fetch all tasks belonging to the current user
    $tasks = fetchUserTasks($conn, $userId);

    // Arrays to store categorized tasks
    $overdue = [];
    $upcoming = [];
    $completed = [];

    // Current time and 5 days in seconds for comparisons
    $currentTime = time();
    $fiveDaysInSeconds = 5 * 24 * 60 * 60;

    // Loop through each task to categorize based on status and due date
    foreach ($tasks as $task) {
        if (empty($task['due_date'])) continue; // Skip tasks without due date

        $dueTime = strtotime($task['due_date'] . ' 23:59:59'); // End of due date day
        $stage = strtolower($task['stage']);
        $updatedAt = strtotime($task['updated_at']);
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $currentDate = strtotime(date('Y-m-d H:i:s'));
        // Calculate days crossed from starting date
        $timeDiff = $currentDate - strtotime($task['starting_date']);
        $crossedDaysFromStarting = floor($timeDiff / (60 * 60 * 24));

        if ($stage === 'completed') {
            // Task is completed
            $completed[] = $task;
        } elseif (($task['starting_date'] === $today || $task['starting_date'] === $tomorrow) && $task['stage'] == 'pending') {
            // Task scheduled for today or tomorrow and still pending
            $task['isScheduled'] =  true;
            $task['targetDay'] =  $task['starting_date'] === $today ? 'today' : 'tomorrow';
            $upcoming[] = $task;
        } elseif ($crossedDaysFromStarting >= 1 && $task['stage'] == 'pending') {
            // Task's starting date has passed but still pending
            $task['startingDateCrossed'] =  true;
            $task['crossedDaysFromStarting'] =  $crossedDaysFromStarting;
            $upcoming[] = $task;
        } elseif ($dueTime < $currentTime) {
            // Task is overdue
            $timeDiff = $currentTime - strtotime($task['due_date']);
            $task['days_overdue'] = floor($timeDiff / 86400);
            $overdue[] = $task;
        } elseif (($dueTime - $currentTime) <= $fiveDaysInSeconds) {
            // Task due within the next 5 days
            $task['days_left'] = ceil(($dueTime - $currentTime) / 86400);
            $upcoming[] = $task;
        }
    };
    // Combine all categorized tasks, maintaining order: overdue, upcoming, completed
    $allTasks = [...$overdue, ...$upcoming, ...$completed];
    ?>
    <div class="mainContainer">
        <!-- Include navigation bar -->
        <?php include 'navbar.php'; ?>
        <div class="mainContent flex flex-col w-full h-full">
            <!-- Include topbar -->
            <?php include 'topbar.php'; ?>
            <div class="widgets">
                <?php if (empty($allTasks)): ?>
                    <!-- Display message if no notifications -->
                    <div style="background-color: transparent !important;" class="widget w-auto task_some_data">
                        <div class="widget-content">
                            <div class="text-center p-6 rounded-xl w-full h-full">
                                <p class="text-lg font-semibold text-gray-700">No notification</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Display list of notifications -->
                    <div class="widget w-auto task_some_data">
                        <div class="widget-content">
                            <div class="widget-content">
                                <!-- Loop through each task in notifications -->
                                <?php foreach ($allTasks as $task): ?>
                                    <a href="myTasks.php?task_id=<?= $task['id'] ?>" class="otherInfoCard cursor-pointer flex items-center justify-between relative">
                                        <div class="task_title_status flex items-center justify-center gap-x-2">
                                            <i class="fa-solid fa-clock text-xl text-gray-600"></i>
                                            <!-- Task title, safely escaped -->
                                            <h3 class="text-md text-gray-600"><?= htmlspecialchars($task['title']) ?></h3>
                                        </div>
                                        <div class="text-sm     <?php
                                                                // Dynamic text color based on task status
                                                                $stage = strtolower($task['stage']);
                                                                if ($stage === 'completed') echo 'text-green-600';
                                                                elseif (isset($task['days_overdue'])) echo 'text-red-600';
                                                                elseif (isset($task['days_left'])) echo 'text-yellow-600';
                                                                else echo 'text-gray-500';
                                                                ?>">
                                            <?php
                                            // Display status message based on task properties
                                            if ($stage === 'completed') {
                                                echo "This task has been completed successfully.";
                                            } elseif (isset($task['isScheduled'])) {
                                                echo "<div class='text-red-500 font-semibold'>This task is scheduled for {$task['targetDay']}</div>";
                                            } elseif (isset($task['startingDateCrossed'])) {
                                                echo "<div class='text-red-500 font-semibold'>This task was supposed to start " .
                                                    ($task['crossedDaysFromStarting'] == 1 ? "yesterday" : "{$task['crossedDaysFromStarting']} days ago") .
                                                    "</div>";
                                            } elseif (isset($task['days_overdue'])) {
                                                echo "This task was due {$task['days_overdue']} day(s) ago. Please take action!";
                                            } elseif (isset($task['days_left'])) {
                                                echo "Only {$task['days_left']} day(s) left to complete this task.";
                                            }
                                            ?>
                                        </div>

                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Include profile card -->
        <?php include 'profileCard.php'; ?>
    </div>
    <!-- Main JavaScript file -->
    <script src="./js/main.js"></script>
</body>

</html>