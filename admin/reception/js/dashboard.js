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
const discountInput = document.querySelector('input[name="discount"]');

if (totalAmountInput && advanceAmountInput && dueAmountInput && discountInput) {
    function calculateDue() {
        const total = parseFloat(totalAmountInput.value) || 0;
        const advance = parseFloat(advanceAmountInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;

        let due = total - discount - advance;

        if (due < 0) {
            due = 0;
        }
        dueAmountInput.value = due.toFixed(2);
    }

    totalAmountInput.addEventListener('input', calculateDue);
    advanceAmountInput.addEventListener('input', calculateDue);
    discountInput.addEventListener('input', calculateDue);
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

// ==========================================================
// 4. "What's New" Changelog Popup
// ==========================================================
document.addEventListener('DOMContentLoaded', () => {
    const changelogModalOverlay = document.getElementById('changelog-modal-overlay');
    if (!changelogModalOverlay) return;

    const changelogIntro = document.getElementById('changelog-intro');
    const changelogBody = document.getElementById('changelog-body');
    const changelogVersionText = document.getElementById('changelog-version-text');
    const closeBtn = document.getElementById('changelog-close-btn');

    const showChangelogPopup = async () => {
        try {
            // Fetch the changelog HTML content
            const response = await fetch('changelog.html');
            if (!response.ok) throw new Error('Changelog file not found.');

            const htmlText = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');

            // Get the very first log entry
            const latestEntry = doc.querySelector('.log-entry');
            if (!latestEntry) return;

            // Extract version, title, and changes
            const versionText = latestEntry.querySelector('h2').textContent.split(' ')[0]; // e.g., "v2.2.5"
            const changesList = latestEntry.querySelector('.changes');

            // Get the last seen version from localStorage
            const lastSeenVersion = localStorage.getItem('lastSeenVersion');

            // If the latest version is the same as the last seen one, do nothing
            if (versionText === lastSeenVersion) {
                console.log('Changelog up to date.');
                return;
            }

            // 1. Prepare content
            changelogVersionText.textContent = `You are now on version ${versionText}`;
            changelogBody.innerHTML = ''; // Clear any previous content/loader
            if (changesList) {
                const listItems = changesList.querySelectorAll('li');
                let newHtml = '<div class="changes-list">';

                listItems.forEach(item => {
                    const tagEl = item.querySelector('.tag');
                    const tagType = tagEl ? tagEl.classList[1] || 'added' : 'added'; // e.g., 'added', 'improved', 'fixed'
                    const tagText = tagEl ? tagEl.textContent : 'Update';

                    // Determine icon based on tag type
                    let iconClass = 'fa-solid fa-info';
                    if (tagType === 'added') iconClass = 'fa-solid fa-plus';
                    if (tagType === 'improved') iconClass = 'fa-solid fa-arrow-up';
                    if (tagType === 'fixed') iconClass = 'fa-solid fa-wrench';

                    // Remove the tag from the item's content to avoid duplication
                    if (tagEl) tagEl.remove();
                    const changeText = item.innerHTML.trim();

                    newHtml += `
                        <div class="change-item">
                            <div class="change-icon ${tagType}"><i class="${iconClass}"></i></div>
                            <div class="change-content">
                                <span class="change-tag ${tagType}">${tagText}</span>
                                <p class="change-text">${changeText}</p>
                            </div>
                        </div>
                    `;
                });
                newHtml += '</div>';
                changelogBody.innerHTML = newHtml;
            }

            // 2. Show the modal and start animations
            changelogModalOverlay.style.display = 'flex';
            changelogIntro.style.display = 'flex';

            // 3. Add animation classes sequentially
            changelogIntro.querySelector('.icon').classList.add('animate-pop-in');
            changelogIntro.querySelector('h2').classList.add('animate-slide-up-1');
            changelogIntro.querySelector('p').classList.add('animate-slide-up-2');
            changelogBody.classList.add('animate-slide-up-3');
            document.querySelector('.changelog-footer').classList.add('animate-slide-up-3');


            // Handle closing the modal
            const closeModal = () => {
                changelogModalOverlay.style.display = 'none';
                // IMPORTANT: Store the new version so it doesn't show again
                localStorage.setItem('lastSeenVersion', versionText);
            };

            closeBtn.addEventListener('click', closeModal);
            changelogModalOverlay.addEventListener('click', (e) => {
                if (e.target === changelogModalOverlay) closeModal();
            });

        } catch (error) {
            console.error('Failed to fetch or process changelog:', error);
        }
    };

    showChangelogPopup();
});
