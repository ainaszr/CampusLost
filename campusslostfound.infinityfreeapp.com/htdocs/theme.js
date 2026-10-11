// =========================
// CAMPUSLOST GLOBAL THEME
// =========================

function applyGlobalTheme(theme) {

    if (theme === "dark") {

        document.documentElement.classList.add("dark-mode");
        document.documentElement.setAttribute(
            "data-theme",
            "dark"
        );

    } else {

        document.documentElement.classList.remove("dark-mode");
        document.documentElement.setAttribute(
            "data-theme",
            "light"
        );

    }
}


// Apply immediately
const savedTheme =
    localStorage.getItem("campusTheme") || "light";

applyGlobalTheme(savedTheme);


// Apply again after page loaded
document.addEventListener(
    "DOMContentLoaded",
    function () {

        const theme =
            localStorage.getItem("campusTheme") || "light";

        applyGlobalTheme(theme);

    }
);