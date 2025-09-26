const drawer = document.getElementById('test-drawer');
const drawerContent = drawer.querySelector('.drawer-content');

// Toast function
function showToast(message, type = "success") {
    const toast = document.createElement("div");
    toast.textContent = message;
    toast.style.padding = "12px 20px";
    toast.style.borderRadius = "6px";
    toast.style.color = "#fff";
    toast.style.fontSize = "14px";
    toast.style.boxShadow = "0 2px 6px rgba(0,0,0,0.2)";
    toast.style.opacity = "0";
    toast.style.transition = "all 0.4s ease";
    toast.style.transform = "translateX(100%)";
    toast.style.backgroundColor = type === "success" ? "#28a745" : "#dc3545";

    document.getElementById("toast-container").appendChild(toast);

    // Slide in
    setTimeout(() => {
        toast.style.opacity = "1";
        toast.style.transform = "translateX(0)";
    }, 50);

    // Auto remove after 3s
    setTimeout(() => {
        toast.style.opacity = "0";
        toast.style.transform = "translateX(100%)";
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

function createInfoItem(label, value) {
    const div = document.createElement('div');
    div.className = 'info-item';
    div.innerHTML = `<span class="label">${label}</span><span class="value">${value || ''}</span>`;
    return div;
}

document.querySelectorAll('.open-drawer').forEach(btn => {
    btn.addEventListener('click', () => {
        const testId = btn.dataset.id;
        fetch(`../api/fetch_test.php?id=${testId}`)
            .then(res => res.json())
            .then(data => {
                drawerContent.innerHTML = '';

                // --- Patient Info Card ---
                const patientCard = document.createElement('div');
                patientCard.className = 'drawer-card';
                patientCard.innerHTML = `<h4>Patient Info</h4>`;
                const patientGrid = document.createElement('div');
                patientGrid.className = 'info-grid';
                ['patient_name', 'phone_number', 'alternate_phone_no', 'gender', 'age', 'dob', 'parents', 'relation'].forEach(key => {
                    patientGrid.appendChild(createInfoItem(key.replace(/_/g, ' '), data[key]));
                });
                patientCard.appendChild(patientGrid);
                drawerContent.appendChild(patientCard);

                // --- Test Info Card ---
                const testCard = document.createElement('div');
                testCard.className = 'drawer-card';
                testCard.innerHTML = `<h4>Test Info</h4>`;
                const testGrid = document.createElement('div');
                testGrid.className = 'info-grid';
                ['test_name', 'referred_by', 'test_done_by', 'limb', 'visit_date', 'assigned_test_date'].forEach(key => {
                    testGrid.appendChild(createInfoItem(key.replace(/_/g, ' '), data[key]));
                });
                testCard.appendChild(testGrid);
                drawerContent.appendChild(testCard);

                // --- Payment Info Card ---
                const paymentCard = document.createElement('div');
                paymentCard.className = 'drawer-card';
                paymentCard.innerHTML = `<h4>Payment Info</h4>`;
                const paymentGrid = document.createElement('div');
                paymentGrid.className = 'info-grid';
                ['total_amount', 'advance_amount', 'due_amount', 'discount', 'payment_method'].forEach(key => {
                    let value = data[key];
                    if (key.includes('amount')) value = `₹${parseFloat(value || 0).toFixed(2)}`;
                    paymentGrid.appendChild(createInfoItem(key.replace(/_/g, ' '), value));
                });

                // Test Status Select
                const testStatusDiv = document.createElement('div');
                testStatusDiv.className = 'info-item';
                testStatusDiv.innerHTML = `<span class="label">Test Status</span>
            <select class="status-select" data-id="${data.test_id}">
                <option value="pending" ${data.test_status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="completed" ${data.test_status === 'completed' ? 'selected' : ''}>Completed</option>
                <option value="cancelled" ${data.test_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
            </select>`;
                paymentGrid.appendChild(testStatusDiv);

                // Payment Status Select
                const paymentStatusDiv = document.createElement('div');
                paymentStatusDiv.className = 'info-item';
                paymentStatusDiv.innerHTML = `<span class="label">Payment Status</span>
            <select class="payment-select" data-id="${data.test_id}">
                <option value="pending" ${data.payment_status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="partial" ${data.payment_status === 'partial' ? 'selected' : ''}>Partial</option>
                <option value="paid" ${data.payment_status === 'paid' ? 'selected' : ''}>Paid</option>
            </select>`;
                paymentGrid.appendChild(paymentStatusDiv);

                // --- Update Payment Section ---
                const updateDiv = document.createElement('div');
                updateDiv.className = 'info-item';
                updateDiv.innerHTML = `
                    <span class="label">Add Payment</span>
                    <input type="number" min="1" class="payment-input" placeholder="Enter amount" style="margin-left:10px; padding:10px 8px; width:160px; border-radius:5px; border:1px solid #ccc;">
                    <button class="save-payment-btn" style="margin-left:8px; padding:5px 10px;">Save</button>
                `;

                // Handle Save button click
                updateDiv.querySelector('.save-payment-btn').addEventListener('click', () => {
                    const amount = updateDiv.querySelector('.payment-input').value;
                    if (amount && !isNaN(amount)) {
                        fetch("../api/update_payment.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify({
                                test_id: data.test_id,
                                amount: parseFloat(amount)
                            })
                        })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message || "Payment updated!", "success");
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 3000);
                                } else {
                                    showToast(resp.message || "Error updating payment", "error");
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                showToast("Something went wrong!", "error");
                            });
                    } else {
                        showToast("Please enter a valid amount", "error");
                    }
                });

                drawer.addEventListener('change', e => {
                    if (e.target.classList.contains('status-select')) {
                        fetch("../api/update_test_status.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                test_id: e.target.dataset.id,
                                test_status: e.target.value
                            })
                        })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message, "success");
                                    setTimeout(() => window.location.reload(), 3000);
                                } else {
                                    showToast(resp.message || "Failed to update test status", "error");
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                showToast("Something went wrong!", "error");
                            });
                    }

                    if (e.target.classList.contains('payment-select')) {
                        fetch("../api/update_payment_status.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({
                                test_id: e.target.dataset.id,
                                payment_status: e.target.value
                            })
                        })
                            .then(res => res.json())
                            .then(resp => {
                                if (resp.status === "success") {
                                    showToast(resp.message, "success");
                                    setTimeout(() => window.location.reload(), 3000);
                                } else {
                                    showToast(resp.message || "Failed to update payment status", "error");
                                }
                            })
                            .catch(err => {
                                console.error(err);
                                showToast("Something went wrong!", "error");
                            });
                    }
                });

                paymentGrid.appendChild(updateDiv);

                paymentCard.appendChild(paymentGrid);
                drawerContent.appendChild(paymentCard);

                drawer.classList.add('open');
            })
            .catch(err => console.error(err));
    });
});

// Close drawer
drawer.querySelector('.close-drawer').addEventListener('click', () => {
    drawer.classList.remove('open');
});

// Handle status/payment changes
drawer.addEventListener('change', e => {
    if (e.target.classList.contains('status-select')) {
        console.log(`Test ${e.target.dataset.id} status changed to ${e.target.value}`);
    }
    if (e.target.classList.contains('payment-select')) {
        console.log(`Test ${e.target.dataset.id} payment changed to ${e.target.value}`);
    }
});