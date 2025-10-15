document.addEventListener("DOMContentLoaded", () => {
    // ==========================================================
    // 1. Core Utilities: Toast Notifications & Modal Controls
    // ==========================================================
    const toastContainer = document.getElementById("toast-container");

    function showToast(message, type = 'success') {
        if (!toastContainer) {
            console.error("Toast container not found.");
            return;
        }
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
            if (show) {
                modal.classList.add('is-visible');
            } else {
                modal.classList.remove('is-visible');
            }
        }
    }

    // Generic close modal functionality
    document.querySelectorAll('.modal-overlay .close-modal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) {
                toggleModal(modal.id, false);
            }
        });
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                toggleModal(overlay.id, false);
            }
        });
    });

    // Password visibility toggle
    document.querySelectorAll('.toggle-password-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const passwordInput = btn.previousElementSibling;
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            btn.classList.toggle('fa-eye');
            btn.classList.toggle('fa-eye-slash');
        });
    });

    // ==========================================================
    // 2. "Create Employee" Functionality
    // ==========================================================
    const createEmployeeBtn = document.getElementById('create-employee-btn');
    const createEmployeeModal = document.getElementById('create-employee-modal');
    const createEmployeeForm = document.getElementById('createEmployeeForm');

    if (createEmployeeBtn && createEmployeeModal) {
        createEmployeeBtn.addEventListener('click', () => {
            createEmployeeForm.reset();
            toggleModal('create-employee-modal', true);
        });
    }

    if (createEmployeeForm) {
        createEmployeeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(createEmployeeForm);
            const data = Object.fromEntries(formData.entries());
            data.action = 'create_employee';

            try {
                const response = await fetch('../api/manage_user_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    toggleModal('create-employee-modal', false);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
                console.error('Create employee error:', error);
            }
        });
    }

    // ==========================================================
    // 3. "Create User" Functionality
    // ==========================================================
    const createUserBtn = document.getElementById('create-user-btn');
    const createUserModal = document.getElementById('create-user-modal');
    const createUserForm = document.getElementById('createUserForm');

    if (createUserBtn && createUserModal) {
        createUserBtn.addEventListener('click', () => {
            createUserForm.reset();
            toggleModal('create-user-modal', true);
        });
    }

    if (createUserForm) {
        createUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(createUserForm);
            const data = Object.fromEntries(formData.entries());
            data.action = 'create';

            try {
                const response = await fetch('../api/manage_user_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    toggleModal('create-user-modal', false);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
                console.error('Create user error:', error);
            }
        });
    }

    // ==========================================================
    // 4. "Edit User" Functionality
    // ==========================================================
    const editUserModal = document.getElementById('edit-user-modal');
    const editUserForm = document.getElementById('editUserForm');
    const employeeSelect = document.getElementById('employee_id');

    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const userData = JSON.parse(btn.dataset.user);
            
            // Populate form
            editUserForm.user_id.value = userData.id;
            editUserForm.username.value = userData.username;
            editUserForm.email.value = userData.email || '';
            editUserForm.role.value = userData.role;
            editUserForm.branch_id.value = userData.branch_id || '';
            editUserForm.is_active.value = userData.is_active;

            // Handle the employee link dropdown
            const currentEmployeeOpt = employeeSelect.querySelector('option.current-employee');
            if (currentEmployeeOpt) {
                currentEmployeeOpt.remove();
            }

            if (userData.employee_id) {
                const newOpt = document.createElement('option');
                newOpt.value = userData.employee_id;
                newOpt.textContent = `${userData.first_name} ${userData.last_name} (Currently Linked)`;
                newOpt.classList.add('current-employee');
                newOpt.selected = true;
                employeeSelect.prepend(newOpt);
            } else {
                employeeSelect.value = '';
            }

            toggleModal('edit-user-modal', true);
        });
    });

    if (editUserForm) {
        editUserForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editUserForm);
            const data = Object.fromEntries(formData.entries());
            data.action = 'update';

            try {
                const response = await fetch('../api/manage_user_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    toggleModal('edit-user-modal', false);
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
                console.error('Update user error:', error);
            }
        });
    }

    // ==========================================================
    // 5. "Change Password" Functionality
    // ==========================================================
    const changePasswordModal = document.getElementById('change-password-modal');
    const changePasswordForm = document.getElementById('changePasswordForm');

    document.querySelectorAll('.change-password-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            changePasswordForm.reset();
            changePasswordForm.user_id.value = btn.dataset.userid;
            toggleModal('change-password-modal', true);
        });
    });

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const newPassword = changePasswordForm.new_password.value;
            const confirmPassword = changePasswordForm.confirm_password.value;

            if (newPassword !== confirmPassword) {
                showToast('Passwords do not match.', 'error');
                return;
            }

            const formData = new FormData(changePasswordForm);
            const data = Object.fromEntries(formData.entries());
            data.action = 'change_password';

            try {
                const response = await fetch('../api/manage_user_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    toggleModal('change-password-modal', false);
                } else {
                    showToast(result.message, 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
                console.error('Change password error:', error);
            }
        });
    }
});