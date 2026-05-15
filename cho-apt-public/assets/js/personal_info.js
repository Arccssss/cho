// ── Birth Date: Month / Day / Year dropdowns ─────────────────
(function () {
    const monthSel = document.getElementById('dob_month');
    const daySel   = document.getElementById('dob_day');
    const yearSel  = document.getElementById('dob_year');
    const hidden   = document.getElementById('date_of_birth');

    // Populate year dropdown: current year down to 1920
    const currentYear = new Date().getFullYear();
    for (let y = currentYear; y >= 1920; y--) {
        const opt = document.createElement('option');
        opt.value = y;
        opt.textContent = y;
        yearSel.appendChild(opt);
    }

    // Populate days based on selected month/year
    function populateDays() {
        const m    = parseInt(monthSel.value) || 1;
        const y    = parseInt(yearSel.value)  || 2000;
        const prev = daySel.value;
        daySel.innerHTML = '<option value="">Day</option>';
        const daysInMonth = new Date(y, m, 0).getDate();
        for (let d = 1; d <= daysInMonth; d++) {
            const opt = document.createElement('option');
            opt.value = String(d).padStart(2, '0');
            opt.textContent = d;
            daySel.appendChild(opt);
        }
        if (prev && parseInt(prev) <= daysInMonth) daySel.value = prev;
    }

    function updateHidden() {
        const m = monthSel.value;
        const d = daySel.value;
        const y = yearSel.value;
        if (m && d && y) {
            hidden.value = y + '-' + m + '-' + d;
            [monthSel, daySel, yearSel].forEach(s => s.classList.remove('is-invalid'));
            const dobError = document.getElementById('dob-error');
            if(dobError) dobError.style.display = 'none';
        } else {
            hidden.value = '';
        }
    }

    if(monthSel) monthSel.addEventListener('change', function () { populateDays(); updateHidden(); });
    if(yearSel) yearSel.addEventListener('change',  function () { populateDays(); updateHidden(); });
    if(daySel) daySel.addEventListener('change',   updateHidden);

    if(monthSel && yearSel && daySel) populateDays();

    window.validateDOB = function () {
        if (!hidden || !hidden.value) {
            if(monthSel) [monthSel, daySel, yearSel].forEach(s => s.classList.add('is-invalid'));
            const dobError = document.getElementById('dob-error');
            if(dobError) dobError.style.display = 'block';
            if(monthSel) monthSel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
        return true;
    };
})();

// Validate DOB on submit
const bookingForm = document.getElementById('bookingForm');
if(bookingForm) {
    bookingForm.addEventListener('submit', function(e) {
        if (!window.validateDOB()) {
            e.preventDefault();
        }
    });
}

function goToStep2() {
    const checked = document.querySelectorAll('input[name="purpose[]"]:checked');
    if (checked.length === 0) {
        document.getElementById('purpose-error').style.display = 'block';
        return;
    }
    document.getElementById('purpose-error').style.display = 'none';

    // Update step indicators
    document.getElementById('step-dot-1').classList.remove('active');
    document.getElementById('step-dot-1').classList.add('completed');
    document.getElementById('step-dot-1').querySelector('.step-circle').innerHTML = '<i class="fas fa-check" style="font-size:0.8rem"></i>';
    document.querySelector('.step-line').classList.add('completed');
    document.getElementById('step-dot-2').classList.add('active');

    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.goToStep2 = goToStep2; // Make available globally for onclick attributes

function goToStep1() {
    document.getElementById('step-dot-1').classList.add('active');
    document.getElementById('step-dot-1').classList.remove('completed');
    document.getElementById('step-dot-1').querySelector('.step-circle').textContent = '1';
    document.querySelector('.step-line').classList.remove('completed');
    document.getElementById('step-dot-2').classList.remove('active');

    document.getElementById('step2').style.display = 'none';
    document.getElementById('step1').style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.goToStep1 = goToStep1;

// Checkbox card toggle styling
document.querySelectorAll('input[name="purpose[]"]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        const label = this.nextElementSibling;
        if (this.checked) {
            label.style.borderColor = '#0d6efd';
            label.style.background  = '#e8f0fe';
            label.style.color       = '#0d6efd';
        } else {
            label.style.borderColor = '#dee2e6';
            label.style.background  = '#fff';
            label.style.color       = '#495057';
        }
    });
});

// Maiden name: show & require when Female + Married
function toggleMaidenName() {
    const sex         = document.getElementById('sex').value;
    const civilStatus = document.getElementById('civil_status').value;
    const row         = document.getElementById('maiden-name-row');
    const input       = document.getElementById('maiden_name');
    const label       = document.querySelector('label[for="maiden_name"]');

    if (sex === 'Female' && civilStatus === 'Married') {
        row.style.display = 'flex';
        input.required = true;
        label.classList.add('required-field');
    } else {
        row.style.display = 'none';
        input.required = false;
        input.value = '';
        label.classList.remove('required-field');
    }
}

const sexSelect = document.getElementById('sex');
const civilSelect = document.getElementById('civil_status');
if(sexSelect) sexSelect.addEventListener('change', toggleMaidenName);
if(civilSelect) civilSelect.addEventListener('change', toggleMaidenName);

// PhilHealth fields: show number & status type only when member = Yes
function togglePhilhealth() {
    const isMember = document.getElementById('philhealth_member').value === 'Yes';
    document.getElementById('philhealth-status-wrap').style.display = isMember ? 'block' : 'none';
}
window.togglePhilhealth = togglePhilhealth;