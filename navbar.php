<?php
// Include the configuration file for database connection and other settings
include './config/config.php';

// Get the current script filename to determine active navigation state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="navbar overflow-hidden rounded-lg w-auto h-full">
    <div class="navbarInner flex items-center bg-white justify-between flex-col w-auto h-full overflow-y-auto">
        <div class="w-full h-auto block">
            <h2 class="text-lg font-semibold p-3 flex items-center justify-between w-full">
                <!-- Site logo -->
                <a href="https://my-tasking.wuaze.com/" style="width:70px">
                    <img src="https://res.cloudinary.com/dn0hsbnpl/image/upload/v1750943751/task_manager/xjj9qj39iislmobzuehc.png" alt="Avatar" class="w-full h-full" />
                </a>
                <!-- Navbar toggle button (hidden by default) -->
                <i class="fa-solid fa-xmark navbarToggleButtonOnNavbar" style="display: none" onclick="toggleNavbar()"></i>
            </h2>
            <div class="menu">
                <div class="menu-item">
                    <!-- Link to add a new task with dynamic sourcePage parameter based on current page -->
                    <a href="./taskForm.php?sourcePage=<?php echo ($currentPage == 'myTasks.php') ? 'myTasks' : 'index'; ?>"
                        class="cursor-pointer addTaskButtonOnNavbar text-left hidden flex items-center justify-start gap-x-2 p-3 w-full h-full block">
                        <i class="fa-solid fa-plus"></i> New task
                    </a>
                </div>
                <div class="menu-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <!-- Dashboard navigation link, highlights if current page is index.php -->
                    <a href="./"
                        class="cursor-pointer flex items-center gap-x-2 p-3 w-full h-full block">
                        <i class="fa-solid fa-table-columns"></i>
                        Dashboard
                    </a>
                </div>
                <div class="menu-item <?php echo ($currentPage == 'myTasks.php') ? 'active' : ''; ?>">
                    <!-- My tasks navigation link, highlights if current page is myTasks.php -->
                    <a href="./myTasks.php"
                        class="cursor-pointer flex items-center gap-x-2 p-3 w-full h-full block">
                        <i class="fa-solid fa-list-check"></i>
                        My tasks
                    </a>
                </div>
                <div class="menu-item <?php echo ($currentPage == 'notification.php') ? 'active' : ''; ?>">
                    <!-- Notification navigation link, highlights if current page is notification.php -->
                    <a href="./notification.php"
                        class="cursor-pointer flex items-center gap-x-2 p-3 w-full h-full block">
                        <i class="fa-solid fa-bell"></i>
                        Notification
                    </a>
                </div>
            </div>
        </div>
        <div class="w-full h-auto">
            <!-- Logout link -->
            <a href="./auth/logout.php" class="menu-item cursor-pointer flex items-center gap-x-2 p-3">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                Logout
            </a>
            <!-- Account button, initially hidden, toggles profile card visibility -->
            <div class="accounBtnOnNavbar hidden menu-item cursor-pointer flex items-center gap-x-2 p-3"
                onclick="toggleProfileCard()">
                <i class="fa-solid fa-user"></i> Account
            </div>
        </div>
    </div>
</div>