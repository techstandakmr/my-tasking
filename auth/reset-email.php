<?php
// Include the database configuration file
require '../config/config.php';

// Include authentication functions
require '../auth/auth.php';

// Include the email sending script
require "../sendMail.php";

// Include utility or helper functions (possibly for OTP/account handling)
require_once '../api/functions.php';

// Delete expired or unused OTP records from the database
deleteExtraOTP($conn);

// Delete user accounts that are not verified within a certain time frame
deleteUnverifiedAccounts($conn);

// Initialize error variables for different steps of the process
$emailError = $otpError = $passwordError = $newEmailError = '';

// Set the current step (e.g., email entry, OTP verification, etc.)
$step = 'email';

// Store the current email address being processed (initially empty)
$currentEmail = '';

// Store the success message (if any)
$success = false;

// Control the visibility of the form (default is visible)
$showForm = true;

// Get the current logged-in user's session data (if available)
$currentUser = $_SESSION['current_user'] ?? null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // ===== STEP 1: Submit Email + Password =====
  if ($_POST['step'] === 'email') {

    // Trim and retrieve the email and password from the submitted form
    $currentEmail = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars($_POST['password']);

    // Prepare and execute SQL to fetch user details using the email
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $currentEmail);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if the email exists in the database
    if ($result && mysqli_num_rows($result) > 0) {
      $currentUser = mysqli_fetch_assoc($result);

      // Verify the entered password with the hashed password stored in the database
      if (password_verify($password, $currentUser['password'])) {

        // Store verified email and user ID in the session for later use
        $_SESSION['account_email'] = $currentEmail;
        $_SESSION['account_id'] = $currentUser['id'];

        // Generate a 6-digit OTP and set its expiration time (5 minutes from now)
        $otp = rand(100000, 999999);
        // Check if an existing OTP already exists for this email and action type
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM otp WHERE email = ? AND action_type = 'reset_email'");
        mysqli_stmt_bind_param($checkStmt, "s", $currentEmail);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        // If OTP exists, update it with the new OTP and expiry
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
          $updateStmt = mysqli_prepare($conn, "UPDATE otp SET otp = ? WHERE email = ? AND action_type = 'reset_email'");
          mysqli_stmt_bind_param($updateStmt, "ss", $otp, $currentEmail);
          $otpUpdated = mysqli_stmt_execute($updateStmt);
        } else {
          // If no OTP exists, insert a new OTP entry
          $insertStmt = mysqli_prepare($conn, "INSERT INTO otp (email, otp, action_type) VALUES (?, ?, 'reset_email')");
          mysqli_stmt_bind_param($insertStmt, "ss", $currentEmail, $otp);
          $otpUpdated = mysqli_stmt_execute($insertStmt);
        }

        // If OTP is successfully inserted/updated
        if ($otpUpdated) {
          // Prepare the HTML content for the OTP email
          $html = '<div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f7f9fc; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
        <div style="text-align: center;">
          <h2 style="color: #FACC15;">Email Change Verification</h2>
          <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($currentUser["name"]) . '</strong>,</p>
        </div>
        <p style="font-size: 15px; color: #444;">
          You requested to reset your email. Please use the OTP below to proceed with changing your account email:
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

          // Attempt to send the email with the OTP
          if (sendMail($currentEmail, "Verify OTP to Change Email", $html)) {
            // If email sent, move to next step (OTP verification)
            $step = 'otp';
          } else {
            // Handle case where email sending fails
            $emailError = "Failed to send OTP email.";
          }
        } else {
          // Handle case where OTP could not be saved in the database
          $emailError = "Failed to generate OTP.";
        }
      } else {
        // Handle case of incorrect password
        $passwordError = "Incorrect password.";
      }
    } else {
      // Handle case of email not registered in the system
      $emailError = "Email not registered.";
    }

    // ===== STEP 2: Submit OTP =====
  } // ===== STEP 2: Verify OTP =====
  elseif ($_POST['step'] === 'otp') {
    // Get the current email stored in session during the OTP request
    $currentEmail = $_SESSION['account_email'] ?? '';

    // Get the OTP entered by the user and trim any whitespace
    $inputOtp = (int) trim($_POST['otp']);

    // Get the current time
    $now = time() - 300;

    // Prepare SQL statement to find a matching OTP for the given email that hasn't expired
    $stmt = mysqli_prepare($conn, "SELECT * FROM otp WHERE email = ? AND otp = ? AND action_type = 'reset_email' AND UNIX_TIMESTAMP(updated_at) >= ?");

    // Bind parameters to the prepared statement
    mysqli_stmt_bind_param($stmt, "sss", $currentEmail, $inputOtp, $now);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Get the result of the query
    $result = mysqli_stmt_get_result($stmt);

    // If a matching OTP is found and not expired
    if ($result && mysqli_num_rows($result) > 0) {
      // Proceed to the next step: asking for new email
      $step = 'new_email';
    } else {
      // OTP is invalid or expired, show error and stay on OTP step
      $otpError = "Invalid or expired OTP.";
      $step = 'otp';
    }

    // ===== STEP 3: Submit New Email =====
  } // ===== STEP 3: Submit New Email =====
  elseif ($_POST['step'] === 'new_email') {
    // Get the new email input and trim whitespace
    $newEmail = htmlspecialchars(trim($_POST['new_email']));

    // Get the current user ID from session
    $userId = $_SESSION['account_id'] ?? '';

    // Validate the format of the new email
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
      // If email is invalid, set error message and stay on new_email step
      $newEmailError = "Enter a valid email address.";
      $step = 'new_email';
    } else {
      // Check if the new email already exists in the users table
      $checkEmailStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
      mysqli_stmt_bind_param($checkEmailStmt, "s", $newEmail);
      mysqli_stmt_execute($checkEmailStmt);
      $checkEmailResult = mysqli_stmt_get_result($checkEmailStmt);

      // If email is already taken, set error and stay on current step
      if ($checkEmailResult && mysqli_num_rows($checkEmailResult) > 0) {
        $newEmailError = "This email is already taken.";
        $step = 'new_email';
      } else {
        // Get the user's IP address
        $ip = $_SERVER['REMOTE_ADDR'];

        // If the IP is localhost (::1), change it to readable format
        if ($ip === '::1') {
          $ip = '127.0.0.1 (localhost)';
        };

        // Default location to 'Unknown'
        $location = 'Unknown';

        // Attempt to get IP-based location info using ipinfo.io API
        $ipInfo = @file_get_contents("https://ipinfo.io/json?token=ac6ee70e825afc");
        if ($ipInfo !== false) {
          $data = json_decode($ipInfo, true);
          if (isset($data['city'], $data['region'], $data['country'])) {
            // Format location as City, Region, Country
            $location = $data['city'] . ', ' . $data['region'] . ', ' . $data['country'];
          }
        };

        // Construct confirmation email HTML to send to the new email
        $htmlForNewEmail = '<div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
              <div style="text-align: center;">
                <h2 style="color: #3B82F6;">Email Changed ✅</h2>
                <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($_SESSION['name']) . '</strong>,</p>
              </div>
              <p style="font-size: 15px; color: #444;">
                This is a confirmation that your email address for your <strong>My Tasking</strong> account has been changed successfully.
              </p>
              <ul style="font-size: 14px; color: #555; line-height: 1.6;">
                <li><strong>New Email:</strong> ' . htmlspecialchars($newEmail) . '</li>
                <li><strong>Date & Time:</strong> ' . date("d M Y, h:i A") . '</li>
                <li><strong>IP Address:</strong> ' . $ip . '</li>
                <li><strong>Location:</strong> ' . htmlspecialchars($location) . '</li>
                <li><strong>Device/Browser:</strong> ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . '</li>
              </ul>
              <p style="font-size: 14px; color: #777;">
                If you made this change, no further action is needed. If you did <strong>not</strong> request this change, please reset your password immediately to secure your account.
              </p>
              <div style="text-align: center; margin-top: 30px;">
                <a href="https://my-tasking.wuaze.com/auth/reset-password.php" style="background-color: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 14px;">Reset Password</a>
              </div>
              <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
              <p style="font-size: 13px; color: #999; text-align: center;">© ' . date('Y') . ' My Tasking. All rights reserved.</p>
            </div>';

        // Send confirmation email to new email
        sendMail($newEmail, "Email Changed ✅", $htmlForNewEmail);

        // Construct confirmation email for old email address
        $htmlForOldEmail = '
              <div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                <div style="text-align: center;">
                  <h2 style="color: #3B82F6;">Email Changed ✅</h2>
                  <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($currentUser['name']) . '</strong>,</p>
                </div>
                <p style="font-size: 15px; color: #444;">
                  Your email address for your <strong>My Tasking</strong> account has been successfully updated.
                </p>
                <ul style="font-size: 14px; color: #555; line-height: 1.6;">
                  <li><strong>Old Email:</strong> ' . htmlspecialchars($currentEmail) . '</li>
                  <li><strong>New Email:</strong> ' . htmlspecialchars($newEmail) . '</li>
                  <li><strong>Date & Time:</strong> ' . date("d M Y, h:i A") . '</li>
                  <li><strong>IP Address:</strong> ' . $ip . '</li>
                  <li><strong>Location:</strong> ' . htmlspecialchars($location) . '</li>
                  <li><strong>Device/Browser:</strong> ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . '</li>
                </ul>
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
                <p style="font-size: 13px; color: #999; text-align: center;">© ' . date('Y') . ' My Tasking. All rights reserved.</p>
              </div>';

        // Send confirmation email to old email address
        sendMail($currentUser['email'], "Email Changed ✅", $htmlForOldEmail);

        // Prepare statement to update the user's email in the database
        $updateEmailStmt = mysqli_prepare($conn, "UPDATE users SET email = ? WHERE id = ?");
        mysqli_stmt_bind_param($updateEmailStmt, "si", $newEmail, $userId);

        // If email update is successful
        if (mysqli_stmt_execute($updateEmailStmt)) {
          // Delete the used OTP from the database
          $deleteOtpStmt = mysqli_prepare($conn, "DELETE FROM otp WHERE email = ?");
          mysqli_stmt_bind_param($deleteOtpStmt, "s", $_SESSION['account_email']);
          mysqli_stmt_execute($deleteOtpStmt);

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
          // If update fails, show error and stay on same step
          $newEmailError = "Failed to update email. Try again.";
          $step = 'new_email';
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <!-- Meta tags for character encoding and responsiveness -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>Update Email - My Tasking – Secure & Smart Task Management System</title>
  <meta name="title" content="Update Email - My Tasking – Secure & Smart Task Management System" />
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
  <!-- Main container for the update email form -->
  <div class="form_container bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
    <!-- Show form conditionally -->
    <?php if ($showForm): ?>
      <!-- Heading -->
      <h2 class="text-3xl font-bold text-gray-700 mb-2 text-center">Update Email</h2>
      <p class="text-md text-gray-700 text-center">
        <?php if ($step == 'email'): ?>
          Please confirm your account
        <?php endif; ?>
      </p>

      </p>
      <form method="POST" class="grid gap-4">
        <!-- Hidden field to maintain step in form -->
        <input type="hidden" name="step" value="<?= $step ?>">

        <!-- Step: Email input and password -->
        <?php if ($step === 'email'): ?>
          <div>
            <label class="text-sm text-gray-600 mb-1 block">Email</label>
            <input type="email" name="email" id="email" class="w-full px-4 py-2 rounded-xl border" />
            <!-- Email error message -->
            <?php if ($emailError): ?><p class="text-red-500 text-sm"><?= $emailError ?></p><?php endif; ?>
          </div>
          <div class="relative">
            <label class="text-sm text-gray-600 mb-1 block">Password</label>
            <input type="password" name="password" id="password" class="w-full px-4 py-2 rounded-xl border" />
            <!-- Password error message -->
            <?php if ($passwordError): ?><p class="text-red-500 text-sm"><?= $passwordError ?></p><?php endif; ?>
          </div>

          <!-- Step: OTP input -->
        <?php elseif ($step === 'otp'): ?>
          <div>
            <label class="text-sm text-gray-600 mb-1 block">Enter OTP sent to your email</label>
            <input type="text" name="otp" id="otp" maxlength="6" class="w-full px-4 py-2 rounded-xl border" />
            <!-- OTP error message -->
            <?php if ($otpError): ?><p class="text-red-500 text-sm"><?= $otpError ?></p><?php endif; ?>
          </div>

          <!-- Step: New Email input -->
        <?php elseif ($step === 'new_email'): ?>
          <div>
            <label class="text-sm text-gray-600 mb-1 block">New Email</label>
            <input type="email" id="email" name="new_email" class="w-full px-4 py-2 rounded-xl border" />
            <!-- New Email error message -->
            <?php if ($newEmailError): ?><p class="text-red-500 text-sm"><?= $newEmailError ?></p><?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Submit Button -->
        <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-3 rounded-xl">
          Continue
        </button>

        <!-- Forgot password and back to home links -->
        <p class="text-center text-sm text-gray-600 mt-1">
          Forgot <a href="./reset-password.php" class="text-yellow-500 font-semibold hover:underline">Password</a> ?
        </p>
        <a href="/" class="text-center text-blue-500 font-medium">Back to Home</a>
      </form>
    <?php endif; ?>
    <!-- Success message display -->
    <?php if ($success): ?>
      <div class="text-green-600 text-center text-lg">
        <h2 class="text-green-500 text-center font-semibold">Email updated successfully</h2>
        <p class="text-center w-full"><a href='./login.php' class="text-blue-500">Login now</a></p>
      </div>
    <?php endif; ?>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const form = document.querySelector("form");

      // Select form fields by ID if present
      const emailInput = document.querySelector("#email");
      const otpInput = document.querySelector("#otp");
      const passwordInput = document.querySelector("#password");

      // Add a message container div for each existing input
      [emailInput, otpInput, passwordInput].forEach((input) => {
        if (!input) return;
        const msg = document.createElement("div");
        msg.classList.add("text-sm", "mt-1");
        input.parentNode.appendChild(msg);
      });

      // Add eye icon toggle button for password visibility
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

      // Validate email format using regex
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

      // Validate OTP format: 6-digit number
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

      // Validate password strength
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

      // Mark input field as valid with green border and icon
      function setValid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-green-500 text-sm mt-1";
        input.classList.remove("border-red-500", "border-gray-300");
        input.classList.add("border-green-500");
      }

      // Mark input field as invalid with red border and icon
      function setInvalid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-red-500 text-sm mt-1";
        input.classList.remove("border-green-500", "border-gray-300");
        input.classList.add("border-red-500");
      }

      // Attach input event listeners for live validation
      if (emailInput) emailInput.addEventListener("input", validateEmail);
      if (otpInput) otpInput.addEventListener("input", validateOTP);
      if (passwordInput) passwordInput.addEventListener("input", validatePassword);

      // On form submit, validate visible inputs only
      form?.addEventListener("submit", function(e) {
        let isValid = true;
        if (emailInput && isVisible(emailInput)) isValid = validateEmail() && isValid;
        if (otpInput && isVisible(otpInput)) isValid = validateOTP() && isValid;
        if (passwordInput && isVisible(passwordInput)) isValid = validatePassword() && isValid;

        // Prevent form submission if any input is invalid
        if (!isValid) e.preventDefault();
      });

      // Utility function to check element visibility in DOM
      function isVisible(el) {
        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
      }
    });
  </script>
</body>

</html>