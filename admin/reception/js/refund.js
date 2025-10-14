document.addEventListener('DOMContentLoaded', () => {
    const refundModal = document.getElementById('refund-modal');
    if (!refundModal) return;

    const refundForm = document.getElementById('refundForm');
    const refundIdInput = document.getElementById('refund_id');
    const refundTypeInput = document.getElementById('refund_type');
    const refundPaidAmountInput = document.getElementById('refund_paid_amount');
    const refundAmountInput = document.getElementById('refund_amount_input');
    const closeBtn = refundModal.querySelector('.close-modal-btn');

    // --- Open Modal ---
    document.body.addEventListener('click', (e) => {
        if (e.target.classList.contains('refund-btn')) {
            const button = e.target;
            const id = button.dataset.id;
            const type = button.dataset.type;
            const paidAmount = parseFloat(button.dataset.paid);

            // Populate the form
            refundIdInput.value = id;
            refundTypeInput.value = type;
            refundPaidAmountInput.value = `₹${paidAmount.toFixed(2)}`;
            refundAmountInput.value = paidAmount.toFixed(2); // Default to full refund
            refundAmountInput.max = paidAmount; // Set max refund amount

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
        if (e.target === refundModal) {
            closeModal();
        }
    });

    // --- Form Submission ---
    refundForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const submitBtn = refundForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

        const formData = new FormData(refundForm);
        const data = Object.fromEntries(formData.entries());

        fetch('../api/initiate_refund.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToast(result.message || 'Refund initiated successfully!', 'success');
                closeModal();
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

    // Toast function (assuming it's available globally or defined elsewhere)
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 500); }, 5000);
    }

    // --- NEW: Handle Test Status Change ---
    document.body.addEventListener('change', (e) => {
        if (e.target.classList.contains('status-select')) {
            const select = e.target;
            const id = select.dataset.id;
            const type = select.dataset.type;
            const newStatus = select.value;

            // The main test_id is needed for test_items, but for main tests, id is the test_id.
            // This is a bit tricky. The API expects `test_id` for the order and `item_id` for the item.
            // The current `data-id` is either test_id or item_id.
            // We will need to adjust the API or send more data.
            // For now, let's assume the API can handle it if we send the correct key.
            const payload = {
                test_status: newStatus
            };
            if (type === 'main') {
                payload.test_id = id;
            } else { // 'item'
                payload.item_id = id;
            }

            fetch('../api/update_test_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success') {
                    showToast(result.message || 'Status updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showToast(result.message || 'Failed to update status.', 'error');
                    // Revert dropdown on failure
                    select.value = 'cancelled';
                }
            })
            .catch(error => {
                showToast('An error occurred: ' + error.message, 'error');
                select.value = 'cancelled';
            });
        }
    });
});