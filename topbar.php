<?php
require_once './config/config.php';   // Includes the database connection setup
require_once './auth/auth.php';       // Includes authentication functions such as login check

$currentUserData = $_SESSION['current_user'];  // Get current logged-in user data from session
$currentPage = basename($_SERVER['PHP_SELF']); // Get current PHP file name
?>


<div class="topbar flex items-center justify-between gap-x-4 w-full bg-white rounded-lg p-3">
  <div
    class="actionButtonsOnTopBar flex items-center justify-between  gap-x-2 text-white w-full">
    <!-- Link to task form with source page parameter based on current page -->
    <a href="./taskForm.php?sourcePage=<?php echo ($currentPage == 'myTasks.php') ? 'myTasks' : 'index'; ?>"
      
      class="actionButton addTaskButtonOnTopbar flex items-center justify-center gap-x-2">
      <i class="fa-solid fa-plus"></i> New task
    </a>
    
    <!-- User profile button with avatar or first letter of user name -->
    <p
      class="accounBtnOnTopbar cursor-pointer rounded-full bg-gray-200 w-11 h-11 flex items-center justify-center text-2xl"
      style="background: var(--primary-yellow)"
      onclick="toggleProfileCard()">
      <!-- Display avatar image if available, otherwise display first letter of name -->
      <?php if (!empty($currentUserData['avatar'])): ?>
        <img src="<?= htmlspecialchars($currentUserData['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover rounded-full" />
      <?php else: ?>
        <?= htmlspecialchars($currentUserData['name'][0]) ?>
      <?php endif; ?>
    </p>
  </div>
  
  <!-- Navbar toggle button for mobile/smaller screens -->
  <a href="https://my-tasking.wuaze.com/" class='topbar_logo hidden' style="width:90px">
    <img src="https://res.cloudinary.com/dn0hsbnpl/image/upload/v1750943751/task_manager/xjj9qj39iislmobzuehc.png" alt="Avatar" class="w-full h-full" />
  </a>
  <p
    class="navbarToggleButtonOnTopbar text-white hidden cursor-pointer rounded-full bg-gray-200 w-11 h-11 flex items-center justify-center"
    style="background: var(--primary-yellow)"
    // onclick="toggleNavbar()"
    >
    <i class="fa-solid fa-bars"></i>
  </p>
</div>
