document.addEventListener('DOMContentLoaded', () => {
    /* ------------------------------
       Utilities
    ------------------------------ */
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
    const safeText = v => (v === null || v === undefined) ? '' : v;

    const showToast = (msg, type = 'info') => {
        // placeholder: your existing showToast implementation
        console.log(`[${type}] ${msg}`);
    };

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
    const dueAmountInput = $('#dueAmount', addDrawer);
    const treatmentOptions = $$('input[name="treatmentType"]', addDrawer); // might be empty

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
        if (registrationIdInput) registrationIdInput.value = regId;
        addDrawer.classList.add('is-open');
        addDrawer.setAttribute('aria-hidden', 'false');
    };
    const closeAddPatientDrawer = () => {
        addDrawer.classList.remove('is-open');
        addDrawer.setAttribute('aria-hidden', 'true');
        if (addPatientForm) addPatientForm.reset();
        // reset calculated fields
        if (totalCostInput) totalCostInput.value = '';
        if (dueAmountInput) dueAmountInput.value = '';
    };

    // Close buttons
    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeMainDrawer);
    if (closeAddPatientBtn) closeAddPatientBtn.addEventListener('click', closeAddPatientDrawer);

    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') {
            // close whichever is open (prioritize add drawer)
            if (addDrawer.classList.contains('is-open')) closeAddPatientDrawer();
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
            <div>
              <h2>${safeText(data.patient_name)}</h2>
              <br>
              <small>ID: ${safeText(data.registration_id)} • ${safeText(data.created_at)}</small>
            </div>
            <span class="pill status ${safeText((data.status || '').toLowerCase())}">${safeText(data.status)}</span>
          </div>
          <div class="info-grid">
            <div class="info-item"><strong>Phone</strong><span>${safeText(data.phone_number)}</span></div>
            <div class="info-item"><strong>Email</strong><span>${safeText(data.email) || 'N/A'}</span></div>
            <div class="info-item"><strong>Age</strong><span>${safeText(data.age)}</span></div>
            <div class="info-item"><strong>Gender</strong><span>${safeText(data.gender)}</span></div>
            <div class="info-item"><strong>Condition</strong><span>${safeText(data.chief_complain)}</span></div>
            <div class="info-item"><strong>Inquiry Type</strong><span>${safeText(data.consultation_type) || 'N/A'}</span></div>
            <div class="info-item"><strong>Source</strong><span>${safeText(data.referralSource) || 'N/A'}</span></div>
            <div class="info-item"><strong>Consultation</strong><span>₹ ${safeText(data.consultation_amount)}</span></div>
            <div class="info-item"><strong>Payment</strong><span>${safeText(data.payment_method) || 'N/A'}</span></div>
            <div class="info-item"><strong>Doctor Notes</strong><span>${safeText(data.doctor_notes) || 'N/A'}</span></div>
            <div class="info-item"><strong>Prescription</strong><span>${safeText(data.prescription) || 'N/A'}</span></div>
            <div class="info-item"><strong>Follow Up Date</strong><span>${safeText(data.follow_up_date) || 'N/A'}</span></div>
            <div class="info-item"><strong>Remarks</strong><span id="remarksDisplay">${safeText(data.remarks) || 'No remarks available.'}</span></div>
          </div>
          <div class="addremarks">
            <h3 class="section-title">Add Remarks</h3>
            <textarea id="remarksTextarea"></textarea>
            <button id="submitRemarkBtn" data-id="${safeText(data.registration_id)}">Submit</button>
          </div>
          <div class="imp">
            <div class="addToPatient">
              <p><strong>Add to Patients</strong></p>
              <button id="addToPatientBtn" data-id="${safeText(data.registration_id)}" data-open="add-patient">Add</button>
            </div>
          </div>
        `;

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
    }); // end document click delegation

    /* ------------------------------
       Add-patient form logic (calculations)
       Only wire handlers if form elements exist
    ------------------------------ */

    let selectedTreatmentCost = 0;

    const updateCalculations = () => {
        if (!treatmentDaysInput || !discountInput || !advancePaymentInput || !totalCostInput || !dueAmountInput) return;
        const days = parseInt(treatmentDaysInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        const advancePayment = parseFloat(advancePaymentInput.value) || 0;
        const totalCost = selectedTreatmentCost === 30000 ? 30000 : selectedTreatmentCost * days;
        const finalCost = totalCost * (1 - discount / 100);
        const dueAmount = finalCost - advancePayment;
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
                treatmentDaysInput.value = e.target.value === 'package' ? 22 : '';
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
    if (discountInput) discountInput.addEventListener('input', updateCalculations);
    if (advancePaymentInput) advancePaymentInput.addEventListener('input', updateCalculations);

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

                const res = await fetch('add_patient.php', {
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

    /* ------------------------------
       End of module
    ------------------------------ */
});