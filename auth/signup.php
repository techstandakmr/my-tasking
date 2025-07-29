<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>Sign Up - My Tasking – Secure & Smart Task Management System</title>
  <meta name="title" content="Sign Up - My Tasking – Secure & Smart Task Management System" />
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
  session_start(); // Start the session to store user messages and states
  require '../config/config.php'; // Include database configuration
  require "../sendMail.php"; // Include mail sending function
  require  '../api/functions.php'; // Include custom functions like deleting expired OTPs and unverified accounts

  // Clean up expired OTP entries from the database
  deleteExtraOTP($conn);
  // Remove unverified user accounts that are expired or inactive
  deleteUnverifiedAccounts($conn);

  $emailError = ''; // Initialize variable to store email-related error messages
  $name = $email = ''; // Initialize user input variables
  $step = 1; // Track the current step of the registration process (1 = registration form, 2 = email sent)

  // Function to generate a unique custom user ID combining a timestamp and random string
  function generateUserId()
  {
    $timestamp = number_format(microtime(true) * 1000, 0, '', ''); // returns a string without decimals or commas
    return 'TASK_' . $timestamp;
  };

  // Handle form submission on POST request
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = generateUserId(); // Generate a new user ID
    $name = htmlspecialchars(trim($_POST['name'])); // Sanitize and trim the name input
    $email = htmlspecialchars(trim($_POST['email'])); // Sanitize and trim the email input
    $password = htmlspecialchars($_POST['password']); // Get the password input
    $confirmPassword = htmlspecialchars($_POST['confirm_password']); // Get the confirmation password input

    // 1. Check if the email already exists in the users table
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    // If email is found, set an error message
    if (mysqli_num_rows($result) > 0) {
      $emailError = "Email already exists.";
    }
    // Check if passwords do not match and set error message
    elseif ($password !== $confirmPassword) {
      $emailError = "Passwords do not match.";
    } else {
      // Hash the password securely using bcrypt algorithm
      $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

      // 2. Generate a secure 64-character verification token for email confirmation
      $verification_token = bin2hex(random_bytes(32));

      // 3. Insert the new user with is_verified flag set to 0 (false) and store the verification token
      $stmt = mysqli_prepare($conn, "INSERT INTO users (id, name, email, password, is_verified, verification_token) VALUES (?, ?, ?, ?, 0, ?)");
      mysqli_stmt_bind_param($stmt, "sssss", $id, $name, $email, $hashedPassword, $verification_token);
      $insert = mysqli_stmt_execute($stmt);

      if ($insert) {
        // 4. Prepare the verification email content with an HTML styled message and verification link
        $verifyLink = "https://my-tasking.wuaze.com/auth/verifyEmail.php?token=$verification_token";
        $subject = "Verify Your Email";
        $html = '
        <div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f7f9fc; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
          <div style="text-align: center;">
            <h2 style="color: #FACC15;">Welcome to My Tasking 👋</h2>
            <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
          </div>
          <p style="font-size: 15px; color: #444;">
            Thanks for signing up! You\'re almost ready to get started with <strong>My Tasking</strong>.
            Please verify your email address by clicking the button below:
          </p>
          <div style="text-align: center; margin: 30px 0;">
            <a href="' . htmlspecialchars($verifyLink) . '" target="_blank" style="width: fit-content; margin: auto; background-color: #FACC15; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-size: 16px;">Verify Email</a>
          </div>
          <p style="font-size: 14px; color: #777;">
            If you didn\'t sign up for My Tasking, you can safely ignore this email.
          </p>
          <p style="font-size: 13px; color: #aaa;">This verification link will expire in 15 minutes.</p>
          <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
          <p style="font-size: 13px; color: #999; text-align: center;">© ' . date('Y') . ' My Tasking. All rights reserved.</p>
        </div>';

        // Send the verification email to the user
        if (sendMail($email, $subject, $html)) {
          $_SESSION['success'] = "Verification email sent. Please check your inbox.";
          $step = 2; // Proceed to the next step after successful email sending
        } else {
          // Error if email sending fails
          $emailError = "Registration failed: couldn't send verification email.";
        }
      } else {
        // Error if database insertion fails
        $emailError = "Something went wrong. Please try again.";
      }
    }
  }
  ?>


  <?php if ($step == 1): ?>
    <!-- Step 1: Display the Sign Up form -->
    <div class="flex items-center justify-center min-h-screen overflow-y-auto">
      <div
        class="form_container my-3 m-4 bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
        <h2 class="text-3xl font-bold text-gray-700 mb-8 text-center">
          Sign Up
        </h2>
        <!-- Form to collect user input -->
        <form action="<?php $_SERVER['PHP_SELF'] ?>" method="POST" class="grid grid-cols-1 gap-4">
          <!-- Name input field -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" placeholder="Name"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
          </div>

          <!-- Email input field -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="Email"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
            <!-- Display email error if exists -->
            <?php if ($emailError): ?>
              <div class="text-red-500 text-sm mt-1"><?= $emailError ?></div>
            <?php endif; ?>
          </div>

          <!-- Password input field -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Password</label>
            <input type="password" name="password" placeholder="Password"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
          </div>

          <!-- Confirm Password input field -->
          <div class="relative">
            <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm Password"
              class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
          </div>

          <!-- Submit button for registration -->
          <button
            type="submit"
            class="w-full bg-yellow-400 hover:bg-yellow-500 active:scale-95 text-white font-bold py-3 rounded-xl shadow-md transition">
            Sign Up
          </button>
        </form>

        <!-- Link to login page for existing users -->
        <p class="text-center text-sm text-gray-600 mt-4">
          Already have an account?
          <a
            href="./login.php"
            class="text-yellow-500 font-semibold hover:underline">Login</a>
        </p>
      </div>
    </div>

    <script>
      // Wait for the DOM to fully load
      document.addEventListener("DOMContentLoaded", function() {
        const form = document.querySelector("form");
        const inputs = form.querySelectorAll("input");
        const nameInput = inputs[0];
        const emailInput = inputs[1];
        const passwordInput = inputs[2];
        const confirmPasswordInput = inputs[3];

        // Add eye icon buttons to toggle password visibility
        [passwordInput, confirmPasswordInput].forEach((input) => {
          const wrapper = input.parentNode;
          const eyeBtn = document.createElement("button");
          eyeBtn.type = "button";
          eyeBtn.innerHTML = '<i class="fas fa-eye"></i>';
          eyeBtn.classList.add(
            "absolute",
            "right-3",
            "top-9",
            "text-gray-400",
            "hover:text-gray-600"
          );
          wrapper.appendChild(eyeBtn);

          // Toggle password text visibility on eye icon click
          eyeBtn.addEventListener("click", function() {
            if (input.type === "password") {
              input.type = "text";
              eyeBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
              input.type = "password";
              eyeBtn.innerHTML = '<i class="fas fa-eye"></i>';
            }
          });
        });

        // Create divs to show validation messages under each input
        inputs.forEach((input) => {
          const message = document.createElement("div");
          message.classList.add("text-sm", "mt-1");
          input.parentNode.appendChild(message);
        });

        // Validation function for Name field
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

        // Validation function for Email field
        function validateEmail() {
          const value = emailInput.value.trim();
          const msg = emailInput.parentNode.querySelector("div.text-sm");
          if (!/^\S+@\S+\.\S+$/.test(value)) {
            setInvalid(
              emailInput,
              msg,
              `<i class="fa-solid fa-xmark"></i>  Please enter a valid email address.`
            );
            return false;
          } else {
            setValid(
              emailInput,
              msg,
              `<i class="fa-solid fa-check"></i> Looks good!`
            );
            return true;
          }
        }

        // Validation function for Password field
        function validatePassword() {
          const value = passwordInput.value;
          const msg = passwordInput.parentNode.querySelector("div.text-sm");
          if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(value)) {
            setInvalid(
              passwordInput,
              msg,
              `<i class="fa-solid fa-xmark"></i>  8+ chars, uppercase, lowercase, number, special char.`
            );
            return false;
          } else {
            setValid(
              passwordInput,
              msg,
              `<i class="fa-solid fa-check"></i> Strong password`
            );
            return true;
          }
        }

        // Validation function for Confirm Password field
        function validateConfirmPassword() {
          const value = confirmPasswordInput.value;
          const passwordValue = passwordInput.value;
          const msg =
            confirmPasswordInput.parentNode.querySelector("div.text-sm");
          if (value !== passwordValue || value === "") {
            setInvalid(
              confirmPasswordInput,
              msg,
              `<i class="fa-solid fa-xmark"></i>  Passwords do not match.`
            );
            return false;
          } else {
            setValid(
              confirmPasswordInput,
              msg,
              `<i class="fa-solid fa-check"></i> Passwords match`
            );
            return true;
          }
        }

        // Set valid input styling and message
        function setValid(input, msgDiv, text) {
          msgDiv.innerHTML = text;
          msgDiv.className = "text-green-500 text-sm mt-1 ${input}";
          input.classList.remove("border-red-500", "border-gray-300");
          input.classList.add("border-green-500");
        }

        // Set invalid input styling and message
        function setInvalid(input, msgDiv, text) {
          msgDiv.innerHTML = text;
          msgDiv.className = "text-red-500 text-sm mt-1";
          input.classList.remove("border-green-500", "border-gray-300");
          input.classList.add("border-red-500");
        }

        // Attach event listeners for live validation on input
        nameInput.addEventListener("input", validateName);
        emailInput.addEventListener("input", validateEmail);
        passwordInput.addEventListener("input", validatePassword);
        confirmPasswordInput.addEventListener("input", validateConfirmPassword);

        // On form submit, validate all inputs and only submit if valid
        form?.addEventListener("submit", async function(e) {
          e.preventDefault();
          const validName = validateName();
          const validEmail = validateEmail();
          const validPassword = validatePassword();
          const validConfirm = validateConfirmPassword();
          if (validName && validEmail && validPassword && validConfirm) {
            form.submit()
          }
        });
      });
    </script>
  <?php endif; ?>

  <?php if ($step == 2): ?>
    <!-- Step 2: Show message to check email inbox for verification -->

    <body class="flex items-center justify-center min-h-screen bg-gray-100">
      <div class="form_container bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
        <h2 style="color: #FACC15" class="text-center">Check Your Inbox 📬</h2>
        <p style="color: #444; font-size: 16px" class="text-center">
          We've sent a verification link to your email.
        </p>
        <p style="color: #555; font-size: 14px" class="text-center">
          Please open your inbox and click on the verification link to activate your account.
        </p>
        <p style="color: #999; font-size: 13px" class="text-center">
          Didn’t receive the email? Check your spam folder or try again later.
        </p>
      </div>
    </body>
  <?php endif; ?>
</body>

</html>