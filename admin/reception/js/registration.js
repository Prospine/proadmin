document.addEventListener('DOMContentLoaded', () => {
    /* ------------------------------

       Utilities
    ------------------------------ */
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
    const safeText = v => (v === null || v === undefined) ? '' : v;

    const showToast = (msg, type = 'info') => {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            console.error('Toast container not found!');
            return;
        }

        const toast = document.createElement('div');
        toast.classList.add('toast', `toast-${type}`);
        toast.textContent = msg;

        toastContainer.appendChild(toast);

        // Make sure the element is in the DOM before animating
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            // Remove the element after the animation is complete
            toast.addEventListener('transitionend', () => toastContainer.removeChild(toast), { once: true });
        }, 3000);
    };

    /* ------------------------------
       NEW: Table Search & Filter Logic
    ------------------------------ */
    const searchInput = $('#searchInput');
    const statusFilter = $('#statusFilter');
    const genderFilter = $('#genderFilter');
    const referredByFilter = $('#referredByFilter');
    const inquiryTypeFilter = $('#inquiryTypeFilter');
    const conditionFilter = $('#conditionFilter');
    const sortDirectionBtn = $('#sortDirectionBtn');
    const tableBody = $('#registrationTableBody');
    const tableHeaders = $$('th.sortable');

    let currentSort = {
        key: 'created_at', // Default sort
        direction: 'desc' // Default direction
    };

    const processTable = () => {
        if (!tableBody) return;

        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        const genderValue = genderFilter.value.toLowerCase();
        const referredByValue = referredByFilter.value.toLowerCase();
        const inquiryTypeValue = inquiryTypeFilter.value.toLowerCase();
        const conditionValue = conditionFilter.value.toLowerCase();
        const rows = $$('tr', tableBody);
        let visibleRows = 0;

        rows.forEach(row => {
            // Hide "no data" rows during filtering
            if (row.querySelector('td[colspan]')) {
                row.style.display = 'none';
                return;
            }

            const rowText = row.textContent.toLowerCase();
            const statusCell = row.querySelector('.pill');
            const genderCell = row.querySelector('td:nth-child(5)');
            const inquiryTypeCell = row.querySelector('td:nth-child(6)'); // NEW: 6th column is Inquiry Type
            const referredByCell = row.querySelector('td:nth-child(7)'); // 7th column is Referred By
            const conditionCell = row.querySelector('td:nth-child(8)'); // 8th column is Condition Type

            const rowStatus = statusCell ? statusCell.textContent.trim().toLowerCase() : '';
            const rowGender = genderCell ? genderCell.textContent.trim().toLowerCase() : '';
            const rowInquiryType = inquiryTypeCell ? inquiryTypeCell.textContent.trim().toLowerCase() : '';
            const rowReferredBy = referredByCell ? referredByCell.textContent.trim().toLowerCase() : '';
            const rowCondition = conditionCell ? conditionCell.textContent.trim().toLowerCase() : '';

            // Match conditions
            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = statusValue ? rowStatus === statusValue : true;
            const matchesGender = genderValue ? rowGender === genderValue : true;
            const matchesReferredBy = referredByValue ? rowReferredBy === referredByValue : true;
            const matchesInquiryType = inquiryTypeValue ? rowInquiryType === inquiryTypeValue : true;
            const matchesCondition = conditionValue ? rowCondition === conditionValue : true;

            if (matchesSearch && matchesStatus && matchesGender && matchesReferredBy && matchesInquiryType && matchesCondition) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });

        // Handle "no results" message
        let noResultsRow = $('.no-results-row', tableBody);
        if (visibleRows === 0) {
            if (!noResultsRow) {
                noResultsRow = tableBody.insertRow();
                noResultsRow.className = 'no-results-row';
                const cell = noResultsRow.insertCell();
                const colSpan = tableBody.closest('table').querySelector('thead th').length;
                cell.colSpan = colSpan;
                cell.textContent = 'No registrations match your search criteria.';
                cell.style.textAlign = 'center';
            }
        } else {
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }

        // --- NEW: Sorting Logic ---
        const visibleTableRows = $$('tr:not([style*="display: none"])', tableBody);

        visibleTableRows.sort((a, b) => {
            const key = currentSort.key;
            const direction = currentSort.direction === 'asc' ? 1 : -1;

            // Find the correct cell based on the data-key of the header
            const headerIndex = tableHeaders.findIndex(th => th.dataset.key === key);
            if (headerIndex === -1) return 0;

            let valA = a.cells[headerIndex]?.textContent.trim() || '';
            let valB = b.cells[headerIndex]?.textContent.trim() || '';

            // Handle numeric/date/currency sorting
            const isNumeric = tableHeaders[headerIndex].classList.contains('numeric');
            const isDate = key === 'created_at';

            if (isNumeric) {
                valA = parseFloat(valA.replace(/[^0-9.-]+/g, "")) || 0;
                valB = parseFloat(valB.replace(/[^0-9.-]+/g, "")) || 0;
            } else if (isDate) {
                valA = new Date(valA).getTime() || 0;
                valB = new Date(valB).getTime() || 0;
            } else {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }

            if (valA < valB) return -1 * direction;
            if (valA > valB) return 1 * direction;
            return 0;
        });

        // Re-append sorted rows
        visibleTableRows.forEach(row => tableBody.appendChild(row));
    };

    // --- NEW: Sorting Event Listeners ---
    tableHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.key;

            // If same header, toggle direction. Otherwise, set to default 'desc'.
            if (currentSort.key === key) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.key = key;
                currentSort.direction = 'desc';
            }

            // Update sort indicators
            tableHeaders.forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
            header.classList.add(currentSort.direction === 'asc' ? 'sort-asc' : 'sort-desc');

            processTable(); // Re-run filtering and sorting
        });
    });

    if (sortDirectionBtn) {
        sortDirectionBtn.addEventListener('click', () => {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            // Update active header indicator
            const activeHeader = $(`th[data-key="${currentSort.key}"]`);
            if (activeHeader) {
                activeHeader.classList.toggle('sort-asc');
                activeHeader.classList.toggle('sort-desc');
            }
            processTable();
        });
    }

    // Attach event listeners if filter elements exist
    [searchInput, statusFilter, genderFilter, referredByFilter, inquiryTypeFilter, conditionFilter].forEach(el => {
        if (el) el.addEventListener('input', processTable);
    });


    /* ------------------------------
       Ensure single drawer container
    ------------------------------ */
    let drawersContainer = $('#app-drawers');
    if (!drawersContainer) {
        drawersContainer = document.createElement('div');
        drawersContainer.id = 'app-drawers';
        document.body.appendChild(drawersContainer);
    }

    // Create main drawer if missing
    let mainDrawer = $('#drawer', drawersContainer);
    if (!mainDrawer) {
        mainDrawer = document.createElement('div');
        mainDrawer.id = 'drawer';
        mainDrawer.className = 'drawer';
        mainDrawer.innerHTML = `
      <div class="drawer-content">
        <button id="closeDrawer" class="drawer-close">&times;</button>
        <div id="drawer-body"></div>
      </div>
    `;
        drawersContainer.appendChild(mainDrawer);
    }

    /* ------------------------------
       Element references (safe)
    ------------------------------ */
    const drawer = mainDrawer;
    const drawerBody = $('#drawer-body', drawer);
    const closeDrawerBtn = $('#closeDrawer', drawer);

    const addDrawer = addPatientDrawer;
    const addPatientForm = $('#addPatientForm', addDrawer);
    const closeAddPatientBtn = $('.add-drawer-close', addDrawer);
    const registrationIdInput = $('#registrationId', addDrawer);
    const treatmentDaysGroup = $('#treatmentDaysGroup', addDrawer);
    const treatmentDaysInput = $('#treatmentDays', addDrawer);
    const startDateInput = $('#startDate', addDrawer);
    const discountInput = $('#discount', addDrawer);
    const advancePaymentInput = $('#advancePayment', addDrawer);
    const totalCostInput = $('#totalCost', addDrawer);
    const discountApprovedByInput = $('#discountApprovedBy', addDrawer);
    const dueAmountInput = $('#dueAmount', addDrawer);
    const treatmentTimeSlotSelect = $('#treatmentTimeSlot', addDrawer);
    const treatmentOptions = $$('input[name="treatmentType"]', addDrawer); // might be empty

    // NEW: Speech Drawer element references
    const addSpeechDrawer = $('#addSpeechPatientDrawer');
    const addSpeechPatientForm = $('#addSpeechPatientForm', addSpeechDrawer);
    const closeAddSpeechPatientBtn = $('.add-drawer-close', addSpeechDrawer);
    const speechRegistrationIdInput = $('#speechRegistrationId', addSpeechDrawer);
    const speechTreatmentDaysGroup = $('#speechTreatmentDaysGroup', addSpeechDrawer);
    const speechTreatmentDaysInput = $('#speechTreatmentDays', addSpeechDrawer);
    const speechStartDateInput = $('#speechStartDate', addSpeechDrawer);
    const speechDiscountInput = $('#speechDiscount', addSpeechDrawer);
    const speechAdvancePaymentInput = $('#speechAdvancePayment', addSpeechDrawer);
    const speechTotalCostInput = $('#speechTotalCost', addSpeechDrawer);
    const speechDiscountApprovedByInput = $('#speechDiscountApprovedBy', addSpeechDrawer);
    const speechDueAmountInput = $('#speechDueAmount', addSpeechDrawer);
    const speechTreatmentTimeSlotSelect = $('#speechTreatmentTimeSlot', addSpeechDrawer);
    const speechTreatmentOptions = $$('input[name="treatmentType"]', addSpeechDrawer);

    /* ------------------------------
       Drawer open/close helpers
    ------------------------------ */
    const openMainDrawer = () => {
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
    };
    const closeMainDrawer = () => {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        // optionally clear content: drawerBody.innerHTML = '';
    };

    const openAddPatientDrawer = (regId = '') => {
        if (!addDrawer) return;
        if (addPatientForm) addPatientForm.reset(); // Reset form first

        if (registrationIdInput) registrationIdInput.value = regId; // Set new ID

        // NEW: Set today's date and fetch slots every time the drawer opens
        if (startDateInput && treatmentTimeSlotSelect) {
            const today = new Date().toISOString().split('T')[0];
            startDateInput.value = today;
            fetchAndPopulateSlots(today, treatmentTimeSlotSelect, 'physio');
        }

        addDrawer.classList.add('is-open'); // Show the drawer
        addDrawer.setAttribute('aria-hidden', 'false');
    };
    const closeAddPatientDrawer = () => {
        if (!addDrawer) return;
        addDrawer.classList.remove('is-open');
        addDrawer.setAttribute('aria-hidden', 'true');
        if (addPatientForm) addPatientForm.reset();
        // NEW: Also reset date and time slot fields
        if (totalCostInput) totalCostInput.value = '';
        if (dueAmountInput) dueAmountInput.value = '';
    };

    // NEW: Speech Drawer helpers
    const openAddSpeechPatientDrawer = (regId = '') => {
        if (speechRegistrationIdInput) speechRegistrationIdInput.value = regId;

        // NEW: Set today's date and fetch slots every time the drawer opens
        if (speechStartDateInput && speechTreatmentTimeSlotSelect) {
            const today = new Date().toISOString().split('T')[0];
            speechStartDateInput.value = today;
            fetchAndPopulateSlots(today, speechTreatmentTimeSlotSelect, 'speech_therapy');
        }

        addSpeechDrawer.classList.add('is-open');
        addSpeechDrawer.setAttribute('aria-hidden', 'false');
    };
    const closeAddSpeechPatientDrawer = () => {
        if (!addSpeechDrawer) return;
        addSpeechDrawer.classList.remove('is-open');
        addSpeechDrawer.setAttribute('aria-hidden', 'true');
        if (addSpeechPatientForm) addSpeechPatientForm.reset();
        // NEW: Also reset date and time slot fields
        if (speechTotalCostInput) speechTotalCostInput.value = '';
        if (speechDueAmountInput) speechDueAmountInput.value = '';
        if (speechStartDateInput) speechStartDateInput.value = '';
        if (speechTreatmentTimeSlotSelect) speechTreatmentTimeSlotSelect.innerHTML = '<option value="">Select a date first</option>';
    };

    // Close buttons
    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeMainDrawer);
    if (closeAddPatientBtn) closeAddPatientBtn.addEventListener('click', closeAddPatientDrawer);
    if (closeAddSpeechPatientBtn) closeAddSpeechPatientBtn.addEventListener('click', closeAddSpeechPatientDrawer);

    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') {
            // close whichever is open (prioritize add drawer)
            if (addSpeechDrawer.classList.contains('is-open')) closeAddSpeechPatientDrawer();
            else if (addDrawer.classList.contains('is-open')) closeAddPatientDrawer();
            else if (drawer.classList.contains('open')) closeMainDrawer();
        }
    });

    /* ------------------------------
       Status update helper (reusable)
    ------------------------------ */
    async function updateStatus(id, type, status, pillElement = null) {
        try {
            const res = await fetch('../api/update_registration_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    id,
                    type,
                    status
                })
            });
            const json = await res.json();
            if (json.success) {
                if (pillElement) {
                    pillElement.textContent = status;
                    pillElement.className = 'pill ' + status.toLowerCase();
                }
                showToast('Status updated', 'success');
                return true;
            } else {
                showToast(json.message || 'Update failed', 'error');
                return false;
            }
        } catch (err) {
            console.error('updateStatus error', err);
            showToast('Network error', 'error');
            return false;
        }
    }

    /* ------------------------------
       Table status selects (existing DOM)
       Use safe-checks so missing selects don't crash
    ------------------------------ */
    $$('.registration-table select[data-id]').forEach(select => {
        // if you don't have container class, fallback to table select all
    });

    // Fallback: attach to all table selects with data-id
    $$('table select[data-id]').forEach(select => {
        select.addEventListener('change', function () {
            const id = this.dataset.id;
            const type = this.dataset.type || 'registration'; // fallback
            const status = this.value;
            const pill = this.closest('tr') ? this.closest('tr').querySelector('.pill') : null;
            updateStatus(id, type, status, pill);
        });
    });

    /* ------------------------------
       Main click handler - delegated
       Handles:
        - Open main drawer on .action-btn
        - Open add patient drawer on [data-open="add-patient"]
        - Close add drawer on .add-drawer-close (already bound above but kept for completeness)
    ------------------------------ */
    document.addEventListener('click', async (e) => {
        const actionBtn = e.target.closest('.action-btn');
        if (actionBtn) {
            e.preventDefault();
            const id = actionBtn.dataset.id;
            if (!id) {
                console.warn('action-btn clicked but no data-id');
                return;
            }

            openMainDrawer();
            drawerBody.innerHTML = '<p>Loading...</p>';

            try {
                const res = await fetch(`../api/get_registration.php?id=${encodeURIComponent(id)}`);
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                const data = await res.json();

                // Build drawer HTML (use safeText to avoid 'undefined')
                drawerBody.innerHTML = `
          <div class="PatientBill">
            <a href="registration_bill.php?registration_id=${safeText(data.registration_id)}"><button id="printBillBtn">Print Bill</button></a>
          </div>
          <div class="drawer-header2">
            <div class="drawer-title">
                <h2 data-field="patient_name">${safeText(data.patient_name)}</h2>
                <br>
                <small>ID: ${safeText(data.registration_id)} • ${safeText(data.created_at)}</small>
            </div>
            <div class="drawer-actions">
                <button id="editRegistrationBtn" class="action-btn" data-id="${safeText(data.registration_id)}">Edit</button>
                <button id="saveRegistrationBtn" class="action-btn" data-id="${safeText(data.registration_id)}" style="display: none;">Save</button>
                <button id="cancelEditBtn" class="action-btn secondary" style="display: none;">Cancel</button>
                <span class="pill status ${safeText((data.status || '').toLowerCase())}">${safeText(data.status)}</span>
            </div>
          </div>
          <div class="info-grid">
            <div class="info-item"><strong>Phone</strong><span>${safeText(data.phone_number)}</span></div>
            <div class="info-item"><strong>Email</strong><span>${safeText(data.email) || 'N/A'}</span></div>
            <div class="info-item"><strong>Age</strong><span>${safeText(data.age)}</span></div>
            <div class="info-item"><strong>Gender</strong><span>${safeText(data.gender)}</span></div>
            <div class="info-item"><strong>Condition</strong><span>${safeText(data.chief_complain)}</span></div>
            <div class="info-item"><strong>Inquiry Type</strong><span>${safeText(data.consultation_type) || 'N/A'}</span></div>
            <div class="info-item"><strong>Source</strong><span>${safeText(data.referralSource) || 'N/A'}</span></div>
            <div class="info-item"><strong>Referral By</strong><span>${safeText(data.reffered_by) || 'N/A'}</span></div>
            <div class="info-item"><strong>Consultation</strong><span>₹ ${safeText(data.consultation_amount)}</span></div>
            <div class="info-item"><strong>Payment</strong><span>${safeText(data.payment_method) || 'N/A'}</span></div>
            <div class="info-item"><strong>Address</strong><span>${safeText(data.address) || 'N/A'}</span></div>
            <div class="info-item"><strong>Doctor Notes</strong><span>${safeText(data.doctor_notes) || 'N/A'}</span></div>
            <div class="info-item"><strong>Prescription</strong><span>${safeText(data.prescription) || 'N/A'}</span></div>
            <div class="info-item"><strong>Follow Up Date</strong><span>${safeText(data.follow_up_date) || 'N/A'}</span></div>
            <div class="info-item"><strong>Remarks</strong><span id="remarksDisplay">${safeText(data.remarks) || 'No remarks available.'}</span></div>
          </div>
          <div class="footer-grid">
          <div class="addToPatient">
          <p><strong>Add to Patients</strong></p>
          <div class="btn-group" style="display: flex; gap: 10px;">
          <button id="addToPatientBtn" data-id="${safeText(data.registration_id)}" data-open="add-patient">Add to Physio</button>
          <button id="addToPatientBtnSpch" data-id="${safeText(data.registration_id)}">Add to Speech</button>
          </div>
          <div id="patientMessage" class="patient-message">
              <p><strong>Patient Message</strong></p>
              ${safeText(data.patient_message)}
          </div
          </div>
          </div>
                <div class="addremarks">
                    <h3 class="section-title">Add Remarks</h3>
                    <textarea id="remarksTextarea"></textarea>
                    <button id="submitRemarkBtn" data-id="${safeText(data.registration_id)}">Submit</button>
                </div>
        `;

                // --- NEW: Edit/Save/Cancel Logic ---
                const originalData = { ...data }; // Store original data for cancellation

                const toggleEditMode = (isEditing) => {
                    const infoItems = $$('.info-item', drawerBody);
                    const patientNameH2 = $('h2[data-field="patient_name"]', drawerBody);

                    // Toggle buttons
                    $('#editRegistrationBtn', drawerBody).style.display = isEditing ? 'none' : 'inline-block';
                    $('#saveRegistrationBtn', drawerBody).style.display = isEditing ? 'inline-block' : 'none';
                    $('#cancelEditBtn', drawerBody).style.display = isEditing ? 'inline-block' : 'none';

                    // Toggle patient name
                    if (isEditing) {
                        const currentName = patientNameH2.textContent;
                        patientNameH2.innerHTML = `<input type="text" class="inline-edit" data-field="patient_name" value="${currentName}">`;
                    } else {
                        patientNameH2.textContent = originalData.patient_name;
                    }

                    // Toggle info grid items
                    infoItems.forEach(item => {
                        let label = item.querySelector('strong').textContent.toLowerCase().replace(/ /g, '_');
                        
                        // --- FIX: Map UI labels to correct database column names ---
                        const fieldMap = {
                            'phone': 'phone_number',
                            'condition': 'chief_complain',
                            'inquiry_type': 'consultation_type',
                            'source': 'referralSource',
                            'referral_by': 'reffered_by',
                            'consultation': 'consultation_amount',
                            'payment': 'payment_method'
                        };

                        if (fieldMap[label]) {
                            label = fieldMap[label];
                        }

                        const valueSpan = item.querySelector('span');
                        if (!valueSpan) return;

                        const currentValue = originalData[label] || '';

                        if (isEditing) {
                            let inputHtml = `<input type="text" class="inline-edit" data-field="${label}" value="${currentValue}">`;
                            
                            // Special case for consultation amount to remove currency symbol
                            if (label === 'consultation_amount') {
                                const numericValue = currentValue.replace(/[^0-9.-]+/g, "") || '0';
                                inputHtml = `<input type="number" class="inline-edit" data-field="${label}" value="${numericValue}">`;
                            }
                            if (label === 'gender') {
                                inputHtml = `
                                    <select class="inline-edit" data-field="gender">
                                        <option value="Male" ${currentValue === 'Male' ? 'selected' : ''}>Male</option>
                                        <option value="Female" ${currentValue === 'Female' ? 'selected' : ''}>Female</option>
                                        <option value="Other" ${currentValue === 'Other' ? 'selected' : ''}>Other</option>
                                    </select>`;
                            } else if (label === 'age') {
                                inputHtml = `<input type="number" class="inline-edit" data-field="age" value="${currentValue}">`;
                            } else if (label.includes('date')) {
                                inputHtml = `<input type="date" class="inline-edit" data-field="${label}" value="${currentValue}">`;
                            }
                            valueSpan.innerHTML = inputHtml;
                        } else {
                            valueSpan.innerHTML = currentValue || 'N/A';
                        }
                    });
                };

                $('#editRegistrationBtn', drawerBody).addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent the main document click listener from re-firing
                    toggleEditMode(true);
                });
                $('#cancelEditBtn', drawerBody).addEventListener('click', () => toggleEditMode(false));

                $('#saveRegistrationBtn', drawerBody).addEventListener('click', async (e) => {
                    const button = e.target;
                    button.disabled = true;
                    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

                    const updatedData = {
                        registration_id: button.dataset.id
                    };

                    $$('.inline-edit', drawerBody).forEach(input => {
                        updatedData[input.dataset.field] = input.value;
                    });

                    try {
                        const res = await fetch('../api/update_registration_details.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(updatedData)
                        });
                        const result = await res.json();

                        if (result.success) {
                            showToast('Details updated successfully!', 'success');
                            // Update originalData with new data and exit edit mode
                            Object.assign(originalData, updatedData);
                            toggleEditMode(false);
                            // Manually update the displayed name
                            $('h2[data-field="patient_name"]', drawerBody).textContent = updatedData.patient_name;
                            // Optionally, reload the page to reflect table changes
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showToast(result.message || 'Failed to save changes.', 'error');
                        }
                    } catch (err) {
                        console.error('Save error:', err);
                        showToast('A network error occurred.', 'error');
                    } finally {
                        button.disabled = false;
                        button.textContent = 'Save';
                    }
                });


                // Attach event handlers inside drawer via delegation
                // Submit remark
                const submitRemarkBtn = $('#submitRemarkBtn', drawerBody);
                if (submitRemarkBtn) {
                    submitRemarkBtn.addEventListener('click', async (ev) => {
                        ev.preventDefault();
                        const inquiryId = submitRemarkBtn.dataset.id;
                        const remarksEl = $('#remarksTextarea', drawerBody);
                        const remarks = remarksEl ? remarksEl.value.trim() : '';
                        if (!remarks) {
                            showToast('Remarks cannot be empty.', 'error');
                            return;
                        }
                        try {
                            const saveRes = await fetch('../api/save_remark.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    registration_id: inquiryId,
                                    remarks
                                })
                            });
                            const result = await saveRes.json();
                            if (result.success) {
                                showToast('Remarks saved successfully!', 'success');
                                const remarksDisplay = $('#remarksDisplay', drawerBody);
                                if (remarksDisplay) remarksDisplay.textContent = remarks;
                                if (remarksEl) remarksEl.value = '';
                            } else {
                                showToast(`Error: ${result.message}`, 'error');
                            }
                        } catch (err) {
                            console.error('save remark error', err);
                            showToast('Failed to save remarks.', 'error');
                        }
                    });
                }

                // Add to patient button (opens add patient drawer)
                const addToPatientBtn = $('#addToPatientBtn', drawerBody);
                if (addToPatientBtn) {
                    addToPatientBtn.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        const regId = addToPatientBtn.dataset.id;
                        openAddPatientDrawer(regId);
                    });
                }

                // NEW: Add to Speech patient button
                const addToPatientBtnSpch = $('#addToPatientBtnSpch', drawerBody);
                if (addToPatientBtnSpch) {
                    addToPatientBtnSpch.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        const regId = addToPatientBtnSpch.dataset.id;
                        openAddSpeechPatientDrawer(regId);
                    });
                }

                // Optionally: status dropdown in drawer (if you add one)
                const statusDropdown = $('.status-dropdown-drawer', drawerBody);
                if (statusDropdown) {
                    statusDropdown.addEventListener('change', () => {
                        const sid = statusDropdown.dataset.id;
                        const stype = statusDropdown.dataset.type || 'registration';
                        const svalue = statusDropdown.value;
                        const pill = $('.pill', drawerBody) || $('.pill', drawer);
                        updateStatus(sid, stype, svalue, pill);
                    });
                }

            } catch (err) {
                console.error('Drawer fetch error', err);
                drawerBody.innerHTML = '<p>Failed to load registration details.</p>';
                showToast('Failed to load registration details', 'error');
            }

            return; // handled click
        } // end actionBtn

        // if a control has data-open="add-patient" (supports other elements too)
        const addOpen = e.target.closest('[data-open="add-patient"]');
        if (addOpen) {
            const rid = addOpen.dataset.id || addOpen.getAttribute('data-id') || '';
            openAddPatientDrawer(rid);
            return;
        }

        // if add-drawer-close clicked (fallback)
        if (e.target.closest('.add-drawer-close')) {
            closeAddPatientDrawer();
            return;
        }

        // NEW: if speech-drawer-close clicked
        if (e.target.closest('#addSpeechPatientDrawer .add-drawer-close')) {
            closeAddSpeechPatientDrawer();
            return;
        }
    }); // end document click delegation

    /* ------------------------------
       Add-patient form logic (calculations)
       Only wire handlers if form elements exist
    ------------------------------ */

    let selectedTreatmentCost = 0;

    const updateCalculations = () => {
        if (!treatmentDaysInput || !discountInput || !advancePaymentInput || !totalCostInput || !dueAmountInput || !discountApprovedByInput) return;
        const days = parseInt(treatmentDaysInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const advancePayment = parseFloat(advancePaymentInput.value) || 0;
        const totalCost = selectedTreatmentCost === 30000 ? 30000 : selectedTreatmentCost * days;
        const finalCost = totalCost * (1 - discount / 100);
        const dueAmount = finalCost - advancePayment;

        // NEW: Make 'Approved By' required if there is a discount
        discountApprovedByInput.required = (discount > 0);

        totalCostInput.value = isFinite(finalCost) ? finalCost.toFixed(2) : '';
        dueAmountInput.value = isFinite(dueAmount) ? dueAmount.toFixed(2) : '';
    };

    const updateEndDate = () => {
        if (!treatmentDaysInput || !startDateInput) return;
        const days = parseInt(treatmentDaysInput.value) || 0;
        const startDate = startDateInput.value;
        if (days && startDate) {
            const date = new Date(startDate);
            date.setDate(date.getDate() + days - 1);
            const str = date.toISOString().slice(0, 10);
            const endDateInput = $('#endDate', addDrawer);
            if (endDateInput) endDateInput.value = str;
        } else {
            const endDateInput = $('#endDate', addDrawer);
            if (endDateInput) endDateInput.value = '';
        }
    };

    // --- NEW: Treatment Time Slot Logic ---
    const generateTimeSlots = (serviceType = 'physio') => {
        const slots = [];
        let start, end, interval;

        if (serviceType === 'physio') {
            start = new Date('1970-01-01T09:00:00');
            end = new Date('1970-01-01T19:00:00');
            interval = 90; // 1.5 hours in minutes
        } else { // speech_therapy
            start = new Date('1970-01-01T15:00:00');
            end = new Date('1970-01-01T19:00:00');
            interval = 60; // 1 hour in minutes
        }

        while (start < end) {
            const time = start.toTimeString().substring(0, 5); // HH:mm
            const label = start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            slots.push({ time, label });
            start.setMinutes(start.getMinutes() + interval);
        }
        return slots;
    };

    const fetchAndPopulateSlots = async (dateString, slotSelect, serviceType) => {
        if (!dateString || !slotSelect) return;

        slotSelect.innerHTML = '<option value="">Loading slots...</option>';
        slotSelect.disabled = true;

        try {
            const res = await fetch(`../api/get_treatment_slots.php?date=${dateString}&service_type=${serviceType}`);
            const data = await res.json();

            slotSelect.innerHTML = ''; // Clear loading message

            if (!data.success) {
                throw new Error(data.message || 'Failed to load slots.');
            }

            const capacity = serviceType === 'physio' ? 10 : 1;
            const allSlots = generateTimeSlots(serviceType);

            allSlots.forEach(slot => {
                const bookedCount = data.booked[`${slot.time}:00`] || 0;
                const isFull = bookedCount >= capacity;
                const option = document.createElement('option');
                option.value = slot.time;
                option.textContent = `${slot.label} (${bookedCount}/${capacity} booked)`;
                option.disabled = isFull;
                slotSelect.appendChild(option);
            });

        } catch (error) {
            console.error(`Error fetching ${serviceType} slots:`, error);
            slotSelect.innerHTML = `<option value="">Error loading slots</option>`;
        } finally {
            slotSelect.disabled = false;
        }
    };


    // NEW: Speech Drawer Calculation Logic
    let selectedSpeechTreatmentCost = 0;

    const updateSpeechCalculations = () => {
        if (!speechTreatmentDaysInput || !speechDiscountInput || !speechAdvancePaymentInput || !speechTotalCostInput || !speechDueAmountInput || !speechDiscountApprovedByInput) return;
        const days = parseInt(speechTreatmentDaysInput.value) || 0;
        const discount = parseFloat(speechDiscountInput.value) || 0;
        const advancePayment = parseFloat(speechAdvancePaymentInput.value) || 0;
        const totalCost = selectedSpeechTreatmentCost === 11000 ? 11000 : selectedSpeechTreatmentCost * days;
        const finalCost = totalCost * (1 - discount / 100);
        const dueAmount = finalCost - advancePayment;

        speechDiscountApprovedByInput.required = (discount > 0);

        speechTotalCostInput.value = isFinite(finalCost) ? finalCost.toFixed(2) : '';
        speechDueAmountInput.value = isFinite(dueAmount) ? dueAmount.toFixed(2) : '';
    };

    const updateSpeechEndDate = () => {
        if (!speechTreatmentDaysInput || !speechStartDateInput) return;
        const days = parseInt(speechTreatmentDaysInput.value) || 0;
        const startDate = speechStartDateInput.value;
        if (days && startDate) {
            const date = new Date(startDate);
            date.setDate(date.getDate() + days - 1);
            const str = date.toISOString().slice(0, 10);
            const endDateInput = $('#speechEndDate', addSpeechDrawer);
            if (endDateInput) endDateInput.value = str;
        } else {
            const endDateInput = $('#speechEndDate', addSpeechDrawer);
            if (endDateInput) endDateInput.value = '';
        }
    };

    // NEW: Speech Treatment option change
    if (speechTreatmentOptions && speechTreatmentOptions.length) {
        speechTreatmentOptions.forEach(option => {
            option.addEventListener('change', (e) => {
                const parentLabel = e.target.closest('label');
                if (!parentLabel) return;
                $$('label.treatment-option', addSpeechDrawer).forEach(l => l.classList.remove('selected'));
                parentLabel.classList.add('selected');
                selectedSpeechTreatmentCost = parseInt(parentLabel.dataset.cost) || 0;
                speechTreatmentDaysInput.readOnly = e.target.value === 'package';
                speechTreatmentDaysInput.value = e.target.value === 'package' ? 26 : '';
                if (speechTreatmentDaysGroup) speechTreatmentDaysGroup.style.display = 'block';
                updateSpeechCalculations();
                updateSpeechEndDate();
            });
        });
    }


    // Treatment option change
    if (treatmentOptions && treatmentOptions.length) {
        treatmentOptions.forEach(option => {
            option.addEventListener('change', (e) => {
                const parentLabel = e.target.closest('label');
                if (!parentLabel) return;
                $$('label.treatment-option', addDrawer).forEach(l => l.classList.remove('selected'));
                parentLabel.classList.add('selected');
                selectedTreatmentCost = parseInt(parentLabel.dataset.cost) || 0;
                treatmentDaysInput.readOnly = e.target.value === 'package';
                treatmentDaysInput.value = e.target.value === 'package' ? 21 : '';
                if (treatmentDaysGroup) treatmentDaysGroup.style.display = 'block';
                updateCalculations();
                updateEndDate();
            });
        });
    }

    if (treatmentDaysInput) treatmentDaysInput.addEventListener('input', () => {
        updateCalculations();
        updateEndDate();
    });
    if (startDateInput) startDateInput.addEventListener('change', updateEndDate);
    if (discountInput) discountInput.addEventListener('input', updateCalculations); // Physio
    if (advancePaymentInput) advancePaymentInput.addEventListener('input', updateCalculations); // Physio

    // --- NEW: Consolidated Date Change Listeners ---
    // Physio Drawer: When start date changes, update end date AND fetch slots.
    if (startDateInput) {
        startDateInput.addEventListener('change', (event) => {
            updateEndDate(); // Update the end date based on treatment days
            if (treatmentTimeSlotSelect) {
                fetchAndPopulateSlots(event.target.value, treatmentTimeSlotSelect, 'physio');
            }
        });
    }

    // Speech Drawer: When start date changes, update end date AND fetch slots.
    if (speechStartDateInput) {
        speechStartDateInput.addEventListener('change', (event) => {
            updateSpeechEndDate(); // Update the end date based on treatment days
            if (speechTreatmentTimeSlotSelect) {
                fetchAndPopulateSlots(event.target.value, speechTreatmentTimeSlotSelect, 'speech_therapy');
            }
        });
    }

    // Add patient form submit
    if (addPatientForm) {
        addPatientForm.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const formData = new FormData(addPatientForm);
            const dataObj = Object.fromEntries(formData.entries());
            const registrationId = dataObj.registrationId || '';

            try {
                // check if patient exists
                const checkRes = await fetch(`../api/check_patient.php?registrationId=${encodeURIComponent(registrationId)}`);
                const checkJson = await checkRes.json();
                if (checkJson.exists) {
                    showToast('Patient already exists.', 'error');
                    return;
                }

                showToast('Adding patient...', 'success');

                const res = await fetch('../api/add_patient.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dataObj)
                });
                const result = await res.json();
                if (result.success) {
                    showToast('Patient added successfully!', 'success');
                    closeAddPatientDrawer();
                } else {
                    showToast(`Error: ${result.message}`, 'error');
                }
            } catch (err) {
                console.error('add patient error', err);
                showToast('Failed to add patient.', 'error');
            }
        });
    }

    // NEW: Add Speech Patient form submit
    if (addSpeechPatientForm) {
        addSpeechPatientForm.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const formData = new FormData(addSpeechPatientForm);
            const dataObj = Object.fromEntries(formData.entries());

            try {
                const res = await fetch('../api/add_speech_patient.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dataObj)
                });
                const result = await res.json();
                if (result.success) {
                    showToast('Speech Patient added successfully!', 'success');
                    closeAddSpeechPatientDrawer();
                    // Optional: reload the main registration table after a delay
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(`Error: ${result.message}`, 'error');
                }
            } catch (err) {
                console.error('add speech patient error', err);
                showToast('Failed to add speech patient.', 'error');
            }
        });
    }

    /* ------------------------------
       NEW: Photo Capture Logic
    ------------------------------ */
    const photoModalOverlay = $('#photo-modal-overlay');
    if (photoModalOverlay) {
        const video = $('#webcam-feed');
        const canvas = $('#photo-canvas');
        const webcamError = $('#webcam-error');
        const initialControls = $('#initial-controls');
        const confirmControls = $('#confirm-controls');
        const captureBtn = $('#capture-photo-btn');
        const retakeBtn = $('#retake-photo-btn');
        const uploadBtn = $('#upload-photo-btn');
        const closeModalBtns = $$('#close-photo-modal-1, #close-photo-modal-2');

        let stream = null;
        let currentRegistrationId = null;

        const startWebcam = async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                video.srcObject = stream;
                webcamError.classList.add('hidden');
            } catch (err) {
                console.error("Error accessing webcam: ", err);
                webcamError.classList.remove('hidden');
            }
        };

        const stopWebcam = () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        };

        const openPhotoModal = (registrationId) => {
            currentRegistrationId = registrationId;
            photoModalOverlay.style.display = 'flex';
            video.classList.remove('hidden');
            canvas.classList.add('hidden');
            initialControls.classList.remove('hidden');
            confirmControls.classList.add('hidden');
            startWebcam();
        };

        const closePhotoModal = () => {
            photoModalOverlay.style.display = 'none';
            stopWebcam();
        };

        const capturePhoto = () => {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.translate(canvas.width, 0);
            context.scale(-1, 1); // Un-mirror
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            video.classList.add('hidden');
            canvas.classList.remove('hidden');
            initialControls.classList.add('hidden');
            confirmControls.classList.remove('hidden');
        };

        const retakePhoto = () => {
            video.classList.remove('hidden');
            canvas.classList.add('hidden');
            initialControls.classList.remove('hidden');
            confirmControls.classList.add('hidden');
        };

        const uploadPhoto = async () => {
            const imageData = canvas.toDataURL('image/jpeg');
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';

            try {
                const response = await fetch('../api/upload_patient_photo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ registration_id: currentRegistrationId, image_data: imageData })
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    showToast('Photo uploaded successfully!', 'success');
                    closePhotoModal();
                    // Reload to show the new photo
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(result.message || 'Failed to upload photo.');
                }
            } catch (error) {
                console.error('Upload failed:', error);
                showToast('Error: ' + error.message, 'error');
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Upload';
            }
        };

        // Event Listeners
        $$('.photo-cell').forEach(cell => cell.addEventListener('click', () => {
            const registrationId = cell.dataset.registrationId;
            if (registrationId) openPhotoModal(registrationId);
        }));
        closeModalBtns.forEach(btn => btn.addEventListener('click', closePhotoModal));
        captureBtn.addEventListener('click', capturePhoto);
        retakeBtn.addEventListener('click', retakePhoto);
        uploadBtn.addEventListener('click', uploadPhoto);
    }

    /* ------------------------------
       End of module
    ------------------------------ */
});