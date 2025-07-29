<?php
date_default_timezone_set('UTC');
require '../config/config.php';                    // Load database configuration and constants
require '../auth/auth.php';                        // Load authentication helper functions
require_once '../api/functions.php';              // Include general utility functions
deleteExtraOTP($conn);                             // Clean up expired or extra OTP entries in DB
deleteUnverifiedAccounts($conn);                   // Remove unverified user accounts from DB
require "../sendMail.php";                         // Include mail sending functionality
require_once __DIR__ .'/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$emailError = $otpError = $passwordError = '';    // Initialize error message variables
$step = 'email';                                  // Current form step: 'email' or 'otp'
$success = '';                                    // Success message placeholder
$showForm = true;                                 // Whether to show form or not
$currentUser = $_SESSION['current_user'] ?? null; // Current logged-in user info from session

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // ===== STEP 1: Submit Email + Password =====
  if ($_POST['step'] === 'email') {
    $email = htmlspecialchars(trim($_POST['email']));               // Sanitize email input
    $password = htmlspecialchars($_POST['password']);                // Get password input

    // Prepare SQL to select user by email
    $stmt = mysqli_prepare($conn, "SELECT id, name, password FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if user exists
    if ($result && mysqli_num_rows($result) > 0) {
      $user = mysqli_fetch_assoc($result);         // Fetch user record

      // Verify input password with hashed password from DB
      if (password_verify($password, $user['password'])) {
        $_SESSION['account_email'] = $email;       // Save email in session for OTP step

        // Generate 6-digit OTP and set expiration time (5 minutes from now)
        $otp = rand(100000, 999999);

        // Check if OTP already exists for this email and action type 'delete_account'
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM otp WHERE email = ? AND action_type = 'delete_account'");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
          // Update existing OTP entry with new OTP
          $updateStmt = mysqli_prepare($conn, "UPDATE otp SET otp = ? WHERE email = ? AND action_type = 'delete_account'");
          mysqli_stmt_bind_param($updateStmt, "ss", $otp, $email);
          $otpUpdated = mysqli_stmt_execute($updateStmt);
        } else {
          // Insert new OTP record into OTP table
          $insertStmt = mysqli_prepare($conn, "INSERT INTO otp (email, otp, action_type) VALUES (?, ?, 'delete_account')");
          mysqli_stmt_bind_param($insertStmt, "ss", $email, $otp);
          $otpUpdated = mysqli_stmt_execute($insertStmt);
        }

        if ($otpUpdated) {
          // Prepare HTML content for OTP email
          $html = '<div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f7f9fc; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
            <div style="text-align: center;">
              <h2 style="color: #ef4444;">Account Deletion Request ⚠️</h2>
              <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($user["name"]) . '</strong>,</p>
            </div>
            <p style="font-size: 15px; color: #444;">
              You have requested to delete your account. Use the OTP below to confirm:
            </p>
            <div style="text-align: center; margin: 30px 0;">
              <div style="width: fit-content; margin: auto; background-color: #FACC15; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-size: 16px;">
                ' . htmlspecialchars($otp) . '
              </div>
            </div>
            <p style="font-size: 13px; color: #aaa;">This OTP is valid for 5 minutes. Do not share it with anyone.</p>
            <p style="font-size: 13px; color: #aaa;">Once confirmed, your account will be permanently deleted.</p>
            <p style="font-size: 14px; color: #777;">
              If you didn\'t request this, you can safely ignore this email.
            </p>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
            <p style="font-size: 13px; color: #999; text-align: center;">© ' . date('Y') . ' My Tasking. All rights reserved.</p>
          </div>';

          // Send OTP email and advance to OTP input step if successful
          if (sendMail($email, "Account Deletion OTP", $html)) {
            $step = 'otp';
          } else {
            $emailError = "Failed to send OTP email."; // Email sending failed
          }
        } else {
          $emailError = "Failed to generate OTP. Try again later."; // OTP insert/update failed
        }
      } else {
        $passwordError = "Incorrect password.";          // Password did not match
      }
    } else {
      $emailError = "Email not registered.";             // No user found with entered email
    }

    // ===== STEP 2: Submit OTP =====
  } elseif ($_POST['step'] === 'otp') {
    // Get the current email stored in session during the OTP request
    $accountEmail = $_SESSION['account_email'] ?? '';

    // Get the OTP entered by the user and trim any whitespace
    $inputOtp = (int) trim($_POST['otp']);

    // Get the current time
    $now = time() - 300;

    // Prepare SQL statement to find a matching OTP for the given email that hasn't expired
    $stmt = mysqli_prepare($conn, "SELECT * FROM otp WHERE email = ? AND otp = ? AND action_type = 'delete_account' AND UNIX_TIMESTAMP(updated_at) >= ?");

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmt, "sss", $accountEmail, $inputOtp, $now);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Get the result of the query
    $result = mysqli_stmt_get_result($stmt);


    if ($result && mysqli_num_rows($result) > 0) {
      // OTP is valid and not expired - proceed with account deletion

      // Delete all tasks associated with the user
      $deleteTask = mysqli_prepare($conn, "DELETE FROM tasks WHERE user_id = ?");
      mysqli_stmt_bind_param($deleteTask, "s", $currentUser['id']);
      mysqli_stmt_execute($deleteTask);

      // Delete user account by email
      $delUserStmt = mysqli_prepare($conn, "DELETE FROM users WHERE email = ?");
      mysqli_stmt_bind_param($delUserStmt, "s", $accountEmail);
      mysqli_stmt_execute($delUserStmt);

      // Delete OTP entry to clean up
      $deleteStmt = mysqli_prepare($conn, "DELETE FROM otp WHERE email = ?");
      mysqli_stmt_bind_param($deleteStmt, "s", $accountEmail);
      mysqli_stmt_execute($deleteStmt);
      // Prepare confirmation email HTML
      $html = '<div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f7f9fc; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
            <div style="text-align: center;">
                                    <h2 style="color: #ef4444;">Account Deleted Successfully</h2>
              <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($currentUser["name"]) . '</strong>,</p>
            </div>
                                <p style="font-size: 15px; color: #444;">
                                    Your account with <strong>My Tasking</strong> has been permanently deleted. All your personal data, profile information, and tasks have been removed from our systems.
                                </p>
                                <div style="text-align: center; margin: 30px 0;">
                                    <a href="https://my-tasking.wuaze.com/auth/signup.php" target="_blank" style="background-color: #3b82f6; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-size: 16px;">Create a New Account</a>
                                </div>
                                <p style="font-size: 14px; color: #777;">
                                    We’re sorry to see you go. You’re always welcome back at <strong>My Tasking</strong> whenever you’re ready. 😊
                                </p>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
            <p style="font-size: 13px; color: #999; text-align: center;">© ' . date('Y') . ' My Tasking. All rights reserved.</p>
          </div>';

      sendMail($currentUser["email"], "Your Account Has Been Deleted", $html); // Send confirmation email


      // Destroy the session
      $showForm = false;
      $success = true;
      // Destroy all session variables
      session_unset();
      session_destroy();

      // Remove the login cookie if exists
      if (isset($_COOKIE['task_manager_user_token'])) {
        // Clear cookie
        setcookie('task_manager_user_token', '', time() - 3600, "/", "", true, true);  // Expire cookie
      }
    } else {
      $otpError = "Invalid or expired OTP.";             // OTP invalid or expired message
      $step = 'otp';                                      // Stay on OTP input step
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Delete Account - My Tasking – Secure & Smart Task Management System</title>

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
  <script src="../js/fontawesome.js"></script>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="../css/style.css" />
</head>

<body class="flex items-center justify-center min-h-screen bg-gray-100">
  <!-- Container for the delete account form -->
  <div class="my-3 form_container bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
    <!-- Show the form only if $showForm is true -->
    <?php if ($showForm): ?>
      <!-- Show warning message only -->
      <h2 class="text-3xl font-bold text-gray-700 mb-2 text-center">Delete Account</h2>
      <p class="text-red-500 text-center mb-2 font-semibold">Deleting your account is permanent. <br> All your data will be lost forever. <br> Are you absolutely sure you want to continue?</p>
      <form method="POST" class="grid gap-4">
        <!-- Hidden input to track the current step -->
        <input type="hidden" name="step" value="<?= $step ?>">

        <!-- Email and password step -->
        <?php if ($step === 'email'): ?>
          <div>
            <label class="text-sm text-gray-600 mb-1 block">Email</label>
            <!-- Email input field -->
            <input type="email" name="email" id="email" required placeholder="Enter your email" class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
            <!-- Show email error message if any -->
            <?php if ($emailError): ?><p class="text-red-500 text-sm mt-1"><?= $emailError ?></p><?php endif; ?>
          </div>
          <div class="relative">
            <label class="text-sm text-gray-600 mb-1 block">Password</label>
            <!-- Password input field -->
            <input type="password" name="password" id="password" required placeholder="Enter your password" class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
            <!-- Show password error message if any -->
            <?php if ($passwordError): ?><p class="text-red-500 text-sm mt-1"><?= $passwordError ?></p><?php endif; ?>
          </div>
          <!-- OTP input step -->
        <?php elseif ($step === 'otp'): ?>
          <div>
            <label class="text-sm text-gray-600 mb-2 block">Enter OTP sent to your email</label>
            <!-- OTP input field -->
            <input type="text" name="otp" id="otp" required maxlength="6" class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
            <!-- Show OTP error message if any -->
            <?php if ($otpError): ?><p class="text-red-500 text-sm mt-1"><?= $otpError ?></p><?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Submit button -->
        <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-3 rounded-xl shadow-md transition">
          Continue
        </button>
        <!-- Links shown in email step -->
        <?php if ($step === 'email'): ?>
          <p class="text-center text-sm text-gray-600 mt-1">
            Forgot <a href="./reset-password.php" class="text-yellow-500 font-semibold hover:underline">Password</a> ?
          </p>
          <a href="/" class="w-full text-center text-blue-500 font-bold  rounded-xl  transition">
            Back to home
          </a>
          <!-- Link shown in OTP step -->
        <?php elseif ($step === 'otp'): ?>
          <a href="<?php $_SERVER['PHP_SELF'] ?>" class="w-full text-center text-blue-500 font-bold  rounded-xl  transition">
            Back to email
          </a>
        <?php endif; ?>
      </form>
    <?php endif; ?>
    <!-- Display success message if set -->
    <?php if ($success): ?>
      <div class="text-green-600 text-center">
        <h2 class="text-green-500 text-center font-semibold">Account deleted successfully</h2>
        <p class="text-center w-full"><a href='./signup.php' class="text-blue-500">Sign up now</a></p>
      </div>
    <?php endif; ?>
  </div>

  <script>
    // Run the script when DOM is fully loaded
    document.addEventListener("DOMContentLoaded", function() {
      const form = document.querySelector("form");

      // Select input elements by ID
      const emailInput = document.querySelector("#email");
      const otpInput = document.querySelector("#otp");
      const passwordInput = document.querySelector("#password");

      // Add a message container below each input if it exists
      [emailInput, otpInput, passwordInput].forEach((input) => {
        if (!input) return;
        const msg = document.createElement("div");
        msg.classList.add("text-sm", "mt-1");
        input.parentNode.appendChild(msg);
      });

      // Add an eye icon button to toggle password visibility if password input exists
      if (passwordInput) {
        const wrapper = passwordInput.parentNode;
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

        // Toggle password visibility on eye icon click
        eyeBtn.addEventListener("click", function() {
          if (passwordInput.type === "password") {
            passwordInput.type = "text";
            eyeBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
          } else {
            passwordInput.type = "password";
            eyeBtn.innerHTML = '<i class="fas fa-eye"></i>';
          }
        });
      }

      // Validate email input format
      function validateEmail() {
        if (!emailInput) return true;
        const value = emailInput.value.trim();
        const msg = emailInput.parentNode.querySelector("div.text-sm");
        if (!/^\S+@\S+\.\S+$/.test(value)) {
          setInvalid(
            emailInput,
            msg,
            `<i class="fa-solid fa-xmark"></i> Please enter a valid email address.`
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

      // Validate OTP input to be exactly 6 digits
      function validateOTP() {
        if (!otpInput) return true;
        const value = otpInput.value.trim();
        const msg = otpInput.parentNode.querySelector("div.text-sm");
        if (!/^\d{6}$/.test(value)) {
          setInvalid(
            otpInput,
            msg,
            `<i class="fa-solid fa-xmark"></i> OTP must be a 6-digit number.`
          );
          return false;
        } else {
          setValid(
            otpInput,
            msg,
            `<i class="fa-solid fa-check"></i> OTP verified format`
          );
          return true;
        }
      }

      // Validate password with complexity rules: min 8 chars, uppercase, lowercase, number, special char
      function validatePassword() {
        if (!passwordInput) return true;
        const value = passwordInput.value;
        const msg = passwordInput.parentNode.querySelector("div.text-sm");
        if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(value)) {
          setInvalid(
            passwordInput,
            msg,
            `<i class="fa-solid fa-xmark"></i> 8+ chars, uppercase, lowercase, number, special char.`
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

      // Set valid input styles and message
      function setValid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-green-500 text-sm mt-1";
        input.classList.remove("border-red-500", "border-gray-300");
        input.classList.add("border-green-500");
      }

      // Set invalid input styles and message
      function setInvalid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-red-500 text-sm mt-1";
        input.classList.remove("border-green-500", "border-gray-300");
        input.classList.add("border-red-500");
      }

      // Attach input event listeners for live validation feedback
      if (emailInput) emailInput.addEventListener("input", validateEmail);
      if (otpInput) otpInput.addEventListener("input", validateOTP);
      if (passwordInput) passwordInput.addEventListener("input", validatePassword);

      // Form submission handler with validation for visible inputs only
      form?.addEventListener("submit", function(e) {
        let isValid = true;
        if (emailInput && isVisible(emailInput)) isValid = validateEmail() && isValid;
        if (otpInput && isVisible(otpInput)) isValid = validateOTP() && isValid;
        if (passwordInput && isVisible(passwordInput)) isValid = validatePassword() && isValid;

        // Prevent form submission if any validation fails
        if (!isValid) e.preventDefault();
      });

      // Utility function to check if element is visible on page
      function isVisible(el) {
        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
      }
    });
  </script>
</body>

</html>