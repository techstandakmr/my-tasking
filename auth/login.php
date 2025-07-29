<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - My Tasking – Secure & Smart Task Management System</title>
  <meta name="title" content="Login - My Tasking – Secure & Smart Task Management System" />
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
  date_default_timezone_set('UTC');
  session_start(); // Start the session to manage user login state
  require '../config/config.php'; // Include database configuration
  require "../sendMail.php"; // Include mail sending functions
  require_once '../api/functions.php'; // Include additional helper functions
  require_once __DIR__ . '/../vendor/autoload.php';

  use Dotenv\Dotenv;

  $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
  $dotenv->load();
  // Cleanup expired OTPs and delete unverified accounts before login attempts
  deleteExtraOTP($conn);
  deleteUnverifiedAccounts($conn);

  // Initialize error message variables for email and password
  $emailError = $passwordError = '';

  // Check if form was submitted via POST
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Trim and retrieve submitted email and password from POST data
    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars($_POST['password']);

    // Prepare SQL statement to select user by email securely (prevents SQL injection)
    $stmt = mysqli_prepare($conn, "SELECT id, password,name,is_verified,verification_token FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if a user record was found
    if ($result && mysqli_num_rows($result) > 0) {
      $user = mysqli_fetch_assoc($result);

      // Check if user is verified and has no pending verification token
      if ($user['is_verified'] == 1 && $user['verification_token'] == null) {
        // Verify the submitted password against hashed password in DB
        if (password_verify($password, $user['password'])) {
          // Generate a new login token for session management
          $newToken = bin2hex(random_bytes(16));
          //   date_default_timezone_set('UTC'); // Ensures time() matches MySQL UTC time
          // $now = date('Y-m-d H:i:s'); // UTC
          // Prepare and execute statement to update user token in DB
          $updateStmt = mysqli_prepare($conn, "UPDATE users SET token = ? WHERE id = ?");
          mysqli_stmt_bind_param($updateStmt, "ss", $newToken, $user['id']);
          mysqli_stmt_execute($updateStmt);




          // Retrieve the client's IP address; handle localhost (::1) case
          $ip = $_SERVER['REMOTE_ADDR'];
          if ($ip === '::1') {
            $ip = '127.0.0.1 (localhost)';
          };

          // Store user info in session for maintaining login state
          $_SESSION['current_user'] = $user;

          // Initialize location as Unknown before fetching from external API
          $location = 'Unknown';

          // Attempt to get user's location info based on IP from ipinfo.io API
          $ipInfo = @file_get_contents("https://ipinfo.io/json?token=ac6ee70e825afc");
          if ($ipInfo !== false) {
            $data = json_decode($ipInfo, true);
            if (isset($data['city'], $data['region'], $data['country'])) {
              $location = $data['city'] . ', ' . $data['region'] . ', ' . $data['country'];
            }
          }

          // Compose an HTML email notification about new login
          $html = '
              <div style="max-width: 600px; margin: auto; font-family: Arial, sans-serif; background-color: #f9fafb; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                <div style="text-align: center;">
                  <h2 style="color: #3B82F6;">Login Alert ⚠️</h2>
                  <p style="font-size: 16px; color: #333;">Hi <strong>' . htmlspecialchars($user['name']) . '</strong>,</p>
                </div>
                <p style="font-size: 15px; color: #444;">
                  We noticed a new login to your <strong>My Tasking</strong> account.
                </p>
                  <ul style="font-size: 14px; color: #555; line-height: 1.6;">
                    <li><strong>Date & Time:</strong> ' . date("d M Y, h:i A") . '</li>
                    <li><strong>IP Address:</strong> ' . $ip . '</li>
                    <li><strong>Location:</strong> ' . htmlspecialchars($location) . '</li>
                    <li><strong>Device/Browser:</strong> ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . '</li>
                  </ul>
                <p style="font-size: 14px; color: #777;">
                  If this was you, you can safely ignore this message. If you don\'t recognize this activity, please reset your password immediately.
                </p>
                <div style="text-align: center; margin-top: 30px;">
                  <a href=" https://my-tasking.wuaze.com/auth/reset-password.php" style="background-color: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 14px;">Reset Password</a>
                </div>
                <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;" />
                <p style="font-size: 13px; color: #999; text-align: center;">© ' . date('Y') . ' My Tasking. All rights reserved.</p>
              </div>';

          // Send the login alert email to the user
          sendMail($email, "Login Notification",  $html);

          // Set a secure HttpOnly cookie with the new token for persistent login (expires in 30 days)
          setcookie('task_manager_user_token', $newToken, time() + (86400 * 30), "/", "", true, true);

          // Redirect the logged-in user to the main index page
          header("Location:  {$_ENV['APP_URL']}");
          exit();

          // Note: session already stores user data here for page access control

        } else {
          // Password did not match; set appropriate error message
          $passwordError = "Incorrect password.";
        }
      } else {
        // User is not verified or has a verification token pending

        // Store user's name for email personalization
        $name = $user['name'];

        // Generate a new 64-character verification token
        $verification_token = bin2hex(random_bytes(32));

        // // Update the verification token in the database for the user
        $updateTokenStmt = mysqli_prepare($conn, "UPDATE users SET verification_token = ? WHERE id = ?");
        mysqli_stmt_bind_param($updateTokenStmt, "ss", $verification_token, $user['id']);
        mysqli_stmt_execute($updateTokenStmt);

        // Build the verification link including the token
        $verifyLink = " https://my-tasking.wuaze.com/auth/verifyEmail.php?token=$verification_token";

        // Email subject for verification
        $subject = "Verify Your Email";

        // Compose HTML email for email verification
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

        // Send the verification email and notify user if email was sent
        if (sendMail($email, $subject, $html)) {
          $emailError = "Email not verified. A new verification email has been sent.";
        };
      };
    } else {
      // No user found with the submitted email
      $emailError = "Email not found.";
    }
  }
  ?>


  <div class="flex items-center justify-center min-h-screen">
    <!-- Container for the login form with padding, rounded corners, and shadow -->
    <div class="my-3 form_container bg-white p-8 rounded-3xl shadow-2xl w-full max-w-md">
      <h2 class="text-3xl font-bold text-gray-700 mb-8 text-center">Login</h2>

      <!-- Login form, posts to itself -->
      <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="grid grid-cols-1 gap-4">
        <!-- Email input field -->
        <div class="relative">
          <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Email</label>
          <input type="email" name="email" placeholder="Email" class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" required />
          <!-- Display email error message if any -->
          <?php if ($emailError): ?>
            <div class="text-red-500 text-sm mt-1"><?= $emailError ?></div>
          <?php endif; ?>
        </div>

        <!-- Password input field -->
        <div class="relative">
          <label class="block text-sm font-medium text-gray-600 ml-1 mb-2">Password</label>
          <input type="password" name="password" placeholder="Password" class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition" required />
          <!-- Display password error message if any -->
          <?php if ($passwordError): ?>
            <div class="text-red-500 text-sm mt-1"><?= $passwordError ?></div>
          <?php endif; ?>

        </div>
        <!-- Submit button for login -->
        <button type="submit" class="w-full bg-yellow-400 hover:bg-yellow-500 active:scale-95 text-white font-bold py-3 rounded-xl shadow-md transition">
          Login
        </button>
      </form>
      <!-- Link for forgot password -->
      <p class="text-center text-sm text-gray-600 mt-4">
        Forgot <a href="./reset-password.php" class="text-yellow-500 font-semibold hover:underline">Password</a> ?
      </p>
      <!-- Link to sign up page -->
      <p class="text-center text-sm text-gray-600 mt-4">
        Don’t have an account?
        <a href="./signup.php" class="text-yellow-500 font-semibold hover:underline">Sign up</a>
      </p>
    </div>
  </div>

  <script>
    // Wait until DOM is fully loaded
    document.addEventListener("DOMContentLoaded", function() {
      const form = document.querySelector("form"); // Select the form element
      const inputs = form.querySelectorAll("input"); // Select all input fields
      const emailInput = inputs[0]; // Email input
      const passwordInput = inputs[1]; // Password input

      // Add eye icon button to toggle password visibility
      const wrapper = passwordInput;
      const eyeBtn = document.createElement("button");
      eyeBtn.type = "button"; // Button type to prevent form submission
      eyeBtn.innerHTML = '<i class="fas fa-eye"></i>'; // Initial eye icon
      eyeBtn.classList.add(
        "absolute",
        "right-3",
        "top-9",
        "text-gray-400",
        "hover:text-gray-600"
      );
      wrapper.appendChild(eyeBtn); // Add eye button inside password input container

      eyeBtn.addEventListener("click", function() {
        // Toggle input type between password and text
        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          eyeBtn.innerHTML = '<i class="fas fa-eye-slash"></i>'; // Change icon to eye-slash
        } else {
          passwordInput.type = "password";
          eyeBtn.innerHTML = '<i class="fas fa-eye"></i>'; // Change icon to eye
        }
      });

      // Create message elements below each input for validation feedback
      inputs.forEach((input) => {
        const message = document.createElement("div");
        message.classList.add("text-sm", "mt-1"); // Styling classes for messages
        input.parentNode.appendChild(message); // Append message div to input's parent
      });

      // Validate email format with regex
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

      // Validate password complexity with regex: 8+ chars, uppercase, lowercase, number, special char
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

      // Mark input and message as valid with green border and check icon
      function setValid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-green-500 text-sm mt-1";
        input.classList.remove("border-red-500", "border-gray-300");
        input.classList.add("border-green-500");
      }

      // Mark input and message as invalid with red border and cross icon
      function setInvalid(input, msgDiv, text) {
        msgDiv.innerHTML = text;
        msgDiv.className = "text-red-500 text-sm mt-1";
        input.classList.remove("border-green-500", "border-gray-300");
        input.classList.add("border-red-500");
      }

      // Add live validation event listeners to inputs
      emailInput.addEventListener("input", validateEmail);
      passwordInput.addEventListener("input", validatePassword);

      // On form submission, validate inputs and submit if valid
      form?.addEventListener("submit", function(e) {
        e.preventDefault(); // Prevent default form submit

        const validEmail = validateEmail();
        const validPassword = validatePassword();
        if (validEmail && validPassword) {
          form.submit(); // Submit form if all validations pass
        }
      });
    });
  </script>
</body>

</html>