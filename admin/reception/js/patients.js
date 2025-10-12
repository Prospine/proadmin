document.addEventListener('DOMContentLoaded', () => {
    // Helper Functions
    const ucFirst = str => str ? str.charAt(0).toUpperCase() + str.slice(1) : '-';
    const numberFormat = num => num ? Number(num).toLocaleString('en-IN', {
        minimumFractionDigits: 2
    }) : '-';
    const formatDate = date => date ? new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    }) : '-';
    const formatDateTime = date => date ? new Date(date).toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }) : '-';
    const showToast = (message, type = 'info') => {
        const toastContainer = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.classList.add('toast', `toast-${type}`);
        const iconClass = {
            success: 'fas fa-check-circle success',
            error: 'fas fa-times-circle error',
            info: 'fas fa-info-circle info'
        }[type] || 'fas fa-info-circle info';
        toast.innerHTML = `<i class="${iconClass} toast-icon"></i> ${message}`;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    };

    /* ------------------------------
       NEW: Table Search, Filter & Sort Logic
    ------------------------------ */
    const searchInput = document.getElementById('searchInput');
    const doctorFilter = document.getElementById('doctorFilter');
    const treatmentFilter = document.getElementById('treatmentFilter');
    const serviceTypeFilter = document.getElementById('serviceTypeFilter'); // NEW
    const statusFilter = document.getElementById('statusFilter');
    const sortDirectionBtn = document.getElementById('sortDirectionBtn');
    const tableBody = document.getElementById('patientsTableBody');
    const tableHeaders = document.querySelectorAll('#patientsTable th.sortable');

    let currentSort = {
        key: 'patient_id', // Default sort
        direction: 'desc' // Default direction
    };

    const processTable = () => {
        if (!tableBody) return;

        const searchTerm = searchInput.value.toLowerCase();
        const doctorValue = doctorFilter.value.toLowerCase();
        const treatmentValue = treatmentFilter.value.toLowerCase();
        const serviceValue = serviceTypeFilter.value.toLowerCase(); // NEW
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
            const doctorCell = row.querySelector('td:nth-child(5)'); // Corrected: Doctor is in the 5th column
            const serviceCell = row.querySelector('td:nth-child(7)'); // NEW: Service Type is in the 7th column
            const treatmentCell = row.querySelector('td:nth-child(8)'); // Corrected: Treatment Type is now in the 8th column
            const statusCell = row.querySelector('td:nth-child(12) .pill'); // Corrected: Status is now in the 12th column

            const rowDoctor = doctorCell ? doctorCell.textContent.trim().toLowerCase() : '';
            const rowService = serviceCell ? serviceCell.textContent.trim().toLowerCase() : ''; // NEW
            const rowTreatment = treatmentCell ? treatmentCell.textContent.trim().toLowerCase() : '';
            const rowStatus = statusCell ? statusCell.textContent.trim().toLowerCase() : '';

            const matchesSearch = rowText.includes(searchTerm);
            const matchesDoctor = doctorValue ? rowDoctor === doctorValue : true;
            const matchesTreatment = treatmentValue ? rowTreatment === treatmentValue : true;
            const matchesService = serviceValue ? rowService === serviceValue : true; // NEW
            const matchesStatus = statusValue ? rowStatus === statusValue : true;

            if (matchesSearch && matchesDoctor && matchesTreatment && matchesService && matchesStatus) {
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
            const isDate = key === 'start_date';

            if (isNumeric) {
                valA = parseFloat(valA.replace(/[^0-9.-]+/g, "")) || 0;
                valB = parseFloat(valB.replace(/[^0-9.-]+/g, "")) || 0;
            } else if (isDate) {
                valA = new Date(valA.replace('Start: ', '')).getTime() || 0; // Corrected: Simpler and correct date parsing
                valB = new Date(valB.replace('Start: ', '')).getTime() || 0; // Corrected: Simpler and correct date parsing
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
                cell.colSpan = tableHeaders.length + 3; // +3 for non-sortable action columns
                cell.textContent = 'No patients match your criteria.';
                cell.style.textAlign = 'center';
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    };

    // Attach event listeners
    [searchInput, doctorFilter, treatmentFilter, serviceTypeFilter, statusFilter].forEach(el => {
        if (el) el.addEventListener('input', processTable);
    });

    // Navigation
    const navBacksBtn = document.getElementById('navBacks');
    const navForwardsBtn = document.getElementById('navForwards');
    if (navBacksBtn) navBacksBtn.addEventListener('click', () => window.history.back());
    if (navForwardsBtn) navForwardsBtn.addEventListener('click', () => window.history.forward());
    document.body.classList.add('loaded');

    // Patient Details Drawer
    const patientDrawer = document.getElementById('drawer');
    const patientDrawerBody = document.getElementById('drawer-body');
    const patientCloseBtn = document.getElementById('closeDrawer');
    const closePatientDrawer = () => patientDrawer.classList.remove('open');
    if (patientCloseBtn) patientCloseBtn.addEventListener('click', closePatientDrawer);
    if (patientDrawer) patientDrawer.addEventListener('click', e => {
        if (e.target === patientDrawer) closePatientDrawer();
    });


    // Escape key to close drawer
    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape' && patientDrawer.classList.contains('open')) {
            closePatientDrawer();
        }
    });

    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            patientDrawerBody.innerHTML = `<p style="text-align: center; color: var(--text-muted);">Loading patient data...</p>`;
            patientDrawer.classList.add('open');

            fetch(`../api/fetch_patient.php?id=${id}`)
                .then(res => {
                    if (!res.ok) throw new Error(`HTTP error! Status: ${res.status}`);
                    return res.json();
                })
                .then(data => {
                    if (data.error && data.error !== '0' && data.error !== false) {
                        patientDrawerBody.innerHTML = `<p style="color: red; text-align: center;">${data.error}</p>`;
                    } else {
                        patientDrawerBody.innerHTML = `
                                   <div class="drawer-section" data-collapsible>
    <h3><i class="fas fa-user-circle"></i> Patient Info</h3>
    <div class="info-grid">
        <div class="info-item"><span class="label">ID</span><span class="value">${data.patient_id ?? '-'}</span></div>
        <div class="info-item"><span class="label">Name</span><span class="value">${data.patient_name ?? '-'}</span></div>
        <div class="info-item"><span class="label">Age</span><span class="value">${data.age ?? '-'}</span></div>
        <div class="info-item"><span class="label">Gender</span><span class="value">${data.gender ?? '-'}</span></div>
        <div class="info-item"><span class="label">Phone</span><span class="value">${data.phone_number ?? '-'}</span></div>
        <div class="info-item"><span class="label">Email</span><span class="value">${data.email ?? 'N/A'}</span></div>
        <div class="info-item"><span class="label">Assigned Doctor</span><span class="value">${data.assigned_doctor ?? '-'}</span></div>
        <div class="info-item"><span class="label">Service Type</span><span class="value">${ucFirst(data.service_type?.replace('_', ' ') ?? '-')}</span></div>
        <div class="info-item"><span class="label">Chief Complaint</span><span class="value">${ucFirst(data.chief_complain ?? '-')}</span></div>
         <div class="info-item">
    <span class="label">Update Status</span>
    <select id="statusSelect" class="status-select">
      <option value="active" ${data.patient_status?.toLowerCase() === 'active' ? 'selected' : ''}>Active</option>
      <option value="inactive" ${data.patient_status?.toLowerCase() === 'inactive' ? 'selected' : ''}>Inactive</option>
      <option value="completed" ${data.patient_status?.toLowerCase() === 'completed' ? 'selected' : ''}>Completed</option>
    </select>
  </div>
    </div>
</div>

<div class="drawer-section" data-collapsible>
    <h3><i class="fas fa-money-bill-wave"></i> Financial Details</h3>
    <div class="info-grid">
        <div class="info-item"><span class="label">Cost / Day</span><span class="value">₹${numberFormat(data.cost_per_day)}</span></div>
        <div class="info-item"><span class="label">Consumed Amount</span><span class="value">₹${numberFormat(data.consumed_amount)}</span></div>
        <div class="info-item"><span class="label">Total Paid</span><span class="value">₹${numberFormat(data.total_paid)}</span></div>
        <div class="info-item"><span class="label">Today Paid</span><span class="value">₹${numberFormat(data.today_paid)}</span></div>
        <div class="info-item"><span class="label">Attendance</span><span class="value">${data.attendance_completed} days</span></div>
        
        <div class="info-item"><span class="label">Package Cost</span><span class="value">₹${numberFormat(data.package_cost ?? 0)}</span></div>
        <div class="info-item"><span class="label">Total Amount</span><span class="value">₹${numberFormat(data.total_amount ?? 0)}</span></div>
        <div class="info-item"><span class="label">Discount</span><span class="value">${data.discount_percentage ?? 0}%</span></div>
        <div class="info-item"><span class="label">Discount Approved By</span><span class="value">${data.discount_approver_name ?? 'None'}</span></div>
        <div class="info-item"><span class="label">Expected Due Amount</span><span class="value">₹${numberFormat(data.due_amount ?? 0)}</span></div>
        <div class="info-item"><span class="label">Treatment Payment Method</span><span class="value">${ucFirst(data.treatment_payment_method ?? 'N/A')}</span></div>
    </div>
</div>

<div class="drawer-section" data-collapsible>
    <h3><i class="fas fa-calendar-alt"></i> Treatment & Appointment</h3>
    <div class="info-grid">
        <div class="info-item"><span class="label">Treatment Type</span><span class="value">${ucFirst(data.treatment_type ?? '-')}</span></div>
        <div class="info-item"><span class="label">Treatment Days</span><span class="value">${data.treatment_days ?? '-'}</span></div>
        <div class="info-item"><span class="label">Start Date</span><span class="value">${formatDate(data.start_date)}</span></div>
        <div class="info-item"><span class="label">End Date</span><span class="value">${formatDate(data.end_date)}</span></div>
        <div class="info-item"><span class="label">Consultation Type</span><span class="value">${ucFirst(data.consultation_type ?? 'N/A')}</span></div>
        <div class="info-item"><span class="label">Appointment Date</span><span class="value">${formatDate(data.appointment_date)}</span></div>
        <div class="info-item"><span class="label">Appointment Time</span><span class="value">${data.appointment_time ?? '-'}</span></div>
        <div class="info-item"><span class="label">Follow-up Date</span><span class="value">${formatDate(data.follow_up_date)}</span></div>
    </div>

    <!-- Attendance & Payments summary -->
    <h4 style="margin-top: 2rem;"><i class="fas fa-clipboard-check"></i> Attendance & Payments</h4>
    <div class="info-grid">
        <div class="info-item"><span class="label">Attendance Progress</span><span class="value">${data.attendance_completed ?? 0}/${data.treatment_days ?? '-'}</span></div>
        <div class="info-item"><span class="label">Last Visit</span><span class="value">${formatDate(data.last_attendance_date)}</span></div>
        <div class="info-item"><span class="label">Total Paid</span><span class="value">₹${numberFormat(data.total_paid ?? 0)}</span></div>
        <div class="info-item"><span class="label">Last Payment</span><span class="value">${formatDate(data.last_payment_date)}</span></div>
    </div>
</div>


<div class="drawer-section" data-collapsible>
    <h3><i class="fas fa-info-circle"></i> Registration Details</h3>
    <div class="info-grid">
        <div class="info-item"><span class="label">Registration ID</span><span class="value">${data.registration_id ?? '-'}</span></div>
        <div class="info-item"><span class="label">Referral Source</span><span class="value">${ucFirst(data.referralSource ?? 'N/A')}</span></div>
        <div class="info-item"><span class="label">Referred By</span><span class="value">${data.reffered_by ?? 'N/A'}</span></div>
        <div class="info-item"><span class="label">Occupation</span><span class="value">${data.occupation ?? 'N/A'}</span></div>
        <div class="info-item"><span class="label">Address</span><span class="value">${data.address ?? 'N/A'}</span></div>
        <div class="info-item"><span class="label">Created At</span><span class="value">${formatDateTime(data.created_at)}</span></div>
        <div class="info-item"><span class="label">Updated At</span><span class="value">${formatDateTime(data.updated_at)}</span></div>
        <div class="info-item"><span class="label">Registration Status</span><span class="value status-badge ${data.registration_status?.toLowerCase() ?? 'inactive'}">${ucFirst(data.registration_status ?? 'Inactive')}</span></div>
        <div class="info-item"><span class="label">Consultation Fee</span><span class="value">₹${numberFormat(data.consultation_amount ?? 0)}</span></div>
        <div class="info-item"><span class="label">Payment Method</span><span class="value">${ucFirst(data.payment_method ?? 'N/A')}</span></div>
        <div class="info-item"><span class="label">Doctor Notes</span><span class="value">${data.doctor_notes ?? '-'}</span></div>
        <div class="info-item"><span class="label">Prescription</span><span class="value">${data.prescription ?? '-'}</span></div>
        <div class="info-item"><span class="label">Remarks</span><span class="value">${data.remarks ?? 'N/A'}</span></div>
    </div>
</div>

                                `;

                        document.getElementById("statusSelect").addEventListener("change", function () {
                            const newStatus = this.value;

                            fetch("../api/update_patient_status.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded"
                                },
                                body: `id=${data.patient_id}&status=${newStatus}`,
                            })
                                .then((res) => res.json())
                                .then((result) => {
                                    showToast("success", "Status updated to " + newStatus);
                                    // This line was a duplicate and had arguments in the wrong order. Removed.
                                })
                                .catch(() => {
                                    showToast("Failed to update status", "error");
                                });
                        });
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    patientDrawerBody.innerHTML = `<p style="color: red; text-align: center;">Failed to load patient data: ${error.message}. Please try again.</p>`;
                });
        });
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

    if (sortDirectionBtn) {
        sortDirectionBtn.addEventListener('click', () => {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            processTable();
        });
    }

    // --- NEW LOGIC: Step 1 - Open token modal with client-side data ---
    document.querySelectorAll(".print-token-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const modal = document.getElementById("token-modal");
            const printBtn = document.getElementById("popup-print-btn");

            // Get data from the button's attributes
            const patientId = this.dataset.patientId;
            const patientName = this.dataset.patientName;
            const assignedDoctor = this.dataset.assignedDoctor;
            const attendanceProgress = this.dataset.attendanceProgress;

            // Populate the modal
            document.getElementById("popup-token-uid").textContent = "Pending...";
            document.getElementById("popup-name").textContent = patientName;
            document.getElementById("popup-doctor").textContent = assignedDoctor;
            document.getElementById("popup-attendance").textContent = attendanceProgress;
            document.getElementById("popup-date").textContent = new Date().toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            
            // Store patient ID on the modal's print button for the next step
            printBtn.dataset.patientId = patientId;

            // Show modal
            modal.style.display = "block";
        });
    });

    // Close modal when clicking close button
    document.querySelectorAll(".close-token").forEach(closeBtn => {
        closeBtn.addEventListener("click", function () {
            document.getElementById("token-modal").style.display = "none";
        });
    });

    // --- NEW LOGIC: Step 2 - Generate token and print from modal ---
    document.getElementById("popup-print-btn").addEventListener("click", function () {
        const patientId = this.dataset.patientId;
        if (!patientId) return;

        const printBtn = this;
        printBtn.disabled = true;
        printBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';

        fetch(`../api/generate_token.php?patient_id=${patientId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("popup-token-uid").textContent = data.token_uid;
                    document.getElementById("popup-total-paid").textContent = data.total_paid; // This still comes from API
                    window.print(); // Trigger print dialog
                    // Reload to update the table button state
                    setTimeout(() => window.location.reload(), 1500); // Increased timeout slightly
                } else {
                    alert(data.message || "Failed to generate token.");
                    printBtn.disabled = false;
                    printBtn.innerHTML = '🖨 Print';
                }
            })
            .catch(err => {
                console.error("Error generating token:", err);
                alert("An error occurred. Please check the console.");
                printBtn.disabled = false;
                printBtn.innerHTML = '🖨 Print';
            });
    });
});