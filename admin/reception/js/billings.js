document.addEventListener("DOMContentLoaded", () => {
    const drawerOverlay = document.getElementById("drawer-overlay");
    const drawerPanel = document.getElementById("drawer-panel");
    const drawerHeader = document.getElementById("drawer-patient-name");
    const drawerBody = document.getElementById("drawer-body");
    const closeDrawerButton = document.getElementById("closeDrawer");
    const viewButtons = document.querySelectorAll(".open-drawer"); // CHANGED: More specific selector

    const closeDrawer = () => {
        if (drawerPanel) drawerPanel.classList.remove('is-open');
        if (drawerOverlay) setTimeout(() => drawerOverlay.style.display = 'none', 300);
    };

    const openDrawerWithDetails = async (patientId) => {
        if (!patientId || !drawerOverlay) return;

        try {
            drawerBody.innerHTML = '<p>Loading details...</p>';
            drawerOverlay.style.display = 'block';
            setTimeout(() => drawerPanel.classList.add('is-open'), 10);

            // CHANGED: Using a root-relative path for reliability
            const response = await fetch(`../api/get_billing_details.php?id=${patientId}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                drawerHeader.textContent = data.patient_name || 'Billing Details';

                let html = '<h4>Transaction History</h4><div class="payment-list">';

                // Consultation always at top
                html += `
        <div class="payment-item">
            <p><strong>Consultation Fee Paid</strong></p>
            <p>Amount: ₹${parseFloat(data.consultation_amount).toFixed(2)}</p>
        </div>
    `;

                if (data.payments.length > 0) {
                    // 🔹 Group payments by type (p.remarks or status)
                    const grouped = {};
                    data.payments.forEach(p => {
                        const key = p.status || p.remarks || "Other";
                        if (!grouped[key]) grouped[key] = [];
                        grouped[key].push(p);
                    });

                    // 🔹 Render grouped payments
                    Object.keys(grouped).forEach(type => {
                        html += `
                <div class="payment-item">
                    <p><strong>${type}</strong></p>
                    <ul style="margin:0; padding-left:1.2rem; color:#555; font-size:0.9rem;">
                        ${grouped[type].map(p => `
                            <li>
                                Date: ${p.payment_date} | 
                                Amount: ₹${parseFloat(p.amount).toFixed(2)} | 
                                Mode: ${p.mode}
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
                    });
                } else {
                    html += '<p>No other payments have been recorded.</p>';
                }

                html += '</div>';
                drawerBody.innerHTML = html;

            } else {
                drawerBody.innerHTML = `<p>Error: ${data.message}</p>`;
            }

        } catch (error) {
            console.error("Fetch error:", error);
            drawerBody.innerHTML = '<p>Could not fetch patient details. Please try again.</p>';
        }
    };

    viewButtons.forEach(button => {
        button.addEventListener('click', function () {
            const patientId = this.dataset.id;
            openDrawerWithDetails(patientId);
        });
    });

    if (closeDrawerButton) closeDrawerButton.addEventListener('click', closeDrawer);
    if (drawerOverlay) drawerOverlay.addEventListener('click', (e) => {
        if (e.target === drawerOverlay) {
            closeDrawer();
        }
    });
});