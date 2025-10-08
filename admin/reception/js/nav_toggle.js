// Hamburger Menu Logic
document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.getElementById('hamburger-menu');
    const nav = document.querySelector('nav');

    if (hamburger && nav) {
        hamburger.addEventListener('click', function (event) {
            event.stopPropagation();
            const isOpening = !nav.classList.contains('open');
            if (isOpening) {
                // The CSS now handles the display property, so this is not needed.
                setTimeout(() => nav.classList.add('open'), 10); // Allow repaint
            } else {
                nav.classList.remove('open');
            }
        });

        // Close the nav when clicking outside of it
        document.addEventListener('click', function (event) {
            // Check if the nav is open and the click was outside the nav and not on the hamburger
            if (nav.classList.contains('open') && !nav.contains(event.target) && !hamburger.contains(event.target)) {
                nav.classList.remove('open');
            }
        });
    }
});