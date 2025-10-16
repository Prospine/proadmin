document.addEventListener('DOMContentLoaded', function () {
    const applyFilterBtn = document.getElementById('apply-filter-btn');
    const reportTbody = document.getElementById('report-tbody');
    const loader = document.getElementById('loader');
    const form = document.getElementById('filter-form');
    const filterStatusMessage = document.getElementById('filter-status-message');

    // NEW: Get the summary total elements
    const totalAmountSpan = document.getElementById('total-amount-sum');
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
                // Clear previous results
                reportTbody.innerHTML = '';

                // --- NEW: Update Summary Totals ---
                if (data.totals) {
                    const formatCurrency = (value) => `₹${parseFloat(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                    totalAmountSpan.textContent = formatCurrency(data.totals.total_sum);
                    totalPaidSpan.textContent = formatCurrency(data.totals.paid_sum);
                    totalDueSpan.textContent = formatCurrency(data.totals.due_sum);
                }
                // --- END NEW ---

                const resultCount = data.tests ? data.tests.length : 0;
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
                    data.tests.forEach(test => {
                        // Use template literals for cleaner HTML string construction
                        const row = `
                            <tr>
                                <td>${escapeHTML(test.assigned_test_date)}</td>
                                <td>${escapeHTML(test.patient_name)}</td>
                                <td>${escapeHTML(test.test_name).toUpperCase()}</td>
                                <td>${escapeHTML(test.referred_by)}</td>
                                <td>${ucfirst(escapeHTML(test.test_done_by))}</td>
                                <td>${parseFloat(test.total_amount).toFixed(2)}</td>
                                <td>${parseFloat(test.advance_amount).toFixed(2)}</td>
                                <td>${parseFloat(test.due_amount).toFixed(2)}</td>
                                <td>
                                    <span class="status-pill status-${escapeHTML(test.payment_status).toLowerCase()}">
                                        ${ucfirst(escapeHTML(test.payment_status) || 'N/A')}
                                    </span>
                                </td>
                            </tr>
                        `;
                        reportTbody.innerHTML += row;
                    });
                } else {
                    reportTbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">No records found.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                reportTbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">Failed to load data. Please try again.</td></tr>';

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