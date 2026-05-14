// Tab switching
function switchTab(tab) {
    document.getElementById('table-consent').style.display = tab === 'consent' ? 'block' : 'none';
    document.getElementById('table-itr').style.display    = tab === 'itr'     ? 'block' : 'none';
    document.getElementById('tab-consent').classList.toggle('active', tab === 'consent');
    document.getElementById('tab-itr').classList.toggle('active', tab === 'itr');
    
    const search = document.getElementById('globalSearch');
    search.value = '';
    search.placeholder = tab === 'consent'
        ? 'Search by patient name, service type, creator, or date...'
        : 'Search by patient name, sex, age, creator, or date...';
        
    document.querySelectorAll('#formsTableBody tr[data-searchable]').forEach(r => r.style.display = '');
    document.querySelectorAll('#itrTableBody tr[data-itr-searchable]').forEach(r => r.style.display = '');
}

// Modal and Form Functions
function editForm(formId) {
    window.location.href = 'admin_dashboard.php?edit_form=' + formId;
}

function deleteForm(formId) {
    if (confirm('Are you sure you want to delete this consent form? This action cannot be undone.')) {
        window.location.href = 'admin_dashboard.php?delete_form=' + formId;
    }
}

function deleteITR(id) {
    if (confirm('Are you sure you want to delete this ITR record? This cannot be undone.')) {
        window.location.href = 'delete_enrollment.php?id=' + id;
    }
}

function closeEditForm() {
    document.getElementById('editFormModal').style.display = 'none';
}

// Live Date and Time
function updateDateTime() {
    const now = new Date();
    const options = {
        weekday: 'long', year: 'numeric', month: 'long', 
        day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true
    };
    const dateTimeDisplay = document.getElementById('dateTimeDisplay');
    if (dateTimeDisplay) {
        dateTimeDisplay.textContent = now.toLocaleDateString('en-US', options);
    }
}

// Wait for the HTML to fully load before attaching clicks!
document.addEventListener('DOMContentLoaded', function() {
    
    // Service Card Quick Filter Functionality
    const serviceCards = document.querySelectorAll('.service-card');
    
    serviceCards.forEach(card => {
        card.addEventListener('click', function() {
            const serviceType = this.dataset.service;
            
            // Update active state (colors the card orange)
            serviceCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            // Filter the table
            filterTableByService(serviceType);
            
            // Update search input to reflect filter
            const searchInput = document.getElementById('globalSearch');
            if (serviceType === 'all') {
                searchInput.value = '';
            } else {
                const serviceName = this.querySelector('.service-name').textContent;
                searchInput.value = serviceName;
            }
            searchInput.dispatchEvent(new Event('input'));
        });
    });

    // Make sure your filterTableByService function is also inside here!
    function filterTableByService(serviceType) {
        const tableBody = document.getElementById('formsTableBody');
        const noDataRow = document.getElementById('noDataRow');
        const noSearchResultsRow = document.getElementById('noSearchResults');

            const allRows = Array.from(tableBody.querySelectorAll('tr[data-searchable="true"]'));
            let visibleCount = 0;
                
            allRows.forEach(row => {
                if (serviceType === 'all') {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    const serviceCell = row.querySelector('.service-type');
                    const serviceText = serviceCell ? serviceCell.textContent : '';
                    
                    // Split comma-separated services and check if any match the filter
                    const individualServices = serviceText.split(',').map(s => s.trim().toLowerCase());
                    const filterService = serviceType.replace(/-/g, ' ').toLowerCase();
                    
                    if (individualServices.includes(filterService)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
            
            // Show appropriate message
            // if (serviceType !== 'all' && visibleCount === 0) {
            //     noDataRow.style.display = 'none';
            //     noSearchResultsRow.style.display = '';
            // } else {
            //     noDataRow.style.display = '';
            //     noSearchResultsRow.style.display = 'none';
            // }
        }
    });

updateDateTime();
setInterval(updateDateTime, 1000);

// (Note: You would also paste the rest of your long Search and Notification JS here)
// Copy everything from line 297 down to line 554 into this file.