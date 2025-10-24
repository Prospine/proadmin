/**
 * dashboard.js
 * Animates dashboard cards on page load with pure CSS keyframes.
 * Leaves form, slider, and popup logic untouched as per request.
 * No Tailwind animation utils, just raw CSS classes.
 */

// Nav active state toggle
const links = document.querySelectorAll('.nav-links a');
links.forEach(link => {
    link.addEventListener('click', () => {
        links.forEach(l => l.classList.remove('active'));
        link.classList.add('active');
    });
});

// Slider for Registration/Test form (unchanged)
// The following variable declarations are being removed because they conflict
// with the more specific, inline script in testpage.php. That inline script
// is now the single source of truth for the slider toggle functionality.
// const sliderToggle, indicator, buttons, inquiryForm, testForm, headerTitle;

// Due Amount Calculator (unchanged)
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
        if (due < 0) due = 0;
        dueAmountInput.value = due.toFixed(2);
    }

    totalAmountInput.addEventListener('input', calculateDue);
    advanceAmountInput.addEventListener('input', calculateDue);
    discountInput.addEventListener('input', calculateDue);
    calculateDue();
}

// Inquiry/Test tabs (unchanged)
const inquiryTabUnique = document.getElementById('inquiryTabUnique');
const testTabUnique = document.getElementById('testTabUnique');
const uniqueInquiryForm = document.getElementById('uniqueInquiryForm');
const uniqueTestForm = document.getElementById('uniqueTestForm');

if (inquiryTabUnique && uniqueInquiryForm && uniqueTestForm) {
    inquiryTabUnique.addEventListener('click', () => {
        inquiryTabUnique.classList.add('active');
        testTabUnique.classList.remove('active');
        uniqueInquiryForm.classList.add('active');
        uniqueTestForm.classList.remove('active');
    });
}

if (testTabUnique && uniqueInquiryForm && uniqueTestForm) {
    testTabUnique.addEventListener('click', () => {
        testTabUnique.classList.add('active');
        inquiryTabUnique.classList.remove('active');
        uniqueTestForm.classList.add('active');
        uniqueInquiryForm.classList.remove('active');
    });
}

// Popup handlers (unchanged)
function openForm() {
    const menu = document.getElementById("myMenu");
    if (menu) {
        menu.style.display = "block";
    }
}

function closeForm() {
    const menu = document.getElementById("myMenu");
    if (menu) {
        menu.style.display = "none";
    }
}

function openNotif() {
    const notif = document.getElementById("myNotif");
    if (notif) {
        notif.style.display = "block";
    }
}

function closeNotif() {
    const notif = document.getElementById("myNotif");
    if (notif) {
        notif.style.display = "none";
    }
}

// Ensure initial state (unchanged)
// This is also handled by the inline script and the initial HTML class.

// Card Animations (NEW)
document.addEventListener('DOMContentLoaded', () => {
    // Target cards in the metrics grid
    const cards = document.querySelectorAll('.cards .card');
    cards.forEach((card, index) => {
        card.style.opacity = '0'; // Start invisible
        setTimeout(() => {
            card.classList.add('animate-card-pop');
            card.style.opacity = '1';
        }, index * 150); // Stagger by 150ms for dramatic effect
    });

    // Changelog Popup (unchanged)
    const changelogModalOverlay = document.getElementById('changelog-modal-overlay');
    if (!changelogModalOverlay) return;

    const changelogIntro = document.getElementById('changelog-intro');
    const changelogBody = document.getElementById('changelog-body');
    const changelogVersionText = document.getElementById('changelog-version-text');
    const closeBtn = document.getElementById('changelog-close-btn');

    const showChangelogPopup = async () => {
        try {
            const response = await fetch('changelog.html');
            if (!response.ok) throw new Error('Changelog file not found.');

            const htmlText = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(htmlText, 'text/html');
            const latestEntry = doc.querySelector('.log-entry');
            if (!latestEntry) return;

            const versionText = latestEntry.querySelector('h2')?.textContent.split(' ')[0];
            const changesList = latestEntry.querySelector('.changes');

            if (versionText === localStorage.getItem('lastSeenVersion')) {
                console.log('Changelog up to date.');
                return;
            }

            changelogVersionText.textContent = `You are now on version ${versionText}`;
            changelogBody.innerHTML = '';
            if (changesList) {
                const listItems = changesList.querySelectorAll('li');
                let newHtml = '<div class="changes-list">';
                listItems.forEach(item => {
                    const tagEl = item.querySelector('.tag');
                    const tagType = tagEl ? tagEl.classList[1] || 'added' : 'added';
                    const tagText = tagEl ? tagEl.textContent : 'Update';
                    let iconClass = 'fa-solid fa-info';
                    if (tagType === 'added') iconClass = 'fa-solid fa-plus';
                    if (tagType === 'improved') iconClass = 'fa-solid fa-arrow-up';
                    if (tagType === 'fixed') iconClass = 'fa-solid fa-wrench';
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

            changelogModalOverlay.style.display = 'flex';

            const closeModal = () => {
                changelogModalOverlay.style.display = 'none';
                localStorage.setItem('lastSeenVersion', versionText);
            };

            closeBtn?.addEventListener('click', closeModal);
            changelogModalOverlay.addEventListener('click', (e) => {
                if (e.target === changelogModalOverlay) closeModal();
            });
        } catch (error) {
            console.error('Changelog fetch failed:', error);
        }
    };

    showChangelogPopup();
});