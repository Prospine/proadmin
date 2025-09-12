// --- TOAST NOTIFICATION SCRIPT (Kept as is) ---
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            container.removeChild(toast);
        }, 500);
    }, 5000);
}


// All your event listeners will now be inside this single DOMContentLoaded event.
document.addEventListener("DOMContentLoaded", () => {
    // --- TABLE TOGGLE LOGIC ---
    const quickBtn = document.getElementById('quickBtn');
    const testBtn = document.getElementById('testBtn');
    const quickTable = document.getElementById('quickTable');
    const testTable = document.getElementById('testTable');

    if (quickBtn && testBtn && quickTable && testTable) {
        quickBtn.addEventListener('click', () => {
            quickBtn.classList.add('active');
            testBtn.classList.remove('active');
            quickTable.classList.remove('hidden');
            testTable.classList.add('hidden');
        });

        testBtn.addEventListener('click', () => {
            testBtn.classList.add('active');
            quickBtn.classList.remove('active');
            testTable.classList.remove('hidden');
            quickTable.classList.add('hidden');
        });
    }

    // --- STATUS UPDATE LOGIC ---
    document.querySelectorAll("table select").forEach(select => {
        select.addEventListener("change", async function () {
            const id = this.dataset.id;
            const type = this.dataset.type;
            const status = this.value;

            try {
                const res = await fetch("../api/update_inquiry_status.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        id,
                        type,
                        status,
                        // Note: We don't need CSRF token for this action, but if you did, you'd use it here
                    })
                });

                const data = await res.json();

                if (data.success) {
                    const pill = this.closest("tr").querySelector(".pill");
                    pill.textContent = status;

                    pill.className = "pill";
                    if (status.toLowerCase() === "visited") {
                        pill.classList.add("visited");
                    } else if (status.toLowerCase() === "cancelled") {
                        pill.classList.add("cancelled");
                    } else {
                        pill.classList.add("pending");
                    }
                } else {
                    showToast(data.message || "Update failed", 'error');
                }
            } catch (err) {
                console.error("Error:", err);
                showToast("Network error", 'error');
            }
        });
    });
});

// Toast helper
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerText = message;

    container.appendChild(toast);

    // trigger animation
    setTimeout(() => toast.classList.add('show'), 100);

    // auto remove after 3s
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
    // Expose PHP CSRF token to JS safely (escaped server-side).
    const CSRF_TOKEN = "<?= htmlspecialchars($csrf ?? $_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>";

    const drawer = document.getElementById('rightDrawer');
    const drawerTitle = document.getElementById('drawerTitle');
    const closeBtn = drawer.querySelector('.close-drawer');
    const quickForm = document.getElementById('quickForm');
    const testForm = document.getElementById('testForm');
    const inquiryIdInput = document.getElementById('inquiry_id'); // hidden input

    function openDrawer(inquiryId, rowData) {
        // set inquiry id in dataset + hidden input
        quickForm.dataset.inquiryId = inquiryId;
        if (inquiryIdInput) inquiryIdInput.value = inquiryId;

        // optional: fill form with row data
        if (rowData) fillForm(quickForm, rowData);

        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('drawer-open');
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('drawer-open');
    }

    // universal fill: sets form field values from object keys that match input/select/textarea names
    function fillForm(form, data) {
        if (!form || !data) return;
        const csrfInput = form.querySelector('input[name="csrf"]');
        if (csrfInput) csrfInput.value = CSRF_TOKEN;

        form.querySelectorAll('[name]').forEach(el => {
            const key = el.getAttribute('name');
            if (Object.prototype.hasOwnProperty.call(data, key)) {
                el.value = data[key] == null ? '' : data[key];
            }
        });
    }

    // Delegated click for opening drawer
    document.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.open-drawer');
        if (!btn) return;

        ev.preventDefault();
        const inquiryId = btn.getAttribute('data-id');
        const inquiryType = btn.getAttribute('data-type') || 'quick';

        // hide both forms
        quickForm.classList.add('hidden');
        testForm.classList.add('hidden');

        drawerTitle.textContent = inquiryType === 'quick' ? 'Quick Inquiry Details' : 'Test Inquiry Details';
        openDrawer();

        // show loading while fetching
        const targetBody = drawer.querySelector('.drawer-body');
        if (targetBody) targetBody.classList.add('loading');

        try {
            const resp = await fetch(`../api/fetch_inquiry.php?id=${encodeURIComponent(inquiryId)}&type=${encodeURIComponent(inquiryType)}`);
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const data = await resp.json();

            // show registered/not registered message
            const drawerMessage = document.getElementById('drawerMessage');
            if (drawerMessage) {
                drawerMessage.textContent = data.message || '';
                drawerMessage.classList.remove('registered', 'not-registered');
                if (data.already_registered) {
                    drawerMessage.classList.add('registered');
                } else {
                    drawerMessage.classList.add('not-registered');
                }
            }

            if (!data || !data.success) {
                console.error('Fetch failed:', data);
                drawer.querySelector('.drawer-body').innerHTML = `<p class="error">Failed: ${data?.message ?? 'Unknown'}</p>`;
                return;
            }

            const d = data.data || {};
            if (inquiryType === 'quick') {
                quickForm.classList.remove('hidden');
                fillForm(quickForm, d);
                // store id for potential submit handler
                quickForm.dataset.inquiryId = inquiryId;
            } else {
                testForm.classList.remove('hidden');
                fillForm(testForm, d);
                testForm.dataset.inquiryId = inquiryId;
            }
        } catch (err) {
            console.error('Drawer fetch error:', err);
            drawer.querySelector('.drawer-body').innerHTML = `<p class="error">Error: ${err.message}</p>`;
        } finally {
            if (targetBody) targetBody.classList.remove('loading');
        }
    });

    // close btn
    closeBtn.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDrawer();
    });

    // Example submit handler for quickForm
    quickForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(quickForm);
        formData.append('type', 'quick'); // still fine

        try {
            const res = await fetch('../api/insert_inquiry_reg.php', {
                method: 'POST',
                body: formData
            });
            const j = await res.json();
            if (j.success) {
                showToast(j.message ?? 'Saved successfully', 'success');
                closeDrawer();
            } else {
                showToast(j.message ?? 'Save failed', 'error');
            }
        } catch (err) {
            console.error('submit error', err);
            showToast('Save failed: ' + err.message, 'error');
        }
    });

    // testForm submit handler (similar)
    testForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(testForm);
        formData.append('id', testForm.dataset.inquiryId || '');
        formData.append('type', 'test');

        try {
            const res = await fetch('../api/insert_inquiry_test.php', {
                method: 'POST',
                body: formData
            });
            const j = await res.json();
            if (j.success) {
                showToast(j.message ?? 'Saved successfully', 'success');
                closeDrawer();
            } else {
                showToast(j.message ?? 'Save failed', 'error');
            }
        } catch (err) {
            console.error('submit error', err);
            showToast('Save failed: ' + err.message, 'error');
        }
    });

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

        calculateDue(); // initial run
    }

});