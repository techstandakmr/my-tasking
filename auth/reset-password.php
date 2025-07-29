<?php
// Include database configuration file
require '../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();  // Start the session only if not already started
}
// Include the email sending functionality
require "../sendMail.php";

// Include file with utility functions
require_once '../api/functions.php'; // Include the functions file
require_once __DIR__ .'/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
// Clean up expired or invalid OTP entries from the database
deleteExtraOTP($conn);

// Remove accounts that haven't been verified in a specified time
deleteUnverifiedAccounts($conn);

// Initialize error message variables
$emailError = $otpError = $passwordError = '';

// Default form step to show (email/password step)
$step = 'email';

// Variable to store success message after successful operation
$success = '';

// Whether to show the form (used to conditionally render the form)
$showForm = true;


if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // ===== STEP 1: Submit Email =====
  if ($_POST['step'] === 'email') {
    // Get and trim the submitted email
    $email = htmlspecialchars(trim($_POST['email']));

    // Prepare SQL statement to check if email exists in users table
    $stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // If email exists in database
    if ($result && mysqli_num_rows($result) > 0) {
      // Fetch user data
      $user = mysqli_fetch_assoc($result);

      // Store email in session for later steps
      $_SESSION['account_email'] = $email;
      // Generate 6-digit OTP and set expiration time (5 minutes from now)
      $otp = rand(100000, 999999);

      // Check if an OTP already exists for this email and reset_password action
      $checkStmt = mysqli_prepare($conn, "SELECT id FROM otp WHERE email = ? AND action_type = 'reset_password'");
      mysqli_stmt_bind_param($checkStmt, "s", $email);
      mysqli_stmt_execute($checkStmt);
      $checkResult = mysqli_stmt_get_result($checkStmt);
      // If OTP exists, update it with new value and expiry
      if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        $updateStmt = mysqli_prepare($conn, "UPDATE otp SET otp = ? WHERE email = ? AND action_type = 'reset_password'");
        mysqli_stmt_bind_param($updateStmt, "ss", $otp, $email);
        $otpUpdated = mysqli_stmt_execute($updateStmt);
      } else {
        // Otherwise, insert a new OTP record
        $insertStmt = mysqli_prepare($conn, "INSERT INTO otp (email, otp, action_type) VALUES (?, ?, 'reset_password')");
        mysqli_stmt_bind_param($insertStmt, "ss", $email, $otp);
        $otpUpdated = mysqli_stmt_execute($insertStmt);
      }

      // If OTP was successfully updated/inserted
      if ($otpUpdated) {
        // Compose the OTP email HTML content
        $html = '<div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f7f9fc; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
              <div style="text-align: center;">
                <h2 style="color: #FACC15;">Reset Your Password 🔐</h2>
                <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($user["name"]) . '</strong>,</p>
              </div>
              <p style="font-size: 15px; color: #444;">
                You requested to reset your password. Use the OTP below to proceed:
              </p>
              <div style="text-align: center; margin: 30px 0;">
                <div style="width: fit-content; margin: auto; background-color: #FACC15; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-size: 16px;">
                  ' . htmlspecialchars($otp) . '
                </div>
              </div>
              <p style="font-size: 13px; color: #aaa;">This OTP is valid for 5 minutes. Do not share it with anyone.</p>
              <p style="font-size: 14px; color: #777;">
                If you didn\'t request this, you can safely ignore this email.
              </p>
              <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
              <p style="font-size: 13px; color: #999; text-align: center;">© ' . date('Y') . ' My Tasking. All rights reserved.</p>
            </div>';

        // Send the OTP email, proceed to next step if successful
        if (sendMail($email, "Password Reset OTP", $html)) {
          $step = 'otp';
        } else {
          // Show error if email sending fails
          $emailError = "Failed to send OTP email.";
        }
      } else {
        // Show error if OTP generation or DB update fails
        $emailError = "Failed to generate OTP. Try again later.";
      }
    } else {
      // Show error if email is not registered
      $emailError = "Email not registered.";
    }

    // ===== STEP 2: Submit OTP =====
  } elseif ($_POST['step'] === 'otp') {
    // Get the current email stored in session during the OTP request
    $currentEmail = $_SESSION['account_email'] ?? '';

    // Get the OTP entered by the user and trim any whitespace
    $inputOtp = (int) trim($_POST['otp']);

    // Get the current time
    $now = time() - 300;

    // Prepare SQL statement to find a matching OTP for the given email that hasn't expired
    $stmt = mysqli_prepare($conn, "SELECT * FROM otp WHERE email = ? AND otp = ? AND action_type = 'reset_password' AND UNIX_TIMESTAMP(updated_at) >= ?");

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmt, "sss", $currentEmail, $inputOtp, $now);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Get the result of the query
    $result = mysqli_stmt_get_result($stmt);

    // If a matching OTP is found and not expired
    if ($result && mysqli_num_rows($result) > 0) {
      $step = 'password';
      // Delete the used OTP
      $deleteStmt = mysqli_prepare($conn, "DELETE FROM otp WHERE email = ?");
      mysqli_stmt_bind_param($deleteStmt, "s", $email);
      mysqli_stmt_execute($deleteStmt);
    } else {
      // OTP is invalid or expired, show error and stay on OTP step
      $otpError = "Invalid or expired OTP.";
      $step = 'otp';
    }

    // ===== STEP 3: Submit New Password =====
  } elseif ($_POST['step'] === 'password') {
    // Get the new password from the form submission
    $password = htmlspecialchars($_POST['password']);

    // Retrieve the email from session set during OTP verification
    $email = $_SESSION['account_email'] ?? '';

    // Validate password complexity: minimum 8 chars, uppercase, lowercase, digit, special char
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
      // Set error message and stay on password step if validation fails
      $passwordError = "Password must be 8+ chars, include uppercase, lowercase, number, and special character.";
      $step = 'password';
    } else {
      // Hash the new password securely using PASSWORD_DEFAULT algorithm
      $hashed = password_hash($password, PASSWORD_DEFAULT);

      // Prepare and execute SQL query to update user's password in the database
      $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
      mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);
      mysqli_stmt_execute($stmt);

      // Get user's IP address for security logging
      $ip = $_SERVER['REMOTE_ADDR'];
      if ($ip === '::1') {
        $ip = '127.0.0.1 (localhost)'; // Normalize localhost IPv6 to IPv4 representation
      }

      // Set current user session variable (assuming $user is defined elsewhere)
      $_SESSION['current_user'] = $user;

      // Default location string if IP info is not available
      $location = 'Unknown';

      // Fetch location details using ipinfo API
      $ipInfo = @file_get_contents("https://ipinfo.io/json?token=ac6ee70e825afc");
      if ($ipInfo !== false) {
        $data = json_decode($ipInfo, true);
        if (isset($data['city'], $data['region'], $data['country'])) {
          // Construct a readable location string
          $location = $data['city'] . ', ' . $data['region'] . ', ' . $data['country'];
        }
      }

      // Compose HTML email notifying user of successful password change with security details
      $html = '<div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f7f9fc; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
            <div style="text-align: center;">
                <h2 style="color: #34D399;">Password Changed Successfully ✅</h2>
                <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($user["name"]) . '</strong>,</p>
            </div>
            <p style="font-size: 15px; color: #444;">
                We wanted to let you know that your account password was changed successfully. If you made this change, no further action is required.
            </p>
            <hr style="margin: 30px 20px; border: none; border-top: 1px solid #eee;" />
            <h4 style="color: #333; font-size: 15px;">Security Details:</h4>
            <ul style="font-size: 14px; color: #555; line-height: 1.6;">
                <li><strong>Date & Time:</strong> ' . date("d M Y, h:i A") . '</li>
                <li><strong>IP Address:</strong> ' . $ip . '</li>
                <li><strong>Location:</strong> ' . htmlspecialchars($location) . '</li>
                <li><strong>Device/Browser:</strong> ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . '</li>
            </ul>
            <p style="font-size: 14px; color: #777;">
                If you did <strong>not</strong> make this change, please reset your password immediately or contact our support team for help.
            </p>
            <div style="text-align: center; margin-top: 30px;">
                <a href="https://my-tasking.wuaze.com/auth/reset-password.php" style="background-color: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 14px;">Reset Password</a>
            </div>
            <p style="font-size: 13px; color: #aaa; margin-top: 20px;">This email is intended to keep your account safe. You don’t need to reply.</p>
            <p style="font-size: 13px; color: #999; text-align: center; margin-top: 30px;">© ' . date('Y') . ' My Tasking. All rights reserved.</p>
        </div>';

      // Send the notification email about password change to the user
      sendMail($email, "Password Reset Alert", $html);

      // Clear the reset email session variable after successful password reset
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
    }
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>Reset Password - My Tasking – Secure & Smart Task Management System</title>
  <meta name="title" content="Reset Password - My Tasking – Secure & Smart Task Management System" />
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
  <!-- Container for the form -->
  <div class="my-3 form_container bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
    <!-- Show form only if $showForm is true -->
    <?php if ($showForm): ?>
      <h2 class="text-3xl font-bold text-gray-700 mb-6 text-center">Reset Password</h2>
      <form method="POST" class="grid gap-4">
        <!-- Hidden input to keep track of current step -->
        <input type="hidden" name="step" value="<?= $step ?>">

        <!-- Step: email input -->
        <?php if ($step === 'email'): ?>
          <div>
            <label class="text-sm text-gray-600 mb-2 block">Email</label>
            <input type="email" name="email" id="email" placeholder="Enter your email" class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
            <!-- Show email error if any -->
            <?php if ($emailError): ?><p class="text-red-500 text-sm mt-1"><?= $emailError ?></p><?php endif; ?>
          </div>
          <!-- Step: OTP input -->
        <?php elseif ($step === 'otp'): ?>
          <div>
            <label class="text-sm text-gray-600 mb-2 block">Enter OTP sent to your email</label>
            <input type="text" name="otp" id="otp" class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
            <!-- Show OTP error if any -->
            <?php if ($otpError): ?><p class="text-red-500 text-sm mt-1"><?= $otpError ?></p><?php endif; ?>
          </div>
          <!-- Step: Password input -->
        <?php elseif ($step === 'password'): ?>
          <div class="relative">
            <label class="text-sm text-gray-600 mb-2 block">New Password</label>
            <input type="password" name="password" id="password" placeholder="••••••••" class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" />
            <!-- Show password error if any -->
            <?php if ($passwordError): ?><p class="text-red-500 text-sm mt-1"><?= $passwordError ?></p><?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Submit button -->
        <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-3 rounded-xl shadow-md transition">
          Continue
        </button>
        <!-- Link to go back to email step if current step is otp -->
        <?php if ($step === 'otp'): ?>
          <a href="<?php $_SERVER['PHP_SELF'] ?>" class="w-full text-center text-blue-500 font-bold  rounded-xl  transition">
            Back to email
          </a>
        <?php endif; ?>
        <a href="/" class="w-full text-center text-blue-500 font-bold  rounded-xl  transition">
          Back to home
        </a>
      </form>
    <?php endif; ?>
    <!-- Display success message if set -->
    <?php if ($success): ?>
      <div class="text-green-600 text-center">
        <h2 class="text-green-500 text-center font-semibold">Password updated successfully</h2>
        <p class="text-center w-full"><a href='./login.php' class="text-blue-500">Login now</a></p>
      </div>
    <?php endif; ?>
  </div>

  <script>
    // Wait for DOM to load before running script
    document.addEventListener("DOMContentLoaded", function() {
      const form = document.querySelector("form");

      // Select input elements by ID (may or may not be present depending on step)
      const emailInput = document.querySelector("#email");
      const otpInput = document.querySelector("#otp");
      const passwordInput = document.querySelector("#password");

      // For each input, create a message div below it for validation feedback
      [emailInput, otpInput, passwordInput].forEach((input) => {
        if (!input) return; // Skip if input does not exist
        const msg = document.createElement("div");
        msg.classList.add("text-sm", "mt-1"); // Styling for message
        input.parentNode.appendChild(msg);
      });

      // If password input exists, add an eye icon button to toggle visibility
      if (passwordInput) {
        const wrapper = passwordInput.parentNode;
        const eyeBtn = document.createElement("button");
        eyeBtn.type = "button"; // Not submit button
        eyeBtn.innerHTML = '<i class="fas fa-eye"></i>'; // Eye icon
        eyeBtn.classList.add(
          "absolute",
          "right-3",
          "top-9",
          "text-gray-400",
          "hover:text-gray-600"
        );
        wrapper.appendChild(eyeBtn);

        // Toggle password visibility on click
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

      // Validate email format with regex
      function validateEmail() {
        if (!emailInput) return true; // No email input to validate
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

      // Validate OTP: must be exactly 6 digits
      function validateOTP() {
        if (!otpInput) return true; // No OTP input to validate
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

      // Validate password with complex regex requirements
      function validatePassword() {
        if (!passwordInput) return true; // No password input to validate
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

      // Set input as valid: green border and message
      function setValid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-green-500 text-sm mt-1";
        input.classList.remove("border-red-500", "border-gray-300");
        input.classList.add("border-green-500");
      }

      // Set input as invalid: red border and message
      function setInvalid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-red-500 text-sm mt-1";
        input.classList.remove("border-green-500", "border-gray-300");
        input.classList.add("border-red-500");
      }

      // Attach live input event listeners for validation feedback
      if (emailInput) emailInput.addEventListener("input", validateEmail);
      if (otpInput) otpInput.addEventListener("input", validateOTP);
      if (passwordInput) passwordInput.addEventListener("input", validatePassword);

      // On form submit, validate only visible inputs and prevent submit if invalid
      form?.addEventListener("submit", function(e) {
        let isValid = true;
        if (emailInput && isVisible(emailInput)) isValid = validateEmail() && isValid;
        if (otpInput && isVisible(otpInput)) isValid = validateOTP() && isValid;
        if (passwordInput && isVisible(passwordInput)) isValid = validatePassword() && isValid;

        if (!isValid) e.preventDefault();
      });

      // Utility function to check if element is visible in the DOM
      function isVisible(el) {
        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
      }
    });
  </script>

</body>

</html>