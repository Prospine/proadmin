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

                // --- NEW: Function to create a detailed card for a single test item ---
                const createTestItemCard = (itemData, title) => {
                    const card = document.createElement('div');
                    card.className = 'drawer-card';

                    // Card Header
                    const header = document.createElement('h4');
                    header.textContent = title;
                    card.appendChild(header);

                    // Test Details Grid
                    const testGrid = document.createElement('div');
                    testGrid.className = 'info-grid';
                    ['test_name', 'limb', 'referred_by', 'test_done_by', 'assigned_test_date'].forEach(key => {
                        let value = itemData[key] || 'N/A';
                        if (key === 'test_name') value = value.toUpperCase();
                        testGrid.appendChild(createInfoItem(key.replace(/_/g, ' '), value));
                    });
                    card.appendChild(testGrid);

                    // Financial Details Grid
                    const financialHeader = document.createElement('h5');
                    financialHeader.textContent = 'Financials for this Test';
                    financialHeader.style.marginTop = '1rem';
                    financialHeader.style.borderTop = '1px solid var(--border-color)';
                    financialHeader.style.paddingTop = '1rem';
                    card.appendChild(financialHeader);

                    const paymentGrid = document.createElement('div');
                    paymentGrid.className = 'info-grid';
                    ['total_amount', 'discount', 'advance_amount', 'due_amount', 'payment_method'].forEach(key => {
                        let value = itemData[key] || (key.includes('amount') || key.includes('discount') ? '0.00' : 'N/A');
                        if (key.includes('amount') || key.includes('discount')) {
                            value = `₹${parseFloat(value).toFixed(2)}`;
                        }
                        paymentGrid.appendChild(createInfoItem(key.replace(/_/g, ' '), value));
                    });
                    card.appendChild(paymentGrid);

                    // --- NEW: Add individual controls to each card ---
                    const controlsGrid = document.createElement('div');
                    controlsGrid.className = 'info-grid';
                    controlsGrid.style.marginTop = '1rem';

                    // Test Status Select
                    const testStatusDiv = document.createElement('div');
                    testStatusDiv.className = 'info-item';
                    testStatusDiv.innerHTML = `<span class="label">Test Status</span>
                        <select class="status-select" data-id="${itemData.test_id}" data-item-id="${itemData.item_id || ''}" data-type="${title === 'Original Test Details' ? 'main' : 'item'}">
                            <option value="pending" ${itemData.test_status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="completed" ${itemData.test_status === 'completed' ? 'selected' : ''}>Completed</option>
                            <option value="cancelled" ${itemData.test_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>`;
                    controlsGrid.appendChild(testStatusDiv);

                    // Payment Status Select (for ALL cards)
                    const paymentStatusDiv = document.createElement('div');
                    paymentStatusDiv.className = 'info-item';
                    paymentStatusDiv.innerHTML = `<span class="label">Payment Status</span>
                        <select class="payment-select" data-id="${itemData.test_id}" data-item-id="${itemData.item_id || ''}">
                            <option value="pending" ${itemData.payment_status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="partial" ${itemData.payment_status === 'partial' ? 'selected' : ''}>Partial</option>
                            <option value="paid" ${itemData.payment_status === 'paid' ? 'selected' : ''}>Paid</option>
                        </select>`;
                    controlsGrid.appendChild(paymentStatusDiv);

                    // --- Update Payment Section ---
                    const updateDiv = document.createElement('div');
                    updateDiv.className = 'info-item';
                    updateDiv.innerHTML = `
                        <span class="label">Add Payment</span>
                        <input type="number" min="1" class="payment-input" placeholder="Enter amount" style="margin-left:10px; padding:10px 8px; width:160px; border-radius:5px; border:1px solid #ccc;">
                        <button class="save-payment-btn" style="margin-left:8px; padding:5px 10px;">Save</button>
                    `;

                    // Handle Save button click for this specific card
                    updateDiv.querySelector('.save-payment-btn').addEventListener('click', () => {
                        const amount = updateDiv.querySelector('.payment-input').value;
                        if (amount && !isNaN(amount)) {
                            fetch("../api/update_test_item.php", { // Use the new unified API
                                method: "POST",
                                headers: { "Content-Type": "application/json" },
                                body: JSON.stringify({
                                    test_id: itemData.test_id, // Main order ID
                                    item_id: itemData.item_id || null, // Specific item ID if it exists
                                    amount: parseFloat(amount)
                                })
                            })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message || "Payment updated!", "success");
                                    setTimeout(() => window.location.reload(), 2000);
                                } else {
                                    showToast(resp.message || "Error updating payment", "error");
                                }
                            });
                        }
                    });
                    controlsGrid.appendChild(updateDiv);
                    card.appendChild(controlsGrid);

                    return card;
                };

                // --- Render Original Test Card ---
                // The main `tests` table record is treated as the first item.
                const originalTestCard = createTestItemCard(data, 'Original Test Details');
                drawerContent.appendChild(originalTestCard);


                // --- Render Additional Test Item Cards ---
                if (data.test_items && Array.isArray(data.test_items) && data.test_items.length > 0) {
                    data.test_items.forEach((item, index) => {
                        const itemCard = createTestItemCard(item, `Additional Test Item #${index + 1}`);
                        drawerContent.appendChild(itemCard);
                    });
                }

                // --- Overall Financial Summary Card ---
                let grandTotalAmount = parseFloat(data.total_amount || 0);
                let grandTotalAdvance = parseFloat(data.advance_amount || 0);
                let grandTotalDiscount = parseFloat(data.discount || 0);

                if (data.test_items && Array.isArray(data.test_items)) {
                    data.test_items.forEach(item => {
                        grandTotalAmount += parseFloat(item.total_amount || 0);
                        grandTotalAdvance += parseFloat(item.advance_amount || 0);
                        grandTotalDiscount += parseFloat(item.discount || 0);
                    });
                }
                const grandTotalDue = Math.max(0, grandTotalAmount - grandTotalDiscount - grandTotalAdvance);

                const summaryCard = document.createElement('div');
                summaryCard.className = 'drawer-card summary-card'; // Add a class for special styling
                summaryCard.innerHTML = `<h4>Overall Financial Summary</h4>`;
                const summaryGrid = document.createElement('div');
                summaryGrid.className = 'info-grid';
                summaryGrid.appendChild(createInfoItem('Grand Total', `₹${grandTotalAmount.toFixed(2)}`));
                summaryGrid.appendChild(createInfoItem('Total Discount', `₹${grandTotalDiscount.toFixed(2)}`));
                summaryGrid.appendChild(createInfoItem('Total Paid', `₹${grandTotalAdvance.toFixed(2)}`));
                summaryGrid.appendChild(createInfoItem('Final Due', `₹${grandTotalDue.toFixed(2)}`));
                summaryCard.appendChild(summaryGrid);
                drawerContent.appendChild(summaryCard);

                drawer.addEventListener('change', e => {
                    if (e.target.classList.contains('status-select')) {
                        fetch("../api/update_test_item.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                test_id: e.target.dataset.id, // Main test_id
                                item_id: e.target.dataset.itemId || null, // Specific item_id
                                test_status: e.target.value
                            })
                        })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message, "success");
                                    setTimeout(() => window.location.reload(), 2000);
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
                        fetch("../api/update_test_item.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                test_id: e.target.dataset.id,
                                item_id: e.target.dataset.itemId || null,
                                payment_status: e.target.value
                            })
                        })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message, "success");
                                    setTimeout(() => window.location.reload(), 2000);
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

                // --- NEW: Add 'Add More Test' Button ---
                const drawerFooter = document.createElement('div');
                drawerFooter.className = 'drawer-footer';
                const addMoreTestBtn = document.createElement('button');
                addMoreTestBtn.className = 'action-btn';
                addMoreTestBtn.textContent = 'Add More Test for this Patient';
                addMoreTestBtn.addEventListener('click', () => {
                    // --- FIX: Setup calculator when modal is opened ---
                    const addTestItemModal = document.getElementById('add-test-item-modal');
                    const totalAmountInput = addTestItemModal.querySelector('input[name="total_amount"]');
                    const advanceAmountInput = addTestItemModal.querySelector('input[name="advance_amount"]');
                    const discountInput = addTestItemModal.querySelector('input[name="discount"]');
                    const dueAmountInput = addTestItemModal.querySelector('input[name="due_amount"]');

                    const calculateItemDue = () => {
                        const total = parseFloat(totalAmountInput.value) || 0;
                        const advance = parseFloat(advanceAmountInput.value) || 0;
                        const discount = parseFloat(discountInput.value) || 0;
                        const due = total - discount - advance;
                        dueAmountInput.value = Math.max(0, due).toFixed(2);
                    };
                    
                    calculateItemDue(); // Run once on open

                    // Pass the main test_id to the new item form
                    document.getElementById('item_test_id').value = data.test_id;
                    addTestItemModal.classList.add('is-visible');
                });

                drawerFooter.appendChild(addMoreTestBtn);
                drawerContent.appendChild(drawerFooter);

                drawer.classList.add('open');
            })
            .catch(err => console.error(err));
    });
});

// Close drawer
const addTestItemModal = document.getElementById('add-test-item-modal');
if (addTestItemModal) {
    const closeBtn = addTestItemModal.querySelector('.close-modal-btn');
    closeBtn.addEventListener('click', () => {
        addTestItemModal.classList.remove('is-visible');
    });
    addTestItemModal.addEventListener('click', (e) => {
        if (e.target === addTestItemModal) {
            addTestItemModal.classList.remove('is-visible');
        }
    });

    // --- Due Amount Calculator for Test Item Modal (Listeners) ---
    const totalAmountInput = addTestItemModal.querySelector('input[name="total_amount"]');
    const advanceAmountInput = addTestItemModal.querySelector('input[name="advance_amount"]');
    const discountInput = addTestItemModal.querySelector('input[name="discount"]');
    const dueAmountInput = addTestItemModal.querySelector('input[name="due_amount"]');

    if (totalAmountInput && advanceAmountInput && discountInput && dueAmountInput) {
        const calculateItemDueOnInput = () => {
            const total = parseFloat(totalAmountInput.value) || 0;
            const advance = parseFloat(advanceAmountInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            const due = total - discount - advance;
            dueAmountInput.value = Math.max(0, due).toFixed(2);
        };
        totalAmountInput.addEventListener('input', calculateItemDueOnInput);
        advanceAmountInput.addEventListener('input', calculateItemDueOnInput);
        discountInput.addEventListener('input', calculateItemDueOnInput);
    }

    // --- NEW: Add Test Item Form Submission ---
    const addTestItemForm = document.getElementById('addTestItemForm');
    if (addTestItemForm) {
        addTestItemForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = addTestItemForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            const formData = new FormData(addTestItemForm);
            const data = Object.fromEntries(formData.entries());

            fetch('../api/add_test_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showToast(result.message || 'Test item added successfully!', 'success');
                    addTestItemModal.classList.remove('is-visible');
                    addTestItemForm.reset();
                    setTimeout(() => window.location.reload(), 2000); // Reload to see changes
                } else {
                    showToast(result.message || 'Failed to add test item.', 'error');
                }
            })
            .catch(error => showToast('An error occurred: ' + error.message, 'error'))
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Test Item';
            });
        });
    }
}
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