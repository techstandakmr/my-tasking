<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Tasking</title>

  <meta name="title" content="My Tasking – Secure & Smart Task Management System" />
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
  ini_set('display_errors', 1);
  // ini_set('display_startup_errors', 1);
  // error_reporting(E_ALL);
  include './auth/auth.php';
  require_once './api/functions.php'; // Your functions
  deleteExtraOTP($conn);
  // deleteExtraOTP
  if (session_status() === PHP_SESSION_NONE) {
    session_start();  // Start the session only if not already started
  }
  $currentUser = $_SESSION['current_user'];
  $userId = $currentUser['id'];
  // ✅ Fetch all tasks for user
  $tasks = fetchUserTasks($conn, $userId);
  $totalTasks = count($tasks);
  // Group tasks by due_date with their stage
  $tasksByDate = [];

  foreach ($tasks as $task) {
    $date = $task['due_date'];
    if (!isset($tasksByDate[$date])) {
      $tasksByDate[$date] = $task['stage']; // store only one stage
    }
  }

  $closestTasks = [];
  $currentTime = time();
  $oneDayInSeconds = 24 * 60 * 60;

  foreach ($tasks as $task) {
    // Skip if task is not pending
    if (strtolower($task['stage']) !== 'pending') continue;

    // Skip if no starting_date
    if (empty($task['starting_date'])) continue;

    // $startTime = strtotime($task['starting_date']);
    $startTime = strtotime($task['starting_date'] . ' 23:59:59');
    // ✅ Only include if starting_time is not past, and is within 24 hours
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    if (
      $task['stage'] === 'pending' &&
      ($task['starting_date'] === $today || $task['starting_date'] === $tomorrow)
    ) {
      $diffInSeconds = $startTime - $currentTime;

      $task['is_today'] = $task['starting_date'] == date('Y-m-d'); // Check if task starts today
      // $task['time_diff_human'] = formatTimeDiff($diffInSeconds); // Human-friendly difference
      $task['time_diff'] = $diffInSeconds;

      $closestTasks[] = $task;
    }
  }

  // ✅ Sort by soonest start time
  usort($closestTasks, fn($a, $b) => $a['time_diff'] <=> $b['time_diff']);

  // Count upcoming
  $upcomingCount = count($closestTasks);

  $startedCount = 0;
  $completedCount = 0;
  $pendingCount = 0;
  $overdueCount = 0;
  $today = date('Y-m-d');

  foreach ($tasks as $task) {
    $dueDate = $task['due_date'];
    $stage = strtolower(trim($task['stage']));
    if ($stage === 'started') {
      $startedCount++;
    } elseif ($stage === 'completed') {
      $completedCount++;
    } else {
      if ($dueDate < $today) {
        $overdueCount++;
      } else {
        $pendingCount++;
      }
    }
  }

  ?>
  <div class="mainContainer">
    <?php include 'navbar.php'; ?>
    <div class="mainContent flex flex-col w-full h-full">
      <?php include 'topbar.php'; ?>
      <div class="widgets overflow-x-hidden">
        <div class="widget w-auto task_some_data">
          <div class="widget-header" style="border: 0px">
            <h3>Status</h3>
          </div>
          <div class="flex flex-col gap-4 justify-center px-4 pb-6">
            <!-- Completed Tasks -->
            <div class="flex flex-col gap-2 justify-center">
              <div class="flex justify-between text-sm font-medium text-gray-800 ">
                <span>Started</span>
                <span><?= $startedCount ?>/<?= $totalTasks ?></span>
              </div>
              <div class="w-full bg-blue-100 rounded h-2">
                <div class="bg-orange-500 h-2 rounded" style="width: <?= ($totalTasks > 0) ? ($startedCount / $totalTasks * 100) : 0 ?>%;"></div>
              </div>
            </div>

            <div class="flex flex-col gap-2 justify-center">
              <div class="flex justify-between text-sm font-medium text-gray-800 ">
                <span>Completed</span>
                <span><?= $completedCount ?>/<?= $totalTasks ?></span>
              </div>
              <div class="w-full bg-blue-100 rounded h-2">
                <div
                  class="bg-green-500 h-2 rounded"
                  style="width: <?= ($totalTasks > 0) ? ($completedCount / $totalTasks * 100) : 0 ?>%;"></div>
              </div>
            </div>
            <!-- Pending Tasks -->
            <div class="flex flex-col gap-2 justify-center">
              <div class="flex justify-between text-sm font-medium text-gray-800">
                <span>Pending</span>
                <span><?= $pendingCount ?>/<?= $totalTasks ?></span>
              </div>
              <div class="w-full bg-blue-100 rounded h-2">
                <div
                  class="bg-yellow-500 h-2 rounded"
                  style="width: <?= ($totalTasks > 0) ? ($pendingCount / $totalTasks * 100) : 0 ?>%;"></div>
              </div>
            </div>

            <!-- Overdue Tasks -->
            <div class="flex flex-col gap-2 justify-center">
              <div class="flex justify-between text-sm font-medium text-gray-800 ">
                <span>Overdue Tasks</span>
                <span><?= $overdueCount ?>/<?= $totalTasks ?></span>
              </div>
              <div class="w-full bg-blue-100 rounded h-2">
                <div
                  class="bg-red-500 h-2 rounded"
                  style="width: <?= ($totalTasks > 0) ? ($overdueCount / $totalTasks * 100) : 0 ?>%;"></div>
              </div>
            </div>
          </div>
        </div>
        <div class="widget w-auto task_some_data">
          <div class="widget-header" style="border: 0px">
            <h3>Upcoming tasks </h3>
          </div>
          <div class="widget-content">
            <?php if (empty($closestTasks)): ?>
              <div class="text-center p-6 rounded-xl w-full h-full">
                <p class="text-lg font-semibold text-gray-700">No urgent tasks right now!</p>
                <p class="text-sm text-gray-500 mt-2">
                  All your tasks are either far in the future or completed.<br>
                  <a href="./taskForm.php?sourcePage=index" class="text-blue-400">Add a new task</a> or check back later.
                </p>
              </div>
            <?php else: ?>
              <?php foreach (array_slice($closestTasks, 0, 5) as $task): ?>
                <div class="otherInfoCard cursor-pointer flex items-center justify-between relative">
                  <div class="task_title_status flex items-center justify-center gap-x-2">
                    <i class="fa-solid fa-clock text-xl text-gray-600"></i>
                    <h3 class="text-md text-gray-600"><?= htmlspecialchars($task['title']) ?></h3>
                  </div>
                  <div class="text-lg">
                    <div class="text-lg flex items-center justify-center gap-x-2 relative text-yellow-500">
                      <?= $task['is_today'] ? 'Today' : 'Tomorrow' ?>
                      <div class="taskActionContainer showChildOnParentHover text-gray-600">
                        <i class="fa-solid fa-ellipsis-vertical cursor-pointer text-gray-600"></i>
                        <div class="taskActionList z-[10] absolute transition-all duration-300 showOnHover origin-top-right top-7 right-3 flex gap-y-1.5 flex-col text-gray-600 py-3 px-4 rounded-lg bg-white shadow-xl/20 w-auto">
                          <a href="myTasks.php?task_id=<?= $task['id'] ?>" class="text-md flex items-center justify-between gap-x-10 cursor-pointer">
                            <span>Explore</span>
                            <i class="fa-solid fa-eye"></i>
                          </a>
                          <a href="taskForm.php?task_id=<?= $task['id'] ?>&sourcePage=index" class="text-md flex items-center justify-between gap-x-10 cursor-pointer">
                            <span>Edit</span>
                            <i class="fa-solid fa-pen"></i>
                          </a>
                          <a href="./api/deleteTask.php?task_id=<?= $task['id'] ?>&sourcePage=index" onclick="return confirm('Are you sure to delete the task!')" class="text-md flex items-center justify-between gap-x-10 cursor-pointer" data-id="<?= $task['id'] ?>">
                            <span>Delete</span>
                            <i class="fa-solid fa-trash"></i>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="widget w-auto calendar_data">
          <div class="calendar-header widget-header">
            <div
              class="widget_title_text flex items-center justify-center gap-x-1">
              <h3 id="monthYear"></h3>
            </div>
            <div class="flex items-center justify-center gap-x-3">
              <button
                class="text-sm cursor-pointer"
                id="prevMonth"
                type="button">
                <i class="fa-solid fa-chevron-left"></i>
              </button>
              <button
                class="text-sm cursor-pointer"
                id="nextMonth"
                type="button">
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>
          </div>
          <div
            id="calendarDays"
            class="day-names flex items-center justify-between px-3 mb-4 gap-y-5 gap-x-4 h-auto"></div>
        </div>
      </div>
    </div>

    <!-- profile card  -->
    <?php include 'profileCard.php'; ?>
  </div>
  <script src="./js/main.js"></script>
  <script>
    const tasksByDate = <?php echo json_encode($tasksByDate); ?>;
    document.querySelectorAll(".delete-task-btn").forEach(btn => {
      btn.addEventListener("click", async (e) => {
        e.preventDefault();
        const taskId = btn.getAttribute("data-id");

        if (!confirm("Are you sure you want to delete this task?")) return;

        try {
          const response = await fetch(`./api/deleteTask.php?task_id=${taskId}`, {
            method: "GET",
          });

          const result = await response.text();

          if (result === "success") {
            btn.closest(".otherInfoCard").style.transition = "opacity 0.3s";
            btn.closest(".otherInfoCard").style.opacity = "0";
            setTimeout(() => btn.closest(".otherInfoCard").remove(), 300);
          } else {
            alert("Failed to delete task.");
          }
        } catch (err) {
          console.error(err);
          alert("Error deleting task.");
        }
      });
    });
  </script>
  <script src="./js/calendard.js"></script>
</body>

</html>