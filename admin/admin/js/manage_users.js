document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('edit-user-modal');
    const passwordModal = document.getElementById('change-password-modal');
    const createModal = document.getElementById('create-user-modal');
    const editForm = document.getElementById('editUserForm');
    const passwordForm = document.getElementById('changePasswordForm');
    const createForm = document.getElementById('createUserForm');

    // --- Modal Opening/Closing ---
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', () => {
            const userData = JSON.parse(button.dataset.user);
            // Reset form to clear previous data
            editForm.reset();
            populateEditForm(userData);
            editModal.classList.add('is-visible');
        });
    });

    editModal.querySelectorAll('.close-modal-btn').forEach(button => {
        button.addEventListener('click', () => {
            editModal.classList.remove('is-visible');
        });
    });

    passwordModal.querySelectorAll('.close-modal-btn').forEach(button => {
        button.addEventListener('click', () => {
            passwordModal.classList.remove('is-visible');
        });
    });

    document.querySelectorAll('.change-password-btn').forEach(button => {
        button.addEventListener('click', () => {
            const userId = button.dataset.userid;
            // Reset form and populate user ID
            passwordForm.reset();
            document.getElementById('password_user_id').value = userId;
            passwordModal.classList.add('is-visible');
        });
    });

    document.getElementById('create-user-btn').addEventListener('click', () => {
        createForm.reset();
        createModal.classList.add('is-visible');
    });

    // Close modal when clicking the overlay
    editModal.addEventListener('click', (e) => {
        if (e.target === editModal) {
            editModal.classList.remove('is-visible');
        }
    });
    passwordModal.addEventListener('click', (e) => {
        if (e.target === passwordModal) {
            passwordModal.classList.remove('is-visible');
        }
    });

    createModal.querySelectorAll('.close-modal-btn').forEach(button => {
        button.addEventListener('click', () => {
            createModal.classList.remove('is-visible');
        });
    });

    createModal.addEventListener('click', (e) => {
        if (e.target === createModal) {
            createModal.classList.remove('is-visible');
        }
    });

    // --- Password Visibility Toggle ---
    document.querySelectorAll('.toggle-password-btn').forEach(button => {
        button.addEventListener('click', () => {
            const passwordInput = button.previousElementSibling;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                button.classList.remove('fa-eye');
                button.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                button.classList.remove('fa-eye-slash');
                button.classList.add('fa-eye');
            }
        });
    });


    // --- Form Population ---
    function populateEditForm(user) {
        document.getElementById('user_id').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email || '';
        document.getElementById('role').value = user.role;
        document.getElementById('branch_id').value = user.branch_id || '';
        document.getElementById('is_active').value = user.is_active;
    }

    // --- Edit Form Submission ---
    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = Object.fromEntries(new FormData(editForm));

        try {
            const response = await fetch('../api/update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                editModal.classList.remove('is-visible');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(result.message || 'An error occurred.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('A network error occurred. Please try again.', 'error');
        }
    });

    // --- Password Form Submission ---
    passwordForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword.length < 8) {
            showToast('Password must be at least 8 characters long.', 'error');
            return;
        }

        if (newPassword !== confirmPassword) {
            showToast('Passwords do not match.', 'error');
            return;
        }

        const formData = Object.fromEntries(new FormData(passwordForm));

        try {
            const response = await fetch('../api/change_user_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                passwordModal.classList.remove('is-visible');
            } else {
                showToast(result.message || 'An error occurred.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('A network error occurred. Please try again.', 'error');
        }
    });

    // --- Create User Form Submission ---
    createForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const password = document.getElementById('create_password').value;
        if (password.length < 8) {
            showToast('Password must be at least 8 characters long.', 'error');
            return;
        }

        const formData = Object.fromEntries(new FormData(createForm));

        try {
            const response = await fetch('../api/create_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                createModal.classList.remove('is-visible');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(result.message || 'An error occurred.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('A network error occurred. Please try again.', 'error');
        }
    });


    // --- Toast Notifications ---
    const toastContainer = document.getElementById('toast-container');

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }
});