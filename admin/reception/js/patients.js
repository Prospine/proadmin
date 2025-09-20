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
        <div class="info-item"><span class="label">Cost / Day</span><span class="value">₹${numberFormat(data.treatment_cost_per_day ?? 0)}</span></div>
        <div class="info-item"><span class="label">Package Cost</span><span class="value">₹${numberFormat(data.package_cost ?? 0)}</span></div>
        <div class="info-item"><span class="label">Total Amount</span><span class="value">₹${numberFormat(data.total_amount ?? 0)}</span></div>
        <div class="info-item"><span class="label">Advance Paid</span><span class="value">₹${numberFormat(data.advance_payment ?? 0)}</span></div>
        <div class="info-item"><span class="label">Discount</span><span class="value">${data.discount_percentage ?? 0}%</span></div>
        <div class="info-item"><span class="label">Due Amount</span><span class="value">₹${numberFormat(data.due_amount ?? 0)}</span></div>
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
                                })
                                .catch(() => {
                                    showToast("error", "Failed to update status");
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


});