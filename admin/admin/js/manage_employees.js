document.addEventListener("DOMContentLoaded", () => {
    // ==========================================================
    // 1. Core Utilities: Toast Notifications & Modal Controls
    // ==========================================================
    const toastContainer = document.getElementById("toast-container");

    function showToast(message, type = 'success') {
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toastContainer.removeChild(toast), 500);
        }, 5000);
    }

    function toggleModal(modalId, show) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = show ? 'flex' : 'none';
            modal.classList.toggle('is-visible', show);
        }
    }

    // Generic close modal functionality
    document.querySelectorAll('.modal-overlay .close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) toggleModal(modal.id, false);
        });
    });

    // ==========================================================
    // 2. "Edit Employee" Functionality
    // ==========================================================
    const editEmployeeModal = document.getElementById('edit-employee-modal');
    const editEmployeeForm = document.getElementById('editEmployeeForm');

    document.querySelectorAll('.edit-employee-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const employeeData = JSON.parse(btn.dataset.employee);

            // Populate form
            editEmployeeForm.employee_id.value = employeeData.employee_id;
            editEmployeeForm.first_name.value = employeeData.first_name || '';
            editEmployeeForm.last_name.value = employeeData.last_name || '';
            editEmployeeForm.job_title.value = employeeData.job_title || '';
            editEmployeeForm.phone_number.value = employeeData.phone_number || '';
            editEmployeeForm.address.value = employeeData.address || '';
            editEmployeeForm.date_of_birth.value = employeeData.date_of_birth || '';
            editEmployeeForm.date_of_joining.value = employeeData.date_of_joining || '';
            editEmployeeForm.is_active.value = employeeData.is_active;

            toggleModal('edit-employee-modal', true);
        });
    });

    if (editEmployeeForm) {
        editEmployeeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editEmployeeForm);
            const data = Object.fromEntries(formData.entries());
            data.action = 'update_employee';

            try {
                const response = await fetch('../api/manage_user_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    toggleModal('edit-employee-modal', false);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
                console.error('Update employee error:', error);
            }
        });
    }
});