document.addEventListener('DOMContentLoaded', function () {
    const applyFilterBtn = document.getElementById('apply-filter-btn');
    const reportTbody = document.getElementById('report-tbody');
    const loader = document.getElementById('loader');
    const form = document.getElementById('filter-form');
    const filterStatusMessage = document.getElementById('filter-status-message');

    // Get the summary total elements
    const consultedTotalSpan = document.getElementById('consulted-total');
    const pendingTotalSpan = document.getElementById('pending-total');
    const closedTotalSpan = document.getElementById('closed-total');

    applyFilterBtn.addEventListener('click', function () {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        loader.style.display = 'block';
        reportTbody.style.display = 'none';
        filterStatusMessage.style.display = 'none';

        const url = `?fetch=true&${params.toString()}`;

        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject('Network response was not ok.'))
            .then(data => {
                reportTbody.innerHTML = '';

                // Update Summary Total
                if (data.totals) {
                    const formatCurrency = (value) => `₹${parseFloat(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    if (consultedTotalSpan) consultedTotalSpan.textContent = formatCurrency(data.totals.consulted_sum);
                    if (pendingTotalSpan) pendingTotalSpan.textContent = formatCurrency(data.totals.pending_sum);
                    if (closedTotalSpan) closedTotalSpan.textContent = formatCurrency(data.totals.closed_sum);
                }

                const resultCount = data.registrations ? data.registrations.length : 0;
                let statusText = '';

                if (resultCount > 1) {
                    statusText = `Filtering complete! Found <strong>${resultCount}</strong> records matching your criteria.`;
                } else if (resultCount === 1) {
                    statusText = `Filtering complete! Found <strong>1</strong> record matching your criteria.`;
                } else {
                    statusText = `Filtering complete. <strong>No records</strong> found for the selected criteria.`;
                }

                filterStatusMessage.innerHTML = `<i class="fa-solid fa-circle-info"></i> ${statusText}`;
                filterStatusMessage.style.display = 'flex';

                if (resultCount > 0) {
                    data.registrations.forEach(reg => {
                        const row = `
                            <tr>
                                <td data-label="Appt. Date">${escapeHTML(reg.appointment_date)}</td>
                                <td data-label="Patient Name">${escapeHTML(reg.patient_name)}</td>
                                <td data-label="Age">${escapeHTML(String(reg.age))}</td>
                                <td data-label="Condition">${ucfirst(escapeHTML(reg.chief_complain).replace(/_/g, ' '))}</td>
                                <td data-label="Source">${ucfirst(escapeHTML(reg.referralSource).replace(/_/g, ' '))}</td>
                                <td data-label="Referred By">${ucfirst(escapeHTML(reg.reffered_by).replace(/_/g, ' '))}</td>
                                <td data-label="Consultation">${ucfirst(escapeHTML(reg.consultation_type).replace(/-/g, ' '))}</td>
                                <td data-label="Amount" class="amount-total">₹${parseFloat(reg.consultation_amount).toFixed(2)}</td>
                                <td data-label="Pay Mode">${ucfirst(escapeHTML(reg.payment_method))}</td>
                                <td data-label="Status"><span class="status-pill status-${escapeHTML(reg.status).toLowerCase()}">${escapeHTML(reg.status)}</span></td>
                            </tr>
                        `;
                        reportTbody.innerHTML += row;
                    });
                } else {
                    reportTbody.innerHTML = '<tr><td colspan="10" style="text-align: center;">No records found.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                reportTbody.innerHTML = '<tr><td colspan="10" style="text-align: center;">Failed to load data. Please try again.</td></tr>';
                filterStatusMessage.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> An error occurred while fetching data.`;
                filterStatusMessage.style.display = 'flex';
                filterStatusMessage.style.color = '#842029';
                filterStatusMessage.style.backgroundColor = '#f8d7da';
                filterStatusMessage.style.borderColor = '#f5c2c7';
            })
            .finally(() => {
                loader.style.display = 'none';
                reportTbody.style.display = '';
            });
    });

    // Helper functions
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, match => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[match]));
    }
    function ucfirst(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }
});