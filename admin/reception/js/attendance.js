(function () {
    // --- Get the initial date from the HTML data attribute ---
    // MODIFIED: This is the new way to get the date from the PHP file.
    const pageDateElement = document.body;
    const DEFAULT_PAGE_DATE = pageDateElement.dataset.pageDate || new Date().toISOString().slice(0, 10);

    // DOM references
    const overlay = document.getElementById('attendance-drawer-overlay');
    const panel = document.getElementById('attendance-drawer-panel');
    const titleEl = document.getElementById('attendance-drawer-title');
    const closeBtn = document.getElementById('attendance-drawer-close');
    const calendarEl = document.getElementById('attendance-calendar');
    const monthLabel = document.getElementById('attendance-month-label');
    const prevBtn = document.getElementById('month-prev');
    const nextBtn = document.getElementById('month-next');
    const detailsBox = document.getElementById('attendance-date-details');
    const detailsDateEl = document.getElementById('attendance-details-date');
    const detailsTextEl = document.getElementById('attendance-details-text');

    let attendanceSet = new Set(); // YYYY-MM-DD strings
    let attendanceMap = {}; // optional: { 'YYYY-MM-DD': {...remarks...} }
    let currentYear, currentMonth; // numeric (month 0-11)

    // Helpers
    const formatMonthLabel = (y, m) => {
        const d = new Date(y, m, 1);
        return d.toLocaleString('default', {
            month: 'long',
            year: 'numeric'
        });
    };

    const pad = (n) => (n < 10 ? '0' + n : '' + n);

    const isoDate = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;

    const openDrawer = () => {
        overlay.style.display = 'flex';
        // force reflow then open
        panel.offsetHeight;
        panel.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
    };

    const closeDrawer = () => {
        panel.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        // wait for transition then hide overlay
        setTimeout(() => {
            overlay.style.display = 'none';
            // reset details
            detailsBox.style.display = 'none';
            detailsTextEl.textContent = 'Click a date to see details here.';
        }, 320);
    };

    // Render calendar for given year/month (month: 0-11)
    const renderCalendar = (year, month) => {
        calendarEl.innerHTML = ''; // clear
        monthLabel.textContent = formatMonthLabel(year, month);

        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        for (let i = 0; i < 7; i++) {
            const dh = document.createElement('div');
            dh.className = 'day-head';
            dh.textContent = days[i];
            calendarEl.appendChild(dh);
        }

        const first = new Date(year, month, 1);
        const firstIndex = first.getDay();
        const lastDay = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < firstIndex; i++) {
            const empty = document.createElement('div');
            empty.className = 'date-cell empty';
            calendarEl.appendChild(empty);
        }

        for (let day = 1; day <= lastDay; day++) {
            const cell = document.createElement('div');
            cell.className = 'date-cell';
            const dateStr = isoDate(year, month, day);
            cell.dataset.date = dateStr;

            const num = document.createElement('div');
            num.className = 'date-number';
            num.textContent = day;
            cell.appendChild(num);

            const sub = document.createElement('div');
            sub.className = 'date-sub';
            const weekday = new Date(year, month, day).toLocaleString('default', {
                weekday: 'short'
            });
            sub.textContent = weekday;
            cell.appendChild(sub);

            if (attendanceSet.has(dateStr)) {
                cell.classList.add('attended');
            }

            cell.addEventListener('click', () => {
                if (cell.classList.contains('empty')) return;

                const prev = calendarEl.querySelector('.date-cell.selected');
                if (prev) prev.classList.remove('selected');
                cell.classList.add('selected');

                detailsBox.style.display = 'block';
                const clickedDate = new Date(year, month, day);
                detailsDateEl.textContent = clickedDate.toLocaleDateString('en-GB', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });

                if (attendanceMap && attendanceMap[dateStr]) {
                    detailsTextEl.textContent = `Status: Present\nRemarks: ${attendanceMap[dateStr].remarks || attendanceMap[dateStr].note || '—'}`;
                } else {
                    if (attendanceSet.has(dateStr)) {
                        detailsTextEl.textContent = 'Status: Present — attendance recorded for this date.';
                    } else {
                        detailsTextEl.textContent = 'Status: Absent — no attendance record for this date.';
                    }
                }
            });

            calendarEl.appendChild(cell);
        }
    };

    // Fetch attendance history for a patient and open drawer
    const openDrawerWithHistory = async (patientId) => {
        try {
            calendarEl.innerHTML = '<div style="grid-column:1/-1;padding:12px;color:#6b7280;">Loading attendance history…</div>';
            detailsBox.style.display = 'none';
            titleEl.textContent = 'Loading…';
            openDrawer();

            const response = await fetch(`../api/get_attendance_history.php?id=${encodeURIComponent(patientId)}`);
            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();

            if (!data.success) {
                calendarEl.innerHTML = `<div style="grid-column:1/-1;padding:12px;color:#b91c1c;">Error: ${data.message || 'Unable to load history'}</div>`;
                titleEl.textContent = 'Attendance Calendar';
                return;
            }

            const dates = Array.isArray(data.attendance_dates) ? data.attendance_dates : [];
            attendanceSet = new Set(dates);
            attendanceMap = data.attendance_map || {};

            titleEl.textContent = `History — ${data.patient_name || ''}`;

            const [prefY, prefM] = DEFAULT_PAGE_DATE.split('-').map(Number);
            currentYear = prefY || new Date().getFullYear();
            currentMonth = (prefM ? prefM - 1 : new Date().getMonth());

            renderCalendar(currentYear, currentMonth);

        } catch (err) {
            console.error('Attendance drawer error', err);
            calendarEl.innerHTML = `<div style="grid-column:1/-1;padding:12px;color:#b91c1c;">Could not fetch attendance history.</div>`;
            titleEl.textContent = 'Attendance Calendar';
        }
    };

    // This function needs to be exposed to be callable from other scripts or the main page
    // if buttons are added dynamically. For now, we assume they exist on page load.
    const initializeAttendanceButtons = () => {
        document.querySelectorAll('.view-attendance-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const pid = this.dataset.id;
                openDrawerWithHistory(pid);
            });
        });
    };

    // Initial setup
    const init = () => {
        initializeAttendanceButtons();

        prevBtn.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentYear, currentMonth);
        });
        nextBtn.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentYear, currentMonth);
        });

        closeBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeDrawer();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.style.display === 'flex') closeDrawer();
        });

        detailsBox.style.display = 'none';
    };

    // Run the initialization once the DOM is fully loaded.
    document.addEventListener('DOMContentLoaded', init);

})();
