<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Task form</title>

  <meta name="title" content="Task form – Secure & Smart Task Management System" />
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
  require_once './config/config.php'; // Include configuration file for database connection, etc.
  require_once './auth/auth.php'; // Include authentication helper functions
  require __DIR__ . '/vendor/autoload.php'; // Include Composer autoload for PHPMailer and dependencies
  use Dotenv\Dotenv;

  $dotenv = Dotenv::createImmutable(__DIR__ . '/'); // adjust path if .env is in parent folder
  $dotenv->load();
  $currentUser = $_SESSION['current_user']; // Get current logged-in user data from session
  $userId = $currentUser['id']; // Extract current user's ID for queries
  $editMode = false; // Flag to determine if the form is in edit mode
  $task = null; // Initialize variable to hold task data if editing

  // Check if a task ID is provided in the URL to load task for editing
  if (isset($_GET['task_id'])) {
    $editMode = true; // Enable edit mode
    $taskId = $_GET['task_id']; // Get task ID from query string

    // Prepare SQL statement to fetch task matching the given ID and user ID (security check)
    $stmt = mysqli_prepare($conn, "SELECT * FROM tasks WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ss", $taskId, $userId); // Bind parameters (task ID and user ID)
    mysqli_stmt_execute($stmt); // Execute the prepared statement
    $result = mysqli_stmt_get_result($stmt); // Get result set

    // Check if a matching task was found
    if ($result && mysqli_num_rows($result) > 0) {
      $task = mysqli_fetch_assoc($result); // Fetch task data as associative array
    } else {
      die("Task not found or unauthorized."); // Stop execution if task doesn't exist or belongs to another user
    }
  };

  $taregtDate = ""; // Initialize variable for pre-filling due date field
  if (isset($_GET['due_date'])) {
    $taregtDate = $_GET['due_date']; // Set due date from query parameter if provided
  };

  // Function to generate a unique custom user ID combining a timestamp and random string
  function generateTaskId()
  {
    $timestamp = number_format(microtime(true) * 1000, 0, '', ''); // returns a string without decimals or commas
    return 'TASK_' . $timestamp;
  };

  // Handle form submission (POST request)
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim and collect all input values from the submitted form
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));
    $stage = trim($_POST['stage']);
    $priority = trim($_POST['priority']);
    $category = htmlspecialchars(trim($_POST['category']));
    $starting_date = trim($_POST['starting_date']);

    // Create DateTime object and format starting date to 'Y-m-d'
    $startingDateObj = date_create($starting_date);
    $starting_date_formatted = $startingDateObj->format('Y-m-d');

    $due_date = trim($_POST['due_date']);

    // Create DateTime object and format due date to 'Y-m-d'
    $dueDateObj = date_create($due_date);
    $due_date_formatted = $dueDateObj->format('Y-m-d');

    // Check if editing an existing task (task_id provided in POST)
    if (isset($_POST['task_id'])) {
      // Update existing task with new values
      $editId = $_POST['task_id'];

      // Prepare SQL statement to update task where ID and user_id match
      $stmt = mysqli_prepare($conn, "UPDATE tasks SET title=?, description=?, stage=?, priority=?, category=?,starting_date=?,due_date=?, updated_at=NOW() WHERE id=? AND user_id=?");
      mysqli_stmt_bind_param($stmt, "sssssssss", $title, $description, $stage, $priority, $category, $starting_date_formatted, $due_date_formatted, $editId, $userId);
    } else {
      // Insert a new task record

      $id = generateTaskId(); // Generate a new custom ID for the task

      // Prepare SQL statement to insert new task into the database
      $stmt = mysqli_prepare($conn, "INSERT INTO tasks (id, user_id, title, description, stage, priority,category, starting_date,due_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?, NOW(), NOW())");
      mysqli_stmt_bind_param($stmt, "sssssssss", $id, $userId, $title, $description, $stage, $priority, $category, $starting_date_formatted, $due_date_formatted);
    }

    // Execute the prepared statement (insert or update)
    if (mysqli_stmt_execute($stmt)) {
      // On success, redirect back to the source page (passed via GET parameter)
      echo $sourcePage;
      header("Location: {$_ENV['APP_URL']}{$_GET['sourcePage']}.php");
      exit(); // Stop further execution after redirect
    } else {
      // If error occurs, display error message
      echo "Error: " . mysqli_stmt_error($stmt);
    }
  }
  ?>


  <div class="taskForm flex items-center justify-center min-h-screen">
    <!-- Container for the task form, centered vertically and horizontally -->
    <div
      class="my-3 form_container bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
      <!-- Inner white box with padding, rounded corners, shadow, and max width -->
      <h2 class="text-3xl font-bold text-gray-700 mb-8 text-center">
        New Task
      </h2>
      <!-- Form title -->

      <form action="<?php $_SERVER['PHP_SELF'] ?>" method="POST" class="grid grid-cols-1 gap-4">
        <!-- Form element submitting to itself via POST, styled as a grid with gaps -->

        <?php if ($editMode): ?>
          <!-- If editing, include a hidden input to hold the task ID -->
          <input type="hidden" name="task_id" value="<?= $task['id'] ?>" />
        <?php endif; ?>

        <!-- Title input field -->
        <div>
          <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Title</label>
          <input
            type="text"
            placeholder="Title"
            name="title"
            value="<?= $editMode ? htmlspecialchars($task['title']) : '' ?>"
            class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
        </div>

        <!-- Description textarea -->
        <div class="relative">
          <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Description</label>
          <textarea
            name="description"
            rows="4"
            placeholder="Description"
            class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition resize-y"><?= $editMode ? htmlspecialchars($task['description']) : '' ?></textarea>
        </div>

        <!-- Starting Date and Due Date inputs side by side -->
        <div class="grid grid-cols-2 gap-4 twoFieldInOne">
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Starting Date</label>
            <input
              type="date"
              id="starting_date"
              placeholder=" "
              name="starting_date"
              value="<?= !empty($taregtDate) ? $taregtDate : ($task['starting_date'] ?? '') ?>"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
          </div>
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Due Date</label>
            <input
              type="date"
              id="due_date"
              placeholder=" "
              name="due_date"
              value="<?= !empty($taregtDate) ? $taregtDate : ($task['due_date'] ?? '') ?>"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
          </div>
        </div>

        <!-- Stage and Priority select dropdowns -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Stage</label>
            <select
              id="stage"
              name="stage"
              class="peer w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-400 shadow-sm transition bg-white">
              <option disabled selected>Select Stage</option>
              <!-- Option values with selected attribute set conditionally in edit mode -->
              <option value="started" <?= $editMode && $task['stage'] == 'started' ? 'selected' : '' ?>>Started</option>
              <option value="pending" <?= $editMode && $task['stage'] == 'pending' ? 'selected' : '' ?>>Pending</option>
              <option value="completed" <?= $editMode && $task['stage'] == 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
          </div>
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Priority</label>
            <select
              id="priority"
              name="priority"
              class="peer w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-400 shadow-sm transition bg-white">
              <option disabled selected>Select Priority</option>
              <!-- Priority options with conditional selected attribute -->
              <option value="low" <?= $editMode && $task['priority'] == 'low' ? 'selected' : '' ?>>Low</option>
              <option value="medium" <?= $editMode && $task['priority'] == 'medium' ? 'selected' : '' ?>>Medium</option>
              <option value="high" <?= $editMode && $task['priority'] == 'high' ? 'selected' : '' ?>>High</option>
            </select>
          </div>
        </div>

        <!-- Category input field -->
        <div>
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Category</label>
            <input
              type="text"
              placeholder="Category"
              name="category"
              value="<?= $editMode ? htmlspecialchars($task['category']) : '' ?>"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
          </div>
        </div>

        <!-- Submit button with dynamic label for create or update -->
        <button
          type="submit"
          class="w-full bg-yellow-400 hover:bg-yellow-500 active:scale-95 text-white font-bold py-3 rounded-xl shadow-md transition">
          <?= $editMode ? 'Update Task' : 'Create Task' ?>
        </button>
      </form>

      <!-- Link back to either My Tasks or Home depending on sourcePage -->
      <p class="text-center text-sm text-gray-600 mt-4">
        Back to
        <a
          href="https://my-tasking.wuaze.com/<?php $_GET['sourcePage'] . "php" ?>"
          class="text-yellow-500 font-semibold hover:underline"><?php echo $_GET['sourcePage'] == "myTasks" ? "My Tasks" : "Home" ?></a>
      </p>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Select the task form element
      const form = document.querySelector(".taskForm form");
      if (!form) return; // Safety check if form not found

      // Select all input, textarea and select elements except hidden inputs
      const inputs = form.querySelectorAll("input:not([type='hidden']), textarea, select");
      console.log(inputs);

      // Assign each input to a descriptive variable for easier reference
      const titleInput = inputs[0];
      const descriptionInput = inputs[1];
      const startingDateInput = inputs[2];
      const dueDateInput = inputs[3];
      const stageSelect = inputs[4];
      const prioritySelect = inputs[5];
      const categoryInput = inputs[6];

      // Create or find message divs for each input to display validation messages
      inputs.forEach((input) => {
        let msgDiv = input.parentNode.querySelector("div.text-sm");
        if (!msgDiv) {
          msgDiv = document.createElement("div");
          msgDiv.classList.add("text-sm", "mt-1");
          input.parentNode.appendChild(msgDiv);
        }
      });

      // Helper function to mark an input as valid and show a green message
      function setValid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-green-500 text-sm mt-1";
        input.classList.remove("border-red-500");
        input.classList.add("border-green-500");
      }

      // Helper function to mark an input as invalid and show a red message
      function setInvalid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-red-500 text-sm mt-1";
        input.classList.remove("border-green-500");
        input.classList.add("border-red-500");
      }

      // Validate the title input (minimum 3 characters)
      function validateTitle() {
        const value = titleInput.value.trim();
        const msg = titleInput.parentNode.querySelector("div.text-sm");
        if (value.length < 3) {
          setInvalid(titleInput, msg, `<i class="fa-solid fa-xmark"></i> Title must be at least 3 characters.`);
          return false;
        } else {
          setValid(titleInput, msg, `<i class="fa-solid fa-check"></i> Looks good!`);
          return true;
        }
      }

      // Validate the description input (minimum 10 characters)
      function validateDescription() {
        const value = descriptionInput.value.trim();
        const msg = descriptionInput.parentNode.querySelector("div.text-sm");
        if (value.length < 10) {
          setInvalid(descriptionInput, msg, `<i class="fa-solid fa-xmark"></i> Description must be at least 10 characters.`);
          return false;
        } else {
          setValid(descriptionInput, msg, `<i class="fa-solid fa-check"></i> Looks good!`);
          return true;
        }
      }

      // Validate the starting date input (must be future date and before due date if set)
      function validateStartingDate() {
        const startValue = startingDateInput.value;
        const endValue = dueDateInput.value;
        const msg = startingDateInput.parentNode.querySelector("div.text-sm");

        const startDate = new Date(startValue);
        const endDate = new Date(endValue);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (!startValue || startDate < today) {
          setInvalid(startingDateInput, msg, `<i class="fa-solid fa-xmark"></i> Please select a valid future start date.`);
          return false;
        }

        if (endValue && startDate > endDate) {
          setInvalid(startingDateInput, msg, `<i class="fa-solid fa-xmark"></i> Start date cannot be after due date.`);
          return false;
        }

        setValid(startingDateInput, msg, `<i class="fa-solid fa-check"></i> Valid start date!`);
        return true;
      }

      // Validate the due date input (must be future date and after start date if set)
      function validateDueDate() {
        const endValue = dueDateInput.value;
        const startValue = startingDateInput.value;
        const msg = dueDateInput.parentNode.querySelector("div.text-sm");

        const endDate = new Date(endValue);
        const startDate = new Date(startValue);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (!endValue || endDate < today) {
          setInvalid(dueDateInput, msg, `<i class="fa-solid fa-xmark"></i> Please select a valid future due date.`);
          return false;
        }

        if (startValue && endDate < startDate) {
          setInvalid(dueDateInput, msg, `<i class="fa-solid fa-xmark"></i> Due date cannot be before start date.`);
          return false;
        }

        setValid(dueDateInput, msg, `<i class="fa-solid fa-check"></i> Valid due date!`);
        return true;
      };

      // Validate the stage select input (must be one of predefined stages)
      function validateStage() {
        const value = stageSelect.value.trim().toLowerCase();
        const msg = stageSelect.parentNode.querySelector("div.text-sm");
        const validStages = ["started", "pending", "completed"];
        if (!validStages.includes(value) || value === "") {
          setInvalid(stageSelect, msg, `<i class="fa-solid fa-xmark"></i> Please select a stage.`);
          return false;
        } else {
          setValid(stageSelect, msg, `<i class="fa-solid fa-check"></i> Selected!`);
          return true;
        }
      }

      // Validate the priority select input (must be one of predefined priorities)
      function validatePriority() {
        const value = prioritySelect.value.trim().toLowerCase();
        const msg = prioritySelect.parentNode.querySelector("div.text-sm");
        const validPriorities = ["low", "medium", "high"];
        if (!validPriorities.includes(value) || value === "") {
          setInvalid(prioritySelect, msg, `<i class="fa-solid fa-xmark"></i> Please select a priority.`);
          return false;
        } else {
          setValid(prioritySelect, msg, `<i class="fa-solid fa-check"></i> Selected!`);
          return true;
        }
      };

      // Validate the category input (minimum 3 characters)
      function validateCategory() {
        const value = categoryInput.value.trim();
        const msg = categoryInput.parentNode.querySelector("div.text-sm");
        if (value.length < 2) {
          setInvalid(categoryInput, msg, `<i class="fa-solid fa-xmark"></i> Category must be at least 3 characters.`);
          return false;
        } else {
          setValid(categoryInput, msg, `<i class="fa-solid fa-check"></i> Looks good!`);
          return true;
        }
      }

      // Attach event listeners to inputs for live validation on input/change
      titleInput.addEventListener("input", validateTitle);
      descriptionInput.addEventListener("input", validateDescription);
      startingDateInput.addEventListener("change", validateStartingDate);
      dueDateInput.addEventListener("change", validateDueDate);
      stageSelect.addEventListener("change", validateStage);
      prioritySelect.addEventListener("change", validatePriority);
      categoryInput.addEventListener("input", validateCategory);

      // On form submit, validate all inputs; if valid, submit form, else prevent submission
      form.addEventListener("submit", function(e) {
        e.preventDefault();
        const validTitle = validateTitle();
        const validDescription = validateDescription();
        const validStartingDate = validateStartingDate();
        const validDueDate = validateDueDate();
        const validStage = validateStage();
        const validPriority = validatePriority();
        const validCategory = validateCategory();

        if (validTitle && validDescription && validStartingDate && validDueDate && validStage && validPriority && validCategory) {
          form.submit(); // This bypasses e.preventDefault() and sends the form
        }
      });
    });
  </script>


</body>

</html>