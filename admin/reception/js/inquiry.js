document.addEventListener("DOMContentLoaded", () => {
    // --- ELEMENT REFERENCES ---
    const quickBtn = document.getElementById('quickBtn');
    const testBtn = document.getElementById('testBtn');
    const quickTable = document.getElementById('quickTable');
    const testTable = document.getElementById('testTable');
    const searchInput = document.getElementById('searchInput');
    const quickInquiryFilters = document.getElementById('quickInquiryFilters');
    const testInquiryFilters = document.getElementById('testInquiryFilters');
    const quickStatusFilter = document.getElementById('quickStatusFilter');
    const testStatusFilter = document.getElementById('testStatusFilter');
    // --- PAGINATION ELEMENTS ---
    const quickPagination = document.getElementById('quickPagination');
    const testPagination = document.getElementById('testPagination');


    // --- DRAWER ELEMENTS ---
    const drawer = document.getElementById('rightDrawer');
    const drawerOverlay = document.getElementById('drawer-overlay');
    const drawerBody = drawer.querySelector('.drawer-body');
    const drawerTitle = document.getElementById('drawerTitle');
    const quickForm = document.getElementById('quickForm');
    const testForm = document.getElementById('testForm');
    const drawerMessage = document.getElementById('drawerMessage');
    const closeDrawerButton = drawer.querySelector('.close-drawer');

    // --- TIME SLOT ELEMENTS ---
    const slotSelect = document.getElementById("appointment_time");
    const dateInput = document.querySelector("input[name='appointment_date']");

    // --- CORE FILTERING FUNCTION ---
    const filterTable = () => {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        let activeTableBody, statusFilter;

        // Determine which table and filters are active
        if (!quickTable.classList.contains('hidden')) {
            activeTableBody = quickTable.querySelector('tbody');
            statusFilter = quickStatusFilter ? quickStatusFilter.value : '';
        } else {
            activeTableBody = testTable.querySelector('tbody');
            statusFilter = testStatusFilter ? testStatusFilter.value : '';
        }

        if (!activeTableBody) return;

        const rows = activeTableBody.querySelectorAll('tr');
        let visibleRows = 0;
        let noResultsRow = activeTableBody.querySelector('.no-results-row');
        const colCount = activeTableBody.closest('table')?.querySelector('thead th')?.length || 1;

        rows.forEach(row => {
            if (row.classList.contains('no-results-row')) {
                return;
            }

            const rowText = row.textContent.toLowerCase();
            const statusCell = row.querySelector('.pill');
            const rowStatus = statusCell ? statusCell.textContent.trim().toLowerCase() : '';

            // Match conditions
            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = statusFilter ? rowStatus === statusFilter : true;

            if (matchesSearch && matchesStatus) {
                row.classList.remove('hidden');
                visibleRows++;
            } else {
                row.classList.add('hidden');
            }
        });

        // Show a "no results" message if no rows are visible
        if (visibleRows === 0) {
            if (!noResultsRow) {
                const newRow = activeTableBody.insertRow();
                newRow.className = 'no-results-row';
                const cell = newRow.insertCell();
                cell.colSpan = colCount;
                cell.className = 'px-6 py-4 text-center text-gray-500 dark:text-gray-400';
                cell.textContent = 'No inquiries match your search.';
            }
        } else {
            if (noResultsRow) noResultsRow.remove();
        }
    };

    // --- TABLE TOGGLE LOGIC ---
    if (quickBtn && testBtn && quickTable && testTable) {
        const sliderIndicator = document.querySelector('.slider-indicator');

        quickBtn.addEventListener('click', () => {
            quickBtn.classList.add('bg-white', 'dark:bg-gray-800', 'shadow', 'text-gray-800', 'dark:text-white');
            testBtn.classList.remove('bg-white', 'dark:bg-gray-800', 'shadow', 'text-gray-800', 'dark:text-white');
            testBtn.classList.add('text-gray-500', 'dark:text-gray-400');

            quickTable.classList.remove('hidden');
            testTable.classList.add('hidden');

            if (quickInquiryFilters) quickInquiryFilters.classList.replace('hidden', 'md:flex');
            if (testInquiryFilters) testInquiryFilters.classList.add('hidden');

            // Toggle pagination
            if (quickPagination) quickPagination.classList.remove('hidden');
            if (testPagination) testPagination.classList.add('hidden');

            if (searchInput) searchInput.value = '';
            if (quickStatusFilter) quickStatusFilter.value = '';
            filterTable();

            if (sliderIndicator) sliderIndicator.style.transform = 'translateX(0%)';
        });

        testBtn.addEventListener('click', () => {
            testBtn.classList.add('bg-white', 'dark:bg-gray-800', 'shadow', 'text-gray-800', 'dark:text-white');
            quickBtn.classList.remove('bg-white', 'dark:bg-gray-800', 'shadow', 'text-gray-800', 'dark:text-white');
            quickBtn.classList.add('text-gray-500', 'dark:text-gray-400');

            testTable.classList.remove('hidden');
            quickTable.classList.add('hidden');

            if (testInquiryFilters) testInquiryFilters.classList.replace('hidden', 'md:flex');
            if (quickInquiryFilters) quickInquiryFilters.classList.add('hidden');

            // Toggle pagination
            if (testPagination) testPagination.classList.remove('hidden');
            if (quickPagination) quickPagination.classList.add('hidden');

            if (searchInput) searchInput.value = '';
            if (testStatusFilter) testStatusFilter.value = '';
            filterTable();

            if (sliderIndicator) sliderIndicator.style.transform = 'translateX(100%)';
        });
    }

    // --- EVENT LISTENERS FOR FILTERS ---
    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (quickStatusFilter) quickStatusFilter.addEventListener('change', filterTable);
    if (testStatusFilter) testStatusFilter.addEventListener('change', filterTable);

    // --- STATUS UPDATE LOGIC ---
    document.querySelectorAll("table select").forEach(select => {
        select.addEventListener("change", async function () {
            const id = this.dataset.id;
            const type = this.dataset.type;
            const status = this.value;

            try {
                const res = await fetch("../api/update_inquiry_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({
                        id,
                        type,
                        status,
                    })
                });
                const data = await res.json();
                if (data.success) {
                    const pill = this.closest("tr").querySelector(".pill");
                    pill.textContent = status;
                    pill.className = `pill ${status.toLowerCase()}`;
                } else {
                    showToast(data.message || "Update failed", 'error');
                }
            } catch (err) {
                console.error("Error:", err);
                showToast("Network error during status update.", 'error');
            }
        });
    });

    // --- TOAST NOTIFICATION ---
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        const color = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        toast.className = `flex items-center gap-3 p-4 rounded-lg text-white shadow-lg transform transition-all duration-300 ease-in-out translate-x-full opacity-0 ${color}`;
        toast.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 5000);
    }

    // --- DRAWER LOGIC ---
    function openDrawer() {
        if (!drawer || !drawerOverlay) return;
        drawer.classList.remove('translate-x-full');
        drawer.setAttribute('aria-hidden', 'false');
        drawerOverlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        if (!drawer || !drawerOverlay) return;
        drawer.classList.add('translate-x-full');
        drawer.setAttribute('aria-hidden', 'true');
        drawerOverlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
    function fillForm(form, data) {
        if (!form || !data) return;
        for (const key in data) {
            if (Object.prototype.hasOwnProperty.call(data, key)) {
                const input = form.querySelector(`[name="${key}"]`);
                if (input) {
                    input.value = data[key] || '';
                }
            }
        }
    }

    document.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.open-drawer');
        if (!btn) return;
        ev.preventDefault();
        const inquiryId = btn.getAttribute('data-id');
        const inquiryType = btn.getAttribute('data-type') || 'quick';

        quickForm.classList.add('hidden');
        testForm.classList.add('hidden');
        drawerMessage.classList.add('hidden');
        drawerBody.innerHTML = '<p class="text-center p-8 text-gray-500 dark:text-gray-400">Loading details...</p>';
        drawerTitle.textContent = 'Loading...';
        openDrawer();

        try {
            const resp = await fetch(`../api/fetch_inquiry.php?id=${encodeURIComponent(inquiryId)}&type=${encodeURIComponent(inquiryType)}`);
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const data = await resp.json();

            if (data.message) {
                drawerMessage.textContent = data.message || '';
                drawerMessage.classList.remove('hidden');
            }

            if (data.success && data.data) {
                drawerBody.innerHTML = '';
                if (inquiryType === 'quick') {
                    drawerTitle.textContent = 'Quick Inquiry Details';
                    drawerBody.appendChild(quickForm);
                    quickForm.classList.remove('hidden');
                    fillForm(quickForm, data.data);
                    document.getElementById('inquiry_id').value = inquiryId;
                } else {
                    drawerTitle.textContent = 'Test Inquiry Details';
                    drawerBody.appendChild(testForm);
                    testForm.classList.remove('hidden');
                    fillForm(testForm, data.data);
                    document.getElementById('inquiry_id_test').value = inquiryId;
                }
            } else {
                drawerBody.innerHTML = `<p class="text-center p-8 text-red-500">${data.message || 'Failed to load details.'}</p>`;
            }
        } catch (err) {
            console.error('Drawer fetch error:', err);
            drawerBody.innerHTML = `<p class="text-center p-8 text-red-500">An error occurred while fetching details.</p>`;
        }
    });

    if (closeDrawerButton) closeDrawerButton.addEventListener('click', closeDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !drawer.classList.contains('translate-x-full')) {
            closeDrawer();
        }
    });

    // --- DRAWER FORM SUBMISSION ---
    if (quickForm) {
        quickForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(quickForm);
            try {
                const response = await fetch('../api/insert_inquiry_reg.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message || 'Registration successful!', 'success');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    throw new Error(result.message || 'An unknown error occurred.');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        });
    }
    if (testForm) {
        testForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(testForm);
            try {
                const response = await fetch('../api/test_submission.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showToast(result.message || 'Test submitted successfully!', 'success');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    throw new Error(result.message || 'An unknown error occurred.');
                }
            } catch (error) {
                showToast('Error: ' + error.message, 'error');
            }
        });
    }

    // --- TIME SLOT LOGIC ---
    function fetchSlotsForDate(dateString) {
        if (!dateString || !slotSelect) return;
        slotSelect.innerHTML = '<option>Loading slots...</option>';
        fetch(`../api/get_slots.php?date=${dateString}`)
            .then(res => res.json())
            .then(data => {
                slotSelect.innerHTML = '';
                if (data.success && data.slots.length > 0) {
                    data.slots.forEach(slot => {
                        const opt = document.createElement("option");
                        opt.value = slot.time;
                        opt.textContent = slot.label;
                        if (slot.disabled) {
                            opt.disabled = true;
                            opt.textContent += " (Booked)";
                        }
                        slotSelect.appendChild(opt);
                    });
                } else {
                    const errorOption = document.createElement("option");
                    errorOption.textContent = data.message || "No slots available.";
                    errorOption.disabled = true;
                    slotSelect.appendChild(errorOption);
                }
            })
            .catch(err => {
                slotSelect.innerHTML = '<option>Error loading slots.</option>';
                console.error("Error fetching slots:", err);
            });
    }

    if (dateInput) {
        dateInput.addEventListener('change', (event) => {
            fetchSlotsForDate(event.target.value);
        });
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
        dateInput.min = today;
        fetchSlotsForDate(today);
    }

    // --- HAMBURGER/DRAWER NAVIGATION ---
    const menuBtn = document.getElementById('menuBtn'); // Hamburger for tablet/mobile
    const closeNavBtn = document.getElementById('closeBtn'); // Close button inside drawer
    const drawerNav = document.getElementById('drawerNav');
    const navDrawerOverlay = document.getElementById('drawer-overlay'); // Shared overlay
    const mainNavLinks = document.querySelector('header nav');
    const drawerLinksContainer = drawerNav.querySelector('nav');

    if (menuBtn && closeNavBtn && drawerNav && navDrawerOverlay && mainNavLinks && drawerLinksContainer) {
        // Clone main navigation links into the drawer
        drawerLinksContainer.innerHTML = mainNavLinks.innerHTML;

        function openNavDrawer() {
            drawerNav.classList.remove('translate-x-full');
            navDrawerOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeNavDrawer() {
            drawerNav.classList.add('translate-x-full');
            navDrawerOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        }

        menuBtn.addEventListener('click', openNavDrawer);
        closeNavBtn.addEventListener('click', closeNavDrawer);
        navDrawerOverlay.addEventListener('click', (e) => {
            // This overlay is shared, so we only close if the nav drawer is open
            if (!drawerNav.classList.contains('translate-x-full')) {
                closeNavDrawer();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !drawerNav.classList.contains('translate-x-full')) closeNavDrawer();
        });
    }
});