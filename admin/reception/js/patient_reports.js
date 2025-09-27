document.addEventListener('DOMContentLoaded', function () {
    const applyFilterBtn = document.getElementById('apply-filter-btn');
    const reportTbody = document.getElementById('report-tbody');
    const loader = document.getElementById('loader');
    const form = document.getElementById('filter-form');
    // This line was missing
    const filterStatusMessage = document.getElementById('filter-status-message');

    applyFilterBtn.addEventListener('click', function () {
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        loader.style.display = 'block';
        reportTbody.style.display = 'none';
        // This line was missing
        filterStatusMessage.style.display = 'none';

        const url = `?fetch=true&${params.toString()}`;

        fetch(url)
            .then(response => response.ok ? response.json() : Promise.reject('Network response was not ok.'))
            .then(data => {
                reportTbody.innerHTML = '';

                const resultCount = data.patients ? data.patients.length : 0;
                let statusText = '';

                // This whole block of logic was missing
                if (resultCount > 1) {
                    statusText = `Filtering complete! Found <strong>${resultCount}</strong> patient records matching your criteria.`;
                } else if (resultCount === 1) {
                    statusText = `Filtering complete! Found <strong>1</strong> patient record matching your criteria.`;
                } else {
                    statusText = `Filtering complete. <strong>No patient records</strong> found for the selected filters.`;
                }

                // And these lines to show the message were missing
                filterStatusMessage.innerHTML = `<i class="fa-solid fa-circle-info"></i> ${statusText}`;
                filterStatusMessage.style.display = 'flex';

                if (resultCount > 0) {
                    data.patients.forEach(patient => {
                        const row = `
                                <tr>
                                    <td>${escapeHTML(patient.patient_id)}</td>
                                    <td>${escapeHTML(patient.patient_name)}</td>
                                    <td>${escapeHTML(patient.assigned_doctor)}</td>
                                    <td>${ucfirst(escapeHTML(patient.treatment_type))}</td>
                                    <td>${parseFloat(patient.total_amount).toFixed(2)}</td>
                                    <td>${parseFloat(patient.advance_payment).toFixed(2)}</td>
                                    <td>${parseFloat(patient.due_amount).toFixed(2)}</td>
                                    <td>${escapeHTML(patient.start_date)}</td>
                                    <td>${escapeHTML(patient.end_date)}</td>
                                    <td>
                                        <span class="status-pill status-${escapeHTML(patient.status).toLowerCase()}">
                                            ${ucfirst(escapeHTML(patient.status))}
                                        </span>
                                    </td>
                                </tr>
                            `;
                        reportTbody.innerHTML += row;
                    });
                } else {
                    reportTbody.innerHTML = '<tr><td colspan="10" style="text-align: center;">No patient records found for the selected filters.</td></tr>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                reportTbody.innerHTML = '<tr><td colspan="10" style="text-align: center;">Failed to load data. Please try again.</td></tr>';

                // This error handling for the message was missing
                filterStatusMessage.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> An error occurred while fetching data.`;
                filterStatusMessage.style.display = 'flex';
                filterStatusMessage.style.borderColor = '#dc3545';
            })
            .finally(() => {
                loader.style.display = 'none';
                reportTbody.style.display = '';
            });
    });

    // Helper functions
    function escapeHTML(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function (match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match];
        });
    }

    function ucfirst(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }
});