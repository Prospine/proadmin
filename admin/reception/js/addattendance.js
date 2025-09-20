/************************************************************************
     * Smart, unified attendance JS (with min payment check)
     ************************************************************************/

// helper: find closest TR for a button
function closestRow(el) {
    return el.closest('tr');
}

// basic toast
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    let icon = '';
    if (type === 'success') icon = '<i class="fa-solid fa-circle-check toast-icon"></i>';
    else if (type === 'error') icon = '<i class="fa-solid fa-circle-xmark toast-icon"></i>';

    toast.innerHTML = `${icon}<span class="toast-message">${message}</span>`;

    toastContainer.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 50);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

// Modal controls
const attendanceModal = document.getElementById('attendanceModal');
const closeModalBtn = attendanceModal.querySelector('.close-modal');
const attendanceCancel = document.getElementById('attendanceCancel');
const attendanceForm = document.getElementById('attendanceForm');

// Modal fields
const inputPatientId = document.getElementById('patient_id');
const inputTreatmentType = document.getElementById('treatment_type');
const paymentSection = document.getElementById('payment_section');
const paymentLabel = document.getElementById('payment_label');
const paymentToday = document.getElementById('payment_today');
const paymentMode = document.getElementById('payment_mode');
const remarksField = document.getElementById('remarks');

// Dynamic message container for minimum payment requirement
let minPaymentMsg = document.createElement('div');
minPaymentMsg.style.color = 'red';
minPaymentMsg.style.fontSize = '13px';
minPaymentMsg.style.marginTop = '5px';
paymentToday.insertAdjacentElement('afterend', minPaymentMsg);

let currentCostPerDay = 0;
let currentEffectiveBalance = 0;

function openModalForRow(row) {
    const pid = row.dataset.patientId || row.getAttribute('data-id');
    const pname = row.querySelector('td:nth-child(2)') ? row.querySelector('td:nth-child(2)').innerText.trim() : '';
    const treatmentType = row.dataset.treatmentType || '';
    const costPerDay = parseFloat(row.dataset.costPerDay || 0);
    const effectiveBalance = parseFloat(row.dataset.effectiveBalance || 0);

    currentCostPerDay = costPerDay;
    currentEffectiveBalance = effectiveBalance;

    const minRequired = Math.max(0, costPerDay - effectiveBalance);

    // set hidden fields
    inputPatientId.value = pid;
    inputTreatmentType.value = treatmentType;

    // title
    document.getElementById('modalTitle').innerText = `Mark Attendance for ${pname}`;

    // show payment section and pre-fill amount
    paymentSection.style.display = 'block';
    paymentLabel.textContent = 'Payment Today';
    paymentToday.value = minRequired > 0 ? minRequired : costPerDay;
    paymentMode.value = '';
    remarksField.value = '';

    // show min payment message
    if (minRequired > 0) {
        minPaymentMsg.textContent = `Minimum amount required for today's session is ₹${minRequired}`;
    } else {
        minPaymentMsg.textContent = '';
    }

    showModal();
}

function showModal() {
    attendanceModal.style.display = 'flex';
    attendanceModal.setAttribute('aria-hidden', 'false');
}

function hideModal() {
    attendanceModal.style.display = 'none';
    attendanceModal.setAttribute('aria-hidden', 'true');
}

closeModalBtn.addEventListener('click', hideModal);
attendanceCancel.addEventListener('click', hideModal);
attendanceModal.addEventListener('click', function (e) {
    if (e.target === attendanceModal) hideModal();
});

// Auto mark function (if enough balance)
async function autoMarkAttendance(patientId, rowElement) {
    const btn = rowElement.querySelector('.mark-attendance-btn');
    if (btn) btn.disabled = true;

    const fd = new FormData();
    fd.append('patient_id', patientId);
    fd.append('payment_amount', '0');
    fd.append('mode', '');
    fd.append('remarks', 'Auto: Used advance payment');

    try {
        const res = await fetch('../api/add_attendance.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast('Attendance saved successfully!');
            setTimeout(() => location.reload(), 3000);
        } else {
            showToast('Error: ' + (data.message || 'Unable to mark attendance'), 'error');
            if (btn) btn.disabled = false;
        }
    } catch (err) {
        console.error('Auto mark error', err);
        showToast('Network error. Try again.', 'error');
        if (btn) btn.disabled = false;
    }
}

// Handle form submit
attendanceForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    const fd = new FormData(attendanceForm);
    const amt = parseFloat(fd.get('payment_amount') || 0);
    const minRequired = Math.max(0, currentCostPerDay - currentEffectiveBalance);

    // Ensure remarks
    if (!fd.get('remarks') || fd.get('remarks').trim() === '') {
        const tt = fd.get('treatment_type') || 'daily';
        fd.set('remarks', (tt.charAt(0).toUpperCase() + tt.slice(1)) + ' attendance marked');
    }

    // Validation: require payment mode if > 0
    const paymentVisible = paymentSection.style.display !== 'none';
    if (paymentVisible && amt > 0 && (!fd.get('mode') || fd.get('mode') === '')) {
        showToast('Please select a payment mode for the amount entered.', 'error');
        return;
    }

    // Validation: amount must cover at least minRequired
    if (amt < minRequired) {
        showToast(`Minimum payment required is ₹${minRequired}`, 'error');
        return;
    }

    // Disable submit
    const submitBtn = attendanceForm.querySelector('.btn-save');
    submitBtn.disabled = true;

    try {
        const res = await fetch('../api/add_attendance.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: fd
        });
        const data = await res.json();

        if (data.status === 'success') {
            showToast('Attendance saved successfully!');
            hideModal();
            setTimeout(() => location.reload(), 3000);
        } else {
            showToast('Error: ' + (data.message || 'Unable to save attendance'), 'error');
            submitBtn.disabled = false;
        }
    } catch (err) {
        console.error('Submit attendance error', err);
        showToast('Network error. Try again.', 'error');
        submitBtn.disabled = false;
    }
});

// Attach mark buttons
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.mark-attendance-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = closestRow(btn);
            if (!row) return;

            const patientId = row.dataset.patientId;
            const effectiveBalance = parseFloat(row.dataset.effectiveBalance);
            const costPerDay = parseFloat(row.dataset.costPerDay);

            if (effectiveBalance >= costPerDay) {
                autoMarkAttendance(patientId, row);
            } else {
                openModalForRow(row);
            }
        });
    });
});