<?php
// Get current user data from session
$currentUserData = $_SESSION['current_user'];
?>
<!-- Profile Card Modal Container (hidden by default) -->
<div class="profile_card hidden model_box fixed top-0 left-0 bottom-0 right-0 w-full flex items-center justify-center overflow-y-auto">
    <div class="model_box_inner w-auto m-auto h-auto bg-white rounded-3xl shadow-2xl">
        <!-- Close button for profile card -->
        <div class="w-auto px-8 pt-4 cursor-pointer text-xl rounded-full text-yellow-400" onclick="toggleProfileCard()">
            <i class="fa-solid fa-xmark"></i>
        </div>
        <div class="profileCardInner p-8 w-full h-full">
            <!-- Profile Image Section -->
            <div class="flex justify-center w-full relative">
                <div class="w-24 h-24 rounded-full border-4 border-yellow-400 shadow-md flex items-center justify-center overflow-hidden bg-white text-4xl text-yellow-400">
                    <?php if (!empty($currentUserData['avatar'])): ?>
                        <!-- Show user's avatar image if exists -->
                        <img src="<?= htmlspecialchars($currentUserData['avatar']) ?>" alt="Avatar" class="w-full h-full object-cover rounded-full" />
                    <?php else: ?>
                        <!-- Show first letter of user's name as fallback -->
                        <?= htmlspecialchars($currentUserData['name'][0]) ?>
                    <?php endif; ?>
                    <!-- Edit profile picture button -->
                    <button onclick="toggleProfilePicOption()" type="button" class="cursor-pointer absolute bottom-0 right-0 w-7 h-7 text-white flex items-center justify-center bg-yellow-400 rounded-full" style="font-size: 12px; left: 55%">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                </div>
            </div>

            <!-- User Name and Title -->
            <div class="text-center mt-4">
                <h2 class="text-xl font-bold text-gray-800">Name : <?= htmlspecialchars($currentUserData['name']) ?></h2>
                <p class="text-sm text-gray-500">Title : <?= htmlspecialchars($currentUserData['title'] ?? 'No title') ?></p>
            </div>

            <!-- User Description -->
            <p class="text-center text-gray-600 mt-1 text-sm">
                Description : <?= htmlspecialchars($currentUserData['description'] ?? 'No description available.') ?>
            </p>
            <!-- User Email -->
            <p class="text-center text-gray-600 mt-1 text-sm">
                Email : <?= htmlspecialchars($currentUserData['email']) ?>
            </p>
            <!-- Action Buttons Container -->
            <div class="mt-4 mx-auto flex justify-center items-center gap-x-4 actionButton">
                <!-- Link to edit profile -->
                <a href="./profileEditForm.php" target="_blank" class="flex flex-col gap-y-0.5 text-md">
                    <p class="flex items-center justify-center gap-x-2">
                        <i class="fa-solid fa-pen"></i>
                    </p>
                    Edit Profile
                </a>
                <!-- Link to reset email -->
                <a href="./auth/reset-email.php" class="flex flex-col gap-y-0.5 text-md">
                    <p class="flex items-center justify-center gap-x-2">
                        <i class="fa-solid fa-envelope"></i>
                    </p>
                    Reset Email
                </a>
                <!-- Link to reset password -->
                <a href="./auth/reset-password.php" class="flex flex-col gap-y-0.5 text-md">
                    <p class="flex items-center justify-center gap-x-2">
                        <i class="fa-solid fa-lock"></i>
                    </p>
                    Reset Password
                </a>
                <!-- Link to logout -->
                <a href="./auth/logout.php" class="flex flex-col gap-y-0.5 text-md">
                    <p class="flex items-center justify-center gap-x-2">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </p>
                    Logout
                </a>
                <!-- Link to delete account -->
                <a href="./auth/delete-account.php" class="flex flex-col gap-y-0.5 text-md">
                    <p class="flex items-center justify-center gap-x-2">
                        <i class="fa-solid fa-trash"></i>
                    </p>
                    Delete A/C
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Options Modal (hidden by default) -->
<div class="model_box profilePicOptionContainer hidden fixed top-0 left-0 bottom-0 right-0 w-full flex items-center justify-center overflow-y-auto">
    <div class="model_box_inner m-auto w-auto h-auto actionButton">
        <div class="w-full h-full text-center text-lg p-7">
            <div class="w-full flex items-center justify-center gap-x-2 ">
                <!-- Close profile picture options -->
                <p class="flex flex-col" onclick="toggleProfilePicOption()">
                    <button class="btn cursor-pointer">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <span class="option-text">Close</span>
                </p>
                <!-- Upload new profile picture from gallery -->
                <p class="flex flex-col cursor-pointer">
                    <label class="file-upload-btn cursor-pointer">
                        <i class="fa-solid fa-upload"></i>
                        <input type="file" accept="image/*" id="imageUploader" class="hidden" />
                    </label>
                    <span class="option-text">Gallery</span>
                </p>
                <?php if (!empty($currentUserData['avatar'])): ?>
                    <!-- Option to remove current avatar if exists -->
                    <a href="./api/delete-avatar.php" class="flex flex-col cursor-pointer">
                        <button class="btn cursor-pointer">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        <span class="option-text">Remove</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Profile Picture Cropping Container (hidden by default) -->
<div class="profileCardContainer hidden">
    <div class="profileCardContainerInner">
        <div class="croppingSection profileCardPreviewContainer bg-dark w-full h-full flex justify-between items-center relative">
            <!-- Toolbar with cancel and confirm cropping buttons -->
            <div class="toolbar">
                <button id="cancelBtn" class="text-xl cursor-pointer text-gray-500 hover:text-black">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <button id="confirmBtn" class="text-xl cursor-pointer text-gray-500 hover:text-black">
                    <i class="fa-solid fa-check"></i>
                </button>
            </div>
            <!-- Preview box for cropped image -->
            <div class="previewBox text-gray-600 flex justify-center items-center">
                <div id="croppingContainer" class='border-1 border-gray-400 croppingContainer relative w-full h-auto overflow-hidden'>
                    <!-- Image to be cropped -->
                    <img id="previewImage" src="" />
                    <!-- Crop box with resize handles -->
                    <div id="cropBox" class="cropBox">
                        <div class="resizeHandleCropBox top-left"></div>
                        <div class="resizeHandleCropBox top-right"></div>
                        <div class="resizeHandleCropBox bottom-left"></div>
                        <div class="resizeHandleCropBox bottom-right"></div>
                    </div>
                    <!-- Hidden canvas element for cropping logic -->
                    <canvas id="canvas" class="hidden"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Predefined sizes for different image aspect ratios
    const sizes = {
        square: 400,
        wide: 640,
        moderate: 480,
        tall: 210,
        miniTall: 180,
        mini2Tall: 110
    };

    // Calculate proportional height based on new width to maintain aspect ratio
    function calculateReductions(originalWidth, originalHeight, newWidth) {
        return (originalHeight * newWidth) / originalWidth;
    }

    // Determine new width based on the aspect ratio of the original image
    function getNewWidth(width, height) {
        let newWidth;

        if (width === height) {
            // Square image
            newWidth = sizes.square;
        } else if (width > height * 1.75) {
            // Very wide image
            newWidth = sizes.wide;
        } else if (width > height) {
            // Moderately wide image
            newWidth = sizes.moderate;
        } else if (height > width * 3.5) {
            // Very tall image
            newWidth = sizes.mini2Tall;
        } else if (height > width * 3) {
            // Moderately tall image
            newWidth = sizes.miniTall;
        } else {
            // Default tall size
            newWidth = sizes.tall;
        }

        return newWidth;
    }

    // DOM references for profile card elements and cropping UI
    const profile_card = document.querySelector(".profile_card");
    const profile_avatar = document.querySelectorAll(".profile_card img, .accounBtnOnTopbar img");
    const profilePicOptionContainer = document.querySelector(".profilePicOptionContainer");
    const imageUploader = document.getElementById("imageUploader");
    const profileCardContainer = document.querySelector(".profileCardContainer");
    const previewImage = document.getElementById("previewImage");
    const croppingContainer = document.getElementById("croppingContainer");
    const cropBox = document.getElementById("cropBox");
    const canvas = document.getElementById("canvas");

    const confirmBtn = document.getElementById("confirmBtn");
    const cancelBtn = document.getElementById("cancelBtn");

    // Function to handle the cropping interaction logic
    function handleCropping() {
        // Flags and variables to track touch and resizing states
        let isTouch = false,
            isResizing = false,
            diffX = 0,
            diffY = 0,
            cropBoxStartWidth, cropBoxStartHeight;
        const ctx = canvas?.getContext("2d");
        const img = previewImage;

        // Function to handle moving the crop box by dragging
        function moving(e) {
            const mainRect = croppingContainer.getBoundingClientRect();
            const boxRect = cropBox.getBoundingClientRect();
            // Use touch or mouse coordinates depending on input type
            const posX = isTouch ? e.touches[0].clientX : e.clientX;
            const posY = isTouch ? e.touches[0].clientY : e.clientY;

            // Calculate difference between pointer and crop box top-left corner
            diffX = posX - boxRect.left;
            diffY = posY - boxRect.top;

            // Function to handle the drag movement continuously
            function dragMove(evt) {
                if (isResizing) return; // Do not move if resizing

                const moveX = isTouch ? evt.touches[0].clientX : evt.clientX;
                const moveY = isTouch ? evt.touches[0].clientY : evt.clientY;

                // Calculate new top-left coordinates within cropping container bounds
                let aX = moveX - diffX - mainRect.left;
                let aY = moveY - diffY - mainRect.top;

                // Constrain movement so crop box stays inside the container
                aX = Math.max(0, Math.min(aX, mainRect.width - boxRect.width));
                aY = Math.max(0, Math.min(aY, mainRect.height - boxRect.height));
                cropBox.style.transform = "none";
                cropBox.style.left = `${aX}px`;
                cropBox.style.top = `${aY}px`;
                evt.preventDefault();
            }

            // Attach drag move and end listeners depending on input type
            document.addEventListener(isTouch ? "touchmove" : "mousemove", dragMove, {
                passive: false
            });
            document.addEventListener(isTouch ? "touchend" : "mouseup", () => {
                document.removeEventListener(isTouch ? "touchmove" : "mousemove", dragMove);
            });
        }

        // Function to handle resizing of the crop box by dragging the resize handle
        function resizing(e) {
            const mainRect = croppingContainer.getBoundingClientRect();
            const boxRect = cropBox.getBoundingClientRect();
            const posX = isTouch ? e.touches[0].clientX : e.clientX;
            const posY = isTouch ? e.touches[0].clientY : e.clientY;

            const startPosX = posX;
            const startPosY = posY;

            // Store initial crop box dimensions at start of resize
            cropBoxStartWidth = cropBox.offsetWidth;
            cropBoxStartHeight = cropBox.offsetHeight;

            // Function to handle resizing movement continuously
            function resizeMove(evt) {
                const currentX = isTouch ? evt.touches[0].clientX : evt.clientX;
                const currentY = isTouch ? evt.touches[0].clientY : evt.clientY;

                // Calculate changes in pointer position
                const dx = currentX - startPosX;
                const dy = currentY - startPosY;

                // Calculate new size, keep it square by taking min of width and height changes
                let newSize = Math.min(cropBoxStartWidth + dx, cropBoxStartHeight + dy);

                // Ensure crop box does not exceed cropping container bounds
                newSize = Math.min(newSize, mainRect.width - boxRect.left + mainRect.left, mainRect.height - boxRect.top + mainRect.top);
                cropBox.style.transform = "none";
                cropBox.style.width = `${newSize}px`;
                cropBox.style.height = `${newSize}px`;
                evt.preventDefault();
            }

            // Attach resize move and end listeners depending on input type
            document.addEventListener(isTouch ? "touchmove" : "mousemove", resizeMove, {
                passive: false
            });
            document.addEventListener(isTouch ? "touchend" : "mouseup", () => {
                document.removeEventListener(isTouch ? "touchmove" : "mousemove", resizeMove);
                isResizing = false;
            });
        }

        // Add event listeners for moving the crop box (excluding resize handle)
        cropBox.addEventListener("mousedown", (e) => {
            if (!e.target.classList.contains("resizeHandleCropBox")) {
                isTouch = false;
                moving(e);
            }
        });
        cropBox.addEventListener("touchstart", (e) => {
            if (!e.target.classList.contains("resizeHandleCropBox")) {
                isTouch = true;
                moving(e);
            }
        });

        // Add event listeners for resizing the crop box via resize handle
        cropBox.addEventListener("mousedown", (e) => {
            if (e.target.classList.contains("resizeHandleCropBox")) {
                isTouch = false;
                isResizing = true;
                resizing(e);
            }
        });
        cropBox.addEventListener("touchstart", (e) => {
            if (e.target.classList.contains("resizeHandleCropBox")) {
                isTouch = true;
                isResizing = true;
                resizing(e);
            }
        });

        // Check if crop box has non-default styles (indicating cropping is needed)
        let needToCrop = cropBox.style?.width !== "" || cropBox.style?.height !== "" || cropBox.style?.left !== "" || cropBox.style?.top !== "";

        // Confirm button event handler to crop image and upload it
        confirmBtn.addEventListener("click", async () => {
            const mainRect = croppingContainer.getBoundingClientRect();
            const boxRect = cropBox.getBoundingClientRect();

            // Calculate scale factors between natural image size and displayed container size
            const scaleX = previewImage.naturalWidth / mainRect.width;
            const scaleY = previewImage.naturalHeight / mainRect.height;

            // Calculate cropping rectangle in natural image coordinates
            const cropX = (boxRect.left - mainRect.left) * scaleX;
            const cropY = (boxRect.top - mainRect.top) * scaleY;
            const cropWidth = boxRect.width * scaleX;
            const cropHeight = boxRect.height * scaleY;

            // Check if crop box has valid size and position
            let isReadyImage = boxRect.left !== 0 && boxRect.top !== 0 && boxRect.width !== 0 && boxRect.height !== 0;
            if (!isReadyImage) return alert("Crop box not valid.");

            const canvas = document.getElementById("canvas");
            const ctx = canvas.getContext("2d");

            // Set canvas size to twice crop box size for better quality
            canvas.width = boxRect.width * 2;
            canvas.height = boxRect.height * 2;

            // Clear canvas before drawing cropped image
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw cropped image on canvas, scaling to canvas size
            ctx.drawImage(previewImage, cropX, cropY, cropWidth, cropHeight, 0, 0, canvas.width, canvas.height);

            // Convert canvas to data URL and then to blob for uploading
            const croppedDataURL = canvas.toDataURL("image/png");
            const blob = await (await fetch(croppedDataURL)).blob();
            const formData = new FormData();
            formData.append("croppedImage", blob, "cropped.png");

            // Upload cropped image to server API
            fetch("https://my-tasking.wuaze.com/api/upload-avatar.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    // Reload page on success
                    window.location.reload();
                })
                .catch(err => {
                    // Reload page and log error on failure
                    window.location.reload();
                    console.log("Network error: " + err.message);
                    // profileCardContainer.classList.add("hidden");
                });
        });
    };

    // Event listener for image file input change
    imageUploader.addEventListener("change", function(e) {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith("image/")) return; // Only proceed if file is an image

        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                const {
                    width,
                    height
                } = img;
                // Calculate new display width and height based on aspect ratio
                const newWidth = getNewWidth(width, height);
                const newHeight = calculateReductions(width, height, newWidth);

                // Set preview image source and cropping container width
                previewImage.src = event.target.result;
                croppingContainer.style.width = `${newWidth}px`;

                // Show cropping UI elements
                profileCardContainer.classList.toggle("hidden");
                profile_card.classList.toggle("hidden");
                profilePicOptionContainer.classList.toggle("hidden");

                // Initialize cropping functionality
                handleCropping();
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file); // Read file as data URL to load into preview image
    });

    // Cancel button hides cropping UI and resets preview image and container size
    cancelBtn.addEventListener("click", function() {
        profileCardContainer.classList.toggle("hidden");
        previewImage.src = "";
        croppingContainer.style.width = "0";
    });

    // Toggle visibility of profile picture options container
    function toggleProfilePicOption() {
        let profilePicOptionContainer = document.querySelector(".profilePicOptionContainer");
        profilePicOptionContainer.classList.toggle("hidden");
    };

    // // Function to delete profile image (commented out)
    // function deleteProfileImage() {
    //     if (!confirm("Are you sure you want to delete your profile picture?")) return;
    //     profile_card.classList.toggle("hidden");
    //     profilePicOptionContainer.classList.toggle("hidden");
    //     fetch('./api/delete-avatar.php', {
    //             method: 'POST',
    //             headers: {
    //                 'X-Requested-With': 'XMLHttpRequest'
    //             }
    //         })
    //         .then(res => res?.json())
    //         .then(data => {
    //             console.log("Raw text response:", data);
    //             if (data?.success) {
    //                 window.location.reload();
    //             } else {
    //                 window.location.reload();
    //             }
    //         })
    //         .catch(err => {
    //             alert('Something went wrong.');
    //             console.error(err);
    //         });
    // }
</script>
