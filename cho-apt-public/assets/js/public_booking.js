// Uses window.serverTodayStr defined in the HTML
const serverToday = new Date(window.serverTodayStr + 'T00:00:00');
let currentYear  = serverToday.getFullYear();
let currentMonth = serverToday.getMonth(); // 0-indexed
let selectedDateStr = null;
window.bookings = null;

function renderCalendar() {
    const year  = currentYear;
    const month = currentMonth;

    const monthNames = ['January','February','March','April','May','June',
                        'July','August','September','October','November','December'];
    document.getElementById('current-month').textContent = monthNames[month] + ' ' + year;

    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';

    ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => {
        const h = document.createElement('div');
        h.className = 'calendar-day-header';
        h.textContent = d;
        grid.appendChild(h);
    });

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDayOfWeek = new Date(year, month, 1).getDay();

    for (let i = 0; i < firstDayOfWeek; i++) {
        const e = document.createElement('div');
        e.className = 'calendar-day';
        e.style.background = '#fafafa';
        grid.appendChild(e);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const el = document.createElement('div');
        el.className = 'calendar-day';

        const mm      = String(month + 1).padStart(2, '0');
        const dd      = String(day).padStart(2, '0');
        const dateStr = year + '-' + mm + '-' + dd;

        const cellDate   = new Date(year, month, day);
        const dow        = cellDate.getDay(); 
        const todayStr   = serverToday.getFullYear() + '-' +
                           String(serverToday.getMonth()+1).padStart(2,'0') + '-' +
                           String(serverToday.getDate()).padStart(2,'0');
        const isPast     = dateStr < todayStr;
        const isToday    = dateStr === todayStr;
        const isWeekend  = (dow === 0 || dow === 6);

        const numSpan = document.createElement('span');
        numSpan.className = 'day-number';
        numSpan.textContent = day;
        el.appendChild(numSpan);

        if (isWeekend) {
            el.classList.add('weekend');
            const lbl = document.createElement('span');
            lbl.style.cssText = 'font-size:9px;color:#ccc;font-weight:600;text-transform:uppercase;';
            lbl.textContent = dow === 0 ? 'Sunday' : 'Saturday';
            el.appendChild(lbl);
        } else if (isPast) {
            el.classList.add('past');
        } else {
            const data    = window.bookings && window.bookings.bookings && window.bookings.bookings[dateStr];
            const amSlots = data ? data.AM : 50;
            const pmSlots = data ? data.PM : 50;
            const total   = amSlots + pmSlots;

            if (total === 0) {
                el.classList.add('fully-booked');
                const lbl = document.createElement('span');
                lbl.className = 'fully-booked-label';
                lbl.textContent = 'Fully Booked';
                el.appendChild(lbl);
            } else {
                el.classList.add('available');
                const btn = document.createElement('button');
                btn.className = 'book-btn';
                btn.textContent = 'Book';
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    pickDate(dateStr, total);
                });
                el.appendChild(btn);
                el.addEventListener('click', function() { pickDate(dateStr, total); });
            }
        }

        if (isToday) el.classList.add('today');
        if (dateStr === selectedDateStr) el.classList.add('selected');

        grid.appendChild(el);
    }
}

function changeMonth(dir) {
    currentMonth += dir;
    if (currentMonth > 11) { currentMonth = 0; currentYear++; }
    if (currentMonth < 0)  { currentMonth = 11; currentYear--; }
    fetchBookings(currentYear, currentMonth + 1);
}

function fetchBookings(year, month) {
    fetch('get_month_bookings.php?year=' + year + '&month=' + month)
        .then(r => r.json())
        .then(data => { window.bookings = data; renderCalendar(); })
        .catch(() => renderCalendar());
}

function pickDate(dateStr, totalSlots) {
    document.querySelectorAll('.calendar-day.selected').forEach(el => el.classList.remove('selected'));
    document.querySelectorAll('.calendar-day.available').forEach(cell => {
        const n = cell.querySelector('.day-number');
        const mm = String(currentMonth + 1).padStart(2,'0');
        const dd = String(parseInt(n ? n.textContent : 0)).padStart(2,'0');
        if (n && (currentYear + '-' + mm + '-' + dd) === dateStr) cell.classList.add('selected');
    });

    selectedDateStr = dateStr;
    const parts = dateStr.split('-');
    const d = new Date(parseInt(parts[0]), parseInt(parts[1])-1, parseInt(parts[2]));
    document.getElementById('date-display').textContent = d.toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    const panel = document.getElementById('booking-panel');
    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function proceedToBooking() {
    if (!selectedDateStr) return;
    document.getElementById('selected-date-input').value = selectedDateStr;
    document.getElementById('date-form').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    fetchBookings(currentYear, currentMonth + 1);
});