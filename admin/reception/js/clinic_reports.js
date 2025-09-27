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

                // The key change is here: using 'data.registrations'
                const resultCount = data.registrations ? data.registrations.length : 0;
                let statusText = '';

                // NEW: Create a dynamic message based on the result count
                if (resultCount > 1) {
                    statusText = `Filtering complete! Found <strong>${resultCount}</strong> registrations matching your criteria.`;
                } else if (resultCount === 1) {
                    statusText = `Filtering complete! Found <strong>1</strong> registration matching your criteria.`;
                } else {
                    statusText = `Filtering complete. <strong>No registrations</strong> found for the selected filters.`;
                }

                // NEW: Update and show the message bar
                filterStatusMessage.innerHTML = `<i class="fa-solid fa-circle-info"></i> ${statusText}`;
                filterStatusMessage.style.display = 'flex';

                if (resultCount > 0) {
                    data.registrations.forEach(reg => {
                        const row = `
                                <tr>
                                    <td>${escapeHTML(reg.appointment_date)}</td>
                                    <td>${escapeHTML(reg.patient_name)}</td>
                                    <td>${escapeHTML(reg.age)}</td>
                                    <td>${escapeHTML(reg.gender)}</td>
                                    <td>${ucfirst(escapeHTML(reg.chief_complain).replace(/_/g, ' '))}</td>
                                    <td>${ucfirst(escapeHTML(reg.referralSource).replace(/_/g, ' '))}</td>
                                    <td>${ucfirst(escapeHTML(reg.consultation_type).replace(/-/g, ' '))}</td>
                                    <td>${parseFloat(reg.consultation_amount).toFixed(2)}</td>
                                    <td>${ucfirst(escapeHTML(reg.payment_method))}</td>
                                    <td>
                                        <span class="status-pill status-${escapeHTML(reg.status).toLowerCase()}">
                                            ${escapeHTML(reg.status)}
                                        </span>
                                    </td>
                                </tr>
                            `;
                        reportTbody.innerHTML += row;
                    });
                } else {
                    reportTbody.innerHTML = '<tr><td colspan="10" style="text-align: center;">No registration records found for the selected filters.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                reportTbody.innerHTML = '<tr><td colspan="10" style="text-align: center;">Failed to load data. Please try again.</td></tr>';

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
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }

    function ucfirst(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }
});