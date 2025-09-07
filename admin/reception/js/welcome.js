const progressBar = document.getElementById("progressBar");
const progressText = document.getElementById("progressText");

const messages = [
    { pct: 0, text: "Initializing system..." },
    { pct: 20, text: "Loading patient database..." },
    { pct: 40, text: "Setting up appointment scheduler..." },
    { pct: 60, text: "Syncing billing & payment modules..." },
    { pct: 80, text: "Preparing dashboard interface..." },
    { pct: 95, text: "Almost ready..." }
];

let width = 0;
const interval = setInterval(() => {
    if (width >= 100) {
        clearInterval(interval);
    } else {
        width += 1; // progress speed
        progressBar.style.width = width + "%";

        // Update message
        for (let i = messages.length - 1; i >= 0; i--) {
            if (width >= messages[i].pct) {
                if (progressText.textContent !== messages[i].text) {
                    progressText.classList.add("fade-out");
                    setTimeout(() => {
                        progressText.textContent = messages[i].text;
                        progressText.classList.remove("fade-out");
                        progressText.classList.add("fade-in");
                    }, 200);
                }
                break;
            }
        }
    }
}, 25); // 3s to reach 100%

// Redirect
setTimeout(() => {
    window.location.href = "views/dashboard.html";
}, 3000);