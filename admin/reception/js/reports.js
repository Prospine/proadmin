document.addEventListener('DOMContentLoaded', function () {
    const applyFilterBtn = document.getElementById('apply-filter-btn');
    const reportTbody = document.getElementById('report-tbody');
    const loader = document.getElementById('loader');
    const form = document.getElementById('filter-form');
    // NEW: Get the message element
    const filterStatusMessage = document.getElementById('filter-status-message');

    applyFilterBtn.addEventListener('click', function () {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        loader.style.display = 'block';
        reportTbody.style.display = 'none';
        // NEW: Hide the status message while loading
        filterStatusMessage.style.display = 'none';

        const url = `?fetch=true&${params.toString()}`;

        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject('Network response was not ok.'))
            .then(data => {
                reportTbody.innerHTML = '';

                const resultCount = data.tests ? data.tests.length : 0;
                let statusText = '';

                // NEW: Create a dynamic message based on the result count
                if (resultCount > 1) {
                    statusText = `Filtering complete! Found <strong>${resultCount}</strong> records matching your criteria.`;
                } else if (resultCount === 1) {
                    statusText = `Filtering complete! Found <strong>1</strong> record matching your criteria.`;
                } else {
                    statusText = `Filtering complete. <strong>No records</strong> found for the selected filters.`;
                }

                // NEW: Update and show the message bar
                filterStatusMessage.innerHTML = `<i class="fa-solid fa-circle-info"></i> ${statusText}`;
                filterStatusMessage.style.display = 'flex';

                if (resultCount > 0) {
                    data.tests.forEach(test => {
                        const row = `
                            <tr>
                                <td>${escapeHTML(test.assigned_test_date)}</td>
                                <td>${escapeHTML(test.patient_name)}</td>
                                <td>${escapeHTML(test.test_name)}</td>
                                <td>${escapeHTML(test.referred_by)}</td>
                                <td>${escapeHTML(test.test_done_by)}</td>
                                <td>${parseFloat(test.total_amount).toFixed(2)}</td>
                                <td>${parseFloat(test.advance_amount).toFixed(2)}</td>
                                <td>${parseFloat(test.due_amount).toFixed(2)}</td>
                                <td>
                                    <span class="status-pill status-${escapeHTML(test.payment_status).toLowerCase()}">
                                        ${ucfirst(escapeHTML(test.payment_status))}
                                    </span>
                                </td>
                            </tr>
                        `;
                        reportTbody.innerHTML += row;
                    });
                } else {
                    reportTbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">No test records found for the selected filters.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                reportTbody.innerHTML = '<tr><td colspan="9" style="text-align: center;">Failed to load data. Please try again.</td></tr>';

                // NEW: Show an error message in the status bar
                filterStatusMessage.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> An error occurred while fetching data.`;
                filterStatusMessage.style.display = 'flex';
                filterStatusMessage.style.borderColor = '#dc3545'; // Use an error color
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