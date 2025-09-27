
const hamburger = document.getElementById('hamburger');
const navLinks = document.getElementById('navLinks');

hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('show'); // Toggle the 'show' class
});

document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const faqItem = question.closest('.faq-item');
        faqItem.classList.toggle('active');
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const slider = document.getElementById('slider');
    const slides = document.querySelectorAll('.slide');
    const dotsContainer = document.getElementById('dotsContainer');
    const totalSlides = slides.length;
    let currentIndex = 0;
    let direction = 1;
    let intervalId;
    let sliderStarted = false; // To track if the slider has started

    // Create dots
    slides.forEach((_, index) => {
        const dot = document.createElement('div');
        dot.classList.add('dot');
        if (index === 0) dot.classList.add('active');
        dot.addEventListener('click', () => goToSlide(index));
        dotsContainer.appendChild(dot);
    });

    const dots = document.querySelectorAll('.dot');

    function updateDots() {
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }

    function goToSlide(index) {
        currentIndex = index;
        slider.style.transform = `translateX(-${currentIndex * (100 / totalSlides)}%)`;
        updateDots();
        resetInterval();
    }

    function slide() {
        if (currentIndex === totalSlides - 1) {
            direction = -1;
        } else if (currentIndex === 0) {
            direction = 1;
        }
        currentIndex += direction;
        slider.style.transform = `translateX(-${currentIndex * (100 / totalSlides)}%)`;
        updateDots();
    }

    function resetInterval() {
        clearInterval(intervalId);
        intervalId = setInterval(slide, 3000); // Change slide every 3 seconds
    }

    // Function to check if the slider is in the viewport
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return rect.top < window.innerHeight && rect.bottom > 0;
    }

    // Start slider when it becomes visible
    function checkSliderVisibility() {
        if (isInViewport(slider) && !sliderStarted) {
            sliderStarted = true;
            resetInterval();
        } else if (!isInViewport(slider)) {
            clearInterval(intervalId); // Stop slider when out of view (optional)
        }
    }

    // Listen for scroll events
    window.addEventListener("scroll", checkSliderVisibility);
    checkSliderVisibility(); // Initial check

    // Error handling for images
    slides.forEach(slide => {
        const img = slide.querySelector('img');
        img.onerror = () => console.error(`Image failed to load: ${img.src}`);
    });
});