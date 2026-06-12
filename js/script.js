// MyMarket-ZA - Main JavaScript

// Confirm before deleting anything
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}

// Show or hide an element by its ID
function toggleElement(id) {
    var el = document.getElementById(id);
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}

// Auto-hide alert messages after 4 seconds
window.addEventListener('load', function () {
    var alerts = document.querySelectorAll('.alert-auto-hide');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function () {
                alert.style.display = 'none';
            }, 500);
        }, 4000);
    });

    // Apply dark mode immediately on load from localStorage
    // This prevents a flash of light mode before PHP can respond
    if (localStorage.getItem('darkMode') === '1') {
        document.body.classList.add('dark');
    }
});

// Simple form validation
function validateForm(formId) {
    var form = document.getElementById(formId);
    if (!form) return true;
    var valid = true;
    var fields = form.querySelectorAll('input[required], textarea[required], select[required]');
    fields.forEach(function (field) {
        field.style.borderColor = '';
        if (!field.value.trim()) {
            field.style.borderColor = '#dc2626';
            valid = false;
        }
    });
    return valid;
}

// Character counter for textareas
function charCounter(textareaId, counterId, maxLength) {
    var textarea = document.getElementById(textareaId);
    var counter  = document.getElementById(counterId);
    if (!textarea || !counter) return;
    textarea.addEventListener('input', function () {
        var remaining = maxLength - textarea.value.length;
        counter.textContent = remaining + ' characters remaining';
        counter.style.color = remaining < 20 ? '#dc2626' : '#6b7280';
    });
}

// Dark mode toggle — saves to server via fetch and localStorage
function toggleDarkMode() {
    var isDark = document.body.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark ? '1' : '0');

    // Save preference to database via fetch
    fetch('/MyMarket-ZA/save-darkmode.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'dark_mode=' + (isDark ? '1' : '0')
    });
}

// Show loading state on Add to Cart button
document.addEventListener('DOMContentLoaded', function () {
    var cartForms = document.querySelectorAll('form[action="cart.php"]');
    cartForms.forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.textContent = 'Adding...';
                btn.disabled = true;
            }
        });
    });
});
