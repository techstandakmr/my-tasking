<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Tasks</title>

  <meta name="title" content="My Tasks – Secure & Smart Task Management System" />
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
  // Importing required config and function files
  require_once './config/config.php';
  require_once './auth/auth.php';
  require_once './api/functions.php'; // Include the functions file

  // Clean up extra OTP entries
  deleteExtraOTP($conn);

  // Get current user info from session
  $currentUser = $_SESSION['current_user'];
  $userId = $currentUser['id'];

  // Fetch all tasks of the current user
  $tasksData = fetchUserTasks($conn, $userId);

  // Collect task categories from all tasks
  $taskCategories = [];
  foreach ($tasksData as $task) {
    if (!empty($task['category'])) {
      $taskCategories[] = $task['category'];
    }
  };

  // Remove duplicate categories
  $taskCategories = array_values(array_unique($taskCategories));

  // Apply filtering if specific GET parameters are set
  if (isset($_GET['task_id'])) {
    $taskId = $_GET['task_id'];
    // Filter tasks by task ID
    $tasks = array_filter($tasksData, function ($task) use ($taskId) {
      return $task['id'] === $taskId;
    });
    $tasks = array_values($tasks); // Reset array keys
  } elseif (isset($_GET['due_date'])) {
    $due_date = $_GET['due_date'];
    // Filter tasks by due date
    $tasks = array_filter($tasksData, function ($task) use ($due_date) {
      return $task['due_date'] === $due_date;
    });
    $tasks = array_values($tasks); // Reset array keys
  } else {
    // No filter applied
    $tasks = $tasksData;
  };

  // Function to format remaining time in a readable way
  function formatTimeRemaining($seconds)
  {
    if ($seconds <= 60) {
      return "less than a minute";
    }

    $minutes = floor($seconds / 60);
    $hours = floor($seconds / 3600);
    $days = floor($seconds / (3600 * 24));

    if ($days >= 1) {
      return $days . ' day' . ($days > 1 ? 's' : '');
    } elseif ($hours >= 1) {
      return $hours . ' hour' . ($hours > 1 ? 's' : '');
    } else {
      return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
    }
  };
  ?>

  <div class="mainContainer">
    <!-- Navigation bar -->
    <?php include 'navbar.php'; ?>

    <div class="mainContent flex flex-col w-full h-full">
      <!-- Top bar -->
      <?php include 'topbar.php'; ?>

      <!-- Display filters if there are tasks -->
      <?php if (!empty($tasks)): ?>
        <div class="topbar flex items-center justify-between gap-x-4 w-full bg-white rounded-lg mt-3 p-3 relative">
          <!-- Search input bar -->
          <div class="flex items-center justify-center search_bar p-2.5 rounded-lg bg-gray-200">
            <i class="fa-solid fa-magnifying-glass text-gray-700 search_bar_icon mx-1.5 cursor-pointer"></i>
            <input
              class="w-full search_input outline-none border-none text-gray-700"
              type="text" id="searchInput" placeholder="Search tasks..." />
          </div>

          <!-- Filters dropdown -->
          <div class="actionButton showChildOnParentHover">
            <button id="toggleFiltersBtn" class="cursor-pointer" type="button">
              Filters
            </button>
            <div id="filterSection" class="z-[10] absolute transition-all duration-300 showOnHover origin-top-right top-16 right-3 flex gap-y-1.5 flex-col text-gray-600 py-3 px-4 rounded-lg w-fit-content bg-white shadow-xl/20">
              <!-- Filter by stage -->
              <select id="filterStage" class="bg-gray-50 p-2 rounded">
                <option value="">All Stages</option>
                <option value="started">Started</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
              </select>

              <!-- Filter by priority -->
              <select id="filterPriority" class="bg-gray-50 p-2 rounded">
                <option value="">All Priorities</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>

              <!-- Filter by category -->
              <select id="filterCategory" class="bg-gray-50 p-2 rounded">
                <option value="">All Categories</option>
                <?php foreach ($taskCategories as $category): ?>
                  <option value="<?= htmlspecialchars($category) ?>">
                    <?= ucfirst(htmlspecialchars($category)) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="widgets">
        <div class="w-full task_some_data">
          <div class="taskCardContainer">
            <!-- If no tasks available -->
            <?php if (empty($tasks)): ?>
              <div class="text-center p-6 rounded-xl w-full h-full">
                <p class="text-lg font-semibold text-gray-700">No tasks found!</p>
                <p class="text-sm text-gray-500 mt-2">You’re all caught up. <a href="./taskForm.php?sourcePage=myTasks" class="text-blue-400">Add a new task</a> to get started!</p>
              </div>
            <?php else: ?>
              <!-- Display tasks -->
              <?php foreach ($tasks as $task): ?>
                <div
                  class="taskCard bg-white rounded-xl shadow-md p-4 flex flex-col justify-between"
                  data-stage="<?= htmlspecialchars(strtolower($task['stage'])) ?>"
                  data-priority="<?= htmlspecialchars(strtolower($task['priority'])) ?>"
                  data-category="<?= htmlspecialchars(strtolower($task['category'] ?? 'other')) ?>"
                  data-due="<?= htmlspecialchars(strtolower($task['due_date'])) ?>"
                  starting-date="<?= htmlspecialchars(strtolower($task['starting_date'] ?? '')) ?>"
                  style="<?= isset($_GET['task_id']) ? 'max-width: 100%;width: 100%;' : '' ?>">

                  <!-- Task title and priority badge -->
                  <div class="flex justify-between items-cente">
                    <h3 class="text-lg font-semibold text-gray-800 <?= isset($_GET['task_id']) ? '' : 'truncate' ?>">
                      <?= htmlspecialchars($task['title']) ?>
                    </h3>
                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                        <?= $task['priority'] === 'high' ? 'bg-yellow-100 text-yellow-700'
                          : ($task['priority'] === 'medium' ? 'bg-blue-100 text-blue-700'
                            : 'bg-green-100 text-green-700') ?> capitalize">
                      <?= htmlspecialchars($task['priority']) ?>
                    </span>
                  </div>

                  <!-- Task description -->
                  <p class="text-sm text-gray-600 <?= isset($_GET['task_id']) ? '' : 'truncate' ?>">
                    <?= nl2br(htmlspecialchars($task['description'])) ?>
                  </p>

                  <!-- Task meta info -->
                  <div class="flex justify-between items-start gap-y-1 flex-col text-sm text-gray-500">
                    <div><span class="font-medium">Starting:</span> <?= htmlspecialchars($task['starting_date']) ?></div>
                    <div><span class="font-medium">Due:</span> <?= htmlspecialchars($task['due_date']) ?></div>

                    <?php
                    // Set stage color
                    $stage = strtolower($task['stage'] ?? '');
                    $stageColor = match ($stage) {
                      'started' => 'orange',
                      'pending' => 'blue',
                      'completed' => 'green',
                      default => 'gray',
                    };

                    // Various time and stage calculations
                    $isDueCrossed = strtotime($task['due_date']) < strtotime(date('Y-m-d'));
                    $stageLower = strtolower($task['stage']);
                    $updatedAt = strtotime($task['updated_at']);
                    $currentDate = strtotime(date('Y-m-d H:i:s'));
                    ?>

                    <!-- Stage and category -->
                    <div>
                      <span class="font-medium">Stage:</span>
                      <span style="color: <?= $stageColor ?>; font-weight: 600;">
                        <?= htmlspecialchars($task['stage']) ?>
                      </span>
                      <div>
                        <span class="font-medium text-gray-500">Category:</span>
                        <?= htmlspecialchars($task['category']) ?>
                      </div>

                      <!-- Show overdue message -->
                      <?php if ($isDueCrossed && $stageLower != 'completed'): ?>
                        <?php
                        $timeDiff = $currentDate - strtotime($task['due_date']);
                        $daysOverdue = floor($timeDiff / (60 * 60 * 24));
                        ?>
                        <div class='text-red-500 text-sm font-semibold mt-1'>This task was due <?= $daysOverdue ?> day<?= $daysOverdue > 1 ? 's' : '' ?> ago</div>
                      <?php endif; ?>
                    </div>

                    <?php
                    // Check if task is pending and not overdue
                    if ($stageLower != 'completed') {
                      if (!$isDueCrossed) {
                        $currentTime = time();
                        $timeDiff = $currentTime - strtotime($task['starting_date']);
                        $crossedDaysFromStarting = floor($timeDiff / (60 * 60 * 24));
                        $fiveDaysInSeconds = 5 * 24 * 60 * 60;
                        $dueTime = strtotime($task['due_date'] . ' 23:59:59');
                        $task['days_left'] = ceil(($dueTime - $currentTime) / 86400);
                        $today = date('Y-m-d');
                        $tomorrow = date('Y-m-d', strtotime('+1 day'));

                        // Notify if task is starting today/tomorrow
                        if (($task['starting_date'] === $today || $task['starting_date'] === $tomorrow)) {
                          $targetDay = $task['starting_date'] === $today ? 'today' : 'tomorrow';
                          echo "<div class='text-red-500 font-semibold'>This task is scheduled for $targetDay</div>";
                        } else if ($crossedDaysFromStarting >= 1 && $stageLower == 'pending') {
                          echo "<div class='text-red-500 font-semibold'>This task was supposed to start " .
                            ($crossedDaysFromStarting == 1 ? "yesterday" : "$crossedDaysFromStarting days ago") .
                            "</div>";
                        };

                        // Show warning if only a few days left
                        if (($dueTime - $currentTime) <= $fiveDaysInSeconds) {
                          if (isset($task['days_left'])) {
                            echo "<div class='text-red-500 font-semibold'>Only {$task['days_left']} day(s) left to complete this task.</div>";
                          };
                        };
                      };
                    };
                    ?>
                  </div>

                  <!-- Action buttons -->
                  <div class="mx-auto mt-2 flex justify-center items-center gap-x-4 actionButton">
                    <?php if (!isset($_GET['task_id'])): ?>
                      <a href="myTasks.php?task_id=<?= $task['id'] ?>" class="flex flex-col gap-y-0.5 text-md">
                        <p class="flex items-center justify-center gap-x-2">
                          <i class="fa-solid fa-eye"></i>
                        </p>
                        Explore
                      </a>
                    <?php endif; ?>
                    <a href="taskForm.php?task_id=<?= $task['id'] ?>&sourcePage=myTasks" class="flex flex-col gap-y-0.5 text-md">
                      <p class="flex items-center justify-center gap-x-2">
                        <i class="fa-solid fa-pen"></i>
                      </p>
                      Edit
                    </a>
                    <a href="./api/deleteTask.php?task_id=<?= $task['id'] ?>&sourcePage=myTasks" onclick="return confirm('Are you sure to delete the task!')" class="flex flex-col gap-y-0.5 text-md" data-id="<?= $task['id'] ?>">
                      <p class="flex items-center justify-center gap-x-2">
                        <i class="fa-solid fa-trash"></i>
                      </p>
                      Delete
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <!-- Back button -->
          <?php if (isset($_GET['task_id'])): ?>
            <div class="text-center mt-4">
              <a href="myTasks.php" class="text-blue-600 hover:underline">&larr; Back to all tasks</a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Profile card section -->
  <?php include 'profileCard.php'; ?>

  <!-- Main JavaScript -->
  <script src="./js/main.js"></script>

  <script>
    // DOM manipulation and search/filter logic
    document.addEventListener("DOMContentLoaded", () => {
      const searchInput = document.getElementById("searchInput");
      if (searchInput) {
        const filterStage = document.getElementById("filterStage");
        const filterPriority = document.getElementById("filterPriority");
        const filterCategory = document.getElementById("filterCategory");

        // Normalize string for comparison
        function normalize(str) {
          return str?.toLowerCase()?.trim();
        }

        // Filtering tasks based on search & selected filters
        function filterTasks() {
          const searchValue = normalize(searchInput.value);
          const stageValue = normalize(filterStage.value);
          const priorityValue = normalize(filterPriority.value);
          const categoryValue = normalize(filterCategory.value);

          document.querySelectorAll(".taskCard").forEach(card => {
            const title = normalize(card.querySelector("h3")?.textContent);
            const desc = normalize(card.querySelector("p")?.textContent);
            const stage = normalize(card.getAttribute("data-stage"));
            const priority = normalize(card.getAttribute("data-priority"));
            const category = normalize(card.getAttribute("data-category"));
            const startingDate = normalize(card.getAttribute("starting-date"));
            const dueDate = normalize(card.getAttribute("data-due"));

            const matchesSearch = !searchValue || [title, desc, stage, priority, category, dueDate].some(text =>
              text?.includes(searchValue)
            );

            const matchesStage = !stageValue || stage === stageValue;
            const matchesPriority = !priorityValue || priority === priorityValue;
            const matchesCategory = !categoryValue || category === categoryValue;

            // Show/hide card based on match
            if (matchesSearch && matchesStage && matchesPriority && matchesCategory) {
              card.style.display = "block";
            } else {
              card.style.display = "none";
            }
          });
        }

        // Event listeners for filtering
        searchInput.addEventListener("input", filterTasks);
        filterStage.addEventListener("change", filterTasks);
        filterPriority.addEventListener("change", filterTasks);
        filterCategory.addEventListener("change", filterTasks);
      };
    });
  </script>
</body>

</html>