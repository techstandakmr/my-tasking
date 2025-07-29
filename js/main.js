// Select the <body> element
let body = document.querySelector("body");
// Select the navbar element with class "navbar"
let navbar = document.querySelector(".navbar");

// Function to toggle scrolling on the body element by adding/removing overflow-hidden class
function toggleBodyScrolling() {
    if (!body.classList.contains("overflow-hidden")) {
        // If overflow-hidden class is not present, add it to disable scrolling
        body.classList.add("overflow-hidden");
    } else {
        // Otherwise remove it to enable scrolling
        body.classList.remove("overflow-hidden");
    };
};

// Function to toggle the navbar's active state and toggle body scrolling accordingly
function toggleNavbar() {
    // Toggle the "active" class on the navbar element (show/hide navbar)
    navbar.classList.toggle("active");
    // Toggle body scroll locking when navbar toggles
    toggleBodyScrolling();
}

// Function to toggle the profile card visibility and manage related UI states
function toggleProfileCard() {
    // Select container for profile picture options
    let profilePicOptionContainer = document.querySelector(".profilePicOptionContainer");
    // Select the profile card element
    let profile_card = document.querySelector(".profile_card");
    
    // If navbar is active (open), close it before toggling profile card
    if (navbar.classList.contains("active")) {
        navbar.classList.remove("active");
        // body.classList.add("overflow-hidden"); // commented out code
    };
    
    // Toggle the visibility of the profile card (show/hide)
    profile_card.classList.toggle("hidden");
    
    // If profile card is now hidden, toggle body scrolling back
    if (profile_card.classList.contains("hidden")) {
        toggleBodyScrolling();
    };
    
    // If profile picture options container is visible, hide it
    if (!profilePicOptionContainer.classList.contains("hidden")) {
        profilePicOptionContainer.classList.add("hidden");
    };
};

// Function to toggle visibility of the search input field
function toggleSearchInput() {
    // Select the search input element
    let search_input = document.querySelector(".search_input");
    // Toggle hidden class to show or hide the search input
    search_input.classList.toggle("hidden");
}
