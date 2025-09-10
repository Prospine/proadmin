// --- TOAST NOTIFICATION SCRIPT ---
// This function is what was missing!
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            container.removeChild(toast);
        }, 500);
    }, 5000);
}

const quickBtn = document.getElementById('quickBtn');
const testBtn = document.getElementById('testBtn');
const quickTable = document.getElementById('quickTable');
const testTable = document.getElementById('testTable');

quickBtn.addEventListener('click', () => {
    quickBtn.classList.add('active');
    testBtn.classList.remove('active');
    quickTable.classList.remove('hidden');
    testTable.classList.add('hidden');
});

testBtn.addEventListener('click', () => {
    testBtn.classList.add('active');
    quickBtn.classList.remove('active');
    testTable.classList.remove('hidden');
    quickTable.classList.add('hidden');
});


document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("table select").forEach(select => {
        select.addEventListener("change", async function () {
            const id = this.dataset.id;
            const type = this.dataset.type;
            const status = this.value;

            try {
                const res = await fetch("../api/update_inquiry_status.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        id,
                        type,
                        status
                    })
                });

                const data = await res.json();

                if (data.success) {
                    // Update pill text + color
                    const pill = this.closest("tr").querySelector(".pill");
                    pill.textContent = status;

                    pill.className = "pill"; // reset
                    if (status.toLowerCase() === "visited") {
                        pill.classList.add("visited");
                    } else if (status.toLowerCase() === "cancelled") {
                        pill.classList.add("cancelled");
                    } else {
                        pill.classList.add("pending");
                    }
                } else {
                    alert(data.message || "Update failed");
                }
            } catch (err) {
                console.error("Error:", err);
                alert("Network error");
            }
        });
    });
});