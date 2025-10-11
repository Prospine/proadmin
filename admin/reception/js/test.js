const drawer = document.getElementById('test-drawer');
const drawerContent = drawer.querySelector('.drawer-content');

document.addEventListener('DOMContentLoaded', () => {
    /* ------------------------------
       NEW: Table Search, Filter & Sort Logic
    ------------------------------ */
    const searchInput = document.getElementById('searchInput');
    const testNameFilter = document.getElementById('testNameFilter');
    const paymentStatusFilter = document.getElementById('paymentStatusFilter');
    const testStatusFilter = document.getElementById('testStatusFilter');
    const sortDirectionBtn = document.getElementById('sortDirectionBtn');
    const tableBody = document.getElementById('testsTableBody');
    const tableHeaders = document.querySelectorAll('.modern-table th.sortable');

    let currentSort = {
        key: 'id', // Default sort
        direction: 'desc' // Default direction
    };

    const processTable = () => {
        if (!tableBody) return;

        const searchTerm = searchInput.value.toLowerCase();
        const testNameValue = testNameFilter.value.toLowerCase();
        const paymentStatusValue = paymentStatusFilter.value.toLowerCase();
        const testStatusValue = testStatusFilter.value.toLowerCase();
        const rows = Array.from(tableBody.querySelectorAll('tr'));
        let visibleRows = 0;

        // --- Filtering ---
        rows.forEach(row => {
            if (row.querySelector('td[colspan]')) {
                row.style.display = 'none';
                return;
            }

            const rowText = row.textContent.toLowerCase();
            const testNameCell = row.querySelector('td:nth-child(3)');
            const paymentStatusCell = row.querySelector('td:nth-child(6) .pill');
            const testStatusCell = row.querySelector('td:nth-child(7) .pill');

            const rowTestName = testNameCell ? testNameCell.textContent.trim().toLowerCase() : '';
            const rowPaymentStatus = paymentStatusCell ? paymentStatusCell.textContent.trim().toLowerCase() : '';
            const rowTestStatus = testStatusCell ? testStatusCell.textContent.trim().toLowerCase() : '';

            const matchesSearch = rowText.includes(searchTerm);
            const matchesTestName = testNameValue ? rowTestName === testNameValue : true;
            const matchesPaymentStatus = paymentStatusValue ? rowPaymentStatus === paymentStatusValue : true;
            const matchesTestStatus = testStatusValue ? rowTestStatus === testStatusValue : true;

            if (matchesSearch && matchesTestName && matchesPaymentStatus && matchesTestStatus) {
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
                cell.colSpan = tableHeaders.length + 1;
                cell.textContent = 'No tests match your criteria.';
                cell.style.textAlign = 'center';
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    };

    // Attach event listeners
    [searchInput, testNameFilter, paymentStatusFilter, testStatusFilter].forEach(el => {
        if (el) el.addEventListener('input', processTable);
    });

    if (sortDirectionBtn) {
        sortDirectionBtn.addEventListener('click', () => {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            processTable();
        });
    }

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

// Toast function
function showToast(message, type = "success") {
    const toast = document.createElement("div");
    toast.textContent = message;
    toast.style.padding = "12px 20px";
    toast.style.borderRadius = "6px";
    toast.style.color = "#fff";
    toast.style.fontSize = "14px";
    toast.style.boxShadow = "0 2px 6px rgba(0,0,0,0.2)";
    toast.style.opacity = "0";
    toast.style.transition = "all 0.4s ease";
    toast.style.transform = "translateX(100%)";
    toast.style.backgroundColor = type === "success" ? "#28a745" : "#dc3545";

    document.getElementById("toast-container").appendChild(toast);

    // Slide in
    setTimeout(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateX(0)";
    }, 50);

    // Auto remove after 3s
    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(100%)";
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

function createInfoItem(label, value) {
    const div = document.createElement('div');
    div.className = 'info-item';
    div.innerHTML = `<span class="label">${label}</span><span class="value">${value || ''}</span>`;
    return div;
}

document.querySelectorAll('.open-drawer').forEach(btn => {
    btn.addEventListener('click', () => {
        const testId = btn.dataset.id;
        fetch(`../api/fetch_test.php?id=${testId}`)
            .then(res => res.json())
            .then(data => {
                drawerContent.innerHTML = '';

                // --- Patient Info Card ---
                const patientCard = document.createElement('div');
                patientCard.className = 'drawer-card';
                patientCard.innerHTML = `<h4>Patient Info</h4>`;
                const patientGrid = document.createElement('div');
                patientGrid.className = 'info-grid';
                ['patient_name', 'phone_number', 'alternate_phone_no', 'gender', 'age', 'dob', 'parents', 'relation'].forEach(key => {
                    patientGrid.appendChild(createInfoItem(key.replace(/_/g, ' '), data[key]));
                });
                patientCard.appendChild(patientGrid);
                drawerContent.appendChild(patientCard);

                // --- Test Info Card ---
                const testCard = document.createElement('div');
                testCard.className = 'drawer-card';
                testCard.innerHTML = `<h4>Test Info</h4>`;
                const testGrid = document.createElement('div');
                testGrid.className = 'info-grid';
                ['test_name', 'referred_by', 'test_done_by', 'limb', 'visit_date', 'assigned_test_date'].forEach(key => {
                    testGrid.appendChild(createInfoItem(key.replace(/_/g, ' '), data[key]));
                });
                testCard.appendChild(testGrid);
                drawerContent.appendChild(testCard);

                // --- Payment Info Card ---
                const paymentCard = document.createElement('div');
                paymentCard.className = 'drawer-card';
                paymentCard.innerHTML = `<h4>Payment Info</h4>`;
                const paymentGrid = document.createElement('div');
                paymentGrid.className = 'info-grid';
                ['total_amount', 'advance_amount', 'due_amount', 'discount', 'payment_method'].forEach(key => {
                    let value = data[key];
                    if (key.includes('amount')) value = `₹${parseFloat(value || 0).toFixed(2)}`;
                    paymentGrid.appendChild(createInfoItem(key.replace(/_/g, ' '), value));
                });

                // Test Status Select
                const testStatusDiv = document.createElement('div');
                testStatusDiv.className = 'info-item';
                testStatusDiv.innerHTML = `<span class="label">Test Status</span>
            <select class="status-select" data-id="${data.test_id}">
                <option value="pending" ${data.test_status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="completed" ${data.test_status === 'completed' ? 'selected' : ''}>Completed</option>
                <option value="cancelled" ${data.test_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
            </select>`;
                paymentGrid.appendChild(testStatusDiv);

                // Payment Status Select
                const paymentStatusDiv = document.createElement('div');
                paymentStatusDiv.className = 'info-item';
                paymentStatusDiv.innerHTML = `<span class="label">Payment Status</span>
            <select class="payment-select" data-id="${data.test_id}">
                <option value="pending" ${data.payment_status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="partial" ${data.payment_status === 'partial' ? 'selected' : ''}>Partial</option>
                <option value="paid" ${data.payment_status === 'paid' ? 'selected' : ''}>Paid</option>
            </select>`;
                paymentGrid.appendChild(paymentStatusDiv);

                // --- Update Payment Section ---
                const updateDiv = document.createElement('div');
                updateDiv.className = 'info-item';
                updateDiv.innerHTML = `
                    <span class="label">Add Payment</span>
                    <input type="number" min="1" class="payment-input" placeholder="Enter amount" style="margin-left:10px; padding:10px 8px; width:160px; border-radius:5px; border:1px solid #ccc;">
                    <button class="save-payment-btn" style="margin-left:8px; padding:5px 10px;">Save</button>
                `;

                // Handle Save button click
                updateDiv.querySelector('.save-payment-btn').addEventListener('click', () => {
                    const amount = updateDiv.querySelector('.payment-input').value;
                    if (amount && !isNaN(amount)) {
                        fetch("../api/update_payment.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                test_id: data.test_id,
                                amount: parseFloat(amount)
                            })
                        })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message || "Payment updated!", "success");
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 3000);
                                } else {
                                    showToast(resp.message || "Error updating payment", "error");
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                showToast("Something went wrong!", "error");
                            });
                    } else {
                        showToast("Please enter a valid amount", "error");
                    }
                });

                drawer.addEventListener('change', e => {
                    if (e.target.classList.contains('status-select')) {
                        fetch("../api/update_test_status.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                test_id: e.target.dataset.id,
                                test_status: e.target.value
                            })
                        })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message, "success");
                                    setTimeout(() => window.location.reload(), 3000);
                                } else {
                                    showToast(resp.message || "Failed to update test status", "error");
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                showToast("Something went wrong!", "error");
                            });
                    }

                    if (e.target.classList.contains('payment-select')) {
                        fetch("../api/update_payment_status.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                test_id: e.target.dataset.id,
                                payment_status: e.target.value
                            })
                        })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message, "success");
                                    setTimeout(() => window.location.reload(), 3000);
                                } else {
                                    showToast(resp.message || "Failed to update payment status", "error");
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                showToast("Something went wrong!", "error");
                            });
                    }
                });

                paymentGrid.appendChild(updateDiv);

                paymentCard.appendChild(paymentGrid);
                drawerContent.appendChild(paymentCard);

                drawer.classList.add('open');
            })
            .catch(err => console.error(err));
    });
});

// Close drawer
drawer.querySelector('.close-drawer').addEventListener('click', () => {
    drawer.classList.remove('open');
});

// Handle status/payment changes
drawer.addEventListener('change', e => {
    if (e.target.classList.contains('status-select')) {
        console.log(`Test ${e.target.dataset.id} status changed to ${e.target.value}`);
    }
    if (e.target.classList.contains('payment-select')) {
        console.log(`Test ${e.target.dataset.id} payment changed to ${e.target.value}`);
    }
});