// Main JavaScript for IT Inventory Management System

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.btn-danger').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        
        $(this).find('input[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
    
    // Real-time search in tables
    $('.table-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        const table = $(this).closest('.card').find('table tbody');
        
        table.find('tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.includes(searchTerm));
        });
    });
    
    // Date pickers
    $('input[type="date"]').each(function() {
        if (!$(this).val()) {
            $(this).attr('placeholder', 'YYYY-MM-DD');
        }
    });
    
    // Status color coding
    $('.status-badge').each(function() {
        const status = $(this).text().toLowerCase();
        switch(status) {
            case 'active':
                $(this).addClass('bg-success');
                break;
            case 'excess':
                $(this).addClass('bg-warning');
                break;
            case 'disposed':
                $(this).addClass('bg-danger');
                break;
            case 'maintenance':
                $(this).addClass('bg-info');
                break;
        }
    });
    
    // Loading states for buttons
    $('form').on('submit', function() {
        $(this).find('button[type="submit"]').html('<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...').prop('disabled', true);
    });
    
    // Export confirmation
    $('button[name="export"]').on('click', function() {
        return confirm('This will download all inventory data as CSV. Continue?');
    });
});

// Utility functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('.container-fluid').prepend(notification);
    
    setTimeout(() => {
        notification.alert('close');
    }, 5000);
}