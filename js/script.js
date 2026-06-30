function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this?');
}

function toggleElement(id) {
    var el = document.getElementById(id);
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}

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

    if (localStorage.getItem('darkMode') === '1') {
        document.body.classList.add('dark');
    }
});

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
