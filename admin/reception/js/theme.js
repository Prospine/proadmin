document.addEventListener("DOMContentLoaded", () => {
    const themeToggle = document.getElementById("theme-toggle");
    const themeIcon = document.getElementById("theme-icon");

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
            document.body.classList.add("dark");
            themeIcon.classList.replace("fa-moon", "fa-sun");
        } else {
            document.body.classList.remove("dark");
            themeIcon.classList.replace("fa-sun", "fa-moon");
        }
    }
});
