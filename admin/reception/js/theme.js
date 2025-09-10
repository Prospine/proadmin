document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("theme-toggle");
    const themeIcon = document.getElementById("theme-icon");
    const themeLink = document.getElementById("theme-style");

    // Load stored theme
    let currentTheme = localStorage.getItem("theme") || "light";
    applyTheme(currentTheme);

    themeToggle.addEventListener("click", () => {
        currentTheme = (currentTheme === "light") ? "dark" : "light";
        applyTheme(currentTheme);
        localStorage.setItem("theme", currentTheme);
    });

    function applyTheme(theme) {
        if (theme === "dark") {
            themeLink.setAttribute("href", "../css/dark.css");
            themeIcon.classList.replace("fa-moon", "fa-sun");
        } else {
            themeLink.setAttribute("href", "");
            themeIcon.classList.replace("fa-sun", "fa-moon");
        }
    }
});
