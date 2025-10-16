document.addEventListener('DOMContentLoaded', function () {
    const applyFilterBtn = document.getElementById('apply-filter-btn');
    const reportTbody = document.getElementById('report-tbody');
    const loader = document.getElementById('loader');
    const form = document.getElementById('filter-form');
    const filterStatusMessage = document.getElementById('filter-status-message');

    // Get the summary total elements
    const totalBilledSpan = document.getElementById('total-billed-sum');
    const totalPaidSpan = document.getElementById('total-paid-sum');
    const totalDueSpan = document.getElementById('total-due-sum');

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

                // Update Summary Totals
                if (data.totals) {
                    const formatCurrency = (value) => `₹${parseFloat(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    if (totalBilledSpan) totalBilledSpan.textContent = formatCurrency(data.totals.total_sum);
                    if (totalPaidSpan) totalPaidSpan.textContent = formatCurrency(data.totals.paid_sum);
                    if (totalDueSpan) totalDueSpan.textContent = formatCurrency(data.totals.due_sum);
                }

                const resultCount = data.patients ? data.patients.length : 0;
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
                    data.patients.forEach(patient => {
                        const row = `
                            <tr>
                                <td data-label="Patient ID">${escapeHTML(String(patient.patient_id))}</td>
                                <td data-label="Patient Name">${escapeHTML(patient.patient_name)}</td>
                                <td data-label="Assigned Doctor">${escapeHTML(patient.assigned_doctor)}</td>
                                <td data-label="Treatment">${ucfirst(escapeHTML(patient.treatment_type))}</td>
                                <td data-label="Total Amt" class="amount-total">₹${parseFloat(patient.total_amount).toFixed(2)}</td>
                                <td data-label="Paid" class="amount-paid">₹${parseFloat(patient.advance_payment).toFixed(2)}</td>
                                <td data-label="Due" class="amount-due">₹${parseFloat(patient.due_amount).toFixed(2)}</td>
                                <td data-label="Start Date">${escapeHTML(patient.start_date)}</td>
                                <td data-label="End Date">${escapeHTML(patient.end_date)}</td>
                                <td data-label="Status"><span class="status-pill status-${escapeHTML(patient.status).toLowerCase()}">${ucfirst(escapeHTML(patient.status))}</span></td>
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