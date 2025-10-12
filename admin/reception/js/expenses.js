document.addEventListener('DOMContentLoaded', function () {
    // --- Modal Handling for "Add Expense" ---
    const addExpenseModal = document.getElementById('expense-modal');
    const addExpenseBtn = document.getElementById('add-expense-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');

    if (addExpenseBtn) {
        addExpenseBtn.onclick = () => addExpenseModal.style.display = 'flex';
    }
    if (closeModalBtn) {
        closeModalBtn.onclick = () => addExpenseModal.style.display = 'none';
    }

    // --- Modal Handling for "Upload Bill" ---
    const uploadModal = document.getElementById('upload-modal');
    const closeUploadModalBtn = document.getElementById('close-upload-modal-btn');
    const uploadExpenseIdInput = document.getElementById('upload_expense_id');
    document.querySelectorAll('.upload-btn').forEach(button => {
        button.addEventListener('click', function () {
            const expenseId = this.getAttribute('data-expense-id');
            if (uploadExpenseIdInput) {
                uploadExpenseIdInput.value = expenseId;
                uploadModal.style.display = 'flex';
            }
        });
    });

    if (closeUploadModalBtn) {
        closeUploadModalBtn.onclick = () => uploadModal.style.display = 'none';
    }

    // --- Modal Handling for "View Details" ---
    const viewModal = document.getElementById('view-modal');
    const closeViewModalBtn = document.getElementById('close-view-modal-btn');
    const viewModalBody = document.getElementById('view-modal-body');

    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function () {
            const expenseData = JSON.parse(this.getAttribute('data-expense-details'));
            populateViewModal(expenseData);
            viewModal.style.display = 'flex';
        });
    });

    if (closeViewModalBtn) {
        closeViewModalBtn.onclick = () => viewModal.style.display = 'none';
    }

    // --- NEW: Modal Handling for Image Preview ---
    const imagePreviewModal = document.getElementById('image-preview-modal');
    const closeImageModalBtn = document.getElementById('close-image-modal-btn');
    const modalImageContent = document.getElementById('modal-image-content');

    if (closeImageModalBtn) {
        closeImageModalBtn.onclick = () => imagePreviewModal.style.display = 'none';
    }

    // --- Close any modal when clicking on the overlay ---
    window.onclick = function (event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    };

    // --- Helper function to populate the view modal ---
    function populateViewModal(data) {
        if (!viewModalBody) return;

        const date = new Date(data.expense_date);
        const formattedDate = date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });

        Object.keys(data).forEach(key => { data[key] = data[key] === null ? '' : data[key]; });

        let imageHtml = '';
        if (data.bill_image_path) {
            const imageUrl = `../../${data.bill_image_path}`;
            // CHANGED: Removed the <a> tag. We'll add a click event later.
            imageHtml = `
                <div class="view-section">
                    <h4>Uploaded Bill</h4>
                    <img src="${imageUrl}" alt="Bill Image" class="bill-image-preview">
                </div>
            `;
        } else {
            imageHtml = `
                <div class="view-section">
                    <h4>Uploaded Bill</h4>
                    <p>No bill has been uploaded for this expense.</p>
                </div>
            `;
        }

        viewModalBody.innerHTML = `
            <div class="view-grid">
                <div class="view-item"><strong>Voucher No:</strong><span>${data.voucher_no}</span></div>
                <div class="view-item"><strong>Expense ID:</strong><span>${data.expense_id}</span></div>
                <div class="view-item"><strong>Date:</strong><span>${formattedDate}</span></div>
                <div class="view-item"><strong>Paid To:</strong><span>${data.paid_to}</span></div>
                <div class="view-item"><strong>Done By:</strong><span>${data.expense_done_by}</span></div>
                <div class="view-item"><strong>For:</strong><span>${data.expense_for}</span></div>
                <div class="view-item"><strong>Amount:</strong><span>₹ ${parseFloat(data.amount).toFixed(2)}</span></div>
                <div class="view-item"><strong>Status:</strong><span class="status-pill status-${data.status.toLowerCase()}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span></div>
                <div class="view-item"><strong>Payment Method:</strong><span>${data.payment_method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></div>
                <div class="view-item full-width"><strong>Amount in Words:</strong><span>${data.amount_in_words}</span></div>
                <div class="view-item full-width"><strong>Description:</strong><p>${data.description}</p></div>
                <div class="view-item full-width"><strong>Created By:</strong><span>${data.creator_username || 'N/A'} at ${new Date(data.created_at).toLocaleString()}</span></div>
            </div>
            ${imageHtml}
        `;

        // --- NEW: Add click listener to the newly created image preview ---
        const previewImage = viewModalBody.querySelector('.bill-image-preview');
        if (previewImage) {
            previewImage.addEventListener('click', function () {
                if (modalImageContent && imagePreviewModal) {
                    modalImageContent.src = this.src; // Set the large image source
                    imagePreviewModal.style.display = 'flex'; // Show the modal
                }
            });
        }
    }

    // --- Amount to Words Converter ---
    const amountInput = document.getElementById('amount');
    const amountInWordsInput = document.getElementById('amount_in_words');
    if (amountInput && amountInWordsInput) {
        amountInput.addEventListener('input', function () {
            amountInWordsInput.value = convertNumberToWords(this.value) + ' Only';
        });
    }

    function convertNumberToWords(num) {
        const a = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        const b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        const s = ['', 'thousand', 'lakh', 'crore'];
        let number = parseFloat(num);
        if (isNaN(number) || number === 0) return 'Zero';
        let rupees = Math.floor(number);
        let paisa = Math.round((number - rupees) * 100);
        let str = '';
        let i = 0;
        while (rupees > 0) {
            let chunk = (i === 0) ? rupees % 1000 : rupees % 100;
            rupees = (i === 0) ? Math.floor(rupees / 1000) : Math.floor(rupees / 100);
            if (chunk) {
                str = convertChunk(chunk) + (s[i] ? ' ' + s[i] : '') + ' ' + str;
            }
            i++;
        }
        str = 'Rupees ' + str.trim();
        if (paisa > 0) {
            str += ' and ' + convertChunk(paisa) + ' Paisa';
        }
        return str.replace(/\s\s+/g, ' ').trim();
    }

    function convertChunk(n) {
        const a = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        const b = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        if (n < 20) return a[n];
        if (n < 100) return b[Math.floor(n / 10)] + (n % 10 !== 0 ? ' ' + a[n % 10] : '');
        return a[Math.floor(n / 100)] + ' hundred' + (n % 100 !== 0 ? ' ' + convertChunk(n % 100) : '');
    }
});
