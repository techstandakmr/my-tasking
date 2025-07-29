<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile Edit</title>

  <meta name="title" content="Profile Edit – Secure & Smart Task Management System" />
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
  // Include configuration file for DB connection
  require './config/config.php';
  // Include authentication check
  require './auth/auth.php'; // if you're using an auth check
  require __DIR__ . '/vendor/autoload.php'; // Include Composer autoload for PHPMailer and dependencies
  use Dotenv\Dotenv;

  $dotenv = Dotenv::createImmutable(__DIR__ . '/'); // adjust path if .env is in parent folder
  $dotenv->load();
  // Get current logged-in user from session
  $currentUser = $_SESSION['current_user'];
  $userId = $currentUser['id'];

  // Initialize variables for form fields and messages
  $name  = $title = $description = '';
  $success = $error = '';
  // Fetch current user data from database to prefill the form
  $result = mysqli_query($conn, "SELECT * FROM users WHERE id='$userId'");
  if ($result && mysqli_num_rows($result)) {
    $row = mysqli_fetch_assoc($result);
    $name = $row['name'];
    $title = $row['title'] ?? '';
    $description = $row['description'] ?? '';
  }

  // Handle form submission for profile update
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and trim submitted form data
    $name = htmlspecialchars(trim($_POST['name']));
    $title = htmlspecialchars(trim($_POST['title']));
    $description = htmlspecialchars(trim($_POST['description']));

    // Prepare SQL query to update user data safely
    $updateQuery = "UPDATE users SET name=?, title=?, description=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $title, $description, $userId);
    // Execute the update query
    if (mysqli_stmt_execute($stmt)) {
      $success = "Profile updated successfully!";
      // Redirect to home page after successful update
      header("Location: {$_ENV['APP_URL']}");
      exit();
      // Optionally update avatar too
    } else {
      $error = "Failed to update profile.";
    }
  }
  ?>

  <div class="userEditingForm flex items-center justify-center min-h-screen overflow-y-auto">
    <div
      class="my-3 form_container m-4 bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
      <h2 class="text-3xl font-bold text-gray-700 mb-8 text-center">
        Profile Edit
      </h2>
      <form action="<?php $_SERVER['PHP_SELF'] ?>" method="POST" class="grid grid-cols-1 gap-4">
        <!-- Name input field -->
        <div class="relative">
          <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Name"
            class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
        </div>
        <!-- Title input field -->
        <div>
          <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Title</label>
          <input
            type="text"
            placeholder="Title"
            name="title"
            value="<?= htmlspecialchars($title) ?>"
            class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
        </div>

        <!-- Description textarea field -->
        <div class="relative">
          <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Description</label>
          <textarea
            name="description"
            rows="4"
            placeholder="Description"
            class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition resize-y"><?= htmlspecialchars($description) ?></textarea>

        </div>
        <!-- Submit button to update profile -->
        <button
          type="submit"
          class="w-full bg-yellow-400 hover:bg-yellow-500 active:scale-95 text-white font-bold py-3 rounded-xl shadow-md transition">
          Update
        </button>
      </form>

      <!-- Link back to home page -->
      <p class="text-center text-sm text-gray-600 mt-4">
        Back to
        <a
          href="/"
          class="text-yellow-500 font-semibold hover:underline">home</a>
      </p>
    </div>
  </div>
  <script>
    // Wait for the DOM to fully load before running script
    document.addEventListener("DOMContentLoaded", function() {
      // Select the profile edit form inside the container
      const form = document.querySelector(".userEditingForm form");
      if (!form) return; // Safety check if form not found
      // Select all input and textarea fields inside the form
      const inputs = form.querySelectorAll("input,textarea");
      console.log("inputs", inputs)
      // Assign individual inputs for easier access
      const nameInput = inputs[0];
      const titleInput = inputs[1];
      const descriptionInput = inputs[2];
      // Create or get message spans below each input for validation messages
      inputs.forEach((input) => {
        let msgDiv = input.parentNode.querySelector("div.text-sm");
        if (!msgDiv) {
          msgDiv = document.createElement("div");
          msgDiv.classList.add("text-sm", "mt-1");
          input.parentNode.appendChild(msgDiv);
        }
      });

      // Function to mark input as valid with a green message
      function setValid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-green-500 text-sm mt-1";
        input.classList.remove("border-red-500");
        input.classList.add("border-green-500");
      }

      // Function to mark input as invalid with a red message
      function setInvalid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-red-500 text-sm mt-1";
        input.classList.remove("border-green-500");
        input.classList.add("border-red-500");
      }
      // Validation function for Name input
      function validateName() {
        const value = nameInput.value.trim();
        const msg = nameInput.parentNode.querySelector("div.text-sm");
        if (!/^[A-Za-z ]{3,}$/.test(value)) {
          setInvalid(
            nameInput,
            msg,
            `<i class="fa-solid fa-xmark"></i>  Name must be at least 3 letters (alphabets only).`
          );
          return false;
        } else {
          setValid(
            nameInput,
            msg,
            `<i class="fa-solid fa-check"></i> Looks good!`
          );
          return true;
        }
      }

      // Validation function for Title input
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

      // Validation function for Description textarea
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

      // Attach input event listeners for real-time validation feedback
      nameInput.addEventListener("input", validateName);
      titleInput.addEventListener("input", validateTitle);
      descriptionInput.addEventListener("input", validateDescription);
      // On form submission, validate all inputs before submitting
      form.addEventListener("submit", function(e) {
        e.preventDefault();
        const validName = validateName();
        const validTitle = validateTitle();
        const validDescription = validateDescription();

        // Submit form only if all validations pass
        if (validName && validTitle && validDescription) {
          form.submit(); // This bypasses e.preventDefault() and sends the form
        }
      });
    });
  </script>
</body>

</html>