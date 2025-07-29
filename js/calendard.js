// Get DOM elements for month/year display, calendar days container, and navigation buttons
const monthYear = document.getElementById('monthYear');
const calendarDays = document.getElementById('calendarDays');
const prevBtn = document.getElementById('prevMonth');
const nextBtn = document.getElementById('nextMonth');

console.log("JHGFDSFGHJK") // Debug log to check script execution

// Array of month names for display
const monthNames = [
    "January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"
];
// Array of day names (Monday first) for header display
const daysOfWeek = ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"];

// Today's date details
const today = new Date();
let currentMonth = today.getMonth();
let currentYear = today.getFullYear();

// Returns number of days in a given month/year
function getDaysInMonth(month, year) {
    return new Date(year, month + 1, 0).getDate();
}

// Returns day index (0=Monday, ..., 6=Sunday) for first day of given month/year
function getFirstDayOfMonth(month, year) {
    const day = new Date(year, month, 1).getDay();
    return day === 0 ? 6 : day - 1; // Convert Sunday=0 to 6 to make Monday first
}

// Renders the calendar grid and UI for the currentMonth and currentYear
function renderCalendar() {
    // Set month and year text on top
    monthYear.textContent = `${monthNames[currentMonth]} ${currentYear}`;
    // Clear existing calendar days
    calendarDays.innerHTML = '';

    // Render day names header row (Mo, Tu, ...)
    daysOfWeek.forEach(day => {
        const dayName = document.createElement('div');
        dayName.className = 'calendar-day-name';
        dayName.textContent = day;
        calendarDays.appendChild(dayName);
    });

    // Get days count and first day offset of the current month/year
    const daysInMonth = getDaysInMonth(currentMonth, currentYear);
    const firstDay = getFirstDayOfMonth(currentMonth, currentYear);
    // Check if the calendar is showing the current real month and year
    const isTodayMonth = currentMonth === today.getMonth() && currentYear === today.getFullYear();

    // Add empty cells before the first day of the month to align days correctly
    for (let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.className = 'calendar-day empty';
        calendarDays.appendChild(emptyCell);
    };

    // Create day cells for each day of the month
    for (let i = 1; i <= daysInMonth; i++) {
        const dayCell = document.createElement('div');
        dayCell.className = 'calendar-day relative group showChildOnParentHover';
        // Add click event to adjust dropdown position on day cell
        // dayCell.onclick = function (event) {
        //   adjustDropdownPosition(event);
        // };
        dayCell.onmouseenter = function (event) {
            // adjustDropdownPosition(event);
        };
        // Highlight cell if it is today's date
        if (isTodayMonth && i === today.getDate()) {
            dayCell.classList.add('today');
        }

        // Set the day number text inside the cell
        dayCell.textContent = i;
        calendarDays.appendChild(dayCell);

        // Format date string YYYY-MM-DD for lookup
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        // Get task stage for this date from tasksByDate (assumed defined elsewhere)
        const stage = tasksByDate[dateStr];

        // If a task stage exists, show a colored dot indicating status
        if (stage) {
            const dot = document.createElement('div');
            // dot.className = 'task-dot';
            switch (stage.toLowerCase()) {
                case 'started':
                    dot.className = 'task-dot bg-orange-500';
                    break;
                case 'pending':
                    dot.className = 'task-dot bg-yellow-500';
                    break;
                case 'completed':
                    dot.className = 'task-dot bg-green-500';
                    break;
                default:
                    dot.className = 'task-dot bg-red-500';
            }
            dayCell.appendChild(dot);
        }

        // Create dropdown menu container for actions on the day cell
        const dropdown = document.createElement('div');
        dropdown.className = "z-[10] absolute transition-all duration-300 showOnHover origin-top-right flex gap-y-2 flex-col text-gray-600 w-auto p-4 rounded-lg w-fit-content bg-white shadow-xl/20 drop-up dateActionButtons";

        // Date object for the current cell's date
        const selectedDate = new Date(currentYear, currentMonth, i);
        // Date object for today's date with no time component
        const todayDateOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());

        // Show "Create Task" link only for today or future dates
        if (selectedDate >= todayDateOnly) {
            const createLink = document.createElement('a');
            createLink.href = `taskForm.php?due_date=${dateStr}&sourcePage=index`;
            createLink.textContent = 'Create Task';
            createLink.className = ' whitespace-nowrap';
            dropdown.appendChild(createLink);
        }

        // Always show "Show Tasks" link if tasks exist for this date
        if (stage) {
            const showLink = document.createElement('a');
            showLink.href = `myTasks.php?due_date=${dateStr}`;
            showLink.textContent = ' Show Tasks';
            showLink.className = 'whitespace-nowrap mt-1';
            dropdown.appendChild(showLink);
        }

        // Append dropdown to day cell only if either condition matches
        if ((selectedDate >= todayDateOnly) || (stage)) {
            dayCell.appendChild(dropdown);
        };
    }

    // Disable prevBtn if we're at the real current month and year
    if (currentYear === today.getFullYear() && currentMonth === today.getMonth()) {
        prevBtn.disabled = true;
    } else {
        prevBtn.disabled = false;
    }

}

// Event listener for previous month button click
prevBtn.addEventListener('click', () => {
    // Prevent going before current real month
    if (currentYear === today.getFullYear() && currentMonth === today.getMonth()) {
        return; // Do nothing
    }

    currentMonth--;
    if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    renderCalendar(); // Re-render calendar
});


// Event listener for next month button click
nextBtn.addEventListener('click', () => {
    currentMonth++;
    // If month goes beyond December, wrap to January next year
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    }
    renderCalendar(); // Re-render calendar after changing month/year
});

// Adjusts the dropdown position to prevent overflow outside viewport
function adjustDropdownPosition(event) {
    const mainParent = document.querySelector('.calendar_data');
    const parent = event.currentTarget;
    const dropdown = parent.querySelector('.dateActionButtons');
    if (dropdown) {
        const bodyRect = mainParent.getBoundingClientRect();
        const childRect = dropdown.getBoundingClientRect();

        const spaceTop = childRect.top - bodyRect.top;
        const spaceBottom = bodyRect.bottom - childRect.bottom;
        const spaceLeft = childRect.left - bodyRect.left;
        const spaceRight = bodyRect.right - childRect.right;

        console.log('adjustDropdownPosition', {
            top: spaceTop,
            bottom: spaceBottom,
            left: spaceLeft,
            right: spaceRight
            // bodyRect,
            // childRect,
            // width: dropdown.offsetWidth,
            // leftEK: (bodyRect?.width - dropdown.offsetWidth)
        });
        if (dropdown) {
            // Reset styles before calculating position
            let leftPlace = spaceLeft < 0 ? 0 : Math.round(Math.abs(spaceLeft));
            let ightPlace = spaceLeft < 0 ? 0 : Math.round(Math.abs(spaceRight));
            if (leftPlace < 0 || leftPlace < 20) {
                console.log(leftPlace, spaceLeft);
            };
            // dropdown.style.right = 'auto';
            // dropdown.style.left = 'auto';
            // dropdown.style.left = `${leftPlace}px`;
            // dropdown.style.right = `${ightPlace}px`;
        };
    };
};

// Initial rendering of the calendar on page load
renderCalendar();
// document.querySelectorAll(".calendar-day").forEach(day => {
//   day.addEventListener("mouseenter", () => {
//     const calendar = document.querySelector(".calendar_data");
//     const actionBox = day.querySelector(".dateActionButtons");
//     if (!actionBox) return;

//     // Reset position styles first
//     actionBox.style.left = "";
//     actionBox.style.right = "";

//     const calendarRect = calendar.getBoundingClientRect();
//     const actionRect = actionBox.getBoundingClientRect();

//     const overflowRight = actionRect.right > calendarRect.right;
//     const overflowLeft = actionRect.left < calendarRect.left;

//     if (overflowRight && !overflowLeft) {
//       // Overflowing to the right → stick to right
//       actionBox.style.left = "auto";
//       actionBox.style.right = "0";
//     } else if (overflowLeft && !overflowRight) {
//       // Overflowing to the left → stick to left
//       actionBox.style.right = "auto";
//       actionBox.style.left = "0";
//     } else if (overflowLeft && overflowRight) {
//       // If both are overflowing (very rare), center it
//       actionBox.style.right = "50%";
//       actionBox.style.transform = "translateX(50%)";
//     } else {
//       // Normal (enough space both sides)
//       actionBox.style.left = "0";
//     }
//   });

//   // Reset transform when mouse leaves
//   day.addEventListener("mouseleave", () => {
//     const actionBox = day.querySelector(".dateActionButtons");
//     if (actionBox) {
//       actionBox.style.transform = "";
//     }
//   });
// });
// document.querySelectorAll('.calendar-day').forEach(day => {
//   day.addEventListener('mouseenter', (e) => {
//     const dropdown = day.querySelector('.dateActionButtons');
//     if (!dropdown) return;


//     const parentRect = document.getElementById('calendarDays').getBoundingClientRect();
//     const dropdownRect = dropdown.getBoundingClientRect();
//     let posX = e.clientX;
//     // let parentRectLeft = dropdownRect.left;
//     let diffX = posX - dropdownRect.left;
//     let aX = diffX - parentRect.left;
//     aX = Math.max(0, Math.min(aX, parentRect.width - dropdown.offsetWidth));
//     console.log("posX", posX, 'dropdownRect.left', dropdownRect.left, "dropdownRectX",dropdownRect?.x,'diffX',diffX,"aX", aX, "parentRectwidth", parentRect.width, 'offsetWidth', dropdown.offsetWidth);
//     // Adjust position if it overflows right
//     dropdown.style.left = `${aX}px`; // reset
//   });
// });

document.querySelectorAll('.calendar-day').forEach(day => {
    day.addEventListener('mouseenter', (e) => {
        const dropdown = day.querySelector('.dateActionButtons');
        if (!dropdown) return;

        const parentRect = document.getElementById('calendarDays').getBoundingClientRect();
        const dayRect = day.getBoundingClientRect();

        console.log("parentRectLeft", parentRect);
        console.log("dayRect", dayRect);
        const relativeLeft = dayRect.left - parentRect.left;
        console.log("relativeLeft", dayRect, relativeLeft);
        if (dayRect?.right > dayRect?.left && (relativeLeft <= 76)) {
            dropdown.style.left = `0px`;
            dropdown.style.right = `auto`;
        } else {
            dropdown.style.right = `0px`;
            dropdown.style.left = `auto`;
        };
    });
});
