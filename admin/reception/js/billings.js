document.addEventListener("DOMContentLoaded", () => {
    /* ------------------------------
       NEW: Table Search, Filter & Sort Logic
    ------------------------------ */
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const sortDirectionBtn = document.getElementById('sortDirectionBtn');
    const tableBody = document.getElementById('billingTableBody');
    const tableHeaders = document.querySelectorAll('.modern-table th.sortable');

    let currentSort = {
        key: 'id', // Default sort
        direction: 'desc' // Default direction
    };

    const processTable = () => {
        if (!tableBody) return;

        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        const rows = Array.from(tableBody.querySelectorAll('tr'));
        let visibleRows = 0;

        // --- Filtering ---
        rows.forEach(row => {
            if (row.querySelector('td[colspan]')) {
                row.style.display = 'none';
                return;
            }

            const rowText = row.textContent.toLowerCase();
            const statusCell = row.querySelector('.pill');
            const rowStatus = statusCell ? statusCell.textContent.trim().toLowerCase() : '';

            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = statusValue ? rowStatus === statusValue : true;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });

        // --- Sorting ---
        const visibleTableRows = rows.filter(row => row.style.display !== 'none');
        visibleTableRows.sort((a, b) => {
            const key = currentSort.key;
            const direction = currentSort.direction === 'asc' ? 1 : -1;
            const headerIndex = Array.from(tableHeaders).findIndex(th => th.dataset.key === key);
            if (headerIndex === -1) return 0;

            let valA = a.cells[headerIndex]?.textContent.trim() || '';
            let valB = b.cells[headerIndex]?.textContent.trim() || '';

            const isNumeric = tableHeaders[headerIndex].classList.contains('numeric');

            if (isNumeric) {
                valA = parseFloat(valA.replace(/[^0-9.-]+/g, "")) || 0;
                valB = parseFloat(valB.replace(/[^0-9.-]+/g, "")) || 0;
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return -1 * direction;
            if (valA > valB) return 1 * direction;
            return 0;
        });

        // Re-append sorted rows and handle no results
        visibleTableRows.forEach(row => tableBody.appendChild(row));

        let noResultsRow = tableBody.querySelector('.no-results-row');
        if (visibleRows === 0) {
            if (!noResultsRow) {
                noResultsRow = tableBody.insertRow();
                noResultsRow.className = 'no-results-row';
                const cell = noResultsRow.insertCell();
                cell.colSpan = tableHeaders.length + 2; // +2 for non-sortable action columns
                cell.textContent = 'No billing records match your criteria.';
                cell.style.textAlign = 'center';
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    };

    // Attach event listeners
    if (searchInput) searchInput.addEventListener('input', processTable);
    if (statusFilter) statusFilter.addEventListener('change', processTable);
    if (sortDirectionBtn) {
        sortDirectionBtn.addEventListener('click', () => {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            processTable();
        });
    }

    /* ------------------------------
       Drawer Logic
    ------------------------------ */
    const drawerOverlay = document.getElementById("drawer-overlay");
    const drawerPanel = document.getElementById("drawer-panel");
    const drawerHeader = document.getElementById("drawer-patient-name");
    const drawerBody = document.getElementById("drawer-body");
    const closeDrawerButton = document.getElementById("closeDrawer");
    const viewButtons = document.querySelectorAll(".open-drawer"); // CHANGED: More specific selector

    const closeDrawer = () => {
        if (drawerPanel) drawerPanel.classList.remove('is-open');
        if (drawerOverlay) setTimeout(() => drawerOverlay.style.display = 'none', 300);
    };

    const openDrawerWithDetails = async (patientId) => {
        if (!patientId || !drawerOverlay) return;

        try {
            drawerBody.innerHTML = '<p>Loading details...</p>';
            drawerOverlay.style.display = 'block';
            setTimeout(() => drawerPanel.classList.add('is-open'), 10);

            // CHANGED: Using a root-relative path for reliability
            const response = await fetch(`../api/get_billing_details.php?id=${patientId}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                drawerHeader.textContent = data.patient_name || 'Billing Details';

                let html = '<h4>Transaction History</h4><div class="payment-list">';

                // Consultation always at top
                html += `
        <div class="payment-item">
            <p><strong>Consultation Fee Paid</strong></p>
            <p>Amount: ₹${parseFloat(data.consultation_amount).toFixed(2)}</p>
        </div>
    `;

                if (data.payments.length > 0) {
                    // 🔹 Group payments by type (p.remarks or status)
                    const grouped = {};
                    data.payments.forEach(p => {
                        const key = p.status || p.remarks || "Other";
                        if (!grouped[key]) grouped[key] = [];
                        grouped[key].push(p);
                    });

                    // 🔹 Render grouped payments
                    Object.keys(grouped).forEach(type => {
                        html += `
                <div class="payment-item">
                    <p><strong>${type}</strong></p>
                    <ul style="margin:0; padding-left:1.2rem; color:#555; font-size:0.9rem;">
                        ${grouped[type].map(p => `
                            <li>
                                Date: ${p.payment_date} | 
                                Amount: ₹${parseFloat(p.amount).toFixed(2)} | 
                                Mode: ${p.mode}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
                    });
                } else {
                    html += '<p>No other payments have been recorded.</p>';
                }

                html += '</div>';
                drawerBody.innerHTML = html;

            } else {
                drawerBody.innerHTML = `<p>Error: ${data.message}</p>`;
            }

        } catch (error) {
            console.error("Fetch error:", error);
            drawerBody.innerHTML = '<p>Could not fetch patient details. Please try again.</p>';
        }
    };

    viewButtons.forEach(button => {
        button.addEventListener('click', function () {
            const patientId = this.dataset.id;
            openDrawerWithDetails(patientId);
        });
    });

    if (closeDrawerButton) closeDrawerButton.addEventListener('click', closeDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', (e) => {
        if (e.target === drawerOverlay) {
            closeDrawer();
        }
    });

    // --- Sorting Event Listeners ---
    tableHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.key;
            if (currentSort.key === key) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.key = key;
                currentSort.direction = 'desc';
            }
            tableHeaders.forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
            header.classList.add(currentSort.direction === 'asc' ? 'sort-asc' : 'sort-desc');
            processTable();
        });
    });
});