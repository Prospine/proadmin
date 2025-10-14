document.addEventListener('DOMContentLoaded', () => {
    const refundModal = document.getElementById('refund-modal');
    if (!refundModal) return;

    const refundForm = document.getElementById('refundForm');
    const refundIdInput = document.getElementById('refund_id');
    const refundPaidAmountInput = document.getElementById('refund_paid_amount');
    const refundAmountInput = document.getElementById('refund_amount_input');
    const closeBtn = refundModal.querySelector('.close-modal-btn');

    // --- Open Modal ---
    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('refund-btn')) {
            const button = e.target;
            const id = button.dataset.id;
            const paidAmount = parseFloat(button.dataset.paid);

            refundIdInput.value = id;
            refundPaidAmountInput.value = `₹${paidAmount.toFixed(2)}`;
            refundAmountInput.value = paidAmount.toFixed(2);
            refundAmountInput.max = paidAmount;

            refundModal.classList.add('is-visible');
        }
    });

    // --- Close Modal ---
    const closeModal = () => {
        refundModal.classList.remove('is-visible');
        refundForm.reset();
    };

    closeBtn.addEventListener('click', closeModal);
    refundModal.addEventListener('click', (e) => {
        if (e.target === refundModal) closeModal();
    });

    // --- Refund Form Submission ---
    refundForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const submitBtn = refundForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

        const formData = new FormData(refundForm);
        const data = Object.fromEntries(formData.entries());

        fetch('../api/initiate_registration_refund.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToast(result.message || 'Refund initiated successfully!', 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                showToast(result.message || 'Failed to initiate refund.', 'error');
            }
        })
        .catch(error => showToast('An error occurred: ' + error.message, 'error'))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Initiate Refund';
        });
    });

    // --- Status Change Handler ---
    document.body.addEventListener('change', (e) => {
        if (e.target.classList.contains('status-select')) {
            const select = e.target;
            fetch('../api/update_registration_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id: select.dataset.id, type: 'registration', status: select.value })
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showToast('Status updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(result.message || 'Failed to update status.', 'error');
                    select.value = 'cancelled'; // Revert on failure
                }
            }).catch(() => showToast('An error occurred.', 'error'));
        }
    });

    // --- Status Change Handler ---
    document.body.addEventListener('change', (e) => {
        if (e.target.classList.contains('status-select')) {
            const select = e.target;
            fetch('../api/update_registration_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id: select.dataset.id, type: 'registration', status: select.value })
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    showToast('Status updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(result.message || 'Failed to update status.', 'error');
                    select.value = 'cancelled'; // Revert on failure
                }
            }).catch(() => showToast('An error occurred.', 'error'));
        }
    });

    // Toast function, as it's not globally available on this page
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 500); }, 5000);
    }
});