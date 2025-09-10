// Simple nav active state toggle
const links = document.querySelectorAll('.nav-links a');
links.forEach(link => {
    link.addEventListener('click', () => {
        links.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
    });
});

const sliderToggle = document.getElementById("sliderToggle");
const indicator = sliderToggle.querySelector(".slider-indicator");
const buttons = sliderToggle.querySelectorAll("button");

const inquiryForm = document.getElementById("inquiryForm");
const testForm = document.getElementById("testForm");

const headerTitle = document.querySelector(".quick-view-header h2");

buttons.forEach((btn, index) => {
    btn.addEventListener("click", () => {
        // Move indicator
        indicator.style.transform = `translateX(${index * 100}%)`;

        // Update active button
        buttons.forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        // Show/hide forms
        if (index === 0) {
            inquiryForm.classList.add("active");
            testForm.classList.remove("active");
            headerTitle.textContent = "New Registration";
        } else {
            inquiryForm.classList.remove("active");
            testForm.classList.add("active");
            headerTitle.textContent = "New Test";
        }
    });
});

// ==========================================================
// 3. Due Amount Calculator
// ==========================================================
const totalAmountInput = document.querySelector('input[name="total_amount"]');
const advanceAmountInput = document.querySelector('input[name="advance_amount"]');
const dueAmountInput = document.querySelector('input[name="due_amount"]');

if (totalAmountInput && advanceAmountInput && dueAmountInput) {
    function calculateDue() {
        const total = parseFloat(totalAmountInput.value) || 0;
        const advance = parseFloat(advanceAmountInput.value) || 0;
        let due = total - advance;

        if (due < 0) {
            due = 0;
        }
        dueAmountInput.value = due.toFixed(2);
    }

    totalAmountInput.addEventListener('input', calculateDue);
    advanceAmountInput.addEventListener('input', calculateDue);
    calculateDue();
}


const inquiryTabUnique = document.getElementById('inquiryTabUnique');
const testTabUnique = document.getElementById('testTabUnique');
const uniqueInquiryForm = document.getElementById('uniqueInquiryForm');
const uniqueTestForm = document.getElementById('uniqueTestForm');

if (inquiryTabUnique) {
    inquiryTabUnique.addEventListener('click', () => {
        inquiryTabUnique.classList.add('active');
        testTabUnique.classList.remove('active');
        uniqueInquiryForm.classList.add('active');
        uniqueTestForm.classList.remove('active');
    });
}

if (testTabUnique) {
    testTabUnique.addEventListener('click', () => {
        testTabUnique.classList.add('active');
        inquiryTabUnique.classList.remove('active');
        uniqueTestForm.classList.add('active');
        uniqueInquiryForm.classList.remove('active');
    });
}


function openForm() {
    document.getElementById("myMenu").style.display = "block";
}

function closeForm() {
    document.getElementById("myMenu").style.display = "none";
}

function openNotif() {
    document.getElementById("myNotif").style.display = "block";
}

function closeNotif() {
    document.getElementById("myNotif").style.display = "none";
}

